<?php
/**
 * DiscussionModel — discussion_groups + discussion_messages operations
 * WhatsApp-style group chat: one group per Course+Batch (teacher-created)
 * or per Course across all batches (admin-created, batch_id = NULL).
 * CSE Department Portal
 */
class DiscussionModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // ── Groups ───────────────────────────────────────────────

    /**
     * Create a group. Returns ['success'=>bool,'message'=>string,'group_id'=>int|null]
     * A group is unique per (course_id, batch_id) — including batch_id IS NULL for admin's all-batch groups.
     */
    public function createGroup(int $courseId, ?int $batchId, int $createdBy, ?string $title = null): array
    {
        $existing = $this->findGroupByCourseBatch($courseId, $batchId);
        if ($existing) {
            return ['success' => false, 'message' => 'A discussion group already exists for this course/batch.', 'group_id' => (int)$existing['group_id']];
        }
        $stmt = $this->db->prepare("INSERT INTO discussion_groups (course_id, batch_id, created_by, title) VALUES (?,?,?,?)");
        $stmt->execute([$courseId, $batchId, $createdBy, $title]);
        return ['success' => true, 'message' => 'Discussion group created.', 'group_id' => (int) $this->db->lastInsertId()];
    }

    public function findGroupByCourseBatch(int $courseId, ?int $batchId): ?array
    {
        if ($batchId === null) {
            $stmt = $this->db->prepare("SELECT * FROM discussion_groups WHERE course_id=? AND batch_id IS NULL");
            $stmt->execute([$courseId]);
        } else {
            $stmt = $this->db->prepare("SELECT * FROM discussion_groups WHERE course_id=? AND batch_id=?");
            $stmt->execute([$courseId, $batchId]);
        }
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findGroupById(int $groupId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT dg.*, c.course_code, c.course_title, b.batch_name, u.name AS creator_name
             FROM discussion_groups dg
             JOIN courses c ON dg.course_id = c.course_id
             LEFT JOIN batches b ON dg.batch_id = b.batch_id
             JOIN users u ON dg.created_by = u.id
             WHERE dg.group_id = ?"
        );
        $stmt->execute([$groupId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Groups visible to a teacher: ones they created OR for courses they teach */
    public function groupsForTeacherCourses(array $courseBatchPairs): array
    {
        if (empty($courseBatchPairs)) return [];
        $clauses = [];
        $params  = [];
        foreach ($courseBatchPairs as $pair) {
            $clauses[] = "(dg.course_id = ? AND (dg.batch_id = ? OR dg.batch_id IS NULL))";
            $params[]  = $pair['course_id'];
            $params[]  = $pair['batch_id'];
        }
        $where = implode(' OR ', $clauses);
        $stmt = $this->db->prepare(
            "SELECT dg.*, c.course_code, c.course_title, b.batch_name,
                    (SELECT COUNT(*) FROM discussion_messages WHERE group_id = dg.group_id) AS message_count,
                    (SELECT created_at FROM discussion_messages WHERE group_id = dg.group_id ORDER BY created_at DESC LIMIT 1) AS last_activity
             FROM discussion_groups dg
             JOIN courses c ON dg.course_id = c.course_id
             LEFT JOIN batches b ON dg.batch_id = b.batch_id
             WHERE $where
             ORDER BY last_activity DESC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Groups visible to a student: their batch's groups + any all-batch groups for their batch's courses */
    public function groupsForStudentBatch(int $batchId, array $courseIds): array
    {
        if (empty($courseIds)) return [];
        $in = implode(',', array_fill(0, count($courseIds), '?'));
        $params = array_merge($courseIds, [$batchId]);
        $stmt = $this->db->prepare(
            "SELECT dg.*, c.course_code, c.course_title, b.batch_name,
                    (SELECT COUNT(*) FROM discussion_messages WHERE group_id = dg.group_id) AS message_count,
                    (SELECT created_at FROM discussion_messages WHERE group_id = dg.group_id ORDER BY created_at DESC LIMIT 1) AS last_activity
             FROM discussion_groups dg
             JOIN courses c ON dg.course_id = c.course_id
             LEFT JOIN batches b ON dg.batch_id = b.batch_id
             WHERE dg.course_id IN ($in) AND (dg.batch_id = ? OR dg.batch_id IS NULL)
             ORDER BY last_activity DESC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function allGroups(): array
    {
        $stmt = $this->db->query(
            "SELECT dg.*, c.course_code, c.course_title, b.batch_name, u.name AS creator_name,
                    (SELECT COUNT(*) FROM discussion_messages WHERE group_id = dg.group_id) AS message_count
             FROM discussion_groups dg
             JOIN courses c ON dg.course_id = c.course_id
             LEFT JOIN batches b ON dg.batch_id = b.batch_id
             JOIN users u ON dg.created_by = u.id
             ORDER BY dg.created_at DESC"
        );
        return $stmt->fetchAll();
    }

    public function deleteGroup(int $groupId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM discussion_groups WHERE group_id=?");
        return $stmt->execute([$groupId]);
    }

    public function isCreator(int $groupId, int $userId): bool
    {
        $stmt = $this->db->prepare("SELECT created_by FROM discussion_groups WHERE group_id=?");
        $stmt->execute([$groupId]);
        $row = $stmt->fetch();
        return $row && (int)$row['created_by'] === $userId;
    }

    // ── Messages (group chat feed) ──────────────────────────

    public function postMessage(int $groupId, int $userId, ?string $body, ?string $attachment): int
    {
        $stmt = $this->db->prepare("INSERT INTO discussion_messages (group_id, user_id, body, attachment) VALUES (?,?,?,?)");
        $stmt->execute([$groupId, $userId, $body, $attachment]);
        return (int) $this->db->lastInsertId();
    }

    public function getMessages(int $groupId): array
    {
        $stmt = $this->db->prepare(
            "SELECT dm.*, u.name AS author, u.role AS author_role
             FROM discussion_messages dm JOIN users u ON dm.user_id = u.id
             WHERE dm.group_id = ? ORDER BY dm.created_at ASC"
        );
        $stmt->execute([$groupId]);
        return $stmt->fetchAll();
    }

    public function deleteMessage(int $messageId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM discussion_messages WHERE message_id=?");
        return $stmt->execute([$messageId]);
    }

    public function isMessageOwner(int $messageId, int $userId): bool
    {
        $stmt = $this->db->prepare("SELECT user_id FROM discussion_messages WHERE message_id=?");
        $stmt->execute([$messageId]);
        $row = $stmt->fetch();
        return $row && (int)$row['user_id'] === $userId;
    }

    /** Verify a user (student) belongs to the batch a group is scoped to (or it's an all-batch group) */
    public function studentCanAccessGroup(array $group, ?int $studentBatchId): bool
    {
        return $group['batch_id'] === null || (int)$group['batch_id'] === (int)$studentBatchId;
    }
}
