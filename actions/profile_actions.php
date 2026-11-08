<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/upload.php';
require_once __DIR__ . '/../config/db.php';

$user = require_role('student');
$redirect = '../student/account.php';
$fail = static function (string $message) use ($redirect): never {
    $_SESSION['account_flash'] = ['message' => $message, 'error' => true];
    header('Location: ' . $redirect);
    exit;
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}
if (!csrf_token_is_valid($_POST['csrf'] ?? null)) {
    $fail('Your session expired. Please try again.');
}

$facebookUrl = is_string($_POST['facebook_url'] ?? null) ? trim($_POST['facebook_url']) : '';
if (($facebookUrl !== '' && !is_valid_facebook_url($facebookUrl)) || strlen($facebookUrl) > 255) {
    $fail('Enter a valid HTTPS Facebook profile link or leave it blank.');
}

$removePhoto = isset($_POST['remove_photo']);
$photoFile = $_FILES['profile_photo'] ?? [];
if (!is_array($photoFile)) {
    $fail('The uploaded photo could not be verified.');
}
if ($removePhoto && ($photoFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    $fail('Choose either a new photo or remove the current photo.');
}

$pdo = Database::connect();
$currentPhotoStmt = $pdo->prepare("SELECT profile_photo_path FROM users WHERE id = ? AND role = 'student'");
$currentPhotoStmt->execute([(int) $user['id']]);
$oldPhotoPath = $currentPhotoStmt->fetchColumn();
if ($oldPhotoPath === false) {
    $fail('Your student account could not be found.');
}

$newPhotoPath = '';
try {
    if (($photoFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $newPhotoPath = store_profile_photo($photoFile);
    }
} catch (RuntimeException $exception) {
    $fail($exception->getMessage());
}

try {
    $update = $pdo->prepare(
        'UPDATE users
         SET facebook_url = ?,
             profile_photo_path = CASE
                 WHEN ? <> \'\' THEN ?
                 WHEN ? = 1 THEN NULL
                 ELSE profile_photo_path
             END
         WHERE id = ? AND role = \'student\''
    );
    $update->execute([
        $facebookUrl === '' ? null : $facebookUrl,
        $newPhotoPath,
        $newPhotoPath,
        $removePhoto ? 1 : 0,
        (int) $user['id'],
    ]);
} catch (PDOException $exception) {
    if ($newPhotoPath !== '') {
        try {
            delete_profile_photo($newPhotoPath);
        } catch (RuntimeException $cleanupException) {
            error_log('New student profile photo cleanup failed: ' . $cleanupException->getMessage());
        }
    }
    error_log('Student classmate profile update failed: ' . $exception->getMessage());
    $fail('Your classmate profile could not be updated. Please try again.');
}

if ($newPhotoPath !== '' || $removePhoto) {
    try {
        delete_profile_photo(is_string($oldPhotoPath) ? $oldPhotoPath : null);
    } catch (RuntimeException $exception) {
        error_log('Previous student profile photo cleanup failed: ' . $exception->getMessage());
    }
}

$_SESSION['account_flash'] = ['message' => 'Your classmate profile has been updated.', 'error' => false];
header('Location: ' . $redirect);
exit;
