<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';

$user = require_role('admin');
$basePath = '../';
$pageTitle = 'Administrator Console';
$activePage = 'accounts';
$pdo = Database::connect();
$flash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);

$metrics = $pdo->query(
    "SELECT
        COUNT(*) AS accounts,
        SUM(role = 'teacher') AS teachers,
        SUM(role = 'student') AS students
     FROM users"
)->fetch();
$metrics['classes'] = (int) $pdo->query('SELECT COUNT(*) FROM classes')->fetchColumn();

$users = $pdo->query(
    'SELECT id, user_code, full_name, email, role, created_at
     FROM users ORDER BY created_at DESC, id DESC'
)->fetchAll();
$classes = $pdo->query(
    'SELECT c.id, c.code, c.name, c.semester, c.fiscal_year,
            u.full_name AS teacher_name, COALESCE(enrollment_counts.students_count, 0) AS students_count
     FROM classes c
     LEFT JOIN users u ON u.id = c.teacher_id
     LEFT JOIN (
         SELECT class_id, COUNT(DISTINCT student_id) AS students_count
         FROM class_enrollments
         GROUP BY class_id
     ) enrollment_counts ON enrollment_counts.class_id = c.id
     ORDER BY c.code'
)->fetchAll();
$teachers = $pdo->query(
    "SELECT id, user_code, full_name FROM users WHERE role = 'teacher' ORDER BY full_name"
)->fetchAll();
$students = $pdo->query(
    "SELECT id, user_code, full_name FROM users WHERE role = 'student' ORDER BY full_name"
)->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <div class="page-header">
        <h2>System Overview</h2>
        <p>Manage institutional accounts and class assignments.</p>
    </div>

    <?php if ($flash): ?>
        <p class="<?= !empty($flash['error']) ? 'notice error' : 'notice' ?>" role="status" data-toast><?= e($flash['message']) ?></p>
    <?php endif; ?>

    <section class="progress-grid" aria-label="System totals">
        <?php foreach ([
            'Total Accounts' => (int) $metrics['accounts'],
            'Teachers' => (int) $metrics['teachers'],
            'Students' => (int) $metrics['students'],
            'Classes' => (int) $metrics['classes'],
        ] as $label => $value): ?>
            <article class="progress-card">
                <span class="progress-label"><?= e($label) ?></span>
                <span class="progress-value"><?= $value ?></span>
            </article>
        <?php endforeach; ?>
    </section>

    <section id="accounts">
        <div class="page-header">
            <h2>User Accounts</h2>
            <p>Create, review, and remove institutional accounts.</p>
        </div>
        <form class="card form" method="post" action="../actions/admin_actions.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create_user">
            <div class="field">
                <label for="user-code">User code</label>
                <input id="user-code" name="user_code" required maxlength="50">
            </div>
            <div class="field">
                <label for="full-name">Full name</label>
                <input id="full-name" name="full_name" required maxlength="150">
            </div>
            <div class="field">
                <label for="email">Institutional email</label>
                <input id="email" name="email" type="email" required maxlength="150">
            </div>
            <div class="field">
                <label for="role">Role</label>
                <select id="role" name="role" required>
                    <option value="teacher">Teacher</option>
                    <option value="student">Student</option>
                    <option value="dean">Dean</option>
                    <option value="admin">Administrator</option>
                </select>
            </div>
            <div class="field">
                <label for="password">Temporary password</label>
                <input id="password" name="password" type="password" required minlength="8">
            </div>
            <button class="btn" type="submit">Create Account</button>
        </form>

        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>User code</th><th>Name</th><th>Email</th><th>Role</th><th>Created</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($users as $account): ?>
                    <tr>
                        <td><?= e($account['user_code']) ?></td>
                        <td><?= e($account['full_name']) ?></td>
                        <td><?= e($account['email']) ?></td>
                        <td><?= e(ucfirst($account['role'])) ?></td>
                        <td><?= e($account['created_at'] ?? '') ?></td>
                        <td>
                            <?php if ($account['role'] !== 'admin' && (int) $account['id'] !== (int) $user['id']): ?>
                                <form method="post" action="../actions/admin_actions.php">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="id" value="<?= (int) $account['id'] ?>">
                                    <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                                </form>
                            <?php else: ?><span>Protected</span><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$users): ?><tr><td colspan="6">No accounts found.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section id="classes">
        <div class="page-header">
            <h2>Class Management</h2>
            <p>Create classes and assign them to teachers.</p>
        </div>
        <form class="card form" method="post" action="../actions/admin_actions.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create_class">
            <div class="field">
                <label for="class-code">Class code / section</label>
                <input id="class-code" name="code" required maxlength="50">
            </div>
            <div class="field">
                <label for="class-name">Subject title</label>
                <input id="class-name" name="name" required maxlength="150">
            </div>
            <div class="field">
                <label for="teacher-id">Assigned teacher</label>
                <select id="teacher-id" name="teacher_id" required>
                    <?php foreach ($teachers as $teacher): ?>
                        <option value="<?= (int) $teacher['id'] ?>"><?= e($teacher['full_name'] . ' (' . $teacher['user_code'] . ')') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="semester">Semester</label>
                <input id="semester" name="semester" required value="1st Semester" maxlength="50">
            </div>
            <div class="field">
                <label for="fiscal-year">Academic year</label>
                <input id="fiscal-year" name="fiscal_year" required value="2026-2027" maxlength="50">
            </div>
            <button class="btn" type="submit" <?= !$teachers ? 'disabled' : '' ?>>Create &amp; Assign Class</button>
            <?php if (!$teachers): ?><p>Add a teacher account before creating a class.</p><?php endif; ?>
        </form>

        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Code</th><th>Subject</th><th>Teacher</th><th>Term / Year</th><th>Students</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($classes as $class): ?>
                    <tr>
                        <td><?= e($class['code']) ?></td>
                        <td><?= e($class['name']) ?></td>
                        <td><?= e($class['teacher_name'] ?? 'Unassigned') ?></td>
                        <td><?= e($class['semester'] . ' / ' . $class['fiscal_year']) ?></td>
                        <td><?= (int) $class['students_count'] ?></td>
                        <td>
                            <?php if ($students): ?>
                                <form class="enrollment-form" method="post" action="../actions/admin_actions.php">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="enroll_student">
                                    <input type="hidden" name="class_id" value="<?= (int) $class['id'] ?>">
                                    <select name="student_id" aria-label="Select student for <?= e($class['code']) ?>" required>
                                        <option value="">Enroll a student</option>
                                        <?php foreach ($students as $student): ?>
                                            <option value="<?= (int) $student['id'] ?>"><?= e($student['full_name'] . ' (' . $student['user_code'] . ')') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-sm" type="submit">Enroll</button>
                                </form>
                                <details class="batch-enrollment">
                                    <summary>Enroll multiple students</summary>
                                    <form method="post" action="../actions/admin_actions.php">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="batch_enroll_students">
                                        <input type="hidden" name="class_id" value="<?= (int) $class['id'] ?>">
                                        <label class="field">
                                            <span>Student user codes or emails</span>
                                            <textarea name="student_identifiers" rows="3" maxlength="30000" placeholder="One per line, or comma-separated" required></textarea>
                                        </label>
                                        <small>Paste up to 200 student identifiers. Existing enrollments are skipped.</small>
                                        <button class="btn btn-outline btn-sm" type="submit">Enroll students</button>
                                    </form>
                                </details>
                            <?php else: ?><span>Create a student account first.</span><?php endif; ?>
                            <form method="post" action="../actions/admin_actions.php">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete_class">
                                <input type="hidden" name="id" value="<?= (int) $class['id'] ?>">
                                <button class="btn btn-danger btn-sm" type="submit">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$classes): ?><tr><td colspan="6">No classes have been created.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
