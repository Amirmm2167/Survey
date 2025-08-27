-- SQL Script to update the database schema for statistics and credit tracking.
-- Run this script AFTER all previous schema scripts.

-- 1. Create the `credit_transactions` table.
-- This table will log every time a user's credit balance changes.
CREATE TABLE IF NOT EXISTS `credit_transactions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `survey_id` INT NULL,
  `transaction_type` ENUM('survey_response', 'admin_add', 'plan_renewal', 'initial_credits') NOT NULL,
  `credits_changed` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`survey_id`) REFERENCES `surveys`(`id`) ON DELETE SET NULL,
  INDEX `idx_transaction_type` (`transaction_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Note:
-- `credits_changed` will be a negative number for expenses (e.g., -15 for a 15-question survey response).
-- `credits_changed` will be a positive number for additions (e.g., when an admin adds credits).
-- `survey_id` will be set for 'survey_response' types.
-- `transaction_type` 'admin_add' is for when an admin manually changes a user's credits.
-- `transaction_type` 'initial_credits' is for when a user is first created.
-- `transaction_type` 'plan_renewal' can be used in the future for monthly subscription refills.
