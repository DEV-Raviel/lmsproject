<?php
// includes/student_init.php - first line of every student page
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/student_data.php';
require_once __DIR__ . '/../config/db.php';

$user     = require_role('student');
$basePath = '../';   // student pages sit one level below the project root
