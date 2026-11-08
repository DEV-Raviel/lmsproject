<?php
require_once __DIR__ . '/../includes/student_init.php';

$pageTitle  = 'Grade Report';
$activePage = 'grade-report';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <div class="page-header">
        <h2>Grade Report</h2>
        <p>Assessment results and submission status for your enrolled classes.</p>
    </div>
    <?php render_table(
        ['Class', 'Teacher', 'Assessment', 'Type', 'Score', 'Status', 'Due'],
        get_grades(),
        'Assessment scores will appear here when your classes have coursework.'
    ); ?>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
