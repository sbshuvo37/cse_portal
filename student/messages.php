<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
Auth::requireRole('student');

$messageModel = new MessageModel();
$userModel    = new User();
$studentModel = new Student();
$courseModel  = new CourseModel();
$myId         = Auth::userId();
$error        = '';

$student = $studentModel->findByUserId($myId);

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
                if ($res['success']) $messageModel->addFile($messageId, $res['path']);
            }
            (new NotificationModel())->create($receiverId, 'New Message', 'You have a new message.', 'message');
            Helper::redirect('student/messages.php?with=' . $receiverId);
        }
    }
}

$conversations = $messageModel->conversationList($myId);
$activeWith    = intval($_GET['with'] ?? 0);
$activeUser    = $activeWith ? $userModel->findById($activeWith) : null;
$thread        = $activeWith ? $messageModel->conversation($myId, $activeWith) : [];

// Contacts Data for Filtering Select [Point 6]
$db = Database::getInstance()->getConnection();
$adminsList = $db->query("SELECT id, name FROM users WHERE role='admin' AND status='active'")->fetchAll();

$myTeachersList = [];
if ($student['batch_id']) {
    foreach ($courseModel->getCoursesForBatch($student['batch_id']) as $c) {
        $myTeachersList[] = [
            'user_id' => $c['teacher_id'], // This is mapped to users via model inside class
            'name'    => $c['teacher_name'],
            'course'  => $c['course_code']
        ];
    }
}

// Ensure teacher actual user_ids are mapped correctly
$teacherModel = new Teacher();
$teachersRefined = [];
foreach ($myTeachersList as $teacherItem) {
    $t = $teacherModel->findById((int)$teacherItem['user_id']);
    if ($t) {
        $teachersRefined[] = [
            'id'   => $t['user_id'],
            'name' => $t['name'] . ' (' . $teacherItem['course'] . ')'
        ];
    }
}

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
    <div><h2>💬 Messages</h2><div class="page-subtitle"> Inbox communications channels</div></div>
    <button class="btn btn-primary" onclick="openModal('newMsgFilterModal')">+ New Message</button>
</div>

<div class="messaging-layout">
    <div class="conversation-list">
        <div style="padding: 10px 14px; border-bottom:1px solid var(--border);">
            <input type="text" id="contactSearchInput" class="form-control" placeholder="Search contacts..." oninput="filterInboxContacts()">
        </div>
        <div id="inboxContainer">
        <?php foreach ($conversations as $c): ?>
        <a href="?with=<?= $c['id'] ?>" class="conversation-item <?= $activeWith==$c['id']?'active':'' ?>">
            <div class="user-avatar"><?= Helper::initials($c['name']) ?></div>
            <div style="flex:1;min-width:0;">
                <div class="conversation-name"><?= htmlspecialchars($c['name']) ?> <span class="badge badge-gray" style="font-size:0.6rem"><?= htmlspecialchars($c['role']) ?></span></div>
                <div class="conversation-preview"><?= htmlspecialchars($c['last_message'] ?: '📎 Attachment') ?></div>
            </div>
        </a>
        <?php endforeach; ?>
        </div>
    </div>

    <div class="chat-panel">
        <?php if ($activeUser): ?>
        <div class="chat-header">
            <div class="user-avatar"><?= Helper::initials($activeUser['name']) ?></div>
            <div><div style="font-weight:700;color:var(--primary-dark)"><?= htmlspecialchars($activeUser['name']) ?></div><div style="font-size:0.74rem;color:var(--text-muted);text-transform:capitalize"><?= htmlspecialchars($activeUser['role']) ?></div></div>
        </div>
        <div class="chat-messages">
            <?php foreach ($thread as $m): ?>
            <?php $files = $messageModel->getFiles($m['message_id']); $isMe = $m['sender_id']==$myId; ?>
            <div class="chat-bubble <?= $isMe?'sent':'received' ?>">
                <?php if (!empty($m['message'])): ?><?= nl2br(htmlspecialchars($m['message'])) ?><?php endif; ?>
                <?php foreach ($files as $f): ?><a href="<?= UPLOAD_URL . htmlspecialchars($f['file_path']) ?>" target="_blank" class="chat-attachment-link">📎 Download File</a><?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <form method="POST" enctype="multipart/form-data" class="chat-input-row" id="chatForm">
            <input type="hidden" name="action" value="send">
            <input type="hidden" name="receiver_id" value="<?= $activeUser['id'] ?>">
            <input type="text" name="message" class="form-control" placeholder="Type a message...">
            <label class="btn btn-sm btn-outline" style="cursor:pointer">📎<input type="file" name="attachment" style="display:none"></label>
            <button type="submit" class="btn btn-primary">Send</button>
        </form>
        <?php else: ?>
        <div class="empty-state" style="margin:auto;"><p>Select a contact to chat.</p></div>
        <?php endif; ?>
    </div>
</div>

</div>
<?php include '../includes/footer.php'; ?>
</div>
</div>

<!-- Modal: Dynamic Category Selection [Point 6] -->
<div class="modal-overlay" id="newMsgFilterModal">
<div class="modal-box">
    <div class="modal-header"><span class="modal-title">+ New Message</span><button class="modal-close" onclick="closeModal('newMsgFilterModal')">✕</button></div>
    <div class="modal-body">
    <form method="GET">
        <div class="form-group">
            <label class="form-label">Recipient Category</label>
            <select id="roleTypeSelect" class="form-control" required onchange="toggleStudentRecipientRole()">
                <option value="">-- Choose Category --</option>
                <option value="admin">Administrator</option>
                <option value="teacher">My Assigned Teacher</option>
            </select>
        </div>

        <!-- Dynamic Admin Selector -->
        <div class="form-group" id="adminSelectWrapper" style="display:none;">
            <label class="form-label">Select Admin *</label>
            <select name="with" id="adminSelect" class="form-control">
                <option value="">-- Choose Administrator --</option>
                <?php foreach ($adminsList as $a): ?>
                <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Dynamic Teacher Selector -->
        <div class="form-group" id="teacherSelectWrapper" style="display:none;">
            <label class="form-label">Select Teacher *</label>
            <select name="with" id="teacherSelect" class="form-control">
                <option value="">-- Choose Teacher --</option>
                <?php foreach ($teachersRefined as $t): ?>
                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" id="msgSubmitBtn" class="btn btn-primary" style="width:100%; margin-top:15px;" disabled>Start Chat</button>
    </form>
    </div>
</div>
</div>

<script>
function toggleStudentRecipientRole() {
    const type = document.getElementById('roleTypeSelect').value;
    const aWrap = document.getElementById('adminSelectWrapper');
    const tWrap = document.getElementById('teacherSelectWrapper');
    const submitBtn = document.getElementById('msgSubmitBtn');

    // Reset values
    document.getElementById('adminSelect').value = '';
    document.getElementById('teacherSelect').value = '';

    if (type === 'admin') {
        aWrap.style.display = 'block';
        tWrap.style.display = 'none';
        document.getElementById('adminSelect').required = true;
        document.getElementById('teacherSelect').required = false;
        submitBtn.removeAttribute('disabled');
    } else if (type === 'teacher') {
        aWrap.style.display = 'none';
        tWrap.style.display = 'block';
        document.getElementById('adminSelect').required = false;
        document.getElementById('teacherSelect').required = true;
        submitBtn.removeAttribute('disabled');
    } else {
        aWrap.style.display = 'none';
        tWrap.style.display = 'none';
        submitBtn.setAttribute('disabled', 'disabled');
    }
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