<?php
require_once __DIR__ . '/../includes/teacher_init.php';

$pageTitle = 'Teacher Profile';
$activePage = 'profile';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    teacher_verify_csrf();
    $name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        teacher_flash_redirect('teacher-profile.php', 'Enter a name and a valid email address.', true);
    }
    try {
        $stmt = $pdo->prepare('UPDATE users SET full_name = ?, email = ? WHERE id = ? AND role = ?');
        $stmt->execute([$name, $email, $user['id'], 'teacher']);
        $_SESSION['user']['name'] = $name;
        $_SESSION['user']['email'] = $email;
        teacher_flash_redirect('teacher-profile.php', 'Profile updated successfully.');
    } catch (PDOException $exception) {
        error_log('Teacher profile update failed: ' . $exception->getMessage());
        teacher_flash_redirect('teacher-profile.php', 'The profile could not be updated. Check that the email is not already in use.', true);
    }
}
$flash = $_SESSION['teacher_flash'] ?? null;
unset($_SESSION['teacher_flash']);
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <div class="page-header"><h2>Teacher Profile</h2><p>Keep your account information up to date.</p></div>
    <?php if ($flash): ?><p class="<?= !empty($flash['error']) ? 'notice error' : 'notice' ?>" role="status"><?= e($flash['message']) ?></p><?php endif; ?>
    <form class="card form" method="post">
        <?= csrf_field() ?>
        <div class="field"><label for="user-code">Teacher ID</label><input id="user-code" value="<?= e($user['user_code'] ?? '') ?>" readonly></div>
        <div class="field"><label for="full-name">Full name</label><input id="full-name" name="full_name" value="<?= e($user['name'] ?? '') ?>" maxlength="150" required></div>
        <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" value="<?= e($user['email'] ?? '') ?>" maxlength="150" required></div>
        <div class="field"><label>Assigned classes</label><p><?= $classes ? e(implode(', ', array_column($classes, 'code'))) : 'No classes assigned' ?></p></div>
        <button class="btn" type="submit">Save Profile</button>
    </form>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
