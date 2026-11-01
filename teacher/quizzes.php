<?php
require_once __DIR__ . '/../includes/teacher_init.php';

$pageTitle = 'Quiz Builder';
$activePage = 'quizzes';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    teacher_verify_csrf();
    $classId = filter_var(is_scalar($_POST['class_id'] ?? null) ? (string) $_POST['class_id'] : '', FILTER_VALIDATE_INT);
    $title = is_string($_POST['title'] ?? null) ? trim($_POST['title']) : '';
    $description = is_string($_POST['description'] ?? null) ? trim($_POST['description']) : '';
    $deadlineInput = is_string($_POST['deadline'] ?? null) ? trim($_POST['deadline']) : '';
    $durationInput = is_string($_POST['duration_minutes'] ?? null) ? trim($_POST['duration_minutes']) : '';
    $deadline = DateTime::createFromFormat('!Y-m-d\TH:i', $deadlineInput);
    $questions = $_POST['questions'] ?? [];
    $classIds = array_map('intval', array_column($classes, 'id'));

    $valid = $classId && in_array($classId, $classIds, true)
        && $title !== '' && mb_strlen($title) <= 200
        && $deadline && $deadline->format('Y-m-d\TH:i') === $deadlineInput
        && $deadline > new DateTime()
        && is_array($questions) && count($questions) >= 1 && count($questions) <= 30
        && ($durationInput === '' || (ctype_digit($durationInput) && (int) $durationInput >= 1 && (int) $durationInput <= 240));

    $preparedQuestions = [];
    if ($valid) {
        foreach (array_values($questions) as $question) {
            if (!is_array($question)) {
                $valid = false;
                break;
            }
            $prompt = is_string($question['prompt'] ?? null) ? trim($question['prompt']) : '';
            $type = is_string($question['type'] ?? null) ? $question['type'] : '';
            $points = filter_var(is_scalar($question['points'] ?? null) ? (string) $question['points'] : '', FILTER_VALIDATE_INT);
            if ($prompt === '' || mb_strlen($prompt) > 4000 || !$points || $points > 100 || !in_array($type, ['multiple_choice', 'short_answer'], true)) {
                $valid = false;
                break;
            }

            if ($type === 'multiple_choice') {
                $rawOptions = $question['options'] ?? null;
                $options = is_array($rawOptions)
                    ? array_map(static fn($option): string => is_string($option) ? trim($option) : '', $rawOptions)
                    : [];
                $correct = filter_var(is_scalar($question['correct'] ?? null) ? (string) $question['correct'] : '', FILTER_VALIDATE_INT);
                if (count($options) !== 4 || in_array('', $options, true)
                    || array_filter($options, static fn(string $option): bool => mb_strlen($option) > 500)
                    || $correct === false || $correct < 0 || $correct > 3) {
                    $valid = false;
                    break;
                }
                $preparedQuestions[] = [
                    'prompt' => $prompt,
                    'type' => $type,
                    'points' => $points,
                    'options' => $options,
                    'correct' => $correct,
                ];
            } else {
                $answer = is_string($question['answer'] ?? null) ? trim($question['answer']) : '';
                if ($answer === '' || mb_strlen($answer) > 4000) {
                    $valid = false;
                    break;
                }
                $preparedQuestions[] = [
                    'prompt' => $prompt,
                    'type' => $type,
                    'points' => $points,
                    'answer' => $answer,
                ];
            }
        }
    }

    if (!$valid) {
        teacher_flash_redirect('quizzes.php', 'Check the quiz details: add a future due date, select your class, and provide 1–30 complete questions.', true);
    }

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare(
            'INSERT INTO quizzes (class_id, title, description, deadline, duration_minutes, created_by)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $classId,
            $title,
            $description,
            $deadline->format('Y-m-d H:i:s'),
            $durationInput !== '' ? (int) $durationInput : null,
            $user['id'],
        ]);
        $quizId = (int) $pdo->lastInsertId();
        $questionStmt = $pdo->prepare(
            'INSERT INTO quiz_questions (quiz_id, question_text, question_type, points, correct_answer, position)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $optionStmt = $pdo->prepare(
            'INSERT INTO quiz_options (question_id, option_text, is_correct, position) VALUES (?, ?, ?, ?)'
        );
        foreach ($preparedQuestions as $index => $question) {
            $questionStmt->execute([
                $quizId,
                $question['prompt'],
                $question['type'],
                $question['points'],
                $question['type'] === 'short_answer' ? $question['answer'] : null,
                $index + 1,
            ]);
            if ($question['type'] === 'multiple_choice') {
                $questionId = (int) $pdo->lastInsertId();
                foreach ($question['options'] as $optionIndex => $option) {
                    $optionStmt->execute([$questionId, $option, $optionIndex === $question['correct'] ? 1 : 0, $optionIndex + 1]);
                }
            }
        }
        $pdo->commit();
        teacher_flash_redirect('quizzes.php', 'Quiz created successfully.');
    } catch (PDOException $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Teacher quiz creation failed: ' . $exception->getMessage());
        teacher_flash_redirect('quizzes.php', 'The quiz could not be saved. Check your database setup and try again.', true);
    }
}

