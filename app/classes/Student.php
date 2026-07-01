<?php
/**
 * Student — Student-table operations
 * CSE Department Portal
 */
class Student
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create(int $userId, string $roll, string $regNo, ?int $batchId, ?string $session, ?string $phone): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO students (user_id, roll, registration_no, batch_id, session, phone) VALUES (?,?,?,?,?,?)"
        );
        $stmt->execute([$userId, $roll, $regNo, $batchId, $session, $phone]);
        return (int) $this->db->lastInsertId();
    }

    public function findByUserId(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT s.*, u.name, u.email, u.status, u.profile_photo, u.created_at, b.batch_name
             FROM students s
             JOIN users u ON s.user_id = u.id
             LEFT JOIN batches b ON s.batch_id = b.batch_id
             WHERE s.user_id = ?"
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findById(int $studentId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT s.*, u.name, u.email, u.status, u.profile_photo, b.batch_name
             FROM students s
             JOIN users u ON s.user_id = u.id
             LEFT JOIN batches b ON s.batch_id = b.batch_id
             WHERE s.student_id = ?"
        );
        $stmt->execute([$studentId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function all(): array
    {
        $stmt = $this->db->query(
            "SELECT s.*, u.name, u.email, u.status, u.created_at, b.batch_name
             FROM students s
             JOIN users u ON s.user_id = u.id
             LEFT JOIN batches b ON s.batch_id = b.batch_id
             WHERE u.status != 'pending'
             ORDER BY s.student_id DESC"
        );
        return $stmt->fetchAll();
    }

    public function byBatch(int $batchId): array
    {
        $stmt = $this->db->prepare(
            "SELECT s.*, u.name, u.email, u.status
             FROM students s
             JOIN users u ON s.user_id = u.id
             WHERE s.batch_id = ? AND u.status = 'active'
             ORDER BY u.name"
        );
        $stmt->execute([$batchId]);
        return $stmt->fetchAll();
    }

    public function update(int $studentId, string $roll, string $regNo, ?int $batchId, ?string $session, ?string $phone): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE students SET roll=?, registration_no=?, batch_id=?, session=?, phone=? WHERE student_id=?"
        );
        return $stmt->execute([$roll, $regNo, $batchId, $session, $phone, $studentId]);
    }

    public function updatePhone(int $userId, string $phone): bool
    {
        $stmt = $this->db->prepare("UPDATE students SET phone=? WHERE user_id=?");
        return $stmt->execute([$phone, $userId]);
    }

    public function search(string $term): array
    {
        $like = "%$term%";
        $stmt = $this->db->prepare(
            "SELECT s.*, u.name, u.email, u.status, b.batch_name
             FROM students s
             JOIN users u ON s.user_id = u.id
             LEFT JOIN batches b ON s.batch_id = b.batch_id
             WHERE u.status != 'pending' AND (u.name LIKE ? OR s.roll LIKE ? OR s.registration_no LIKE ?)
             ORDER BY u.name"
        );
        $stmt->execute([$like, $like, $like]);
        return $stmt->fetchAll();
    }

    public function setCr(int $studentId, bool $isCr): bool
    {
        $stmt = $this->db->prepare("UPDATE students SET is_cr=? WHERE student_id=?");
        return $stmt->execute([$isCr ? 1 : 0, $studentId]);
    }

    public function isCr(int $userId): bool
    {
        $stmt = $this->db->prepare("SELECT is_cr FROM students WHERE user_id=?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row && (int)$row['is_cr'] === 1;
    }

    public function getCrOfBatch(int $batchId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT s.*, u.name FROM students s JOIN users u ON s.user_id=u.id WHERE s.batch_id=? AND s.is_cr=1 LIMIT 1"
        );
        $stmt->execute([$batchId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function count(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM students s JOIN users u ON s.user_id=u.id WHERE u.status != 'pending'");
        return (int) $stmt->fetchColumn();
    }
}
