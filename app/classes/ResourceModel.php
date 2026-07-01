<?php
/**
 * ResourceModel — resources table operations (teacher resource library)
 * CSE Department Portal
 */
class ResourceModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create(int $courseId, int $teacherId, string $title, ?string $description, string $path): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO resources (course_id, teacher_id, title, description, file_path) VALUES (?,?,?,?,?)"
        );
        $stmt->execute([$courseId, $teacherId, $title, $description, $path]);
        return (int) $this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM resources WHERE resource_id=?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function byTeacher(int $teacherId): array
    {
        $stmt = $this->db->prepare(
            "SELECT r.*, c.course_code, c.course_title
             FROM resources r JOIN courses c ON r.course_id = c.course_id
             WHERE r.teacher_id = ? ORDER BY r.created_at DESC"
        );
        $stmt->execute([$teacherId]);
        return $stmt->fetchAll();
    }

    public function byCourse(int $courseId): array
    {
        $stmt = $this->db->prepare(
            "SELECT r.*, u.name AS teacher_name
             FROM resources r
             JOIN teachers t ON r.teacher_id = t.teacher_id
             JOIN users u ON t.user_id = u.id
             WHERE r.course_id = ? ORDER BY r.created_at DESC"
        );
        $stmt->execute([$courseId]);
        return $stmt->fetchAll();
    }

    /** All resources visible to a student, based on their batch's assigned courses */
    public function forBatch(int $batchId): array
    {
        $stmt = $this->db->prepare(
            "SELECT r.*, c.course_code, c.course_title, u.name AS teacher_name
             FROM resources r
             JOIN courses c ON r.course_id = c.course_id
             JOIN course_assignments ca ON ca.course_id = c.course_id AND ca.batch_id = ?
             JOIN teachers t ON r.teacher_id = t.teacher_id
             JOIN users u ON t.user_id = u.id
             ORDER BY r.created_at DESC"
        );
        $stmt->execute([$batchId]);
        return $stmt->fetchAll();
    }

    public function all(): array
    {
        $stmt = $this->db->query(
            "SELECT r.*, c.course_code, c.course_title, u.name AS teacher_name
             FROM resources r
             JOIN courses c ON r.course_id = c.course_id
             JOIN teachers t ON r.teacher_id = t.teacher_id
             JOIN users u ON t.user_id = u.id
             ORDER BY r.created_at DESC"
        );
        return $stmt->fetchAll();
    }

    public function update(int $id, string $title, ?string $description): bool
    {
        $stmt = $this->db->prepare("UPDATE resources SET title=?, description=? WHERE resource_id=?");
        return $stmt->execute([$title, $description, $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM resources WHERE resource_id=?");
        return $stmt->execute([$id]);
    }

    public function isOwner(int $resourceId, int $teacherId): bool
    {
        $stmt = $this->db->prepare("SELECT teacher_id FROM resources WHERE resource_id=?");
        $stmt->execute([$resourceId]);
        $row = $stmt->fetch();
        return $row && (int)$row['teacher_id'] === $teacherId;
    }

    public function search(string $term): array
    {
        $like = "%$term%";
        $stmt = $this->db->prepare(
            "SELECT r.*, c.course_code, c.course_title
             FROM resources r JOIN courses c ON r.course_id = c.course_id
             WHERE r.title LIKE ? OR c.course_title LIKE ?
             ORDER BY r.created_at DESC"
        );
        $stmt->execute([$like, $like]);
        return $stmt->fetchAll();
    }
}
