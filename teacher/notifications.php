<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
Auth::requireRole('teacher');

$notifModel = new NotificationModel();
$notifications = $notifModel->forUser(Auth::userId(), 50);
$notifModel->markAllRead(Auth::userId());

$typeIcons = ['notice'=>'📢','result'=>'📈','resource'=>'📁','message'=>'💬','routine'=>'🗓️','exam'=>'📝','approval'=>'✅','security'=>'🔒'];

$pageTitle   = 'Notifications';
$currentPage = '';
include '../includes/header.php';
?>
<div class="portal-layout">
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
<?php include '../includes/navbar.php'; ?>
<div class="page-content">

<div class="page-header">
    <div><h2>🔔 Notifications</h2><div class="page-subtitle">Recent activity and updates</div></div>
</div>

<div class="card">
<div class="card-body" style="padding:0">
<?php if (empty($notifications)): ?>
    <div class="empty-state"><div class="empty-icon">🔔</div><p>No notifications yet.</p></div>
<?php else: ?>
<ul class="activity-list" style="padding:0 20px">
<?php foreach ($notifications as $n): ?>
<li class="activity-item">
    <span class="activity-type-icon"><?= $typeIcons[$n['type']] ?? '🔔' ?></span>
    <div>
        <div style="font-size:0.88rem;font-weight:600;color:var(--primary-dark)">
            <?= htmlspecialchars($n['title']) ?>
            <?php if (!$n['is_read']): ?><span class="badge badge-info" style="margin-left:6px;font-size:0.6rem">NEW</span><?php endif; ?>
        </div>
        <?php if ($n['message']): ?><div style="font-size:0.82rem;color:var(--text-muted);margin-top:2px"><?= htmlspecialchars($n['message']) ?></div><?php endif; ?>
        <div class="activity-time"><?= Helper::formatDate($n['created_at'], 'M j, Y \a\t g:i A') ?></div>
    </div>
</li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
</div>
</div>

</div>
<?php include '../includes/footer.php'; ?>
</div>
</div>
