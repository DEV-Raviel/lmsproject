<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/student_profile.php';
require_once __DIR__ . '/../config/db.php';

$user = require_role('admin');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}

$redirect = '../admin/student_profiles.php';
$respond = static function (string $message, bool $isError = false, ?int $studentId = null) use ($redirect): never {
    $_SESSION['student_profiles_flash'] = [
        'message' => $message,
        'error' => $isError,
    ];
    header('Location: ' . $redirect . ($studentId ? '?user_id=' . $studentId : ''));
    exit;
};

$csrf = $_POST['csrf'] ?? null;
if (!is_string($csrf) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $csrf)) {
    $respond('Your session expired. Please try again.', true);
}

$action = $_POST['action'] ?? '';
$pdo = Database::connect();

if ($action === 'save_profile') {
    $studentId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
    if (!$studentId || $studentId < 1) {
        $respond('Select a valid student account.', true);
    }

    $studentStmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'student'");
    $studentStmt->execute([$studentId]);
    if (!$studentStmt->fetchColumn()) {
        $respond('That student account could not be found.', true);
    }

    $keys = array_keys(student_profile_fields());
    [$values, $errors] = student_profile_validate_values($_POST, $keys);
    if ($errors) {
        $respond(implode(' ', array_slice($errors, 0, 4)), true, (int) $studentId);
    }

    $columns = array_keys($values);
    $insertColumns = array_merge(['user_id'], $columns);
    $quotedColumns = implode(', ', array_map(static fn(string $column): string => '`' . $column . '`', $insertColumns));
    $placeholders = implode(', ', array_fill(0, count($insertColumns), '?'));
    $updates = implode(', ', array_map(
        static fn(string $column): string => '`' . $column . '` = VALUES(`' . $column . '`)',
        $columns
    ));

    try {
        $stmt = $pdo->prepare(
            "INSERT INTO student_profiles ($quotedColumns) VALUES ($placeholders)
             ON DUPLICATE KEY UPDATE $updates"
        );
        $stmt->execute(array_merge([(int) $studentId], array_values($values)));
        $respond('Student profile saved.', false, (int) $studentId);
    } catch (PDOException $exception) {
        error_log('Student profile save failed: ' . $exception->getMessage());
        $respond('The profile could not be saved. Please try again.', true, (int) $studentId);
    }
}

if ($action !== 'import_csv') {
    $respond('Choose a valid profile action.', true);
}

$file = $_FILES['profile_csv'] ?? null;
if (!is_array($file)) {
    $respond('Choose a CSV file to import.', true);
}
$uploadError = $file['error'] ?? UPLOAD_ERR_NO_FILE;
if ($uploadError !== UPLOAD_ERR_OK) {
    $message = match ($uploadError) {
        UPLOAD_ERR_NO_FILE => 'Choose a CSV file to import.',
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The CSV exceeds the server upload size limit.',
        default => 'The CSV upload failed. Please choose the file again.',
    };
    $respond($message, true);
}
if (($file['size'] ?? 0) > 5 * 1024 * 1024 || !is_uploaded_file((string) $file['tmp_name'])) {
    $respond('The CSV must be a valid upload no larger than 5 MB.', true);
}

$handle = fopen((string) $file['tmp_name'], 'rb');
if ($handle === false) {
    $respond('The CSV file could not be read.', true);
}

$headers = fgetcsv($handle);
if (!is_array($headers) || !$headers) {
    fclose($handle);
    $respond('The CSV is empty or has no header row.', true);
}
$headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]) ?? (string) $headers[0];
$columnKeys = [];
$seenKeys = [];
foreach ($headers as $index => $header) {
    $key = student_profile_csv_header_key((string) $header);
    $columnKeys[$index] = $key;
    if ($key !== null) {
        if (isset($seenKeys[$key])) {
            fclose($handle);
            $respond('The CSV contains duplicate columns for ' . str_replace('_', ' ', $key) . '.', true);
        }
        $seenKeys[$key] = true;
    }
}
$identifierKeys = array_values(array_intersect(['student_id', 'student_email', 'ccc_email'], array_filter($columnKeys)));
$profileKeys = array_values(array_intersect(array_keys(student_profile_fields()), array_filter($columnKeys)));
if (!$identifierKeys) {
    fclose($handle);
    $respond('The CSV needs a Student ID or Student Email column.', true);
}
if (!$profileKeys) {
    fclose($handle);
    $respond('No recognized profile fields were found in the CSV headers.', true);
}

