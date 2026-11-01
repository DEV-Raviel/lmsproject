<?php
require_once __DIR__ . '/../includes/student_init.php';

$pageTitle  = 'Lessons';
$activePage = 'my-class';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <h2>Lessons</h2>
    <p>Lessons and materials shared by your teacher will appear here.</p>
    <?php render_table(['Lesson', 'Subject', 'Resource', 'Status'], get_lessons()); ?>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
