<?php
/**
 * migrate_unread_tracking.php — Run ONCE if you already imported an older
 * version of database.sql. This migration adds/changes:
 *   - notifications.is_read
 *   - messages.is_read, messages.message nullable
 *   - Replaces old discussions/discussion_replies (Q&A threads) with the
 *     new discussion_groups/discussion_messages (WhatsApp-style group chat)
 * Safe to run multiple times (checks before altering).
 * DELETE this file after running.
 */
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();
$log = [];

function columnExists(PDO $db, string $table, string $column): bool {
    $stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $stmt->execute([$table, $column]);
    return (int)$stmt->fetchColumn() > 0;
}

function tableExists(PDO $db, string $table): bool {
    $stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
    $stmt->execute([$table]);
    return (int)$stmt->fetchColumn() > 0;
}

try {
    if (!columnExists($db, 'notifications', 'is_read')) {
        $db->exec("ALTER TABLE notifications ADD COLUMN is_read TINYINT(1) NOT NULL DEFAULT 0 AFTER type");
        $log[] = "✅ Added notifications.is_read";
    } else {
        $log[] = "⚠️  notifications.is_read already exists — skipped";
    }

    if (!columnExists($db, 'messages', 'is_read')) {
        $db->exec("ALTER TABLE messages ADD COLUMN is_read TINYINT(1) NOT NULL DEFAULT 0 AFTER message");
        $log[] = "✅ Added messages.is_read";
    } else {
        $log[] = "⚠️  messages.is_read already exists — skipped";
    }

    // Allow attachment-only messages (no text required)
    $db->exec("ALTER TABLE messages MODIFY message TEXT NULL");
    $log[] = "✅ Made messages.message nullable (supports attachment-only messages)";

    // ── Discussion system: Q&A threads → WhatsApp-style group chat ──
    if (!tableExists($db, 'discussion_groups')) {
        $db->exec("
            CREATE TABLE discussion_groups (
                group_id INT AUTO_INCREMENT PRIMARY KEY,
                course_id INT NOT NULL,
                batch_id INT DEFAULT NULL,
                created_by INT NOT NULL,
                title VARCHAR(255) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_group (course_id, batch_id),
                CONSTRAINT fk_dg_course  FOREIGN KEY (course_id)  REFERENCES courses(course_id) ON DELETE CASCADE,
                CONSTRAINT fk_dg_batch   FOREIGN KEY (batch_id)   REFERENCES batches(batch_id)  ON DELETE CASCADE,
                CONSTRAINT fk_dg_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB
        ");
        $log[] = "✅ Created discussion_groups table";
    } else {
        $log[] = "⚠️  discussion_groups already exists — skipped";
    }

    if (!tableExists($db, 'discussion_messages')) {
        $db->exec("
            CREATE TABLE discussion_messages (
                message_id INT AUTO_INCREMENT PRIMARY KEY,
                group_id INT NOT NULL,
                user_id INT NOT NULL,
                body TEXT NULL,
                attachment VARCHAR(255) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_dm_group FOREIGN KEY (group_id) REFERENCES discussion_groups(group_id) ON DELETE CASCADE,
                CONSTRAINT fk_dm_user  FOREIGN KEY (user_id)  REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB
        ");
        $log[] = "✅ Created discussion_messages table";
    } else {
        $log[] = "⚠️  discussion_messages already exists — skipped";
    }

    // Drop old Q&A-thread tables if they still exist (data is not migrated —
    // the model changed shape entirely, from threads+replies to group chat)
    if (tableExists($db, 'discussion_replies')) {
        $db->exec("DROP TABLE discussion_replies");
        $log[] = "✅ Dropped old discussion_replies table";
    }
    if (tableExists($db, 'discussions')) {
        $db->exec("DROP TABLE discussions");
        $log[] = "✅ Dropped old discussions table (old Q&A threads are not preserved — new group-chat model is structurally different)";
    }

} catch (PDOException $e) {
    $log[] = "❌ Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Migration</title>
<style>
body { font-family: monospace; background: #0d2f5c; color: #f0f4f8; padding: 40px; }
.box { background: #1a4d8f; border-radius: 12px; padding: 30px; max-width: 640px; margin: auto; }
h2 { color: #0ea5e9; }
.log { line-height: 1.9; font-size: 0.88rem; }
a { color: #0ea5e9; }
</style>
</head>
<body>
<div class="box">
    <h2>🔧 Database Migration</h2>
    <div class="log"><?php foreach ($log as $line) echo "<div>$line</div>"; ?></div>
    <p style="margin-top:20px">⚠️ Delete this file now. <a href="<?= BASE_URL ?>login.php">→ Go to Login</a></p>
</div>
</body></html>
