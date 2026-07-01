<?php
require_once 'config/database.php';

if (Auth::isLoggedIn()) {
    Auth::redirectByRole();
}

$settingsModel = new SettingsModel();
$settings      = $settingsModel->get();
$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $auth  = new Auth();
        $token = $auth->createResetToken($email);

        // For a portal without an SMTP/mail server, we surface the reset link directly.
        // In a production deployment this would be emailed instead.
        if ($token) {
            $resetLink = BASE_URL . 'reset_password.php?token=' . $token;
            $success = 'A password reset link has been generated. Since this demo environment has no mail server configured, use the link below:';
            $_SESSION['demo_reset_link'] = $resetLink;
        } else {
            // Don't reveal whether the email exists, for security.
            $success = 'If an account exists with that email, a password reset link has been generated.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — <?= htmlspecialchars($settings['portal_name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/auth.css">
</head>
<body class="auth-page">

<div class="auth-container">
    <div class="auth-left">
        <div class="auth-logo">🔑</div>
        <div class="auth-brand-name">Forgot Your Password?</div>
        <div class="auth-univ"><?= htmlspecialchars($settings['university_name']) ?></div>
        <div class="auth-desc">No worries. Enter your registered email address and we'll generate a secure password reset link for you.</div>
    </div>

    <div class="auth-right">
        <div class="auth-form-title">Reset Password</div>
        <div class="auth-form-subtitle">Enter your email to receive a reset link</div>

        <?php if ($error): ?><div class="auth-alert auth-alert-danger">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?>
            <div class="auth-alert auth-alert-success">✅ <?= htmlspecialchars($success) ?></div>
            <?php if (!empty($_SESSION['demo_reset_link'])): ?>
                <div class="auth-alert auth-alert-info">
                    🔗 <a href="<?= htmlspecialchars($_SESSION['demo_reset_link']) ?>"><?= htmlspecialchars($_SESSION['demo_reset_link']) ?></a>
                </div>
                <?php unset($_SESSION['demo_reset_link']); ?>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form class="auth-form" method="POST">
            <div class="form-group">
                <label class="form-label" for="email">Registered Email Address</label>
                <input class="form-control" type="email" id="email" name="email" placeholder="your@email.com" required autofocus>
            </div>
            <button type="submit" class="btn-auth">Send Reset Link →</button>
        </form>
        <?php endif; ?>

        <div class="auth-switch">
            Remembered your password? <a href="<?= BASE_URL ?>login.php">Back to Login</a>
        </div>
    </div>
</div>

</body>
</html>
