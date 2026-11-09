-- Apply once after backing up the database.
CREATE TABLE IF NOT EXISTS `courses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL UNIQUE,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `academic_terms` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `fiscal_year` VARCHAR(50) NOT NULL,
    `semester` VARCHAR(50) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_academic_term` (`fiscal_year`, `semester`)
) ENGINE=InnoDB;

ALTER TABLE `classes`
    ADD COLUMN `course_id` INT NULL AFTER `id`,
    ADD COLUMN `academic_term_id` INT NULL AFTER `course_id`;

INSERT INTO `courses` (`name`)
SELECT DISTINCT `name`
FROM `classes`
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

INSERT INTO `academic_terms` (`fiscal_year`, `semester`)
SELECT DISTINCT
    COALESCE(NULLIF(TRIM(`fiscal_year`), ''), 'Unassigned'),
    COALESCE(NULLIF(TRIM(`semester`), ''), 'Unassigned')
FROM `classes`
ON DUPLICATE KEY UPDATE `semester` = VALUES(`semester`);

UPDATE `classes` AS class_offering
JOIN `courses` AS course ON course.`name` = class_offering.`name`
SET class_offering.`course_id` = course.`id`;

UPDATE `classes` AS class_offering
JOIN `academic_terms` AS academic_term
    ON academic_term.`fiscal_year` = COALESCE(NULLIF(TRIM(class_offering.`fiscal_year`), ''), 'Unassigned')
    AND academic_term.`semester` = COALESCE(NULLIF(TRIM(class_offering.`semester`), ''), 'Unassigned')
SET class_offering.`academic_term_id` = academic_term.`id`;

ALTER TABLE `classes`
    MODIFY COLUMN `course_id` INT NOT NULL,
    MODIFY COLUMN `academic_term_id` INT NOT NULL,
    ADD CONSTRAINT `fk_classes_course`
        FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE RESTRICT,
    ADD CONSTRAINT `fk_classes_academic_term`
        FOREIGN KEY (`academic_term_id`) REFERENCES `academic_terms`(`id`) ON DELETE RESTRICT,
    DROP COLUMN `name`,
    DROP COLUMN `fiscal_year`,
    DROP COLUMN `semester`;
