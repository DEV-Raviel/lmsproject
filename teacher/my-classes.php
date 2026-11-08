<?php
require_once __DIR__ . '/../includes/teacher_init.php';

$pageTitle = 'My Classes';
$activePage = 'classes';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <div class="page-header">
        <p class="eyebrow">TEACHER TOOLS</p>
        <h2>My Classes</h2>
        <p>Open a class to work with its students, assignments, quizzes, and learning materials.</p>
    </div>

    <?php if ($classes): ?>
        <div class="teacher-class-grid">
            <?php foreach ($classes as $class): ?>
                <?php $classId = (int) $class['id']; ?>
                <article class="teacher-class-card">
                    <div class="teacher-class-card-header">
                        <span class="teacher-class-code"><?= e($class['code']) ?></span>
                        <span class="teacher-class-student-count">
                            <?= (int) $class['students_count'] ?> <?= (int) $class['students_count'] === 1 ? 'student' : 'students' ?>
                        </span>
                    </div>
                    <h3><?= e($class['name']) ?></h3>
                    <p class="teacher-class-term"><?= e($class['semester'] . ' / ' . $class['fiscal_year']) ?></p>
                    <nav class="teacher-class-actions" aria-label="Actions for <?= e($class['code']) ?>">
                        <a href="<?= e(teacher_class_page('students.php', $classId)) ?>">View students</a>
                        <a href="<?= e(teacher_class_page('assignments.php', $classId)) ?>">Assignments</a>
                        <a href="<?= e(teacher_class_page('quizzes.php', $classId)) ?>">Quizzes</a>
                        <a href="<?= e(teacher_class_page('learning-materials.php', $classId)) ?>">Materials</a>
                    </nav>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <strong>No classes assigned yet</strong>
            <p>Ask an administrator to assign a class to your teacher account. It will appear here when ready.</p>
        </div>
    <?php endif; ?>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
