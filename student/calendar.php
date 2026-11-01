<?php
require_once __DIR__ . '/../includes/student_init.php';

$pageTitle  = 'Calendar of Events';
$activePage = 'calendar';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <h2>Calendar of Events</h2>
    <?php render_table(['Date', 'Event'], get_calendar_events(), 'No events yet.'); ?>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
