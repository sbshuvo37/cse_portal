<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
Auth::requireRole('admin');

$userModel    = new User();
$studentModel = new Student();
$teacherModel = new Teacher();
$prModel      = new ProfileRequestModel();
$notifModel   = new NotificationModel();
$error        = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── Approve/Reject new registration ─────────────────────
    if ($action === 'approve_user' || $action === 'reject_user') {
        $userId = intval($_POST['user_id'] ?? 0);
        $user   = $userModel->findById($userId);

        if ($user) {
            if ($action === 'approve_user') {
                $userModel->updateStatus($userId, 'active');
                $notifModel->create($userId, 'Registration Approved', 'Your account has been approved. You can now log in.', 'approval');
                Helper::setFlash('success', 'User approved successfully. They can now log in.');
            } else {
                $userModel->updateStatus($userId, 'rejected');
                $notifModel->create($userId, 'Registration Rejected', 'Your registration request was rejected. Please contact the department.', 'approval');
                Helper::setFlash('success', 'User registration rejected.');
            }
        }
        Helper::redirect('admin/approvals.php');
    }

    // ── Approve/Reject profile-change request ───────────────
    if ($action === 'approve_request' || $action === 'reject_request') {
        $reqId = intval($_POST['request_id'] ?? 0);
        $req   = $prModel->findById($reqId);

        if ($req) {
            if ($action === 'approve_request') {
                $data = json_decode($req['requested_data'], true) ?: [];
                if (!empty($data['name'])) {
                    $userModel->updateBasicInfo((int)$req['user_id'], $data['name']);
                }
                if ($req['role'] === 'teacher') {
                    $teacher = $teacherModel->findByUserId((int)$req['user_id']);
                    if ($teacher) {
                        $teacherModel->update(
                            $teacher['teacher_id'],
                            $data['designation'] ?? $teacher['designation'],
                            $data['phone'] ?? $teacher['phone']
                        );
                    }
                } elseif ($req['role'] === 'student') {
                    $studentModel->updatePhone((int)$req['user_id'], $data['phone'] ?? '');
                }
                if (!empty($data['photo'])) {
                    $userModel->updatePhoto((int)$req['user_id'], $data['photo']);
                }
                $prModel->approve($reqId);
                $notifModel->create((int)$req['user_id'], 'Profile Update Approved', 'Your profile change request has been approved.', 'approval');
                Helper::setFlash('success', 'Profile change request approved.');
            } else {
                $prModel->reject($reqId);
                $notifModel->create((int)$req['user_id'], 'Profile Update Rejected', 'Your profile change request was rejected.', 'approval');
                Helper::setFlash('success', 'Profile change request rejected.');
            }
        }
        Helper::redirect('admin/approvals.php');
    }
}

$pendingUsers    = $userModel->getPendingUsers();
$pendingRequests = $prModel->allPending();

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
    <div><h2>✅ Approvals</h2><div class="page-subtitle">Review pending registrations and profile change requests</div></div>
</div>

<!-- ============ Pending Registrations ============ -->
<h3 class="mb-2">⏳ Pending Registrations (<?= count($pendingUsers) ?>)</h3>

<?php if (empty($pendingUsers)): ?>
    <div class="empty-state mb-3"><div class="empty-icon">✅</div><p>No pending registrations.</p></div>
<?php else: ?>
<?php foreach ($pendingUsers as $u): ?>
<?php
    $student = $u['role']==='student' ? $studentModel->findByUserId($u['id']) : null;
    $teacher = $u['role']==='teacher' ? $teacherModel->findByUserId($u['id']) : null;
