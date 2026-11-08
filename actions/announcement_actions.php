<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';

$user = require_role('dean');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}

$redirect = '../admin/manage_announcements.php';
$respond = static function (string $message, bool $isError = false) use ($redirect): never {
    $_SESSION['announcement_flash'] = ['message' => $message, 'error' => $isError];
    header('Location: ' . $redirect);
    exit;
};

$csrf = $_POST['csrf'] ?? null;
if (!is_string($csrf) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $csrf)) {
    $respond('Your session expired. Please try again.', true);
}

$action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
$pdo = Database::connect();

try {
    if ($action === 'delete') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id || $id < 1) {
            $respond('Select a valid announcement to delete.', true);
        }
        $stmt = $pdo->prepare('DELETE FROM announcements WHERE id = ?');
        $stmt->execute([$id]);
        $respond($stmt->rowCount() === 1 ? 'Announcement deleted.' : 'That announcement was not found.', $stmt->rowCount() !== 1);
    }

    if (!in_array($action, ['create', 'update'], true)) {
        $respond('Choose a valid announcement action.', true);
    }

    $title = is_string($_POST['title'] ?? null) ? trim($_POST['title']) : '';
    $body = is_string($_POST['body'] ?? null) ? trim($_POST['body']) : '';
    $published = isset($_POST['is_published']) && $_POST['is_published'] === '1' ? 1 : 0;
    if ($title === '' || mb_strlen($title) > 200 || $body === '' || mb_strlen($body) > 20000) {
        $respond('Enter a title up to 200 characters and announcement text up to 20,000 characters.', true);
    }

    if ($action === 'create') {
        $stmt = $pdo->prepare(
            'INSERT INTO announcements (title, body, is_published, created_by)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$title, $body, $published, $user['id']]);
        $respond($published ? 'Announcement published.' : 'Announcement saved as a draft.');
    }

    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id || $id < 1) {
        $respond('Select a valid announcement to update.', true);
    }
    $stmt = $pdo->prepare(
        'UPDATE announcements
         SET title = ?, body = ?, is_published = ?
         WHERE id = ?'
    );
    $stmt->execute([$title, $body, $published, $id]);
    $respond($stmt->rowCount() > 0 ? 'Announcement updated.' : 'No announcement changes were made.');
} catch (PDOException $exception) {
    error_log('Dean announcement action failed: ' . $exception->getMessage());
    $respond('The announcement could not be saved. Please try again.', true);
}
