<?php
// includes/header.php - Tick-Hosting style global header layout
$pageTitle = $pageTitle ?? 'Dashboard';
$basePath  = $basePath ?? '../';
$user      = $user ?? current_user();
$avatarInitial = strtoupper(substr((string) ($user['name'] ?? 'U'), 0, 1));
$profileHref = match ($user['role'] ?? '') {
    'admin', 'dean' => $basePath . 'admin/dashboard.php',
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
    <link rel="stylesheet" href="<?= e($basePath) ?>assets/css/style.css">
</head>
<body>

<header class="topbar">
    <div class="brand-area">
        <h1>e-GURO</h1>
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
                <li><a href="<?= e($profileHref) ?>">My Profile</a></li>
                <?php if (($user['role'] ?? '') === 'student'): ?>
                    <li><a href="<?= e($basePath) ?>student/notifications.php">Notifications</a></li>
                <?php endif; ?>
                <li><hr style="border: 0; border-top: 1px solid var(--border); margin: 0.25rem 0;"></li>
                <li><a href="<?= e($basePath) ?>logout.php" style="color: #ef4444;">Sign Out</a></li>
            </ul>
        </details>
    </div>
</header>

<div class="layout-container">