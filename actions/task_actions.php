<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';

$user = require_login();
$role = (string) ($user['role'] ?? '');
if (!in_array($role, ['dean', 'teacher', 'student'], true)) {
    http_response_code(403);
    exit('Access denied.');
}

$calendarPage = match ($role) {
    'dean' => '../admin/calendar.php',
    'teacher' => '../teacher/calendar.php',
    'student' => '../student/calendar.php',
};
$redirectWithMessage = static function (string $message, bool $isError = false) use ($calendarPage): never {
    $_SESSION['calendar_flash'] = ['message' => $message, 'error' => $isError];
    header('Location: ' . $calendarPage);
    exit;
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}

$csrf = $_POST['csrf'] ?? null;
if (!is_string($csrf) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $csrf)) {
    $redirectWithMessage('Your session expired. Please try again.', true);
}

$pdo = Database::connect();
$action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
$taskId = filter_var($_POST['task_id'] ?? null, FILTER_VALIDATE_INT);

try {
    if ($action === 'create' || $action === 'update') {
        $title = is_string($_POST['title'] ?? null) ? trim($_POST['title']) : '';
        $description = is_string($_POST['description'] ?? null) ? trim($_POST['description']) : '';
        $dateInput = is_string($_POST['due_date'] ?? null) ? trim($_POST['due_date']) : '';
        $dueDate = DateTimeImmutable::createFromFormat('!Y-m-d', $dateInput);
        $dateErrors = DateTimeImmutable::getLastErrors();
        $validDate = $dueDate !== false
            && ($dateErrors === false || ($dateErrors['warning_count'] === 0 && $dateErrors['error_count'] === 0))
            && $dueDate->format('Y-m-d') === $dateInput;

        if ($title === '' || mb_strlen($title) > 160 || mb_strlen($description) > 2000 || !$validDate) {
            $redirectWithMessage('Enter a task title of up to 160 characters and choose a valid due date.', true);
        }

        if ($action === 'create') {
            $stmt = $pdo->prepare(
                'INSERT INTO user_tasks (user_id, title, description, due_date) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([(int) $user['id'], $title, $description !== '' ? $description : null, $dateInput]);
            $redirectWithMessage('Task added to your calendar.');
        }

        if (!$taskId || $taskId < 1) {
            $redirectWithMessage('Select a valid task to update.', true);
        }
        $stmt = $pdo->prepare(
            'UPDATE user_tasks SET title = ?, description = ?, due_date = ?
             WHERE id = ? AND user_id = ?'
        );
        $stmt->execute([$title, $description !== '' ? $description : null, $dateInput, $taskId, (int) $user['id']]);
        $redirectWithMessage($stmt->rowCount() > 0 ? 'Task updated.' : 'Task details are unchanged or the task was not found.');
    }

    if ($action === 'toggle') {
        if (!$taskId || $taskId < 1) {
            $redirectWithMessage('Select a valid task to update.', true);
        }
        $stmt = $pdo->prepare(
            'UPDATE user_tasks SET is_complete = 1 - is_complete WHERE id = ? AND user_id = ?'
        );
        $stmt->execute([$taskId, (int) $user['id']]);
        $redirectWithMessage($stmt->rowCount() === 1 ? 'Task status updated.' : 'That task was not found.');
    }

    if ($action === 'delete') {
        if (!$taskId || $taskId < 1) {
            $redirectWithMessage('Select a valid task to delete.', true);
        }
        $stmt = $pdo->prepare('DELETE FROM user_tasks WHERE id = ? AND user_id = ?');
        $stmt->execute([$taskId, (int) $user['id']]);
        $redirectWithMessage($stmt->rowCount() === 1 ? 'Task deleted.' : 'That task was not found.');
    }

    $redirectWithMessage('Choose a valid task action.', true);
} catch (PDOException $exception) {
    error_log('Personal task action failed: ' . $exception->getMessage());
    $redirectWithMessage('The task could not be saved. Please try again.', true);
}
