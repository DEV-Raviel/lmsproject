<?php
require_once __DIR__ . '/../includes/student_init.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/announcements.php';

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';

$pdo = Database::connect();
$schoolAnnouncements = get_school_announcements($pdo, 2);
$enrolledClasses = get_classes();
$counts = get_task_counts();
$availableQuizzes = array_values(array_filter(
    array_merge(get_tasks('due-today'), get_tasks('assigned')),
    static fn(array $item): bool => $item['type'] === 'quiz'
));
$fy     = get_fiscal_year();

$activityCards = [
    ['Due today', $counts['due_today'],           'Assignments and quizzes',       'todo.php?filter=due-today',          'View tasks'],
    ['Upcoming',  $counts['assigned'],            'Assigned work',                  'todo.php?filter=assigned',           'View tasks'],
    ['Past due',  $counts['missed'],              'Needs attention',                'todo.php?filter=missed',             'Review work'],
    ['Materials', $counts['unread_lessons'],      'Class resources',                'my-class.php',                      'View classes'],
];

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-content dashboard-grid">
    <!-- Master Card Wrapper for Dashboard Content -->
    <main class="dashboard-main-area card dashboard-master-card">
        <div class="page-header">
            <h2>Welcome back, <?= e($user['name']) ?></h2>
            <p>Your classes and coursework at a glance.</p>
        </div>
        <div class="dashboard-term-bar">
            <span>Academic Year: <?= e($fy['year']) ?></span>
            <span class="term-separator">|</span>
            <span><?= e($fy['semester']) ?></span>
        </div>

        <!-- Row 1: Activities, Quizzes, and Updates -->
        <div class="section-break-title">Your work</div>
        <div class="card-grid dashboard-metrics-grid">
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
        <div class="section-break-title">Classes and quizzes</div>
        <div class="card-grid">
                <section class="card sub-card metric-card status-my-classes">
                    <div class="metric-card-top">
                        <span class="metric-label">My classes</span>
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
                        <span class="metric-label">Quizzes</span>
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
        <section class="school-announcements" aria-labelledby="school-announcements-title">
            <h3 id="school-announcements-title">School Announcements</h3>
            <?php if ($schoolAnnouncements): ?>
                <?php foreach ($schoolAnnouncements as $announcement): ?>
                    <article class="school-announcement-card">
                        <h4><?= e($announcement['title']) ?></h4>
                        <p><?= nl2br(e($announcement['body'])) ?></p>
                        <time datetime="<?= e(date('c', strtotime($announcement['created_at']))) ?>">
                            <?= e(date('M j, Y', strtotime($announcement['created_at']))) ?>
                        </time>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="empty">There are no school announcements right now.</p>
            <?php endif; ?>
        </section>
        <details class="bulletin-disclosure">
            <summary>More resources <span class="disclosure-indicator" aria-hidden="true"></span></summary>
            <?php include __DIR__ . '/../includes/widgets/bulletin-widget.php'; ?>
        </details>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>