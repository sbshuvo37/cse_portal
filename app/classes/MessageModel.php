<?php
/**
 * MessageModel — messages + message_files operations
 * CSE Department Portal
 */
class MessageModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function send(int $senderId, int $receiverId, ?string $body): int
    {
        $body = ($body !== null && trim($body) !== '') ? trim($body) : null;
        $stmt = $this->db->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?,?,?)");
        $stmt->execute([$senderId, $receiverId, $body]);
        return (int) $this->db->lastInsertId();
    }

    public function addFile(int $messageId, string $path): bool
    {
        $stmt = $this->db->prepare("INSERT INTO message_files (message_id, file_path) VALUES (?,?)");
        return $stmt->execute([$messageId, $path]);
    }

    public function getFiles(int $messageId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM message_files WHERE message_id=?");
        $stmt->execute([$messageId]);
        return $stmt->fetchAll();
    }

    /** Conversation between two users, oldest first. Marks incoming messages as read. */
    public function conversation(int $userA, int $userB): array
    {
        $stmt = $this->db->prepare(
            "SELECT m.*, u.name AS sender_name
             FROM messages m JOIN users u ON m.sender_id = u.id
             WHERE (sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?)
             ORDER BY m.created_at ASC"
        );
        $stmt->execute([$userA, $userB, $userB, $userA]);
        $rows = $stmt->fetchAll();

        // Mark messages sent TO userA (i.e. from userB) as read, since userA is viewing this thread now
        $this->markThreadRead($userB, $userA);

        return $rows;
    }

    /** Mark all messages from $fromUserId to $toUserId as read */
    public function markThreadRead(int $fromUserId, int $toUserId): bool
    {
        $stmt = $this->db->prepare("UPDATE messages SET is_read=1 WHERE sender_id=? AND receiver_id=? AND is_read=0");
        return $stmt->execute([$fromUserId, $toUserId]);
    }

    /** List of distinct conversation partners with last message preview + per-thread unread count */
    public function conversationList(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT u.id, u.name, u.role, u.profile_photo,
                    (SELECT message FROM messages m2
                     WHERE (m2.sender_id=u.id AND m2.receiver_id=:uid1) OR (m2.sender_id=:uid2 AND m2.receiver_id=u.id)
                     ORDER BY m2.created_at DESC LIMIT 1) AS last_message,
                    (SELECT created_at FROM messages m3
                     WHERE (m3.sender_id=u.id AND m3.receiver_id=:uid3) OR (m3.sender_id=:uid4 AND m3.receiver_id=u.id)
                     ORDER BY m3.created_at DESC LIMIT 1) AS last_time,
                    (SELECT COUNT(*) FROM messages m4
                     WHERE m4.sender_id=u.id AND m4.receiver_id=:uid7 AND m4.is_read=0) AS unread_count
             FROM users u
             WHERE u.id IN (
                SELECT sender_id FROM messages WHERE receiver_id=:uid5
                UNION
                SELECT receiver_id FROM messages WHERE sender_id=:uid6
             )
             ORDER BY last_time DESC"
        );
        $stmt->execute([
            'uid1' => $userId, 'uid2' => $userId, 'uid3' => $userId,
            'uid4' => $userId, 'uid5' => $userId, 'uid6' => $userId, 'uid7' => $userId,
        ]);
        return $stmt->fetchAll();
    }

    /** Total unread messages across all conversations — for the navbar badge */
    public function countUnreadTotal(int $userId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id=? AND is_read=0");
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }
}
