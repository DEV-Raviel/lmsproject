<?php
require_once __DIR__ . '/../includes/teacher_init.php';

$pageTitle = 'Teacher Dashboard';
$activePage = 'dashboard';
$classIds = array_column($classes, 'id');
$studentCount = 0;
$assignmentCount = 0;
$quizCount = 0;
$materialCount = 0;
$upcoming = [];
if ($classIds) {
    $params = array_map('intval', $classIds);
    $marks = implode(',', array_fill(0, count($params), '?'));
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT student_id) FROM class_enrollments WHERE class_id IN ($marks)");
    $stmt->execute($params);
    $studentCount = (int) $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM assignments WHERE created_by = ?");
    $stmt->execute([$user['id']]);
    $assignmentCount = (int) $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM quizzes WHERE created_by = ?");
    $stmt->execute([$user['id']]);
    $quizCount = (int) $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM study_materials WHERE uploaded_by = ?");
    $stmt->execute([$user['id']]);
    $materialCount = (int) $stmt->fetchColumn();
    $stmt = $pdo->prepare(
        "SELECT a.title, a.deadline, c.code, 'Assignment' AS activity_type FROM assignments a
         JOIN classes c ON c.id = a.class_id
         WHERE c.teacher_id = ? AND a.deadline >= NOW()
         UNION ALL
         SELECT q.title, q.deadline, c.code, 'Quiz' AS activity_type FROM quizzes q
         JOIN classes c ON c.id = q.class_id
         WHERE c.teacher_id = ? AND q.deadline >= NOW()
         ORDER BY deadline LIMIT 5"
    );
    $stmt->execute([$user['id'], $user['id']]);
    $upcoming = $stmt->fetchAll();
}
$flash = $_SESSION['teacher_flash'] ?? null;
unset($_SESSION['teacher_flash']);
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content dashboard-grid">
    <main class="dashboard-main-area card dashboard-master-card">
        <div class="page-header">
            <p class="eyebrow">TEACHING OVERVIEW</p>
            <h2>Welcome back, <?= e($user['name']) ?></h2>
            <p>Your classes, learners, and teaching tools at a glance.</p>
            <div class="dashboard-shortcuts">
                <a class="btn" href="quizzes.php">+ Create a quiz</a>
                <a class="btn btn-outline" href="assignments.php">Create assignment</a>
                <a class="btn btn-outline" href="learning-materials.php">Upload material</a>
            </div>
        </div>
        <?php if ($flash): ?>
            <p class="<?= !empty($flash['error']) ? 'notice error' : 'notice' ?>" role="status"><?= e($flash['message']) ?></p>
        <?php endif; ?>
        <div class="section-break-title">Teaching Overview</div>
        <div class="card-grid">
            <?php foreach ([
                ['MY CLASSES', count($classes), 'Assigned sections', 'dashboard', 'View Classes'],
                ['STUDENTS', $studentCount, 'Across your classes', 'students.php', 'View Students'],
                ['ASSIGNMENTS', $assignmentCount, 'Created activities', 'assignments.php', 'Manage Assignments'],
                ['QUIZZES', $quizCount, 'Knowledge checks', 'quizzes.php', 'Build a Quiz'],
                ['MATERIALS', $materialCount, 'Class resources', 'learning-materials.php', 'Manage Materials'],
            ] as [$label, $count, $caption, $href, $action]): ?>
                <a class="card sub-card metric-card status-assigned" href="<?= e($href === 'dashboard' ? '#classes' : $href) ?>">
                    <div class="metric-card-top"><span class="metric-label"><?= e($label) ?></span><span class="metric-caption"><?= e($caption) ?></span></div>
                    <div class="metric-value"><?= (int) $count ?></div>
                    <div class="metric-card-footer"><span class="metric-action"><?= e($action) ?> &rarr;</span></div>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="section-break-title" id="classes">My Classes</div>
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Class</th><th>Subject</th><th>Term</th><th>Students</th></tr></thead>
                <tbody>
                <?php foreach ($classes as $class): ?>
                    <tr>
                        <td><?= e($class['code']) ?></td>
                        <td><?= e($class['name']) ?></td>
                        <td><?= e($class['semester'] . ' / ' . $class['fiscal_year']) ?></td>
                        <td><?= (int) $class['students_count'] ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$classes): ?><tr><td colspan="4">No classes have been assigned to your account yet.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
    <aside class="bulletin-column card dashboard-master-card">
        <h3>Upcoming Deadlines</h3>
        <?php if ($upcoming): ?>
            <?php foreach ($upcoming as $item): ?>
                <article class="announcement-item">
                    <h4><?= e($item['title']) ?></h4>
                    <p class="announcement-meta"><?= e($item['activity_type']) ?> · <?= e($item['code']) ?> · <?= e(date('M j, Y g:i A', strtotime($item['deadline']))) ?></p>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No upcoming assignment deadlines.</p>
        <?php endif; ?>
        <p><a href="assignments.php">Manage assignments</a></p>
    </aside>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
