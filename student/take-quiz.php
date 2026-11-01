<?php
require_once __DIR__ . '/../includes/student_init.php';
require_once __DIR__ . '/../config/db.php';

$quizId = filter_var($_GET['id'] ?? $_POST['quiz_id'] ?? null, FILTER_VALIDATE_INT);
if (!$quizId) {
    abort_404('Quiz not found.');
}
$pdo = Database::connect();
$stmt = $pdo->prepare(
    'SELECT q.*, c.code
     FROM quizzes q
     JOIN class_enrollments ce ON ce.class_id = q.class_id AND ce.student_id = ?
     JOIN classes c ON c.id = q.class_id
     WHERE q.id = ?'
);
$stmt->execute([$user['id'], $quizId]);
$quiz = $stmt->fetch();
if (!$quiz) {
    abort_404('Quiz not found in your enrolled classes.');
}

$stmt = $pdo->prepare('SELECT * FROM quiz_attempts WHERE quiz_id = ? AND student_id = ?');
$stmt->execute([$quizId, $user['id']]);
$attempt = $stmt->fetch();
if (!$attempt && strtotime($quiz['deadline']) > time()) {
    $stmt = $pdo->prepare(
        'INSERT IGNORE INTO quiz_attempts (quiz_id, student_id, score, max_score)
         SELECT ?, ?, 0, COALESCE(SUM(points), 0) FROM quiz_questions WHERE quiz_id = ?'
    );
    $stmt->execute([$quizId, $user['id'], $quizId]);
    $stmt = $pdo->prepare('SELECT * FROM quiz_attempts WHERE quiz_id = ? AND student_id = ?');
    $stmt->execute([$quizId, $user['id']]);
    $attempt = $stmt->fetch();
}
if (!$attempt) {
    $_SESSION['quiz_flash'] = ['message' => 'The quiz deadline has passed.', 'error' => true];
    header('Location: quizzes.php');
    exit;
}
if (empty($attempt['submitted_at']) && strtotime($quiz['deadline']) <= time()) {
    $stmt = $pdo->prepare('UPDATE quiz_attempts SET submitted_at = NOW() WHERE id = ? AND submitted_at IS NULL');
    $stmt->execute([$attempt['id']]);
    $_SESSION['quiz_flash'] = ['message' => 'The quiz deadline passed before your attempt was submitted.', 'error' => true];
    header('Location: quizzes.php');
    exit;
}

$stmt = $pdo->prepare('SELECT id, question_text, question_type, points, correct_answer, position FROM quiz_questions WHERE quiz_id = ? ORDER BY position');
$stmt->execute([$quizId]);
$questions = $stmt->fetchAll();
if (!$questions) {
    abort_404('This quiz does not have any questions yet.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($attempt['submitted_at'])) {
    if (!isset($_POST['csrf'], $_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], (string) $_POST['csrf'])) {
        $_SESSION['quiz_flash'] = ['message' => 'Your session expired. Reopen the quiz and try again.', 'error' => true];
        header('Location: quizzes.php');
        exit;
    }

    $deadlinePassed = strtotime($quiz['deadline']) <= time();
    $timeLimitPassed = $quiz['duration_minutes']
        && strtotime($attempt['started_at']) + (int) $quiz['duration_minutes'] * 60 < time();
    if ($deadlinePassed || $timeLimitPassed) {
        $stmt = $pdo->prepare('UPDATE quiz_attempts SET submitted_at = NOW() WHERE id = ? AND submitted_at IS NULL');
        $stmt->execute([$attempt['id']]);
        $_SESSION['quiz_flash'] = [
            'message' => $timeLimitPassed ? 'The quiz time limit ended. Your attempt has been submitted.' : 'The quiz deadline has passed.',
            'error' => false,
        ];
        header('Location: quizzes.php');
        exit;
    }

    $submittedAnswers = is_array($_POST['answers'] ?? null) ? $_POST['answers'] : [];
    $maxScore = array_sum(array_map(static fn(array $question): int => (int) $question['points'], $questions));
    $score = 0;
    try {
        $pdo->beginTransaction();
        $answerStmt = $pdo->prepare(
            'INSERT INTO quiz_answers (attempt_id, question_id, answer_text, is_correct, points_awarded)
             VALUES (?, ?, ?, ?, ?)'
        );
        foreach ($questions as $question) {
            $questionId = (int) $question['id'];
            $answer = $submittedAnswers[$questionId] ?? '';
            $answer = is_scalar($answer) ? trim((string) $answer) : '';
            $correct = false;
            $savedAnswer = $answer;
            if ($question['question_type'] === 'multiple_choice' && $answer !== '') {
                $optionId = filter_var($answer, FILTER_VALIDATE_INT);
                $optionStmt = $pdo->prepare(
                    'SELECT option_text, is_correct FROM quiz_options WHERE id = ? AND question_id = ?'
                );
                $optionStmt->execute([$optionId, $questionId]);
                $option = $optionStmt->fetch();
                if (!$option) {
                    throw new InvalidArgumentException('Choose a valid answer for each multiple-choice question.');
                }
                $savedAnswer = $option['option_text'];
                $correct = (bool) $option['is_correct'];
            } elseif ($question['question_type'] === 'short_answer') {
                $normalize = static fn(string $text): string => preg_replace('/\s+/u', ' ', mb_strtolower(trim($text)));
                $correct = $answer !== '' && $normalize($answer) === $normalize((string) $question['correct_answer']);
            }
            $points = $correct ? (int) $question['points'] : 0;
            $score += $points;
            $answerStmt->execute([$attempt['id'], $questionId, $savedAnswer !== '' ? $savedAnswer : null, (int) $correct, $points]);
        }
        $stmt = $pdo->prepare(
            'UPDATE quiz_attempts SET score = ?, max_score = ?, submitted_at = NOW()
             WHERE id = ? AND student_id = ? AND submitted_at IS NULL'
        );
        $stmt->execute([$score, $maxScore, $attempt['id'], $user['id']]);
        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException('This quiz attempt has already been submitted.');
        }
        $pdo->commit();
        $_SESSION['quiz_flash'] = ['message' => "Quiz submitted. Your score is $score / $maxScore.", 'error' => false];
        header('Location: quizzes.php');
        exit;
    } catch (InvalidArgumentException | RuntimeException $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['quiz_flash'] = ['message' => $exception->getMessage(), 'error' => true];
        header('Location: take-quiz.php?id=' . $quizId);
        exit;
    } catch (PDOException $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Student quiz submission failed: ' . $exception->getMessage());
        $_SESSION['quiz_flash'] = ['message' => 'Your answers could not be saved. Please try again.', 'error' => true];
        header('Location: take-quiz.php?id=' . $quizId);
        exit;
    }
}

