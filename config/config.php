<?php
/**
 * config.php — Application-wide constants & settings
 * CSE Department Portal — JKKNIU
 */

// ── Base URL Configuration ──────────────────────────────────
define('BASE_URL', '/cse_portal/');
define('ROOT_PATH', dirname(__DIR__));               // .../cse_portal
define('UPLOAD_PATH', ROOT_PATH . '/uploads/');       // filesystem path
define('UPLOAD_URL', BASE_URL . 'uploads/');          // browser path

// ── Database Configuration ──────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'cse_portal');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ── File Upload Rules ────────────────────────────────────────
define('MAX_UPLOAD_SIZE', 20 * 1024 * 1024); // 20 MB
define('ALLOWED_EXTENSIONS', ['pdf','jpg','jpeg','png','ppt','pptx','doc','docx','zip']);
define('ALLOWED_PHOTO_EXTENSIONS', ['jpg','jpeg','png']);

// ── Session Bootstrapping ───────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Error Reporting (development) ───────────────────────────
error_reporting(E_ALL);
ini_set('display_errors', '1');
date_default_timezone_set('Asia/Dhaka');

// ── Autoload OOP classes ─────────────────────────────────────
spl_autoload_register(function ($class) {
    $paths = [
        ROOT_PATH . '/app/classes/' . $class . '.php',
        ROOT_PATH . '/app/models/' . $class . '.php',
        ROOT_PATH . '/app/controllers/' . $class . '.php',
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});
