<?php
require_once __DIR__ . '/../includes/teacher_init.php';

$pageTitle = 'My Students';
$activePage = 'students';
$selectedClassId = teacher_selected_class_id($classes);
$search = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 100) : '';
$selectedClass = null;
foreach ($classes as $class) {
    if ((int) $class['id'] === $selectedClassId) {
        $selectedClass = $class;
        break;
    }
}
$query = "SELECT DISTINCT u.user_code, u.full_name, u.email, c.code
          FROM class_enrollments ce
          JOIN users u ON u.id = ce.student_id AND u.role = 'student'
          JOIN classes c ON c.id = ce.class_id
          WHERE c.teacher_id = ?";
$parameters = [$user['id']];
if ($selectedClassId !== null) {
    $query .= ' AND c.id = ?';
    $parameters[] = $selectedClassId;
}
if ($search !== '') {
    $query .= ' AND (u.user_code LIKE ? OR u.full_name LIKE ? OR u.email LIKE ? OR c.code LIKE ?)';
    $term = '%' . $search . '%';
    array_push($parameters, $term, $term, $term, $term);
}
$query .= ' ORDER BY c.code, u.full_name';
$stmt = $pdo->prepare($query);
$stmt->execute($parameters);
$students = $stmt->fetchAll();
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <div class="page-header">
        <p class="eyebrow">TEACHER TOOLS</p>
        <h2>My Students</h2>
        <p><?= $selectedClass === null ? 'Enrolled learners in your assigned classes.' : 'Enrolled learners in ' . e($selectedClass['code']) . '.' ?></p>
        <?php if ($selectedClassId !== null): ?><p><a href="students.php">View students across all classes</a></p><?php endif; ?>
    </div>
    <details class="filter-disclosure" <?= ($search !== '' || $selectedClassId !== null) ? 'open' : '' ?>>
        <summary>Filters</summary>
        <form class="list-filter-form" method="get">
            <label>
                <span>Search students</span>
                <input type="search" name="q" value="<?= e($search) ?>" maxlength="100" placeholder="Name, ID, email, or class">
            </label>
            <label>
                <span>Class</span>
                <select name="class_id">
                    <option value="">All my classes</option>
                    <?php foreach ($classes as $class): ?>
                        <option value="<?= (int) $class['id'] ?>" <?= $selectedClassId === (int) $class['id'] ? 'selected' : '' ?>>
                            <?= e($class['code'] . ' — ' . $class['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button class="btn" type="submit">Apply filters</button>
            <a class="btn btn-outline" href="students.php">Clear</a>
        </form>
    </details>
    <div class="table-responsive"><table class="data-table">
        <thead><tr><th>No.</th><th>Student ID</th><th>Student Name</th><th>Email</th><th>Class</th></tr></thead>
        <tbody>
        <?php foreach ($students as $index => $student): ?>
            <tr><td><?= $index + 1 ?></td><td><?= e($student['user_code']) ?></td><td><?= e($student['full_name']) ?></td><td><?= e($student['email']) ?></td><td><?= e($student['code']) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$students): ?><tr><td colspan="5"><?= $search !== '' || $selectedClassId !== null ? 'No students match these filters.' : 'No students are enrolled in your classes yet.' ?></td></tr><?php endif; ?>
        </tbody>
    </table></div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
