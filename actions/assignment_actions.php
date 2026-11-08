<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/upload.php';
require_once __DIR__ . '/../config/db.php';

$user = require_role('student');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

$assignmentId = filter_var($_POST['task_id'] ?? null, FILTER_VALIDATE_INT);
$redirect = '../student/submit-work.php?task=' . (int) $assignmentId;
$fail = static function (string $message) use ($redirect): never {
    $_SESSION['submission_flash'] = ['message' => $message, 'error' => true];
    header('Location: ' . $redirect);
    exit;
};
if (!$assignmentId || !isset($_POST['csrf'], $_SESSION['csrf'])
    || !hash_equals($_SESSION['csrf'], (string) $_POST['csrf'])) {
    $fail('Your session expired or the assignment link is invalid. Please open the assignment again.');
}

$pdo = Database::connect();
$stmt = $pdo->prepare(
    'SELECT a.id, a.deadline, a.title
     FROM assignments a
     JOIN class_enrollments ce ON ce.class_id = a.class_id AND ce.student_id = ?
     WHERE a.id = ?'
);
$stmt->execute([$user['id'], $assignmentId]);
$assignment = $stmt->fetch();
if (!$assignment) {
    http_response_code(404);
    exit('Assignment not found in your enrolled classes.');
}
if (strtotime($assignment['deadline']) < time()) {
    $fail('The assignment deadline has passed and submissions are closed.');
}
$stmt = $pdo->prepare('SELECT id FROM submissions WHERE assignment_id = ? AND student_id = ? LIMIT 1');
$stmt->execute([$assignmentId, $user['id']]);
if ($stmt->fetchColumn()) {
    $fail('You have already submitted work for this assignment.');
}

$file = $_FILES['file'] ?? [];
if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || ($file['size'] ?? 0) > 10 * 1024 * 1024) {
    $fail('Choose a file smaller than 10 MB and try again.');
}
try {
    $relativePath = store_safe_upload($file, 'submissions');
} catch (RuntimeException $exception) {
    $fail($exception->getMessage());
}
$absolutePath = __DIR__ . '/../' . $relativePath;

try {
    $stmt = $pdo->prepare(
        'INSERT INTO submissions (assignment_id, student_id, file_path) VALUES (?, ?, ?)'
    );
    $stmt->execute([$assignmentId, $user['id'], $relativePath]);
    $_SESSION['submission_flash'] = ['message' => 'Your assignment was submitted successfully.', 'error' => false];
    header('Location: ' . $redirect);
    exit;
} catch (PDOException $exception) {
    if (is_file($absolutePath) && !unlink($absolutePath)) {
        error_log('Unable to remove failed assignment upload: ' . $absolutePath);
    }
    error_log('Student assignment submission failed: ' . $exception->getMessage());
    $fail('Your work could not be submitted. Please try again.');
}
