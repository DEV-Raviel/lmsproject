# e-GURO LMS Audit

**Audit date:** 2026-11-08
**Scope:** Current PHP/MySQL application, its role pages and handlers, schema,
authentication, uploads, navigation, and available automated tests.
**Baseline:** Branch `main` at `42aee9f`; the implementation work is being done
on `overhaul/role-based-lms-foundation`.

## Application map

| Area | Pages and handlers | Current responsibility |
|---|---|---|
| Sign-in and shared shell | `index.php`, `logout.php`, `reset-password.php`, `includes/auth.php`, `includes/header.php`, `includes/sidebar.php`, `includes/footer.php` | Login, session role checks, page shell, role-specific links, reset-link completion, and shared client scripts. |
| Administrator | `admin/dashboard.php`, `admin/recover_accounts.php`, `admin/student_profiles.php`; `actions/admin_actions.php`, `actions/account_recovery_action.php`, `actions/student_profile_actions.php` | Accounts, classes, enrollments, account recovery, and enrollment-profile maintenance. |
| Dean | `admin/manage_announcements.php`, `admin/calendar.php`; `actions/announcement_actions.php`, `actions/calendar_actions.php` | School announcements and calendar events. |
| Teacher | `teacher/dashboard.php`, `teacher/my-classes.php`, `teacher/students.php`, `teacher/assignments.php`, `teacher/quizzes.php`, `teacher/learning-materials.php`, `teacher/calendar.php`, `teacher/teacher-profile.php`, `teacher/settings.php` | Assigned classes, learners, teaching materials, assignments, quizzes, calendar, profile, and settings. Shared initialization is in `includes/teacher_init.php`. |
| Student | `student/dashboard.php`, `student/my-class.php`, `student/class-details.php`, `student/lessons.php`, `student/tasks.php`, `student/todo.php`, `student/quizzes.php`, `student/take-quiz.php`, `student/submit-work.php`, `student/grade-report.php`, `student/student-info.php`, `student/calendar.php`, `student/fiscal-year.php`, `student/account.php`, `student/notifications.php`, `student/oer.php`, `student/privacy.php`, `student/bulletin.php`, `student/bulletins.php` | Enrolled classes, course material, assignments, quizzes, calendar, profile, grades, and informational pages. Shared initialization is in `includes/student_init.php`. |
| Shared and persistence | `includes/student_data.php`, `includes/student_profile.php`, `includes/announcements.php`, `includes/calendar.php`, `includes/teacher_init.php`, `includes/widgets/bulletin-widget.php`, `config/db.php`, `database/schema.sql`, `database/migrations/` | Queries, rendering helpers, shared calendar/profile logic, connection settings, schema, and incremental upgrades. |
| Other POST handlers | `actions/assignment_actions.php`, `actions/task_actions.php` | Student work submissions and signed-in-user calendar task changes. |

All student-facing pages are PHP routes. The abandoned static profile copies in
`.backup-student-html/` are excluded from the application and are not linked by
the current navigation. No Composer, PHPUnit, npm, or Playwright test
configuration is present at the audit baseline.

## Findings

### High priority

1. **Login has no request throttling or CSRF token.** `actions/auth_action.php`
   accepts repeated POST attempts and redirects for invalid credentials, but
   has no rate limit. The login form in `index.php` posts without a CSRF token.
   The "Remember me" checkbox is rendered but is not implemented.
2. **There is a password-reset utility that resets every user to a known
   password.** The ignored local file `reset.php` updates every `users.password`
   to a hash of `password123`. It is not in Git, but is present in the web root
   and can be requested directly. It must not be used or exposed.
3. **New accounts accept an administrator-chosen password without a forced
   change.** `actions/admin_actions.php` accepts a password with an eight
   character minimum and stores it directly as the user's password hash. There
   is no first-login lock or change-password route.
4. **Account reset links do not currently force another password change.**
   `reset-password.php` accepts a new password and returns the user to sign-in;
   no password-change-required state is saved.
