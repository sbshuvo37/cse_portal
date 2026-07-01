<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
Auth::requireRole('student');

$studentModel = new Student();
$examModel    = new ExamModel();

$student = $studentModel->findByUserId(Auth::userId());
$batchId = $student['batch_id'];

$exams = $batchId ? $examModel->byBatch($batchId) : [];
$today = date('Y-m-d');
$upcoming = array_filter($exams, fn($e) => $e['exam_date'] >= $today);
$past     = array_filter($exams, fn($e) => $e['exam_date']  < $today);

$pageTitle   = 'Exam Schedule';
$currentPage = 'Exam Schedule';
include '../includes/header.php';
?>
<div class="portal-layout">
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
<?php include '../includes/navbar.php'; ?>
<div class="page-content">

<div class="page-header">
    <div><h2>📝 Exam Schedule</h2><div class="page-subtitle"><?= htmlspecialchars($student['batch_name'] ?? 'Your batch') ?> examination dates</div></div>
</div>

<?php if (!$batchId): ?>
    <div class="alert alert-warning">You are not assigned to a batch yet. Please contact the administrator.</div>
<?php elseif (empty($exams)): ?>
    <div class="empty-state"><div class="empty-icon">📅</div><p>No exam schedules published yet.</p></div>
<?php else: ?>

<?php if (!empty($upcoming)): ?>
<h3 class="mb-2">📅 Upcoming Exams</h3>
<div style="display:flex;flex-direction:column;gap:12px;margin-bottom:26px">
<?php foreach ($upcoming as $e): ?>
<?php $isToday = $e['exam_date']===$today; $daysLeft = (int)ceil((strtotime($e['exam_date'])-strtotime($today))/86400); ?>
<div class="exam-card" style="<?= $isToday?'border:2px solid var(--accent);background:#fff7ed':'' ?>">
    <div class="exam-date-box" style="<?= $isToday?'background:var(--accent)':'' ?>"><div class="exam-date-day"><?= date('d', strtotime($e['exam_date'])) ?></div><div class="exam-date-month"><?= date('M', strtotime($e['exam_date'])) ?></div></div>
    <div style="flex:1">
        <div class="exam-info-title"><?= htmlspecialchars($e['course_title']) ?></div>
        <div class="exam-info-meta"><span class="badge badge-info"><?= htmlspecialchars($e['course_code']) ?></span> 🕐 <?= Helper::formatTime($e['exam_time']) ?> &nbsp;|&nbsp; 📅 <?= Helper::formatDate($e['exam_date'],'l, F j, Y') ?> &nbsp;|&nbsp; ✏️ <?= htmlspecialchars($e['credit']) ?> credits</div>
    </div>
    <div style="flex-shrink:0">
        <?php if ($isToday): ?><span class="badge badge-warning" style="font-size:0.8rem;padding:6px 12px">Today!</span>
        <?php elseif ($daysLeft===1): ?><span class="badge badge-danger" style="font-size:0.8rem;padding:6px 12px">Tomorrow</span>
        <?php else: ?><span class="badge badge-info" style="font-size:0.8rem;padding:6px 12px"><?= $daysLeft ?> days left</span><?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($past)): ?>
<h3 class="mb-2">🗃️ Past Exams</h3>
<div class="card"><div class="card-body" style="padding:0"><div class="table-wrapper"><table>
<thead><tr><th>Date</th><th>Course</th><th>Time</th><th>Status</th></tr></thead><tbody>
<?php foreach (array_reverse($past) as $e): ?>
<tr style="opacity:0.75">
    <td><strong><?= Helper::formatDate($e['exam_date']) ?></strong><br><span style="font-size:0.75rem;color:var(--text-muted)"><?= date('l', strtotime($e['exam_date'])) ?></span></td>
    <td><div style="font-weight:500"><?= htmlspecialchars($e['course_title']) ?></div><span class="badge badge-info"><?= htmlspecialchars($e['course_code']) ?></span></td>
    <td class="time-cell"><?= Helper::formatTime($e['exam_time']) ?></td>
    <td><span class="badge badge-success">Completed</span></td>
</tr>
<?php endforeach; ?>
</tbody></table></div></div></div>
<?php endif; ?>

<?php endif; ?>

</div>
<?php include '../includes/footer.php'; ?>
</div>
</div>
