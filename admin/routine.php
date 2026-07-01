<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
Auth::requireRole('admin');

$routineModel = new RoutineModel();
$batchModel   = new BatchModel();
$studentModel = new Student();
$error        = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'upload') {
        $batchId = intval($_POST['batch_id'] ?? 0) ?: null; // null = all batches

        if (empty($_FILES['routine_file']['name'])) {
            $error = 'Please choose a PDF or image file to upload.';
        } else {
            $uploader = new FileUpload('routines', ['pdf','jpg','jpeg','png']);
            $res = $uploader->upload($_FILES['routine_file']);
            if ($res['success']) {
                $routineModel->addFile($batchId, $res['path'], Auth::userId());
                Helper::setFlash('success', 'Routine file uploaded successfully.');
            } else {
                $error = $res['message'];
            }
        }
        if (!$error) Helper::redirect('admin/routine.php');
    }

    if ($action === 'delete_file') {
        $id  = intval($_POST['file_id'] ?? 0);
        $row = $routineModel->deleteFile($id);
        if ($row) {
            $uploader = new FileUpload();
            $uploader->delete($row['file_path']);
        }
        Helper::setFlash('success', 'Routine file removed.');
        Helper::redirect('admin/routine.php');
    }

    if ($action === 'delete_entry') {
        $id = intval($_POST['routine_id'] ?? 0);
        $routineModel->delete($id);
        Helper::setFlash('success', 'Routine entry removed.');
        Helper::redirect('admin/routine.php');
    }
}

$batches = $batchModel->all();
$files   = $routineModel->getFiles();

// Structured entries grouped by batch
$filterBatch = intval($_GET['batch'] ?? 0);
$entries = $filterBatch ? $routineModel->getByBatch($filterBatch) : [];

$pageTitle   = 'Class Routine';
$currentPage = 'Routine';
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
    <div><h2>🗓️ Class Routine</h2><div class="page-subtitle">Upload routine files; CR-created structured entries shown below</div></div>
    <button class="btn btn-primary" onclick="openModal('uploadModal')">+ Upload Routine</button>
</div>

<div class="alert alert-info">ℹ️ The Class Representative (CR) of each batch creates structured routine entries (day/time/course). You can upload a master PDF/image routine here that's visible to all or a specific batch.</div>

<!-- Uploaded routine files -->
<h3 class="mb-2">📄 Uploaded Routine Files</h3>
<?php if (empty($files)): ?>
    <div class="empty-state mb-3"><div class="empty-icon">🗓️</div><p>No routine files uploaded yet.</p></div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:12px;margin-bottom:24px">
<?php foreach ($files as $f): ?>
<div class="routine-file-card">
    <div class="routine-file-icon"><?= Helper::fileIcon($f['file_path']) ?></div>
    <div style="flex:1">
        <div style="font-weight:700;color:var(--primary-dark)"><?= $f['batch_name'] ? htmlspecialchars($f['batch_name']) : 'All Batches' ?></div>
        <div style="font-size:0.78rem;color:var(--text-muted)">Uploaded <?= Helper::timeAgo($f['created_at']) ?></div>
    </div>
    <a href="<?= UPLOAD_URL . htmlspecialchars($f['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline">View</a>
    <form method="POST" onsubmit="return confirmAction('Remove this routine file?')">
        <input type="hidden" name="action" value="delete_file">
        <input type="hidden" name="file_id" value="<?= $f['id'] ?>">
        <button class="btn btn-sm btn-danger">🗑</button>
    </form>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Structured entries viewer -->
<h3 class="mb-2">📋 CR-Created Structured Entries</h3>
<form method="GET" class="search-bar">
    <select name="batch" class="form-control" style="max-width:260px" onchange="this.form.submit()">
        <option value="">— Select Batch to View —</option>
        <?php foreach ($batches as $b): ?>
        <option value="<?= $b['batch_id'] ?>" <?= $filterBatch==$b['batch_id']?'selected':'' ?>><?= htmlspecialchars($b['batch_name']) ?></option>
        <?php endforeach; ?>
    </select>
</form>

<?php if ($filterBatch): ?>
<?php
$cr = $studentModel->getCrOfBatch($filterBatch);
?>
<div class="alert alert-info mb-2">👑 CR for this batch: <strong><?= $cr ? htmlspecialchars($cr['name']) : 'Not assigned'?></strong></div>

<div class="card">
<div class="card-body" style="padding:0">
<div class="table-wrapper">
<table>
    <thead><tr><th>Day</th><th>Course</th><th>Teacher</th><th>Time</th><th>Actions</th></tr></thead>
    <tbody>
    <?php if (empty($entries)): ?>
        <tr><td colspan="5" class="text-center" style="padding:30px;color:var(--text-muted)">No structured entries yet for this batch.</td></tr>
    <?php else: foreach ($entries as $r): ?>
    <tr>
        <td><strong><?= htmlspecialchars($r['day']) ?></strong></td>
        <td class="course-cell"><strong><?= htmlspecialchars($r['course']) ?></strong></td>
        <td><?= htmlspecialchars($r['teacher'] ?: '—') ?></td>
        <td class="time-cell"><?= Helper::formatTime($r['start_time']) ?> – <?= Helper::formatTime($r['end_time']) ?></td>
        <td>
            <form method="POST" onsubmit="return confirmAction('Remove this routine entry?')">
                <input type="hidden" name="action" value="delete_entry">
                <input type="hidden" name="routine_id" value="<?= $r['routine_id'] ?>">
                <button class="btn btn-sm btn-danger">🗑</button>
            </form>
        </td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>
</div>
</div>
</div>
<?php endif; ?>

</div>
<?php include '../includes/footer.php'; ?>
</div>
</div>

<!-- Upload Modal -->
<div class="modal-overlay" id="uploadModal">
<div class="modal-box">
    <div class="modal-header"><span class="modal-title">+ Upload Routine File</span><button class="modal-close" onclick="closeModal('uploadModal')">✕</button></div>
    <div class="modal-body">
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="upload">
        <div class="form-group">
            <label class="form-label">Target</label>
            <select name="batch_id" class="form-control">
                <option value="">All Batches</option>
                <?php foreach ($batches as $b): ?>
                <option value="<?= $b['batch_id'] ?>"><?= htmlspecialchars($b['batch_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Routine File (PDF/Image) *</label>
            <input type="file" name="routine_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:10px">
            <button type="button" class="btn btn-outline" onclick="closeModal('uploadModal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Upload</button>
        </div>
    </form>
    </div>
</div>
</div>
