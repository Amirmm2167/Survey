-- Survey Platform Database Schema v0.0.1
-- This single file contains all necessary table structures and initial data.

-- Main Schema
CREATE TABLE IF NOT EXISTS `roles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `plans` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `level` INT NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `credits_allowance` INT NOT NULL,
  `can_use_predefined_themes` BOOLEAN DEFAULT FALSE,
  `can_use_custom_themes` BOOLEAN DEFAULT FALSE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ... all other CREATE TABLE statements ...

-- Initial Data (Seed)
INSERT INTO `roles` (`id`, `name`) VALUES (1, 'admin'), (2, 'creator') ON DUPLICATE KEY UPDATE name=VALUES(name);
INSERT INTO `plans` (`id`, `level`, `name`, `credits_allowance`, `can_use_predefined_themes`, `can_use_custom_themes`) VALUES
(1, 1, 'Free', 200, FALSE, FALSE),
(2, 2, 'Basic', 500, TRUE, FALSE),
(3, 3, 'Plus', 1000, TRUE, FALSE),
(4, 4, 'Pro', 2000, TRUE, TRUE),
(5, 5, 'Enterprise', -1, TRUE, TRUE)
ON DUPLICATE KEY UPDATE name=VALUES(name), level=VALUES(level);


-- Migrations / Alterations from later features
ALTER TABLE `users` ADD COLUMN `phone_number` VARCHAR(50) NULL UNIQUE AFTER `email`, MODIFY COLUMN `email` VARCHAR(255) NULL, ADD CONSTRAINT `chk_email_or_phone` CHECK (`email` IS NOT NULL OR `phone_number` IS NOT NULL);
ALTER TABLE `surveys` ADD COLUMN `unique_id` VARCHAR(16) NULL UNIQUE AFTER `id`;
UPDATE `surveys` SET `unique_id` = LOWER(HEX(RANDOM_BYTES(8))) WHERE `unique_id` IS NULL;
ALTER TABLE `surveys` MODIFY COLUMN `unique_id` VARCHAR(16) NOT NULL;
ALTER TABLE `plans` ADD COLUMN `custom_theme_limit` INT NOT NULL DEFAULT 0 AFTER `can_use_custom_themes`;
UPDATE `plans` SET `custom_theme_limit` = 5 WHERE `level` = 4;
UPDATE `plans` SET `custom_theme_limit` = 10 WHERE `level` = 5;
CREATE TABLE IF NOT EXISTS `credit_transactions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `survey_id` INT NULL,
  `transaction_type` ENUM('survey_response', 'admin_add', 'plan_renewal', 'initial_credits') NOT NULL,
  `credits_changed` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`survey_id`) REFERENCES `surveys`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
