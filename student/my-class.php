<?php
require_once __DIR__ . '/../includes/student_init.php';

$pageTitle  = 'My Classes';
$activePage = 'my-class';

$classes = get_classes();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <!-- Breadcrumb navigation -->
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <ol>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li class="current">My Classes</li>
        </ol>
    </nav>

    <div class="page-header">
        <h2>My Classes</h2>
        <p>Select a class to view its course materials, assessments, and announcements.</p>
    </div>

    <?php if (!$classes): ?>
        <p class="empty">You are not enrolled in any classes yet.</p>
    <?php else: ?>
        <div class="class-card-grid">
            <?php foreach ($classes as $c): ?>
                <a href="class-details.php?id=<?= (int) $c['id'] ?>" class="class-card">
                    <div class="class-card-header">
                        <span class="class-card-code">CLASS SECTION · <?= e($c['section']) ?></span>
                        <h3><?= e($c['subject']) ?></h3>
                    </div>
                    <div class="class-card-body">
                        <dl>
                            <div class="class-card-field">
                                <dt>Instructor</dt>
                                <dd><?= e($c['teacher']) ?></dd>
                            </div>
                        </dl>
                    </div>
                    <div class="class-card-footer">
                        <span class="class-card-action">Open Class</span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
