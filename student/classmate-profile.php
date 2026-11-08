<?php
require_once __DIR__ . '/../includes/student_init.php';
$pdo = Database::connect();

$studentIdInput = is_string($_GET['id'] ?? null) ? $_GET['id'] : '';
$classIdInput = is_string($_GET['class_id'] ?? null) ? $_GET['class_id'] : '';
$studentId = filter_var($studentIdInput, FILTER_VALIDATE_INT);
$classId = filter_var($classIdInput, FILTER_VALIDATE_INT);
if (!$studentId || $studentId < 1 || !$classId || $classId < 1) {
    abort_404('Classmate profile not found.');
}

$classmateStmt = $pdo->prepare(
    "SELECT u.id, u.full_name, u.email, u.facebook_url, u.profile_photo_path,
            c.code AS class_code, c.name AS class_name
     FROM users u
     JOIN class_enrollments classmate ON classmate.student_id = u.id
     JOIN classes c ON c.id = classmate.class_id
     JOIN class_enrollments viewer ON viewer.class_id = c.id AND viewer.student_id = ?
     WHERE u.id = ? AND u.role = 'student' AND c.id = ? AND u.id <> viewer.student_id"
);
$classmateStmt->execute([(int) $user['id'], $studentId, $classId]);
$classmate = $classmateStmt->fetch();
if (!$classmate) {
    abort_404('Classmate profile not found.');
}

$pageTitle = 'Classmate Profile';
$activePage = 'my-class';
$backUrl = 'class-details.php?' . http_build_query([
    'id' => (int) $classId,
    'tab' => 'colleagues',
]);
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <a class="btn-back profile-back" href="<?= e($backUrl) ?>">← Back to Colleagues</a>
    <div class="page-header">
        <h2>Classmate profile</h2>
        <p>Contact details shared with students in this class.</p>
    </div>
    <section class="card teacher-contact-card" aria-labelledby="classmate-contact-title">
        <div class="person-profile">
            <?php $classmatePhoto = profile_photo_src(
                $classmate['profile_photo_path'],
                (int) $classmate['id'],
                '../',
                (int) $classId
            ); ?>
            <?php if ($classmatePhoto): ?>
                <img class="person-photo person-photo-large" src="<?= e($classmatePhoto) ?>" alt="">
            <?php else: ?>
                <span class="person-photo person-photo-large person-photo-fallback" aria-hidden="true"><?= e(profile_initials((string) $classmate['full_name'])) ?></span>
            <?php endif; ?>
            <h3 id="classmate-contact-title"><?= e($classmate['full_name']) ?></h3>
            <span class="person-role"><?= e($classmate['class_code'] . ' - ' . $classmate['class_name']) ?></span>
        </div>
        <dl class="teacher-contact-list" aria-label="Classmate contact details">
            <div>
                <dt>Email</dt>
                <dd><a href="mailto:<?= e($classmate['email']) ?>"><?= e($classmate['email']) ?></a></dd>
            </div>
            <div>
                <dt>Facebook</dt>
                <dd>
                    <?php if (is_string($classmate['facebook_url']) && is_valid_facebook_url($classmate['facebook_url'])): ?>
                        <a href="<?= e($classmate['facebook_url']) ?>" rel="noopener noreferrer">Open Facebook profile</a>
                    <?php else: ?>
                        <span>Not provided</span>
                    <?php endif; ?>
                </dd>
            </div>
        </dl>
    </section>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
