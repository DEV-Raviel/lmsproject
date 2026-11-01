<?php
require_once __DIR__ . '/../includes/student_init.php';

$pageTitle  = 'Bulletins';
$activePage = 'dashboard';

$rows = [];
foreach (get_bulletins() as $b) {
    $rows[] = [['href' => 'bulletin.php?id=' . (int) $b['id'], 'text' => $b['title']], time_ago($b['created_at'])];
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <h2>All Bulletins</h2>
    <?php render_table(['Title', 'Posted'], $rows); ?>
    <p><a href="dashboard.php">Back to Dashboard</a></p>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
