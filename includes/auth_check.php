<?php
/**
 * auth_check.php — Bootstraps config, DB, and session.
 * Include this at the very top of every protected page, then call
 * Auth::requireRole() or Auth::requireRoles() as needed.
 *
 * Usage:
 *   require_once '../config/database.php';
 *   require_once '../includes/auth_check.php';
 *   Auth::requireRole('admin');
 */

// config/database.php already loads config.php and the autoloader.
// This file just ensures classes are available even if included directly.
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/database.php';
}
