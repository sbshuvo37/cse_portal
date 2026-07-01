<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
Auth::requireRole('admin');

$batchModel = new BatchModel();
$error      = '';
$editData   = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $name    = trim($_POST['batch_name'] ?? '');
        $session = trim($_POST['session']    ?? '');
        $bid     = intval($_POST['batch_id'] ?? 0);

        if (empty($name) || empty($session)) {
            $error = 'Both batch name and session are required.';
        } else {
            if ($action === 'add') {
                $batchModel->create($name, $session);
                Helper::setFlash('success', 'Batch added successfully.');
            } else {
                $batchModel->update($bid, $name, $session);
                Helper::setFlash('success', 'Batch updated successfully.');
            }
            Helper::redirect('admin/batches.php');
        }
    }

    if ($action === 'delete') {
        $bid = intval($_POST['batch_id'] ?? 0);
        $batchModel->delete($bid);
        Helper::setFlash('success', 'Batch removed.');
        Helper::redirect('admin/batches.php');
    }
}

if (isset($_GET['edit'])) {
    $editData = $batchModel->findById(intval($_GET['edit']));
}

$batches   = $batchModel->all();
$showModal = (isset($_GET['action']) && $_GET['action']==='add') || $editData;

$pageTitle   = 'Manage Batches';
$currentPage = 'Batches';
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
    <div><h2>🗂️ Manage Batches</h2><div class="page-subtitle">Simple batch and session management</div></div>
    <a href="?action=add" class="btn btn-primary">+ Add Batch</a>
</div>

<div class="card">
<div class="card-body" style="padding:0">
<div class="table-wrapper">
<table>
    <thead><tr><th>#</th><th>Batch Name</th><th>Session</th><th>Created</th><th>Actions</th></tr></thead>
    <tbody>
    <?php if (empty($batches)): ?>
        <tr><td colspan="5" class="text-center" style="padding:40px;color:var(--text-muted)">No batches found.</td></tr>
    <?php else: foreach ($batches as $i => $b): ?>
    <tr>
        <td><?= $i+1 ?></td>
        <td><strong><?= htmlspecialchars($b['batch_name']) ?></strong></td>
        <td><?= htmlspecialchars($b['session']) ?></td>
        <td><?= Helper::formatDate($b['created_at']) ?></td>
        <td>
            <div style="display:flex;gap:6px">
                <a href="?edit=<?= $b['batch_id'] ?>" class="btn btn-sm btn-accent">✏️</a>
                <form method="POST" style="display:inline" onsubmit="return confirmAction('Delete this batch? Existing students/courses referencing it will be unlinked.')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="batch_id" value="<?= $b['batch_id'] ?>">
                    <button class="btn btn-sm btn-danger">🗑</button>
                </form>
            </div>
        </td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>
</div>
</div>
</div>

<div class="modal-overlay <?= $showModal?'open':'' ?>" id="batchModal">
<div class="modal-box" style="max-width:420px">
    <div class="modal-header">
        <span class="modal-title"><?= $editData?'✏️ Edit Batch':'+ Add Batch' ?></span>
        <button class="modal-close" onclick="closeModal('batchModal')">✕</button>
    </div>
    <div class="modal-body">
    <form method="POST">
        <input type="hidden" name="action" value="<?= $editData?'edit':'add' ?>">
        <?php if ($editData): ?><input type="hidden" name="batch_id" value="<?= $editData['batch_id'] ?>"><?php endif; ?>
        <div class="form-group">
            <label class="form-label">Batch Name *</label>
            <input type="text" name="batch_name" class="form-control" required placeholder="e.g. 2022-23" value="<?= htmlspecialchars($editData['batch_name']??'') ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Session *</label>
            <input type="text" name="session" class="form-control" required placeholder="e.g. 2022-2026" value="<?= htmlspecialchars($editData['session']??'') ?>">
        </div>
        <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:10px">
            <button type="button" class="btn btn-outline" onclick="closeModal('batchModal')">Cancel</button>
            <button type="submit" class="btn btn-primary"><?= $editData?'Update Batch':'Add Batch' ?></button>
        </div>
    </form>
    </div>
</div>
</div>

</div>
<?php include '../includes/footer.php'; ?>
</div>
</div>
<script><?php if ($showModal): ?>document.addEventListener('DOMContentLoaded',()=>openModal('batchModal'));<?php endif; ?></script>
