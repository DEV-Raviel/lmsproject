<?php
require_once __DIR__ . '/../includes/student_init.php';

$pageTitle  = 'Grade Report';
$activePage = 'grade-report';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <h2>Grade Report</h2>
    <?php render_table(['Subject', 'Teacher', 'Prelim', 'Midterm', 'Final', 'Remarks'], get_grades()); ?>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
