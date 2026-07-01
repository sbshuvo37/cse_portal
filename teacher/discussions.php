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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_group') {
        $courseId = intval($_POST['course_id'] ?? 0);
        $batchId  = intval($_POST['batch_id']  ?? 0);

        $isAssigned = false;
        foreach ($courses as $c) { if ($c['course_id']==$courseId && $c['batch_id']==$batchId) { $isAssigned = true; break; } }

        if (!$isAssigned) {
            $error = 'You can only create a discussion group for your own assigned course + batch.';
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

        if (empty($body) && !$hasFile) {
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

    if ($action === 'delete_message') {
        $messageId = intval($_POST['message_id'] ?? 0);
        $groupId   = intval($_POST['group_id'] ?? 0);
        if ($discussionModel->isMessageOwner($messageId, $uid)) {
            $discussionModel->deleteMessage($messageId);
        }
        Helper::redirect('teacher/discussions.php?group=' . $groupId);
    }

    if ($action === 'delete_group') {
        $groupId = intval($_POST['group_id'] ?? 0);
        if ($discussionModel->isCreator($groupId, $uid)) {
            $discussionModel->deleteGroup($groupId);
            Helper::setFlash('success', 'Discussion group deleted.');
        }
        Helper::redirect('teacher/discussions.php');
    }
}

$courseBatchPairs = array_map(fn($c) => ['course_id'=>$c['course_id'], 'batch_id'=>$c['batch_id']], $courses);
$groups = $discussionModel->groupsForTeacherCourses($courseBatchPairs);

$activeGroupId = intval($_GET['group'] ?? 0);
$activeGroup   = $activeGroupId ? $discussionModel->findGroupById($activeGroupId) : null;
$messages      = $activeGroupId ? $discussionModel->getMessages($activeGroupId) : [];

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
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<?php if ($activeGroup): ?>
<!-- ===== Group chat view ===== -->
<div class="page-header">
    <div><h2>💡 <?= htmlspecialchars($activeGroup['course_code'].' — '.$activeGroup['course_title']) ?></h2>
    <div class="page-subtitle"><?= htmlspecialchars($activeGroup['batch_name'] ?? 'All Batches') ?> · Group Discussion</div></div>
    <a href="discussions.php" class="btn btn-outline">← Back to Groups</a>
</div>

<div class="group-chat-panel">
    <div class="group-chat-header">
        <div>
            <div style="font-weight:700;color:var(--primary-dark)">👥 <?= htmlspecialchars($activeGroup['course_title']) ?> Group</div>
            <div style="font-size:0.76rem;color:var(--text-muted)">Created by <?= htmlspecialchars($activeGroup['creator_name']) ?></div>
        </div>
        <?php if ($discussionModel->isCreator($activeGroup['group_id'], $uid)): ?>
        <form method="POST" onsubmit="return confirmAction('Delete this entire group and all its messages?')">
            <input type="hidden" name="action" value="delete_group">
            <input type="hidden" name="group_id" value="<?= $activeGroup['group_id'] ?>">
            <button class="btn btn-sm btn-danger">🗑 Delete Group</button>
        </form>
        <?php endif; ?>
    </div>
    <div class="group-chat-feed">
        <?php if (empty($messages)): ?>
            <div class="empty-state"><div class="empty-icon">💬</div><p>No messages yet. Start the conversation!</p></div>
        <?php else: foreach ($messages as $m): ?>
        <?php $isMe = (int)$m['user_id'] === $uid; ?>
        <div class="group-msg-row <?= $isMe?'own':'' ?>">
            <div class="group-msg-avatar"><?= Helper::initials($m['author']) ?></div>
            <div class="group-msg-bubble">
                <div class="group-msg-author"><?= htmlspecialchars($m['author']) ?> <span class="badge badge-gray" style="text-transform:capitalize;font-size:0.6rem"><?= htmlspecialchars($m['author_role']) ?></span></div>
                <div class="group-msg-content">
                    <?php if (!empty($m['body'])): ?><?= nl2br(htmlspecialchars($m['body'])) ?><?php endif; ?>
                    <?php if ($m['attachment']): ?><a href="<?= UPLOAD_URL . htmlspecialchars($m['attachment']) ?>" target="_blank" class="chat-attachment-link"><?= Helper::fileIcon($m['attachment']) ?> File</a><?php endif; ?>
                </div>
                <div class="group-msg-time"><?= Helper::formatDate($m['created_at'], 'M j, g:i A') ?></div>
            </div>
            <?php if ($isMe): ?>
            <form method="POST" onsubmit="return confirmAction('Delete this message?')">
                <input type="hidden" name="action" value="delete_message">
                <input type="hidden" name="message_id" value="<?= $m['message_id'] ?>">
                <input type="hidden" name="group_id" value="<?= $activeGroup['group_id'] ?>">
                <button class="group-msg-delete">✕</button>
            </form>
            <?php endif; ?>
        </div>
        <?php endforeach; endif; ?>
    </div>
    <form method="POST" enctype="multipart/form-data" class="group-chat-input" id="groupChatForm">
        <input type="hidden" name="action" value="post_message">
        <input type="hidden" name="group_id" value="<?= $activeGroup['group_id'] ?>">
        <input type="text" name="body" id="groupMsgInput" class="form-control" placeholder="Message the group...">
        <label class="btn btn-sm btn-outline" style="cursor:pointer">📎<input type="file" name="attachment" id="groupAttachInput" style="display:none"></label>
        <span id="groupAttachName" class="chat-attach-name"></span>
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
    form.addEventListener('submit', (e) => {
        if (msgInput.value.trim() === '' && fileInput.files.length === 0) {
            e.preventDefault(); msgInput.focus(); msgInput.placeholder = 'Write something or attach a file...';
        }
    });
    const feed = document.querySelector('.group-chat-feed');
    if (feed) feed.scrollTop = feed.scrollHeight;
})();
</script>

