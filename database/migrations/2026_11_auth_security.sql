CREATE TABLE IF NOT EXISTS `login_attempts` (
    `bucket_key` CHAR(64) NOT NULL PRIMARY KEY,
    `failure_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `window_started_at` DATETIME NOT NULL,
    `locked_until` DATETIME NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_login_attempts_locked_until` (`locked_until`)
) ENGINE=InnoDB;
