-- Create student_custom_items table manually
-- Run this SQL script if migrations are blocked

CREATE TABLE IF NOT EXISTS `student_custom_items` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id` BIGINT(20) UNSIGNED NOT NULL COMMENT 'References user.user_id',
    `school_id` BIGINT(20) UNSIGNED NOT NULL,
    `description` VARCHAR(255) NOT NULL COMMENT 'User-entered description of the item',
    `amount` DECIMAL(12,2) NOT NULL COMMENT 'Amount (positive for charges, negative for discounts)',
    `start_date` DATE NOT NULL COMMENT 'Validity start date',
    `end_date` DATE NULL COMMENT 'Validity end date (optional)',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Whether this item is currently active',
    `created_by` BIGINT(20) UNSIGNED NULL COMMENT 'User who created this item',
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    KEY `idx_student_school` (`student_id`, `school_id`),
    KEY `idx_dates` (`start_date`, `end_date`),
    KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;






