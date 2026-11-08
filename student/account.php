<?php
require_once __DIR__ . '/../includes/student_init.php';

$pageTitle  = 'My Account';
$activePage = '';
$pdo = Database::connect();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/my_account.php';
?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
