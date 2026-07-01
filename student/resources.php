<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
Auth::requireRole('student');

$studentModel  = new Student();
$resourceModel = new ResourceModel();

$student = $studentModel->findByUserId(Auth::userId());
$resources = $student['batch_id'] ? $resourceModel->forBatch($student['batch_id']) : [];

// Group by course
$byCourse = [];
foreach ($resources as $r) { $byCourse[$r['course_code'].' — '.$r['course_title']][] = $r; }

$pageTitle   = 'Resources';
$currentPage = 'Resources';
include '../includes/header.php';
?>
<div class="portal-layout">
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
<?php include '../includes/navbar.php'; ?>
<div class="page-content">

<div class="page-header">
    <div><h2>📁 Course Resources</h2><div class="page-subtitle">Materials shared by your teachers</div></div>
</div>

<?php if (empty($byCourse)): ?>
    <div class="empty-state"><div class="empty-icon">📁</div><p>No resources available yet.</p></div>
<?php else: foreach ($byCourse as $courseLabel => $items): ?>
<h3 class="mb-2">📚 <?= htmlspecialchars($courseLabel) ?></h3>
<div style="display:flex;flex-direction:column;gap:10px;margin-bottom:24px">
<?php foreach ($items as $r): ?>
<div class="resource-card">
    <div class="resource-icon"><?= Helper::fileIcon($r['file_path']) ?></div>
    <div style="flex:1">
        <div style="font-weight:700;color:var(--primary-dark)"><?= htmlspecialchars($r['title']) ?></div>
        <div style="font-size:0.78rem;color:var(--text-muted)">By <?= htmlspecialchars($r['teacher_name']) ?> · <?= Helper::timeAgo($r['created_at']) ?></div>
        <?php if ($r['description']): ?><div style="font-size:0.82rem;margin-top:4px"><?= htmlspecialchars($r['description']) ?></div><?php endif; ?>
    </div>
    <a href="<?= UPLOAD_URL . htmlspecialchars($r['file_path']) ?>" target="_blank" class="btn btn-sm btn-primary">📥 Download</a>
</div>
<?php endforeach; ?>
</div>
<?php endforeach; endif; ?>

</div>
<?php include '../includes/footer.php'; ?>
</div>
</div>
