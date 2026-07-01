<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
Auth::requireRole('admin');

$userModel    = new User();
$studentModel = new Student();
$batchModel   = new BatchModel();
$notifModel   = new NotificationModel();
$error        = '';
$editData     = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name    = trim($_POST['name']     ?? '');
        $email   = trim($_POST['email']    ?? '');
        $pass    = trim($_POST['password'] ?? '');
        $roll    = trim($_POST['roll']     ?? '');
        $regNo   = trim($_POST['reg_no']   ?? '');
        $batchId = intval($_POST['batch_id'] ?? 0);
        $session = trim($_POST['session']  ?? '');
        $phone   = trim($_POST['phone']    ?? '');

        if (empty($name) || empty($email) || empty($pass) || empty($roll) || empty($regNo)) {
            $error = 'Please fill in all required fields.';
        } elseif ($userModel->emailExists($email)) {
            $error = 'This email is already registered.';
        } else {
            $userId = $userModel->create($name, $email, $pass, 'student', 'active');
            $studentModel->create($userId, $roll, $regNo, $batchId ?: null, $session, $phone);
            Helper::setFlash('success', 'Student added successfully.');
            Helper::redirect('admin/students.php');
        }
    }

    if ($action === 'edit') {
        $userId    = intval($_POST['user_id'] ?? 0);
        $studentId = intval($_POST['student_id'] ?? 0);
        $name      = trim($_POST['name']    ?? '');
        $roll      = trim($_POST['roll']    ?? '');
        $regNo     = trim($_POST['reg_no']  ?? '');
        $batchId   = intval($_POST['batch_id'] ?? 0);
        $session   = trim($_POST['session'] ?? '');
        $phone     = trim($_POST['phone']   ?? '');

        if (empty($name) || empty($roll) || empty($regNo)) {
            $error = 'Please fill in all required fields.';
        } else {
            $userModel->updateBasicInfo($userId, $name);
            $studentModel->update($studentId, $roll, $regNo, $batchId ?: null, $session, $phone);
            Helper::setFlash('success', 'Student updated successfully.');
            Helper::redirect('admin/students.php');
        }
    }

    if ($action === 'toggle_status') {
        $userId = intval($_POST['user_id'] ?? 0);
        $newStatus = $_POST['current_status'] === 'active' ? 'inactive' : 'active';
        $userModel->updateStatus($userId, $newStatus);
        Helper::setFlash('success', 'Account status updated to ' . $newStatus . '.');
        Helper::redirect('admin/students.php');
    }

    if ($action === 'delete') {
        $userId = intval($_POST['user_id'] ?? 0);
        $userModel->delete($userId); // cascades to students row via FK
        Helper::setFlash('success', 'Student account deleted.');
        Helper::redirect('admin/students.php');
    }

    if ($action === 'set_cr') {
        $studentId = intval($_POST['student_id'] ?? 0);
        $batchId   = intval($_POST['batch_id_for_cr'] ?? 0);
        // Unset existing CR for this batch first
        $currentCr = $studentModel->getCrOfBatch($batchId);
        if ($currentCr) $studentModel->setCr((int)$currentCr['student_id'], false);
        $studentModel->setCr($studentId, true);
        Helper::setFlash('success', 'Class Representative (CR) updated for this batch.');
        Helper::redirect('admin/students.php');
    }
}

if (isset($_GET['edit'])) {
    $eid = intval($_GET['edit']);
    $editData = $studentModel->findById($eid);
}

$batches = $batchModel->all();
$students = $studentModel->all();

$showModal = (isset($_GET['action']) && $_GET['action']==='add') || $editData;

$pageTitle   = 'Manage Students';
$currentPage = 'Students';
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
    <div><h2>🎓 Manage Students</h2><div class="page-subtitle">Add, edit, and manage student accounts — use the header search to find a student</div></div>
    <a href="?action=add" class="btn btn-primary">+ Add Student</a>
</div>

