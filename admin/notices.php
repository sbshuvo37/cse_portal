<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
Auth::requireRole('admin');

$noticeModel = new NoticeModel();
$batchModel  = new BatchModel();
$studentModel= new Student();
$notifModel  = new NotificationModel();
$error       = '';
$editData    = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $title  = trim($_POST['title']       ?? '');
        $desc   = trim($_POST['description'] ?? '');
        $target = $_POST['target']           ?? 'all_students';
        $batchId= $target === 'specific_batch' ? intval($_POST['batch_id'] ?? 0) : null;
        $nid    = intval($_POST['notice_id'] ?? 0);

        if (empty($title) || empty($desc)) {
            $error = 'Title and description are required.';
        } elseif ($target === 'specific_batch' && !$batchId) {
            $error = 'Please select a batch for the target audience.';
        } else {
            if ($action === 'add') {
                $noticeId = $noticeModel->create($title, $desc, $target, $batchId, Auth::userId());

                // Handle file attachment
                if (!empty($_FILES['attachment']['name'])) {
                    $uploader = new FileUpload('notices');
                    $res = $uploader->upload($_FILES['attachment']);
                    if ($res['success']) $noticeModel->addFile($noticeId, $res['path']);
                }

                // Notify relevant students
                if ($target === 'specific_batch' && $batchId) {
                    $students = $studentModel->byBatch($batchId);
                    $userIds = array_column($students, 'user_id');
                    $notifModel->createBulk($userIds, 'New Notice: ' . $title, $desc, 'notice');
                }
                Helper::setFlash('success', 'Notice posted successfully.');
            } else {
                $noticeModel->update($nid, $title, $desc, $target, $batchId);
                if (!empty($_FILES['attachment']['name'])) {
                    $uploader = new FileUpload('notices');
                    $res = $uploader->upload($_FILES['attachment']);
                    if ($res['success']) $noticeModel->addFile($nid, $res['path']);
                }
                Helper::setFlash('success', 'Notice updated successfully.');
            }
            Helper::redirect('admin/notices.php');
        }
    }

    if ($action === 'delete') {
        $nid = intval($_POST['notice_id'] ?? 0);
        $noticeModel->delete($nid); // notice_files cascade-deleted via FK
        Helper::setFlash('success', 'Notice deleted.');
        Helper::redirect('admin/notices.php');
    }
}

if (isset($_GET['edit'])) {
    $editData = $noticeModel->findById(intval($_GET['edit']));
}

$notices   = $noticeModel->all();
$batches   = $batchModel->all();
$showModal = (isset($_GET['action']) && $_GET['action']==='add') || $editData;

$pageTitle   = 'Manage Notices';
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
    <div><h2>📢 Manage Notices</h2><div class="page-subtitle">Admin has full control — can edit/delete any notice</div></div>
    <a href="?action=add" class="btn btn-primary">+ Post Notice</a>
</div>

<div class="notice-list">
<?php if (empty($notices)): ?>
    <div class="empty-state"><div class="empty-icon">📭</div><p>No notices published yet.</p></div>
<?php else: foreach ($notices as $n): ?>
<?php $files = $noticeModel->getFiles($n['notice_id']); ?>
<div class="notice-card">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px">
        <div style="flex:1">
            <div class="notice-card-title"><?= htmlspecialchars($n['title']) ?></div>
            <div class="notice-card-meta">
                📅 <?= Helper::formatDate($n['created_at'], 'F j, Y \a\t g:i A') ?> &nbsp;|&nbsp; By <?= htmlspecialchars($n['poster']) ?>
                <span class="badge <?= $n['target']==='all_students'?'badge-info':'badge-primary' ?>">
                    <?= $n['target']==='all_students' ? 'All Students' : 'Batch: '.htmlspecialchars($n['batch_name'] ?? '') ?>
                </span>
            </div>
            <div class="notice-card-body"><?= nl2br(htmlspecialchars(substr($n['description'],0,300))) ?><?= strlen($n['description'])>300?'…':'' ?></div>
            <?php if (!empty($files)): ?>
            <div class="notice-attachments">
                <?php foreach ($files as $f): ?>
                <a href="<?= UPLOAD_URL . htmlspecialchars($f['file_path']) ?>" class="notice-attachment-chip" target="_blank"><?= Helper::fileIcon($f['file_path']) ?> Attachment</a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <div style="display:flex;gap:6px;flex-shrink:0">
            <a href="?edit=<?= $n['notice_id'] ?>" class="btn btn-sm btn-accent">✏️</a>
            <form method="POST" style="display:inline" onsubmit="return confirmAction('Delete this notice?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="notice_id" value="<?= $n['notice_id'] ?>">
                <button class="btn btn-sm btn-danger">🗑</button>
            </form>
        </div>
    </div>
</div>
<?php endforeach; endif; ?>
</div>

<!-- Modal -->
<div class="modal-overlay <?= $showModal?'open':'' ?>" id="noticeModal">
<div class="modal-box">
    <div class="modal-header">
        <span class="modal-title"><?= $editData?'✏️ Edit Notice':'+ Post Notice' ?></span>
        <button class="modal-close" onclick="closeModal('noticeModal')">✕</button>
    </div>
    <div class="modal-body">
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="<?= $editData?'edit':'add' ?>">
        <?php if ($editData): ?><input type="hidden" name="notice_id" value="<?= $editData['notice_id'] ?>"><?php endif; ?>
        <div class="form-group">
            <label class="form-label">Title *</label>
            <input type="text" name="title" class="form-control" required value="<?= htmlspecialchars($editData['title']??'') ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Description *</label>
            <textarea name="description" class="form-control" required rows="5" style="resize:vertical"><?= htmlspecialchars($editData['description']??'') ?></textarea>
        </div>
        <div class="form-group">
            <label class="form-label">Target Audience *</label>
            <select name="target" class="form-control" id="targetSelect" onchange="document.getElementById('batchSelectGroup').style.display = this.value==='specific_batch' ? 'block' : 'none'">
                <option value="all_students" <?= ($editData['target']??'')==='all_students'?'selected':'' ?>>All Students</option>
                <option value="specific_batch" <?= ($editData['target']??'')==='specific_batch'?'selected':'' ?>>Specific Batch</option>
            </select>
        </div>
        <div class="form-group" id="batchSelectGroup" style="display:<?= ($editData['target']??'')==='specific_batch'?'block':'none' ?>">
            <label class="form-label">Batch *</label>
            <select name="batch_id" class="form-control">
                <option value="">Select Batch</option>
                <?php foreach ($batches as $b): ?>
                <option value="<?= $b['batch_id'] ?>" <?= ($editData['batch_id']??0)==$b['batch_id']?'selected':'' ?>><?= htmlspecialchars($b['batch_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Attachment (optional)</label>
            <input type="file" name="attachment" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.ppt,.pptx,.zip">
            <div class="form-hint">PDF, JPG, PNG, DOC, PPT, ZIP — max 20 MB</div>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:10px">
            <button type="button" class="btn btn-outline" onclick="closeModal('noticeModal')">Cancel</button>
            <button type="submit" class="btn btn-primary"><?= $editData?'Update':'Post Notice' ?></button>
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
