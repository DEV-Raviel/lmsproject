<?php
require_once __DIR__ . '/../includes/student_init.php';

$pageTitle  = 'To Do';
$activePage = 'todo';

// Keep the filter vocabulary constrained to the views shown in the form.
$currentFilter = is_string($_GET['filter'] ?? null) ? $_GET['filter'] : 'all';
if (!in_array($currentFilter, ['all', 'due-today', 'assigned', 'missed'], true)) {
    $currentFilter = 'all';
}
$search = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 100) : '';

// Fetch the supported task categories.
$dueToday = get_tasks('due-today', $search);
$assigned = get_tasks('assigned', $search);
$missed   = get_tasks('missed', $search);

// Combine all tasks for the 'all' view and sort by deadline (closest first)
$allTasks = array_merge($missed, $dueToday, $assigned);
usort($allTasks, function($a, $b) {
    $timeA = !empty($a['deadline']) ? strtotime($a['deadline']) : PHP_INT_MAX;
    $timeB = !empty($b['deadline']) ? strtotime($b['deadline']) : PHP_INT_MAX;
    return $timeA <=> $timeB;
});

// Select the requested view.
$displayTasks = [];
switch ($currentFilter) {
    case 'due-today':
        $displayTasks = $dueToday;
        break;
    case 'assigned':
        $displayTasks = $assigned;
        break;
    case 'missed':
        $displayTasks = $missed;
        break;
    case 'all':
    default:
        $displayTasks = $allTasks;
        break;
}

// Format rows for your render_table function
$rows = [];
foreach ($displayTasks as $t) {
    $rows[] = [
        $t['title'] ?? '',
        $t['subject'] ?? '',
        $t['deadline'] ?? '',
        $t['type'] === 'quiz'
            ? ['href' => 'take-quiz.php?id=' . (int) $t['id'], 'text' => 'Take Quiz']
            : ['href' => 'submit-work.php?task=' . (int) $t['id'], 'text' => $t['status'] ?? 'Open'],
    ];
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <h2>To Do</h2>

    <details class="filter-disclosure" <?= ($currentFilter !== 'all' || $search !== '') ? 'open' : '' ?>>
        <summary>Filters</summary>
        <form class="list-filter-form" method="get">
            <label>
                <span>Search tasks</span>
                <input type="search" name="q" value="<?= e($search) ?>" maxlength="100" placeholder="Task or class">
            </label>
            <label>
                <span>Due date</span>
                <select name="filter">
                    <option value="all" <?= $currentFilter === 'all' ? 'selected' : '' ?>>All</option>
                    <option value="due-today" <?= $currentFilter === 'due-today' ? 'selected' : '' ?>>Due today</option>
                    <option value="assigned" <?= $currentFilter === 'assigned' ? 'selected' : '' ?>>Upcoming</option>
                    <option value="missed" <?= $currentFilter === 'missed' ? 'selected' : '' ?>>Past due</option>
                </select>
            </label>
            <button class="btn" type="submit">Apply filters</button>
            <a class="btn btn-outline" href="todo.php">Clear</a>
        </form>
    </details>

    <h3>Activity and Quiz</h3>
    <?php render_table(
        ['Task', 'Subject', 'Due Date', 'Status'],
        $rows,
        $search !== '' || $currentFilter !== 'all' ? 'No tasks match these filters.' : 'You are all caught up.'
    ); ?>

    <h3>Quizzes</h3>
    <p>
        <a href="tasks.php?view=due-assessment">View Quizzes Due Today</a>
        <a href="tasks.php?view=assigned-assessment">View Assigned Quizzes</a>
    </p>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>