<?php
// One page for every bulletin: bulletin.php?id=1
require_once __DIR__ . '/../includes/student_init.php';

$bulletin = get_bulletin((int) ($_GET['id'] ?? 0));
if (!$bulletin) {
    abort_404('Bulletin not found.');
}

$pageTitle  = $bulletin['title'];
$activePage = 'dashboard';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <h2><?= e($bulletin['title']) ?></h2>
    <p class="muted">Posted <?= e(time_ago($bulletin['created_at'])) ?></p>
    <p><?= nl2br(e($bulletin['body'])) ?></p>
    <p><a href="bulletins.php">Back to Bulletins</a></p>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
