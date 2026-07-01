<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
Auth::requireRole('teacher');

$noticeModel  = new NoticeModel();
$batchModel   = new BatchModel();
$teacherModel = new Teacher();
$studentModel = new Student();
$notifModel   = new NotificationModel();
$error        = '';
$editData     = null;
$uid          = Auth::userId();

$teacher = $teacherModel->findByUserId($uid);
$courses = $teacherModel->getAssignedCourses($teacher['teacher_id']);
$myBatches = [];
foreach ($courses as $c) { $myBatches[$c['batch_id']] = $c['batch_name']; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $title  = trim($_POST['title']       ?? '');
        $desc   = trim($_POST['description'] ?? '');
        $target = $_POST['target']           ?? 'all_students';
        $batchId= $target === 'specific_batch' ? intval($_POST['batch_id'] ?? 0) : null;

        if (empty($title) || empty($desc)) {
            $error = 'Title and description are required.';
        } else {
            $noticeId = $noticeModel->create($title, $desc, $target, $batchId, $uid);
            if (!empty($_FILES['attachment']['name'])) {
                $uploader = new FileUpload('notices');
                $res = $uploader->upload($_FILES['attachment']);
                if ($res['success']) $noticeModel->addFile($noticeId, $res['path']);
            }
            if ($batchId) {
                $students = $studentModel->byBatch($batchId);
                $notifModel->createBulk(array_column($students,'user_id'), 'New Notice: '.$title, $desc, 'notice');
            }
            Helper::setFlash('success', 'Notice posted successfully.');
            Helper::redirect('teacher/notices.php');
        }
    }

    if ($action === 'edit') {
        $nid    = intval($_POST['notice_id']   ?? 0);
        $title  = trim($_POST['title']       ?? '');
        $desc   = trim($_POST['description'] ?? '');
        $target = $_POST['target']           ?? 'all_students';
        $batchId= $target === 'specific_batch' ? intval($_POST['batch_id'] ?? 0) : null;

        if (!$noticeModel->isOwner($nid, $uid)) {
            $error = 'You can only edit your own notices.';
        } elseif (empty($title) || empty($desc)) {
            $error = 'Title and description are required.';
        } else {
            $noticeModel->update($nid, $title, $desc, $target, $batchId);
            Helper::setFlash('success', 'Notice updated.');
            Helper::redirect('teacher/notices.php');
        }
    }

    if ($action === 'delete') {
        $nid = intval($_POST['notice_id'] ?? 0);
        if ($noticeModel->isOwner($nid, $uid)) {
            $noticeModel->delete($nid);
            Helper::setFlash('success', 'Notice deleted.');
        } else {
            Helper::setFlash('danger', 'You can only delete your own notices.');
        }
        Helper::redirect('teacher/notices.php');
    }
}

if (isset($_GET['edit'])) {
    $candidate = $noticeModel->findById(intval($_GET['edit']));
    if ($candidate && (int)$candidate['posted_by'] === $uid) $editData = $candidate;
}

// All notices visible (read), but only own are editable
$allNotices = $noticeModel->all();
$showModal  = (isset($_GET['action']) && $_GET['action']==='add') || $editData;

$pageTitle   = 'Notices';
$currentPage = 'Notices';
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
    <div><h2>📢 Notices</h2><div class="page-subtitle">You can edit/delete only your own notices</div></div>
    <a href="?action=add" class="btn btn-primary">+ Post Notice</a>
</div>

<div class="notice-list">
<?php if (empty($allNotices)): ?>
    <div class="empty-state"><div class="empty-icon">📭</div><p>No notices yet.</p></div>
