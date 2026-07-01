<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
Auth::requireRole('student');

$studentModel = new Student();
$resultModel  = new ResultModel();
$noticeModel  = new NoticeModel();
$examModel    = new ExamModel();
$courseModel  = new CourseModel();

$student = $studentModel->findByUserId(Auth::userId());
$sid     = $student['student_id'];
$batchId = $student['batch_id'];

$myResults = $resultModel->byStudent($sid);
$myCourses = $batchId ? $courseModel->getCoursesForBatch($batchId) : [];
$notices   = $batchId ? $noticeModel->visibleTo($batchId) : $noticeModel->visibleTo();
$exams     = $batchId ? $examModel->byBatch($batchId) : [];

$today = date('Y-m-d');
$upcomingExams = array_filter($exams, fn($e) => $e['exam_date'] >= $today);

$avgTotal = count($myResults) ? round(array_sum(array_column($myResults,'total')) / count($myResults), 1) : 0;

$pageTitle   = 'My Dashboard';
$currentPage = 'Dashboard';
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
    <div>
        <h2>Welcome, <?= htmlspecialchars($student['name']) ?>!</h2>
        <div class="page-subtitle">
            Roll: <strong><?= htmlspecialchars($student['roll']) ?></strong> &nbsp;|&nbsp;
            Batch: <strong><?= htmlspecialchars($student['batch_name'] ?? '—') ?></strong> &nbsp;|&nbsp;
            <?php if ($student['is_cr']): ?><span class="badge badge-primary">👑 Class Representative</span><?php endif; ?>
        </div>
    </div>
    <div style="font-size:0.8rem;color:var(--text-muted)">📅 <?= date('l, F j, Y') ?></div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon amber">📚</div>
        <div class="stat-info"><div class="stat-value"><?= count($myCourses) ?></div><div class="stat-label">My Courses</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">📈</div>
        <div class="stat-info"><div class="stat-value"><?= count($myResults) ?></div><div class="stat-label">Results Available</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue">📊</div>
        <div class="stat-info"><div class="stat-value"><?= $avgTotal ?></div><div class="stat-label">Average Total</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red">📝</div>
        <div class="stat-info"><div class="stat-value"><?= count($upcomingExams) ?></div><div class="stat-label">Upcoming Exams</div></div>
    </div>
</div>

<?php if ($student['is_cr']): ?>
<div class="alert alert-info">👑 You are the <strong>Class Representative (CR)</strong> for your batch. You can manage your batch's structured class routine entries.</div>
<?php endif; ?>

<div class="grid-2">
    <div class="card">
        <div class="card-header"><span class="card-title">📈 My Recent Results</span><a href="<?= BASE_URL ?>student/results.php" class="btn btn-sm btn-outline">View All</a></div>
        <div class="card-body" style="padding:0">
            <?php if (empty($myResults)): ?>
                <div class="empty-state"><div class="empty-icon">📊</div><p>No results published yet.</p></div>
            <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>Course</th><th>Total</th></tr></thead>
                    <tbody>
                    <?php foreach (array_slice($myResults,0,5) as $r): ?>
                    <tr>
                        <td><div style="font-size:0.85rem;font-weight:500"><?= htmlspecialchars($r['course_title']) ?></div><span class="badge badge-info"><?= htmlspecialchars($r['course_code']) ?></span></td>
                        <td><span class="total-badge <?= $r['total']>=60?'total-good':($r['total']>=40?'total-ok':'total-low') ?>"><?= number_format($r['total'],1) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><span class="card-title">📢 Latest Notices</span><a href="<?= BASE_URL ?>student/notices.php" class="btn btn-sm btn-outline">View All</a></div>
        <div class="card-body" style="padding:0">
            <?php if (empty($notices)): ?>
                <div class="empty-state"><div class="empty-icon">📭</div><p>No notices yet.</p></div>
            <?php else: ?>
            <ul class="activity-list" style="padding:0 20px">
            <?php foreach (array_slice($notices,0,5) as $n): ?>
                <li class="activity-item">
                    <span class="activity-dot" style="background:#38bdf8"></span>
                    <div><div style="font-size:0.86rem;font-weight:600;color:var(--primary-dark)"><?= htmlspecialchars($n['title']) ?></div><div class="activity-time">By <?= htmlspecialchars($n['poster']) ?> · <?= Helper::timeAgo($n['created_at']) ?></div></div>
                </li>
            <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!empty($upcomingExams)): ?>
<div class="card mt-3">
    <div class="card-header"><span class="card-title">📝 Upcoming Exams</span><a href="<?= BASE_URL ?>student/exam_schedule.php" class="btn btn-sm btn-outline">Full Schedule</a></div>
    <div class="card-body" style="display:flex;flex-direction:column;gap:12px">
    <?php foreach (array_slice($upcomingExams,0,3) as $e): ?>
        <div class="exam-card" style="padding:12px 16px">
            <div class="exam-date-box" style="width:48px;height:52px"><div class="exam-date-day" style="font-size:1.2rem"><?= date('d', strtotime($e['exam_date'])) ?></div><div class="exam-date-month"><?= date('M', strtotime($e['exam_date'])) ?></div></div>
            <div><div class="exam-info-title"><?= htmlspecialchars($e['course_title']) ?></div><div class="exam-info-meta"><span class="badge badge-info"><?= htmlspecialchars($e['course_code']) ?></span> 🕐 <?= Helper::formatTime($e['exam_time']) ?></div></div>
        </div>
    <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

</div>
<?php include '../includes/footer.php'; ?>
</div>
</div>