$stmt = $pdo->prepare(
    'SELECT q.id, q.title, q.description, q.deadline, q.duration_minutes, q.created_at,
            c.code,
            (SELECT COUNT(*) FROM quiz_questions qq WHERE qq.quiz_id = q.id) AS question_count,
            (SELECT COALESCE(SUM(qq.points), 0) FROM quiz_questions qq WHERE qq.quiz_id = q.id) AS total_points
     FROM quizzes q
     JOIN classes c ON c.id = q.class_id
     WHERE q.created_by = ? AND c.teacher_id = ?
     ORDER BY q.created_at DESC'
);
$stmt->execute([$user['id'], $user['id']]);
$quizzes = $stmt->fetchAll();
$flash = $_SESSION['teacher_flash'] ?? null;
unset($_SESSION['teacher_flash']);
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <div class="page-header quiz-page-header">
        <div>
            <p class="eyebrow">TEACHER TOOLS</p>
            <h2>Quiz Builder</h2>
            <p>Create a class quiz with multiple-choice and short-answer questions.</p>
        </div>
        <a class="btn" href="#create-quiz">+ Create a quiz</a>
    </div>
    <?php if ($flash): ?><p class="<?= !empty($flash['error']) ? 'notice error' : 'notice' ?>" role="status"><?= e($flash['message']) ?></p><?php endif; ?>

    <section class="quiz-builder card" id="create-quiz">
        <div class="section-heading">
            <span class="step-number">1</span>
            <div><h3>Quiz details</h3><p>Choose the class and set a clear deadline.</p></div>
        </div>
        <?php if ($classes): ?>
            <form method="post" id="quiz-form">
                <?= csrf_field() ?>
                <div class="quiz-details-grid">
                    <div class="field">
                        <label for="quiz-title">Quiz title</label>
                        <input id="quiz-title" name="title" maxlength="200" placeholder="e.g. Chapter 3: Data Structures" required>
                    </div>
                    <div class="field">
                        <label for="quiz-class">Class</label>
                        <select id="quiz-class" name="class_id" required>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?= (int) $class['id'] ?>"><?= e($class['code'] . ' · ' . $class['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="quiz-deadline">Due date and time</label>
                        <input id="quiz-deadline" name="deadline" type="datetime-local" required>
                    </div>
                    <div class="field">
                        <label for="quiz-duration">Time limit <span class="muted">(optional)</span></label>
                        <input id="quiz-duration" name="duration_minutes" type="number" min="1" max="240" placeholder="e.g. 30 minutes">
                    </div>
                    <div class="field field-wide">
                        <label for="quiz-description">Instructions <span class="muted">(optional)</span></label>
                        <textarea id="quiz-description" name="description" rows="2" placeholder="Tell students what to expect before they begin."></textarea>
                    </div>
                </div>

                <div class="section-heading question-section-heading">
                    <span class="step-number">2</span>
                    <div><h3>Questions <span id="question-count" class="count-pill">1</span></h3><p>Add questions and set points. Each quiz needs at least one.</p></div>
                </div>
                <div id="question-list" class="question-list"></div>
                <button class="btn btn-outline" type="button" id="add-question">+ Add a question</button>
                <div class="quiz-submit-row">
                    <span id="quiz-total-points">Total points: 1</span>
                    <button class="btn" type="submit">Save Quiz</button>
                </div>
            </form>
        <?php else: ?>
            <div class="empty-state"><strong>No classes assigned yet</strong><p>Ask an administrator to assign a class to your teacher account before building a quiz.</p></div>
        <?php endif; ?>
    </section>

    <section class="quiz-list-section">
        <div class="section-heading">
            <span class="step-number">✓</span>
            <div><h3>Your quizzes</h3><p>Quizzes you have created for your classes.</p></div>
        </div>
        <?php if ($quizzes): ?>
            <div class="quiz-list">
                <?php foreach ($quizzes as $quiz): ?>
                    <article class="quiz-list-card">
                        <div class="quiz-list-icon" aria-hidden="true">Q</div>
                        <div class="quiz-list-info">
                            <h4><?= e($quiz['title']) ?></h4>
                            <p><?= e($quiz['code']) ?> <span>·</span> <?= (int) $quiz['question_count'] ?> questions <span>·</span> <?= (int) $quiz['total_points'] ?> points</p>
                        </div>
                        <div class="quiz-list-due">
                            <span><?= strtotime($quiz['deadline']) < time() ? 'Closed' : 'Due' ?></span>
                            <strong><?= e(date('M j, Y · g:i A', strtotime($quiz['deadline']))) ?></strong>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state"><strong>Your quiz list is ready</strong><p>Quizzes you create will appear here.</p></div>
        <?php endif; ?>
    </section>