<?php else: ?>
<!-- ===== Group list view ===== -->
<div class="page-header">
    <div><h2>💡 Discussion Groups</h2><div class="page-subtitle">Group chat with your students, per course & batch</div></div>
    <button class="btn btn-primary" onclick="openModal('newGroupModal')">+ New Group</button>
</div>

<?php if (empty($groups)): ?>
    <div class="empty-state"><div class="empty-icon">💡</div><p>No discussion groups yet. Create one for a course you teach.</p></div>
<?php else: ?>
<div class="group-list">
<?php foreach ($groups as $g): ?>
<a href="?group=<?= $g['group_id'] ?>" class="group-card">
    <div class="group-icon">💬</div>
    <div class="group-info">
        <div class="group-name"><?= htmlspecialchars($g['course_code'].' — '.$g['course_title']) ?></div>
        <div class="group-meta">
            <span class="badge badge-primary"><?= htmlspecialchars($g['batch_name'] ?? 'All Batches') ?></span>
            <?= $g['message_count'] ?> message<?= $g['message_count']!=1?'s':'' ?>
        </div>
    </div>
    <div class="group-activity"><?= $g['last_activity'] ? Helper::timeAgo($g['last_activity']) : 'No activity yet' ?></div>
</a>
<?php endforeach; ?>
</div>
<?php endif; ?>

<div class="modal-overlay" id="newGroupModal">
<div class="modal-box">
    <div class="modal-header"><span class="modal-title">+ New Discussion Group</span><button class="modal-close" onclick="closeModal('newGroupModal')">✕</button></div>
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
        <div class="form-hint mb-2">ℹ️ All students in this batch will see and can post in the group. One group per course+batch.</div>
        <div style="display:flex;justify-content:flex-end;gap:10px">
            <button type="button" class="btn btn-outline" onclick="closeModal('newGroupModal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Create Group</button>
        </div>
    </form>
    </div>
</div>
</div>
<?php endif; ?>

</div>
<?php include '../includes/footer.php'; ?>
</div>
</div>
