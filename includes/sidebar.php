<?php
/**
 * sidebar.php — Role-adaptive fixed sidebar
 * Unified Discussion under "Communication" for all users.
 */
$role         = $_SESSION['role'] ?? 'guest';
$currentPage  = $currentPage ?? '';
$sidebarClass = 'sidebar sidebar-' . $role;

$settingsModel = $settingsModel ?? new SettingsModel();
$settings      = $settings ?? $settingsModel->get();

function navItem($href, $icon, $label, $current, $badge = null) {
    $active = ($current === $label) ? ' active' : '';
    $badgeHtml = $badge ? '<span class="nav-badge">' . $badge . '</span>' : '';
    return '<a href="' . htmlspecialchars($href) . '" class="nav-item' . $active . '">
        <span class="nav-icon">' . $icon . '</span>
        <span>' . htmlspecialchars($label) . '</span>' . $badgeHtml . '
    </a>';
}

$pendingUsersCount = count((new User())->getPendingUsers());
?>
<aside class="<?= $sidebarClass ?>">
    <div class="sidebar-brand">
        <div class="brand-logo">
            <div class="brand-icon">
                <?php if (!empty($settings['logo'])): ?>
                    <img src="<?= UPLOAD_URL . htmlspecialchars($settings['logo']) ?>" alt="">
                <?php else: ?>🎓<?php endif; ?>
            </div>
            <div class="brand-text">
                <div class="brand-name"><?= htmlspecialchars($settings['portal_name'] ?? 'CSE Department Portal') ?></div>
                <div class="brand-univ">JKKNIU</div>
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">

    <?php if ($role === 'admin'): ?>
        <div class="nav-section-label">Main</div>
        <?= navItem(BASE_URL.'admin/dashboard.php', '📊', 'Dashboard', $currentPage) ?>
        <?= navItem(BASE_URL.'admin/approvals.php', '✅', 'Approvals', $currentPage, $pendingUsersCount ?: null) ?>

        <div class="nav-section-label">User Management</div>
        <?= navItem(BASE_URL.'admin/students.php', '🎓', 'Students', $currentPage) ?>
        <?= navItem(BASE_URL.'admin/teachers.php', '👨‍🏫', 'Teachers', $currentPage) ?>

        <div class="nav-section-label">Academics</div>
        <?= navItem(BASE_URL.'admin/batches.php', '🗂️', 'Batches', $currentPage) ?>
        <?= navItem(BASE_URL.'admin/courses.php', '📚', 'Courses', $currentPage) ?>
        <?= navItem(BASE_URL.'admin/course_assignments.php', '🔗', 'Course Assignment', $currentPage) ?>
        <?= navItem(BASE_URL.'admin/routine.php', '🗓️', 'Routine', $currentPage) ?>
        <?= navItem(BASE_URL.'admin/exam_schedules.php', '📝', 'Exam Schedules', $currentPage) ?>
        <?= navItem(BASE_URL.'admin/results.php', '📈', 'Final Results', $currentPage) ?>

        <div class="nav-section-label">Communication</div>
        <?= navItem(BASE_URL.'admin/notices.php', '📢', 'Notices', $currentPage) ?>
        <?= navItem(BASE_URL.'admin/discussions.php', '💡', 'Discussion', $currentPage) ?>
        <?= navItem(BASE_URL.'admin/messages.php', '💬', 'Messages', $currentPage) ?>

        <div class="nav-section-label">System</div>
        <?= navItem(BASE_URL.'admin/settings.php', '⚙️', 'Settings', $currentPage) ?>

    <?php elseif ($role === 'teacher'): ?>
        <div class="nav-section-label">Main</div>
        <?= navItem(BASE_URL.'teacher/dashboard.php', '📊', 'Dashboard', $currentPage) ?>
        <?= navItem(BASE_URL.'teacher/profile.php', '👤', 'My Profile', $currentPage) ?>

        <div class="nav-section-label">Teaching</div>
        <?= navItem(BASE_URL.'teacher/courses.php', '📚', 'Assigned Courses', $currentPage) ?>
        <?= navItem(BASE_URL.'teacher/students.php', '🎓', 'Students', $currentPage) ?>
        <?= navItem(BASE_URL.'teacher/results.php', '📈', 'Result Entry', $currentPage) ?>
        <?= navItem(BASE_URL.'teacher/resources.php', '📁', 'Resources', $currentPage) ?>

        <div class="nav-section-label">Communication</div>
        <?= navItem(BASE_URL.'teacher/notices.php', '📢', 'Notices', $currentPage) ?>
        <?= navItem(BASE_URL.'teacher/discussions.php', '💡', 'Discussion', $currentPage) ?>
        <?= navItem(BASE_URL.'teacher/messages.php', '💬', 'Messages', $currentPage) ?>

    <?php elseif ($role === 'student'): ?>
        <div class="nav-section-label">Main</div>
        <?= navItem(BASE_URL.'student/dashboard.php', '📊', 'Dashboard', $currentPage) ?>
        <?= navItem(BASE_URL.'student/profile.php', '👤', 'My Profile', $currentPage) ?>

        <div class="nav-section-label">Academics</div>
        <?= navItem(BASE_URL.'student/routine.php', '🗓️', 'Routine', $currentPage) ?>
        <?= navItem(BASE_URL.'student/exam_schedule.php', '📝', 'Exam Schedule', $currentPage) ?>
        <?= navItem(BASE_URL.'student/results.php', '📈', 'Results', $currentPage) ?>
        <?= navItem(BASE_URL.'student/resources.php', '📁', 'Resources', $currentPage) ?>

        <div class="nav-section-label">Communication</div>
        <?= navItem(BASE_URL.'student/notices.php', '📢', 'Notices', $currentPage) ?>
        <?= navItem(BASE_URL.'student/discussions.php', '💡', 'Discussion', $currentPage) ?>
        <?= navItem(BASE_URL.'student/messages.php', '💬', 'Messages', $currentPage) ?>
    <?php endif; ?>
    </nav>
</aside>