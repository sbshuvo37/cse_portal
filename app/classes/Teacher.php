<?php
/**
 * Teacher — Teacher-table operations
 * CSE Department Portal
 */
class Teacher
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create(int $userId, ?string $designation, ?string $phone): int
    {
        $stmt = $this->db->prepare("INSERT INTO teachers (user_id, designation, phone) VALUES (?,?,?)");
        $stmt->execute([$userId, $designation, $phone]);
        return (int) $this->db->lastInsertId();
    }

    public function findByUserId(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT t.*, u.name, u.email, u.status, u.profile_photo, u.created_at
             FROM teachers t JOIN users u ON t.user_id = u.id WHERE t.user_id = ?"
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findById(int $teacherId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT t.*, u.name, u.email, u.status, u.profile_photo
             FROM teachers t JOIN users u ON t.user_id = u.id WHERE t.teacher_id = ?"
        );
        $stmt->execute([$teacherId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function all(): array
    {
        $stmt = $this->db->query(
            "SELECT t.*, u.name, u.email, u.status, u.created_at
             FROM teachers t JOIN users u ON t.user_id = u.id
             WHERE u.status != 'pending'
             ORDER BY t.teacher_id"
        );
        return $stmt->fetchAll();
    }

    public function update(int $teacherId, ?string $designation, ?string $phone): bool
    {
        $stmt = $this->db->prepare("UPDATE teachers SET designation=?, phone=? WHERE teacher_id=?");
        return $stmt->execute([$designation, $phone, $teacherId]);
    }

    public function search(string $term): array
    {
        $like = "%$term%";
        $stmt = $this->db->prepare(
            "SELECT t.*, u.name, u.email, u.status
             FROM teachers t JOIN users u ON t.user_id = u.id
             WHERE u.status != 'pending' AND (u.name LIKE ? OR t.designation LIKE ?)
             ORDER BY u.name"
        );
        $stmt->execute([$like, $like]);
        return $stmt->fetchAll();
    }

    /** Get courses assigned to a teacher (via course_assignments) */
    public function getAssignedCourses(int $teacherId): array
    {
        $stmt = $this->db->prepare(
            "SELECT ca.id AS assignment_id, c.course_id, c.course_code, c.course_title, c.credit, c.semester,
                    b.batch_id, b.batch_name, b.session
             FROM course_assignments ca
             JOIN courses c ON ca.course_id = c.course_id
             JOIN batches b ON ca.batch_id = b.batch_id
             WHERE ca.teacher_id = ?
             ORDER BY b.batch_name, c.course_code"
        );
        $stmt->execute([$teacherId]);
        return $stmt->fetchAll();
    }

    public function count(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM teachers t JOIN users u ON t.user_id=u.id WHERE u.status != 'pending'");
        return (int) $stmt->fetchColumn();
    }
}
