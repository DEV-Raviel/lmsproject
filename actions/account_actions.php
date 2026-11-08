<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';

$user = require_login();
$accountPath = match ($user['role'] ?? '') {
    'admin', 'dean' => '../admin/account.php',
    'teacher' => '../teacher/account.php',
    'student' => '../student/account.php',
    default => null,
};
if ($accountPath === null) {
    http_response_code(403);
    exit('Access denied.');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

$redirectWithMessage = static function (string $message, bool $isError = false) use ($accountPath): never {
    $_SESSION['account_flash'] = ['message' => $message, 'error' => $isError];
    header('Location: ' . $accountPath);
    exit;
};

if (!csrf_token_is_valid($_POST['csrf'] ?? null)) {
    $redirectWithMessage('Your session expired. Please try again.', true);
}

$currentPassword = is_string($_POST['current_password'] ?? null) ? $_POST['current_password'] : '';
$newPassword = is_string($_POST['new_password'] ?? null) ? $_POST['new_password'] : '';
$confirmPassword = is_string($_POST['confirm_password'] ?? null) ? $_POST['confirm_password'] : '';

if ($currentPassword === '' || strlen($newPassword) < 8) {
    $redirectWithMessage('Enter your current password and a new password of at least 8 characters.', true);
}
if ($newPassword !== $confirmPassword) {
    $redirectWithMessage('The new passwords do not match.', true);
}
if ($currentPassword === $newPassword) {
    $redirectWithMessage('Choose a new password that is different from your current password.', true);
}

$pdo = Database::connect();
$passwordStmt = $pdo->prepare('SELECT password FROM users WHERE id = ? AND role = ?');
$passwordStmt->execute([(int) $user['id'], $user['role']]);
$storedPassword = $passwordStmt->fetchColumn();
if (!is_string($storedPassword) || !password_verify($currentPassword, $storedPassword)) {
    $redirectWithMessage('Your current password is incorrect.', true);
}

try {
    $update = $pdo->prepare(
        'UPDATE users SET password = ?, must_change_password = 0 WHERE id = ? AND role = ?'
    );
    $update->execute([
        password_hash($newPassword, PASSWORD_DEFAULT),
        (int) $user['id'],
        $user['role'],
    ]);
    $_SESSION['user']['must_change_password'] = false;
    $redirectWithMessage('Your password has been updated.');
} catch (PDOException $exception) {
    error_log('Account password update failed: ' . $exception->getMessage());
    $redirectWithMessage('Your password could not be changed. Please try again.', true);
}
