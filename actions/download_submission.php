<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

$user = require_role('teacher');
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    exit('Method not allowed.');
}

$submissionId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$submissionId || $submissionId < 1) {
    http_response_code(404);
    exit('Submission not found.');
}

$stmt = Database::connect()->prepare(
    'SELECT s.file_path
     FROM submissions s
     JOIN assignments a ON a.id = s.assignment_id
     JOIN classes c ON c.id = a.class_id
     WHERE s.id = ? AND c.teacher_id = ?'
);
$stmt->execute([$submissionId, $user['id']]);
$relativePath = $stmt->fetchColumn();
if (!is_string($relativePath) || $relativePath === '') {
    http_response_code(404);
    exit('Submission not found.');
}

$uploadsDirectory = realpath(__DIR__ . '/../uploads/submissions');
$filePath = realpath(__DIR__ . '/../' . $relativePath);
if ($uploadsDirectory === false || $filePath === false
    || !str_starts_with($filePath, $uploadsDirectory . DIRECTORY_SEPARATOR)
    || !is_file($filePath) || !is_readable($filePath)) {
    error_log('Teacher submission download file missing or outside its upload directory.');
    http_response_code(404);
    exit('Submission file not found.');
}

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
header('Content-Length: ' . (string) filesize($filePath));
readfile($filePath);
exit;
