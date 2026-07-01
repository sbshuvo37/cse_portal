<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
Auth::requireRole('admin');

$resultModel  = new ResultModel();
$batchModel   = new BatchModel();
$studentModel = new Student();
$notifModel   = new NotificationModel();
$error        = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'upload') {
        $batchId = intval($_POST['batch_id'] ?? 0);
        $title   = trim($_POST['title'] ?? '');

        if (!$batchId) {
            $error = 'Please select a batch.';
        } elseif (empty($_FILES['result_file']['name'])) {
            $error = 'Please choose a result file (PDF/JPG/PNG) to upload.';
        } else {
            $uploader = new FileUpload('results', ['pdf','jpg','jpeg','png']);
            $res = $uploader->upload($_FILES['result_file']);
            if ($res['success']) {
                $resultModel->addFile($batchId, null, Auth::userId(), $res['path'], $title ?: 'Final Result');
                $students = $studentModel->byBatch($batchId);
                $notifModel->createBulk(array_column($students,'user_id'), 'Final Result Published', $title ?: 'Final Result has been published.', 'result');
                Helper::setFlash('success', 'Final result file uploaded successfully.');
            } else {
                $error = $res['message'];
            }
        }
        if (!$error) Helper::redirect('admin/results.php');
    }

    if ($action === 'delete') {
        $id  = intval($_POST['file_id'] ?? 0);
        $row = $resultModel->deleteFile($id);
        if ($row) {
            $uploader = new FileUpload();
            $uploader->delete($row['file_path']);
        }
        Helper::setFlash('success', 'Result file removed.');
        Helper::redirect('admin/results.php');
    }
}

$batches = $batchModel->all();
$allFiles = [];
foreach ($batches as $b) {
    $files = $resultModel->getFilesByBatch($b['batch_id']);
    foreach ($files as $f) { $f['batch_name'] = $b['batch_name']; $allFiles[] = $f; }
}
// Only show admin's own batch-wise final results (course_id IS NULL distinguishes from teacher uploads)
$allFiles = array_filter($allFiles, fn($f) => empty($f['course_id']));

$pageTitle   = 'Final Results';
$currentPage = 'Final Results';
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
    <div><h2>📈 Final Results</h2><div class="page-subtitle">Upload official batch-wise final results (PDF/JPG/PNG)</div></div>
    <button class="btn btn-primary" onclick="openModal('uploadModal')">+ Upload Result</button>
</div>

<?php if (empty($allFiles)): ?>
    <div class="empty-state"><div class="empty-icon">📈</div><p>No final results uploaded yet.</p></div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:12px">
<?php foreach ($allFiles as $f): ?>
<div class="routine-file-card">
    <div class="routine-file-icon"><?= Helper::fileIcon($f['file_path']) ?></div>
    <div style="flex:1">
        <div style="font-weight:700;color:var(--primary-dark)"><?= htmlspecialchars($f['title'] ?: 'Final Result') ?></div>
        <div style="font-size:0.78rem;color:var(--text-muted)">Batch: <?= htmlspecialchars($f['batch_name']) ?> · Uploaded <?= Helper::timeAgo($f['created_at']) ?></div>
    </div>
    <a href="<?= UPLOAD_URL . htmlspecialchars($f['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline">View</a>
    <form method="POST" onsubmit="return confirmAction('Remove this result file?')">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="file_id" value="<?= $f['id'] ?>">
        <button class="btn btn-sm btn-danger">🗑</button>
    </form>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

</div>
<?php include '../includes/footer.php'; ?>
</div>
</div>

<div class="modal-overlay" id="uploadModal">
<div class="modal-box">
    <div class="modal-header"><span class="modal-title">+ Upload Final Result</span><button class="modal-close" onclick="closeModal('uploadModal')">✕</button></div>
    <div class="modal-body">
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="upload">
        <div class="form-group">
            <label class="form-label">Batch *</label>
            <select name="batch_id" class="form-control" required>
                <option value="">Select Batch</option>
                <?php foreach ($batches as $b): ?>
                <option value="<?= $b['batch_id'] ?>"><?= htmlspecialchars($b['batch_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" placeholder="e.g. Final Result - 5th Semester">
        </div>
        <div class="form-group">
            <label class="form-label">Result File (PDF/JPG/PNG) *</label>
            <input type="file" name="result_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:10px">
            <button type="button" class="btn btn-outline" onclick="closeModal('uploadModal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Upload</button>
        </div>
    </form>
    </div>
</div>
</div>
