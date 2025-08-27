-- Survey Platform Database Schema
-- This script contains all the table definitions for the project.

-- Use `IF NOT EXISTS` to prevent errors if the script is run multiple times.

-- `roles`: Defines user roles (e.g., 'admin', 'creator').
CREATE TABLE IF NOT EXISTS `roles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- `plans`: Defines the 5 subscription tiers for creators.
CREATE TABLE IF NOT EXISTS `plans` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `level` INT NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `credits_allowance` INT NOT NULL, -- Monthly credit allowance
  `can_use_predefined_themes` BOOLEAN DEFAULT FALSE,
  `can_use_custom_themes` BOOLEAN DEFAULT FALSE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- `users`: Stores user account information.
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- `subscriptions`: Links users to their current plan and tracks its validity.
CREATE TABLE IF NOT EXISTS `subscriptions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE, -- A user has one active subscription at a time
  `plan_id` INT NOT NULL,
  `start_date` DATETIME NOT NULL,
  `end_date` DATETIME NOT NULL,
  `is_active` BOOLEAN DEFAULT TRUE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
  FOREIGN KEY (`plan_id`) REFERENCES `plans`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- `wallets`: Manages the credit balance for each creator.
CREATE TABLE IF NOT EXISTS `wallets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `balance` INT NOT NULL DEFAULT 0,
  `reserved_balance` INT NOT NULL DEFAULT 0,
  `last_refill_date` DATETIME,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- `themes`: Stores predefined survey themes for creators.
CREATE TABLE IF NOT EXISTS `themes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `css_path` VARCHAR(255) NOT NULL -- Path to the theme's CSS file
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- `surveys`: The main table for storing survey information.
CREATE TABLE IF NOT EXISTS `surveys` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `creator_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `status` ENUM('draft', 'published', 'closed') NOT NULL DEFAULT 'draft',
  `access_level` ENUM('public', 'private_code') NOT NULL DEFAULT 'public',
  `access_code` VARCHAR(50) NULL, -- For private surveys
  `theme_id` INT NULL, -- For predefined themes
  `custom_css` TEXT NULL, -- For custom styling by high-tier users
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`creator_id`) REFERENCES `users`(`id`),
  FOREIGN KEY (`theme_id`) REFERENCES `themes`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- `questions`: Stores individual questions for each survey.
CREATE TABLE IF NOT EXISTS `questions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `survey_id` INT NOT NULL,
  `question_text` TEXT NOT NULL,
  `question_type` ENUM('text', 'textarea', 'radio', 'checkbox', 'dropdown', 'rating_stars') NOT NULL,
  `display_order` INT NOT NULL,
  `is_required` BOOLEAN DEFAULT TRUE,
  FOREIGN KEY (`survey_id`) REFERENCES `surveys`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- `question_options`: Stores the options for multiple-choice style questions.
CREATE TABLE IF NOT EXISTS `question_options` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `question_id` INT NOT NULL,
  `option_text` VARCHAR(255) NOT NULL,
  `display_order` INT NOT NULL,
  FOREIGN KEY (`question_id`) REFERENCES `questions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- `respondents`: Identifies a single survey-taking session to group answers.
CREATE TABLE IF NOT EXISTS `respondents` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `survey_id` INT NOT NULL,
  `session_identifier` VARCHAR(255) NOT NULL, -- e.g., a unique session token
  `started_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `completed_at` TIMESTAMP NULL,
  UNIQUE KEY `survey_session` (`survey_id`, `session_identifier`),
  FOREIGN KEY (`survey_id`) REFERENCES `surveys`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- `answers`: Stores the actual answers provided by respondents.
CREATE TABLE IF NOT EXISTS `answers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `respondent_id` INT NOT NULL,
  `question_id` INT NOT NULL,
  `answer_text` TEXT, -- For text, textarea, rating
  `selected_option_id` INT, -- For radio, checkbox, dropdown
  FOREIGN KEY (`respondent_id`) REFERENCES `respondents`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`question_id`) REFERENCES `questions`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`selected_option_id`) REFERENCES `question_options`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- `answer_selections`: Handles multiple selections for checkbox questions.
CREATE TABLE IF NOT EXISTS `answer_selections` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `answer_id` INT NOT NULL,
  `selected_option_id` INT NOT NULL,
  FOREIGN KEY (`answer_id`) REFERENCES `answers`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`selected_option_id`) REFERENCES `question_options`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
