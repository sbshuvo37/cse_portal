<?php
/**
 * Auth — Authentication, session, and role-guard logic
 * CSE Department Portal
 */
class Auth
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Attempt login. Returns ['success'=>bool, 'message'=>string]
     */
    public function login(string $email, string $password): array
    {
        $stmt = $this->db->prepare("SELECT id, name, email, password, role, status FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'message' => 'No account found with this email address.'];
        }
        if ($user['status'] === 'pending') {
            return ['success' => false, 'message' => 'Your registration is pending admin approval.'];
        }
        if ($user['status'] === 'rejected') {
            return ['success' => false, 'message' => 'Your registration request was rejected. Please contact the department.'];
        }
        if ($user['status'] === 'inactive') {
            return ['success' => false, 'message' => 'Your account has been deactivated. Please contact the administrator.'];
        }
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Incorrect password. Please try again.'];
        }

        session_regenerate_id(true);
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['email']     = $user['email'];
        $_SESSION['role']      = $user['role'];

        return ['success' => true, 'message' => 'Login successful.'];
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie('PHPSESSID', '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    public static function requireLogin(): void
    {
        if (!self::isLoggedIn()) {
            header('Location: ' . BASE_URL . 'login.php');
            exit();
        }
    }

    public static function requireRole(string $role): void
    {
        self::requireLogin();
        if ($_SESSION['role'] !== $role) {
            header('Location: ' . BASE_URL . 'login.php?error=unauthorized');
            exit();
        }
    }

    public static function requireRoles(array $roles): void
    {
        self::requireLogin();
        if (!in_array($_SESSION['role'], $roles, true)) {
            header('Location: ' . BASE_URL . 'login.php?error=unauthorized');
            exit();
        }
    }

    public static function redirectByRole(): void
    {
        if (!self::isLoggedIn()) return;
        $map = [
            'admin'   => BASE_URL . 'admin/dashboard.php',
            'teacher' => BASE_URL . 'teacher/dashboard.php',
            'student' => BASE_URL . 'student/dashboard.php',
        ];
        $role = $_SESSION['role'];
        if (isset($map[$role])) {
            header('Location: ' . $map[$role]);
            exit();
        }
    }

    public static function userId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function role(): ?string
    {
        return $_SESSION['role'] ?? null;
    }

    public static function userName(): ?string
    {
        return $_SESSION['user_name'] ?? null;
    }

    /**
     * Create a password reset token for a given email.
     * Returns the token string, or null if email not found.
     */
    public function createResetToken(string $email): ?string
    {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if (!$user) return null;

        $token   = bin2hex(random_bytes(32));
        $expiry  = date('Y-m-d H:i:s', time() + 3600); // 1 hour

        $stmt = $this->db->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?");
        $stmt->execute([$token, $expiry, $user['id']]);

        return $token;
    }

    public function validateResetToken(string $token): ?int
    {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_token_expiry > NOW()");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        return $user ? (int)$user['id'] : null;
    }

    public function resetPassword(int $userId, string $newPassword): bool
    {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?");
        return $stmt->execute([$hash, $userId]);
    }
}
