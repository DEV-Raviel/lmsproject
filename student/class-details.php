<?php
// Class detail sub-area: class-details.php?id=1&tab=overview
require_once __DIR__ . '/../includes/student_init.php';

$class = get_class_by_id((int) ($_GET['id'] ?? 0));
if (!$class) {
    abort_404('Class not found.');
}

$classId    = (int) $class['id'];
$activeTab  = $_GET['tab'] ?? 'overview';
$pageTitle  = $class['subject'];
$activePage = 'my-class';

// Pass class context so sidebar can render class-level nav
$classContext = [
    'id'      => $classId,
    'subject' => $class['subject'],
    'section' => $class['section'],
];

// Define valid tabs
$tabs = [
    'overview'      => 'Course Overview',
    'lessons'       => 'Lessons',
    'assessments'   => 'Assessments',
    'progress'      => 'Progress',
    'colleagues'    => 'Colleagues',
    'announcements' => 'Announcements',
];

if (!isset($tabs[$activeTab])) {
    $activeTab = 'overview';
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    <!-- Breadcrumb navigation -->
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <ol>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="my-class.php">My Classes</a></li>
            <li class="current"><?= e($class['subject']) ?></li>
        </ol>
    </nav>

    <!-- Class header -->
    <div class="class-header">
        <div class="class-header-info">
            <h2><?= e($class['subject']) ?></h2>
            <p class="class-meta">Section: <?= e($class['section']) ?>  |  Instructor: <?= e($class['teacher']) ?></p>
        </div>
        <a href="my-class.php" class="btn-back">Back to My Classes</a>
    </div>

    <!-- Tab navigation -->
    <nav class="tab-nav" aria-label="Class sections">
        <?php foreach ($tabs as $key => $label): ?>
            <a href="class-details.php?id=<?= $classId ?>&tab=<?= $key ?>"
               class="tab-link <?= $key === $activeTab ? 'active' : '' ?>">
                <?= e($label) ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- Tab content -->
    <div class="tab-content">
    <?php if ($activeTab === 'overview'): ?>
        <section class="tab-panel">
            <h3>Course Overview</h3>
            <?php render_details(get_class_overview($classId)); ?>
        </section>

    <?php elseif ($activeTab === 'lessons'): ?>
        <section class="tab-panel">
            <h3>Lessons</h3>
            <p>Lessons and learning materials shared by the instructor.</p>
            <?php
            $lessonRows = [];
            foreach (get_class_lessons($classId) as $l) {
                $lessonRows[] = [
                    $l['title'],
                    $l['topic'],
                    date('M j, Y', strtotime($l['date_posted'])),
                    $l['file_path'] ? ['href' => '../' . $l['file_path'], 'text' => 'Download'] : '—',
                ];
            }
            render_table(['Lesson', 'Topic', 'Date Posted', 'File'], $lessonRows, 'No lessons posted yet.');
            ?>
        </section>

    <?php elseif ($activeTab === 'assessments'): ?>
        <section class="tab-panel">
            <h3>Assessments</h3>
            <p>Quizzes, activities, and examinations for this course.</p>
            <?php
            $assessRows = [];
            foreach (get_class_assessments($classId) as $a) {
                $action = $a['status'] === 'Pending'
                    ? [
                        'href' => $a['item_type'] === 'quiz'
                            ? 'take-quiz.php?id=' . (int) $a['id']
                            : 'submit-work.php?task=' . (int) $a['id'],
                        'text' => 'Open',
                    ]
                    : $a['status'];
                $assessRows[] = [$a['title'], $a['type'], $a['due_date'], $a['score'], $action];
            }
            render_table(['Assessment', 'Type', 'Due Date', 'Score', 'Status'], $assessRows, 'No assessments yet.');
            ?>
        </section>

    <?php elseif ($activeTab === 'progress'): ?>
        <section class="tab-panel">
            <h3>Academic Progress</h3>
            <?php
            $prog = get_class_progress($classId);
            ?>
            <div class="progress-grid">
                <div class="progress-card">
                    <span class="progress-label">Attendance</span>
                    <span class="progress-value"><?= e($prog['attendance']) ?></span>
                </div>
                <div class="progress-card">
                    <span class="progress-label">Lessons Completed</span>
                    <span class="progress-value"><?= e($prog['lessons_completed']) ?></span>
                </div>
                <div class="progress-card">
                    <span class="progress-label">Assessments Passed</span>
                    <span class="progress-value"><?= e($prog['assessments_passed']) ?></span>
                </div>
                <div class="progress-card">
                    <span class="progress-label">Current Grade</span>
                    <span class="progress-value"><?= e($prog['current_grade']) ?></span>
                </div>
            </div>

            <h4>Grade Breakdown</h4>
            <?php render_details([
                'Prelim'  => $prog['prelim'],
                'Midterm' => $prog['midterm'],
                'Finals'  => $prog['finals'],
                'Remarks' => $prog['remarks'],
            ]); ?>
        </section>

    <?php elseif ($activeTab === 'colleagues'): ?>
        <section class="tab-panel">
            <h3>Classmates</h3>
            <p>Students enrolled in this section.</p>
            <?php
            $colRows = [];
            foreach (get_class_colleagues($classId) as $c) {
                $colRows[] = [$c['name'], $c['student_id'], $c['email']];
            }
            render_table(['Name', 'Student ID', 'Email'], $colRows, 'No classmates found.');
            ?>
        </section>

    <?php elseif ($activeTab === 'announcements'): ?>
        <section class="tab-panel">
            <h3>Announcements</h3>
            <p>Announcements from the instructor for this class.</p>
            <?php
            $announcements = get_class_announcements($classId);
            if (!$announcements): ?>
                <p class="empty">No announcements yet.</p>
            <?php else: ?>
                <?php foreach ($announcements as $ann): ?>
                    <article class="announcement-item">
                        <h4><?= e($ann['title']) ?></h4>
                        <p class="announcement-meta">
                            Posted by <?= e($ann['posted_by']) ?> &mdash; <?= e(time_ago($ann['created_at'])) ?>
                        </p>
                        <p><?= nl2br(e($ann['body'])) ?></p>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    <?php endif; ?>
    </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
