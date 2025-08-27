-- Initial data for the Survey Platform
-- This script populates the `roles` and `plans` tables with default values.

-- Populate `roles` table
INSERT INTO `roles` (`id`, `name`) VALUES
(1, 'admin'),
(2, 'creator')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Populate `plans` table (5 levels as requested)
INSERT INTO `plans` (`id`, `level`, `name`, `credits_allowance`, `can_use_predefined_themes`, `can_use_custom_themes`) VALUES
(1, 1, 'Free', 200, FALSE, FALSE),
(2, 2, 'Basic', 500, TRUE, FALSE),
(3, 3, 'Plus', 1000, TRUE, FALSE),
(4, 4, 'Pro', 2000, TRUE, TRUE),
(5, 5, 'Enterprise', -1, TRUE, TRUE) -- -1 can represent unlimited credits
ON DUPLICATE KEY UPDATE
  `level`=VALUES(`level`),
  `name`=VALUES(`name`),
  `credits_allowance`=VALUES(`credits_allowance`),
  `can_use_predefined_themes`=VALUES(`can_use_predefined_themes`),
  `can_use_custom_themes`=VALUES(`can_use_custom_themes`);
