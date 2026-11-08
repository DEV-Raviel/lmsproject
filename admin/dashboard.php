<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';

$user = require_role('admin');
$basePath = '../';
$pageTitle = 'User Management';
$activePage = 'accounts';
$pdo = Database::connect();
$flash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);
$accountSearch = is_string($_GET['account_q'] ?? null) ? mb_substr(trim($_GET['account_q']), 0, 100) : '';
$accountRole = is_string($_GET['account_role'] ?? null) ? $_GET['account_role'] : '';
if (!in_array($accountRole, ['admin', 'dean', 'teacher', 'student'], true)) {
    $accountRole = '';
}
$classSearch = is_string($_GET['class_q'] ?? null) ? mb_substr(trim($_GET['class_q']), 0, 100) : '';

$metrics = $pdo->query(
    "SELECT
        COUNT(*) AS accounts,
        SUM(role = 'teacher') AS teachers,
        SUM(role = 'student') AS students
     FROM users"
)->fetch();
$metrics['classes'] = (int) $pdo->query('SELECT COUNT(*) FROM classes')->fetchColumn();

$userQuery = 'SELECT id, user_code, full_name, email, role, created_at FROM users WHERE 1 = 1';
$userParameters = [];
if ($accountSearch !== '') {
    $userQuery .= ' AND (user_code LIKE ? OR full_name LIKE ? OR email LIKE ?)';
    $term = '%' . $accountSearch . '%';
    array_push($userParameters, $term, $term, $term);
}
if ($accountRole !== '') {
    $userQuery .= ' AND role = ?';
    $userParameters[] = $accountRole;
}
$userQuery .= ' ORDER BY created_at DESC, id DESC';
$userStmt = $pdo->prepare($userQuery);
$userStmt->execute($userParameters);
$users = $userStmt->fetchAll();

$classQuery = 'SELECT c.id, c.code, c.name, c.semester, c.fiscal_year,
            u.full_name AS teacher_name, COALESCE(enrollment_counts.students_count, 0) AS students_count
     FROM classes c
     LEFT JOIN users u ON u.id = c.teacher_id
     LEFT JOIN (
         SELECT class_id, COUNT(DISTINCT student_id) AS students_count
         FROM class_enrollments
         GROUP BY class_id
     ) enrollment_counts ON enrollment_counts.class_id = c.id
     WHERE 1 = 1';
