<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
Auth::requireRole('student');

$studentModel = new Student();
$userModel    = new User();
$error        = '';
$uid          = Auth::userId();

$student = $studentModel->findByUserId($uid);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'change_password') {
        $current = trim($_POST['current_password'] ?? '');
        $newPass = trim($_POST['new_password']      ?? '');
        $confirm = trim($_POST['confirm_password']  ?? '');

        if (!$userModel->verifyPassword($uid, $current)) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($newPass) < 6) {
            $error = 'New password must be at least 6 characters.';
        } elseif ($newPass !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $userModel->updatePassword($uid, $newPass);
            Helper::setFlash('success', 'Password updated successfully.');
            Helper::redirect('student/profile.php');
        }
    }

    if ($action === 'update_profile') {
        $name      = trim($_POST['name']  ?? '');
        $phone     = trim($_POST['phone'] ?? '');
        $photoPath = $student['profile_photo'];

        if (empty($name)) {
            $error = 'Name is required.';
        } else {
            if (!empty($_FILES['photo']['name'])) {
                $uploader = new FileUpload('photos', ALLOWED_PHOTO_EXTENSIONS, 5*1024*1024);
                $res = $uploader->upload($_FILES['photo']);
                if ($res['success']) {
                    $photoPath = $res['path'];
                } else {
                    $error = 'Photo upload failed: ' . $res['message'];
                }
            }

            if (!$error) {
                $userModel->updateBasicInfo($uid, $name);
                $userModel->updatePhoto($uid, $photoPath);
                $studentModel->updatePhone($uid, $phone);
                Helper::setFlash('success', 'Profile updated successfully.');
                Helper::redirect('student/profile.php');
            }
        }
    }
}

$pageTitle   = 'My Profile';
$currentPage = 'My Profile';
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
    <div><h2>👤 My Profile</h2><div class="page-subtitle">View and edit your profile details</div></div>
</div>

<div style="display:grid; grid-template-columns:1fr 1.4fr; gap:24px; align-items:start">
    <div class="profile-card">
        <div class="profile-header" style="background:linear-gradient(135deg, #0c4a6e, #0284c7)">
            <div class="profile-avatar">
                <?php if ($student['profile_photo']): ?><img src="<?= UPLOAD_URL . htmlspecialchars($student['profile_photo']) ?>" alt=""><<?php else: ?><?= Helper::initials($student['name']) ?><?php endif; ?>
            </div>
            <div>
                <div class="profile-info-name"><?= htmlspecialchars($student['name']) ?></div>
                <div class="profile-info-role">🎓 Student <?php if($student['is_cr']): ?><span class="badge badge-primary" style="margin-left:6px">CR</span><?php endif; ?></div>
            </div>
        </div>
        <div class="profile-body" style="padding: 20px;">
            <div class="profile-field"><span class="profile-field-label">📧 Email</span><span class="profile-field-value"><?= htmlspecialchars($student['email']) ?></span></div>
            <div class="profile-field"><span class="profile-field-label">🎫 Roll</span><span class="profile-field-value"><?= htmlspecialchars($student['roll']) ?></span></div>
            <div class="profile-field"><span class="profile-field-label">📋 Reg. No</span><span class="profile-field-value"><?= htmlspecialchars($student['registration_no']) ?></span></div>
            <div class="profile-field"><span class="profile-field-label">🗂️ Batch</span><span class="profile-field-value"><?= htmlspecialchars($student['batch_name'] ?? '—') ?></span></div>
            <div class="profile-field"><span class="profile-field-label">📅 Session</span><span class="profile-field-value"><?= htmlspecialchars($student['session'] ?? '—') ?></span></div>
            <div class="profile-field"><span class="profile-field-label">📞 Phone</span><span class="profile-field-value"><?= htmlspecialchars($student['phone'] ?: '—') ?></span></div>
        </div>
    </div>

    <div>
        <div class="card mb-3">
            <div class="card-header"><span class="card-title">✏️ Update Information</span></div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="form-group"><label class="form-label">Full Name</label><input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($student['name']) ?>"></div>
                    <div class="form-group"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($student['phone']??'') ?>"></div>
                    <div class="form-group"><label class="form-label">New Photo</label><input type="file" name="photo" class="form-control" accept=".jpg,.jpeg,.png"></div>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><span class="card-title">🔒 Change Password</span></div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">
                    <div class="form-group"><label class="form-label">Current Password</label><input type="password" name="current_password" class="form-control" required></div>
                    <div class="form-row">
                        <div class="form-group"><label class="form-label">New Password</label><input type="password" name="new_password" class="form-control" required minlength="6"></div>
                        <div class="form-group"><label class="form-label">Confirm New Password</label><input type="password" name="confirm_password" class="form-control" required minlength="6"></div>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

</div>
<?php include '../includes/footer.php'; ?>
</div>
</div>