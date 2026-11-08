<?php
require_once __DIR__ . '/../includes/teacher_init.php';

$pageTitle = 'Learning Materials';
$activePage = 'materials';
$selectedClassId = teacher_selected_class_id($classes);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    teacher_verify_csrf();
    $classId = filter_var($_POST['class_id'] ?? null, FILTER_VALIDATE_INT);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $classIds = array_map('intval', array_column($classes, 'id'));
    $returnClassId = $classId && in_array((int) $classId, $classIds, true)
        ? (int) $classId
        : $selectedClassId;
    if (!$classId || !in_array($classId, $classIds, true) || $title === '') {
        teacher_flash_redirect(teacher_class_page('learning-materials.php', $returnClassId), 'Select your class and enter a material title.', true);
    }
    try {
        $filePath = store_safe_upload($_FILES['material'] ?? [], 'study_materials');
        if ($filePath === '') {
            teacher_flash_redirect(teacher_class_page('learning-materials.php', $classId), 'Choose a file to upload.', true);
        }
        $stmt = $pdo->prepare(
            'INSERT INTO study_materials (class_id, title, description, file_path, uploaded_by)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$classId, $title, $description, $filePath, $user['id']]);
        teacher_flash_redirect(teacher_class_page('learning-materials.php', $classId), 'Learning material uploaded successfully.');
    } catch (RuntimeException $exception) {
        teacher_flash_redirect(teacher_class_page('learning-materials.php', $classId), $exception->getMessage(), true);
    } catch (PDOException $exception) {
        error_log('Teacher material upload failed: ' . $exception->getMessage());
        teacher_flash_redirect(teacher_class_page('learning-materials.php', $classId), 'The material could not be saved. Please try again.', true);
    }
}

if ($selectedClassId !== null) {
    $stmt = $pdo->prepare(
        'SELECT m.title, m.description, m.file_path, m.created_at, c.code
         FROM study_materials m JOIN classes c ON c.id = m.class_id
         WHERE c.teacher_id = ? AND c.id = ? ORDER BY m.created_at DESC'
    );
    $stmt->execute([$user['id'], $selectedClassId]);
} else {
    $stmt = $pdo->prepare(
        'SELECT m.title, m.description, m.file_path, m.created_at, c.code
         FROM study_materials m JOIN classes c ON c.id = m.class_id
         WHERE c.teacher_id = ? ORDER BY m.created_at DESC'
    );
    $stmt->execute([$user['id']]);
}
$materials = $stmt->fetchAll();
$flash = $_SESSION['teacher_flash'] ?? null;
unset($_SESSION['teacher_flash']);
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <div class="page-header"><h2>Learning Materials</h2><p>Share course resources with your assigned classes.</p></div>
    <?php if ($flash): ?><p class="<?= !empty($flash['error']) ? 'notice error' : 'notice' ?>" role="status"><?= e($flash['message']) ?></p><?php endif; ?>
    <?php if ($classes): ?>
        <form class="card form" method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="field"><label for="class-id">Class</label><select id="class-id" name="class_id" required>
                <?php foreach ($classes as $class): ?><option value="<?= (int) $class['id'] ?>" <?= $selectedClassId === (int) $class['id'] ? 'selected' : '' ?>><?= e($class['code'] . ' — ' . $class['name']) ?></option><?php endforeach; ?>
            </select></div>
            <div class="field"><label for="title">Material title</label><input id="title" name="title" maxlength="200" required></div>
            <div class="field"><label for="description">Description</label><textarea id="description" name="description" rows="3"></textarea></div>
            <div class="field field-wide">
                <label for="material">Material file <span class="muted">(max 10 MB)</span></label>
                <label class="file-dropzone" for="material" data-file-drop>
                    <span class="file-drop-icon" aria-hidden="true">↑</span>
                    <strong data-file-label>Drop a file here or <span>browse files</span></strong>
                    <span>PDF, Office files, images, or ZIP</span>
                    <input id="material" name="material" type="file" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.png,.jpg,.jpeg,.zip" required>
                </label>
            </div>
            <button class="btn" type="submit">Upload Material</button>
        </form>
    <?php else: ?><p class="card">You need an assigned class before you can upload learning materials.</p><?php endif; ?>

    <h3>Uploaded Materials</h3>
    <div class="table-responsive"><table class="data-table">
        <thead><tr><th>Class</th><th>Title</th><th>Description</th><th>Uploaded</th><th>File</th></tr></thead>
        <tbody>
        <?php foreach ($materials as $material): ?>
            <tr>
                <td><?= e($material['code']) ?></td>
                <td><?= e($material['title']) ?></td>
                <td><?= e($material['description'] ?? '') ?></td>
                <td><?= e($material['created_at'] ?? '') ?></td>
                <td><a href="<?= e('../' . $material['file_path']) ?>">Download</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$materials): ?><tr><td colspan="5">No materials uploaded yet.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</main>
<script src="../assets/js/file-drop.js"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
