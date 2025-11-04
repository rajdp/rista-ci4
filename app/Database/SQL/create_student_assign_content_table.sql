-- =====================================================
-- Table: student_assign_content
-- Purpose: Stores content assignments to classes with time constraints
-- Created: 2025-10-30
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
-- Optional Foreign Key Constraints
-- Uncomment these if you want to enforce referential integrity
-- Make sure the referenced tables exist before running these
-- =====================================================

-- ALTER TABLE `student_assign_content`
--   ADD CONSTRAINT `fk_sac_class_id` 
--   FOREIGN KEY (`class_id`) 
--   REFERENCES `classes` (`id`) 
--   ON DELETE CASCADE 
--   ON UPDATE CASCADE;

-- ALTER TABLE `student_assign_content`
--   ADD CONSTRAINT `fk_sac_content_id` 
--   FOREIGN KEY (`content_id`) 
--   REFERENCES `content` (`id`) 
--   ON DELETE CASCADE 
--   ON UPDATE CASCADE;

-- ALTER TABLE `student_assign_content`
--   ADD CONSTRAINT `fk_sac_created_by` 
--   FOREIGN KEY (`created_by`) 
--   REFERENCES `users` (`id`) 
--   ON DELETE SET NULL 
--   ON UPDATE CASCADE;






