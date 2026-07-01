<?php
/**
 * ResultModel — results + result_files operations
 * CSE Department Portal
 */
class ResultModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // ── Teacher-entered marks ────────────────────────────────

    public function upsert(int $studentId, int $courseId, float $attendance, float $mid1, float $mid2, float $mid3, int $enteredBy): array
    {
        $total = $attendance + $mid1 + $mid2 + $mid3;

        $stmt = $this->db->prepare("SELECT result_id FROM results WHERE student_id=? AND course_id=?");
        $stmt->execute([$studentId, $courseId]);
        $existing = $stmt->fetch();

        if ($existing) {
            $upd = $this->db->prepare(
                "UPDATE results SET attendance=?, mid1=?, mid2=?, mid3=?, total=?, entered_by=? WHERE result_id=?"
            );
            $upd->execute([$attendance, $mid1, $mid2, $mid3, $total, $enteredBy, $existing['result_id']]);
            return ['success' => true, 'message' => 'Marks updated successfully.', 'id' => $existing['result_id']];
        }

        $ins = $this->db->prepare(
            "INSERT INTO results (student_id, course_id, attendance, mid1, mid2, mid3, total, entered_by) VALUES (?,?,?,?,?,?,?,?)"
        );
        $ins->execute([$studentId, $courseId, $attendance, $mid1, $mid2, $mid3, $total, $enteredBy]);
        return ['success' => true, 'message' => 'Marks added successfully.', 'id' => (int) $this->db->lastInsertId()];
    }

    public function findById(int $resultId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM results WHERE result_id=?");
        $stmt->execute([$resultId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function delete(int $resultId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM results WHERE result_id=?");
        return $stmt->execute([$resultId]);
    }

    public function byCourse(int $courseId): array
    {
        $stmt = $this->db->prepare(
            "SELECT r.*, u.name AS student_name, s.roll
             FROM results r
             JOIN students s ON r.student_id = s.student_id
             JOIN users u ON s.user_id = u.id
             WHERE r.course_id = ?
             ORDER BY u.name"
        );
        $stmt->execute([$courseId]);
        return $stmt->fetchAll();
    }

    public function byStudent(int $studentId): array
    {
        $stmt = $this->db->prepare(
            "SELECT r.*, c.course_code, c.course_title, c.credit, c.semester
             FROM results r JOIN courses c ON r.course_id = c.course_id
             WHERE r.student_id = ?
             ORDER BY c.semester, c.course_code"
        );
        $stmt->execute([$studentId]);
        return $stmt->fetchAll();
    }

    public function getByCourseAndStudent(int $courseId, int $studentId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM results WHERE course_id=? AND student_id=?");
        $stmt->execute([$courseId, $studentId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function isOwner(int $resultId, int $userId): bool
    {
        $stmt = $this->db->prepare("SELECT entered_by FROM results WHERE result_id=?");
        $stmt->execute([$resultId]);
        $row = $stmt->fetch();
        return $row && (int)$row['entered_by'] === $userId;
    }

    // ── Result files (Admin final result / Teacher course result file) ──

    public function addFile(?int $batchId, ?int $courseId, int $uploadedBy, string $path, ?string $title): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO result_files (batch_id, course_id, uploaded_by, file_path, title) VALUES (?,?,?,?,?)"
        );
        $stmt->execute([$batchId, $courseId, $uploadedBy, $path, $title]);
        return (int) $this->db->lastInsertId();
    }

    public function getFilesByBatch(int $batchId): array
    {
        $stmt = $this->db->prepare(
            "SELECT rf.*, u.name AS uploader, c.course_code, c.course_title
             FROM result_files rf
             JOIN users u ON rf.uploaded_by = u.id
             LEFT JOIN courses c ON rf.course_id = c.course_id
             WHERE rf.batch_id = ?
             ORDER BY rf.created_at DESC"
        );
        $stmt->execute([$batchId]);
        return $stmt->fetchAll();
    }
    public function getFilesByTeacher(int $teacherUserId): array
    {
        $stmt = $this->db->prepare(
            "SELECT rf.*, b.batch_name, c.course_code, c.course_title
             FROM result_files rf
             LEFT JOIN batches b ON rf.batch_id = b.batch_id
             LEFT JOIN courses c ON rf.course_id = c.course_id
             WHERE rf.uploaded_by = ?
             ORDER BY rf.created_at DESC"
        );
        $stmt->execute([$teacherUserId]);
        return $stmt->fetchAll();
    }

    public function findFileById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM result_files WHERE id=?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function isFileOwner(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare("SELECT uploaded_by FROM result_files WHERE id=?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row && (int)$row['uploaded_by'] === $userId;
    }

    public function updateFileTitle(int $id, string $title): bool
    {
        $stmt = $this->db->prepare("UPDATE result_files SET title=? WHERE id=?");
        return $stmt->execute([$title, $id]);
    }

    public function deleteFile(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM result_files WHERE id=?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) {
            $del = $this->db->prepare("DELETE FROM result_files WHERE id=?");
            $del->execute([$id]);
        }
        return $row ?: null;
    }
}
