<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
Auth::requireRole('teacher');

$teacherModel    = new Teacher();
$discussionModel = new DiscussionModel();
$error           = '';
$uid             = Auth::userId();

$teacher = $teacherModel->findByUserId($uid);
$courses = $teacherModel->getAssignedCourses($teacher['teacher_id']);

$db = Database::getInstance()->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_group') {
        $courseId = intval($_POST['course_id'] ?? 0);
        $batchId  = intval($_POST['batch_id']  ?? 0);

        $isAssigned = false;
        foreach ($courses as $c) { if ($c['course_id'] == $courseId && $c['batch_id'] == $batchId) { $isAssigned = true; break; } }

        if (!$isAssigned) {
            $error = 'You can only create groups for your assigned course and batch.';
        } else {
            $result = $discussionModel->createGroup($courseId, $batchId, $uid);
            Helper::setFlash($result['success'] ? 'success' : 'danger', $result['message']);
            Helper::redirect('teacher/discussions.php?group=' . $result['group_id']);
        }
    }

    if ($action === 'post_message') {
        $groupId = intval($_POST['group_id'] ?? 0);
        $body    = trim($_POST['body'] ?? '');
        $hasFile = !empty($_FILES['attachment']['name']);

        $group = $discussionModel->findGroupById($groupId);
        
        // ১ নম্বর পরিবর্তন: কাস্টম মেম্বারশিপ ও ক্রিয়েটর ভ্যালিডেশন চেক [Point 6]
        $isAuthorized = false;
        if ($group) {
            $isCreator = (int)$group['created_by'] === $uid;
            
            $stmt = $db->prepare("SELECT 1 FROM discussion_group_members WHERE group_id = ? AND user_id = ?");
            $stmt->execute([$groupId, $uid]);
            $isExplicitMember = (bool)$stmt->fetch();

            $isAssigned = false;
            foreach ($courses as $c) {
                if ($c['course_id'] == $group['course_id'] && $c['batch_id'] == $group['batch_id']) {
                    $isAssigned = true;
                    break;
                }
            }

            if ($isCreator || $isExplicitMember || $isAssigned) {
                $isAuthorized = true;
            }
        }

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
        Helper::redirect('teacher/discussions.php?group=' . $groupId);
    }
}

$courseBatchPairs = array_map(fn($c) => ['course_id'=>$c['course_id'], 'batch_id'=>$c['batch_id']], $courses);

// ২ নম্বর পরিবর্তন: গ্রুপ লোড করার সময় শিক্ষকের ID প্যারামিটার হিসেবে পাস করা হলো [Point 6]
$groups = $discussionModel->groupsForTeacherCourses($courseBatchPairs, $uid);

$activeGroupId = intval($_GET['group'] ?? 0);
$activeGroup   = null;
$messages      = [];

