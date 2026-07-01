<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
Auth::requireRole('admin');

$studentModel = new Student();
$teacherModel = new Teacher();
$courseModel  = new CourseModel();
$batchModel   = new BatchModel();
$noticeModel  = new NoticeModel();
$userModel    = new User();
$prModel      = new ProfileRequestModel();

$stats = [
    'total_students' => $studentModel->count(),
    'total_teachers' => $teacherModel->count(),
    'total_courses'  => $courseModel->count(),
    'total_batches'  => $batchModel->count(),
];

$pendingUsersCount = count($userModel->getPendingUsers());
$pendingReqCount   = $prModel->countPending();

$recentNotices = array_slice($noticeModel->all(), 0, 5);
$recentUsers   = array_slice($userModel->getPendingUsers(), 0, 5);

$pageTitle   = 'Dashboard';
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
        <h2>Admin Dashboard</h2>
        <div class="page-subtitle">Welcome back, <?= htmlspecialchars(Auth::userName()) ?>! Here's the portal overview.</div>
    </div>
    <div style="font-size:0.8rem;color:var(--text-muted);">📅 <?= date('l, F j, Y') ?></div>
</div>

<?php if (($pendingUsersCount + $pendingReqCount) > 0): ?>
<div class="alert alert-warning">
    ⏳ You have <strong><?= $pendingUsersCount ?></strong> pending registration<?= $pendingUsersCount!=1?'s':'' ?> and
    <strong><?= $pendingReqCount ?></strong> pending profile change request<?= $pendingReqCount!=1?'s':'' ?> awaiting review.
    <a href="<?= BASE_URL ?>admin/approvals.php" style="margin-left:6px;font-weight:700">Review now →</a>
</div>
<?php endif; ?>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">🎓</div>
        <div class="stat-info"><div class="stat-value"><?= $stats['total_students'] ?></div><div class="stat-label">Total Students</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">👨‍🏫</div>
        <div class="stat-info"><div class="stat-value"><?= $stats['total_teachers'] ?></div><div class="stat-label">Teachers</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber">📚</div>
        <div class="stat-info"><div class="stat-value"><?= $stats['total_courses'] ?></div><div class="stat-label">Courses</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">🗂️</div>
        <div class="stat-info"><div class="stat-value"><?= $stats['total_batches'] ?></div><div class="stat-label">Batches</div></div>
    </div>
</div>

<div class="grid-2">
    <!-- Recent Notices -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">📢 Recent Notices</span>
            <a href="<?= BASE_URL ?>admin/notices.php" class="btn btn-sm btn-outline">View All</a>
        </div>
        <div class="card-body" style="padding:0">
            <?php if (empty($recentNotices)): ?>
                <div class="empty-state"><div class="empty-icon">📭</div><p>No notices yet.</p></div>
            <?php else: ?>
            <ul class="activity-list" style="padding:0 20px">
            <?php foreach ($recentNotices as $n): ?>
                <li class="activity-item">
                    <span class="activity-dot" style="background:#0ea5e9"></span>
                    <div>
                        <div style="font-size:0.87rem;font-weight:600;color:var(--primary-dark)"><?= htmlspecialchars($n['title']) ?></div>
                        <div class="activity-time">By <?= htmlspecialchars($n['poster']) ?> · <?= Helper::formatDate($n['created_at']) ?></div>
                    </div>
                </li>
            <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pending Approvals Preview -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">⏳ Pending Registrations</span>
            <a href="<?= BASE_URL ?>admin/approvals.php" class="btn btn-sm btn-outline">Review</a>
        </div>
        <div class="card-body" style="padding:0">
            <?php if (empty($recentUsers)): ?>
                <div class="empty-state"><div class="empty-icon">✅</div><p>No pending registrations.</p></div>
            <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>Name</th><th>Role</th><th>Requested</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentUsers as $u): ?>
                        <tr>
                            <td>
                                <div style="font-weight:600"><?= htmlspecialchars($u['name']) ?></div>
                                <div style="font-size:0.75rem;color:var(--text-muted)"><?= htmlspecialchars($u['email']) ?></div>
                            </td>
                            <td><span class="badge badge-info" style="text-transform:capitalize"><?= htmlspecialchars($u['role']) ?></span></td>
                            <td style="font-size:0.78rem;color:var(--text-muted)"><?= Helper::timeAgo($u['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card mt-3">
    <div class="card-header"><span class="card-title">⚡ Quick Actions</span></div>
    <div class="card-body">
        <div style="display:flex;flex-wrap:wrap;gap:12px;">
            <a href="<?= BASE_URL ?>admin/approvals.php" class="btn btn-warning">⏳ Review Approvals</a>
            <a href="<?= BASE_URL ?>admin/students.php?action=add" class="btn btn-primary">+ Add Student</a>
            <a href="<?= BASE_URL ?>admin/teachers.php?action=add" class="btn btn-success">+ Add Teacher</a>
            <a href="<?= BASE_URL ?>admin/notices.php?action=add"  class="btn btn-accent">+ Post Notice</a>
            <a href="<?= BASE_URL ?>admin/course_assignments.php" class="btn btn-outline">🔗 Assign Courses</a>
        </div>
    </div>
</div>

</div>
<?php include '../includes/footer.php'; ?>
</div>
</div>
