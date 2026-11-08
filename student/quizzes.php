<?php
require_once __DIR__ . '/../includes/student_init.php';
require_once __DIR__ . '/../config/db.php';

$pageTitle = 'My Quizzes';
$activePage = 'quizzes';
$search = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 100) : '';
$statusOptions = ['all', 'available', 'in-progress', 'submitted', 'closed'];
$status = is_string($_GET['status'] ?? null) && in_array($_GET['status'], $statusOptions, true)
    ? $_GET['status']
    : 'all';
$pdo = Database::connect();
$query = 'SELECT q.id, q.title, q.description, q.deadline, q.duration_minutes, c.code,
            qa.score, qa.max_score, qa.submitted_at, qa.started_at
     FROM quizzes q
     JOIN classes c ON c.id = q.class_id
     JOIN class_enrollments ce ON ce.class_id = c.id AND ce.student_id = ?
     LEFT JOIN quiz_attempts qa ON qa.quiz_id = q.id AND qa.student_id = ?
     WHERE 1 = 1';
$parameters = [$user['id'], $user['id']];
if ($search !== '') {
    $query .= ' AND (q.title LIKE ? OR q.description LIKE ? OR c.code LIKE ?)';
    $term = '%' . $search . '%';
    array_push($parameters, $term, $term, $term);
}
$statusFilter = match ($status) {
    'available' => ' AND qa.id IS NULL AND q.deadline >= NOW()',
    'in-progress' => ' AND qa.id IS NOT NULL AND qa.submitted_at IS NULL AND q.deadline >= NOW()',
    'submitted' => ' AND qa.submitted_at IS NOT NULL',
    'closed' => ' AND qa.submitted_at IS NULL AND q.deadline < NOW()',
    default => '',
};
$query .= $statusFilter . ' ORDER BY q.deadline';
$stmt = $pdo->prepare($query);
$stmt->execute($parameters);
$quizzes = $stmt->fetchAll();
$flash = $_SESSION['quiz_flash'] ?? null;
unset($_SESSION['quiz_flash']);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <div class="page-header">
        <p class="eyebrow">YOUR LEARNING SPACE</p>
        <h2>My Quizzes</h2>
        <p>View quizzes assigned to your classes and check your results.</p>
    </div>
    <?php if ($flash): ?><p class="<?= !empty($flash['error']) ? 'notice error' : 'notice' ?>" role="status"><?= e($flash['message']) ?></p><?php endif; ?>
    <details class="filter-disclosure" <?= ($search !== '' || $status !== 'all') ? 'open' : '' ?>>
        <summary>Filters</summary>
        <form class="list-filter-form" method="get">
            <label>
                <span>Search quizzes</span>
                <input type="search" name="q" value="<?= e($search) ?>" maxlength="100" placeholder="Quiz title, instructions, or class">
            </label>
            <label>
                <span>Status</span>
                <select name="status">
                    <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All</option>
                    <option value="available" <?= $status === 'available' ? 'selected' : '' ?>>Available</option>
                    <option value="in-progress" <?= $status === 'in-progress' ? 'selected' : '' ?>>In progress</option>
                    <option value="submitted" <?= $status === 'submitted' ? 'selected' : '' ?>>Submitted</option>
                    <option value="closed" <?= $status === 'closed' ? 'selected' : '' ?>>Closed</option>
                </select>
            </label>
            <button class="btn" type="submit">Apply filters</button>
            <a class="btn btn-outline" href="quizzes.php">Clear</a>
        </form>
    </details>
    <?php if (!$quizzes && $search === '' && $status === 'all'): ?>
        <div class="empty-state"><strong>No quizzes are assigned yet</strong><p>Quizzes from your enrolled classes will appear here. If you think a class is missing, contact your school administrator.</p></div>
    <?php elseif (!$quizzes): ?>
        <div class="empty-state"><strong>No matching quizzes</strong><p>Try changing or clearing your filters.</p></div>
    <?php else: ?>
        <div class="quiz-list">
            <?php foreach ($quizzes as $quiz): ?>
                <?php
                $submitted = !empty($quiz['submitted_at']);
                $expired = strtotime($quiz['deadline']) < time();
                $started = !empty($quiz['started_at']);
                ?>
                <article class="quiz-list-card">
                    <div class="quiz-list-icon" aria-hidden="true">Q</div>
                    <div class="quiz-list-info">
                        <h3><?= e($quiz['title']) ?></h3>
                        <p><?= e($quiz['code']) ?> · Due <?= e(date('M j, Y · g:i A', strtotime($quiz['deadline']))) ?>
                            <?php if ($quiz['duration_minutes']): ?> · <?= (int) $quiz['duration_minutes'] ?> min<?php endif; ?>
                        </p>
                        <?php if ($submitted): ?>
                            <strong class="quiz-result">Score: <?= (int) $quiz['score'] ?> / <?= (int) $quiz['max_score'] ?></strong>
                        <?php endif; ?>
                    </div>
                    <div class="quiz-list-due">
                        <?php if ($submitted): ?>
                            <span>Submitted</span>
                            <strong><?= e(date('M j, g:i A', strtotime($quiz['submitted_at']))) ?></strong>
                        <?php elseif ($expired): ?>
                            <span>Closed</span><strong>Deadline passed</strong>
                        <?php else: ?>
                            <span><?= $started ? 'In progress' : 'Ready when you are' ?></span>
                            <a class="btn btn-sm" href="take-quiz.php?id=<?= (int) $quiz['id'] ?>"><?= $started ? 'Resume quiz' : 'Start quiz' ?></a>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