?>
<div class="approval-card">
    <div class="approval-row">
        <div style="display:flex;gap:14px;align-items:center">
            <div class="user-avatar" style="width:50px;height:50px;font-size:1rem">
                <?php if ($u['profile_photo']): ?><img src="<?= UPLOAD_URL . htmlspecialchars($u['profile_photo']) ?>" alt=""><?php else: ?><?= Helper::initials($u['name']) ?><?php endif; ?>
            </div>
            <div>
                <div style="font-weight:700;color:var(--primary-dark)"><?= htmlspecialchars($u['name']) ?> <span class="badge badge-info" style="margin-left:6px;text-transform:capitalize"><?= htmlspecialchars($u['role']) ?></span></div>
                <div style="font-size:0.8rem;color:var(--text-muted)">📧 <?= htmlspecialchars($u['email']) ?></div>
                <?php if ($student): ?>
                    <div style="font-size:0.78rem;color:var(--text-muted);margin-top:4px">
                        Roll: <?= htmlspecialchars($student['roll']) ?> &nbsp;|&nbsp; Reg: <?= htmlspecialchars($student['registration_no']) ?>
                        &nbsp;|&nbsp; Batch: <?= htmlspecialchars($student['batch_name'] ?? '—') ?> &nbsp;|&nbsp; Phone: <?= htmlspecialchars($student['phone']) ?>
                    </div>
                <?php elseif ($teacher): ?>
                    <div style="font-size:0.78rem;color:var(--text-muted);margin-top:4px">
                        Designation: <?= htmlspecialchars($teacher['designation']) ?> &nbsp;|&nbsp; Phone: <?= htmlspecialchars($teacher['phone']) ?>
                    </div>
                <?php endif; ?>
                <div style="font-size:0.72rem;color:var(--text-light);margin-top:4px">Requested <?= Helper::timeAgo($u['created_at']) ?></div>
            </div>
        </div>
        <div style="display:flex;gap:8px;flex-shrink:0">
            <form method="POST" style="display:inline" onsubmit="return confirmAction('Approve this registration?')">
                <input type="hidden" name="action" value="approve_user">
                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                <button class="btn btn-sm btn-success">✅ Approve</button>
            </form>
            <form method="POST" style="display:inline" onsubmit="return confirmAction('Reject this registration?')">
                <input type="hidden" name="action" value="reject_user">
                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                <button class="btn btn-sm btn-danger">✕ Reject</button>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<!-- ============ Pending Profile Change Requests ============ -->
<h3 class="mb-2 mt-3">📝 Pending Profile Change Requests (<?= count($pendingRequests) ?>)</h3>

<?php if (empty($pendingRequests)): ?>
    <div class="empty-state"><div class="empty-icon">✅</div><p>No pending profile change requests.</p></div>
<?php else: ?>
<?php foreach ($pendingRequests as $req): ?>
<?php $data = json_decode($req['requested_data'], true) ?: []; ?>
<div class="approval-card">
    <div class="approval-row">
        <div>
            <div style="font-weight:700;color:var(--primary-dark)"><?= htmlspecialchars($req['name']) ?> <span class="badge badge-info" style="margin-left:6px;text-transform:capitalize"><?= htmlspecialchars($req['role']) ?></span></div>
            <div style="font-size:0.8rem;color:var(--text-muted);margin-top:6px">Requested changes:</div>
            <ul style="font-size:0.8rem;margin:6px 0 0 18px;color:var(--text)">
                <?php foreach ($data as $field => $val): ?>
                    <?php if ($field === 'photo'): ?>
                        <li><strong>Photo:</strong> New photo uploaded</li>
                    <?php else: ?>
                        <li><strong><?= htmlspecialchars(ucfirst($field)) ?>:</strong> <?= htmlspecialchars((string)$val) ?></li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
            <div style="font-size:0.72rem;color:var(--text-light);margin-top:6px">Requested <?= Helper::timeAgo($req['created_at']) ?></div>
        </div>
        <div style="display:flex;gap:8px;flex-shrink:0">
            <form method="POST" style="display:inline" onsubmit="return confirmAction('Approve this profile change?')">
                <input type="hidden" name="action" value="approve_request">
                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                <button class="btn btn-sm btn-success">✅ Approve</button>
            </form>
            <form method="POST" style="display:inline" onsubmit="return confirmAction('Reject this profile change?')">
                <input type="hidden" name="action" value="reject_request">
                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
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
