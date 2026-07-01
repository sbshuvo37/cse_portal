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
                }
            }
            (new NotificationModel())->create($receiverId, 'New Message', 'You have a new message.', 'message');
            Helper::redirect('admin/messages.php?with=' . $receiverId);
        }
    }
}

$conversations = $messageModel->conversationList($myId);
$activeWith    = intval($_GET['with'] ?? 0);
$activeUser    = $activeWith ? $userModel->findById($activeWith) : null;
$thread        = $activeWith ? $messageModel->conversation($myId, $activeWith) : [];

// Contacts Data for Filtering Select [Point 6]
$teachersList = (new Teacher())->all();
$studentsList = (new Student())->all();
$batchesList  = (new BatchModel())->all();

$pageTitle   = 'Messages';
$currentPage = 'Messages';
include '../includes/header.php';
?>
<div class="portal-layout">
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
<?php include '../includes/navbar.php'; ?>
<div class="page-content">

<div class="page-header">
    <div><h2>💬 Messages</h2><div class="page-subtitle">Private conversations inbox</div></div>
    <button class="btn btn-primary" onclick="openModal('newMsgFilterModal')">+ New Message</button>
</div>

<div class="messaging-layout">
    <div class="conversation-list">
        <div style="padding: 10px 14px; border-bottom:1px solid var(--border);">
            <input type="text" id="contactSearchInput" class="form-control" placeholder="Search contacts..." oninput="filterInboxContacts()">
        </div>
        <div id="inboxContainer">
        <?php if (empty($conversations)): ?>
            <div class="empty-state"><p>No active chats.</p></div>
        <?php else: foreach ($conversations as $c): ?>
        <a href="?with=<?= $c['id'] ?>" class="conversation-item <?= $activeWith==$c['id']?'active':'' ?>">
            <div class="user-avatar"><?= Helper::initials($c['name']) ?></div>
            <div style="flex:1;min-width:0;">
                <div class="conversation-name"><?= htmlspecialchars($c['name']) ?> <span class="badge badge-gray" style="font-size:0.6rem"><?= htmlspecialchars($c['role']) ?></span></div>
                <div class="conversation-preview"><?= htmlspecialchars($c['last_message'] ?: '📎 Attachment') ?></div>
            </div>
        </a>
        <?php endforeach; endif; ?>
        </div>
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
            <?php foreach ($thread as $m): ?>
            <?php $files = $messageModel->getFiles($m['message_id']); $isMe = $m['sender_id']==$myId; ?>
            <div class="chat-bubble <?= $isMe?'sent':'received' ?>">
                <?php if (!empty($m['message'])): ?><?= nl2br(htmlspecialchars($m['message'])) ?><?php endif; ?>
                <?php foreach ($files as $f): ?>
                    <a href="<?= UPLOAD_URL . htmlspecialchars($f['file_path']) ?>" target="_blank" class="chat-attachment-link">📎 Download File</a>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <form method="POST" enctype="multipart/form-data" class="chat-input-row" id="chatForm">
            <input type="hidden" name="action" value="send">
            <input type="hidden" name="receiver_id" value="<?= $activeUser['id'] ?>">
            <input type="text" name="message" id="chatMessageInput" class="form-control" placeholder="Type a message...">
            <label class="btn btn-sm btn-outline" style="cursor:pointer">📎<input type="file" name="attachment" style="display:none"></label>
            <button type="submit" class="btn btn-primary">Send</button>
        </form>
        <?php else: ?>
        <div class="empty-state" style="margin:auto;"><p>Select a contact to begin messaging.</p></div>
        <?php endif; ?>
    </div>
</div>

</div>
<?php include '../includes/footer.php'; ?>
</div>
</div>