$optionStmt = $pdo->prepare('SELECT id, option_text FROM quiz_options WHERE question_id = ? ORDER BY position');
foreach ($questions as &$question) {
    $optionStmt->execute([$question['id']]);
    $question['options'] = $question['question_type'] === 'multiple_choice' ? $optionStmt->fetchAll() : [];
}
unset($question);

$pageTitle = $quiz['title'];
$activePage = 'quizzes';
$flash = $_SESSION['quiz_flash'] ?? null;
unset($_SESSION['quiz_flash']);
$timeRemaining = $quiz['duration_minutes']
    ? max(0, strtotime($attempt['started_at']) + (int) $quiz['duration_minutes'] * 60 - time())
    : null;
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <div class="page-header">
        <p class="eyebrow"><?= e($quiz['code']) ?> · QUIZ</p>
        <h2><?= e($quiz['title']) ?></h2>
        <p><?= e($quiz['description'] ?: 'Read each question carefully, then submit your answers.') ?></p>
    </div>
    <?php if ($flash): ?><p class="<?= !empty($flash['error']) ? 'notice error' : 'notice' ?>" role="status"><?= e($flash['message']) ?></p><?php endif; ?>
    <?php if (!empty($attempt['submitted_at'])): ?>
        <div class="empty-state"><strong>Quiz submitted</strong><p>Your score: <?= (int) $attempt['score'] ?> / <?= (int) $attempt['max_score'] ?> points.</p></div>
    <?php else: ?>
        <div class="quiz-attempt-meta">
            <span>Due <?= e(date('M j, Y · g:i A', strtotime($quiz['deadline']))) ?></span>
            <?php if ($timeRemaining !== null): ?><strong id="quiz-countdown" data-seconds="<?= $timeRemaining ?>">Time remaining: <?= (int) ceil($timeRemaining / 60) ?> min</strong><?php endif; ?>
        </div>
        <form class="quiz-attempt-form" method="post" id="quiz-attempt-form">
            <?= csrf_field() ?>
            <input type="hidden" name="quiz_id" value="<?= (int) $quizId ?>">
            <?php foreach ($questions as $index => $question): ?>
                <fieldset class="question-card student-question">
                    <legend><span>Question <?= $index + 1 ?></span><span><?= (int) $question['points'] ?> <?= (int) $question['points'] === 1 ? 'point' : 'points' ?></span></legend>
                    <p class="student-question-prompt"><?= nl2br(e($question['question_text'])) ?></p>
                    <?php if ($question['question_type'] === 'multiple_choice'): ?>
                        <div class="student-answer-options">
                            <?php foreach ($question['options'] as $option): ?>
                                <label><input type="radio" name="answers[<?= (int) $question['id'] ?>]" value="<?= (int) $option['id'] ?>"> <span><?= e($option['option_text']) ?></span></label>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <label class="field"><span>Your answer</span><textarea name="answers[<?= (int) $question['id'] ?>]" rows="3"></textarea></label>
                    <?php endif; ?>
                </fieldset>
            <?php endforeach; ?>
            <div class="quiz-submit-row"><span>Your answers are saved when you submit.</span><button class="btn" type="submit">Submit Quiz</button></div>
        </form>
        <?php if ($timeRemaining !== null): ?><script src="../assets/js/quiz-timer.js"></script><?php endif; ?>
    <?php endif; ?>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
