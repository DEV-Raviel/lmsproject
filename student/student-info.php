<?php
// Replaces both registration.html and student-info.html
require_once __DIR__ . '/../includes/student_init.php';

$pageTitle  = 'Student Information';
$activePage = 'student-info';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <h2>Student Profile</h2>
    <h3>Personal Information</h3>
    <?php render_details(get_student_profile()); ?>

    <h3>Academic Information</h3>
    <?php render_details(get_academic_info()); ?>

    <h3>Enrolled Subjects</h3>
<?php
$subjects = get_enrolled_subjects();
render_table(['Subject Code', 'Subject Description', 'Instructor', 'Units', 'Schedule'], $subjects);
?>
    <p><strong>Total Units:</strong> <?= (int) array_sum(array_column($subjects, 3)) ?></p>

    <h3>Guardian Information</h3>
    <?php render_details(get_guardian()); ?>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
