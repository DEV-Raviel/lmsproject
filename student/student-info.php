<?php
require_once __DIR__ . '/../includes/student_init.php';
require_once __DIR__ . '/../includes/student_profile.php';

$pdo = Database::connect();
$profileStmt = $pdo->prepare('SELECT * FROM student_profiles WHERE user_id = ?');
$profileStmt->execute([(int) $user['id']]);
$profile = $profileStmt->fetch() ?: [];
$classes = get_classes();

$pageTitle = 'Student Profile';
$activePage = 'student-info';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main id="main-content" class="main-content">
    <div class="page-header">
        <p class="eyebrow">ENROLLMENT INFORMATION</p>
        <h2>Student Profile</h2>
        <p>Information submitted during enrollment. Contact your school administrator if anything needs updating.</p>
    </div>

    <?php if (!$profile): ?>
        <p class="notice" role="status">Your enrollment profile has not been added yet.</p>
    <?php else: ?>
        <p class="profile-privacy-note">This profile is private to you and authorized administrators.</p>
        <?php foreach (student_profile_sections() as $sectionKey => $section): ?>
            <details class="profile-section profile-readonly" <?= $sectionKey === 'academic' ? 'open' : '' ?>>
                <summary><?= e($section['title']) ?></summary>
                <dl class="profile-value-grid">
                    <?php if ($sectionKey === 'academic'): ?>
                        <div class="profile-value">
                            <dt>Student ID</dt>
                            <dd><?= e($user['user_code'] ?? 'Not provided') ?></dd>
                        </div>
                    <?php endif; ?>
                    <?php foreach ($section['fields'] as $key => $field):
                        $value = trim((string) ($profile[$key] ?? ''));
                    ?>
                        <div class="profile-value">
                            <dt><?= e($field['label']) ?></dt>
                            <dd><?= $value === '' ? '<span class="muted">Not provided</span>' : nl2br(e($value)) ?></dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
            </details>
        <?php endforeach; ?>
    <?php endif; ?>

    <section class="content-card" aria-labelledby="enrolled-classes-title">
        <h3 id="enrolled-classes-title">Enrolled classes</h3>
        <?php
        $classRows = array_map(
            static fn(array $class): array => [
                $class['subject'],
                $class['section'],
                $class['teacher'] ?? 'Not assigned',
            ],
            $classes
        );
        render_table(['Class / Course', 'Section', 'Instructor'], $classRows, 'You are not enrolled in any classes yet.');
        ?>
    </section>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
