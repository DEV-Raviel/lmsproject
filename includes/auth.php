<?php
// includes/auth.php - secure session and role checks

require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    $isHttps = !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

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
        $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
        $applicationRoot = rtrim(dirname(dirname($scriptName)), '/');
        header('Location: ' . ($applicationRoot !== '' ? $applicationRoot : '') . '/index.php');
        exit;
    }

    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $accountStmt = Database::connect()->prepare(
        'SELECT id, user_code, full_name, email, role, must_change_password
         FROM users WHERE id = ?'
    );
    $accountStmt->execute([(int) ($user['id'] ?? 0)]);
    $account = $accountStmt->fetch();
    if (!$account) {
        $_SESSION = [];
        session_regenerate_id(true);
        $applicationRoot = rtrim(dirname(dirname($scriptName)), '/');
        header('Location: ' . ($applicationRoot !== '' ? $applicationRoot : '') . '/index.php');
        exit;
    }

    $user = [
        'id' => (int) $account['id'],
        'user_code' => $account['user_code'],
        'name' => $account['full_name'],
        'email' => $account['email'],
        'role' => $account['role'],
        'must_change_password' => (bool) $account['must_change_password'],
    ];
    $_SESSION['user'] = $user;

    $allowedPasswordChangeRoutes = ['change-password.php', 'change_password_action.php', 'account.php', 'account_actions.php'];
    if (!empty($user['must_change_password'])
        && !in_array(basename($scriptName), $allowedPasswordChangeRoutes, true)) {
        $applicationRoot = rtrim(dirname(dirname($scriptName)), '/');
        header('Location: ' . ($applicationRoot !== '' ? $applicationRoot : '') . '/change-password.php');
        exit;
    }

    return $user;
}

function require_role(string $role): array
{
    return requireRole($role);
}

function requireRole(string $role): array
{
    $user = require_login();
    if (($user['role'] ?? '') !== $role) {
        http_response_code(403);
        exit('Access denied.');
    }
    return $user;
}

function require_any_role(array $roles): array
{
    $user = require_login();
    if (!in_array($user['role'] ?? '', $roles, true)) {
        http_response_code(403);
        exit('Access denied.');
    }
    return $user;
}
