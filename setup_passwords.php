<?php
/**
 * setup_passwords.php — Run ONCE after importing sql/database.sql
 * to set proper bcrypt password hashes for sample users.
 * DELETE this file after running.
 */
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

$users = [
    ['email' => 'admin@cse.jkkniu.edu.bd',                  'password' => 'Admin@123'],
    ['email' => 'rafiqul@cse.jkkniu.edu.bd',                 'password' => 'Teacher@123'],
    ['email' => 'nasrin@cse.jkkniu.edu.bd',                  'password' => 'Teacher@123'],
    ['email' => 'jahangir@cse.jkkniu.edu.bd',                'password' => 'Teacher@123'],
    ['email' => 'karim@student.jkkniu.edu.bd',               'password' => 'Student@123'],
    ['email' => 'rina@student.jkkniu.edu.bd',                'password' => 'Student@123'],
    ['email' => 'sumon@student.jkkniu.edu.bd',               'password' => 'Student@123'],
    ['email' => 'tania@student.jkkniu.edu.bd',               'password' => 'Student@123'],
    ['email' => 'nasima.pending@student.jkkniu.edu.bd',      'password' => 'Student@123'],
];

$log = [];
$updated = 0;

foreach ($users as $u) {
    $hash = password_hash($u['password'], PASSWORD_BCRYPT);
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->execute([$hash, $u['email']]);
    if ($stmt->rowCount() > 0) {
        $updated++;
        $log[] = "✅ Updated: " . htmlspecialchars($u['email']) . " → " . htmlspecialchars($u['password']);
    } else {
        $log[] = "⚠️  Not found: " . htmlspecialchars($u['email']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Setup Passwords</title>
<style>
body { font-family: monospace; background: #0d2f5c; color: #f0f4f8; padding: 40px; }
.box { background: #1a4d8f; border-radius: 12px; padding: 30px; max-width: 640px; margin: auto; }
h2 { color: #0ea5e9; margin-bottom: 20px; }
.log { line-height: 2; font-size: 0.9rem; }
.note { margin-top: 24px; background: rgba(14,165,233,0.15); border: 1px solid #0ea5e9; border-radius: 8px; padding: 14px; font-size: 0.85rem; color: #7dd3fc; }
a { color: #0ea5e9; }
</style>
</head>
<body>
<div class="box">
    <h2>🔐 Password Setup Utility</h2>
    <div class="log">
        <?php foreach ($log as $line): ?><div><?= $line ?></div><?php endforeach; ?>
        <div style="margin-top:16px;color:#4ade80;">✅ Done! <?= $updated ?> passwords updated.</div>
    </div>
    <div class="note">
        ⚠️ <strong>Important:</strong> Delete this file after running!<br><br>
        <strong>Login credentials:</strong><br>
        Admin: admin@cse.jkkniu.edu.bd / <code>Admin@123</code><br>
        Teacher: rafiqul@cse.jkkniu.edu.bd / <code>Teacher@123</code><br>
        Student: karim@student.jkkniu.edu.bd / <code>Student@123</code><br>
        Pending demo student: nasima.pending@student.jkkniu.edu.bd / <code>Student@123</code> (shows approval flow)<br><br>
        <a href="<?= BASE_URL ?>login.php">→ Go to Login</a>
    </div>
</div>
</body>
</html>
