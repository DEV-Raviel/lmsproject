<?php
// One page for: due-today, assigned, missed, due-assessment, assigned-assessment
// Usage: tasks.php?view=missed
require_once __DIR__ . '/../includes/student_init.php';

$view = $_GET['view'] ?? 'due-today';
if (!isset(TASK_VIEWS[$view])) {
    $view = 'due-today';
}
$config = TASK_VIEWS[$view];

$pageTitle  = $config['title'];
$activePage = 'todo';

$rows = [];
foreach (get_tasks($view) as $t) {
    $last = $view === 'missed'
        ? 'Missed'
        : [
            'href' => ($t['type'] === 'quiz' ? 'take-quiz.php?id=' : 'submit-work.php?task=') . (int) $t['id'],
            'text' => $t['type'] === 'quiz' ? 'Take Quiz' : 'Submit Work',
        ];
    $rows[] = [$t['title'], $t['subject'], $t['deadline'], $last];
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <h2><?= e($config['title']) ?></h2>
    <?php render_table(['Task', 'Subject', 'Deadline', $config['last_col']], $rows); ?>
    <p><a href="todo.php">Back to To Do</a></p>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
