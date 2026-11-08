<?php
require_once __DIR__ . '/../includes/student_init.php';
require_once __DIR__ . '/../includes/calendar.php';

$pageTitle  = 'Calendar';
$activePage = 'calendar';
$pdo = Database::connect();
$events = get_role_calendar_events($pdo, calendar_scope_for_role($user['role']));
$stmt = $pdo->prepare(
    "SELECT a.title, a.deadline AS event_date, c.code AS detail, 'assignment' AS source
     FROM assignments a
     JOIN class_enrollments ce ON ce.class_id = a.class_id AND ce.student_id = ?
     JOIN classes c ON c.id = a.class_id
     UNION ALL
     SELECT q.title, q.deadline AS event_date, c.code AS detail, 'quiz' AS source
     FROM quizzes q
     JOIN class_enrollments ce ON ce.class_id = q.class_id AND ce.student_id = ?
     JOIN classes c ON c.id = q.class_id
     ORDER BY event_date"
);
$stmt->execute([$user['id'], $user['id']]);
foreach ($stmt->fetchAll() as $deadline) {
    $events[] = [
        'id' => null,
        'title' => $deadline['title'],
        'description' => '',
        'date' => date('Y-m-d', strtotime($deadline['event_date'])),
        'editable' => false,
        'source' => $deadline['source'],
        'detail' => $deadline['detail'],
    ];
}
$flash = $_SESSION['calendar_flash'] ?? null;
unset($_SESSION['calendar_flash']);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <div class="page-header">
        <p class="eyebrow">YOUR LEARNING SPACE</p>
        <h2>Calendar of Events</h2>
        <p>View your class deadlines and events shared with students.</p>
    </div>
    <?php if ($flash): ?>
        <p class="<?= !empty($flash['error']) ? 'notice error' : 'notice' ?>" role="status"><?= e($flash['message']) ?></p>
    <?php endif; ?>
    <?php render_calendar($events); ?>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
