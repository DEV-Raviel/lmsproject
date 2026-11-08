<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/calendar.php';
require_once __DIR__ . '/../config/db.php';

$user = require_login();
if (!in_array($user['role'] ?? '', ['admin', 'dean'], true)) {
    http_response_code(403);
    exit('Access denied.');
}

$basePath = '../';
$pageTitle = 'School Calendar';
$activePage = 'calendar';
$pdo = Database::connect();
$events = get_role_calendar_events($pdo, calendar_scope_for_role((string) $user['role']));
$flash = $_SESSION['calendar_flash'] ?? null;
unset($_SESSION['calendar_flash']);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <div class="page-header">
        <p class="eyebrow">INSTITUTIONAL EVENTS</p>
        <h2>School Calendar</h2>
        <p>Plan and manage events shared with administrators and the dean.</p>
    </div>
    <?php if ($flash): ?>
        <p class="<?= !empty($flash['error']) ? 'notice error' : 'notice' ?>" role="status"><?= e($flash['message']) ?></p>
    <?php endif; ?>
    <?php render_calendar($events); ?>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
