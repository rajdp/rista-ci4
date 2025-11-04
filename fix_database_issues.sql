-- =====================================================
-- Quick Fix for Database Issues (2025-10-30)
-- Run this SQL in phpMyAdmin or MySQL client
-- Database: edquill_production
-- =====================================================

-- Issue 1: Add modified_date column to user_token table
-- This fixes the logout error
-- =====================================================
ALTER TABLE `user_token` 
ADD COLUMN `modified_date` DATETIME NULL COMMENT 'Last modification timestamp' 
AFTER `created_date`;

-- Update existing records
UPDATE `user_token` 
SET `modified_date` = `created_date` 
WHERE `modified_date` IS NULL;

-- =====================================================
-- Issue 2: Create student_assign_content table
-- This fixes the content assignment error
-- =====================================================
CREATE TABLE IF NOT EXISTS `student_assign_content` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `class_id` BIGINT(20) UNSIGNED DEFAULT NULL COMMENT 'Foreign key to classes table',
  `content_id` BIGINT(20) UNSIGNED DEFAULT NULL COMMENT 'Foreign key to content table',
  `start_date` DATE NOT NULL COMMENT 'Assignment start date',
  `end_date` DATE NOT NULL DEFAULT '0000-00-00' COMMENT 'Assignment end date (0000-00-00 means no end date)',
  `start_time` TIME NOT NULL DEFAULT '00:00:00' COMMENT 'Daily start time for content access',
  `end_time` TIME NOT NULL DEFAULT '23:59:00' COMMENT 'Daily end time for content access',
  `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=Active, 0=Inactive',
  `created_by` BIGINT(20) UNSIGNED DEFAULT NULL COMMENT 'User ID who created the assignment',
  `created_date` DATETIME NOT NULL COMMENT 'Record creation timestamp',
  PRIMARY KEY (`id`),
  KEY `idx_class_id` (`class_id`),
  KEY `idx_content_id` (`content_id`),
  KEY `idx_status` (`status`),
  KEY `idx_class_content` (`class_id`, `content_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Content assignments to classes with scheduling';

-- =====================================================
-- Verification queries
-- =====================================================

-- Check if modified_date column exists
SELECT 
    COLUMN_NAME, 
    DATA_TYPE, 
    IS_NULLABLE, 
    COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'edquill_production' 
  AND TABLE_NAME = 'user_token'
  AND COLUMN_NAME = 'modified_date';

-- Check if student_assign_content table exists
SELECT 
    TABLE_NAME, 
    TABLE_TYPE,
    ENGINE,
    TABLE_ROWS
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = 'edquill_production' 
  AND TABLE_NAME = 'student_assign_content';

-- =====================================================
-- Done! Both issues should now be fixed.
-- =====================================================






