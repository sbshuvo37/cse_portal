<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
Auth::requireRole('student');

$studentModel    = new Student();
$courseModel     = new CourseModel();
$discussionModel = new DiscussionModel();
$uid             = Auth::userId();

$student = $studentModel->findByUserId($uid);
$batchId = $student['batch_id'];
$myCourses = $batchId ? $courseModel->getCoursesForBatch($batchId) : [];
$myCourseIds = array_column($myCourses, 'course_id');

$db = Database::getInstance()->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'post_message') {
        $groupId = intval($_POST['group_id'] ?? 0);
        $body    = trim($_POST['body'] ?? '');
        $hasFile = !empty($_FILES['attachment']['name']);

        $group = $discussionModel->findGroupById($groupId);
        
        // ১ নম্বর পরিবর্তন: কাস্টম মেম্বারশিপ ভ্যালিডেশন চেক [Point 6]
        $isExplicitMember = false;
        if ($group) {
            $stmt = $db->prepare("SELECT 1 FROM discussion_group_members WHERE group_id = ? AND user_id = ?");
            $stmt->execute([$groupId, $uid]);
            $isExplicitMember = (bool)$stmt->fetch();
        }

        $isAuthorized = $isExplicitMember || ($group && $discussionModel->studentCanAccessGroup($group, $batchId) && in_array((int)$group['course_id'], $myCourseIds));

        if (!$group || !$isAuthorized) {
            Helper::setFlash('danger', 'Unauthorized access.');
        } elseif (empty($body) && !$hasFile) {
            Helper::setFlash('danger', 'Write something or attach a file.');
        } else {
            $attachment = null;
            if ($hasFile) {
                $uploader = new FileUpload('resources');
                $res = $uploader->upload($_FILES['attachment']);
                if ($res['success']) $attachment = $res['path'];
            }
            $discussionModel->postMessage($groupId, $uid, $body !== '' ? $body : null, $attachment);
        }
        Helper::redirect('student/discussions.php?group=' . $groupId);
    }
}

// ২ নম্বর পরিবর্তন: গ্রুপের তালিকায় শিক্ষার্থীর ID পাস করা হলো [Point 6]
$groups = $batchId ? $discussionModel->groupsForStudentBatch($batchId, $myCourseIds, $uid) : [];

$activeGroupId = intval($_GET['group'] ?? 0);
$activeGroup   = null;
$messages      = [];

if ($activeGroupId) {
    $candidate = $discussionModel->findGroupById($activeGroupId);
    
    // ৩ নম্বর পরিবর্তন: কাস্টম মেম্বারশিপে এড থাকা শিক্ষার্থীরাও চ্যাট পেজ লোড করতে পারবেন [Point 6]
    $isAuthorized = false;
    if ($candidate) {
        $stmt = $db->prepare("SELECT 1 FROM discussion_group_members WHERE group_id = ? AND user_id = ?");
        $stmt->execute([$candidate['group_id'], $uid]);
        $isExplicitMember = (bool)$stmt->fetch();
        
        if ($isExplicitMember || ($discussionModel->studentCanAccessGroup($candidate, $batchId) && in_array((int)$candidate['course_id'], $myCourseIds))) {
            $isAuthorized = true;
        }
    }

    if ($isAuthorized) {
        $activeGroup = $candidate;
        $messages = $discussionModel->getMessages($activeGroupId);
    }
}

$pageTitle   = 'Discussion';
$currentPage = 'Discussion';
include '../includes/header.php';
?>
<div class="portal-layout">
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
<?php include '../includes/navbar.php'; ?>
<div class="page-content">

<?php $flash = Helper::getFlash(); if ($flash): ?><div class="alert alert-<?= $flash['type'] ?>" data-auto-dismiss><?= htmlspecialchars($flash['message']) ?></div><?php endif; ?>

<?php if ($activeGroup): ?>
<div class="page-header">
    <div><h2>💡 <?= htmlspecialchars($activeGroup['course_code'] ? $activeGroup['course_code'].' — '.$activeGroup['course_title'] : 'Custom Admin Group') ?></h2>
    <div class="page-subtitle"><?= htmlspecialchars($activeGroup['batch_name'] ?? 'Custom Group') ?> · Group Discussion</div></div>
    <a href="discussions.php" class="btn btn-outline">← Back to Groups</a>
</div>

