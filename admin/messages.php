<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
Auth::requireRole('admin');

$messageModel = new MessageModel();
$userModel    = new User();
$myId         = Auth::userId();
$error        = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'send') {
        $receiverId = intval($_POST['receiver_id'] ?? 0);
        $body       = trim($_POST['message'] ?? '');
        $hasFile    = !empty($_FILES['attachment']['name']);

        if (!$receiverId || (empty($body) && !$hasFile)) {
            $error = 'Please write a message or attach a file.';
        } else {
            $messageId = $messageModel->send($myId, $receiverId, $body !== '' ? $body : null);
            if ($hasFile) {
                $uploader = new FileUpload('messages');
                $res = $uploader->upload($_FILES['attachment']);
                if ($res['success']) {
                    $messageModel->addFile($messageId, $res['path']);
                } else {
                    Helper::setFlash('danger', 'Message sent, but file attachment failed: ' . $res['message']);
                }
            }
            (new NotificationModel())->create($receiverId, 'New Message', 'You have a new message from ' . Auth::userName(), 'message');
            Helper::redirect('admin/messages.php?with=' . $receiverId);
        }
    }
}

$conversations = $messageModel->conversationList($myId);
$activeWith    = intval($_GET['with'] ?? 0);
$activeUser    = $activeWith ? $userModel->findById($activeWith) : null;
$thread        = $activeWith ? $messageModel->conversation($myId, $activeWith) : [];

// Allowed contacts for admin: all teachers + all students
$allContacts = array_merge(
    array_map(fn($t) => ['id'=>$t['user_id'],'name'=>$t['name'],'role'=>'teacher'], (new Teacher())->all()),
    array_map(fn($s) => ['id'=>$s['user_id'],'name'=>$s['name'],'role'=>'student'], (new Student())->all())
);

$pageTitle   = 'Messages';
$currentPage = 'Messages';
include '../includes/header.php';
?>
<div class="portal-layout">
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
<?php include '../includes/navbar.php'; ?>
<div class="page-content">

<?php $flash = Helper::getFlash(); if ($flash): ?><div class="alert alert-<?= $flash['type'] ?>" data-auto-dismiss><?= htmlspecialchars($flash['message']) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="page-header">
    <div><h2>💬 Messages</h2><div class="page-subtitle">Private messaging with teachers and students</div></div>
    <button class="btn btn-primary" onclick="openModal('newMsgModal')">+ New Message</button>
</div>

<div class="messaging-layout">
    <div class="conversation-list">
        <?php if (empty($conversations)): ?>
            <div class="empty-state"><div class="empty-icon">💬</div><p>No conversations yet.</p></div>
        <?php else: foreach ($conversations as $c): ?>
        <a href="?with=<?= $c['id'] ?>" class="conversation-item <?= $activeWith==$c['id']?'active':'' ?>">
            <div class="user-avatar"><?= Helper::initials($c['name']) ?></div>
            <div style="flex:1;min-width:0">
                <div class="conversation-name"><?= htmlspecialchars($c['name']) ?> <span class="badge badge-gray" style="text-transform:capitalize;margin-left:4px"><?= htmlspecialchars($c['role']) ?></span></div>
                <div class="conversation-preview"><?= htmlspecialchars($c['last_message'] ?: '📎 Attachment') ?></div>
                <div class="conversation-time"><?= $c['last_time'] ? Helper::timeAgo($c['last_time']) : '' ?></div>
            </div>
        </a>
        <?php endforeach; endif; ?>
    </div>

    <div class="chat-panel">
        <?php if ($activeUser): ?>
        <div class="chat-header">
            <div class="user-avatar"><?= Helper::initials($activeUser['name']) ?></div>
            <div>
                <div style="font-weight:700;color:var(--primary-dark)"><?= htmlspecialchars($activeUser['name']) ?></div>
                <div style="font-size:0.74rem;color:var(--text-muted);text-transform:capitalize"><?= htmlspecialchars($activeUser['role']) ?></div>
            </div>
        </div>
        <div class="chat-messages">
            <?php if (empty($thread)): ?>
                <div class="empty-state"><p>No messages yet. Say hello!</p></div>
            <?php else: foreach ($thread as $m): ?>
            <?php $files = $messageModel->getFiles($m['message_id']); $isMe = $m['sender_id']==$myId; ?>
            <div class="chat-bubble <?= $isMe?'sent':'received' ?>">
                <?php if (!empty($m['message'])): ?><?= nl2br(htmlspecialchars($m['message'])) ?><?php endif; ?>
                <?php foreach ($files as $f): ?>
                    <a href="<?= UPLOAD_URL . htmlspecialchars($f['file_path']) ?>" target="_blank" class="chat-attachment-link"><?= Helper::fileIcon($f['file_path']) ?> File</a>
                <?php endforeach; ?>
                <div class="chat-bubble-time"><?= Helper::formatDate($m['created_at'], 'M j, g:i A') ?></div>
            </div>
            <?php endforeach; endif; ?>
        </div>
        <form method="POST" enctype="multipart/form-data" class="chat-input-row" id="chatForm">
            <input type="hidden" name="action" value="send">
            <input type="hidden" name="receiver_id" value="<?= $activeUser['id'] ?>">
            <input type="text" name="message" id="chatMessageInput" class="form-control" placeholder="Type a message...">
            <label class="btn btn-sm btn-outline chat-attach-label" style="cursor:pointer">📎<input type="file" name="attachment" id="chatAttachInput" style="display:none"></label>
            <span id="chatAttachName" class="chat-attach-name"></span>
            <button type="submit" class="btn btn-primary">Send</button>
        </form>
        <?php else: ?>
        <div class="empty-state" style="margin:auto"><div class="empty-icon">💬</div><p>Select a conversation or start a new one.</p></div>
        <?php endif; ?>
    </div>
</div>

</div>
<?php include '../includes/footer.php'; ?>
</div>
</div>

<script>
(() => {
    const fileInput = document.getElementById('chatAttachInput');
    const nameDisplay = document.getElementById('chatAttachName');
    const msgInput = document.getElementById('chatMessageInput');
    const form = document.getElementById('chatForm');
    if (!fileInput || !form) return;

    fileInput.addEventListener('change', () => {
        nameDisplay.textContent = fileInput.files[0] ? '📎 ' + fileInput.files[0].name : '';
    });

    form.addEventListener('submit', (e) => {
        const hasText = msgInput.value.trim() !== '';
        const hasFile = fileInput.files.length > 0;
        if (!hasText && !hasFile) {
            e.preventDefault();
            msgInput.focus();
            msgInput.placeholder = 'Write something or attach a file...';
        }
    });
})();
</script>

<div class="modal-overlay" id="newMsgModal">
<div class="modal-box">
    <div class="modal-header"><span class="modal-title">+ New Message</span><button class="modal-close" onclick="closeModal('newMsgModal')">✕</button></div>
    <div class="modal-body">
    <form method="GET">
        <div class="form-group">
            <label class="form-label">Send to</label>
            <select name="with" class="form-control" required>
                <option value="">Select recipient</option>
                <?php foreach ($allContacts as $c): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['role']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%">Start Conversation</button>
    </form>
    </div>
</div>
</div>
