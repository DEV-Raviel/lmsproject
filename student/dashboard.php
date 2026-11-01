<?php
require_once __DIR__ . '/../includes/student_init.php';
require_once __DIR__ . '/../config/db.php';

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';

$pdo = Database::connect();
$enrolledClasses = get_classes();
$counts = get_task_counts();
$availableQuizzes = array_values(array_filter(
    array_merge(get_tasks('due-today'), get_tasks('assigned')),
    static fn(array $item): bool => $item['type'] === 'quiz'
));
$fy     = get_fiscal_year();

$activityCards = [
    ['DUE TODAY', $counts['due_today'],           'Assignments & quizzes',        'todo.php?filter=due-today',          'View Tasks'],
    ['ASSIGNED',  $counts['assigned'],            'Upcoming work',                 'todo.php?filter=assigned',           'View Tasks'],
    ['MISSED',    $counts['missed'],              'Past due work',                 'todo.php?filter=missed',             'Review Work'],
    ['MATERIALS', $counts['unread_lessons'],      'Class resources',               'my-class.php',                      'View Classes'],
];

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-content dashboard-grid">
    <!-- Master Card Wrapper for Dashboard Content -->
    <main class="dashboard-main-area card dashboard-master-card">
        <div class="page-header">
            <p class="eyebrow">YOUR LEARNING SPACE</p>
            <h2>Welcome back, <?= e($user['name']) ?></h2>
            <p>Here’s what’s happening with your classes and coursework.</p>
        </div>
        <div class="dashboard-term-bar">
            <span>Academic Year: <?= e($fy['year']) ?></span>
            <span class="term-separator">|</span>
            <span><?= e($fy['semester']) ?></span>
        </div>

        <!-- Row 1: Activities, Quizzes, and Updates -->
        <div class="section-break-title">Activities, Quizzes & Updates</div>
        <div class="card-grid">
            <?php foreach ($activityCards as [$label, $count, $caption, $href, $linkText]): ?>
                <?php
                    $statusClass = 'status-' . strtolower(str_replace(' ', '-', $label));
                ?>
                    <section class="card sub-card metric-card <?= e($statusClass) ?>">
                    <div class="metric-card-top">
                        <span class="metric-label"><?= e($label) ?></span>
                        <span class="metric-caption"><?= e($caption) ?></span>
                    </div>
                    <div class="metric-value"><?= (int) $count ?></div>
                    <div class="metric-card-footer">
                            <a class="metric-action" href="<?= e($href) ?>"><?= e($linkText) ?> &rarr;</a>
                    </div>
                    </section>
            <?php endforeach; ?>
        </div>

        <!-- Row 2: Assessments -->
        <div class="section-break-title">Your Classes &amp; Quizzes</div>
        <div class="card-grid">
                <section class="card sub-card metric-card status-my-classes">
                    <div class="metric-card-top">
                        <span class="metric-label">MY CLASSES</span>
                        <span class="metric-caption">Enrolled classes</span>
                    </div>
                    <div class="metric-value"><?= count($enrolledClasses) ?></div>
                    <?php if (!$enrolledClasses): ?>
                        <p class="empty">You don’t have any classes yet. Please ask your school administrator to enroll you in a class.</p>
                    <?php else: ?>
                        <div class="student-dashboard-classes">
                            <?php foreach (array_slice($enrolledClasses, 0, 4) as $class): ?>
                                <a class="student-class-link" href="class-details.php?id=<?= (int) $class['id'] ?>">
                                    <span class="student-class-code"><?= e($class['section']) ?></span>
                                    <span><strong><?= e($class['subject']) ?></strong><small><?= e($class['teacher'] ?? 'Instructor not assigned') ?></small></span>
                                    <span aria-hidden="true">→</span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="metric-card-footer">
                        <a class="metric-action" href="my-class.php">View Classes &rarr;</a>
                    </div>
                </section>

                <section class="card sub-card metric-card status-quizzes">
                        <div class="metric-card-top">
                        <span class="metric-label">QUIZZES</span>
                        <span class="metric-caption">Open quizzes</span>
                    </div>
                    <div class="metric-value"><?= count($availableQuizzes) ?></div>
                    <?php if (!$availableQuizzes): ?>
                        <p class="empty">You’re all caught up. New quizzes will appear here.</p>
                    <?php else: ?>
                        <div class="student-dashboard-quizzes">
                            <?php foreach (array_slice($availableQuizzes, 0, 4) as $quiz): ?>
                                <a class="student-quiz-link" href="take-quiz.php?id=<?= (int) $quiz['id'] ?>">
                                    <span><strong><?= e($quiz['title']) ?></strong><small><?= e($quiz['subject']) ?> · Due <?= e($quiz['deadline']) ?></small></span>
                                    <span aria-hidden="true">→</span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="metric-card-footer">
                        <a class="metric-action" href="quizzes.php">View Quizzes &rarr;</a>
                    </div>
                </section>
            </div>
    </main>

    <!-- Right Side: Bulletin Widget with Hide Option -->
    <div class="bulletin-column card dashboard-master-card">
        <div class="bulletin-toggle-container">
            <button type="button" class="btn-toggle-bulletin" id="toggleBulletinBtn" onclick="toggleBulletin()">Hide Bulletin</button>
        </div>
        <div id="bulletinWrapper" class="bulletin-wrapper">
            <?php include __DIR__ . '/../includes/widgets/bulletin-widget.php'; ?>
        </div>
    </div>
</div>

<script>
    function toggleBulletin() {
        const wrapper = document.getElementById('bulletinWrapper');
        const btn = document.getElementById('toggleBulletinBtn');

        if (wrapper.classList.contains('hidden')) {
            wrapper.classList.remove('hidden');
            btn.textContent = 'Hide Bulletin';
        } else {
            wrapper.classList.add('hidden');
            btn.textContent = 'Show Bulletin';
        }
    }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>