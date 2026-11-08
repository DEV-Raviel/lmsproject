<?php
// One page for: due-today, assigned, missed, due-assessment, assigned-assessment
// Usage: tasks.php?view=missed
require_once __DIR__ . '/../includes/student_init.php';

$view = $_GET['view'] ?? 'due-today';
if (!isset(TASK_VIEWS[$view])) {
    $view = 'due-today';
}
$config = TASK_VIEWS[$view];
$search = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 100) : '';

$pageTitle  = $config['title'];
$activePage = 'todo';

$rows = [];
foreach (get_tasks($view, $search) as $t) {
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
    <details class="filter-disclosure" <?= $search !== '' ? 'open' : '' ?>>
        <summary>Filters</summary>
        <form class="list-filter-form" method="get">
            <input type="hidden" name="view" value="<?= e($view) ?>">
            <label>
                <span>Search tasks</span>
                <input type="search" name="q" value="<?= e($search) ?>" maxlength="100" placeholder="Task or class">
            </label>
            <button class="btn" type="submit">Search</button>
            <a class="btn btn-outline" href="tasks.php?view=<?= e(rawurlencode($view)) ?>">Clear</a>
        </form>
    </details>
    <?php render_table(
        ['Task', 'Subject', 'Deadline', $config['last_col']],
        $rows,
        $search !== '' ? 'No tasks match this search.' : 'No tasks are available in this category.'
    ); ?>
    <p><a href="todo.php">Back to To Do</a></p>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