<?php else: foreach ($allNotices as $n): ?>
<?php $isMine = (int)$n['posted_by'] === $uid; $files = $noticeModel->getFiles($n['notice_id']); ?>
<div class="notice-card" style="<?= $isMine ? 'border-left-color:var(--success)' : '' ?>">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px">
        <div style="flex:1">
            <div class="notice-card-title"><?= htmlspecialchars($n['title']) ?> <?php if($isMine): ?><span class="badge badge-success" style="margin-left:6px">Yours</span><?php endif; ?></div>
            <div class="notice-card-meta">📅 <?= Helper::formatDate($n['created_at']) ?> &nbsp;|&nbsp; By <?= htmlspecialchars($n['poster']) ?>
                <span class="badge <?= $n['target']==='all_students'?'badge-info':'badge-primary' ?>"><?= $n['target']==='all_students'?'All Students':'Batch: '.htmlspecialchars($n['batch_name']??'') ?></span>
            </div>
            <div class="notice-card-body"><?= nl2br(htmlspecialchars(substr($n['description'],0,250))) ?><?= strlen($n['description'])>250?'…':'' ?></div>
            <?php if (!empty($files)): ?>
            <div class="notice-attachments"><?php foreach ($files as $f): ?><a href="<?= UPLOAD_URL . htmlspecialchars($f['file_path']) ?>" class="notice-attachment-chip" target="_blank"><?= Helper::fileIcon($f['file_path']) ?> Attachment</a><?php endforeach; ?></div>
            <?php endif; ?>
        </div>
        <?php if ($isMine): ?>
        <div style="display:flex;gap:6px;flex-shrink:0">
            <a href="?edit=<?= $n['notice_id'] ?>" class="btn btn-sm btn-accent">✏️</a>
            <form method="POST" onsubmit="return confirmAction('Delete this notice?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="notice_id" value="<?= $n['notice_id'] ?>">
                <button class="btn btn-sm btn-danger">🗑</button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; endif; ?>
</div>

<div class="modal-overlay <?= $showModal?'open':'' ?>" id="noticeModal">
<div class="modal-box">
    <div class="modal-header"><span class="modal-title"><?= $editData?'✏️ Edit Notice':'+ Post Notice' ?></span><button class="modal-close" onclick="closeModal('noticeModal')">✕</button></div>
    <div class="modal-body">
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="<?= $editData?'edit':'add' ?>">
        <?php if ($editData): ?><input type="hidden" name="notice_id" value="<?= $editData['notice_id'] ?>"><?php endif; ?>
        <div class="form-group"><label class="form-label">Title *</label><input type="text" name="title" class="form-control" required value="<?= htmlspecialchars($editData['title']??'') ?>"></div>
        <div class="form-group"><label class="form-label">Description *</label><textarea name="description" class="form-control" required rows="5"><?= htmlspecialchars($editData['description']??'') ?></textarea></div>
        <div class="form-group">
            <label class="form-label">Target *</label>
            <select name="target" class="form-control" onchange="document.getElementById('batchGroup').style.display=this.value==='specific_batch'?'block':'none'">
                <option value="all_students" <?= ($editData['target']??'')==='all_students'?'selected':'' ?>>All Students</option>
                <option value="specific_batch" <?= ($editData['target']??'')==='specific_batch'?'selected':'' ?>>Specific Batch</option>
            </select>
        </div>
        <div class="form-group" id="batchGroup" style="display:<?= ($editData['target']??'')==='specific_batch'?'block':'none' ?>">
            <label class="form-label">Batch *</label>
            <select name="batch_id" class="form-control">
                <option value="">Select Batch</option>
                <?php foreach ($myBatches as $bid => $bname): ?>
                <option value="<?= $bid ?>" <?= ($editData['batch_id']??0)==$bid?'selected':'' ?>><?= htmlspecialchars($bname) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if (!$editData): ?>
        <div class="form-group"><label class="form-label">Attachment</label><input type="file" name="attachment" class="form-control"></div>
        <?php endif; ?>
        <div style="display:flex;justify-content:flex-end;gap:10px">
            <button type="button" class="btn btn-outline" onclick="closeModal('noticeModal')">Cancel</button>
            <button type="submit" class="btn btn-primary"><?= $editData?'Update':'Post' ?></button>
        </div>
    </form>
    </div>
</div>
</div>

</div>
<?php include '../includes/footer.php'; ?>
</div>
</div>
<script><?php if ($showModal): ?>document.addEventListener('DOMContentLoaded',()=>openModal('noticeModal'));<?php endif; ?></script>