<div class="card">
<div class="card-body" style="padding:0">
<div class="table-wrapper">
<table id="dataTable">
    <thead><tr><th>#</th><th>Name</th><th>Roll</th><th>Reg. No</th><th>Batch</th><th>Phone</th><th>CR</th><th>Actions</th></tr></thead>
    <tbody>
    <?php if (empty($students)): ?>
        <tr><td colspan="8" class="text-center" style="padding:40px;color:var(--text-muted)">No students found.</td></tr>
    <?php else: foreach ($students as $i => $s): ?>
    <tr>
        <td><?= $i+1 ?></td>
        <td>
            <div style="font-weight:600"><?= htmlspecialchars($s['name']) ?></div>
            <div style="font-size:0.75rem;color:var(--text-muted)"><?= htmlspecialchars($s['email']) ?></div>
        </td>
        <td><?= htmlspecialchars($s['roll']) ?></td>
        <td><?= htmlspecialchars($s['registration_no']) ?></td>
        <td><?= htmlspecialchars($s['batch_name'] ?? '—') ?></td>
        <td><?= htmlspecialchars($s['phone'] ?: '—') ?></td>
        <td><?= $s['is_cr'] ? '<span class="badge badge-primary">CR</span>' : '—' ?></td>
        <td>
            <div style="display:flex;gap:6px;flex-wrap:wrap">
                <a href="?edit=<?= $s['student_id'] ?>" class="btn btn-sm btn-accent">✏️ Edit</a>
                
                <form method="POST" style="display:inline" onsubmit="return confirmAction('Delete this student account? This cannot be undone.')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="user_id" value="<?= $s['user_id'] ?>">
                    <button class="btn btn-sm btn-danger">🗑 Delete</button>
                </form>

                <?php if (!$s['is_cr'] && $s['batch_id']): ?>
                <form method="POST" style="display:inline" onsubmit="return confirmAction('Make this student the CR for their batch?')">
                    <input type="hidden" name="action" value="set_cr">
                    <input type="hidden" name="student_id" value="<?= $s['student_id'] ?>">
                    <input type="hidden" name="batch_id_for_cr" value="<?= $s['batch_id'] ?>">
                    <button class="btn btn-sm btn-outline">👑 Make CR</button>
                </form>
                <?php endif; ?>
            </div>
        </td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>
</div>
</div>
</div>

<!-- Add/Edit Modal -->
<div class="modal-overlay <?= $showModal?'open':'' ?>" id="studentModal">
<div class="modal-box">
    <div class="modal-header">
        <span class="modal-title"><?= $editData?'✏️ Edit Student':'+ Add New Student' ?></span>
        <button class="modal-close" onclick="closeModal('studentModal')">✕</button>
    </div>
    <div class="modal-body">
    <form method="POST">
        <input type="hidden" name="action" value="<?= $editData?'edit':'add' ?>">
        <?php if ($editData): ?>
            <input type="hidden" name="user_id" value="<?= $editData['user_id'] ?>">
            <input type="hidden" name="student_id" value="<?= $editData['student_id'] ?>">
        <?php endif; ?>
        <div class="form-row">
            <div class="form-group"><label class="form-label">Full Name *</label><input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($editData['name'] ?? '') ?>"></div>
            <?php if (!$editData): ?>
            <div class="form-group"><label class="form-label">Email *</label><input type="email" name="email" class="form-control" required></div>
            <?php endif; ?>
            <div class="form-group"><label class="form-label">Roll *</label><input type="text" name="roll" class="form-control" required value="<?= htmlspecialchars($editData['roll'] ?? '') ?>"></div>
            <div class="form-group"><label class="form-label">Registration No *</label><input type="text" name="reg_no" class="form-control" required value="<?= htmlspecialchars($editData['registration_no'] ?? '') ?>"></div>
            <div class="form-group">
                <label class="form-label">Batch</label>
                <select name="batch_id" class="form-control">
                    <option value="">— None —</option>
                    <?php foreach ($batches as $b): ?>
                    <option value="<?= $b['batch_id'] ?>" <?= ($editData['batch_id']??0)==$b['batch_id']?'selected':'' ?>><?= htmlspecialchars($b['batch_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label class="form-label">Session</label><input type="text" name="session" class="form-control" value="<?= htmlspecialchars($editData['session'] ?? '') ?>"></div>
            <div class="form-group"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($editData['phone'] ?? '') ?>"></div>
            <?php if (!$editData): ?>
            <div class="form-group"><label class="form-label">Password *</label><input type="password" name="password" class="form-control" required minlength="6"></div>
            <?php endif; ?>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:10px">
            <button type="button" class="btn btn-outline" onclick="closeModal('studentModal')">Cancel</button>
            <button type="submit" class="btn btn-primary"><?= $editData?'Update Student':'Add Student' ?></button>
        </div>
    </form>
    </div>
</div>
</div>

</div>
<?php include '../includes/footer.php'; ?>
</div>
</div>
<script><?php if ($showModal): ?>document.addEventListener('DOMContentLoaded',()=>openModal('studentModal'));<?php endif; ?></script>
