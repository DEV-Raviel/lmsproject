<?php
require_once __DIR__ . '/../includes/student_init.php';

$pageTitle  = 'Submit Work';
$activePage = 'todo';
$taskId     = filter_var($_GET['task'] ?? null, FILTER_VALIDATE_INT);
$pdo = Database::connect();
$stmt = $pdo->prepare(
    'SELECT a.id, a.title, a.instructions, a.deadline, c.code
     FROM assignments a
     JOIN classes c ON c.id = a.class_id
     JOIN class_enrollments ce ON ce.class_id = a.class_id AND ce.student_id = ?
     WHERE a.id = ?'
);
$stmt->execute([$user['id'], $taskId]);
$assignment = $stmt->fetch();
if (!$assignment) {
    abort_404('Assignment not found in your enrolled classes.');
}
$stmt = $pdo->prepare('SELECT file_path, submitted_at FROM submissions WHERE assignment_id = ? AND student_id = ? LIMIT 1');
$stmt->execute([$taskId, $user['id']]);
$submission = $stmt->fetch();
$flash = $_SESSION['submission_flash'] ?? null;
unset($_SESSION['submission_flash']);
$canSubmit = !$submission && strtotime($assignment['deadline']) >= time();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <div class="page-header">
        <p class="eyebrow"><?= e($assignment['code']) ?> · ASSIGNMENT</p>
        <h2><?= e($assignment['title']) ?></h2>
        <p>Due <?= e(date('M j, Y · g:i A', strtotime($assignment['deadline']))) ?></p>
    </div>
    <?php if ($flash): ?><p class="<?= !empty($flash['error']) ? 'notice error' : 'notice' ?>" role="status"><?= e($flash['message']) ?></p><?php endif; ?>
    <?php if ($assignment['instructions']): ?><div class="card"><h3>Instructions</h3><p><?= nl2br(e($assignment['instructions'])) ?></p></div><?php endif; ?>
    <?php if ($submission): ?>
        <div class="empty-state"><strong>Work submitted</strong><p>Submitted <?= e(date('M j, Y · g:i A', strtotime($submission['submitted_at']))) ?>. <a href="<?= e('../' . $submission['file_path']) ?>">Download your submission</a></p></div>
    <?php elseif (!$canSubmit): ?>
        <div class="empty-state"><strong>Submissions are closed</strong><p>The deadline for this assignment has passed.</p></div>
    <?php else: ?>
        <form class="card form" action="../actions/assignment_actions.php" method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="task_id" value="<?= (int) $taskId ?>">
            <div class="field field-wide">
                <label for="file">Your work <span class="muted">(max 10 MB)</span></label>
                <label class="file-dropzone" for="file" data-file-drop>
                    <span class="file-drop-icon" aria-hidden="true">↑</span>
                    <strong data-file-label>Drop your work here or <span>browse files</span></strong>
                    <span>PDF, Office files, images, or ZIP</span>
                    <input type="file" id="file" name="file" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.png,.jpg,.jpeg,.zip" required>
                </label>
            </div>
            <button type="submit" class="btn">Submit Work</button>
        </form>
        <script src="../assets/js/file-drop.js"></script>
    <?php endif; ?>
    <p><a href="todo.php">Back to To Do</a></p>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
