<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';

$viewer = require_login();
$targetId = filter_var(is_string($_GET['id'] ?? null) ? $_GET['id'] : '', FILTER_VALIDATE_INT);
$classId = filter_var(is_string($_GET['class_id'] ?? null) ? $_GET['class_id'] : '', FILTER_VALIDATE_INT);
if (!$targetId || $targetId < 1) {
    http_response_code(404);
    exit('Photo not found.');
}

$pdo = Database::connect();
if ((int) $viewer['id'] === $targetId) {
    $photoStmt = $pdo->prepare('SELECT profile_photo_path FROM users WHERE id = ?');
    $photoStmt->execute([$targetId]);
} else {
    if (($viewer['role'] ?? '') !== 'student' || !$classId || $classId < 1) {
        http_response_code(404);
        exit('Photo not found.');
    }

    $photoStmt = $pdo->prepare(
        "SELECT target.profile_photo_path
         FROM users target
         JOIN class_enrollments viewer_class ON viewer_class.class_id = ? AND viewer_class.student_id = ?
         WHERE target.id = ?
           AND (
               (target.role = 'student' AND EXISTS (
                   SELECT 1 FROM class_enrollments target_class
                   WHERE target_class.class_id = viewer_class.class_id
                     AND target_class.student_id = target.id
               ))
               OR
               (target.role = 'teacher' AND EXISTS (
                   SELECT 1 FROM classes teacher_class
                   WHERE teacher_class.id = viewer_class.class_id
                     AND teacher_class.teacher_id = target.id
               ))
           )"
    );
    $photoStmt->execute([$classId, (int) $viewer['id'], $targetId]);
}

$photoPath = $photoStmt->fetchColumn();
$relativePath = is_string($photoPath) ? $photoPath : null;
if (!is_valid_profile_photo_path($relativePath)) {
    http_response_code(404);
    exit('Photo not found.');
}

$absolutePath = __DIR__ . '/../' . $photoPath;
if (!is_file($absolutePath)) {
    http_response_code(404);
    exit('Photo not found.');
}

header('Content-Type: image/jpeg');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=300');
header('Content-Length: ' . (string) filesize($absolutePath));
readfile($absolutePath);
