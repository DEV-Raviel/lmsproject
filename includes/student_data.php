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

function get_tasks(string $view, string $search = ''): array
{
    $user = current_user();
    if (!$user || !in_array($view, ['due-today', 'assigned', 'missed', 'due-assessment', 'assigned-assessment'], true)) {
        return [];
    }
    $search = mb_substr(trim($search), 0, 100);
    $assignmentSearchFilter = $search !== '' ? ' AND (a.title LIKE ? OR c.code LIKE ?)' : '';
    $quizSearchFilter = $search !== '' ? ' AND (q.title LIKE ? OR c.code LIKE ?)' : '';
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
           $assignmentSearchFilter
         UNION ALL
         SELECT q.id, q.title, c.code AS subject, q.deadline AS activity_deadline, 'quiz' AS task_type
         FROM quizzes q
         JOIN class_enrollments ce ON ce.class_id = q.class_id AND ce.student_id = ?
         JOIN classes c ON c.id = q.class_id
         LEFT JOIN quiz_attempts qa ON qa.quiz_id = q.id AND qa.student_id = ?
         WHERE qa.submitted_at IS NULL AND $quizDateFilter
           $quizSearchFilter
         ORDER BY activity_deadline"
    );
    $parameters = [$user['id'], $user['id']];
    if ($search !== '') {
        array_push($parameters, '%' . $search . '%', '%' . $search . '%');
    }
    array_push($parameters, $user['id'], $user['id']);
    if ($search !== '') {
        array_push($parameters, '%' . $search . '%', '%' . $search . '%');
    }
    $stmt->execute($parameters);
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

function get_academic_classes_for_current_user(): array
{
    $user = current_user();
    if (!$user) {
        return [];
    }

    $query = 'SELECT c.id, c.code, course.name, academic_term.fiscal_year, academic_term.semester,
                     teacher.full_name AS teacher
              FROM classes c
              JOIN courses course ON course.id = c.course_id
              JOIN academic_terms academic_term ON academic_term.id = c.academic_term_id
              LEFT JOIN users teacher ON teacher.id = c.teacher_id';
    $parameters = [];
    $conditions = ["academic_term.fiscal_year IS NOT NULL", "TRIM(academic_term.fiscal_year) <> ''"];
    if ($user['role'] === 'student') {
        $query .= ' JOIN class_enrollments enrollment ON enrollment.class_id = c.id';
        $conditions[] = 'enrollment.student_id = ?';
        $parameters[] = (int) $user['id'];
    } elseif ($user['role'] === 'teacher') {
        $conditions[] = 'c.teacher_id = ?';
        $parameters[] = (int) $user['id'];
    } elseif (!in_array($user['role'], ['admin', 'dean'], true)) {
        return [];
    }

    $query .= ' WHERE ' . implode(' AND ', $conditions);
    $query .= ' ORDER BY academic_term.fiscal_year DESC, academic_term.semester, c.code';

    $stmt = Database::connect()->prepare($query);
    $stmt->execute($parameters);
    return $stmt->fetchAll();
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
        'SELECT c.id, c.code AS section, course.name AS subject, c.teacher_id,
                academic_term.fiscal_year, academic_term.semester,
                u.full_name AS teacher, u.email AS teacher_email,
                u.facebook_url AS teacher_facebook_url,
                u.profile_photo_path AS teacher_photo
         FROM class_enrollments ce
         JOIN classes c ON c.id = ce.class_id
         JOIN courses course ON course.id = c.course_id
         JOIN academic_terms academic_term ON academic_term.id = c.academic_term_id
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
        'SELECT c.id, c.code AS section, course.name AS subject, c.teacher_id,
                academic_term.fiscal_year, academic_term.semester,
                u.full_name AS teacher, u.email AS teacher_email,
                u.facebook_url AS teacher_facebook_url,
                u.profile_photo_path AS teacher_photo
         FROM class_enrollments ce
         JOIN classes c ON c.id = ce.class_id
         JOIN courses course ON course.id = c.course_id
         JOIN academic_terms academic_term ON academic_term.id = c.academic_term_id
         LEFT JOIN users u ON u.id = c.teacher_id
         WHERE ce.student_id = ? AND c.id = ?'
    );
    $stmt->execute([$user['id'], $id]);
    return $stmt->fetch() ?: null;
}

