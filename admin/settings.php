<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
Auth::requireRole('admin');

$settingsModel = new SettingsModel();
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $portalName = trim($_POST['portal_name']     ?? '');
    $univName   = trim($_POST['university_name'] ?? '');
    $deptName   = trim($_POST['department_name'] ?? '');
    $desc       = trim($_POST['description']     ?? '');
    $contact    = trim($_POST['contact_info']    ?? '');
    $logoPath   = null;

    if (empty($portalName) || empty($univName) || empty($deptName)) {
        $error = 'Portal, university, and department names are required.';
    } else {
        if (!empty($_FILES['logo']['name'])) {
            $uploader = new FileUpload('photos', ['jpg','jpeg','png'], 5*1024*1024);
            $res = $uploader->upload($_FILES['logo']);
            if ($res['success']) {
                $logoPath = $res['path'];
            } else {
                $error = $res['message'];
            }
        }
        if (!$error) {
            $settingsModel->update($portalName, $univName, $deptName, $desc, $contact, $logoPath);
            Helper::setFlash('success', 'Settings updated successfully.');
            Helper::redirect('admin/settings.php');
        }
    }
}

$settings = $settingsModel->get();

$pageTitle   = 'Settings';
$currentPage = 'Settings';
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
    <div><h2>⚙️ Portal Settings</h2><div class="page-subtitle">Customize portal name, university/department info, and logo</div></div>
</div>

<div class="card" style="max-width:680px">
<div class="card-body">
<form method="POST" enctype="multipart/form-data">
    <div class="form-group">
        <label class="form-label">Current Logo</label>
        <div style="display:flex;align-items:center;gap:16px">
            <div class="brand-icon" style="background:var(--primary-pale);width:60px;height:60px;border-radius:10px">
                <?php if ($settings['logo']): ?><img src="<?= UPLOAD_URL . htmlspecialchars($settings['logo']) ?>" alt=""><?php else: ?>🎓<?php endif; ?>
            </div>
            <input type="file" name="logo" class="form-control" accept=".jpg,.jpeg,.png" style="max-width:300px">
        </div>
    </div>

    <div class="form-group">
        <label class="form-label">Portal Name *</label>
        <input type="text" name="portal_name" class="form-control" required value="<?= htmlspecialchars($settings['portal_name']) ?>">
    </div>
    <div class="form-group">
        <label class="form-label">University Name *</label>
        <input type="text" name="university_name" class="form-control" required value="<?= htmlspecialchars($settings['university_name']) ?>">
    </div>
    <div class="form-group">
        <label class="form-label">Department Name *</label>
        <input type="text" name="department_name" class="form-control" required value="<?= htmlspecialchars($settings['department_name']) ?>">
    </div>
    <div class="form-group">
        <label class="form-label">Department Description</label>
        <textarea name="description" class="form-control" rows="4" style="resize:vertical"><?= htmlspecialchars($settings['description'] ?? '') ?></textarea>
        <div class="form-hint">Shown on the login page.</div>
    </div>
    <div class="form-group">
        <label class="form-label">Contact Information</label>
        <textarea name="contact_info" class="form-control" rows="2" style="resize:vertical"><?= htmlspecialchars($settings['contact_info'] ?? '') ?></textarea>
        <div class="form-hint">Shown in the footer of every page.</div>
    </div>

    <button type="submit" class="btn btn-primary">💾 Save Settings</button>
</form>
</div>
</div>

</div>
<?php include '../includes/footer.php'; ?>
</div>
</div>
