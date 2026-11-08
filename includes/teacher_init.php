<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/upload.php';

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

function teacher_selected_class_id(array $classes): ?int
{
    $rawClassId = $_GET['class_id'] ?? null;
    $classId = filter_var(is_scalar($rawClassId) ? (string) $rawClassId : '', FILTER_VALIDATE_INT);
    $classIds = array_map('intval', array_column($classes, 'id'));

    return $classId !== false && in_array((int) $classId, $classIds, true)
        ? (int) $classId
        : null;
}

function teacher_class_page(string $page, ?int $classId): string
{
    return $classId === null ? $page : $page . '?class_id=' . $classId;
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
