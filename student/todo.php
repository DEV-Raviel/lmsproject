<?php
require_once __DIR__ . '/../includes/student_init.php';

$pageTitle  = 'To Do';
$activePage = 'todo';

// 1. Get the current filter from the URL (default to 'all' if not set)
$currentFilter = $_GET['filter'] ?? 'all';

// 2. Fetch tasks from all categories
// Assuming your system supports 'missed'. If not, you can adjust or add it to get_tasks()
$dueToday = function_exists('get_tasks') ? get_tasks('due-today') : [];
$assigned = function_exists('get_tasks') ? get_tasks('assigned') : [];
$missed   = function_exists('get_tasks') ? get_tasks('missed') : [];

// Combine all tasks for the 'all' view and sort by deadline (closest first)
$allTasks = array_merge($missed, $dueToday, $assigned);
usort($allTasks, function($a, $b) {
    $timeA = !empty($a['deadline']) ? strtotime($a['deadline']) : PHP_INT_MAX;
    $timeB = !empty($b['deadline']) ? strtotime($b['deadline']) : PHP_INT_MAX;
    return $timeA <=> $timeB;
});

// 3. Select which rows to display based on the dropdown choice
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

    <!-- Google Classroom Style Dropdown Filter -->
    <div class="todo-filter-container" style="margin-bottom: 20px;">
        <label for="todoFilter" style="font-weight: bold; margin-right: 8px;">Filter:</label>
        <select id="todoFilter" onchange="location = this.value;" style="padding: 6px 12px; border-radius: 4px; border: 1px solid #ccc;">
            <option value="?filter=all" <?php echo $currentFilter === 'all' ? 'selected' : ''; ?>>All (Sorted by deadline)</option>
            <option value="?filter=due-today" <?php echo $currentFilter === 'due-today' ? 'selected' : ''; ?>>Due Today</option>
            <option value="?filter=assigned" <?php echo $currentFilter === 'assigned' ? 'selected' : ''; ?>>Assigned</option>
            <option value="?filter=missed" <?php echo $currentFilter === 'missed' ? 'selected' : ''; ?>>Missed</option>
        </select>
    </div>

    <h3>Activity and Quiz</h3>
    <?php
    if (!empty($rows)) {
        render_table(['Task', 'Subject', 'Due Date', 'Status'], $rows);
    } else {
        echo '<p style="color: #666; font-style: italic;">No tasks found for this filter.</p>';
    }
    ?>

    <h3>Quizzes</h3>
    <p>
        <a href="tasks.php?view=due-assessment">View Quizzes Due Today</a>
        <a href="tasks.php?view=assigned-assessment">View Assigned Quizzes</a>
    </p>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>