<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

$user = require_role('admin');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

$redirect = '../admin/dashboard.php';
$fail = static function (string $message) use ($redirect): never {
    $_SESSION['admin_flash'] = ['message' => $message, 'error' => true];
    header('Location: ' . $redirect);
    exit;
};
$succeed = static function (string $message, ?string $temporaryPassword = null) use ($redirect): never {
    $_SESSION['admin_flash'] = [
        'message' => $message,
        'error' => false,
        'temporary_password' => $temporaryPassword,
        'account_created' => $temporaryPassword !== null,
    ];
    header('Location: ' . $redirect);
    exit;
};

if (!isset($_POST['csrf'], $_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], (string) $_POST['csrf'])) {
    $fail('Your session expired. Please try again.');
}

$pdo = Database::connect();
$action = $_POST['action'] ?? '';

try {
    if ($action === 'create_user') {
        $userCode = trim($_POST['user_code'] ?? '');
        $name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? '';

        if ($userCode === '' || $name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)
            || !in_array($role, ['admin', 'dean', 'teacher', 'student'], true)) {
            $fail('Enter a valid user code, name, email, and role.');
        }
        $temporaryPassword = bin2hex(random_bytes(16));
        $stmt = $pdo->prepare(
            'INSERT INTO users (user_code, full_name, email, password, role, must_change_password)
             VALUES (?, ?, ?, ?, ?, 1)'
        );
        $stmt->execute([$userCode, $name, $email, password_hash($temporaryPassword, PASSWORD_DEFAULT), $role]);
        $succeed('Account created. Copy the temporary password and share it securely.', $temporaryPassword);
    }

    if ($action === 'create_class') {
        $code = trim($_POST['code'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $teacherId = filter_var($_POST['teacher_id'] ?? null, FILTER_VALIDATE_INT);
        $semester = trim($_POST['semester'] ?? '');
        $fiscalYear = trim($_POST['fiscal_year'] ?? '');
        if ($code === '' || $name === '' || !$teacherId || $semester === '' || $fiscalYear === '') {
            $fail('Complete all class fields before submitting.');
        }
        $teacherStmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'teacher'");
        $teacherStmt->execute([$teacherId]);
        if (!$teacherStmt->fetchColumn()) {
            $fail('Select a valid teacher account.');
        }
        $stmt = $pdo->prepare('INSERT INTO classes (code, name, teacher_id, semester, fiscal_year) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$code, $name, $teacherId, $semester, $fiscalYear]);
        $succeed('Class created and assigned successfully.');
    }

    if ($action === 'delete_user') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id || $id === (int) $user['id']) {
            $fail('That account cannot be deleted.');
        }
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role <> 'admin'");
        $stmt->execute([$id]);
        $succeed($stmt->rowCount() ? 'Account deleted successfully.' : 'The account was not found or is protected.');
    }

    if ($action === 'delete_class') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id) {
            $fail('Select a valid class to remove.');
        }
        $stmt = $pdo->prepare('DELETE FROM classes WHERE id = ?');
        $stmt->execute([$id]);
        $succeed($stmt->rowCount() ? 'Class removed successfully.' : 'The class was not found.');
    }
    if ($action === 'enroll_student') {
        $classId = filter_var($_POST['class_id'] ?? null, FILTER_VALIDATE_INT);
        $studentId = filter_var($_POST['student_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$classId || !$studentId) {
            $fail('Select a class and student before enrolling.');
        }
        $classStmt = $pdo->prepare('SELECT id FROM classes WHERE id = ?');
        $classStmt->execute([$classId]);
        $studentStmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'student'");
        $studentStmt->execute([$studentId]);
        if (!$classStmt->fetchColumn() || !$studentStmt->fetchColumn()) {
            $fail('Select a valid class and student account.');
        }
        $stmt = $pdo->prepare('INSERT IGNORE INTO class_enrollments (class_id, student_id) VALUES (?, ?)');
        $stmt->execute([$classId, $studentId]);
        $succeed($stmt->rowCount() ? 'Student enrolled successfully.' : 'That student is already enrolled in this class.');
    }

    if ($action === 'batch_enroll_students') {
        $classId = filter_var($_POST['class_id'] ?? null, FILTER_VALIDATE_INT);
        $rawIdentifiers = is_string($_POST['student_identifiers'] ?? null)
            ? trim($_POST['student_identifiers'])
            : '';
        if (!$classId || $rawIdentifiers === '') {
            $fail('Select a class and provide student identifiers.');
        }

        $classStmt = $pdo->prepare('SELECT id FROM classes WHERE id = ?');
        $classStmt->execute([$classId]);
        if (!$classStmt->fetchColumn()) {
            $fail('The selected class was not found.');
        }

        $identifiers = preg_split('/[\s,;]+/', $rawIdentifiers, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $identifiers = array_values(array_unique(array_map('trim', $identifiers)));
        if (count($identifiers) > 200 || count($identifiers) === 0) {
            $fail('Enter between 1 and 200 student identifiers.');
        }
        foreach ($identifiers as $identifier) {
            if (mb_strlen($identifier) > 150) {
                $fail('Each student identifier must be 150 characters or fewer.');
            }
        }

        $placeholders = implode(',', array_fill(0, count($identifiers), '?'));
        $lookup = $pdo->prepare(
            "SELECT id, user_code, email
             FROM users
             WHERE role = 'student'
               AND (user_code IN ($placeholders) OR email IN ($placeholders))"
        );
        $lookup->execute(array_merge($identifiers, $identifiers));
        $matchedStudents = $lookup->fetchAll();
        $matchedIdentifiers = [];
        $studentIds = [];
        foreach ($matchedStudents as $student) {
            $studentIds[(int) $student['id']] = true;
            $matchedIdentifiers[strtolower($student['user_code'])] = true;
            $matchedIdentifiers[strtolower($student['email'])] = true;
        }
        $unknown = array_values(array_filter(
            $identifiers,
            static fn(string $identifier): bool => !isset($matchedIdentifiers[strtolower($identifier)])
        ));

        $insertEnrollment = $pdo->prepare(
            'INSERT IGNORE INTO class_enrollments (class_id, student_id) VALUES (?, ?)'
        );
        $enrolled = 0;
        try {
            $pdo->beginTransaction();
            foreach (array_keys($studentIds) as $studentId) {
                $insertEnrollment->execute([$classId, $studentId]);
                $enrolled += $insertEnrollment->rowCount();
            }
            $pdo->commit();
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }

        $alreadyEnrolled = count($studentIds) - $enrolled;
        $message = sprintf(
            'Batch enrollment complete: %d enrolled, %d already enrolled, %d not found as students.',
            $enrolled,
            $alreadyEnrolled,
            count($unknown)
        );
        if ($unknown) {
            $message .= ' Not matched: ' . implode(', ', array_slice($unknown, 0, 10));
            if (count($unknown) > 10) {
                $message .= ', and ' . (count($unknown) - 10) . ' more';
            }
        }
        $succeed($message);
    }

    $fail('Unknown action.');
} catch (PDOException $exception) {
    error_log('Admin action failed: ' . $exception->getMessage());
    $fail('The requested change could not be saved. Check for duplicate user codes/emails or class codes and try again.');
}
