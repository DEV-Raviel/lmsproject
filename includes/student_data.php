<?php
// includes/student_data.php - data layer for the student pages.
// Shared data helpers for student-facing pages.

/** URL ?view=... => page title + filters. Whitelisted so users can't ask for anything else. */
const TASK_VIEWS = [
    'due-today'           => ['title' => 'Due Today - Activity and Quiz', 'last_col' => 'Action'],
    'assigned'            => ['title' => 'Assigned - Activity and Quiz',  'last_col' => 'Action'],
    'missed'              => ['title' => 'Missed Activities, Quizzes and Assessments', 'last_col' => 'Status'],
    'due-assessment'      => ['title' => 'Due Today - Quizzes',           'last_col' => 'Action'],
    'assigned-assessment' => ['title' => 'Assigned - Quizzes',            'last_col' => 'Action'],
];

function ago(string $modifier): string
{
    return date('Y-m-d H:i:s', strtotime($modifier));
}

function get_task_counts(): array
{
    $dueToday = get_tasks('due-today');
    $assigned = get_tasks('assigned');
    $missed = get_tasks('missed');
    $dueAssessments = get_tasks('due-assessment');
    $assignedAssessments = get_tasks('assigned-assessment');
    $classes = get_classes();
    $materials = 0;
    if ($classes) {
        $pdo = Database::connect();
        $ids = array_map(static fn(array $class): int => (int) $class['id'], $classes);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM study_materials WHERE class_id IN ($placeholders)");
        $stmt->execute($ids);
        $materials = (int) $stmt->fetchColumn();
    }
    return [
        'due_today' => count($dueToday),
        'assigned' => count($assigned),
        'missed' => count($missed),
        'due_assessment' => count($dueAssessments),
        'assigned_assessment' => count($assignedAssessments),
        'unread_lessons' => $materials,
    ];
}

function get_tasks(string $view): array
{
    $user = current_user();
    if (!$user || !in_array($view, ['due-today', 'assigned', 'missed', 'due-assessment', 'assigned-assessment'], true)) {
        return [];
    }
    [$assignmentDateFilter, $quizDateFilter] = match ($view) {
        'due-today' => ['DATE(a.deadline) = CURDATE()', 'DATE(q.deadline) = CURDATE()'],
        'assigned' => ['DATE(a.deadline) > CURDATE()', 'DATE(q.deadline) > CURDATE()'],
        'missed' => ['DATE(a.deadline) < CURDATE()', 'DATE(q.deadline) < CURDATE()'],
        'due-assessment' => ['1 = 0', 'DATE(q.deadline) = CURDATE()'],
        'assigned-assessment' => ['1 = 0', 'DATE(q.deadline) > CURDATE()'],
    };
    $status = match ($view) {
        'due-today', 'due-assessment' => 'Due today',
        'assigned', 'assigned-assessment' => 'Assigned',
        'missed' => 'Missed',
    };
    $pdo = Database::connect();
    $stmt = $pdo->prepare(
        "SELECT a.id, a.title, c.code AS subject, a.deadline AS activity_deadline, 'assignment' AS task_type
         FROM assignments a
         JOIN class_enrollments ce ON ce.class_id = a.class_id AND ce.student_id = ?
         JOIN classes c ON c.id = a.class_id
         LEFT JOIN submissions s ON s.assignment_id = a.id AND s.student_id = ?
         WHERE s.id IS NULL AND $assignmentDateFilter
         UNION ALL
         SELECT q.id, q.title, c.code AS subject, q.deadline AS activity_deadline, 'quiz' AS task_type
         FROM quizzes q
         JOIN class_enrollments ce ON ce.class_id = q.class_id AND ce.student_id = ?
         JOIN classes c ON c.id = q.class_id
         LEFT JOIN quiz_attempts qa ON qa.quiz_id = q.id AND qa.student_id = ?
         WHERE qa.submitted_at IS NULL AND $quizDateFilter
         ORDER BY activity_deadline"
    );
    $stmt->execute([$user['id'], $user['id'], $user['id'], $user['id']]);
    $tasks = $stmt->fetchAll();
    return array_map(static function (array $task) use ($status): array {
        return [
            'id' => (int) $task['id'],
            'title' => $task['title'],
            'subject' => $task['subject'],
            'deadline' => date('M j, Y g:i A', strtotime($task['activity_deadline'])),
            'status' => $status,
            'type' => $task['task_type'],
        ];
    }, $tasks);
}

