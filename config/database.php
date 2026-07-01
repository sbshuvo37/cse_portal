<?php
/**
 * database.php — Loads config.php and exposes a ready PDO instance
 * via the Database class (app/classes/Database.php).
 *
 * Usage in any page:
 *   require_once __DIR__ . '/../config/database.php';
 *   $db = Database::getInstance()->getConnection();
 */

require_once __DIR__ . '/config.php';
// Database class is autoloaded from app/classes/Database.php
// Trigger a connection check early so failures surface immediately.
try {
    Database::getInstance();
} catch (PDOException $e) {
    die('<div style="font-family:sans-serif;background:#fee2e2;color:#991b1b;padding:20px;margin:20px;border-radius:8px;">
        <strong>Database Connection Failed.</strong><br>
        Please ensure MySQL is running in XAMPP and the database <em>cse_portal</em> exists.<br>
        Error: ' . htmlspecialchars($e->getMessage()) . '
    </div>');
}
