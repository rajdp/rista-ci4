-- =====================================================
-- Migration: Add modified_date column to user_token table
-- Purpose: Fix logout functionality that requires modified_date
-- Created: 2025-10-30
-- =====================================================

-- Add modified_date column if it doesn't exist
ALTER TABLE `user_token` 
ADD COLUMN IF NOT EXISTS `modified_date` DATETIME NULL COMMENT 'Last modification timestamp' 
AFTER `created_date`;

-- Optionally, update existing records to have modified_date = created_date
UPDATE `user_token` 
SET `modified_date` = `created_date` 
WHERE `modified_date` IS NULL;