function get_fiscal_year(): array
{
    return ['year' => '2026-2027', 'semester' => '1st Semester'];
}

function get_academic_periods(): array
{
    return [['1st Semester', 'Current'], ['2nd Semester', 'Upcoming'], ['Summer', 'Upcoming']];
}

function get_bulletins(?int $limit = null): array
{
    $all = [
        ['id' => 1, 'title' => '[ INFO BULLETIN 1 ]', 'body' => 'Bulletin content goes here.', 'created_at' => ago('-9 days')],
        ['id' => 2, 'title' => '[ INFO BULLETIN 2 ]', 'body' => 'Bulletin content goes here.', 'created_at' => ago('-9 days')],
        ['id' => 3, 'title' => '[ INFO BULLETIN 3 ]', 'body' => 'Bulletin content goes here.', 'created_at' => ago('-3 months')],
        ['id' => 4, 'title' => '[ INFO BULLETIN 4 ]', 'body' => 'Bulletin content goes here.', 'created_at' => ago('-3 months')],
    ];
    return $limit ? array_slice($all, 0, $limit) : $all;
}

function get_bulletin(int $id): ?array
{
    foreach (get_bulletins() as $b) {
        if ($b['id'] === $id) {
            return $b;
        }
    }
    return null;
}

function get_classes(): array
{
    $user = current_user();
    if (!$user) {
        return [];
    }
    $pdo = Database::connect();
    $stmt = $pdo->prepare(
        'SELECT c.id, c.code AS section, c.name AS subject, u.full_name AS teacher
         FROM class_enrollments ce
         JOIN classes c ON c.id = ce.class_id
         LEFT JOIN users u ON u.id = c.teacher_id
         WHERE ce.student_id = ?
         ORDER BY c.code'
    );
    $stmt->execute([$user['id']]);
    return $stmt->fetchAll();
}

function get_class_by_id(int $id): ?array
{
    $user = current_user();
    if (!$user) {
        return null;
    }
    $pdo = Database::connect();
    $stmt = $pdo->prepare(
        'SELECT c.id, c.code AS section, c.name AS subject, u.full_name AS teacher
         FROM class_enrollments ce
         JOIN classes c ON c.id = ce.class_id
         LEFT JOIN users u ON u.id = c.teacher_id
         WHERE ce.student_id = ? AND c.id = ?'
    );
    $stmt->execute([$user['id'], $id]);
    return $stmt->fetch() ?: null;
}

function get_grades(): array
{
    return [
        ['[SUBJECT]', '[TEACHER]', '--', '--', '--', 'Pending'],
        ['[SUBJECT]', '[TEACHER]', '--', '--', '--', 'Pending'],
    ];
}

function get_lessons(): array
{
    return [['[LESSON TITLE]', '[SUBJECT]', ['href' => 'oer.php', 'text' => 'Open Resource'], 'Unread']];
}

function get_notifications(): array
{
    return [
        ['[NOTIFICATION 1]', '[DATE]'],
        ['[NOTIFICATION 2]', '[DATE]'],
        ['[NOTIFICATION 3]', '[DATE]'],
    ];
}

function get_oer_resources(): array
{
    return [
        ['Khan Academy', 'https://www.khanacademy.org/',   'Learning materials and practice'],
        ['W3Schools',    'https://www.w3schools.com/',     'HTML and web development'],
        ['OpenLearn',    'https://www.open.edu/openlearn/', 'Free learning resources'],
    ];
}

/* ─── Class-detail sub-pages ─────────────────────────────────────────── */

