<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';

$user = require_role('dean');
$pdo = Database::connect();
$announcements = $pdo->query(
    'SELECT a.id, a.title, a.body, a.is_published, a.created_at, a.updated_at,
            u.full_name AS author
     FROM announcements a
     LEFT JOIN users u ON u.id = a.created_by
     ORDER BY a.updated_at DESC, a.id DESC'
)->fetchAll();
$flash = $_SESSION['announcement_flash'] ?? null;
unset($_SESSION['announcement_flash']);

$basePath = '../';
$pageTitle = 'Manage Announcements';
$activePage = 'announcements';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <div class="page-header">
        <p class="eyebrow">DEAN'S OFFICE</p>
        <h2>School Announcements</h2>
        <p>Publish important updates for students and teachers. Drafts remain visible only to the Dean.</p>
    </div>
    <?php if ($flash): ?>
        <p class="<?= !empty($flash['error']) ? 'notice error' : 'notice' ?>" role="status"><?= e($flash['message']) ?></p>
    <?php endif; ?>

    <section class="announcement-composer card" aria-labelledby="create-announcement-title">
        <div class="section-heading">
            <span class="step-number">+</span>
            <div>
                <h3 id="create-announcement-title">Create an announcement</h3>
                <p>Published updates appear on teacher and student dashboards.</p>
            </div>
        </div>
        <form class="announcement-form" method="post" action="../actions/announcement_actions.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">
            <label class="field">
                <span>Title</span>
                <input type="text" name="title" maxlength="200" required>
            </label>
            <label class="field">
                <span>Announcement</span>
                <textarea name="body" rows="5" maxlength="20000" required></textarea>
            </label>
            <label class="announcement-publish-control">
                <input type="checkbox" name="is_published" value="1" checked>
                <span>Publish now for students and teachers</span>
            </label>
            <button class="btn" type="submit">Save Announcement</button>
        </form>
    </section>

    <section class="announcement-management" aria-labelledby="announcement-list-title">
        <div class="section-heading">
            <span class="step-number"><?= count($announcements) ?></span>
            <div>
                <h3 id="announcement-list-title">Manage announcements</h3>
                <p>Edit, publish, unpublish, or remove a school update.</p>
            </div>
        </div>
        <?php if ($announcements): ?>
            <div class="announcement-management-list">
                <?php foreach ($announcements as $announcement): ?>
                    <article class="announcement-management-card <?= (int) $announcement['is_published'] ? 'is-published' : 'is-draft' ?>">
                        <div class="announcement-management-header">
                            <div>
                                <span class="announcement-status"><?= (int) $announcement['is_published'] ? 'Published' : 'Draft' ?></span>
                                <h4><?= e($announcement['title']) ?></h4>
                                <p class="announcement-meta">
                                    <?= e($announcement['author'] ?? 'Former Dean') ?>
                                    · Updated <?= e(date('M j, Y g:i A', strtotime($announcement['updated_at']))) ?>
                                </p>
                            </div>
                            <form method="post" action="../actions/announcement_actions.php" onsubmit="return confirm('Delete this announcement?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $announcement['id'] ?>">
                                <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                            </form>
                        </div>
                        <p class="announcement-preview"><?= nl2br(e($announcement['body'])) ?></p>
                        <details class="announcement-edit">
                            <summary>Edit announcement</summary>
                            <form class="announcement-form" method="post" action="../actions/announcement_actions.php">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="id" value="<?= (int) $announcement['id'] ?>">
                                <label class="field">
                                    <span>Title</span>
                                    <input type="text" name="title" maxlength="200" value="<?= e($announcement['title']) ?>" required>
                                </label>
                                <label class="field">
                                    <span>Announcement</span>
                                    <textarea name="body" rows="5" maxlength="20000" required><?= e($announcement['body']) ?></textarea>
                                </label>
                                <label class="announcement-publish-control">
                                    <input type="checkbox" name="is_published" value="1" <?= (int) $announcement['is_published'] ? 'checked' : '' ?>>
                                    <span>Published for students and teachers</span>
                                </label>
                                <button class="btn btn-outline" type="submit">Save Changes</button>
                            </form>
                        </details>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state"><strong>No announcements yet</strong><p>Create a published update to share it with students and teachers.</p></div>
        <?php endif; ?>
    </section>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
