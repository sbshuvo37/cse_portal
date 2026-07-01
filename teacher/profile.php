<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
Auth::requireRole('teacher');

$teacherModel = new Teacher();
$userModel    = new User();
$prModel      = new ProfileRequestModel();
$error        = '';
$uid          = Auth::userId();

$teacher = $teacherModel->findByUserId($uid);
$hasPendingRequest = $prModel->hasPending($uid);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── Password change: direct, no approval needed ─────────
    if ($action === 'change_password') {
        $current = trim($_POST['current_password'] ?? '');
        $newPass = trim($_POST['new_password']      ?? '');
        $confirm = trim($_POST['confirm_password']  ?? '');

        if (!$userModel->verifyPassword($uid, $current)) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($newPass) < 6) {
            $error = 'New password must be at least 6 characters.';
        } elseif ($newPass !== $confirm) {
            $error = 'New passwords do not match.';
        } else {
            $userModel->updatePassword($uid, $newPass);
            Helper::setFlash('success', 'Password changed successfully.');
            Helper::redirect('teacher/profile.php');
        }
    }

    // ── Profile info change: requires admin approval ────────
    if ($action === 'request_update') {
        if ($hasPendingRequest) {
            $error = 'You already have a pending profile change request. Please wait for admin review.';
        } else {
            $data = [
                'name'        => trim($_POST['name']        ?? $teacher['name']),
                'designation' => trim($_POST['designation'] ?? $teacher['designation']),
                'phone'       => trim($_POST['phone']        ?? $teacher['phone']),
            ];

            if (!empty($_FILES['photo']['name'])) {
                $uploader = new FileUpload('photos', ALLOWED_PHOTO_EXTENSIONS, 5*1024*1024);
                $res = $uploader->upload($_FILES['photo']);
                if ($res['success']) {
                    $data['photo'] = $res['path'];
                } else {
                    $error = 'Photo upload failed: ' . $res['message'];
                }
            }

            if (!$error) {
                $prModel->create($uid, $data);
                Helper::setFlash('success', 'Profile change request submitted. Awaiting admin approval.');
                Helper::redirect('teacher/profile.php');
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
    <div><h2>👤 My Profile</h2><div class="page-subtitle">View profile, change password, or request information updates</div></div>
</div>

<?php if ($hasPendingRequest): ?>
<div class="pending-request-banner">⏳ You have a pending profile change request awaiting admin approval. New requests are disabled until it's reviewed.</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1.4fr;gap:24px;align-items:start">

    <div class="profile-card">
        <div class="profile-header">
            <div class="profile-avatar">
                <?php if ($teacher['profile_photo']): ?><img src="<?= UPLOAD_URL . htmlspecialchars($teacher['profile_photo']) ?>" alt=""><?php else: ?><?= Helper::initials($teacher['name']) ?><?php endif; ?>
            </div>
            <div>
                <div class="profile-info-name"><?= htmlspecialchars($teacher['name']) ?></div>
                <div class="profile-info-role">👨‍🏫 Teacher · <?= htmlspecialchars($teacher['designation'] ?: 'Faculty') ?></div>
            </div>
        </div>
        <div class="profile-body">
            <div class="profile-field"><span class="profile-field-label">📧 Email</span><span class="profile-field-value"><?= htmlspecialchars($teacher['email']) ?></span></div>
            <div class="profile-field"><span class="profile-field-label">🏢 Designation</span><span class="profile-field-value"><?= htmlspecialchars($teacher['designation'] ?: '—') ?></span></div>
            <div class="profile-field"><span class="profile-field-label">📞 Phone</span><span class="profile-field-value"><?= htmlspecialchars($teacher['phone'] ?: '—') ?></span></div>
            <div class="profile-field"><span class="profile-field-label">📅 Joined</span><span class="profile-field-value"><?= Helper::formatDate($teacher['created_at']) ?></span></div>
        </div>
    </div>

    <div style="display:flex;flex-direction:column;gap:20px">
        <!-- Profile info change request -->
        <div class="card">
            <div class="card-header"><span class="card-title">✏️ Request Information Update</span></div>
            <div class="card-body">
                <div class="form-hint mb-2">⚠️ Changes to your name, designation, phone, or photo require admin approval before taking effect.</div>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="request_update">
                    <div class="form-group"><label class="form-label">Full Name</label><input type="text" name="name" class="form-control" value="<?= htmlspecialchars($teacher['name']) ?>" <?= $hasPendingRequest?'disabled':'' ?>></div>
                    <div class="form-row">
                        <div class="form-group"><label class="form-label">Designation</label><input type="text" name="designation" class="form-control" value="<?= htmlspecialchars($teacher['designation']??'') ?>" <?= $hasPendingRequest?'disabled':'' ?>></div>
                        <div class="form-group"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($teacher['phone']??'') ?>" <?= $hasPendingRequest?'disabled':'' ?>></div>
                    </div>
                    <div class="form-group"><label class="form-label">New Photo (optional)</label><input type="file" name="photo" class="form-control" accept=".jpg,.jpeg,.png" <?= $hasPendingRequest?'disabled':'' ?>></div>
                    <button type="submit" class="btn btn-primary" <?= $hasPendingRequest?'disabled':'' ?>>Submit for Approval</button>
                </form>
            </div>
        </div>

        <!-- Password change (direct, no approval) -->
        <div class="card">
            <div class="card-header"><span class="card-title">🔒 Change Password</span></div>
            <div class="card-body">
                <div class="form-hint mb-2">✅ Password changes take effect immediately — no approval needed.</div>
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
