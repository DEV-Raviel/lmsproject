<?php

function calendar_scope_for_role(string $role): string
{
    return match ($role) {
        'admin', 'dean' => 'admin',
        'teacher' => 'teacher',
        'student' => 'student',
        default => throw new InvalidArgumentException('Unsupported calendar role.'),
    };
}

function get_role_calendar_events(PDO $pdo, string $scope): array
{
    $stmt = $pdo->prepare(
        'SELECT id, title, description, event_date
         FROM calendar_events
         WHERE role_scope = ?
         ORDER BY event_date, title'
    );
    $stmt->execute([$scope]);

    return array_map(static fn(array $event): array => [
        'id' => (int) $event['id'],
        'title' => $event['title'],
        'description' => $event['description'] ?? '',
        'date' => $event['event_date'],
        'editable' => true,
        'source' => 'event',
    ], $stmt->fetchAll());
}

function render_calendar(array $events): void
{
    $calendarJson = json_encode(
        array_values($events),
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
    );
    ?>
    <section class="calendar-panel card" data-calendar data-events="<?= e($calendarJson) ?>">
        <div class="calendar-toolbar">
            <div>
                <h3 data-calendar-month aria-live="polite"></h3>
                <p>Events are visible to users with the same role.</p>
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
        <p class="calendar-help">Select a date to add an event. Select one of your role's events to edit or delete it.</p>
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