$students = $pdo->query("SELECT id, user_code, email FROM users WHERE role = 'student'")->fetchAll();
$studentsByCode = [];
$studentsByEmail = [];
foreach ($students as $student) {
    $studentsByCode[strtolower(trim((string) $student['user_code']))] = (int) $student['id'];
    $studentsByEmail[strtolower(trim((string) $student['email']))] = (int) $student['id'];
}

$rowsToImport = [];
$rowErrors = [];
$csvRow = 1;
while (($row = fgetcsv($handle)) !== false) {
    $csvRow++;
    if (count($rowsToImport) + count($rowErrors) >= 5000) {
        $rowErrors[] = 'CSV row ' . $csvRow . ': the import is limited to 5,000 data rows.';
        break;
    }
    if (count(array_filter($row, static fn($value): bool => trim((string) $value) !== '')) === 0) {
        continue;
    }
    if (count($row) !== count($headers)) {
        $rowErrors[] = 'CSV row ' . $csvRow . ': the number of values does not match the header.';
        continue;
    }

    $record = [];
    foreach ($columnKeys as $index => $key) {
        if ($key !== null) {
            $record[$key] = (string) $row[$index];
        }
    }
    $code = trim((string) ($record['student_id'] ?? ''));
    $email = trim((string) ($record['student_email'] ?? $record['ccc_email'] ?? ''));
    if ($code === '' && $email === '') {
        $rowErrors[] = 'CSV row ' . $csvRow . ': provide a Student ID or Student Email.';
        continue;
    }

    $idByCode = $code !== '' ? ($studentsByCode[strtolower($code)] ?? null) : null;
    $idByEmail = $email !== '' ? ($studentsByEmail[strtolower($email)] ?? null) : null;
    if (($code !== '' && $idByCode === null) || ($email !== '' && $idByEmail === null)) {
        $rowErrors[] = 'CSV row ' . $csvRow . ': the supplied student identifier was not found.';
        continue;
    }
    if ($idByCode !== null && $idByEmail !== null && $idByCode !== $idByEmail) {
        $rowErrors[] = 'CSV row ' . $csvRow . ': Student ID and email refer to different accounts.';
        continue;
    }
    $matchedStudentId = $idByCode ?? $idByEmail;
    if (isset($rowsToImport[$matchedStudentId])) {
        $rowErrors[] = 'CSV row ' . $csvRow . ': this student appears more than once.';
        continue;
    }

    $profileInput = array_intersect_key($record, array_flip($profileKeys));
    [$profileValues, $validationErrors] = student_profile_validate_values($profileInput, $profileKeys);
    if ($validationErrors) {
        $rowErrors[] = 'CSV row ' . $csvRow . ': ' . implode(' ', $validationErrors);
        continue;
    }
    $rowsToImport[$matchedStudentId] = $profileValues;
}
fclose($handle);

if ($rowErrors) {
    $details = implode(' ', array_slice($rowErrors, 0, 5));
    if (count($rowErrors) > 5) {
        $details .= ' ' . (count($rowErrors) - 5) . ' more row errors.';
    }
    $respond('Nothing was imported. ' . $details, true);
}
if (!$rowsToImport) {
    $respond('The CSV has no student profile rows to import.', true);
}

$columns = $profileKeys;
$quotedColumns = implode(', ', array_map(static fn(string $column): string => '`' . $column . '`', array_merge(['user_id'], $columns)));
$placeholders = implode(', ', array_fill(0, count($columns) + 1, '?'));
$updates = implode(', ', array_map(
    static fn(string $column): string => '`' . $column . '` = VALUES(`' . $column . '`)',
    $columns
));

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare(
        "INSERT INTO student_profiles ($quotedColumns) VALUES ($placeholders)
         ON DUPLICATE KEY UPDATE $updates"
    );
    foreach ($rowsToImport as $studentId => $profileValues) {
        $stmt->execute(array_merge([(int) $studentId], array_values($profileValues)));
    }
    $pdo->commit();
    $respond('Imported or updated ' . count($rowsToImport) . ' student profile(s).', false);
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Student profile CSV import failed: ' . $exception->getMessage());
    $respond('The import failed and no profiles were changed. Please try again.', true);
}
