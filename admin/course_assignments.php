<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
Auth::requireRole('admin');

$courseModel  = new CourseModel();
$batchModel   = new BatchModel();
$teacherModel = new Teacher();
$error        = '';
$editData     = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'assign') {
        $courseId  = intval($_POST['course_id']  ?? 0);
        $batchId   = intval($_POST['batch_id']   ?? 0);
        $teacherId = intval($_POST['teacher_id'] ?? 0);

        if (!$courseId || !$batchId || !$teacherId) {
            $error = 'Please select course, batch, and teacher.';
        } else {
            $result = $courseModel->assign($courseId, $batchId, $teacherId);
            Helper::setFlash($result['success'] ? 'success' : 'danger', $result['message']);
            Helper::redirect('admin/course_assignments.php');
        }
    }

    if ($action === 'edit') {
        $id        = intval($_POST['assignment_id'] ?? 0);
        $courseId  = intval($_POST['course_id']     ?? 0);
        $batchId   = intval($_POST['batch_id']       ?? 0);
        $teacherId = intval($_POST['teacher_id']     ?? 0);

        if (!$id || !$courseId || !$batchId || !$teacherId) {
            $error = 'Please select course, batch, and teacher.';
        } else {
            $result = $courseModel->updateAssignment($id, $courseId, $batchId, $teacherId);
            Helper::setFlash($result['success'] ? 'success' : 'danger', $result['message']);
            Helper::redirect('admin/course_assignments.php');
        }
    }

    if ($action === 'delete') {
        $id = intval($_POST['assignment_id'] ?? 0);
        $courseModel->deleteAssignment($id);
        Helper::setFlash('success', 'Assignment removed. The course can now be re-assigned for this batch.');
        Helper::redirect('admin/course_assignments.php');
    }
}

if (isset($_GET['edit'])) {
    $editData = $courseModel->findAssignmentById(intval($_GET['edit']));
}

$assignments    = $courseModel->getAllAssignments();
$courses        = $courseModel->allActive();
$batches        = $batchModel->all();
$teachers       = $teacherModel->all();
$showEditModal  = (bool) $editData;

$pageTitle   = 'Course Assignment';
$currentPage = 'Course Assignment';
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
    <div><h2>🔗 Course Assignment</h2><div class="page-subtitle">Assign Course + Batch + Teacher. Admin can edit or remove assignments if needed.</div></div>
    <button class="btn btn-primary" onclick="openModal('assignModal')">+ New Assignment</button>
</div>

<div class="alert alert-info">ℹ️ Teachers cannot request courses — only the admin assigns Course + Batch + Teacher combinations. Use Edit/Delete below for corrections.</div>

<div class="card">
<div class="card-body" style="padding:0">
<div class="table-wrapper">
<table id="dataTable">
    <thead><tr><th>#</th><th>Course</th><th>Batch</th><th>Teacher</th><th>Assigned On</th><th>Actions</th></tr></thead>
    <tbody>
    <?php if (empty($assignments)): ?>
        <tr><td colspan="6" class="text-center" style="padding:40px;color:var(--text-muted)">No course assignments yet.</td></tr>
    <?php else: foreach ($assignments as $i => $a): ?>
    <tr>
        <td><?= $i+1 ?></td>
        <td>
            <div style="font-weight:600"><?= htmlspecialchars($a['course_title']) ?></div>
            <span class="badge badge-info"><?= htmlspecialchars($a['course_code']) ?></span>
        </td>
        <td><?= htmlspecialchars($a['batch_name']) ?> <span style="color:var(--text-muted);font-size:0.78rem">(<?= htmlspecialchars($a['session']) ?>)</span></td>
        <td><?= htmlspecialchars($a['teacher_name']) ?></td>
        <td style="font-size:0.78rem;color:var(--text-muted)"><?= Helper::formatDate($a['created_at']) ?></td>
        <td>
            <div style="display:flex;gap:6px">
                <a href="?edit=<?= $a['id'] ?>" class="btn btn-sm btn-accent">✏️</a>
                <form method="POST" onsubmit="return confirmAction('Remove this assignment? The course will become available to re-assign for this batch.')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="assignment_id" value="<?= $a['id'] ?>">
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

<!-- Assignment Modal (Add) -->
<div class="modal-overlay" id="assignModal">
<div class="modal-box">
    <div class="modal-header">
        <span class="modal-title">+ New Course Assignment</span>
        <button class="modal-close" onclick="closeModal('assignModal')">✕</button>
    </div>
    <div class="modal-body">
    <form method="POST">
        <input type="hidden" name="action" value="assign">
        <div class="form-group">
            <label class="form-label">Course *</label>
            <select name="course_id" class="form-control" required>
                <option value="">Select Course</option>
                <?php foreach ($courses as $c): ?>
                <option value="<?= $c['course_id'] ?>"><?= htmlspecialchars($c['course_code'].' — '.$c['course_title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Batch *</label>
            <select name="batch_id" class="form-control" required>
                <option value="">Select Batch</option>
                <?php foreach ($batches as $b): ?>
                <option value="<?= $b['batch_id'] ?>"><?= htmlspecialchars($b['batch_name'].' ('.$b['session'].')') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Teacher *</label>
            <select name="teacher_id" class="form-control" required>
                <option value="">Select Teacher</option>
                <?php foreach ($teachers as $t): ?>
                <option value="<?= $t['teacher_id'] ?>"><?= htmlspecialchars($t['name'].' ('.($t['designation']?:'Faculty').')') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-hint mb-2">ℹ️ Each course can only be assigned once per batch.</div>
        <div style="display:flex;justify-content:flex-end;gap:10px">
            <button type="button" class="btn btn-outline" onclick="closeModal('assignModal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Assign</button>
        </div>
    </form>
    </div>
</div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay <?= $showEditModal?'open':'' ?>" id="editAssignModal">
<div class="modal-box">
    <div class="modal-header">
        <span class="modal-title">✏️ Edit Assignment</span>
        <a href="course_assignments.php" class="modal-close">✕</a>
    </div>
    <div class="modal-body">
    <?php if ($editData): ?>
    <form method="POST">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="assignment_id" value="<?= $editData['id'] ?>">
        <div class="form-group">
            <label class="form-label">Course *</label>
            <select name="course_id" class="form-control" required>
                <?php foreach ($courses as $c): ?>
                <option value="<?= $c['course_id'] ?>" <?= $editData['course_id']==$c['course_id']?'selected':'' ?>><?= htmlspecialchars($c['course_code'].' — '.$c['course_title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Batch *</label>
            <select name="batch_id" class="form-control" required>
                <?php foreach ($batches as $b): ?>
                <option value="<?= $b['batch_id'] ?>" <?= $editData['batch_id']==$b['batch_id']?'selected':'' ?>><?= htmlspecialchars($b['batch_name'].' ('.$b['session'].')') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Teacher *</label>
            <select name="teacher_id" class="form-control" required>
                <?php foreach ($teachers as $t): ?>
                <option value="<?= $t['teacher_id'] ?>" <?= $editData['teacher_id']==$t['teacher_id']?'selected':'' ?>><?= htmlspecialchars($t['name'].' ('.($t['designation']?:'Faculty').')') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:10px">
            <a href="course_assignments.php" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </form>
    <?php endif; ?>
    </div>
</div>
</div>

</div>
<?php include '../includes/footer.php'; ?>
</div>
</div>
<script><?php if ($showEditModal): ?>document.addEventListener('DOMContentLoaded',()=>openModal('editAssignModal'));<?php endif; ?></script>
