<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
Auth::requireRole('admin');

$userModel    = new User();
$teacherModel = new Teacher();
$error        = '';
$editData     = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name   = trim($_POST['name']        ?? '');
        $email  = trim($_POST['email']       ?? '');
        $pass   = trim($_POST['password']    ?? '');
        $conf   = trim($_POST['confirm_password'] ?? '');
        $desig  = trim($_POST['designation'] ?? '');
        $phone  = trim($_POST['phone']       ?? '');

        if (empty($name) || empty($email) || empty($pass)) {
            $error = 'Please fill in all required fields.';
        } elseif ($pass !== $conf) {
            $error = 'Passwords do not match.';
        } elseif ($userModel->emailExists($email)) {
            $error = 'This email is already registered.';
        } else {
            $userId = $userModel->create($name, $email, $pass, 'teacher', 'active');
            $teacherModel->create($userId, $desig, $phone);
            Helper::setFlash('success', 'Teacher added successfully.');
            Helper::redirect('admin/teachers.php');
        }
    }

    if ($action === 'edit') {
        $userId     = intval($_POST['user_id'] ?? 0);
        $teacherId  = intval($_POST['teacher_id'] ?? 0);
        $name       = trim($_POST['name'] ?? '');
        $desig      = trim($_POST['designation'] ?? '');
        $phone      = trim($_POST['phone'] ?? '');

        if (empty($name)) {
            $error = 'Name is required.';
        } else {
            $userModel->updateBasicInfo($userId, $name);
            $teacherModel->update($teacherId, $desig, $phone);
            Helper::setFlash('success', 'Teacher updated successfully.');
            Helper::redirect('admin/teachers.php');
        }
    }

    if ($action === 'delete') {
        $userId = intval($_POST['user_id'] ?? 0);
        $userModel->delete($userId);
        Helper::setFlash('success', 'Teacher account deleted.');
        Helper::redirect('admin/teachers.php');
    }
}

if (isset($_GET['edit'])) {
    $editData = $teacherModel->findById(intval($_GET['edit']));
}

$teachers  = $teacherModel->all();
$showModal = (isset($_GET['action']) && $_GET['action']==='add') || $editData;

$pageTitle   = 'Manage Teachers';
$currentPage = 'Teachers';
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
    <div><h2>👨‍🏫 Manage Teachers</h2><div class="page-subtitle">Faculty account management</div></div>
    <a href="?action=add" class="btn btn-primary">+ Add Teacher</a>
</div>

<div class="card">
<div class="card-body" style="padding:0">
<div class="table-wrapper">
<table id="dataTable">
    <thead><tr><th>#</th><th>Name</th><th>Designation</th><th>Phone</th><th>Actions</th></tr></thead>
    <tbody>
    <?php if (empty($teachers)): ?>
        <tr><td colspan="5" class="text-center" style="padding:40px;color:var(--text-muted)">No teachers found.</td></tr>
    <?php else: foreach ($teachers as $i => $t): ?>
    <tr>
        <td><?= $i+1 ?></td>
        <td>
            <div style="font-weight:600"><?= htmlspecialchars($t['name']) ?></div>
            <div style="font-size:0.75rem;color:var(--text-muted)"><?= htmlspecialchars($t['email']) ?></div>
        </td>
        <td><?= htmlspecialchars($t['designation'] ?: '—') ?></td>
        <td><?= htmlspecialchars($t['phone'] ?: '—') ?></td>
        <td>
            <div style="display:flex;gap:6px">
                <a href="?edit=<?= $t['teacher_id'] ?>" class="btn btn-sm btn-accent" style="height:32px !important;">✏️ Edit</a>
                <form method="POST" style="display:inline; margin:0;" onsubmit="return confirmAction('Delete teacher account?')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="user_id" value="<?= $t['user_id'] ?>">
                    <button class="btn btn-sm btn-danger" style="height:32px !important;">🗑 Delete</button>
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

<div class="modal-overlay <?= $showModal?'open':'' ?>" id="teacherModal">
<div class="modal-box">
    <div class="modal-header">
        <span class="modal-title"><?= $editData?'✏️ Edit Teacher':'+ Add Teacher' ?></span>
        <button class="modal-close" onclick="closeModal('teacherModal')">✕</button>
    </div>
    <div class="modal-body">
    <form method="POST">
        <input type="hidden" name="action" value="<?= $editData?'edit':'add' ?>">
        <?php if ($editData): ?>
            <input type="hidden" name="user_id" value="<?= $editData['user_id'] ?>">
            <input type="hidden" name="teacher_id" value="<?= $editData['teacher_id'] ?>">
        <?php endif; ?>
        <div class="form-row">
            <div class="form-group"><label class="form-label">Full Name *</label><input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($editData['name'] ?? '') ?>"></div>
            <?php if (!$editData): ?>
            <div class="form-group"><label class="form-label">Email *</label><input type="email" name="email" class="form-control" required></div>
            <?php endif; ?>
            <div class="form-group"><label class="form-label">Designation</label><input type="text" name="designation" class="form-control" placeholder="e.g. Professor" value="<?= htmlspecialchars($editData['designation'] ?? '') ?>"></div>
            <div class="form-group"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($editData['phone'] ?? '') ?>"></div>
            <?php if (!$editData): ?>
            <div class="form-group"><label class="form-label">Password *</label><input type="password" name="password" class="form-control" required minlength="6"></div>
            <div class="form-group"><label class="form-label">Confirm Password *</label><input type="password" name="confirm_password" class="form-control" required minlength="6"></div>
            <?php endif; ?>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:10px">
            <button type="button" class="btn btn-outline" onclick="closeModal('teacherModal')">Cancel</button>
            <button type="submit" class="btn btn-primary"><?= $editData?'Update':'Add Teacher' ?></button>
        </div>
    </form>
    </div>
</div>
</div>

</div>
<?php include '../includes/footer.php'; ?>
</div>
</div>
<script><?php if ($showModal): ?>document.addEventListener('DOMContentLoaded',()=>openModal('teacherModal'));<?php endif; ?></script>