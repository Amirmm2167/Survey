-- SQL Script to update the database schema for secure survey links.
-- Run this script AFTER the initial `schema.sql`.

-- 1. Add the new unique_id column to the surveys table.
-- It is set to be unique. We will add an index for faster lookups.
ALTER TABLE `surveys` ADD COLUMN `unique_id` VARCHAR(16) NULL UNIQUE AFTER `id`;

-- 2. Populate the unique_id for any existing surveys.
-- This ensures that old surveys will also have a unique link.
-- Note: This is a simple update. In a production environment with many rows,
-- this might need to be done in batches.
UPDATE `surveys` SET `unique_id` = LOWER(HEX(RANDOM_BYTES(8))) WHERE `unique_id` IS NULL;

-- 3. Now that all rows are populated, make the column NOT NULL.
ALTER TABLE `surveys` MODIFY COLUMN `unique_id` VARCHAR(16) NOT NULL;
