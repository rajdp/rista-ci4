-- Create crm_followups table
CREATE TABLE IF NOT EXISTS `crm_followups` (
    `followup_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `school_id` BIGINT(20) UNSIGNED NOT NULL,
    `action` VARCHAR(255) NOT NULL COMMENT 'Description of the follow-up action',
    `owner_user_id` BIGINT(20) UNSIGNED NULL COMMENT 'User assigned to complete this follow-up',
    `due_date` DATE NULL COMMENT 'Due date for the follow-up',
    `status` ENUM('pending', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    `related_type` VARCHAR(40) NULL COMMENT 'Type of related entity (e.g., registration, course_registration)',
    `related_id` BIGINT(20) UNSIGNED NULL COMMENT 'ID of the related entity',
    `notes` TEXT NULL COMMENT 'Additional notes about the follow-up',
    `completed_at` TIMESTAMP NULL COMMENT 'When the follow-up was completed',
    `completed_by` BIGINT(20) UNSIGNED NULL COMMENT 'User who completed the follow-up',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_by` BIGINT(20) UNSIGNED NULL COMMENT 'User who created the follow-up',
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`followup_id`),
    KEY `ix_followup_by_owner_due` (`school_id`, `owner_user_id`, `due_date`),
    KEY `ix_followup_by_status_due` (`school_id`, `status`, `due_date`),
    KEY `ix_followup_related` (`related_type`, `related_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

