
# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- web_scheduler_task
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `web_scheduler_task`;

CREATE TABLE `web_scheduler_task`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `slug` VARCHAR(64) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `command_name` VARCHAR(255) NOT NULL,
    `command_arguments` TEXT,
    `strategy` VARCHAR(32) DEFAULT 'auto' NOT NULL,
    `secret` VARCHAR(128) NOT NULL,
    `enabled` TINYINT(1) DEFAULT 1 NOT NULL,
    `min_interval_seconds` INTEGER DEFAULT 0 NOT NULL,
    `max_runtime_seconds` INTEGER DEFAULT 0 NOT NULL,
    `ip_allowlist` TEXT,
    `last_triggered_at` DATETIME,
    `created_at` DATETIME,
    `updated_at` DATETIME,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `uk_web_scheduler_task_slug` (`slug`)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- web_scheduler_execution
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `web_scheduler_execution`;

CREATE TABLE `web_scheduler_execution`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `task_id` INTEGER NOT NULL,
    `triggered_at` DATETIME NOT NULL,
    `started_at` DATETIME,
    `finished_at` DATETIME,
    `status` VARCHAR(32) NOT NULL,
    `exit_code` INTEGER,
    `strategy_used` VARCHAR(32),
    `output` TEXT,
    `trigger_ip` VARCHAR(45),
    PRIMARY KEY (`id`),
    INDEX `idx_web_scheduler_execution_task_triggered` (`task_id`, `triggered_at`),
    CONSTRAINT `fk_web_scheduler_execution_task_id`
        FOREIGN KEY (`task_id`)
        REFERENCES `web_scheduler_task` (`id`)
        ON UPDATE RESTRICT
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- web_scheduler_capability
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `web_scheduler_capability`;

CREATE TABLE `web_scheduler_capability`
(
    `capability_key` VARCHAR(64) NOT NULL,
    `available` TINYINT(1) NOT NULL,
    `details` TEXT,
    `checked_at` DATETIME NOT NULL,
    PRIMARY KEY (`capability_key`)
) ENGINE=InnoDB;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
