<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$user = require_login();
if (empty($user['must_change_password'])) {
    header('Location: index.php');
    exit;
}

$error = $_SESSION['password_change_error'] ?? null;
unset($_SESSION['password_change_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Change Password - e-GURO LMS</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= (int) filemtime(__DIR__ . '/assets/css/style.css') ?>">
</head>
<body class="password-reset-page">
    <main class="password-reset-card">
        <a class="password-reset-brand" href="change-password.php">e-GURO LMS</a>
        <p class="eyebrow">ACCOUNT SECURITY</p>
        <h1>Change your password</h1>
        <p>Set a new password to continue to your <?= e($user['role']) ?> account.</p>
        <p class="notice">Your temporary password can only be used to reach this page.</p>
        <?php if (is_string($error) && $error !== ''): ?>
            <p class="notice error" role="alert"><?= e($error) ?></p>
        <?php endif; ?>
        <form class="password-reset-form" method="post" action="actions/change_password_action.php">
            <?= csrf_field() ?>
            <label class="field">
                <span>New password</span>
                <input type="password" name="password" minlength="12" maxlength="200" autocomplete="new-password" required>
                <small>Use 12 to 200 characters.</small>
            </label>
            <label class="field">
                <span>Confirm new password</span>
                <input type="password" name="password_confirmation" minlength="12" maxlength="200" autocomplete="new-password" required>
            </label>
            <button class="btn" type="submit">Save password and sign out</button>
        </form>
    </main>
</body>
</html>
