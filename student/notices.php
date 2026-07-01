<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
Auth::requireRole('student');

$studentModel = new Student();
$noticeModel  = new NoticeModel();

$student = $studentModel->findByUserId(Auth::userId());
$notices = $student['batch_id'] ? $noticeModel->visibleTo($student['batch_id']) : $noticeModel->visibleTo();

$pageTitle   = 'Notices';
$currentPage = 'Notices';
include '../includes/header.php';
?>
<div class="portal-layout">
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
<?php include '../includes/navbar.php'; ?>
<div class="page-content">

<div class="page-header">
    <div><h2>📢 Notices</h2><div class="page-subtitle">Department and batch announcements</div></div>
    <span class="badge badge-info" style="font-size:0.82rem;padding:7px 14px"><?= count($notices) ?> notices</span>
</div>

<div class="notice-list">
<?php if (empty($notices)): ?>
    <div class="empty-state"><div class="empty-icon">📭</div><p>No notices published yet.</p></div>
<?php else: foreach ($notices as $n): ?>
<?php $files = (new NoticeModel())->getFiles($n['notice_id']); ?>
<div class="notice-card">
    <div class="notice-card-title"><?= htmlspecialchars($n['title']) ?></div>
    <div class="notice-card-meta">
        📅 <?= Helper::formatDate($n['created_at'], 'F j, Y \a\t g:i A') ?> &nbsp;|&nbsp; 👤 <?= htmlspecialchars($n['poster']) ?>
        <span class="badge <?= $n['poster_role']==='admin'?'badge-primary':'badge-success' ?>" style="text-transform:capitalize"><?= htmlspecialchars($n['poster_role']) ?></span>
    </div>
    <div class="notice-card-body"><?= nl2br(htmlspecialchars($n['description'])) ?></div>
    <?php if (!empty($files)): ?>
    <div class="notice-attachments"><?php foreach ($files as $f): ?><a href="<?= UPLOAD_URL . htmlspecialchars($f['file_path']) ?>" target="_blank" class="notice-attachment-chip"><?= Helper::fileIcon($f['file_path']) ?> Download Attachment</a><?php endforeach; ?></div>
    <?php endif; ?>
</div>
<?php endforeach; endif; ?>
</div>

</div>
<?php include '../includes/footer.php'; ?>
</div>
</div>
