<?php
/**
 * header.php — Shared <head> + opening <body>
 * Expects: $pageTitle (string)
 */
if (!defined('BASE_URL')) {
    require_once dirname(__DIR__) . '/config/database.php';
}

$settingsModel = new SettingsModel();
$settings      = $settingsModel->get();
$pageTitle     = $pageTitle ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>document.documentElement.classList.remove('no-js');</script>
    <title><?= htmlspecialchars($pageTitle) ?> — <?= htmlspecialchars($settings['portal_name']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/dashboard.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎓</text></svg>">
</head>
<body>
