<?php
// includes/header.php - Tick-Hosting style global header layout
$pageTitle = $pageTitle ?? 'Dashboard';
$basePath  = $basePath ?? '../';
$user      = $user ?? current_user();
$avatarInitial = strtoupper(substr((string) ($user['name'] ?? 'U'), 0, 1));
$profileLabel = match ($user['role'] ?? '') {
    'admin' => 'User Management',
    'dean' => 'Announcements',
    default => 'My Profile',
};
$profileHref = match ($user['role'] ?? '') {
    'admin' => $basePath . 'admin/dashboard.php',
    'dean' => $basePath . 'admin/manage_announcements.php',
    'teacher' => $basePath . 'teacher/teacher-profile.php',
    default => $basePath . 'student/account.php',
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - e-GURO LMS</title>
    <link rel="stylesheet" href="<?= e($basePath) ?>assets/css/style.css?v=<?= (int) filemtime(__DIR__ . '/../assets/css/style.css') ?>">
</head>
<body>

<a class="skip-link" href="#main-content">Skip to main content</a>
<header class="topbar">
    <div class="brand-area">
        <h1><a class="brand-home" href="<?= e($basePath) ?>index.php">e-GURO</a></h1>
        <span class="brand-badge"><?= e(strtoupper($user['role'] ?? 'LMS')) ?></span>
    </div>
    <div class="header-right">
        <span><?= e(date('D, M j, Y')) ?></span>
        <details class="profile-dropdown">
            <summary>
                <span class="user-avatar" aria-hidden="true"><?= e($avatarInitial) ?></span>
                <span><?= e($user['name'] ?? 'User') ?></span>
            </summary>
            <ul>
                <li><a href="<?= e($profileHref) ?>"><?= e($profileLabel) ?></a></li>
                <?php if (($user['role'] ?? '') === 'student'): ?>
                    <li><a href="<?= e($basePath) ?>student/notifications.php">Notifications</a></li>
                <?php endif; ?>
                <li><hr class="profile-divider"></li>
                <li><a class="signout-link" href="<?= e($basePath) ?>logout.php">Sign out</a></li>
            </ul>
        </details>
    </div>
</header>

<div class="layout-container">