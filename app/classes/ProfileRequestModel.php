<?php
/**
 * ProfileRequestModel — profile_requests operations
 * (Profile info changes require admin approval; password changes do not)
 * CSE Department Portal
 */
class ProfileRequestModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create(int $userId, array $requestedData): int
    {
        $stmt = $this->db->prepare("INSERT INTO profile_requests (user_id, requested_data, status) VALUES (?,?,'pending')");
        $stmt->execute([$userId, json_encode($requestedData)]);
        return (int) $this->db->lastInsertId();
    }

    public function hasPending(int $userId): bool
    {
        $stmt = $this->db->prepare("SELECT id FROM profile_requests WHERE user_id=? AND status='pending'");
        $stmt->execute([$userId]);
        return (bool) $stmt->fetch();
    }

    public function allPending(): array
    {
        $stmt = $this->db->query(
            "SELECT pr.*, u.name, u.email, u.role
             FROM profile_requests pr JOIN users u ON pr.user_id = u.id
             WHERE pr.status = 'pending' ORDER BY pr.created_at ASC"
        );
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT pr.*, u.name, u.email, u.role FROM profile_requests pr JOIN users u ON pr.user_id = u.id WHERE pr.id=?"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function approve(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE profile_requests SET status='approved', reviewed_at=NOW() WHERE id=?");
        return $stmt->execute([$id]);
    }

    public function reject(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE profile_requests SET status='rejected', reviewed_at=NOW() WHERE id=?");
        return $stmt->execute([$id]);
    }

    public function countPending(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM profile_requests WHERE status='pending'")->fetchColumn();
    }
}
