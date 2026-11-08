<?php
$accountStmt = $pdo->prepare(
    'SELECT user_code, full_name, email, role, facebook_url, profile_photo_path
     FROM users WHERE id = ?'
);
$accountStmt->execute([(int) $user['id']]);
$account = $accountStmt->fetch();
if (!$account) {
    http_response_code(404);
    exit('Account not found.');
}

$flash = $_SESSION['account_flash'] ?? null;
unset($_SESSION['account_flash']);
?>
<main class="main-content">
    <div class="page-header">
        <h2>My Account</h2>
        <p>Review your sign-in details and change your password.</p>
    </div>
    <?php if ($flash): ?>
        <p class="<?= !empty($flash['error']) ? 'notice error' : 'notice' ?>" role="status"><?= e($flash['message']) ?></p>
    <?php endif; ?>
    <section class="card account-details" aria-labelledby="account-details-title">
        <h3 id="account-details-title">Account details</h3>
        <?php render_details([
            'Name' => $account['full_name'],
            'User ID' => $account['user_code'],
            'Email' => $account['email'],
            'Role' => ucfirst($account['role']),
        ]); ?>
    </section>
    <?php if (($user['role'] ?? '') === 'student'): ?>
        <section class="card" aria-labelledby="student-profile-title">
            <h3 id="student-profile-title">Classmate profile</h3>
            <p>Your profile photo and Facebook link are visible to students in your classes.</p>
            <form class="form" method="post" action="../actions/profile_actions.php" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <?php $photoSrc = profile_photo_src($account['profile_photo_path'], (int) $user['id'], '../'); ?>
                <div class="field">
                    <label for="profile-photo">Profile photo</label>
                    <?php if ($photoSrc): ?><img class="profile-photo-preview" src="<?= e($photoSrc) ?>" alt="Current profile photo"><?php endif; ?>
                    <input id="profile-photo" name="profile_photo" type="file" accept="image/jpeg,image/png">
                    <small>JPG or PNG; maximum 5 MB. The saved portrait is cropped square.</small>
                    <?php if ($photoSrc): ?><label class="checkbox-field"><input type="checkbox" name="remove_photo" value="1"> Remove current photo</label><?php endif; ?>
                </div>
                <div class="field">
                    <label for="facebook-url">Facebook profile link</label>
                    <input id="facebook-url" name="facebook_url" type="url" value="<?= e($account['facebook_url'] ?? '') ?>" maxlength="255" placeholder="https://www.facebook.com/your.profile">
                    <small>Optional. Visible only to students who share a class with you.</small>
                </div>
                <button class="btn" type="submit">Save classmate profile</button>
            </form>
        </section>
    <?php endif; ?>
    <section class="card" aria-labelledby="password-change-title">
        <h3 id="password-change-title">Change password</h3>
        <p>Use your current password to set a new password for this account.</p>
        <form class="form" method="post" action="<?= e($basePath) ?>actions/account_actions.php">
            <?= csrf_field() ?>
            <div class="field">
                <label for="current-password">Current password</label>
                <input id="current-password" name="current_password" type="password" autocomplete="current-password" required>
            </div>
            <div class="field">
                <label for="new-password">New password</label>
                <input id="new-password" name="new_password" type="password" autocomplete="new-password" minlength="8" required>
            </div>
            <div class="field">
                <label for="confirm-password">Confirm new password</label>
                <input id="confirm-password" name="confirm_password" type="password" autocomplete="new-password" minlength="8" required>
            </div>
            <button class="btn" type="submit">Update password</button>
        </form>
    </section>
</main>