function get_class_colleagues(int $classId): array
{
    $user = current_user();
    if (!$user) {
        return [];
    }
    $pdo = Database::connect();
    $stmt = $pdo->prepare(
        "SELECT u.full_name AS name, u.user_code AS student_id, u.email
         FROM class_enrollments ce
         JOIN class_enrollments viewer ON viewer.class_id = ce.class_id AND viewer.student_id = ?
         JOIN users u ON u.id = ce.student_id AND u.role = 'student'
         WHERE ce.class_id = ? AND ce.student_id <> ?
         ORDER BY u.full_name"
    );
    $stmt->execute([$user['id'], $classId, $user['id']]);
    return $stmt->fetchAll();
}

function get_class_overview(int $classId): array
{
    $class = get_class_by_id($classId);
    return [
        'Subject'         => $class['subject'] ?? '[ Subject ]',
        'Section'         => $class['section'] ?? '[ Section ]',
        'Instructor'      => $class['teacher'] ?? '[ Instructor ]',
        'Schedule'        => '[ Day and Time ]',
        'Room'            => '[ Room Number ]',
        'Units'           => '3',
        'Description'     => 'Course description will be provided by the instructor.',
    ];
}

function get_class_lessons(int $classId): array
{
    if (!get_class_by_id($classId)) {
        return [];
    }
    $pdo = Database::connect();
    $stmt = $pdo->prepare(
        'SELECT id, title, COALESCE(description, \'\') AS topic, created_at AS date_posted, file_path
         FROM study_materials WHERE class_id = ? ORDER BY created_at DESC'
    );
    $stmt->execute([$classId]);
    $lessons = $stmt->fetchAll();
    return array_map(static function (array $lesson): array {
        $lesson['status'] = 'Available';
        return $lesson;
    }, $lessons);
}

function get_class_assessments(int $classId): array
{
    $user = current_user();
    if (!$user || !get_class_by_id($classId)) {
        return [];
    }
    $pdo = Database::connect();
    $stmt = $pdo->prepare(
        "SELECT a.id, a.title, 'Activity' AS type, a.deadline AS due_date,
                CASE WHEN s.id IS NULL THEN 'Pending' ELSE 'Submitted' END AS status,
                COALESCE(CAST(s.score AS CHAR), '--') AS score,
                'assignment' AS item_type
         FROM assignments a
         LEFT JOIN submissions s ON s.assignment_id = a.id AND s.student_id = ?
         WHERE a.class_id = ?
         UNION ALL
         SELECT q.id, q.title, 'Quiz' AS type, q.deadline AS due_date,
                CASE WHEN qa.submitted_at IS NULL THEN 'Pending' ELSE 'Submitted' END AS status,
                CASE WHEN qa.submitted_at IS NULL THEN '--'
                     ELSE CONCAT(qa.score, '/', qa.max_score) END AS score,
                'quiz' AS item_type
         FROM quizzes q
         LEFT JOIN quiz_attempts qa ON qa.quiz_id = q.id AND qa.student_id = ?
         WHERE q.class_id = ?
         ORDER BY due_date"
    );
    $stmt->execute([$user['id'], $classId, $user['id'], $classId]);
    return $stmt->fetchAll();
}

function get_class_progress(int $classId): array
{
    return [
        'attendance'          => '[ 90% ]',
        'lessons_completed'   => '2 / 3',
        'assessments_passed'  => '1 / 3',
        'current_grade'       => '[ Pending ]',
        'prelim'              => '--',
        'midterm'             => '--',
        'finals'              => '--',
        'remarks'             => 'In Progress',
    ];
}

function get_class_announcements(int $classId): array
{
    return [
        ['id' => 1, 'title' => '[ Announcement 1 ]', 'body' => 'Details about this announcement.', 'posted_by' => '[ Instructor ]', 'created_at' => ago('-1 day')],
        ['id' => 2, 'title' => '[ Announcement 2 ]', 'body' => 'Details about this announcement.', 'posted_by' => '[ Instructor ]', 'created_at' => ago('-4 days')],
    ];
}
