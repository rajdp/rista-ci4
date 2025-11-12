-- =====================================================
-- Table: tbl_order
-- Purpose: Stores course orders/payments
-- Created: 2025-11-05
-- =====================================================

CREATE TABLE IF NOT EXISTS `tbl_order` (
  `order_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT(20) UNSIGNED NOT NULL COMMENT 'Foreign key to user table',
  `course_id` BIGINT(20) UNSIGNED DEFAULT NULL COMMENT 'Foreign key to tbl_course table',
  `school_id` BIGINT(20) UNSIGNED DEFAULT NULL COMMENT 'School identifier',
  `payment_id` VARCHAR(255) DEFAULT NULL COMMENT 'Payment/Order identifier',
  `payment_date` DATETIME DEFAULT NULL COMMENT 'Payment date',
  `payment_status` TINYINT(1) DEFAULT 0 COMMENT '1=Success, 0=Failed',
  `cart_data` TEXT DEFAULT NULL COMMENT 'JSON data containing course details, schedule, price, quantity',
  `total_amount` DECIMAL(10, 2) DEFAULT 0.00 COMMENT 'Total order amount',
  `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=Active, 0=Inactive',
  `created_by` BIGINT(20) UNSIGNED DEFAULT NULL COMMENT 'User ID who created the order',
  `created_date` DATETIME NOT NULL COMMENT 'Record creation timestamp',
  `modified_date` DATETIME DEFAULT NULL COMMENT 'Last modification timestamp',
  PRIMARY KEY (`order_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_course_id` (`course_id`),
  KEY `idx_school_id` (`school_id`),
  KEY `idx_payment_id` (`payment_id`),
  KEY `idx_payment_status` (`payment_status`),
  KEY `idx_created_date` (`created_date`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Course orders and payments';

-- =====================================================
-- Optional Foreign Key Constraints
-- Uncomment these if you want to enforce referential integrity
-- Make sure the referenced tables exist before running these
-- =====================================================

-- ALTER TABLE `tbl_order`
--   ADD CONSTRAINT `fk_order_user_id` 
--   FOREIGN KEY (`user_id`) 
--   REFERENCES `user` (`user_id`) 
--   ON DELETE CASCADE 
--   ON UPDATE CASCADE;

-- ALTER TABLE `tbl_order`
--   ADD CONSTRAINT `fk_order_course_id` 
--   FOREIGN KEY (`course_id`) 
--   REFERENCES `tbl_course` (`course_id`) 
--   ON DELETE SET NULL 
--   ON UPDATE CASCADE;

-- =====================================================
-- Verification query
-- =====================================================

-- Check if tbl_order table exists
SELECT 
    TABLE_NAME, 
    TABLE_TYPE,
    ENGINE,
    TABLE_ROWS
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = 'edquill_production' 
  AND TABLE_NAME = 'tbl_order';

