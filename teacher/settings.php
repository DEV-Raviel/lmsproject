<?php
require_once __DIR__ . '/../includes/teacher_init.php';

$pageTitle = 'Settings';
$activePage = 'settings';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    teacher_verify_csrf();
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    if (strlen($newPassword) < 8) {
        teacher_flash_redirect('settings.php', 'New passwords must be at least 8 characters.', true);
    }
    $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ? AND role = ?');
    $stmt->execute([$user['id'], 'teacher']);
    $storedPassword = $stmt->fetchColumn();
    if (!$storedPassword || !password_verify($currentPassword, $storedPassword)) {
        teacher_flash_redirect('settings.php', 'The current password is incorrect.', true);
    }
    try {
        $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
        $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $user['id']]);
        teacher_flash_redirect('settings.php', 'Password changed successfully.');
    } catch (PDOException $exception) {
        error_log('Teacher password update failed: ' . $exception->getMessage());
        teacher_flash_redirect('settings.php', 'Your password could not be changed. Please try again.', true);
    }
}
$flash = $_SESSION['teacher_flash'] ?? null;
unset($_SESSION['teacher_flash']);
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <div class="page-header"><h2>Account Settings</h2><p>Change your account password.</p></div>
    <?php if ($flash): ?><p class="<?= !empty($flash['error']) ? 'notice error' : 'notice' ?>" role="status"><?= e($flash['message']) ?></p><?php endif; ?>
    <form class="card form" method="post">
        <?= csrf_field() ?>
        <div class="field"><label for="current-password">Current password</label><input id="current-password" name="current_password" type="password" autocomplete="current-password" required></div>
        <div class="field"><label for="new-password">New password</label><input id="new-password" name="new_password" type="password" autocomplete="new-password" minlength="8" required></div>
        <button class="btn" type="submit">Change Password</button>
    </form>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
