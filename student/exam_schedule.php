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

$db = Database::getInstance()->getConnection();
$files = [];
if ($batchId) {
    $stmt = $db->prepare("SELECT * FROM exam_schedule_files WHERE batch_id = ? ORDER BY created_at DESC");
    $stmt->execute([$batchId]);
    $files = $stmt->fetchAll();
}

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
    <div><h2>📝 Exam Schedule</h2><div class="page-subtitle"><?= htmlspecialchars($student['batch_name'] ?? 'Your batch') ?> exam schedules</div></div>
</div>

<!-- SECTION 1: MASTER ROUTINE FILES (If uploaded) -->
<?php if (!empty($files)): ?>
<h3 class="mb-2">📄 Official Exam Schedule Files</h3>
<div style="display:flex; flex-direction:column; gap:10px; margin-bottom:24px;">
<?php foreach ($files as $f): ?>
<div class="routine-file-card" style="border: 1px solid var(--border); border-radius: var(--radius); padding:16px; display:flex; align-items:center; justify-content:space-between; background:white;">
    <div style="display:flex; align-items:center; gap:14px;">
        <div style="font-size:2rem;"><?= Helper::fileIcon($f['file_path']) ?></div>
        <div>
            <div style="font-weight:700; color:var(--primary-dark);"><?= htmlspecialchars($f['title']) ?></div>
            <div style="font-size:0.78rem; color:var(--text-muted);">Uploaded <?= Helper::timeAgo($f['created_at']) ?></div>
        </div>
    </div>
    <a href="<?= UPLOAD_URL . htmlspecialchars($f['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline">View Schedule</a>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- SECTION 2: STRUCTURED SINGLE ENTRIES (Point 3 - Start & End Time display) -->
<?php if (!empty($upcoming)): ?>
<h3 class="mb-2">📅 Single Exam Dates</h3>
<div style="display:flex; flex-direction:column; gap:12px; margin-bottom:26px;">
<?php foreach ($upcoming as $e): ?>
<?php $isToday = $e['exam_date']===$today; $daysLeft = (int)ceil((strtotime($e['exam_date'])-strtotime($today))/86400); ?>
<div class="exam-card" style="<?= $isToday?'border:2px solid var(--accent);background:#fff7ed':'' ?>">
    <div class="exam-date-box" style="<?= $isToday?'background:var(--accent)':'' ?>"><div class="exam-date-day"><?= date('d', strtotime($e['exam_date'])) ?></div><div class="exam-date-month"><?= date('M', strtotime($e['exam_date'])) ?></div></div>
    <div style="flex:1">
        <div class="exam-info-title"><?= htmlspecialchars($e['course_title']) ?></div>
        <div class="exam-info-meta">
            <span class="badge badge-info"><?= htmlspecialchars($e['course_code']) ?></span> 
            🕐 <?= Helper::formatTime($e['exam_time']) ?> - <?= !empty($e['end_time']) ? Helper::formatTime($e['end_time']) : 'End' ?>
            &nbsp;|&nbsp; 📅 <?= Helper::formatDate($e['exam_date'],'l, F j, Y') ?>
        </div>
    </div>
    <div>
        <?php if ($isToday): ?><span class="badge badge-warning">Today!</span>
        <?php elseif ($daysLeft===1): ?><span class="badge badge-danger">Tomorrow</span>
        <?php else: ?><span class="badge badge-info"><?= $daysLeft ?> days left</span><?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

</div>
<?php include '../includes/footer.php'; ?>
</div>
</div>