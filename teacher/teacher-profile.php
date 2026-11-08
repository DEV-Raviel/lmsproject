<?php
require_once __DIR__ . '/../includes/teacher_init.php';

$pageTitle = 'Teacher Profile';
$activePage = 'profile';
$profileStmt = $pdo->prepare('SELECT email, facebook_url, profile_photo_path FROM users WHERE id = ? AND role = ?');
$profileStmt->execute([(int) $user['id'], 'teacher']);
$profile = $profileStmt->fetch() ?: ['email' => $user['email'] ?? '', 'facebook_url' => '', 'profile_photo_path' => null];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    teacher_verify_csrf();
    $name = is_string($_POST['full_name'] ?? null) ? trim($_POST['full_name']) : '';
    $facebookUrl = is_string($_POST['facebook_url'] ?? null) ? trim($_POST['facebook_url']) : '';
    if ($name === '' || mb_strlen($name) > 150 || !is_valid_facebook_url($facebookUrl) || mb_strlen($facebookUrl) > 255) {
        teacher_flash_redirect('teacher-profile.php', 'Enter a name and a valid Facebook profile link.', true);
    }

    $removePhoto = isset($_POST['remove_photo']);
    $photoFile = $_FILES['profile_photo'] ?? [];
    if (!is_array($photoFile)) {
        teacher_flash_redirect('teacher-profile.php', 'The uploaded photo could not be verified.', true);
    }
    if ($removePhoto && ($photoFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        teacher_flash_redirect('teacher-profile.php', 'Choose either a new photo or remove the current photo.', true);
    }

    $newPhotoPath = '';
    try {
        if (($photoFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $newPhotoPath = store_profile_photo($photoFile);
        }
    } catch (RuntimeException $exception) {
        teacher_flash_redirect('teacher-profile.php', $exception->getMessage(), true);
    }

    try {
        $profileUpdate = $pdo->prepare(
            'UPDATE users
             SET full_name = ?, facebook_url = ?,
                 profile_photo_path = CASE
                     WHEN ? <> \'\' THEN ?
                     WHEN ? = 1 THEN NULL
                     ELSE profile_photo_path
                 END
             WHERE id = ? AND role = ?'
        );
        $profileUpdate->execute([
            $name,
            $facebookUrl,
            $newPhotoPath,
            $newPhotoPath,
            $removePhoto ? 1 : 0,
            $user['id'],
            'teacher',
        ]);
        $_SESSION['user']['name'] = $name;

        if ($newPhotoPath !== '' || $removePhoto) {
            try {
                delete_profile_photo($profile['profile_photo_path'] ?? null);
            } catch (RuntimeException $exception) {
                error_log('Previous teacher profile photo cleanup failed: ' . $exception->getMessage());
            }
        }

        teacher_flash_redirect('teacher-profile.php', 'Profile updated successfully.');
    } catch (PDOException $exception) {
        if ($newPhotoPath !== '') {
            try {
                delete_profile_photo($newPhotoPath);
            } catch (RuntimeException $cleanupException) {
                error_log('New teacher profile photo cleanup failed: ' . $cleanupException->getMessage());
            }
        }
        error_log('Teacher profile update failed: ' . $exception->getMessage());
        teacher_flash_redirect('teacher-profile.php', 'The profile could not be updated. Please try again.', true);
    }
}
$flash = $_SESSION['teacher_flash'] ?? null;
unset($_SESSION['teacher_flash']);
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <div class="page-header"><h2>Teacher Profile</h2><p>Keep your name and student contact information up to date.</p></div>
    <?php if ($flash): ?><p class="<?= !empty($flash['error']) ? 'notice error' : 'notice' ?>" role="status"><?= e($flash['message']) ?></p><?php endif; ?>
    <form class="card form" method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="field"><label for="user-code">Teacher ID</label><input id="user-code" value="<?= e($user['user_code'] ?? '') ?>" readonly></div>
        <div class="field"><label for="full-name">Full name</label><input id="full-name" name="full_name" value="<?= e($user['name'] ?? '') ?>" maxlength="150" required></div>
        <div class="field">
            <label for="profile-photo">Profile photo</label>
            <?php $photoSrc = profile_photo_src($profile['profile_photo_path'], (int) $user['id'], '../'); ?>
            <?php if ($photoSrc): ?><img class="profile-photo-preview" src="<?= e($photoSrc) ?>" alt="Current profile photo"><?php endif; ?>
            <input id="profile-photo" name="profile_photo" type="file" accept="image/jpeg,image/png">
            <small>JPG or PNG; maximum 5 MB. The saved portrait is cropped square.</small>
            <?php if ($photoSrc): ?><label class="checkbox-field"><input type="checkbox" name="remove_photo" value="1"> Remove current photo</label><?php endif; ?>
        </div>
        <div class="field">
            <label for="email">Institutional email</label>
            <input id="email" type="email" value="<?= e($profile['email']) ?>" readonly>
            <small>This email is provided and managed by your Administrator.</small>
        </div>
        <div class="field">
            <label for="facebook-url">Facebook profile link</label>
            <input id="facebook-url" name="facebook_url" type="url" value="<?= e($profile['facebook_url']) ?>" maxlength="255" placeholder="https://www.facebook.com/your.profile" required>
            <small>Students in your classes can use this link to contact you.</small>
        </div>
        <div class="field"><label>Assigned classes</label><p><?= $classes ? e(implode(', ', array_column($classes, 'code'))) : 'No classes assigned' ?></p></div>
        <button class="btn" type="submit">Save Profile</button>
    </form>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
