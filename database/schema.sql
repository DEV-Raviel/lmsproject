-- e-GURO LMS Database Schema
-- Compatible with MySQL 5.7+ and MariaDB 10.3+

CREATE DATABASE IF NOT EXISTS `lms_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `lms_db`;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_code` VARCHAR(50) NOT NULL UNIQUE,
    `full_name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `must_change_password` TINYINT(1) NOT NULL DEFAULT 0,
    `role` ENUM('admin', 'dean', 'teacher', 'student') NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Login throttling buckets; only hashed identifiers and IP addresses are stored.
CREATE TABLE IF NOT EXISTS `login_attempts` (
    `bucket_key` CHAR(64) NOT NULL PRIMARY KEY,
    `failure_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `window_started_at` DATETIME NOT NULL,
    `locked_until` DATETIME NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_login_attempts_locked_until` (`locked_until`)
) ENGINE=InnoDB;

-- 2. Student enrollment profile responses
CREATE TABLE IF NOT EXISTS `student_profiles` (
    `user_id` INT NOT NULL PRIMARY KEY,
    `classification` VARCHAR(100) NULL,
    `program_course` VARCHAR(200) NULL,
    `major` VARCHAR(150) NULL,
    `year_level` VARCHAR(50) NULL,
    `previous_school_name` VARCHAR(200) NULL,
    `previous_school_address` TEXT NULL,
    `previous_program_name` VARCHAR(200) NULL,
    `gwa` DECIMAL(5,2) NULL,
    `first_name` VARCHAR(100) NULL,
    `middle_name` VARCHAR(100) NULL,
    `last_name` VARCHAR(100) NULL,
    `suffix` VARCHAR(30) NULL,
    `gender` VARCHAR(50) NULL,
    `civil_status` VARCHAR(50) NULL,
    `religion` VARCHAR(100) NULL,
    `date_of_birth` DATE NULL,
    `place_of_birth` VARCHAR(200) NULL,
    `barangay` VARCHAR(150) NULL,
    `home_address` TEXT NULL,
    `recovery_email` VARCHAR(254) NULL,
    `ccc_email` VARCHAR(254) NULL,
    `contact_number` VARCHAR(50) NULL,
    `working_student` ENUM('Yes', 'No') NULL,
    `household_4ps` ENUM('Yes', 'No') NULL,
    `is_pwd` ENUM('Yes', 'No') NULL,
    `is_solo_parent` ENUM('Yes', 'No') NULL,
    `is_indigenous_people` ENUM('Yes', 'No') NULL,
    `father_name` VARCHAR(200) NULL,
    `father_contact_number` VARCHAR(50) NULL,
    `father_occupation` VARCHAR(150) NULL,
    `mother_name` VARCHAR(200) NULL,
    `mother_contact_number` VARCHAR(50) NULL,
    `mother_occupation` VARCHAR(150) NULL,
    `guardian_name` VARCHAR(200) NULL,
    `guardian_contact_number` VARCHAR(50) NULL,
    `guardian_address` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_student_profiles_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 2. Classes Table
CREATE TABLE IF NOT EXISTS `classes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(150) NOT NULL,
    `teacher_id` INT NULL,
    `fiscal_year` VARCHAR(50) DEFAULT '2026-2027',
    `semester` VARCHAR(50) DEFAULT '1st Semester',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`teacher_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 3. Class Enrollments
CREATE TABLE IF NOT EXISTS `class_enrollments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `class_id` INT NOT NULL,
    `student_id` INT NOT NULL,
    `enrolled_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_enrollment` (`class_id`, `student_id`),
    FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4. Study Materials
