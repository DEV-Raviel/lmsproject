<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

$user = require_role('admin');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}

$redirect = '../admin/recover_accounts.php';
$respond = static function (string $message, bool $isError = false, ?string $resetLink = null, ?string $accountName = null) use ($redirect): never {
    $_SESSION['recovery_flash'] = [
        'message' => $message,
        'error' => $isError,
        'reset_link' => $resetLink,
        'account_name' => $accountName,
    ];
    header('Location: ' . $redirect);
    exit;
};

$csrf = $_POST['csrf'] ?? null;
if (!is_string($csrf) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $csrf)) {
    $respond('Your session expired. Please try again.', true);
}

$accountId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
if (!$accountId || $accountId < 1 || $accountId === (int) $user['id']) {
    $respond('Select another valid account to recover.', true);
}

$host = (string) ($_SERVER['HTTP_HOST'] ?? '');
if (!preg_match('/\A[A-Za-z0-9.-]+(?::[0-9]{1,5})?\z/', $host)) {
    $respond('A safe reset link could not be generated for this host. Check the web server host configuration.', true);
}

$pdo = Database::connect();
try {
    $accountStmt = $pdo->prepare('SELECT full_name FROM users WHERE id = ?');
    $accountStmt->execute([$accountId]);
    $accountName = $accountStmt->fetchColumn();
    if ($accountName === false) {
        $respond('That account was not found.', true);
    }

    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $pdo->beginTransaction();
    $invalidateStmt = $pdo->prepare(
        'UPDATE password_reset_tokens SET used_at = NOW()
         WHERE user_id = ? AND used_at IS NULL'
    );
    $invalidateStmt->execute([$accountId]);
    $insertStmt = $pdo->prepare(
        'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at)
         VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))'
    );
    $insertStmt->execute([$accountId, $tokenHash]);
    $pdo->commit();

    $isHttps = !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off';
    $scheme = $isHttps ? 'https' : 'http';
    $sitePath = rtrim(dirname(dirname(str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/actions/account_recovery_action.php')))), '/');
    $resetLink = $scheme . '://' . $host . $sitePath . '/reset-password.php?token=' . rawurlencode($token);
    $respond('A one-time reset link was generated. It expires in 30 minutes.', false, $resetLink, (string) $accountName);
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Account recovery link generation failed: ' . $exception->getMessage());
    $respond('The reset link could not be generated. Please try again.', true);
}
