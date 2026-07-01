<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
Auth::requireRole('teacher');

$teacherModel = new Teacher();
$teacher = $teacherModel->findByUserId(Auth::userId());
$courses = $teacherModel->getAssignedCourses($teacher['teacher_id']);

$pageTitle   = 'Assigned Courses';
$currentPage = 'Assigned Courses';
include '../includes/header.php';
?>
<div class="portal-layout">
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
<?php include '../includes/navbar.php'; ?>
<div class="page-content">

<div class="page-header">
    <div><h2>📚 Assigned Courses</h2><div class="page-subtitle">Courses assigned to you by the admin (cannot be requested or changed)</div></div>
</div>

<?php if (empty($courses)): ?>
    <div class="empty-state"><div class="empty-icon">📚</div><p>No courses have been assigned to you yet.</p></div>
<?php else: ?>
<div class="card">
<div class="card-body" style="padding:0">
<div class="table-wrapper">
<table>
    <thead><tr><th>Course Code</th><th>Course Title</th><th>Credit</th><th>Semester</th><th>Batch</th></tr></thead>
    <tbody>
    <?php foreach ($courses as $c): ?>
    <tr>
        <td><span class="badge badge-info"><?= htmlspecialchars($c['course_code']) ?></span></td>
        <td><strong><?= htmlspecialchars($c['course_title']) ?></strong></td>
        <td><?= htmlspecialchars($c['credit']) ?></td>
        <td><?= htmlspecialchars($c['semester']) ?></td>
        <td><span class="badge badge-primary"><?= htmlspecialchars($c['batch_name']) ?></span> <span style="font-size:0.75rem;color:var(--text-muted)"><?= htmlspecialchars($c['session']) ?></span></td>
    </tr>
    <?php endforeach; ?>
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
