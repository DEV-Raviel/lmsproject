<?php

$databaseName = getenv('LMS_DB_NAME') ?: 'eguro_lms_test';
if (!str_ends_with($databaseName, '_test')) {
    fwrite(STDERR, "Tests require an isolated database whose name ends with _test.\n");
    exit(2);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/student_data.php';
