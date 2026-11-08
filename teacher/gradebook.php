<?php
require_once __DIR__ . '/../includes/teacher_init.php';

$pageTitle = 'Gradebook';
$activePage = 'gradebook';
$selectedClassId = teacher_selected_class_id($classes);
$search = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 100) : '';
$gradebookPage = static function (?int $classId, string $search): string {
    $query = [];
    if ($classId !== null) {
        $query['class_id'] = $classId;
    }
    if ($search !== '') {
        $query['q'] = $search;
    }
    return 'gradebook.php' . ($query ? '?' . http_build_query($query) : '');
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    teacher_verify_csrf();
    $submissionId = filter_var($_POST['submission_id'] ?? null, FILTER_VALIDATE_INT);
    $scoreInput = is_string($_POST['score'] ?? null) ? trim($_POST['score']) : '';
    $score = $scoreInput !== '' ? filter_var($scoreInput, FILTER_VALIDATE_INT) : false;

    if (!$submissionId || $score === false || $score < 0) {
        teacher_flash_redirect($gradebookPage($selectedClassId, $search), 'Enter a valid score of zero or more.', true);
    }

    try {
        $pdo->beginTransaction();
        $submissionStmt = $pdo->prepare(
            'SELECT s.id, a.points_possible, a.class_id
             FROM submissions s
             JOIN assignments a ON a.id = s.assignment_id
             JOIN classes c ON c.id = a.class_id
             WHERE s.id = ? AND c.teacher_id = ?
             FOR UPDATE'
        );
        $submissionStmt->execute([$submissionId, $user['id']]);
        $submission = $submissionStmt->fetch();
        if (!$submission) {
            $pdo->rollBack();
            teacher_flash_redirect($gradebookPage($selectedClassId, $search), 'That submission is not in one of your classes.', true);
        }
        if ($score > (int) $submission['points_possible']) {
            $pdo->rollBack();
            teacher_flash_redirect(
                $gradebookPage((int) $submission['class_id'], $search),
                'The score cannot exceed the assignment points possible.',
                true
            );
        }

        $updateStmt = $pdo->prepare('UPDATE submissions SET score = ? WHERE id = ?');
        $updateStmt->execute([$score, $submissionId]);
        $pdo->commit();
        teacher_flash_redirect(
            $gradebookPage((int) $submission['class_id'], $search),
            'Score saved successfully.'
        );
    } catch (PDOException $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Teacher submission grading failed: ' . $exception->getMessage());
        teacher_flash_redirect($gradebookPage($selectedClassId, $search), 'The score could not be saved. Please try again.', true);
    }
}

$query = 'SELECT s.id AS submission_id, s.file_path, s.score, s.submitted_at,
                 a.title AS assignment, a.points_possible, c.id AS class_id, c.code,
                 student.full_name AS student_name, student.user_code AS student_code
          FROM submissions s
          JOIN assignments a ON a.id = s.assignment_id
          JOIN classes c ON c.id = a.class_id
          JOIN users student ON student.id = s.student_id
          WHERE c.teacher_id = ?';
$parameters = [$user['id']];
if ($selectedClassId !== null) {
    $query .= ' AND c.id = ?';
    $parameters[] = $selectedClassId;
}
$query .= $search !== '' ? ' AND (student.full_name LIKE ? OR student.user_code LIKE ? OR a.title LIKE ? OR c.code LIKE ?)' : '';
if ($search !== '') {
    $term = '%' . $search . '%';
    array_push($parameters, $term, $term, $term, $term);
}
$query .= ' ORDER BY a.deadline DESC, student.full_name';
$stmt = $pdo->prepare($query);
$stmt->execute($parameters);
$submissions = $stmt->fetchAll();
$flash = $_SESSION['teacher_flash'] ?? null;
unset($_SESSION['teacher_flash']);
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <div class="page-header">
        <div>
            <p class="eyebrow">TEACHER TOOLS</p>
            <h2>Gradebook</h2>
            <p>Review submitted work and enter a score within the assignment's point limit.</p>
        </div>
    </div>
    <?php if ($flash): ?><p class="<?= !empty($flash['error']) ? 'notice error' : 'notice' ?>" role="status"><?= e($flash['message']) ?></p><?php endif; ?>

    <details class="filter-disclosure" <?= ($search !== '' || $selectedClassId !== null) ? 'open' : '' ?>>
        <summary>Filters</summary>
        <form class="list-filter-form" method="get">
            <label>
                <span>Search submissions</span>
                <input type="search" name="q" value="<?= e($search) ?>" maxlength="100" placeholder="Student, assignment, or class">
            </label>
            <?php if ($classes): ?>
                <label>
                    <span>Class</span>
                    <select id="gradebook-class" name="class_id">
                        <option value="">All my classes</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?= (int) $class['id'] ?>" <?= $selectedClassId === (int) $class['id'] ? 'selected' : '' ?>>
                                <?= e($class['code'] . ' — ' . $class['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            <?php endif; ?>
            <button class="btn" type="submit">Apply filters</button>
            <a class="btn btn-outline" href="gradebook.php">Clear</a>
        </form>
    </details>

    <section class="table-responsive" aria-label="Student submissions">
        <table class="data-table">
            <thead>
                <tr><th>Class</th><th>Student</th><th>Assignment</th><th>Submitted</th><th>File</th><th>Score</th></tr>
            </thead>
            <tbody>
            <?php foreach ($submissions as $submission): ?>
                <tr>
                    <td><?= e($submission['code']) ?></td>
                    <td><strong><?= e($submission['student_name']) ?></strong><br><span class="muted"><?= e($submission['student_code']) ?></span></td>
                    <td><?= e($submission['assignment']) ?></td>
                    <td><?= e(date('M j, Y g:i A', strtotime($submission['submitted_at']))) ?></td>
                    <td>
                        <?php if ($submission['file_path']): ?>
                            <a href="../actions/download_submission.php?id=<?= (int) $submission['submission_id'] ?>">Download</a>
                        <?php else: ?>No file<?php endif; ?>
                    </td>
                    <td>
                        <form class="grade-entry" method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="submission_id" value="<?= (int) $submission['submission_id'] ?>">
                            <input
                                id="score-<?= (int) $submission['submission_id'] ?>"
                                aria-label="Score for <?= e($submission['student_name'] . ', ' . $submission['assignment']) ?>"
                                name="score"
                                type="number"
                                min="0"
                                max="<?= (int) $submission['points_possible'] ?>"
                                step="1"
                                value="<?= $submission['score'] === null ? '' : (int) $submission['score'] ?>"
                                placeholder="0–<?= (int) $submission['points_possible'] ?>"
                                required
                            >
                            <span>/ <?= (int) $submission['points_possible'] ?></span>
                            <button class="btn btn-sm" type="submit">Save</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$submissions): ?><tr><td colspan="6"><?= $search !== '' || $selectedClassId !== null ? 'No submissions match these filters.' : 'No submissions in your classes yet.' ?></td></tr><?php endif; ?>
            </tbody>
        </table>
    </section>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
