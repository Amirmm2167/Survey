-- SQL Script to update the database schema for advanced theming features.
-- Run this script AFTER the initial `schema.sql`.

-- 1. Add a `custom_theme_limit` column to the `plans` table.
-- This will control how many custom themes a user on a given plan can create.
ALTER TABLE `plans` ADD COLUMN `custom_theme_limit` INT NOT NULL DEFAULT 0 AFTER `can_use_custom_themes`;

-- Update the existing plans with their theme limits as per the requirements.
UPDATE `plans` SET `custom_theme_limit` = 5 WHERE `level` = 4;
UPDATE `plans` SET `custom_theme_limit` = 10 WHERE `level` = 5;


-- 2. Create the `custom_themes` table.
-- This table will store the themes designed by users.
CREATE TABLE IF NOT EXISTS `custom_themes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `creator_id` INT NOT NULL,
  `theme_name` VARCHAR(100) NOT NULL,

  -- Color palette for the theme
  `color_primary` VARCHAR(7) NOT NULL DEFAULT '#77b9df',   -- Buttons, links, main accents
  `color_background` VARCHAR(7) NOT NULL DEFAULT '#FFFFFF', -- Page background
  `color_text` VARCHAR(7) NOT NULL DEFAULT '#333333',       -- Main text color
  `color_accent` VARCHAR(7) NOT NULL DEFAULT '#ff7452',    -- Secondary accents, highlights
  `color_panel_bg` VARCHAR(7) NOT NULL DEFAULT '#f8f9fa',   -- Background of panels/cards

  -- Font selection for Level 5 users
  `font_family` VARCHAR(100) NULL, -- e.g., 'Roboto', 'Open Sans', 'Lato'

  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`creator_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- 3. Add a `custom_theme_id` column to the `surveys` table.
-- This allows a survey to be linked to a user-created theme.
ALTER TABLE `surveys` ADD COLUMN `custom_theme_id` INT NULL DEFAULT NULL AFTER `theme_id`;
ALTER TABLE `surveys` ADD CONSTRAINT `fk_custom_theme` FOREIGN KEY (`custom_theme_id`) REFERENCES `custom_themes`(`id`) ON DELETE SET NULL;

-- Note: The application logic should ensure that a survey uses either a predefined `theme_id` OR a `custom_theme_id`, not both.