<!-- Modal: Advanced Recipient Filtering Selector [Point 6] -->
<div class="modal-overlay" id="newMsgFilterModal">
<div class="modal-box">
    <div class="modal-header">
        <span class="modal-title">+ Send New Message</span>
        <button class="modal-close" onclick="closeModal('newMsgFilterModal')">✕</button>
    </div>
    <div class="modal-body">
    <form method="GET">
        <div class="form-group">
            <label class="form-label">Recipient Category</label>
            <select id="recipientType" class="form-control" onchange="toggleRecipientTypeFields()">
                <option value="">-- Select Category --</option>
                <option value="teacher">Teacher</option>
                <option value="student">Student</option>
            </select>
        </div>

        <!-- Dynamic Teacher Selector -->
        <div class="form-group" id="teacherSelectWrapper" style="display:none;">
            <label class="form-label">Select Teacher *</label>
            <select name="with" id="teacherSelect" class="form-control">
                <option value="">-- Choose Teacher --</option>
                <?php foreach ($teachersList as $t): ?>
                <option value="<?= $t['user_id'] ?>"><?= htmlspecialchars($t['name']) ?> (<?= htmlspecialchars($t['designation'] ?: 'Faculty') ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Dynamic Batch Selector -->
        <div class="form-group" id="studentBatchSelectWrapper" style="display:none;">
            <label class="form-label">Select Student Batch *</label>
            <select id="studentBatchSelect" class="form-control" onchange="filterStudentsBySelectedBatch()">
                <option value="">-- Choose Batch --</option>
                <?php foreach ($batchesList as $b): ?>
                <option value="<?= $b['batch_id'] ?>"><?= htmlspecialchars($b['batch_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Dynamic Student Selector -->
        <div class="form-group" id="studentSelectWrapper" style="display:none;">
            <label class="form-label">Select Student *</label>
            <select name="with" id="studentSelect" class="form-control">
                <option value="">-- Choose Student --</option>
            </select>
        </div>

        <button type="submit" id="msgSubmitBtn" class="btn btn-primary" style="width:100%; margin-top:15px;" disabled>Start Chat</button>
    </form>
    </div>
</div>
</div>

<script>
const studentsDatasetForMessaging = <?= json_encode($studentsList) ?>;

function toggleRecipientTypeFields() {
    const type = document.getElementById('recipientType').value;
    const tWrap = document.getElementById('teacherSelectWrapper');
    const bWrap = document.getElementById('studentBatchSelectWrapper');
    const sWrap = document.getElementById('studentSelectWrapper');
    const submitBtn = document.getElementById('msgSubmitBtn');

    // Reset fields
    document.getElementById('teacherSelect').value = '';
    document.getElementById('studentBatchSelect').value = '';
    document.getElementById('studentSelect').innerHTML = '<option value="">-- Choose Student --</option>';

    if (type === 'teacher') {
        tWrap.style.display = 'block';
        bWrap.style.display = 'none';
        sWrap.style.display = 'none';
        document.getElementById('teacherSelect').required = true;
        document.getElementById('studentBatchSelect').required = false;
        document.getElementById('studentSelect').required = false;
        submitBtn.removeAttribute('disabled');
    } else if (type === 'student') {
        tWrap.style.display = 'none';
        bWrap.style.display = 'block';
        sWrap.style.display = 'block';
        document.getElementById('teacherSelect').required = false;
        document.getElementById('studentBatchSelect').required = true;
        document.getElementById('studentSelect').required = true;
        submitBtn.removeAttribute('disabled');
    } else {
        tWrap.style.display = 'none';
        bWrap.style.display = 'none';
        sWrap.style.display = 'none';
        submitBtn.setAttribute('disabled', 'disabled');
    }
}

function filterStudentsBySelectedBatch() {
    const batchId = document.getElementById('studentBatchSelect').value;
    const sSelect = document.getElementById('studentSelect');
    sSelect.innerHTML = '<option value="">-- Choose Student --</option>';

    if (!batchId) return;

    const matches = studentsDatasetForMessaging.filter(s => String(s.batch_id) === String(batchId));

    if (matches.length === 0) {
        sSelect.innerHTML = '<option value="">No students exist in this batch</option>';
        return;
    }

    matches.forEach(s => {
        sSelect.innerHTML += `<option value="${s.user_id}">${s.name} (Roll: ${s.roll})</option>`;
    });
}

function filterInboxContacts() {
    const input = document.getElementById('contactSearchInput').value.toLowerCase();
    const items = document.querySelectorAll('#inboxContainer .conversation-item');
    items.forEach(item => {
        const name = item.querySelector('.conversation-name').textContent.toLowerCase();
        if (name.includes(input)) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });
}
</script>