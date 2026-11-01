<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

$user = require_login();
if (($user['role'] ?? '') !== 'student') {
    http_response_code(403);
    exit('Access denied.');
}
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
$extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
$allowedTypes = [
    'pdf' => ['application/pdf'],
    'doc' => ['application/msword', 'application/CDFV2'],
    'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
    'ppt' => ['application/vnd.ms-powerpoint', 'application/CDFV2'],
    'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip'],
    'xls' => ['application/vnd.ms-excel', 'application/CDFV2'],
    'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
    'png' => ['image/png'],
    'jpg' => ['image/jpeg'],
    'jpeg' => ['image/jpeg'],
    'zip' => ['application/zip', 'application/x-zip'],
];
$mime = isset($file['tmp_name']) ? (new finfo(FILEINFO_MIME_TYPE))->file((string) $file['tmp_name']) : false;
if (!isset($allowedTypes[$extension]) || !in_array($mime, $allowedTypes[$extension], true)) {
    $fail('Unsupported file type. Upload a PDF, Office document, image, or ZIP file.');
}

$directory = __DIR__ . '/../uploads/submissions';
if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
    $fail('The submission upload folder is unavailable. Please contact your administrator.');
}
$filename = bin2hex(random_bytes(16)) . '.' . $extension;
$absolutePath = $directory . '/' . $filename;
if (!move_uploaded_file((string) $file['tmp_name'], $absolutePath)) {
    $fail('The uploaded file could not be saved. Please try again.');
}
$relativePath = 'uploads/submissions/' . $filename;

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
