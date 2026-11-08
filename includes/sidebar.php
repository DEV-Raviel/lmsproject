<?php
// includes/sidebar.php - Text-only Navigation with class-context support
$activePage   = $activePage ?? 'dashboard';
$basePath     = $basePath ?? '../';
$user         = $user ?? current_user();
$role         = strtolower($user['role'] ?? 'student');
$avatarInitial = strtoupper(substr((string) ($user['name'] ?? 'U'), 0, 1));
$classContext = $classContext ?? null;  // set by class-details.php
$activeTab    = $activeTab ?? null;

// Role-based Nav Structure
$menus = [
    'admin' => [
        'Account Administration' => [
            'accounts' => ['User Management', $basePath . 'admin/dashboard.php'],
            'recovery' => ['Account Recovery', $basePath . 'admin/recover_accounts.php'],
            'student-profiles' => ['Student Profiles', $basePath . 'admin/student_profiles.php'],
        ]
    ],
    'dean' => [
        'School Communications' => [
            'announcements' => ['Announcements', $basePath . 'admin/manage_announcements.php'],
            'calendar' => ['School Events', $basePath . 'admin/calendar.php'],
        ]
    ],
    'teacher' => [
        'Teacher Management' => [
            'dashboard' => ['Overview',          $basePath . 'teacher/dashboard.php'],
            'classes'   => ['My Classes',        $basePath . 'teacher/my-classes.php'],
            'students'  => ['Student List',      $basePath . 'teacher/students.php'],
            'calendar'  => ['Academic Calendar', $basePath . 'teacher/calendar.php'],
            'assignments' => ['Assignments', $basePath . 'teacher/assignments.php'],
            'gradebook' => ['Gradebook', $basePath . 'teacher/gradebook.php'],
            'quizzes' => ['Quiz Builder', $basePath . 'teacher/quizzes.php'],
            'materials' => ['Learning Materials', $basePath . 'teacher/learning-materials.php'],
            'profile' => ['My Profile', $basePath . 'teacher/teacher-profile.php'],
            'settings' => ['Settings', $basePath . 'teacher/settings.php'],
        ]
    ],
    'student' => [
        'Academic Core' => [
            'dashboard'    => ['Dashboard',      'dashboard.php'],
            'todo'         => ['To Do List',     'todo.php'],
            'my-class'     => ['My Classes',     'my-class.php'],
            'quizzes'      => ['Quizzes',        'quizzes.php'],
            'grade-report' => ['Grade Report',   'grade-report.php'],
        ],
        'Information & Resources' => [
            'student-info' => ['Student Profile', 'student-info.php'],
            'calendar'     => ['Calendar',        'calendar.php'],
            'oer'          => ['OER Library',     'oer.php'],
            'privacy'      => ['Privacy Policy',  'privacy.php'],
        ]
    ]
];

$currentMenu = $menus[$role] ?? $menus['student'];
?>
<nav class="sidebar" aria-label="<?= e(ucfirst($role)) ?> navigation">
    <div class="sidebar-profile">
        <span class="user-avatar user-avatar-large" aria-hidden="true"><?= e($avatarInitial) ?></span>
        <h3><?= e($user['name'] ?? 'User Name') ?></h3>
        <p><?= e($user['student_id'] ?? $user['user_code'] ?? 'ID: 2026-0001') ?></p>
    </div>

    <?php if ($role === 'student' && $classContext): ?>
        <!-- Class-context navigation: shown when viewing a specific class -->
        <div class="sidebar-back-link">
            <a href="dashboard.php" class="btn-sidebar-back">Back to Dashboard</a>
        </div>

        <div class="nav-section-title">
            <?= e($classContext['subject']) ?>
            <span class="nav-section-subtitle"><?= e($classContext['section']) ?></span>
        </div>
        <ul>
            <?php
            $classTabs = [
                'overview'      => 'Course Overview',
                'lessons'       => 'Lessons',
                'assessments'   => 'Assessments',
                'progress'      => 'Progress',
                'colleagues'    => 'Classmates',
                'announcements' => 'Announcements',
            ];
            foreach ($classTabs as $tabKey => $tabLabel):
            ?>
                <li>
                    <a href="class-details.php?id=<?= (int) $classContext['id'] ?>&tab=<?= $tabKey ?>"
                       class="<?= ($activeTab ?? ($_GET['tab'] ?? 'overview')) === $tabKey ? 'active' : '' ?>"
                       <?= ($activeTab ?? ($_GET['tab'] ?? 'overview')) === $tabKey ? 'aria-current="page"' : '' ?>>
                        <span><?= e($tabLabel) ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="nav-section-title">Quick Links</div>
        <ul>
            <li><a href="my-class.php"><span>All Classes</span></a></li>
            <li><a href="todo.php"><span>To Do List</span></a></li>
            <li><a href="grade-report.php"><span>Grade Report</span></a></li>
        </ul>

    <?php else: ?>
        <!-- Standard navigation -->
        <?php foreach ($currentMenu as $sectionTitle => $items): ?>
            <div class="nav-section-title"><?= e($sectionTitle) ?></div>
            <ul>
                <?php foreach ($items as $key => [$label, $href]): ?>
                    <li>
                        <a href="<?= e($href) ?>" class="<?= $key === $activePage ? 'active' : '' ?>"
                           <?= $key === $activePage ? 'aria-current="page"' : '' ?>>
                            <span><?= e($label) ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endforeach; ?>
    <?php endif; ?>
</nav>
