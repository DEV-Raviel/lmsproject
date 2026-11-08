<?php
require_once __DIR__ . '/../includes/student_init.php';

$pageTitle  = 'Lessons';
$activePage = 'my-class';
$search = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 100) : '';
$lessons = get_lessons($search);
$emptyMessage = $search !== ''
    ? 'No lessons match this search.'
    : 'Learning materials from your enrolled classes will appear here.';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <h2>Lessons</h2>
    <p>Lessons and materials shared by your teacher will appear here.</p>
    <details class="filter-disclosure" <?= $search !== '' ? 'open' : '' ?>>
        <summary>Filters</summary>
        <form class="list-filter-form" method="get">
            <label>
                <span>Search lessons</span>
                <input type="search" name="q" value="<?= e($search) ?>" maxlength="100" placeholder="Lesson or class">
            </label>
            <button class="btn" type="submit">Search</button>
            <a class="btn btn-outline" href="lessons.php">Clear</a>
        </form>
    </details>
    <?php render_table(['Lesson', 'Subject', 'Resource', 'Status'], $lessons, $emptyMessage); ?>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
