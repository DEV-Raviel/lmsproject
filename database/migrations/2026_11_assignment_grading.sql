ALTER TABLE `assignments`
    ADD COLUMN `points_possible` INT UNSIGNED NOT NULL DEFAULT 100 AFTER `deadline`;