CREATE TABLE IF NOT EXISTS `study_materials` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `class_id` INT NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `uploaded_by` INT NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 5. Assignments & Quizzes
CREATE TABLE IF NOT EXISTS `assignments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `class_id` INT NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `instructions` TEXT NULL,
    `file_path` VARCHAR(255) NULL,
    `deadline` DATETIME NOT NULL,
    `points_possible` INT UNSIGNED NOT NULL DEFAULT 100,
    `created_by` INT NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. Submissions
CREATE TABLE IF NOT EXISTS `submissions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `assignment_id` INT NOT NULL,
    `student_id` INT NOT NULL,
    `file_path` VARCHAR(255) NULL,
    `score` INT DEFAULT NULL,
    `submitted_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`assignment_id`) REFERENCES `assignments`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 7. Teacher-created quizzes
CREATE TABLE IF NOT EXISTS `quizzes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `class_id` INT NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT NULL,
    `deadline` DATETIME NOT NULL,
    `duration_minutes` INT NULL,
    `created_by` INT NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_quizzes_class_deadline` (`class_id`, `deadline`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `quiz_questions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `quiz_id` INT NOT NULL,
    `question_text` TEXT NOT NULL,
    `question_type` ENUM('multiple_choice', 'short_answer') NOT NULL,
    `points` INT NOT NULL DEFAULT 1,
    `correct_answer` TEXT NULL,
    `position` INT NOT NULL,
    FOREIGN KEY (`quiz_id`) REFERENCES `quizzes`(`id`) ON DELETE CASCADE,
    INDEX `idx_quiz_questions_position` (`quiz_id`, `position`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `quiz_options` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `question_id` INT NOT NULL,
    `option_text` VARCHAR(500) NOT NULL,
    `is_correct` TINYINT(1) NOT NULL DEFAULT 0,
    `position` INT NOT NULL,
    FOREIGN KEY (`question_id`) REFERENCES `quiz_questions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `quiz_attempts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `quiz_id` INT NOT NULL,
    `student_id` INT NOT NULL,
    `score` INT NOT NULL DEFAULT 0,
    `max_score` INT NOT NULL DEFAULT 0,
    `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `submitted_at` DATETIME NULL DEFAULT NULL,
    UNIQUE KEY `unique_quiz_attempt` (`quiz_id`, `student_id`),
    FOREIGN KEY (`quiz_id`) REFERENCES `quizzes`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `quiz_answers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `attempt_id` INT NOT NULL,
    `question_id` INT NOT NULL,
    `answer_text` TEXT NULL,
    `is_correct` TINYINT(1) NULL,
    `points_awarded` INT NOT NULL DEFAULT 0,
    UNIQUE KEY `unique_attempt_question` (`attempt_id`, `question_id`),
    FOREIGN KEY (`attempt_id`) REFERENCES `quiz_attempts`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`question_id`) REFERENCES `quiz_questions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 8. School-wide and role-specific calendar events
CREATE TABLE IF NOT EXISTS `calendar_events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `role_scope` ENUM('school', 'teacher', 'student') NOT NULL,
    `title` VARCHAR(160) NOT NULL,
    `description` TEXT NULL,
    `event_date` DATE NOT NULL,
    `created_by` INT NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_calendar_events_scope_date` (`role_scope`, `event_date`),
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 9. Personal tasks shown on each user's role calendar
CREATE TABLE IF NOT EXISTS `user_tasks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(160) NOT NULL,
    `description` TEXT NULL,
    `due_date` DATE NOT NULL,
    `is_complete` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_tasks_owner_date` (`user_id`, `due_date`, `is_complete`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 10. Dean-managed school announcements
CREATE TABLE IF NOT EXISTS `announcements` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(200) NOT NULL,
    `body` TEXT NOT NULL,
    `is_published` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` INT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_announcements_published_created` (`is_published`, `created_at`),
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 11. One-time password recovery links
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `token_hash` CHAR(64) NOT NULL UNIQUE,
    `expires_at` DATETIME NOT NULL,
    `used_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_password_reset_user_expiry` (`user_id`, `expires_at`, `used_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Seed Default Accounts (Default password for all demo accounts is 'password123')
INSERT INTO `users` (`user_code`, `full_name`, `email`, `password`, `role`) VALUES
('ADMIN-01', 'System Administrator', 'admin@eguro.edu.ph', '$2y$12$KRv2jRc9oCuq5qNFBjSVXOFRz6l20qpYf1LjtM4Xp0nzHEiI5m9tC', 'admin'),
('DEAN-01', 'Dean Maria Santos', 'dean@eguro.edu.ph', '$2y$12$KRv2jRc9oCuq5qNFBjSVXOFRz6l20qpYf1LjtM4Xp0nzHEiI5m9tC', 'dean'),
('TECH-01', 'Prof. Juan Dela Cruz', 'teacher@eguro.edu.ph', '$2y$12$KRv2jRc9oCuq5qNFBjSVXOFRz6l20qpYf1LjtM4Xp0nzHEiI5m9tC', 'teacher'),
('TECH-02', 'Prof. Sarah Connor', 'sconnor@eguro.edu.ph', '$2y$12$KRv2jRc9oCuq5qNFBjSVXOFRz6l20qpYf1LjtM4Xp0nzHEiI5m9tC', 'teacher'),
('2026-001', 'Bruno Mars', 'student@eguro.edu.ph', '$2y$12$KRv2jRc9oCuq5qNFBjSVXOFRz6l20qpYf1LjtM4Xp0nzHEiI5m9tC', 'student'),
('2026-002', 'Ada Lovelace', 'ada@eguro.edu.ph', '$2y$12$KRv2jRc9oCuq5qNFBjSVXOFRz6l20qpYf1LjtM4Xp0nzHEiI5m9tC', 'student')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Repair demo passwords created by the original invalid seed hash.
UPDATE `users`
SET `password` = '$2y$12$KRv2jRc9oCuq5qNFBjSVXOFRz6l20qpYf1LjtM4Xp0nzHEiI5m9tC'
WHERE `password` = '$2y$10$4.o7Z9g9c8WqEfZK1k.Gge17u6o2u9Xqj6N2z7O7eYq1bA7Yg8Pbe';

-- Seed Classes
INSERT INTO `classes` (`code`, `name`, `teacher_id`, `fiscal_year`, `semester`) VALUES
('2-CS1', 'Web Systems & Technologies', 3, '2026-2027', '1st Semester'),
('2-CS2', 'Database Management Systems', 3, '2026-2027', '1st Semester'),
('2-CS4', 'Data Structures & Algorithms', 4, '2026-2027', '1st Semester')
ON DUPLICATE KEY UPDATE `id`=`id`;

INSERT IGNORE INTO `class_enrollments` (`class_id`, `student_id`)
SELECT c.`id`, u.`id`
FROM `classes` c
JOIN `users` u ON u.`role` = 'student'
WHERE (u.`user_code` = '2026-001' AND c.`code` IN ('2-CS1', '2-CS2'))
   OR (u.`user_code` = '2026-002' AND c.`code` = '2-CS4');
