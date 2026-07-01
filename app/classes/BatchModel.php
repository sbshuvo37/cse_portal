<?php
/**
 * Batch — batches table operations
 * CSE Department Portal
 */
class BatchModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function all(): array
    {
        $stmt = $this->db->query("SELECT * FROM batches ORDER BY batch_name DESC");
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM batches WHERE batch_id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(string $name, string $session): int
    {
        $stmt = $this->db->prepare("INSERT INTO batches (batch_name, session) VALUES (?,?)");
        $stmt->execute([$name, $session]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, string $name, string $session): bool
    {
        $stmt = $this->db->prepare("UPDATE batches SET batch_name=?, session=? WHERE batch_id=?");
        return $stmt->execute([$name, $session, $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM batches WHERE batch_id=?");
        return $stmt->execute([$id]);
    }

    public function count(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM batches")->fetchColumn();
    }
}
