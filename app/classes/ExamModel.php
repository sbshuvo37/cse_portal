<?php
/**
 * ExamModel — exam_schedules table operations
 * CSE Department Portal
 */
class ExamModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function all(): array
    {
        $stmt = $this->db->query(
            "SELECT e.*, b.batch_name, c.course_code, c.course_title
             FROM exam_schedules e
             JOIN batches b ON e.batch_id = b.batch_id
             JOIN courses c ON e.course_id = c.course_id
             ORDER BY e.exam_date, e.exam_time"
        );
        return $stmt->fetchAll();
    }

    public function byBatch(int $batchId): array
    {
        $stmt = $this->db->prepare(
            "SELECT e.*, c.course_code, c.course_title, c.credit
             FROM exam_schedules e JOIN courses c ON e.course_id = c.course_id
             WHERE e.batch_id = ? ORDER BY e.exam_date, e.exam_time"
        );
        $stmt->execute([$batchId]);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM exam_schedules WHERE exam_id=?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(int $batchId, int $courseId, string $date, string $time): int
    {
        $stmt = $this->db->prepare("INSERT INTO exam_schedules (batch_id, course_id, exam_date, exam_time) VALUES (?,?,?,?)");
        $stmt->execute([$batchId, $courseId, $date, $time]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, int $batchId, int $courseId, string $date, string $time): bool
    {
        $stmt = $this->db->prepare("UPDATE exam_schedules SET batch_id=?, course_id=?, exam_date=?, exam_time=? WHERE exam_id=?");
        return $stmt->execute([$batchId, $courseId, $date, $time, $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM exam_schedules WHERE exam_id=?");
        return $stmt->execute([$id]);
    }
}
