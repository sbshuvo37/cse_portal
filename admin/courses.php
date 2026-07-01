<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
Auth::requireRole('admin');

$courseModel = new CourseModel();
$error       = '';
$editData    = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $code     = trim($_POST['course_code']  ?? '');
        $title    = trim($_POST['course_title'] ?? '');
        $credit   = floatval($_POST['credit']   ?? 0);
        $semester = trim($_POST['semester']     ?? '');
        $cid      = intval($_POST['course_id']  ?? 0);

        if (empty($code) || empty($title) || !$credit || empty($semester)) {
            $error = 'All fields are required.';
        } else {
            if ($action === 'add') {
                try {
                    $courseModel->create($code, $title, $credit, $semester);
                    Helper::setFlash('success', 'Course added successfully.');
                } catch (PDOException $e) {
                    $error = 'Course code already exists.';
                }
            } else {
                $courseModel->update($cid, $code, $title, $credit, $semester);
                Helper::setFlash('success', 'Course updated successfully.');
            }
            if (!$error) Helper::redirect('admin/courses.php');
        }
    }

    if ($action === 'toggle_status') {
        $cid = intval($_POST['course_id'] ?? 0);
        $newStatus = $_POST['current_status'] === 'active' ? 'inactive' : 'active';
        $courseModel->setStatus($cid, $newStatus);
        Helper::setFlash('success', 'Course status updated to ' . $newStatus . '. (Academic data preserved — no permanent deletion.)');
        Helper::redirect('admin/courses.php');
    }
}

if (isset($_GET['edit'])) {
    $editData = $courseModel->findById(intval($_GET['edit']));
}

$courses   = $courseModel->all();
$showModal = (isset($_GET['action']) && $_GET['action']==='add') || $editData;
$semesters = ['1st Semester','2nd Semester','3rd Semester','4th Semester','5th Semester','6th Semester','7th Semester','8th Semester'];

$pageTitle   = 'Manage Courses';
$currentPage = 'Courses';
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
    <div><h2>📚 Manage Courses</h2><div class="page-subtitle">CSE department course catalogue (safe delete — never permanently removed)</div></div>
    <a href="?action=add" class="btn btn-primary">+ Add Course</a>
</div>

<div class="card">
<div class="card-body" style="padding:0">
<div class="table-wrapper">
<table id="dataTable">
    <thead><tr><th>#</th><th>Code</th><th>Title</th><th>Credit</th><th>Semester</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
    <?php if (empty($courses)): ?>
        <tr><td colspan="7" class="text-center" style="padding:40px;color:var(--text-muted)">No courses found.</td></tr>
    <?php else: foreach ($courses as $i => $c): ?>
    <tr>
        <td><?= $i+1 ?></td>
        <td><span class="badge badge-info"><?= htmlspecialchars($c['course_code']) ?></span></td>
        <td><?= htmlspecialchars($c['course_title']) ?></td>
        <td><?= htmlspecialchars($c['credit']) ?></td>
        <td><?= htmlspecialchars($c['semester']) ?></td>
        <td><?= $c['status']==='active' ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-gray">Inactive</span>' ?></td>
        <td>
            <div style="display:flex;gap:6px">
                <a href="?edit=<?= $c['course_id'] ?>" class="btn btn-sm btn-accent">✏️ Edit</a>
                <?php if ($c['status']==='active'): ?>
                <form method="POST" style="display:inline" onsubmit="return confirmAction('Delete this course? It will be deactivated, not permanently removed — existing results/exam records stay intact.')">
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="course_id" value="<?= $c['course_id'] ?>">
                    <input type="hidden" name="current_status" value="<?= $c['status'] ?>">
                    <button class="btn btn-sm btn-danger">🗑 Delete</button>
                </form>
                <?php else: ?>
                <form method="POST" style="display:inline" onsubmit="return confirmAction('Restore this course?')">
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="course_id" value="<?= $c['course_id'] ?>">
                    <input type="hidden" name="current_status" value="<?= $c['status'] ?>">
                    <button class="btn btn-sm btn-success">↺ Restore</button>
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

<div class="modal-overlay <?= $showModal?'open':'' ?>" id="courseModal">
<div class="modal-box">
    <div class="modal-header">
        <span class="modal-title"><?= $editData?'✏️ Edit Course':'+ Add Course' ?></span>
        <button class="modal-close" onclick="closeModal('courseModal')">✕</button>
    </div>
    <div class="modal-body">
    <form method="POST">
        <input type="hidden" name="action" value="<?= $editData?'edit':'add' ?>">
        <?php if ($editData): ?><input type="hidden" name="course_id" value="<?= $editData['course_id'] ?>"><?php endif; ?>
        <div class="form-group">
            <label class="form-label">Course Code *</label>
            <input type="text" name="course_code" class="form-control" required placeholder="e.g. CSE-301" value="<?= htmlspecialchars($editData['course_code']??'') ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Course Title *</label>
            <input type="text" name="course_title" class="form-control" required value="<?= htmlspecialchars($editData['course_title']??'') ?>">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Credit *</label>
                <input type="number" name="credit" class="form-control" required step="0.5" min="1" max="4" value="<?= htmlspecialchars($editData['credit']??'3') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Semester *</label>
                <select name="semester" class="form-control">
                    <?php foreach ($semesters as $sem): ?>
                    <option value="<?= $sem ?>" <?= ($editData['semester']??'')===$sem?'selected':'' ?>><?= $sem ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:10px">
            <button type="button" class="btn btn-outline" onclick="closeModal('courseModal')">Cancel</button>
            <button type="submit" class="btn btn-primary"><?= $editData?'Update Course':'Add Course' ?></button>
        </div>
    </form>
    </div>
</div>
</div>

</div>
<?php include '../includes/footer.php'; ?>
</div>
</div>
<script><?php if ($showModal): ?>document.addEventListener('DOMContentLoaded',()=>openModal('courseModal'));<?php endif; ?></script>
