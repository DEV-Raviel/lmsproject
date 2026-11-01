<?php
require_once __DIR__ . '/../includes/student_init.php';

$pageTitle  = 'Notifications';
$activePage = '';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <h2>Notifications</h2>
    <?php render_table(['Notification', 'Date'], get_notifications(), 'No notifications.'); ?>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
