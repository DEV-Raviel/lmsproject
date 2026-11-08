<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/db.php';

header('Cache-Control: no-store, private');
header('Referrer-Policy: no-referrer');
header('X-Robots-Tag: noindex, nofollow');

$rawToken = $_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['token'] ?? '') : ($_GET['token'] ?? '');
$token = is_string($rawToken) ? trim($rawToken) : '';
$tokenValidFormat = preg_match('/\A[a-f0-9]{64}\z/', $token) === 1;
$error = '';
$success = '';
$pdo = Database::connect();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf'] ?? null;
    if (!is_string($csrf) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $csrf)) {
        $error = 'Your session expired. Reload the page and try again.';
    } elseif (!$tokenValidFormat) {
        $error = 'This reset link is invalid or has expired. Ask the administrator for a new link.';
    } else {
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        $confirmation = is_string($_POST['password_confirmation'] ?? null) ? $_POST['password_confirmation'] : '';
        if (strlen($password) < 12 || strlen($password) > 200) {
            $error = 'Choose a password between 12 and 200 characters.';
        } elseif (!hash_equals($password, $confirmation)) {
            $error = 'The password confirmation does not match.';
        } else {
            try {
                $pdo->beginTransaction();
                $tokenStmt = $pdo->prepare(
                    'SELECT id, user_id FROM password_reset_tokens
                     WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW()
                     FOR UPDATE'
                );
                $tokenStmt->execute([hash('sha256', $token)]);
                $resetToken = $tokenStmt->fetch();
                if (!$resetToken) {
                    $pdo->rollBack();
                    $error = 'This reset link is invalid or has expired. Ask the administrator for a new link.';
                    $token = '';
                    $tokenValidFormat = false;
                } else {
                    $updateUser = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
                    $updateUser->execute([password_hash($password, PASSWORD_DEFAULT), $resetToken['user_id']]);
                    $useTokens = $pdo->prepare(
                        'UPDATE password_reset_tokens SET used_at = NOW()
                         WHERE user_id = ? AND used_at IS NULL'
                    );
                    $useTokens->execute([$resetToken['user_id']]);
                    $pdo->commit();
                    $success = 'Your password has been reset. You can now sign in with your new password.';
                    $token = '';
                    $tokenValidFormat = false;
                }
            } catch (PDOException $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('Password reset failed: ' . $exception->getMessage());
                $error = 'The password could not be reset right now. Please try again.';
            }
        }
    }
} elseif (!$tokenValidFormat) {
    $error = 'This reset link is invalid or has expired. Ask the administrator for a new link.';
} else {
    $checkToken = $pdo->prepare(
        'SELECT id FROM password_reset_tokens
         WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW()'
    );
    $checkToken->execute([hash('sha256', $token)]);
    if (!$checkToken->fetchColumn()) {
        $error = 'This reset link is invalid or has expired. Ask the administrator for a new link.';
        $tokenValidFormat = false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password - e-GURO LMS</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= (int) filemtime(__DIR__ . '/assets/css/style.css') ?>">
</head>
<body class="password-reset-page">
    <main class="password-reset-card">
        <a class="password-reset-brand" href="index.php">e-GURO LMS</a>
        <p class="eyebrow">ACCOUNT SECURITY</p>
        <h1>Reset your password</h1>
        <p>Choose a new password to regain access to your LMS account.</p>
        <?php if ($error !== ''): ?><p class="notice error" role="alert"><?= e($error) ?></p><?php endif; ?>
        <?php if ($success !== ''): ?>
            <p class="notice" role="status"><?= e($success) ?></p>
            <a class="btn" href="index.php">Return to sign in</a>
        <?php elseif ($tokenValidFormat): ?>
            <form class="password-reset-form" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="token" value="<?= e($token) ?>">
                <label class="field">
                    <span>New password</span>
                    <input type="password" name="password" minlength="12" maxlength="200" autocomplete="new-password" required>
                </label>
                <label class="field">
                    <span>Confirm new password</span>
                    <input type="password" name="password_confirmation" minlength="12" maxlength="200" autocomplete="new-password" required>
                </label>
                <button class="btn" type="submit">Save new password</button>
            </form>
        <?php else: ?>
            <a class="btn btn-outline" href="index.php">Return to sign in</a>
        <?php endif; ?>
    </main>
</body>
</html>
