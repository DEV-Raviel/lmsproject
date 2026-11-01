<?php
require_once __DIR__ . '/../includes/student_init.php';
require_once __DIR__ . '/../config/db.php';

$pageTitle = 'My Quizzes';
$activePage = 'quizzes';
$pdo = Database::connect();
$stmt = $pdo->prepare(
    'SELECT q.id, q.title, q.description, q.deadline, q.duration_minutes, c.code,
            qa.score, qa.max_score, qa.submitted_at, qa.started_at
     FROM quizzes q
     JOIN classes c ON c.id = q.class_id
     JOIN class_enrollments ce ON ce.class_id = c.id AND ce.student_id = ?
     LEFT JOIN quiz_attempts qa ON qa.quiz_id = q.id AND qa.student_id = ?
     ORDER BY q.deadline'
);
$stmt->execute([$user['id'], $user['id']]);
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
    <?php if (!$quizzes): ?>
        <div class="empty-state"><strong>No quizzes are assigned yet</strong><p>Quizzes from your enrolled classes will appear here. If you think a class is missing, contact your school administrator.</p></div>
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
