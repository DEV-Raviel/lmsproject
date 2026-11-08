<?php
require_once __DIR__ . '/../includes/student_init.php';

$pageTitle  = 'Grade Report';
$activePage = 'grade-report';
$search = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 100) : '';
$statusOptions = ['Not submitted', 'Awaiting grade', 'Graded', 'Not started', 'In progress'];
$status = is_string($_GET['status'] ?? null) && in_array($_GET['status'], $statusOptions, true)
    ? $_GET['status']
    : '';
$grades = get_grades($search, $status);
$emptyMessage = $search !== '' || $status !== ''
    ? 'No assessments match these filters.'
    : 'Assessment scores will appear here when your classes have coursework.';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <div class="page-header">
        <h2>Grade Report</h2>
        <p>Assessment results and submission status for your enrolled classes.</p>
    </div>
    <details class="filter-disclosure" <?= ($search !== '' || $status !== '') ? 'open' : '' ?>>
        <summary>Filters</summary>
        <form class="list-filter-form" method="get">
            <label>
                <span>Search assessments</span>
                <input type="search" name="q" value="<?= e($search) ?>" maxlength="100" placeholder="Assessment, teacher, or class">
            </label>
            <label>
                <span>Status</span>
                <select name="status">
                    <option value="">All statuses</option>
                    <?php foreach ($statusOptions as $option): ?>
                        <option value="<?= e($option) ?>" <?= $status === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button class="btn" type="submit">Apply filters</button>
            <a class="btn btn-outline" href="grade-report.php">Clear</a>
        </form>
    </details>
    <?php render_table(
        ['Class', 'Teacher', 'Assessment', 'Type', 'Score', 'Status', 'Due'],
        $grades,
        $emptyMessage
    ); ?>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