<div class="group-chat-panel">
    <div class="group-chat-feed">
        <?php if (empty($messages)): ?>
            <div class="empty-state"><div class="empty-icon">💬</div><p>No messages yet. Start the conversation!</p></div>
        <?php else: foreach ($messages as $m): ?>
        <?php $isMe = (int)$m['user_id'] === $uid; ?>
        <div class="group-msg-row <?= $isMe?'own':'' ?>">
            <div class="group-msg-avatar"><?= Helper::initials($m['author']) ?></div>
            <div class="group-msg-bubble">
                <div class="group-msg-author"><?= htmlspecialchars($m['author']) ?> <span class="badge badge-gray" style="font-size:0.6rem"><?= htmlspecialchars($m['author_role']) ?></span></div>
                <div class="group-msg-content">
                    <?php if (!empty($m['body'])): ?><?= nl2br(htmlspecialchars($m['body'])) ?><?php endif; ?>
                    <?php if ($m['attachment']): ?><a href="<?= UPLOAD_URL . htmlspecialchars($m['attachment']) ?>" target="_blank" class="chat-attachment-link">📎 File</a><?php endif; ?>
                </div>
                <div class="group-msg-time"><?= Helper::formatDate($m['created_at'], 'M j, g:i A') ?></div>
            </div>
        </div>
        <?php endforeach; endif; ?>
    </div>
    <form method="POST" enctype="multipart/form-data" class="group-chat-input" id="groupChatForm">
        <input type="hidden" name="action" value="post_message">
        <input type="hidden" name="group_id" value="<?= $activeGroup['group_id'] ?>">
        <input type="text" name="body" id="groupMsgInput" class="form-control" placeholder="Message the group...">
        <label class="btn btn-sm btn-outline" style="cursor:pointer">📎<input type="file" name="attachment" id="groupAttachInput" style="display:none"></label>
        <button type="submit" class="btn btn-primary">Send</button>
    </form>
</div>

<script>
(() => {
    const fileInput = document.getElementById('groupAttachInput');
    const nameDisplay = document.getElementById('groupAttachName');
    const msgInput = document.getElementById('groupMsgInput');
    const form = document.getElementById('groupChatForm');
    fileInput.addEventListener('change', () => { nameDisplay.textContent = fileInput.files[0] ? '📎 ' + fileInput.files[0].name : ''; });
    const feed = document.querySelector('.group-chat-feed');
    if (feed) feed.scrollTop = feed.scrollHeight;
})();
</script>

<?php else: ?>
<div class="page-header">
    <div><h2>💡 Discussion Groups</h2><div class="page-subtitle">Group chats created by your teachers for your courses</div></div>
</div>

<div style="margin-bottom: 18px; max-width: 320px;">
    <input type="text" id="groupSearchInput" class="form-control" placeholder="🔍 Search groups..." oninput="filterGroupsList()">
</div>

<div class="group-list" id="groupsContainer">
<?php foreach ($groups as $g): ?>
<a href="?group=<?= $g['group_id'] ?>" class="group-card" style="border: 1px solid var(--border); border-radius: var(--radius); padding: 18px; margin-bottom: 12px; background:white; display:flex; align-items:center;">
    <div class="group-icon">💬</div>
    <div class="group-info" style="margin-left: 14px;">
        <!-- ৪ নম্বর পরিবর্তন: কাস্টম গ্রুপ টাইটেল প্রপার্টি হ্যান্ডেল করা হয়েছে -->
        <div class="group-name" style="font-weight:700; color:var(--primary-dark);"><?= htmlspecialchars($g['title'] ?: ($g['course_code'] ? $g['course_code'].' — '.$g['course_title'] : 'Custom Admin Group')) ?></div>
        <div class="group-meta" style="font-size:0.8rem; color:var(--text-muted); margin-top:4px;">
            <span class="badge badge-primary"><?= htmlspecialchars($g['batch_name'] ?? 'Custom Group') ?></span>
            · Created by: <strong>Teacher / Admin</strong>
        </div>
    </div>
</a>
<?php endforeach; ?>
</div>
<?php endif; ?>

</div>
<?php include '../includes/footer.php'; ?>
</div>
</div>

<script>
function filterGroupsList() {
    const input = document.getElementById('groupSearchInput').value.toLowerCase();
    const items = document.querySelectorAll('#groupsContainer .group-card');
    items.forEach(item => {
        const name = item.querySelector('.group-name').textContent.toLowerCase();
        if (name.includes(input)) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });
}
</script>