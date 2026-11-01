<?php
// includes/auth.php - session + login/role checks

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function current_user(): ?array
{
    if (!empty($_SESSION['user'])) {
        return $_SESSION['user'];
    }
    return null;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        header('Location: ../index.php');
        exit;
    }
    return $user;
}

function require_role(string $role): array
{
    $user = require_login();
    if (($user['role'] ?? '') !== $role) {
        http_response_code(403);
        exit('Access denied.');
    }
    return $user;
}
