<?php
require_once __DIR__ . '/../includes/teacher_init.php';

$pageTitle = 'My Students';
$activePage = 'students';
$stmt = $pdo->prepare(
    "SELECT DISTINCT u.user_code, u.full_name, u.email, c.code
     FROM class_enrollments ce
     JOIN users u ON u.id = ce.student_id AND u.role = 'student'
     JOIN classes c ON c.id = ce.class_id
     WHERE c.teacher_id = ?
     ORDER BY c.code, u.full_name"
);
$stmt->execute([$user['id']]);
$students = $stmt->fetchAll();
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <div class="page-header"><h2>My Students</h2><p>Enrolled learners in your assigned classes.</p></div>
    <div class="table-responsive"><table class="data-table">
        <thead><tr><th>No.</th><th>Student ID</th><th>Student Name</th><th>Email</th><th>Class</th></tr></thead>
        <tbody>
        <?php foreach ($students as $index => $student): ?>
            <tr><td><?= $index + 1 ?></td><td><?= e($student['user_code']) ?></td><td><?= e($student['full_name']) ?></td><td><?= e($student['email']) ?></td><td><?= e($student['code']) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$students): ?><tr><td colspan="5">No students are enrolled in your classes yet.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
