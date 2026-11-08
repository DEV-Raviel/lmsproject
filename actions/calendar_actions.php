<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/calendar.php';
require_once __DIR__ . '/../config/db.php';

$user = require_any_role(['dean', 'teacher', 'student']);
$role = (string) ($user['role'] ?? '');
$scope = calendar_scope_for_role((string) ($user['role'] ?? ''));
$calendarPage = match ($scope) {
    'school' => '../admin/calendar.php',
    'teacher' => '../teacher/calendar.php',
    'student' => '../student/calendar.php',
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}

$redirectWithMessage = static function (string $message, bool $isError = false) use ($calendarPage): never {
    $_SESSION['calendar_flash'] = ['message' => $message, 'error' => $isError];
    header('Location: ' . $calendarPage);
    exit;
};

$csrf = $_POST['csrf'] ?? null;
if (!is_string($csrf) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $csrf)) {
    $redirectWithMessage('Your session expired. Please try again.', true);
}

$action = $_POST['action'] ?? '';
$eventId = filter_var($_POST['event_id'] ?? null, FILTER_VALIDATE_INT);
$pdo = Database::connect();

try {
    if ($action === 'delete') {
        if (!$eventId || $eventId < 1) {
            $redirectWithMessage('Select a valid event to delete.', true);
        }
        $stmt = $pdo->prepare('DELETE FROM calendar_events WHERE id = ? AND role_scope = ?');
        $stmt->execute([$eventId, $scope]);
        $redirectWithMessage($stmt->rowCount() === 1 ? 'Event deleted.' : 'That event is not available in your role calendar.', $stmt->rowCount() !== 1);
    }

    if ($action !== 'save') {
        $redirectWithMessage('Choose a valid calendar action.', true);
    }

    $title = is_string($_POST['title'] ?? null) ? trim($_POST['title']) : '';
    $description = is_string($_POST['description'] ?? null) ? trim($_POST['description']) : '';
    $dateInput = is_string($_POST['event_date'] ?? null) ? trim($_POST['event_date']) : '';
    $eventDate = DateTimeImmutable::createFromFormat('!Y-m-d', $dateInput);
    $dateErrors = DateTimeImmutable::getLastErrors();
    $validDate = $eventDate !== false
        && ($dateErrors === false || ($dateErrors['warning_count'] === 0 && $dateErrors['error_count'] === 0))
        && $eventDate->format('Y-m-d') === $dateInput;

    if ($title === '' || mb_strlen($title) > 160 || mb_strlen($description) > 2000 || !$validDate) {
        $redirectWithMessage('Enter a title of up to 160 characters and choose a valid date.', true);
    }

    if ($eventId && $eventId > 0) {
        $exists = $pdo->prepare('SELECT id FROM calendar_events WHERE id = ? AND role_scope = ?');
        $exists->execute([$eventId, $scope]);
        if (!$exists->fetchColumn()) {
            $redirectWithMessage('That event is not available in your role calendar.', true);
        }
        $stmt = $pdo->prepare(
            'UPDATE calendar_events
             SET title = ?, description = ?, event_date = ?
             WHERE id = ? AND role_scope = ?'
        );
        $stmt->execute([$title, $description !== '' ? $description : null, $dateInput, $eventId, $scope]);
        $redirectWithMessage('Event updated.');
    }

    $stmt = $pdo->prepare(
        'INSERT INTO calendar_events (role_scope, title, description, event_date, created_by)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$scope, $title, $description !== '' ? $description : null, $dateInput, $user['id']]);
    $redirectWithMessage('Event added.');
} catch (PDOException $exception) {
    error_log('Calendar event save failed: ' . $exception->getMessage());
    $redirectWithMessage('The event could not be saved. Please try again.', true);
}
