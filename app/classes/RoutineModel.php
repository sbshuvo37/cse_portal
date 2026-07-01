<?php
/**
 * RoutineModel — routines + routine_files operations
 * CSE Department Portal
 */
class RoutineModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // ── Structured routine entries (created by CR) ──────────

    public function getByBatch(int $batchId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM routines WHERE batch_id=?
             ORDER BY FIELD(day,'Saturday','Sunday','Monday','Tuesday','Wednesday','Thursday','Friday'), start_time"
        );
        $stmt->execute([$batchId]);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM routines WHERE routine_id=?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(int $batchId, ?string $date, string $day, string $course, ?string $teacher, string $start, string $end, int $createdBy): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO routines (batch_id, date, day, course, teacher, start_time, end_time, created_by) VALUES (?,?,?,?,?,?,?,?)"
        );
        $stmt->execute([$batchId, $date, $day, $course, $teacher, $start, $end, $createdBy]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, ?string $date, string $day, string $course, ?string $teacher, string $start, string $end): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE routines SET date=?, day=?, course=?, teacher=?, start_time=?, end_time=? WHERE routine_id=?"
        );
        return $stmt->execute([$date, $day, $course, $teacher, $start, $end, $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM routines WHERE routine_id=?");
        return $stmt->execute([$id]);
    }

    // ── Admin-uploaded routine files (PDF/Image) ─────────────

    public function addFile(?int $batchId, string $path, int $uploadedBy): int
    {
        $stmt = $this->db->prepare("INSERT INTO routine_files (batch_id, file_path, uploaded_by) VALUES (?,?,?)");
        $stmt->execute([$batchId, $path, $uploadedBy]);
        return (int) $this->db->lastInsertId();
    }

    public function getFiles(?int $batchId = null): array
    {
        if ($batchId) {
            $stmt = $this->db->prepare("SELECT * FROM routine_files WHERE batch_id=? OR batch_id IS NULL ORDER BY created_at DESC");
            $stmt->execute([$batchId]);
        } else {
            $stmt = $this->db->query("SELECT rf.*, b.batch_name FROM routine_files rf LEFT JOIN batches b ON rf.batch_id=b.batch_id ORDER BY rf.created_at DESC");
        }
        return $stmt->fetchAll();
    }

    public function deleteFile(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM routine_files WHERE id=?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) {
            $del = $this->db->prepare("DELETE FROM routine_files WHERE id=?");
            $del->execute([$id]);
        }
        return $row ?: null;
    }
}
