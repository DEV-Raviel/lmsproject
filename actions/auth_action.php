<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/login_throttle.php';
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}

$redirectToLogin = static function (string $error): never {
    header('Location: ../index.php?error=' . rawurlencode($error));
    exit;
};

if (!csrf_token_is_valid($_POST['csrf'] ?? null)) {
    $redirectToLogin('csrf_expired');
}

$identifier = is_string($_POST['identifier'] ?? null) ? trim($_POST['identifier']) : '';
$password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
$ipAddress = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
$bucketKeys = login_throttle_bucket_keys($identifier, $ipAddress);

try {
    $pdo = Database::connect();
    if (login_throttle_is_locked($pdo, $bucketKeys)) {
        $redirectToLogin('rate_limited');
    }

    $user = null;
    if ($identifier !== '' && $password !== '' && strlen($identifier) <= 254 && strlen($password) <= 200) {
        $stmt = $pdo->prepare(
            'SELECT id, user_code, full_name, email, password, role, must_change_password
             FROM users
             WHERE LOWER(user_code) = LOWER(?) OR LOWER(email) = LOWER(?)
             LIMIT 1'
        );
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();
    }

    if (!$user || !password_verify($password, (string) $user['password'])) {
        login_throttle_record_failure($pdo, $bucketKeys);
        $redirectToLogin('invalid_credentials');
    }

    login_throttle_clear($pdo, $bucketKeys);
} catch (PDOException $exception) {
    error_log('LMS sign-in request failed: ' . $exception->getMessage());
    $redirectToLogin('unavailable');
}

session_regenerate_id(true);
unset($_SESSION['csrf']);
$_SESSION['user'] = [
    'id' => (int) $user['id'],
    'user_code' => $user['user_code'],
    'name' => $user['full_name'],
    'email' => $user['email'],
    'role' => $user['role'],
    'must_change_password' => (bool) $user['must_change_password'],
];

if (!empty($user['must_change_password'])) {
    header('Location: ../change-password.php');
    exit;
}

match ($user['role']) {
    'admin' => header('Location: ../admin/dashboard.php'),
    'dean' => header('Location: ../admin/manage_announcements.php'),
    'teacher' => header('Location: ../teacher/dashboard.php'),
    'student' => header('Location: ../student/dashboard.php'),
    default => header('Location: ../index.php'),
};
exit;