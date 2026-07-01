<?php
require_once 'config/database.php';

if (Auth::isLoggedIn()) {
    Auth::redirectByRole();
}

$settingsModel = new SettingsModel();
$settings      = $settingsModel->get();
$error   = '';
$token   = trim($_GET['token'] ?? $_POST['token'] ?? '');

$auth   = new Auth();
$userId = $token ? $auth->validateResetToken($token) : null;

if (!$token || !$userId) {
    $error = 'This password reset link is invalid or has expired. Please request a new one.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $userId) {
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm']  ?? '');

    if (empty($password) || strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $auth->resetPassword($userId, $password);
        header('Location: ' . BASE_URL . 'login.php?reset=1');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — <?= htmlspecialchars($settings['portal_name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/auth.css">
</head>
<body class="auth-page">

<div class="auth-container">
    <div class="auth-left">
        <div class="auth-logo">🔒</div>
        <div class="auth-brand-name">Set a New Password</div>
        <div class="auth-univ"><?= htmlspecialchars($settings['university_name']) ?></div>
        <div class="auth-desc">Choose a strong password you haven't used before. This link can only be used once and expires after 1 hour.</div>
    </div>

    <div class="auth-right">
        <div class="auth-form-title">Reset Password</div>
        <div class="auth-form-subtitle">Enter your new password below</div>

        <?php if ($error): ?>
            <div class="auth-alert auth-alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
            <div class="auth-switch"><a href="<?= BASE_URL ?>forgot_password.php">Request a new reset link →</a></div>
        <?php else: ?>
        <form class="auth-form" method="POST">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <div class="form-group">
                <label class="form-label">New Password</label>
                <input type="password" name="password" class="form-control" placeholder="Min. 6 characters" required autofocus>
            </div>
            <div class="form-group">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="confirm" class="form-control" placeholder="Repeat password" required>
            </div>
            <button type="submit" class="btn-auth">Reset Password →</button>
        </form>
        <?php endif; ?>

        <div class="auth-switch">
            <a href="<?= BASE_URL ?>login.php">Back to Login</a>
        </div>
    </div>
</div>

</body>
</html>