5. **Deleting a class can cascade-delete coursework and grades.**
   `actions/admin_actions.php` deletes a class directly. The schema configures
   cascading foreign keys from classes through enrollments, assignments,
   submissions, quizzes, attempts, and answers. There is no confirmation or
   protection for classes with graded work. User deletion similarly cascades
   historical profile, enrollment, and academic records.
6. **Student grade reporting still returns placeholder data.**
   `get_grades()` in `includes/student_data.php` returns static placeholder
   rows; there is no teacher gradebook or assignment grading interface.

### Medium priority

7. **The requested quick-login removal is not present in the audited source.**
   `index.php` still displays four "QUICK DEMO LOGINS" buttons and JavaScript
   that fills the password `password123`. No demo accounts have been deleted or
   are to be deleted as part of this request.
8. **Database connection errors disclose exception details.**
   `config/db.php` returns the PDO exception text to the page and uses hard-coded
   XAMPP root credentials. A deployment-safe configuration and generic
   user-facing error are needed.
9. **Navigation has no mobile drawer control.** The current sidebar is a
   desktop layout. `assets/js/sidebar-nav.js` only adds the skip-link target
   and scrolls the active link; it does not implement a hamburger or drawer.
   Several role menus exceed the requested 5–7 top-level grouped links.
10. **Long lists have no consistent server-rendered search/filter controls.**
    Teacher student lists and learning materials, Admin accounts/classes, and
    student task/grade lists are rendered without a shared query/filter
    contract. There is no gradebook or submissions list to filter yet.
11. **Authorization patterns are inconsistent between pages and handlers.**
    Student and teacher routes generally include role-specific initializers,
    and Dean/Admin tools call `require_role()`. The assignment handler uses
    `require_login()` followed by a manual role check; calendar/task handlers
    use `require_login()` and rely on per-record ownership checks. These should
    use one explicit policy consistently.
12. **Uploads are stored under the web root.** Teacher uploads use MIME and
    extension allowlists and randomized names, and student submission uploads
    apply similar checks. Files are still stored in `uploads/` below the
    document root and links are directly downloadable; serving sensitive
    submissions through an authorized download handler would provide stronger
    access control.

### Existing safeguards and behavior to preserve

- Role dashboards and tools are separated in navigation, and Admin/Dean pages
  have role checks.
- Most state-changing forms use session CSRF tokens; the login form is an
  exception.
- Database reads and writes inspected in the main role handlers use PDO
  prepared statements.
- Student assignment submission checks that the assignment belongs to one of
  the student's enrolled classes, checks the deadline, and prevents duplicate
  submissions.
- Teacher class queries are scoped to the signed-in teacher; the student list
  query only returns students enrolled in that teacher's assigned classes.
- Student class/material queries are scoped to the signed-in student.
- Quiz attempts and answers are unique per student/quiz and per
  attempt/question in the schema. Quiz submissions calculate scores on the
  server; input limits and scoring invariants need targeted tests during the
  academic-core phase.
- The profile CSV/manual workflow is Admin-only and the student profile query
  is scoped to the signed-in student's own ID.

## Delivery order

Implementation follows the requested eight phases, each committed separately
on the overhaul branch:

1. This audit document.
2. Shared authentication, CSRF, layout/navigation foundations, safe handling
   for PHP routes, prepared database access, uploads, and login throttling.
3. Remove only the quick-login controls and password-filling script from the
   login page. Leave all accounts, database seed rows, and other account data
   untouched.
4. Add forced password changes for Admin-created temporary credentials and
   for accounts reset by an Administrator; after saving, sign out and return
   the user to sign-in.
5. Complete and verify the core role workflows and protect historical academic
   records from accidental destructive deletion.
6. Add server-rendered search and filters to long lists, then provide a
   progressively enhanced mobile Filters control.
7. Add responsive sidebar/drawer navigation and a role-appropriate account
   menu while preserving direct route authorization.
8. Add automated tests using a separate test database and browser tests where
   the environment supports them; produce a completion report.

The only authorized account-related UI change at this point is removal of the
quick-login buttons on the sign-in page. Existing local and seeded accounts
must remain intact.
