<?php
require_once __DIR__ . '/../includes/teacher_init.php';
require_once __DIR__ . '/../includes/calendar.php';

$pageTitle = 'Academic Calendar';
$activePage = 'calendar';
$events = get_role_calendar_events($pdo, calendar_scope_for_role($user['role']));
$stmt = $pdo->prepare(
    "SELECT a.title, a.deadline, c.code, 'Assignment' AS activity_type
     FROM assignments a JOIN classes c ON c.id = a.class_id
     WHERE c.teacher_id = ?
     UNION ALL
     SELECT q.title, q.deadline, c.code, 'Quiz' AS activity_type
     FROM quizzes q JOIN classes c ON c.id = q.class_id
     WHERE c.teacher_id = ?
     ORDER BY deadline"
);
$stmt->execute([$user['id'], $user['id']]);
$deadlines = $stmt->fetchAll();
foreach ($deadlines as $deadline) {
    $events[] = [
        'id' => null,
        'title' => $deadline['title'],
        'description' => '',
        'date' => date('Y-m-d', strtotime($deadline['deadline'])),
        'editable' => false,
        'source' => strtolower($deadline['activity_type']),
        'detail' => $deadline['code'],
    ];
}
$flash = $_SESSION['calendar_flash'] ?? null;
unset($_SESSION['calendar_flash']);
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <div class="page-header">
        <p class="eyebrow">TEACHER TOOLS</p>
        <h2>Academic Calendar</h2>
        <p>View assignment and quiz deadlines alongside events shared with teachers.</p>
    </div>
    <?php if ($flash): ?>
        <p class="<?= !empty($flash['error']) ? 'notice error' : 'notice' ?>" role="status"><?= e($flash['message']) ?></p>
    <?php endif; ?>
    <?php render_calendar($events); ?>
    <h3 class="calendar-deadlines-heading">Class Deadlines</h3>
    <div class="table-responsive"><table class="data-table">
        <thead><tr><th>Date &amp; Time</th><th>Type</th><th>Activity / Deadline</th><th>Class</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($deadlines as $event): ?>
            <tr>
                <td><?= e(date('M j, Y g:i A', strtotime($event['deadline']))) ?></td>
                <td><?= e($event['activity_type']) ?></td>
                <td><?= e($event['title']) ?></td>
                <td><?= e($event['code']) ?></td>
                <td><?= strtotime($event['deadline']) < time() ? 'Past due' : 'Upcoming' ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$deadlines): ?><tr><td colspan="5">No assignment or quiz deadlines to show.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
