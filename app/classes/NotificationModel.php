<?php
/**
 * NotificationModel — notifications table operations
 * CSE Department Portal
 */
class NotificationModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create(int $userId, string $title, ?string $message, string $type): bool
    {
        $stmt = $this->db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?,?,?,?)");
        return $stmt->execute([$userId, $title, $message, $type]);
    }

    /** Notify many users at once (e.g. all students of a batch) */
    public function createBulk(array $userIds, string $title, ?string $message, string $type): void
    {
        $stmt = $this->db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?,?,?,?)");
        foreach ($userIds as $uid) {
            $stmt->execute([$uid, $title, $message, $type]);
        }
    }

    public function forUser(int $userId, int $limit = 20): array
    {
        $stmt = $this->db->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT ?");
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Total notifications (used for empty-state checks, not the badge) */
    public function countForUser(int $userId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=?");
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    /** Unread count — this is what the navbar bell badge should show */
    public function countUnread(int $userId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0");
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    /** Mark all of a user's notifications as read (call when they open the notifications page) */
    public function markAllRead(int $userId): bool
    {
        $stmt = $this->db->prepare("UPDATE notifications SET is_read=1 WHERE user_id=? AND is_read=0");
        return $stmt->execute([$userId]);
    }

    public function markOneRead(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare("UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?");
        return $stmt->execute([$id, $userId]);
    }
}