if ($activeGroupId) {
    $candidate = $discussionModel->findGroupById($activeGroupId);
    
    // ৩ নম্বর পরিবর্তন: কাস্টম মেম্বারশিপে থাকা শিক্ষকের প্রবেশাধিকার অনুমোদন করা হলো [Point 6]
    $isAuthorized = false;
    if ($candidate) {
        $isCreator = (int)$candidate['created_by'] === $uid;
        
        $stmt = $db->prepare("SELECT 1 FROM discussion_group_members WHERE group_id = ? AND user_id = ?");
        $stmt->execute([$activeGroupId, $uid]);
        $isExplicitMember = (bool)$stmt->fetch();

        $isAssigned = false;
        foreach ($courses as $c) {
            if ($c['course_id'] == $candidate['course_id'] && $c['batch_id'] == $candidate['batch_id']) {
                $isAssigned = true;
                break;
            }
        }

        if ($isCreator || $isExplicitMember || $isAssigned) {
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
    <!-- ৪ নম্বর পরিবর্তন: কাস্টম গ্রুপ টাইটেল হ্যান্ডেল করা হলো -->
    <div><h2>💡 <?= htmlspecialchars($activeGroup['title'] ?: ($activeGroup['course_code'] ? $activeGroup['course_code'].' — '.$activeGroup['course_title'] : 'Custom Admin Group')) ?></h2>
    <div class="page-subtitle"><?= htmlspecialchars($activeGroup['batch_name'] ?? 'Custom Group') ?> · Group Discussion</div></div>
    <a href="discussions.php" class="btn btn-outline">← Back to Groups</a>
</div>

<div class="group-chat-panel">
    <div class="group-chat-header">
        <div>
            <div style="font-weight:700;color:var(--primary-dark)">👥 <?= htmlspecialchars($activeGroup['title'] ?: 'Custom Admin Group') ?> Group</div>
            <div style="font-size:0.76rem;color:var(--text-muted)">Created by <?= htmlspecialchars($activeGroup['creator_name']) ?></div>
        </div>
    </div>
    <div class="group-chat-feed">
        <?php if (empty($messages)): ?>
            <div class="empty-state"><div class="empty-icon">💬</div><p>No messages yet.</p></div>
        <?php else: foreach ($messages as $m): ?>
        <?php $isMe = (int)$m['user_id'] === $uid; ?>
        <div class="group-msg-row <?= $isMe?'own':'' ?>">
            <div class="group-msg-avatar"><?= Helper::initials($m['author']) ?></div>
            <div class="group-msg-bubble">
                <div class="group-msg-author"><?= htmlspecialchars($m['author']) ?> <span class="badge badge-gray" style="font-size:0.6rem"><?= htmlspecialchars($m['author_role']) ?></span></div>
                <div class="group-msg-content">
                    <?php if (!empty($m['body'])): ?><?= nl2br(htmlspecialchars($m['body'])) ?><?php endif; ?>
                    <?php if ($m['attachment']): ?><a href="<?= UPLOAD_URL . htmlspecialchars($m['attachment']) ?>" target="_blank" class="chat-attachment-link">📎 Download File</a><?php endif; ?>
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
    <div><h2>💡 Discussion Groups</h2><div class="page-subtitle">Group chats for your assigned courses</div></div>
    <button class="btn btn-primary" onclick="openModal('newGroupModal')">+ New Group</button>
</div>

<div style="margin-bottom: 18px; max-width: 320px;">
    <input type="text" id="groupSearchInput" class="form-control" placeholder="🔍 Search groups..." oninput="filterGroupsList()">
</div>

<div class="group-list" id="groupsContainer">
<?php foreach ($groups as $g): ?>
<a href="?group=<?= $g['group_id'] ?>" class="group-card" style="border: 1px solid var(--border); border-radius: var(--radius); padding: 18px; margin-bottom: 12px; background:white; display:flex; align-items:center;">
    <div class="group-icon">💬</div>
    <div class="group-info" style="margin-left: 14px;">
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

<div class="modal-overlay" id="newGroupModal">
<div class="modal-box">
    <div class="modal-header"><span class="modal-title">+ New Group</span><button class="modal-close" onclick="closeModal('newGroupModal')">✕</button></div>
    <div class="modal-body">
    <form method="POST">
        <input type="hidden" name="action" value="create_group">
        <div class="form-group">
            <label class="form-label">Your Course + Batch *</label>
            <select id="cgSelect" class="form-control" required onchange="
                const [cid,bid] = this.value.split('|');
                document.getElementById('cgCourseId').value = cid;
                document.getElementById('cgBatchId').value = bid;
            ">
                <option value="">Select course & batch</option>
                <?php foreach ($courses as $c): ?>
                <option value="<?= $c['course_id'] ?>|<?= $c['batch_id'] ?>"><?= htmlspecialchars($c['course_code'].' — '.$c['course_title'].' ('.$c['batch_name'].')') ?></option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="course_id" id="cgCourseId">
            <input type="hidden" name="batch_id" id="cgBatchId">
        </div>
        <div style="display:flex;justify-content:flex-end;gap:10px">
            <button type="button" class="btn btn-outline" onclick="closeModal('newGroupModal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Create Group</button>
        </div>
    </form>
    </div>
</div>
</div>

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