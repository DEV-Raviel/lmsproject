<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';

$user = require_role('admin');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}

$redirect = '../admin/recover_accounts.php';
$respond = static function (string $message, bool $isError = false, ?string $temporaryPassword = null, ?string $accountName = null) use ($redirect): never {
    $_SESSION['recovery_flash'] = [
        'message' => $message,
        'error' => $isError,
        'temporary_password' => $temporaryPassword,
        'account_name' => $accountName,
    ];
    header('Location: ' . $redirect);
    exit;
};

if (!csrf_token_is_valid($_POST['csrf'] ?? null)) {
    $respond('Your session expired. Please try again.', true);
}

$accountId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
if (!$accountId || $accountId < 1 || $accountId === (int) $user['id']) {
    $respond('Select another valid account to recover.', true);
}

$pdo = Database::connect();
try {
    $temporaryPassword = generate_temporary_password();
    $pdo->beginTransaction();
    $accountStmt = $pdo->prepare('SELECT full_name FROM users WHERE id = ? FOR UPDATE');
    $accountStmt->execute([$accountId]);
    $accountName = $accountStmt->fetchColumn();
    if ($accountName === false) {
        $pdo->rollBack();
        $respond('That account was not found.', true);
    }

    $updateAccount = $pdo->prepare(
        'UPDATE users SET password = ?, must_change_password = 1 WHERE id = ?'
    );
    $updateAccount->execute([password_hash($temporaryPassword, PASSWORD_DEFAULT), $accountId]);
    if ($updateAccount->rowCount() !== 1) {
        $pdo->rollBack();
        $respond('That account no longer exists. No password was changed.', true);
    }
    $invalidateTokens = $pdo->prepare(
        'UPDATE password_reset_tokens SET used_at = NOW()
         WHERE user_id = ? AND used_at IS NULL'
    );
    $invalidateTokens->execute([$accountId]);
    $pdo->commit();

    $respond('A temporary password was generated. Share it securely; the account must change it at sign-in.', false, $temporaryPassword, (string) $accountName);
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Account recovery link generation failed: ' . $exception->getMessage());
    $respond('The reset link could not be generated. Please try again.', true);
}
