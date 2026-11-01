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
    `submitted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
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
