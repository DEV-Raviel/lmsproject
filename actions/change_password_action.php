<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$user = require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}
if (empty($user['must_change_password'])) {
    header('Location: ../index.php');
    exit;
}
if (!csrf_token_is_valid($_POST['csrf'] ?? null)) {
    $_SESSION['password_change_error'] = 'Your session expired. Reload the page and try again.';
    header('Location: ../change-password.php');
    exit;
}

$password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
$confirmation = is_string($_POST['password_confirmation'] ?? null) ? $_POST['password_confirmation'] : '';
if (strlen($password) < 12 || strlen($password) > 200) {
    $_SESSION['password_change_error'] = 'Choose a password between 12 and 200 characters.';
    header('Location: ../change-password.php');
    exit;
}
if (!hash_equals($password, $confirmation)) {
    $_SESSION['password_change_error'] = 'The password confirmation does not match.';
    header('Location: ../change-password.php');
    exit;
}

$pdo = Database::connect();
try {
    $pdo->beginTransaction();
    $accountStmt = $pdo->prepare(
        'SELECT password, must_change_password FROM users WHERE id = ? FOR UPDATE'
    );
    $accountStmt->execute([(int) $user['id']]);
    $account = $accountStmt->fetch();
    if (!$account || !(bool) $account['must_change_password']) {
        $pdo->rollBack();
        $_SESSION['password_change_error'] = 'This account no longer requires a password change. Sign in again.';
        header('Location: ../index.php');
        exit;
    }
    if (password_verify($password, (string) $account['password'])) {
        $pdo->rollBack();
        $_SESSION['password_change_error'] = 'Choose a password different from your temporary password.';
        header('Location: ../change-password.php');
        exit;
    }

    $update = $pdo->prepare(
        'UPDATE users SET password = ?, must_change_password = 0 WHERE id = ?'
    );
    $update->execute([password_hash($password, PASSWORD_DEFAULT), (int) $user['id']]);
    $invalidateTokens = $pdo->prepare(
        'UPDATE password_reset_tokens SET used_at = NOW()
         WHERE user_id = ? AND used_at IS NULL'
    );
    $invalidateTokens->execute([(int) $user['id']]);
    $pdo->commit();
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Forced password change failed: ' . $exception->getMessage());
    $_SESSION['password_change_error'] = 'The password could not be changed. Please try again.';
    header('Location: ../change-password.php');
    exit;
}

$_SESSION = [];
session_regenerate_id(true);
$_SESSION['login_notice'] = 'Your password was changed. Sign in using your new password.';
header('Location: ../index.php');
exit;
