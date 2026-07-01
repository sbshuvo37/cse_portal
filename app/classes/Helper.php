<?php
/**
 * Helper — Common static utility functions
 * CSE Department Portal
 */
class Helper
{
    public static function sanitize(string $data): string
    {
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }

    public static function setFlash(string $type, string $message): void
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    public static function getFlash(): ?array
    {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }

    public static function redirect(string $path): void
    {
        header('Location: ' . BASE_URL . $path);
        exit();
    }

    public static function formatDate(string $datetime, string $format = 'M j, Y'): string
    {
        return date($format, strtotime($datetime));
    }

    public static function formatTime(string $time, string $format = 'g:i A'): string
    {
        return date($format, strtotime($time));
    }

    public static function timeAgo(string $datetime): string
    {
        $diff = time() - strtotime($datetime);
        if ($diff < 60) return 'Just now';
        if ($diff < 3600) return floor($diff / 60) . ' min ago';
        if ($diff < 86400) return floor($diff / 3600) . ' hr ago';
        if ($diff < 604800) return floor($diff / 86400) . ' days ago';
        return date('M j, Y', strtotime($datetime));
    }

    public static function initials(string $name): string
    {
        $parts = explode(' ', trim($name));
        if (count($parts) === 1) {
            return strtoupper(substr($parts[0], 0, 1));
        }
        return strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1));
    }

    public static function fileIcon(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $icons = [
            'pdf'  => '📄', 'jpg' => '🖼️', 'jpeg' => '🖼️', 'png' => '🖼️',
            'doc'  => '📝', 'docx' => '📝',
            'ppt'  => '📊', 'pptx' => '📊',
            'zip'  => '🗜️',
        ];
        return $icons[$ext] ?? '📎';
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrf(?string $token): bool
    {
        return isset($_SESSION['csrf_token']) && $token === $_SESSION['csrf_token'];
    }
}
