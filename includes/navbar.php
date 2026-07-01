<?php
/**
 * navbar.php — Top navigation bar
 * Expects: $pageTitle, $_SESSION (role, user_name, user_id)
 */
$role     = $_SESSION['role']      ?? 'guest';
$userName = $_SESSION['user_name'] ?? 'User';
$initials = Helper::initials($userName);

// Unread counts (real tracking — bell shows unread notifications, chat shows unread messages)
$notifModel    = new NotificationModel();
$messageModel  = new MessageModel();
$notifCount    = Auth::userId() ? $notifModel->countUnread(Auth::userId()) : 0;
$msgUnreadCount= Auth::userId() ? $messageModel->countUnreadTotal(Auth::userId()) : 0;

// Profile photo (if set)
$userModel = new User();
$currentUser = Auth::userId() ? $userModel->findById(Auth::userId()) : null;
$photo = $currentUser['profile_photo'] ?? null;

$searchQuery = trim($_GET['q'] ?? '');
?>
<nav class="top-navbar">
    <div class="navbar-left">
        <button class="sidebar-toggle navbar-icon-btn" onclick="toggleSidebar()" style="display:none" id="sidebarToggleBtn">☰</button>
        <span class="navbar-title"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></span>
    </div>

    <div class="navbar-search-wrap">
        <form class="navbar-search" id="navbarSearchForm" action="<?= BASE_URL . $role ?>/search.php" method="GET">
            <span class="navbar-search-icon">🔍</span>
            <input type="text" name="q" id="navbarSearchInput" placeholder="Search students, courses, notices, resources..." value="<?= htmlspecialchars($searchQuery) ?>" autocomplete="off">
            <button type="button" class="navbar-search-close" id="navbarSearchClose" aria-label="Close search">✕</button>
        </form>
        <button type="button" class="navbar-search-btn" id="navbarSearchToggle" title="Search" aria-label="Open search">🔍</button>
    </div>

    <div class="navbar-right">
        <a href="<?= BASE_URL ?><?= $role ?>/notifications.php" class="navbar-icon-btn" title="Notifications">
            🔔
            <?php if ($notifCount > 0): ?><span class="nav-badge-float"><?= $notifCount > 9 ? '9+' : $notifCount ?></span><?php endif; ?>
        </a>
        <a href="<?= BASE_URL ?><?= $role ?>/messages.php" class="navbar-icon-btn" title="Messages">
            💬
            <?php if ($msgUnreadCount > 0): ?><span class="nav-badge-float"><?= $msgUnreadCount > 9 ? '9+' : $msgUnreadCount ?></span><?php endif; ?>
        </a>
        <div class="navbar-user">
            <div class="user-avatar">
                <?php if ($photo): ?>
                    <img src="<?= UPLOAD_URL . htmlspecialchars($photo) ?>" alt="">
                <?php else: ?>
                    <?= htmlspecialchars($initials) ?>
                <?php endif; ?>
            </div>
            <div>
                <div style="font-weight:700;font-size:0.84rem;color:var(--primary-dark)"><?= htmlspecialchars($userName) ?></div>
                <div style="font-size:0.7rem;color:var(--text-muted);text-transform:capitalize"><?= htmlspecialchars($role) ?></div>
            </div>
        </div>
        <a href="<?= BASE_URL ?>logout.php" class="navbar-logout">⏻ Logout</a>
    </div>
</nav>
<script>
if (window.innerWidth <= 900) { document.getElementById('sidebarToggleBtn').style.display = 'flex'; }
window.addEventListener('resize', () => {
    const btn = document.getElementById('sidebarToggleBtn');
    if (btn) btn.style.display = window.innerWidth <= 900 ? 'flex' : 'none';
});

// Icon-triggered search box
(() => {
    const toggleBtn = document.getElementById('navbarSearchToggle');
    const closeBtn  = document.getElementById('navbarSearchClose');
    const searchBox = document.getElementById('navbarSearchForm');
    const input      = document.getElementById('navbarSearchInput');
    if (!toggleBtn || !searchBox) return;

    const openSearch = () => {
        searchBox.classList.add('open');
        toggleBtn.classList.add('active');
        setTimeout(() => input.focus(), 150);
    };
    const closeSearch = () => {
        searchBox.classList.remove('open');
        toggleBtn.classList.remove('active');
    };

    toggleBtn.addEventListener('click', () => {
        searchBox.classList.contains('open') ? closeSearch() : openSearch();
    });
    closeBtn?.addEventListener('click', closeSearch);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeSearch();
    });

    // Auto-open if a query is already present (e.g. after submitting from a result page)
    <?php if ($searchQuery): ?>openSearch();<?php endif; ?>
})();
</script>
