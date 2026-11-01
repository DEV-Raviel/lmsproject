<?php
require_once __DIR__ . '/../includes/student_init.php';

$pageTitle  = 'Fiscal Year';
$activePage = 'fiscal-year';
$fy         = get_fiscal_year();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <h2>Fiscal Year</h2>
    <h3>Fiscal Year: <?= e($fy['year']) ?></h3>
    <p>Current Semester: <?= e($fy['semester']) ?></p>
    <?php render_table(['Academic Period', 'Status'], get_academic_periods()); ?>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
