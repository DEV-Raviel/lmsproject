<?php

function calendar_scope_for_role(string $role): string
{
    return match ($role) {
        'dean' => 'school',
        'teacher' => 'teacher',
        'student' => 'student',
        default => throw new InvalidArgumentException('Unsupported calendar role.'),
    };
}

function get_role_calendar_events(PDO $pdo, string $scope): array
{
    $stmt = $pdo->prepare(
        'SELECT id, title, description, event_date, role_scope
         FROM calendar_events
         WHERE role_scope IN (?, \'school\')
         ORDER BY event_date, title'
    );
    $stmt->execute([$scope]);

    return array_map(static fn(array $event): array => [
        'id' => (int) $event['id'],
        'title' => $event['title'],
        'description' => $event['description'] ?? '',
        'date' => $event['event_date'],
        'editable' => $scope === $event['role_scope'],
        'source' => $event['role_scope'] === 'school' ? 'school' : 'event',
    ], $stmt->fetchAll());
}

function get_user_calendar_tasks(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare(
        'SELECT id, title, description, due_date, is_complete
         FROM user_tasks
         WHERE user_id = ?
         ORDER BY is_complete, due_date, id'
    );
    $stmt->execute([$userId]);

    return array_map(static fn(array $task): array => [
        'id' => (int) $task['id'],
        'title' => $task['title'],
        'description' => $task['description'] ?? '',
        'date' => $task['due_date'],
        'is_complete' => (bool) $task['is_complete'],
    ], $stmt->fetchAll());
}

function render_calendar(array $events, array $tasks = []): void
{
    foreach ($tasks as $task) {
        $events[] = [
            'id' => null,
            'title' => $task['title'],
            'description' => $task['description'],
            'date' => $task['date'],
            'editable' => false,
            'source' => 'task',
            'detail' => $task['is_complete'] ? 'Completed task' : 'Personal task',
        ];
    }
    $calendarJson = json_encode(
        array_values($events),
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
    );
    ?>
    <section class="calendar-panel card" data-calendar data-events="<?= e($calendarJson) ?>">
        <div class="calendar-toolbar">
            <div>
                <h3 data-calendar-month aria-live="polite"></h3>
                <p>School events are visible to everyone; role events stay within their role.</p>
            </div>
            <div class="calendar-actions">
                <button class="btn btn-outline btn-sm" type="button" data-calendar-today>Today</button>
                <button class="btn" type="button" data-calendar-add>+ Add Event</button>
            </div>
        </div>
        <div class="calendar-navigation" aria-label="Calendar navigation">
            <button type="button" class="btn btn-outline btn-sm" data-calendar-previous aria-label="Previous month">&larr; Previous</button>
            <button type="button" class="btn btn-outline btn-sm" data-calendar-next aria-label="Next month">Next &rarr;</button>
        </div>
        <div class="calendar-grid" role="grid" aria-label="Monthly calendar">
            <?php foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day): ?>
                <div class="calendar-weekday" role="columnheader"><?= e($day) ?></div>
            <?php endforeach; ?>
            <div class="calendar-days" data-calendar-days role="rowgroup"></div>
        </div>
        <p class="calendar-help">Select a date to add an event. You can edit your events; school-wide events are read-only outside the Dean account.</p>
    </section>

    <section class="personal-tasks card" aria-labelledby="personal-tasks-title">
        <div class="personal-tasks-header">
            <div>
                <p class="eyebrow">PERSONAL PRODUCTIVITY</p>
                <h3 id="personal-tasks-title">My Tasks</h3>
                <p>Tasks are private to your account and appear on the calendar by due date.</p>
            </div>
            <span class="task-count"><?= count(array_filter($tasks, static fn(array $task): bool => !$task['is_complete'])) ?> open</span>
        </div>
        <form class="personal-task-form" method="post" action="<?= e($GLOBALS['basePath'] ?? '../') ?>actions/task_actions.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">
            <label class="field">
                <span>Task</span>
                <input type="text" name="title" maxlength="160" placeholder="e.g. Prepare weekly lesson plan" required>
            </label>
            <label class="field">
                <span>Due date</span>
                <input type="date" name="due_date" required>
            </label>
            <label class="field task-description-field">
                <span>Notes <span class="muted">(optional)</span></span>
                <textarea name="description" rows="2" maxlength="2000"></textarea>
            </label>
            <button class="btn" type="submit">Add task</button>
        </form>
        <?php if ($tasks): ?>
            <ul class="personal-task-list">
                <?php foreach ($tasks as $task): ?>
                    <li class="personal-task-item <?= $task['is_complete'] ? 'is-complete' : '' ?>">
                        <div class="personal-task-info">
                            <strong><?= e($task['title']) ?></strong>
                            <time datetime="<?= e($task['date']) ?>"><?= e(date('M j, Y', strtotime($task['date']))) ?></time>
                            <?php if ($task['description'] !== ''): ?>
                                <p><?= nl2br(e($task['description'])) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="personal-task-actions">
                            <form method="post" action="<?= e($GLOBALS['basePath'] ?? '../') ?>actions/task_actions.php">
                                <?= csrf_field() ?>
                                <input type="hidden" name="task_id" value="<?= (int) $task['id'] ?>">
                                <button class="btn btn-outline btn-sm" type="submit" name="action" value="toggle">
                                    <?= $task['is_complete'] ? 'Reopen' : 'Complete' ?>
                                </button>
                            </form>
                            <details class="personal-task-edit">
                                <summary>Edit</summary>
                                <form method="post" action="<?= e($GLOBALS['basePath'] ?? '../') ?>actions/task_actions.php">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="task_id" value="<?= (int) $task['id'] ?>">
                                    <label class="field"><span>Task</span><input type="text" name="title" maxlength="160" value="<?= e($task['title']) ?>" required></label>
                                    <label class="field"><span>Due date</span><input type="date" name="due_date" value="<?= e($task['date']) ?>" required></label>
                                    <label class="field"><span>Notes</span><textarea name="description" rows="2" maxlength="2000"><?= e($task['description']) ?></textarea></label>
                                    <button class="btn btn-outline btn-sm" type="submit">Save changes</button>
                                </form>
                            </details>
                            <form method="post" action="<?= e($GLOBALS['basePath'] ?? '../') ?>actions/task_actions.php">
                                <?= csrf_field() ?>
                                <input type="hidden" name="task_id" value="<?= (int) $task['id'] ?>">
                                <button class="btn btn-danger btn-sm" type="submit" name="action" value="delete">Delete</button>
                            </form>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="empty-state">No personal tasks yet. Add one above to keep a reminder on your calendar.</p>
        <?php endif; ?>
    </section>

    <dialog class="calendar-dialog" data-calendar-dialog aria-labelledby="calendar-dialog-title">
        <form method="post" action="<?= e($GLOBALS['basePath'] ?? '../') ?>actions/calendar_actions.php" data-calendar-form>
            <?= csrf_field() ?>
            <input type="hidden" name="event_id" value="">
            <div class="calendar-dialog-header">
                <h3 id="calendar-dialog-title" data-calendar-dialog-title>Add event</h3>
                <button class="calendar-close" type="button" data-calendar-close aria-label="Close dialog">&times;</button>
            </div>
            <label class="field">
                <span>Event title</span>
                <input type="text" name="title" maxlength="160" required>
            </label>
            <label class="field">
                <span>Description <span class="muted">(optional)</span></span>
                <textarea name="description" rows="3" maxlength="2000"></textarea>
            </label>
            <label class="field">
                <span>Date</span>
                <input type="date" name="event_date" required>
            </label>
            <div class="calendar-dialog-actions">
                <button class="btn btn-danger btn-sm" type="submit" name="action" value="delete" data-calendar-delete hidden>Delete</button>
                <span class="calendar-dialog-spacer"></span>
                <button class="btn btn-outline btn-sm" type="button" data-calendar-close>Cancel</button>
                <button class="btn btn-sm" type="submit" name="action" value="save">Save Event</button>
            </div>
        </form>
    </dialog>
    <script src="<?= e($GLOBALS['basePath'] ?? '../') ?>assets/js/calendar.js?v=<?= (int) filemtime(__DIR__ . '/../assets/js/calendar.js') ?>" defer></script>
    <?php
}
