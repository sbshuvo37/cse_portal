<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
Auth::requireRole('teacher');

$teacherModel = new Teacher();
$studentModel = new Student();

$teacher = $teacherModel->findByUserId(Auth::userId());
$courses = $teacherModel->getAssignedCourses($teacher['teacher_id']);

// Unique batches this teacher teaches
$batches = [];
foreach ($courses as $c) {
    $batches[$c['batch_id']] = ['batch_id'=>$c['batch_id'], 'batch_name'=>$c['batch_name']];
}

$filterBatch = intval($_GET['batch'] ?? (count($batches) ? array_key_first($batches) : 0));
$students = $filterBatch ? $studentModel->byBatch($filterBatch) : [];

$pageTitle   = 'Students';
$currentPage = 'Students';
include '../includes/header.php';
?>
<div class="portal-layout">
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
<?php include '../includes/navbar.php'; ?>
<div class="page-content">

<div class="page-header">
    <div><h2>🎓 Students</h2><div class="page-subtitle">Students from your assigned batches (every student is automatically enrolled — no separate enrollment system)</div></div>
</div>

<?php if (empty($batches)): ?>
    <div class="empty-state"><div class="empty-icon">🎓</div><p>No courses assigned, so no students to show yet.</p></div>
<?php else: ?>

<form method="GET" class="search-bar">
    <select name="batch" class="form-control" style="max-width:260px" onchange="this.form.submit()">
        <?php foreach ($batches as $b): ?>
        <option value="<?= $b['batch_id'] ?>" <?= $filterBatch==$b['batch_id']?'selected':'' ?>><?= htmlspecialchars($b['batch_name']) ?></option>
        <?php endforeach; ?>
    </select>
</form>

<div class="card">
<div class="card-body" style="padding:0">
<div class="table-wrapper">
<table id="dataTable">
    <thead><tr><th>#</th><th>Name</th><th>Roll</th><th>Registration No</th><th>Phone</th><th>CR</th></tr></thead>
    <tbody>
    <?php if (empty($students)): ?>
        <tr><td colspan="6" class="text-center" style="padding:30px;color:var(--text-muted)">No students in this batch.</td></tr>
    <?php else: foreach ($students as $i => $s): ?>
    <tr>
        <td><?= $i+1 ?></td>
        <td><?= htmlspecialchars($s['name']) ?></td>
        <td><?= htmlspecialchars($s['roll']) ?></td>
        <td><?= htmlspecialchars($s['registration_no']) ?></td>
        <td><?= htmlspecialchars($s['phone'] ?: '—') ?></td>
        <td><?= $s['is_cr'] ? '<span class="badge badge-primary">CR</span>' : '—' ?></td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>
</div>
</div>
</div>
<?php endif; ?>

</div>
<?php include '../includes/footer.php'; ?>
</div>
</div>