$classParameters = [];
if ($classSearch !== '') {
    $classQuery .= ' AND (c.code LIKE ? OR c.name LIKE ? OR c.semester LIKE ? OR c.fiscal_year LIKE ? OR u.full_name LIKE ?)';
    $term = '%' . $classSearch . '%';
    array_push($classParameters, $term, $term, $term, $term, $term);
}
$classQuery .= ' ORDER BY c.code';
$classStmt = $pdo->prepare($classQuery);
$classStmt->execute($classParameters);
$classes = $classStmt->fetchAll();
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
        <h2>User Management</h2>
        <p>Manage accounts and classes.</p>
    </div>

    <?php if ($flash): ?>
        <p class="<?= !empty($flash['error']) ? 'notice error' : 'notice' ?>" role="status" data-toast><?= e($flash['message']) ?></p>
        <?php if (!empty($flash['temporary_password'])): ?>
            <section class="recovery-link-card" aria-labelledby="new-account-password-title">
                <h3 id="new-account-password-title">Copy this temporary password now</h3>
                <p>It is shown once. Share it securely; the new user must change it at first sign-in.</p>
                <label class="field">
                    <span>Temporary password</span>
                    <input type="text" readonly value="<?= e($flash['temporary_password']) ?>" onclick="this.select()">
                </label>
            </section>
        <?php endif; ?>
    <?php endif; ?>

    <section class="progress-grid admin-metrics" aria-label="School totals">
        <?php foreach ([
            'Accounts' => (int) $metrics['accounts'],
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
        <div class="admin-section-heading">
            <h3>Accounts</h3>
            <span><?= count($users) ?> shown</span>
        </div>
        <details class="admin-disclosure">
            <summary>Create account <span class="disclosure-indicator" aria-hidden="true"></span></summary>
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
                    <label for="email">Institutional email (provided by the Administrator)</label>
                    <input id="email" name="email" type="email" required maxlength="150">
                    <small>This email will be used as the teacher's student-facing contact email.</small>
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
                <button class="btn" type="submit">Create account</button>
            </form>
        </details>

        <details class="admin-list-disclosure" <?= ($accountSearch !== '' || $accountRole !== '') ? 'open' : '' ?>>
            <summary>View accounts <span class="disclosure-indicator" aria-hidden="true"></span></summary>
            <details class="filter-disclosure" <?= ($accountSearch !== '' || $accountRole !== '') ? 'open' : '' ?>>
                <summary>Filters</summary>
                <form class="list-filter-form" method="get">
                    <label>
                        <span>Search accounts</span>
                        <input type="search" name="account_q" value="<?= e($accountSearch) ?>" maxlength="100" placeholder="Name, user code, or email">
                    </label>
                    <label>
                        <span>Role</span>
                        <select name="account_role">
                            <option value="">All roles</option>
                            <?php foreach (['admin' => 'Administrator', 'dean' => 'Dean', 'teacher' => 'Teacher', 'student' => 'Student'] as $roleValue => $roleLabel): ?>
                                <option value="<?= e($roleValue) ?>" <?= $accountRole === $roleValue ? 'selected' : '' ?>><?= e($roleLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <button class="btn" type="submit">Apply filters</button>
                    <a class="btn btn-outline" href="dashboard.php#accounts">Clear</a>
                </form>
            </details>
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
                                <form method="post" action="../actions/admin_actions.php" onsubmit="return confirm('Delete this account and its records? This cannot be undone.');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="id" value="<?= (int) $account['id'] ?>">
                                    <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                                </form>
                            <?php else: ?><span>Protected</span><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$users): ?><tr><td colspan="6"><?= $accountSearch !== '' || $accountRole !== '' ? 'No accounts match these filters.' : 'No accounts found.' ?></td></tr><?php endif; ?>
                </tbody>
            </table>
            </div>
        </details>
    </section>

    <section id="classes">
        <div class="admin-section-heading">
            <h3>Classes</h3>
            <span><?= count($classes) ?> shown</span>
        </div>
        <details class="admin-disclosure">
            <summary>Create class <span class="disclosure-indicator" aria-hidden="true"></span></summary>
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
                <button class="btn" type="submit" <?= !$teachers ? 'disabled' : '' ?>>Create class</button>
                <?php if (!$teachers): ?><p>Add a teacher account before creating a class.</p><?php endif; ?>
            </form>
        </details>

        <details class="admin-list-disclosure" <?= $classSearch !== '' ? 'open' : '' ?>>
            <summary>View classes <span class="disclosure-indicator" aria-hidden="true"></span></summary>
            <details class="filter-disclosure" <?= $classSearch !== '' ? 'open' : '' ?>>
                <summary>Filters</summary>
                <form class="list-filter-form" method="get">
                    <label>
                        <span>Search classes</span>
                        <input type="search" name="class_q" value="<?= e($classSearch) ?>" maxlength="100" placeholder="Code, subject, teacher, or term">
                    </label>
                    <button class="btn" type="submit">Search</button>
                    <a class="btn btn-outline" href="dashboard.php#classes">Clear</a>
                </form>
            </details>
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
                                <details class="class-action-disclosure">
                                    <summary>Enroll students <span class="disclosure-indicator" aria-hidden="true"></span></summary>
                                    <form class="enrollment-form" method="post" action="../actions/admin_actions.php">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="enroll_student">
                                        <input type="hidden" name="class_id" value="<?= (int) $class['id'] ?>">
                                        <select name="student_id" aria-label="Select student for <?= e($class['code']) ?>" required>
                                            <option value="">Choose one student</option>
                                            <?php foreach ($students as $student): ?>
                                                <option value="<?= (int) $student['id'] ?>"><?= e($student['full_name'] . ' (' . $student['user_code'] . ')') ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="btn btn-sm" type="submit">Enroll student</button>
                                    </form>
                                    <details class="batch-enrollment">
                                        <summary>Enroll a group <span class="disclosure-indicator" aria-hidden="true"></span></summary>
                                        <form method="post" action="../actions/admin_actions.php">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="batch_enroll_students">
                                            <input type="hidden" name="class_id" value="<?= (int) $class['id'] ?>">
                                            <label class="field">
                                                <span>Student user codes or emails</span>
                                                <textarea name="student_identifiers" rows="3" maxlength="30000" placeholder="One per line, or comma-separated" required></textarea>
                                            </label>
                                            <small>Up to 200 students. Already-enrolled students are skipped.</small>
                                            <button class="btn btn-outline btn-sm" type="submit">Enroll students</button>
                                        </form>
                                    </details>
                                </details>
                            <?php else: ?><span>Create a student account first.</span><?php endif; ?>
                            <details class="class-action-disclosure class-remove-disclosure">
                                <summary>Remove class <span class="disclosure-indicator" aria-hidden="true"></span></summary>
                                <form method="post" action="../actions/admin_actions.php" onsubmit="return confirm('Remove this class and its enrollments? This cannot be undone.');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete_class">
                                    <input type="hidden" name="id" value="<?= (int) $class['id'] ?>">
                                    <button class="btn btn-danger btn-sm" type="submit">Remove <?= e($class['code']) ?></button>
                                </form>
                            </details>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$classes): ?><tr><td colspan="6"><?= $classSearch !== '' ? 'No classes match this search.' : 'No classes have been created.' ?></td></tr><?php endif; ?>
                </tbody>
            </table>
            </div>
        </details>
    </section>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
