<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../config/db.php';

$user = require_role('teacher');
$basePath = '../';
$pdo = Database::connect();

$classQuery = $pdo->prepare(
    'SELECT c.*, COALESCE(enrollment_counts.students_count, 0) AS students_count
     FROM classes c
     LEFT JOIN (
         SELECT class_id, COUNT(DISTINCT student_id) AS students_count
         FROM class_enrollments
         GROUP BY class_id
     ) enrollment_counts ON enrollment_counts.class_id = c.id
     WHERE c.teacher_id = ?
     ORDER BY c.code'
);
$classQuery->execute([$user['id']]);
$classes = $classQuery->fetchAll();

function store_teacher_upload(array $file, string $folder): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return '';
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The file upload failed. Please try again.');
    }
    if (($file['size'] ?? 0) > 10 * 1024 * 1024) {
        throw new RuntimeException('Files must be 10 MB or smaller.');
    }

    $extensions = [
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
    $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file((string) $file['tmp_name']);
    if (!isset($extensions[$extension]) || !in_array($mime, $extensions[$extension], true)) {
        throw new RuntimeException('Unsupported file type. Use PDF, Office documents, images, or ZIP files.');
    }

    $directory = __DIR__ . '/../uploads/' . $folder;
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('The upload directory is unavailable.');
    }
    $filename = bin2hex(random_bytes(16)) . '.' . $extension;
    if (!move_uploaded_file((string) $file['tmp_name'], $directory . '/' . $filename)) {
        throw new RuntimeException('The uploaded file could not be saved.');
    }
    return 'uploads/' . $folder . '/' . $filename;
}

function teacher_flash_redirect(string $page, string $message, bool $isError = false): never
{
    $_SESSION['teacher_flash'] = ['message' => $message, 'error' => $isError];
    header('Location: ' . $page);
    exit;
}

function teacher_verify_csrf(): void
{
    if (!isset($_POST['csrf'], $_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], (string) $_POST['csrf'])) {
        teacher_flash_redirect(basename($_SERVER['PHP_SELF']), 'Your session expired. Please try again.', true);
    }
}
