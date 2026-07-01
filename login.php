<?php
require_once 'config/database.php';

if (Auth::isLoggedIn()) {
    Auth::redirectByRole();
}

$settingsModel = new SettingsModel();
$settings      = $settingsModel->get();

$error   = '';
$success = '';

if (isset($_GET['error']) && $_GET['error'] === 'unauthorized') {
    $error = 'You are not authorized to access that page. Please log in with the correct account.';
}
if (isset($_GET['registered'])) {
    $success = 'Registration submitted! Your account is pending admin approval. You will be able to log in once approved.';
}
if (isset($_GET['reset'])) {
    $success = 'Your password has been reset successfully. Please log in.';
}
if (isset($_GET['logout'])) {
    $success = 'You have been logged out successfully.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Please enter your email and password.';
    } else {
        $auth   = new Auth();
        $result = $auth->login($email, $password);
        if ($result['success']) {
            Auth::redirectByRole();
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>document.documentElement.classList.remove('no-js');</script>
    <title>Login — <?= htmlspecialchars($settings['portal_name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/auth.css">
</head>
<body class="auth-page">

<div class="auth-container">
    <!-- LEFT: Department info -->
    <div class="auth-left">
        <div class="auth-logo">
            <?php if (!empty($settings['logo'])): ?>
                <img src="<?= UPLOAD_URL . htmlspecialchars($settings['logo']) ?>" alt="">
            <?php else: ?>🎓<?php endif; ?>
        </div>
        <div class="auth-brand-name"><?= htmlspecialchars($settings['department_name']) ?></div>
        <div class="auth-univ"><?= htmlspecialchars($settings['university_name']) ?></div>
        <div class="auth-desc"><?= htmlspecialchars($settings['description'] ?: 'Welcome to the official academic management portal of the CSE Department.') ?></div>
        <ul class="auth-features">
            <li><span class="feature-dot"></span>Secure role-based access control</li>
            <li><span class="feature-dot"></span>Results, routines & exam schedules</li>
            <li><span class="feature-dot"></span>Course resources & discussions</li>
            <li><span class="feature-dot"></span>Private messaging between users</li>
            <li><span class="feature-dot"></span>Department notices & announcements</li>
        </ul>
    </div>

    <!-- RIGHT: Login form -->
    <div class="auth-right">
        <div class="auth-form-title">Welcome Back</div>
        <div class="auth-form-subtitle">Sign in to access your portal account</div>

        <?php if ($error): ?><div class="auth-alert auth-alert-danger">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success" data-auto-dismiss><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <form class="auth-form" method="POST" action="">
            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input class="form-control" type="email" id="email" name="email" placeholder="your@email.com"
                       value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required autofocus>
            </div>
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input class="form-control" type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>

            <div class="auth-links-row">
                <span></span>
                <a href="<?= BASE_URL ?>forgot_password.php">Forgot Password?</a>
            </div>

            <button type="submit" class="btn-auth">Sign In →</button>
        </form>

        <div class="auth-switch">
            Don't have an account? <a href="<?= BASE_URL ?>register.php">Register here</a>
        </div>

        <div class="auth-divider">Demo Credentials</div>
        <div style="font-size:0.76rem;color:#64748b;background:#f8fafc;border-radius:8px;padding:14px;line-height:1.9;">
            <strong>Admin:</strong> admin@cse.jkkniu.edu.bd<br>
            <strong>Teacher:</strong> rafiqul@cse.jkkniu.edu.bd<br>
            <strong>Student:</strong> karim@student.jkkniu.edu.bd<br>
            <strong>Password (all):</strong> <code style="background:#e2e8f0;padding:2px 6px;border-radius:4px;">password</code>
            <br><small style="color:#94a3b8;">⚠ Run <a href="<?= BASE_URL ?>setup_passwords.php">setup_passwords.php</a> once after importing the SQL file, then delete it.</small>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>assets/js/script.js"></script>
</body>
</html>
