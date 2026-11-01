<?php
require_once __DIR__ . '/../includes/student_init.php';

$pageTitle  = 'Online Education Resource (OER)';
$activePage = 'oer';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <h2>Online Education Resource (OER)</h2>
    <p>Sample educational resources:</p>
    <ul>
<?php foreach (get_oer_resources() as [$name, $url, $description]): ?>
        <li><a href="<?= e($url) ?>" target="_blank" rel="noopener"><?= e($name) ?></a> - <?= e($description) ?></li>
<?php endforeach; ?>
    </ul>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
