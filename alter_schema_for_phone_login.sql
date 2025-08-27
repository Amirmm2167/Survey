-- SQL Script to update the database schema for phone number login.
-- Run this script AFTER the initial `schema.sql`.

-- 1. Add the new phone_number column to the users table.
-- It is set to be unique, so two users cannot share the same phone number.
ALTER TABLE `users` ADD COLUMN `phone_number` VARCHAR(50) NULL UNIQUE AFTER `email`;

-- 2. Modify the existing email column to be nullable.
ALTER TABLE `users` MODIFY COLUMN `email` VARCHAR(255) NULL;

-- 3. Add a check constraint to ensure at least one of email or phone_number is provided.
-- This is crucial for ensuring every user has at least one contact method.
ALTER TABLE `users` ADD CONSTRAINT `chk_email_or_phone` CHECK (`email` IS NOT NULL OR `phone_number` IS NOT NULL);
