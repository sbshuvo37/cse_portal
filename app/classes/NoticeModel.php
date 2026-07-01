<?php
/**
 * NoticeModel — notices + notice_files operations
 * CSE Department Portal
 */
class NoticeModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /** Notices visible based on target/batch (for students/teachers) */
    public function visibleTo(?int $batchId = null): array
    {
        if ($batchId) {
            $stmt = $this->db->prepare(
                "SELECT n.*, u.name AS poster, u.role AS poster_role
                 FROM notices n JOIN users u ON n.posted_by = u.id
                 WHERE n.target = 'all_students' OR (n.target='specific_batch' AND n.batch_id = ?)
                 ORDER BY n.created_at DESC"
            );
            $stmt->execute([$batchId]);
        } else {
            $stmt = $this->db->query(
                "SELECT n.*, u.name AS poster, u.role AS poster_role
                 FROM notices n JOIN users u ON n.posted_by = u.id
                 ORDER BY n.created_at DESC"
            );
        }
        return $stmt->fetchAll();
    }

    public function all(): array
    {
        $stmt = $this->db->query(
            "SELECT n.*, u.name AS poster, b.batch_name
             FROM notices n
             JOIN users u ON n.posted_by = u.id
             LEFT JOIN batches b ON n.batch_id = b.batch_id
             ORDER BY n.created_at DESC"
        );
        return $stmt->fetchAll();
    }

    public function byPoster(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT n.*, b.batch_name FROM notices n
             LEFT JOIN batches b ON n.batch_id = b.batch_id
             WHERE n.posted_by = ? ORDER BY n.created_at DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM notices WHERE notice_id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(string $title, string $desc, string $target, ?int $batchId, int $postedBy): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO notices (title, description, target, batch_id, posted_by) VALUES (?,?,?,?,?)"
        );
        $stmt->execute([$title, $desc, $target, $batchId, $postedBy]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, string $title, string $desc, string $target, ?int $batchId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE notices SET title=?, description=?, target=?, batch_id=? WHERE notice_id=?"
        );
        return $stmt->execute([$title, $desc, $target, $batchId, $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM notices WHERE notice_id=?");
        return $stmt->execute([$id]);
    }

    public function addFile(int $noticeId, string $path): bool
    {
        $stmt = $this->db->prepare("INSERT INTO notice_files (notice_id, file_path) VALUES (?,?)");
        return $stmt->execute([$noticeId, $path]);
    }

    public function getFiles(int $noticeId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM notice_files WHERE notice_id=?");
        $stmt->execute([$noticeId]);
        return $stmt->fetchAll();
    }

    public function count(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM notices")->fetchColumn();
    }

    public function isOwner(int $noticeId, int $userId): bool
    {
        $stmt = $this->db->prepare("SELECT posted_by FROM notices WHERE notice_id=?");
        $stmt->execute([$noticeId]);
        $row = $stmt->fetch();
        return $row && (int)$row['posted_by'] === $userId;
    }
}
