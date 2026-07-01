<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
Auth::requireRole('teacher');

$teacherModel = new Teacher();
$resultModel  = new ResultModel();
$noticeModel  = new NoticeModel();
$resourceModel= new ResourceModel();

$teacher  = $teacherModel->findByUserId(Auth::userId());
$courses  = $teacherModel->getAssignedCourses($teacher['teacher_id']);
$myNotices   = $noticeModel->byPoster(Auth::userId());
$myResources = $resourceModel->byTeacher($teacher['teacher_id']);

// Quick stats
$totalStudentsTaught = 0;
$uniqueBatches = [];
foreach ($courses as $c) {
    if (!in_array($c['batch_id'], $uniqueBatches)) {
        $uniqueBatches[] = $c['batch_id'];
        $totalStudentsTaught += count((new Student())->byBatch($c['batch_id']));
    }
}

$pageTitle   = 'Teacher Dashboard';
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
        <h2>Welcome, <?= htmlspecialchars($teacher['name']) ?>!</h2>
        <div class="page-subtitle"><?= htmlspecialchars($teacher['designation'] ?: 'Faculty') ?> — Department of Computer Science and Engineering</div>
    </div>
    <div style="font-size:0.8rem;color:var(--text-muted)">📅 <?= date('l, F j, Y') ?></div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">📚</div>
        <div class="stat-info"><div class="stat-value"><?= count($courses) ?></div><div class="stat-label">Assigned Courses</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">🎓</div>
        <div class="stat-info"><div class="stat-value"><?= $totalStudentsTaught ?></div><div class="stat-label">Students Taught</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon teal">📢</div>
        <div class="stat-info"><div class="stat-value"><?= count($myNotices) ?></div><div class="stat-label">My Notices</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber">📁</div>
        <div class="stat-info"><div class="stat-value"><?= count($myResources) ?></div><div class="stat-label">Resources Shared</div></div>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header"><span class="card-title">📚 My Assigned Courses</span><a href="<?= BASE_URL ?>teacher/courses.php" class="btn btn-sm btn-outline">View All</a></div>
        <div class="card-body" style="padding:0">
            <?php if (empty($courses)): ?>
                <div class="empty-state"><div class="empty-icon">📚</div><p>No courses assigned yet.</p></div>
            <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>Course</th><th>Batch</th></tr></thead>
                    <tbody>
                    <?php foreach (array_slice($courses,0,5) as $c): ?>
                    <tr>
                        <td><div style="font-weight:600"><?= htmlspecialchars($c['course_title']) ?></div><span class="badge badge-info"><?= htmlspecialchars($c['course_code']) ?></span></td>
                        <td><?= htmlspecialchars($c['batch_name']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><span class="card-title">📢 My Recent Notices</span><a href="<?= BASE_URL ?>teacher/notices.php" class="btn btn-sm btn-outline">Manage</a></div>
        <div class="card-body" style="padding:0">
            <?php if (empty($myNotices)): ?>
                <div class="empty-state"><div class="empty-icon">📭</div><p>No notices posted yet.</p></div>
            <?php else: ?>
            <ul class="activity-list" style="padding:0 20px">
            <?php foreach (array_slice($myNotices,0,5) as $n): ?>
                <li class="activity-item">
                    <span class="activity-dot" style="background:#4ade80"></span>
                    <div><div style="font-size:0.86rem;font-weight:600;color:var(--primary-dark)"><?= htmlspecialchars($n['title']) ?></div><div class="activity-time"><?= Helper::timeAgo($n['created_at']) ?></div></div>
                </li>
            <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header"><span class="card-title">⚡ Quick Actions</span></div>
    <div class="card-body">
        <div style="display:flex;flex-wrap:wrap;gap:12px">
            <a href="<?= BASE_URL ?>teacher/results.php" class="btn btn-primary">+ Enter Marks</a>
            <a href="<?= BASE_URL ?>teacher/notices.php?action=add" class="btn btn-success">+ Post Notice</a>
            <a href="<?= BASE_URL ?>teacher/resources.php?action=add" class="btn btn-accent">+ Upload Resource</a>
            <a href="<?= BASE_URL ?>teacher/discussions.php" class="btn btn-outline">💡 View Discussions</a>
            <a href="<?= BASE_URL ?>teacher/profile.php" class="btn btn-outline">👤 My Profile</a>
        </div>
    </div>
</div>

</div>
<?php include '../includes/footer.php'; ?>
</div>
</div>
