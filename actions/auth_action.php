<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        header('Location: ../index.php?error=missing_fields');
        exit;
    }

    $pdo = Database::connect();
    if (!$pdo) {
        die("Database connection failed.");
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE LOWER(user_code) = LOWER(:code) OR LOWER(email) = LOWER(:email) LIMIT 1");
    $stmt->execute(['code' => $identifier, 'email' => $identifier]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        header('Location: ../index.php?error=invalid_credentials');
        exit;
    }

    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => $user['id'],
        'user_code' => $user['user_code'],
        'name' => $user['full_name'],
        'email' => $user['email'],
        'role' => $user['role']
    ];

    match ($user['role']) {
        'admin' => header('Location: ../admin/dashboard.php'),
        'dean' => header('Location: ../admin/manage_announcements.php'),
        'teacher' => header('Location: ../teacher/dashboard.php'),
        'student' => header('Location: ../student/dashboard.php'),
        default => header('Location: ../index.php'),
    };
    exit;
}