function get_grades(string $search = '', string $status = ''): array
{
    $user = current_user();
    if (!$user) {
        return [];
    }
    $search = mb_substr(trim($search), 0, 100);
    $allowedStatuses = ['Not submitted', 'Awaiting grade', 'Graded', 'Not started', 'In progress'];
    if (!in_array($status, $allowedStatuses, true)) {
        $status = '';
    }

    $query = "SELECT * FROM (
         SELECT c.code AS subject, teacher.full_name AS teacher, a.title AS assessment,
                'Assignment' AS type,
                CASE WHEN s.score IS NULL THEN '--'
                     ELSE CONCAT(s.score, '/', a.points_possible) END AS score,
                CASE WHEN s.id IS NULL THEN 'Not submitted'
                     WHEN s.score IS NULL THEN 'Awaiting grade'
                     ELSE 'Graded' END AS status,
                a.deadline AS due_date
         FROM class_enrollments ce
         JOIN classes c ON c.id = ce.class_id
         LEFT JOIN users teacher ON teacher.id = c.teacher_id
         JOIN assignments a ON a.class_id = c.id
         LEFT JOIN submissions s ON s.assignment_id = a.id AND s.student_id = ce.student_id
         WHERE ce.student_id = ?
         UNION ALL
         SELECT c.code AS subject, teacher.full_name AS teacher, q.title AS assessment,
                'Quiz' AS type,
                CASE WHEN qa.submitted_at IS NULL THEN '--'
                     ELSE CONCAT(qa.score, '/', qa.max_score) END AS score,
                CASE WHEN qa.id IS NULL THEN 'Not started'
                     WHEN qa.submitted_at IS NULL THEN 'In progress'
                     ELSE 'Graded' END AS status,
                q.deadline AS due_date
         FROM class_enrollments ce
         JOIN classes c ON c.id = ce.class_id
         LEFT JOIN users teacher ON teacher.id = c.teacher_id
         JOIN quizzes q ON q.class_id = c.id
         LEFT JOIN quiz_attempts qa ON qa.quiz_id = q.id AND qa.student_id = ce.student_id
         WHERE ce.student_id = ?
    ) AS grade_items WHERE 1 = 1";
    $parameters = [$user['id'], $user['id']];
    if ($search !== '') {
        $query .= ' AND (subject LIKE ? OR teacher LIKE ? OR assessment LIKE ? OR type LIKE ?)';
        $term = '%' . $search . '%';
        array_push($parameters, $term, $term, $term, $term);
    }
    if ($status !== '') {
        $query .= ' AND status = ?';
        $parameters[] = $status;
    }
    $query .= ' ORDER BY due_date DESC, assessment';
    $stmt = Database::connect()->prepare($query);
    $stmt->execute($parameters);
    $grades = $stmt->fetchAll();
    return array_map(static function (array $grade): array {
        $grade['due_date'] = date('M j, Y g:i A', strtotime($grade['due_date']));
        return $grade;
    }, $grades);
}

function get_lessons(string $search = ''): array
{
    $user = current_user();
    if (!$user) {
        return [];
    }

    $search = mb_substr(trim($search), 0, 100);
    $query = 'SELECT m.title, c.code AS subject, m.file_path, m.created_at
              FROM study_materials m
              JOIN classes c ON c.id = m.class_id
              JOIN class_enrollments ce ON ce.class_id = c.id AND ce.student_id = ?';
    $parameters = [$user['id']];
    if ($search !== '') {
        $query .= ' WHERE (m.title LIKE ? OR m.description LIKE ? OR c.code LIKE ?)';
        $term = '%' . $search . '%';
        array_push($parameters, $term, $term, $term);
    }
    $query .= ' ORDER BY m.created_at DESC, m.title';
    $stmt = Database::connect()->prepare($query);
    $stmt->execute($parameters);
    $materials = $stmt->fetchAll();

    return array_map(static function (array $material): array {
        return [
            $material['title'],
            $material['subject'],
            ['href' => '../' . $material['file_path'], 'text' => 'Open resource'],
            'Available',
        ];
    }, $materials);
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
        "SELECT u.id, u.full_name AS name, u.user_code AS student_id, u.email,
                u.facebook_url, u.profile_photo_path
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
               CASE WHEN s.id IS NULL THEN 'Pending'
                    WHEN s.score IS NULL THEN 'Submitted'
                    ELSE 'Graded' END AS status,
                CASE WHEN s.score IS NULL THEN '--'
                     ELSE CONCAT(s.score, '/', a.points_possible) END AS score,
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
