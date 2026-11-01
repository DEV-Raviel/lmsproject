<?php
require_once __DIR__ . '/../includes/teacher_init.php';

$pageTitle = 'Academic Calendar';
$activePage = 'calendar';
$stmt = $pdo->prepare(
    'SELECT a.title, a.deadline, c.code
     FROM assignments a JOIN classes c ON c.id = a.class_id
     WHERE c.teacher_id = ?
     ORDER BY a.deadline'
);
$stmt->execute([$user['id']]);
$events = $stmt->fetchAll();
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <div class="page-header"><h2>Academic Calendar</h2><p>Assignment deadlines for your classes.</p></div>
    <div class="table-responsive"><table class="data-table">
        <thead><tr><th>Date &amp; Time</th><th>Activity / Deadline</th><th>Class</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($events as $event): ?>
            <tr>
                <td><?= e(date('M j, Y g:i A', strtotime($event['deadline']))) ?></td>
                <td><?= e($event['title']) ?></td>
                <td><?= e($event['code']) ?></td>
                <td><?= strtotime($event['deadline']) < time() ? 'Past due' : 'Upcoming' ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$events): ?><tr><td colspan="4">No assignment deadlines to show.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
