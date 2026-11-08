<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/student_profile.php';
require_once __DIR__ . '/../config/db.php';

$user = require_role('admin');
$pdo = Database::connect();
$flash = $_SESSION['student_profiles_flash'] ?? null;
unset($_SESSION['student_profiles_flash']);
$students = $pdo->query(
    "SELECT u.id, u.user_code, u.full_name, u.email,
            " . implode(', ', array_map(
                static fn(string $column): string => 'p.`' . $column . '`',
                array_keys(student_profile_fields())
            )) . "
     FROM users u
     LEFT JOIN student_profiles p ON p.user_id = u.id
     WHERE u.role = 'student'
     ORDER BY u.full_name"
)->fetchAll();

$selectedStudent = null;
$profile = [];
$requestedId = filter_var($_GET['user_id'] ?? null, FILTER_VALIDATE_INT);
if ($requestedId) {
    foreach ($students as $student) {
        if ((int) $student['id'] === (int) $requestedId) {
            $selectedStudent = $student;
            $profile = $student;
            break;
        }
    }
    if (!$selectedStudent && !$flash) {
        $flash = ['message' => 'That student account could not be found.', 'error' => true];
    }
}

$basePath = '../';
$pageTitle = 'Student Profiles';
$activePage = 'student-profiles';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main id="main-content" class="main-content">
    <div class="page-header">
        <p class="eyebrow">ACCOUNT ADMINISTRATION</p>
        <h2>Student Profiles</h2>
        <p>Manage enrollment information. Only administrators can edit or import these records.</p>
    </div>

    <?php if ($flash): ?>
        <p class="<?= !empty($flash['error']) ? 'notice error' : 'notice' ?>" role="status"><?= e($flash['message']) ?></p>
    <?php endif; ?>

    <section class="content-card" aria-labelledby="profile-import-title">
        <h3 id="profile-import-title">Import Google Forms responses</h3>
        <p>Export responses as a CSV. Include a Student ID, Student Email, or CCC Email column so each row can be matched safely.</p>
        <details class="profile-import-help">
            <summary>How to get a CSV from Google Forms</summary>
            <p>In Forms, open <strong>Responses</strong> and use the response spreadsheet. In Sheets, choose <strong>File → Download → Comma-separated values (.csv)</strong>.</p>
        </details>
        <form method="post" action="../actions/student_profile_actions.php" enctype="multipart/form-data" class="form-stack">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="import_csv">
            <label class="field" for="profile-csv">
                <span>Google Forms CSV</span>
                <input id="profile-csv" type="file" name="profile_csv" accept=".csv,text/csv" required>
                <small>Maximum file size: 5 MB. No records are changed if a row has an error.</small>
            </label>
            <div><button class="btn" type="submit">Import profiles</button></div>
        </form>
    </section>

    <?php if ($selectedStudent): ?>
        <section class="content-card" aria-labelledby="edit-profile-title">
            <div class="page-header">
                <p class="eyebrow"><?= e($selectedStudent['user_code']) ?></p>
                <h3 id="edit-profile-title">Edit <?= e($selectedStudent['full_name']) ?>'s profile</h3>
                <p><?= e($selectedStudent['email']) ?> · Blank fields remain unavailable to the student.</p>
            </div>
            <form method="post" action="../actions/student_profile_actions.php" class="form-stack">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save_profile">
                <input type="hidden" name="user_id" value="<?= (int) $selectedStudent['id'] ?>">
                <?php foreach (student_profile_sections() as $sectionKey => $section): ?>
                    <details class="profile-section" <?= $sectionKey === 'academic' ? 'open' : '' ?>>
                        <summary><?= e($section['title']) ?></summary>
                        <div class="profile-field-grid">
                            <?php foreach ($section['fields'] as $key => $field):
                                $fieldId = 'profile-' . $key;
                                $value = (string) ($profile[$key] ?? '');
                            ?>
                                <label class="field" for="<?= e($fieldId) ?>">
                                    <span><?= e($field['label']) ?></span>
                                    <?php if ($field['type'] === 'textarea'): ?>
                                        <textarea id="<?= e($fieldId) ?>" name="<?= e($key) ?>" maxlength="<?= (int) $field['max'] ?>" rows="3"><?= e($value) ?></textarea>
                                    <?php elseif ($field['type'] === 'choice'): ?>
                                        <select id="<?= e($fieldId) ?>" name="<?= e($key) ?>">
                                            <option value="">Not provided</option>
                                            <option value="Yes" <?= $value === 'Yes' ? 'selected' : '' ?>>Yes</option>
                                            <option value="No" <?= $value === 'No' ? 'selected' : '' ?>>No</option>
                                        </select>
                                    <?php else: ?>
                                        <input id="<?= e($fieldId) ?>"
                                            type="<?= e($field['type']) ?>"
                                            name="<?= e($key) ?>"
                                            value="<?= e($value) ?>"
                                            <?= isset($field['max']) ? 'maxlength="' . (int) $field['max'] . '"' : '' ?>
                                            <?= $field['type'] === 'number' ? 'min="1" max="5" step="0.01"' : '' ?>>
                                    <?php endif; ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </details>
                <?php endforeach; ?>
                <div class="form-actions">
                    <button class="btn" type="submit">Save profile</button>
                    <a class="btn btn-outline" href="student_profiles.php">Back to students</a>
                </div>
            </form>
        </section>
    <?php endif; ?>

    <section class="content-card" aria-labelledby="students-list-title">
        <h3 id="students-list-title">Students</h3>
        <?php if (!$students): ?>
            <p>No student accounts are available yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead><tr><th>Student</th><th>Email</th><th>Profile fields</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php foreach ($students as $student):
                        $providedCount = count(array_filter(
                            array_keys(student_profile_fields()),
                            static fn(string $key): bool => isset($student[$key]) && trim((string) $student[$key]) !== ''
                        ));
                    ?>
                        <tr>
                            <td><strong><?= e($student['full_name']) ?></strong><br><span class="muted"><?= e($student['user_code']) ?></span></td>
                            <td><?= e($student['email']) ?></td>
                            <td><?= $providedCount ?> of <?= count(student_profile_fields()) ?></td>
                            <td><a class="btn btn-outline btn-sm" href="student_profiles.php?user_id=<?= (int) $student['id'] ?>">Edit profile</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
