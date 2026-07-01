<?php
/**
 * User — Core user-table operations (shared across roles)
 * CSE Department Portal
 */
class User
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return (bool) $stmt->fetch();
    }

    /**
     * Create a base user record (used during registration).
     * Returns the new user id.
     */
    public function create(string $name, string $email, string $password, string $role, string $status = 'pending', ?string $photo = null): int
    {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare(
            "INSERT INTO users (name, email, password, role, status, profile_photo) VALUES (?,?,?,?,?,?)"
        );
        $stmt->execute([$name, $email, $hash, $role, $status, $photo]);
        return (int) $this->db->lastInsertId();
    }

    public function updateStatus(int $userId, string $status): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $userId]);
    }

    public function updateBasicInfo(int $userId, string $name): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET name = ? WHERE id = ?");
        return $stmt->execute([$name, $userId]);
    }

    public function updatePassword(int $userId, string $newPassword): bool
    {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
        return $stmt->execute([$hash, $userId]);
    }

    public function updatePhoto(int $userId, string $photoPath): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
        return $stmt->execute([$photoPath, $userId]);
    }

    public function verifyPassword(int $userId, string $password): bool
    {
        $stmt = $this->db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row && password_verify($password, $row['password']);
    }

    public function delete(int $userId): bool
    {
        // We use soft-delete (status) elsewhere; hard delete only for cleanup tools.
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$userId]);
    }

    public function getPendingUsers(): array
    {
        $stmt = $this->db->query("SELECT * FROM users WHERE status = 'pending' ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }

    public function countByRole(string $role): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE role = ? AND status != 'pending'");
        $stmt->execute([$role]);
        return (int) $stmt->fetchColumn();
    }

    public function searchUsers(string $term, string $role = null): array
    {
        $like = "%$term%";
        if ($role) {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE role = ? AND (name LIKE ? OR email LIKE ?) ORDER BY name");
            $stmt->execute([$role, $like, $like]);
        } else {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE name LIKE ? OR email LIKE ? ORDER BY name");
            $stmt->execute([$like, $like]);
        }
        return $stmt->fetchAll();
    }
}
