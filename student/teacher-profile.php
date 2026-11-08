<?php
require_once __DIR__ . '/../includes/student_init.php';
$pdo = Database::connect();

$teacherIdInput = is_string($_GET['id'] ?? null) ? $_GET['id'] : '';
$teacherId = filter_var($teacherIdInput, FILTER_VALIDATE_INT);
if (!$teacherId || $teacherId < 1) {
    abort_404('Teacher profile not found.');
}

$teacherStmt = $pdo->prepare(
    "SELECT u.id, u.full_name, u.email, u.facebook_url, u.profile_photo_path
     FROM users u
     WHERE u.id = ? AND u.role = 'teacher'
       AND EXISTS (
           SELECT 1
           FROM classes c
           JOIN class_enrollments ce ON ce.class_id = c.id
           WHERE c.teacher_id = u.id AND ce.student_id = ?
       )"
);
$teacherStmt->execute([$teacherId, (int) $user['id']]);
$teacher = $teacherStmt->fetch();
if (!$teacher) {
    abort_404('Teacher profile not found.');
}

$classStmt = $pdo->prepare(
    'SELECT c.id, c.code, c.name
     FROM classes c
     JOIN class_enrollments ce ON ce.class_id = c.id
     WHERE c.teacher_id = ? AND ce.student_id = ?
     ORDER BY c.code'
);
$classStmt->execute([$teacherId, (int) $user['id']]);
$classes = $classStmt->fetchAll();
$requestedClassId = filter_var(
    is_string($_GET['class_id'] ?? null) ? $_GET['class_id'] : '',
    FILTER_VALIDATE_INT
);
$profileClassId = (int) $classes[0]['id'];
foreach ($classes as $class) {
    if ($requestedClassId && (int) $class['id'] === $requestedClassId) {
        $profileClassId = (int) $class['id'];
        break;
    }
}
$backUrl = 'class-details.php?' . http_build_query([
    'id' => $profileClassId,
    'tab' => 'colleagues',
]);

$pageTitle = 'Teacher Profile';
$activePage = 'my-class';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <a class="btn-back profile-back" href="<?= e($backUrl) ?>">← Back to Colleagues</a>
    <div class="page-header">
        <h2><?= e($teacher['full_name']) ?></h2>
        <p>Instructor contact information for your classes.</p>
    </div>
    <section class="card teacher-contact-card" aria-labelledby="teacher-contact-title">
        <h3 id="teacher-contact-title">Instructor profile</h3>
        <div class="person-profile">
            <?php $teacherPhoto = profile_photo_src(
                $teacher['profile_photo_path'],
                (int) $teacher['id'],
                '../',
                $profileClassId
            ); ?>
            <?php if ($teacherPhoto): ?>
                <img class="person-photo person-photo-large" src="<?= e($teacherPhoto) ?>" alt="">
            <?php else: ?>
                <span class="person-photo person-photo-large person-photo-fallback" aria-hidden="true"><?= e(profile_initials((string) $teacher['full_name'])) ?></span>
            <?php endif; ?>
            <h4><?= e($teacher['full_name']) ?></h4>
            <span class="person-role">Instructor</span>
        </div>
        <dl class="teacher-contact-list" aria-label="Instructor contact details">
            <div>
                <dt>Email</dt>
                <dd><a href="mailto:<?= e($teacher['email']) ?>"><?= e($teacher['email']) ?></a></dd>
            </div>
            <div>
                <dt>Facebook</dt>
                <dd>
                    <?php if (is_string($teacher['facebook_url']) && is_valid_facebook_url($teacher['facebook_url'])): ?>
                        <a href="<?= e($teacher['facebook_url']) ?>" rel="noopener noreferrer">Open Facebook profile</a>
                    <?php else: ?>
                        <span>Not provided yet</span>
                    <?php endif; ?>
                </dd>
            </div>
            <div>
                <dt>Your classes</dt>
                <dd>
                    <?php foreach ($classes as $index => $class): ?>
                        <?php if ($index > 0): ?>, <?php endif; ?>
                        <a href="class-details.php?id=<?= (int) $class['id'] ?>"><?= e($class['code'] . ' - ' . $class['name']) ?></a>
                    <?php endforeach; ?>
                </dd>
            </div>
        </dl>
    </section>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
