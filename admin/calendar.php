<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/calendar.php';
require_once __DIR__ . '/../config/db.php';

$user = require_role('dean');

$basePath = '../';
$pageTitle = 'School Calendar';
$activePage = 'calendar';
$pdo = Database::connect();
$events = get_role_calendar_events($pdo, calendar_scope_for_role((string) $user['role']));
$tasks = get_user_calendar_tasks($pdo, (int) $user['id']);
$flash = $_SESSION['calendar_flash'] ?? null;
unset($_SESSION['calendar_flash']);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <div class="page-header">
        <p class="eyebrow">INSTITUTIONAL EVENTS</p>
        <h2>School Calendar</h2>
        <p>Create school-wide events visible on every role's calendar.</p>
    </div>
    <?php if ($flash): ?>
        <p class="<?= !empty($flash['error']) ? 'notice error' : 'notice' ?>" role="status" data-toast><?= e($flash['message']) ?></p>
    <?php endif; ?>
    <?php render_calendar($events, $tasks); ?>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