</main>

<template id="question-template">
    <article class="question-card" data-question>
        <div class="question-card-top">
            <div class="question-title"><span class="question-number">Question 1</span><span class="question-points-label">1 point</span></div>
            <button type="button" class="text-button remove-question" aria-label="Remove question">Remove</button>
        </div>
        <div class="question-settings">
            <div class="field">
                <label>Question type</label>
                <select class="question-type" name="questions[__INDEX__][type]">
                    <option value="multiple_choice">Multiple choice</option>
                    <option value="short_answer">Short answer</option>
                </select>
            </div>
            <div class="field points-field">
                <label>Points</label>
                <input class="question-points" type="number" name="questions[__INDEX__][points]" min="1" max="100" value="1" required>
            </div>
        </div>
        <div class="field">
            <label>Question</label>
            <textarea name="questions[__INDEX__][prompt]" rows="2" placeholder="Write a clear question..." required></textarea>
        </div>
        <div class="question-options">
            <div class="answer-fields">
                <div class="field">
                    <label>Answer choices</label>
                    <div class="option-inputs">
                        <label><span>A</span><input name="questions[__INDEX__][options][]" placeholder="Choice A" required></label>
                        <label><span>B</span><input name="questions[__INDEX__][options][]" placeholder="Choice B" required></label>
                        <label><span>C</span><input name="questions[__INDEX__][options][]" placeholder="Choice C" required></label>
                        <label><span>D</span><input name="questions[__INDEX__][options][]" placeholder="Choice D" required></label>
                    </div>
                </div>
                <div class="field correct-answer-field">
                    <label>Correct answer</label>
                    <select name="questions[__INDEX__][correct]">
                        <option value="0">A</option><option value="1">B</option><option value="2">C</option><option value="3">D</option>
                    </select>
                </div>
            </div>
            <div class="short-answer-field field" hidden>
                <label>Expected answer <span class="muted">(for teacher reference)</span></label>
                <input name="questions[__INDEX__][answer]" placeholder="Enter the answer key">
            </div>
        </div>
    </article>
</template>
<script src="../assets/js/quiz-builder.js"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
