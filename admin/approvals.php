<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
Auth::requireRole('admin');

$userModel    = new User();
$studentModel = new Student();
$teacherModel = new Teacher();
$notifModel   = new NotificationModel();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'approve_user' || $action === 'reject_user') {
        $userId = intval($_POST['user_id'] ?? 0);
        $user   = $userModel->findById($userId);
        if ($user) {
            if ($action === 'approve_user') {
                $userModel->updateStatus($userId, 'active');
                $notifModel->create($userId, 'Registration Approved', 'Your account has been approved. You can now log in.', 'approval');
                Helper::setFlash('success', 'User approved successfully.');
            } else {
                $userModel->updateStatus($userId, 'rejected');
                Helper::setFlash('success', 'User registration rejected.');
            }
        }
        Helper::redirect('admin/approvals.php');
    }
}

$pendingUsers = $userModel->getPendingUsers();
$pageTitle   = 'Approvals';
$currentPage = 'Approvals';
include '../includes/header.php';
?>
<div class="portal-layout">
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
<?php include '../includes/navbar.php'; ?>
<div class="page-content">

<?php $flash = Helper::getFlash(); if ($flash): ?>
<div class="alert alert-<?= $flash['type'] ?>" data-auto-dismiss><?= htmlspecialchars($flash['message']) ?></div>
<?php endif; ?>

<div class="page-header">
    <div><h2>✅ Approvals</h2><div class="page-subtitle">Review pending registrations</div></div>
</div>

<h3 class="mb-2">⏳ Pending Registrations (<?= count($pendingUsers) ?>)</h3>
<?php if (empty($pendingUsers)): ?>
    <div class="empty-state mb-3"><div class="empty-icon">✅</div><p>No pending registrations.</p></div>
<?php else: ?>
<?php foreach ($pendingUsers as $u): ?>
<?php
    $student = $u['role']==='student' ? $studentModel->findByUserId($u['id']) : null;
    $teacher = $u['role']==='teacher' ? $teacherModel->findByUserId($u['id']) : null;
?>
<div class="approval-card" style="border: 1px solid var(--border); border-left: 4px solid var(--warning); border-radius: var(--radius); padding: 18px 20px; margin-bottom: 14px;">
    <div class="approval-row" style="display: flex; justify-content: space-between; align-items: center; gap: 14px; flex-wrap: wrap;">
        <div style="display:flex; gap:14px; align-items:center;">
            <div class="user-avatar" style="width:50px; height:50px;">
                <?php if ($u['profile_photo']): ?><img src="<?= UPLOAD_URL . htmlspecialchars($u['profile_photo']) ?>" alt=""><?php else: ?><?= Helper::initials($u['name']) ?><?php endif; ?>
            </div>
            <div>
                <div style="font-weight:700; color:var(--primary-dark);"><?= htmlspecialchars($u['name']) ?> <span class="badge badge-info" style="margin-left:6px;"><?= htmlspecialchars($u['role']) ?></span></div>
                <div style="font-size:0.8rem; color:var(--text-muted);">📧 <?= htmlspecialchars($u['email']) ?></div>
                <?php if ($student): ?>
                    <div style="font-size:0.78rem; color:var(--text-muted); margin-top:4px;">Roll: <?= htmlspecialchars($student['roll']) ?> | Batch: <?= htmlspecialchars($student['batch_name'] ?? '—') ?></div>
                <?php elseif ($teacher): ?>
                    <div style="font-size:0.78rem; color:var(--text-muted); margin-top:4px;">Designation: <?= htmlspecialchars($teacher['designation']) ?></div>
                <?php endif; ?>
            </div>
        </div>
        <div style="display:flex; gap:8px; align-items:center; align-self:center;">
            <form method="POST" style="margin:0;" onsubmit="return confirmAction('Approve this registration?')">
                <input type="hidden" name="action" value="approve_user">
                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                <button class="btn btn-sm btn-success">✅ Approve</button>
            </form>
            <form method="POST" style="margin:0;" onsubmit="return confirmAction('Reject this registration?')">
                <input type="hidden" name="action" value="reject_user">
                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                <button class="btn btn-sm btn-danger">✕ Reject</button>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

</div>
<?php include '../includes/footer.php'; ?>
</div>
</div>