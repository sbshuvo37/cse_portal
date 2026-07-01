<?php
/**
 * CourseModel — courses + course_assignments operations
 * CSE Department Portal
 */
class CourseModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function all(): array
    {
        $stmt = $this->db->query("SELECT * FROM courses ORDER BY semester, course_code");
        return $stmt->fetchAll();
    }

    public function allActive(): array
    {
        $stmt = $this->db->query("SELECT * FROM courses WHERE status='active' ORDER BY semester, course_code");
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM courses WHERE course_id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(string $code, string $title, float $credit, string $semester): int
    {
        $stmt = $this->db->prepare("INSERT INTO courses (course_code, course_title, credit, semester) VALUES (?,?,?,?)");
        $stmt->execute([$code, $title, $credit, $semester]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, string $code, string $title, float $credit, string $semester): bool
    {
        $stmt = $this->db->prepare("UPDATE courses SET course_code=?, course_title=?, credit=?, semester=? WHERE course_id=?");
        return $stmt->execute([$code, $title, $credit, $semester, $id]);
    }

    public function setStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare("UPDATE courses SET status=? WHERE course_id=?");
        return $stmt->execute([$status, $id]);
    }

    public function count(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM courses")->fetchColumn();
    }

    // ── Course Assignments ──────────────────────────────────

    public function assign(int $courseId, int $batchId, int $teacherId): array
    {
        // Check if already assigned (locked once set)
        $stmt = $this->db->prepare("SELECT id FROM course_assignments WHERE course_id=? AND batch_id=?");
        $stmt->execute([$courseId, $batchId]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'This course is already assigned for this batch and cannot be changed.'];
        }
        $stmt = $this->db->prepare("INSERT INTO course_assignments (course_id, batch_id, teacher_id) VALUES (?,?,?)");
        $stmt->execute([$courseId, $batchId, $teacherId]);
        return ['success' => true, 'message' => 'Course assigned successfully.'];
    }

    public function getAllAssignments(): array
    {
        $stmt = $this->db->query(
            "SELECT ca.*, c.course_code, c.course_title, b.batch_name, b.session, u.name AS teacher_name
             FROM course_assignments ca
             JOIN courses c  ON ca.course_id  = c.course_id
             JOIN batches b  ON ca.batch_id   = b.batch_id
             JOIN teachers t ON ca.teacher_id = t.teacher_id
             JOIN users u    ON t.user_id     = u.id
             ORDER BY b.batch_name, c.course_code"
        );
        return $stmt->fetchAll();
    }

    public function deleteAssignment(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM course_assignments WHERE id=?");
        return $stmt->execute([$id]);
    }

    public function findAssignmentById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT ca.*, c.course_code, c.course_title, b.batch_name
             FROM course_assignments ca
             JOIN courses c ON ca.course_id = c.course_id
             JOIN batches b ON ca.batch_id  = b.batch_id
             WHERE ca.id = ?"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Admin-only correction tool: update the teacher (and optionally batch) on
     * an existing assignment. Course+Batch pair must remain unique.
     */
    public function updateAssignment(int $id, int $courseId, int $batchId, int $teacherId): array
    {
        $stmt = $this->db->prepare("SELECT id FROM course_assignments WHERE course_id=? AND batch_id=? AND id != ?");
        $stmt->execute([$courseId, $batchId, $id]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Another assignment already exists for this course and batch.'];
        }
        $stmt = $this->db->prepare("UPDATE course_assignments SET course_id=?, batch_id=?, teacher_id=? WHERE id=?");
        $stmt->execute([$courseId, $batchId, $teacherId, $id]);
        return ['success' => true, 'message' => 'Assignment updated successfully.'];
    }

    public function getAssignmentForCourseBatch(int $courseId, int $batchId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT ca.*, u.name AS teacher_name FROM course_assignments ca
             JOIN teachers t ON ca.teacher_id = t.teacher_id
             JOIN users u ON t.user_id = u.id
             WHERE ca.course_id=? AND ca.batch_id=?"
        );
        $stmt->execute([$courseId, $batchId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getCoursesForBatch(int $batchId): array
    {
        $stmt = $this->db->prepare(
            "SELECT c.*, u.name AS teacher_name, t.teacher_id
             FROM course_assignments ca
             JOIN courses c ON ca.course_id = c.course_id
             JOIN teachers t ON ca.teacher_id = t.teacher_id
             JOIN users u ON t.user_id = u.id
             WHERE ca.batch_id = ?
             ORDER BY c.course_code"
        );
        $stmt->execute([$batchId]);
        return $stmt->fetchAll();
    }
}
