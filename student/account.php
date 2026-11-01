<?php
require_once __DIR__ . '/../includes/student_init.php';

$pageTitle  = 'My Account';
$activePage = '';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <h2>My Account</h2>
    <?php render_details([
        'Name'       => $user['name'] ?? '',
        'Student ID' => $user['student_id'] ?? '',
        'Role'       => ucfirst($user['role'] ?? ''),
    ]); ?>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
