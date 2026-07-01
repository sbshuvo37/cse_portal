<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
Auth::requireRole('student');

$studentModel = new Student();
$resultModel  = new ResultModel();

$student = $studentModel->findByUserId(Auth::userId());
$sid     = $student['student_id'];
$batchId = $student['batch_id'];

$myMarks = $resultModel->byStudent($sid);
$allBatchFiles = $batchId ? $resultModel->getFilesByBatch($batchId) : [];

// Admin's official batch-wide final result (no specific course)
$finalResultFiles = array_filter($allBatchFiles, fn($f) => empty($f['course_id']));
// Teacher-uploaded course-specific result files (e.g. mid-term result sheets) for this batch
$teacherCourseFiles = array_filter($allBatchFiles, fn($f) => !empty($f['course_id']));

// Group teacher marks by semester
$bySem = [];
foreach ($myMarks as $r) { $bySem[$r['semester'] ?? 'General'][] = $r; }

$pageTitle   = 'My Results';
$currentPage = 'Results';
include '../includes/header.php';
?>
<div class="portal-layout">
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
<?php include '../includes/navbar.php'; ?>
<div class="page-content">

<div class="page-header">
    <div><h2>📈 My Results</h2><div class="page-subtitle">Final results, teacher-uploaded files, and entered marks</div></div>
</div>

<!-- Admin's official final result files -->
<h3 class="mb-2">📄 Official Final Result</h3>
<?php if (empty($finalResultFiles)): ?>
    <div class="empty-state mb-3"><div class="empty-icon">📈</div><p>No official final result published yet for your batch.</p></div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:10px;margin-bottom:26px">
<?php foreach ($finalResultFiles as $f): ?>
<div class="routine-file-card">
    <div class="routine-file-icon"><?= Helper::fileIcon($f['file_path']) ?></div>
    <div style="flex:1"><div style="font-weight:700"><?= htmlspecialchars($f['title'] ?: 'Final Result') ?></div><div style="font-size:0.78rem;color:var(--text-muted)">Published <?= Helper::timeAgo($f['created_at']) ?></div></div>
    <a href="<?= UPLOAD_URL . htmlspecialchars($f['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline">📥 View / Download</a>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Teacher-uploaded course-specific result files -->
<h3 class="mb-2">📤 Course Result Files (from Teachers)</h3>
<?php if (empty($teacherCourseFiles)): ?>
    <div class="empty-state mb-3"><div class="empty-icon">📄</div><p>No course-specific result files uploaded yet.</p></div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:10px;margin-bottom:26px">
<?php foreach ($teacherCourseFiles as $f): ?>
<div class="routine-file-card">
    <div class="routine-file-icon"><?= Helper::fileIcon($f['file_path']) ?></div>
    <div style="flex:1">
        <div style="font-weight:700"><?= htmlspecialchars($f['title'] ?: 'Result File') ?></div>
        <div style="font-size:0.78rem;color:var(--text-muted)">
            <?php if (!empty($f['course_code'])): ?><span class="badge badge-info" style="margin-right:6px"><?= htmlspecialchars($f['course_code']) ?></span><?= htmlspecialchars($f['course_title']) ?> · <?php endif; ?>
            By <?= htmlspecialchars($f['uploader']) ?> · <?= Helper::timeAgo($f['created_at']) ?>
        </div>
    </div>
    <a href="<?= UPLOAD_URL . htmlspecialchars($f['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline">📥 View / Download</a>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Teacher-entered marks -->
<h3 class="mb-2">📋 Course-wise Marks (Teacher Entered)</h3>
<?php if (empty($bySem)): ?>
    <div class="empty-state"><div class="empty-icon">📊</div><p>No marks have been entered yet.</p></div>
<?php else: foreach ($bySem as $sem => $marks): ?>
<div class="card mb-3">
    <div class="card-header"><span class="card-title">📖 <?= htmlspecialchars($sem) ?></span></div>
    <div class="card-body" style="padding:0">
    <div class="table-wrapper"><table>
        <thead><tr><th>Course</th><th>Attendance</th><th>Mid-1</th><th>Mid-2</th><th>Mid-3</th><th>Total</th></tr></thead>
        <tbody>
        <?php foreach ($marks as $r): ?>
        <tr>
            <td><div style="font-weight:600"><?= htmlspecialchars($r['course_title']) ?></div><span class="badge badge-info"><?= htmlspecialchars($r['course_code']) ?></span></td>
            <td><?= htmlspecialchars($r['attendance']) ?></td>
            <td><?= htmlspecialchars($r['mid1']) ?></td>
            <td><?= htmlspecialchars($r['mid2']) ?></td>
            <td><?= htmlspecialchars($r['mid3']) ?></td>
            <td><span class="total-badge <?= $r['total']>=60?'total-good':($r['total']>=40?'total-ok':'total-low') ?>"><?= number_format($r['total'],1) ?></span></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    </div>
</div>
<?php endforeach; endif; ?>

</div>
<?php include '../includes/footer.php'; ?>
</div>
</div>
