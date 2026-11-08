<?php
require_once __DIR__ . '/../includes/teacher_init.php';

$pageTitle = 'Assignments';
$activePage = 'assignments';
$selectedClassId = teacher_selected_class_id($classes);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    teacher_verify_csrf();
    $classId = filter_var($_POST['class_id'] ?? null, FILTER_VALIDATE_INT);
    $title = trim($_POST['title'] ?? '');
    $instructions = trim($_POST['instructions'] ?? '');
    $deadlineInput = trim($_POST['deadline'] ?? '');
    $pointsPossibleInput = $_POST['points_possible'] ?? null;
    $pointsPossible = filter_var(
        is_scalar($pointsPossibleInput) ? (string) $pointsPossibleInput : '',
        FILTER_VALIDATE_INT
    );
    $deadline = DateTime::createFromFormat('Y-m-d\TH:i', $deadlineInput);
    $classIds = array_map('intval', array_column($classes, 'id'));
    $returnClassId = $classId && in_array((int) $classId, $classIds, true)
        ? (int) $classId
        : $selectedClassId;
    if (!$classId || !in_array($classId, $classIds, true) || $title === '' || !$deadline
        || $deadline <= new DateTime() || $pointsPossible === false || $pointsPossible < 1 || $pointsPossible > 10000) {
        teacher_flash_redirect(teacher_class_page('assignments.php', $returnClassId), 'Select your class, enter a title, choose a future deadline, and set points from 1 to 10,000.', true);
    }
    try {
        $filePath = store_safe_upload($_FILES['attachment'] ?? [], 'assignments');
        $stmt = $pdo->prepare(
            'INSERT INTO assignments (class_id, title, instructions, file_path, deadline, points_possible, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $classId,
            $title,
            $instructions,
            $filePath !== '' ? $filePath : null,
            $deadline->format('Y-m-d H:i:s'),
            $pointsPossible,
            $user['id'],
        ]);
        teacher_flash_redirect(teacher_class_page('assignments.php', $classId), 'Assignment created successfully.');
    } catch (RuntimeException $exception) {
        teacher_flash_redirect(teacher_class_page('assignments.php', $classId), $exception->getMessage(), true);
    } catch (PDOException $exception) {
        error_log('Teacher assignment creation failed: ' . $exception->getMessage());
        teacher_flash_redirect(teacher_class_page('assignments.php', $classId), 'The assignment could not be saved. Please try again.', true);
    }
}

if ($selectedClassId !== null) {
    $stmt = $pdo->prepare(
        'SELECT a.title, a.instructions, a.file_path, a.deadline, a.points_possible, c.code
         FROM assignments a JOIN classes c ON c.id = a.class_id
         WHERE c.teacher_id = ? AND c.id = ? ORDER BY a.deadline DESC'
    );
    $stmt->execute([$user['id'], $selectedClassId]);
} else {
    $stmt = $pdo->prepare(
        'SELECT a.title, a.instructions, a.file_path, a.deadline, a.points_possible, c.code
         FROM assignments a JOIN classes c ON c.id = a.class_id
         WHERE c.teacher_id = ? ORDER BY a.deadline DESC'
    );
    $stmt->execute([$user['id']]);
}
$assignments = $stmt->fetchAll();
$flash = $_SESSION['teacher_flash'] ?? null;
unset($_SESSION['teacher_flash']);
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <div class="page-header">
        <div><h2>Assignments</h2><p>Create activities for classes assigned to you.</p></div>
        <div class="page-actions">
            <a class="btn btn-outline" href="gradebook.php">Open gradebook</a>
            <a class="btn btn-outline" href="quizzes.php">+ Create a quiz</a>
        </div>
    </div>
    <?php if ($flash): ?><p class="<?= !empty($flash['error']) ? 'notice error' : 'notice' ?>" role="status"><?= e($flash['message']) ?></p><?php endif; ?>
    <?php if ($classes): ?>
        <form class="card form" method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="field"><label for="class-id">Class</label><select id="class-id" name="class_id" required>
                <?php foreach ($classes as $class): ?><option value="<?= (int) $class['id'] ?>" <?= $selectedClassId === (int) $class['id'] ? 'selected' : '' ?>><?= e($class['code'] . ' — ' . $class['name']) ?></option><?php endforeach; ?>
            </select></div>
            <div class="field"><label for="title">Assignment title</label><input id="title" name="title" maxlength="200" required></div>
            <div class="field"><label for="instructions">Instructions</label><textarea id="instructions" name="instructions" rows="4"></textarea></div>
            <div class="field"><label for="deadline">Deadline</label><input id="deadline" name="deadline" type="datetime-local" required></div>
            <div class="field"><label for="points-possible">Points possible</label><input id="points-possible" name="points_possible" type="number" min="1" max="10000" value="100" required></div>
            <div class="field field-wide">
                <label for="attachment">Add an attachment <span class="muted">(optional, max 10 MB)</span></label>
                <label class="file-dropzone" for="attachment" data-file-drop>
                    <span class="file-drop-icon" aria-hidden="true">↑</span>
                    <strong data-file-label>Drop a file here or <span>browse files</span></strong>
                    <span>PDF, Office files, images, or ZIP</span>
                    <input id="attachment" name="attachment" type="file" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.png,.jpg,.jpeg,.zip">
                </label>
            </div>
            <button class="btn" type="submit">Create Assignment</button>
        </form>
    <?php else: ?><p class="card">You need an assigned class before you can create assignments.</p><?php endif; ?>

    <h3>Class Assignments</h3>
    <div class="table-responsive"><table class="data-table">
        <thead><tr><th>Class</th><th>Assignment</th><th>Points</th><th>Deadline</th><th>Attachment</th></tr></thead>
        <tbody>
        <?php foreach ($assignments as $assignment): ?>
            <tr>
                <td><?= e($assignment['code']) ?></td>
                <td><strong><?= e($assignment['title']) ?></strong><?php if ($assignment['instructions']): ?><br><?= nl2br(e($assignment['instructions'])) ?><?php endif; ?></td>
                <td><?= (int) $assignment['points_possible'] ?></td>
                <td><?= e(date('M j, Y g:i A', strtotime($assignment['deadline']))) ?></td>
                <td><?php if ($assignment['file_path']): ?><a href="<?= e('../' . $assignment['file_path']) ?>">Download</a><?php else: ?>—<?php endif; ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$assignments): ?><tr><td colspan="5">No assignments created yet.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</main>
<script src="../assets/js/file-drop.js"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
