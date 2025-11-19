-- Provider and Payment System Tables
-- MySQL 5.7 Compatible Version (No Foreign Keys)
-- Run this migration to create the required tables

-- =====================================================
-- SCHEMA CREATION
-- =====================================================

-- Provider Types (SMS, Email, Payment)
CREATE TABLE IF NOT EXISTS `provider_types` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_provider_types_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Providers (Twilio, Stripe, etc.)
CREATE TABLE IF NOT EXISTS `providers` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `provider_type_id` int(11) unsigned NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `logo_url` varchar(500) DEFAULT NULL,
  `documentation_url` varchar(500) DEFAULT NULL,
  `features` json DEFAULT NULL,
  `config_schema` json DEFAULT NULL COMMENT 'JSON Schema for required configuration fields',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_providers_code` (`code`),
  KEY `idx_providers_type` (`provider_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- School Provider Configurations
CREATE TABLE IF NOT EXISTS `school_provider_configs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(11) unsigned NOT NULL,
  `provider_id` int(11) unsigned NOT NULL,
  `credentials` text NOT NULL COMMENT 'Encrypted JSON credentials',
  `settings` json DEFAULT NULL COMMENT 'Non-sensitive settings',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_test_mode` tinyint(1) NOT NULL DEFAULT 0,
  `priority` int(11) NOT NULL DEFAULT 0 COMMENT 'For fallback ordering',
  `last_used_at` timestamp NULL DEFAULT NULL,
  `last_error_at` timestamp NULL DEFAULT NULL,
  `error_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_school_provider` (`school_id`, `provider_id`),
  KEY `idx_spc_provider` (`provider_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Student Payment Methods
CREATE TABLE IF NOT EXISTS `student_payment_methods` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(11) unsigned NOT NULL,
  `school_id` int(11) unsigned NOT NULL,
  `provider_id` int(11) unsigned NOT NULL,
  `payment_token` varchar(500) NOT NULL COMMENT 'Gateway token',
  `token_type` enum('card', 'ach', 'bank_account', 'other') NOT NULL DEFAULT 'card',
  `display_info` json NOT NULL COMMENT 'Card last4, brand, expiry etc',
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `billing_address` json DEFAULT NULL,
  `authorized_at` timestamp NULL DEFAULT NULL,
  `authorized_by` int(11) unsigned DEFAULT NULL,
  `authorization_ip` varchar(45) DEFAULT NULL,
  `authorization_user_agent` varchar(500) DEFAULT NULL,
  `gateway_customer_id` varchar(255) DEFAULT NULL,
  `gateway_payment_method_id` varchar(255) DEFAULT NULL,
  `gateway_metadata` json DEFAULT NULL,
  `expires_at` date DEFAULT NULL,
  `expiry_notification_sent` tinyint(1) NOT NULL DEFAULT 0,
  `verification_status` varchar(50) DEFAULT NULL,
  `verification_attempts` int(11) NOT NULL DEFAULT 0,
  `verified_at` timestamp NULL DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `total_charges` int(11) NOT NULL DEFAULT 0,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_spm_student` (`student_id`),
  KEY `idx_spm_school` (`school_id`),
  KEY `idx_spm_provider` (`provider_id`),
  KEY `idx_spm_default` (`student_id`, `is_default`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payment Transactions
CREATE TABLE IF NOT EXISTS `payment_transactions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(11) unsigned NOT NULL,
  `student_id` int(11) unsigned NOT NULL,
  `payment_method_id` int(11) unsigned DEFAULT NULL,
  `provider_id` int(11) unsigned NOT NULL,
  `transaction_type` enum('charge', 'refund', 'authorization', 'capture', 'void') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'USD',
  `gateway_transaction_id` varchar(255) DEFAULT NULL,
  `gateway_response` json DEFAULT NULL,
  `gateway_fee` decimal(8,2) DEFAULT NULL,
  `status` enum('pending', 'processing', 'succeeded', 'failed', 'refunded', 'partially_refunded', 'cancelled', 'disputed') NOT NULL DEFAULT 'pending',
  `failure_code` varchar(100) DEFAULT NULL,
  `failure_message` varchar(500) DEFAULT NULL,
  `retry_count` int(11) NOT NULL DEFAULT 0,
  `invoice_id` int(11) unsigned DEFAULT NULL,
  `enrollment_id` int(11) unsigned DEFAULT NULL,
  `fee_id` int(11) unsigned DEFAULT NULL,
  `course_id` int(11) unsigned DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  `internal_notes` text DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `refunded_amount` decimal(12,2) DEFAULT NULL,
  `refund_reason` varchar(500) DEFAULT NULL,
  `parent_transaction_id` int(11) unsigned DEFAULT NULL,
  `receipt_url` varchar(500) DEFAULT NULL,
  `receipt_sent` tinyint(1) NOT NULL DEFAULT 0,
  `receipt_sent_at` timestamp NULL DEFAULT NULL,
  `processed_by` int(11) unsigned DEFAULT NULL,
  `processed_by_ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pt_school` (`school_id`),
  KEY `idx_pt_student` (`student_id`),
  KEY `idx_pt_payment_method` (`payment_method_id`),
  KEY `idx_pt_provider` (`provider_id`),
  KEY `idx_pt_status` (`status`),
  KEY `idx_pt_gateway_txn` (`gateway_transaction_id`),
  KEY `idx_pt_invoice` (`invoice_id`),
  KEY `idx_pt_parent` (`parent_transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Provider Usage Logs
CREATE TABLE IF NOT EXISTS `provider_usage_logs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(11) unsigned NOT NULL,
  `provider_id` int(11) unsigned NOT NULL,
  `action` varchar(100) NOT NULL,
  `status` enum('success', 'failure', 'pending') NOT NULL DEFAULT 'pending',
  `request_data` json DEFAULT NULL,
  `response_data` json DEFAULT NULL,
  `error_message` varchar(500) DEFAULT NULL,
  `duration_ms` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pul_school` (`school_id`),
  KEY `idx_pul_provider` (`provider_id`),
  KEY `idx_pul_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SEED DATA
-- =====================================================

-- Insert default provider types
INSERT INTO `provider_types` (`code`, `name`, `description`) VALUES
('sms', 'SMS', 'SMS messaging providers'),
('email', 'Email', 'Email service providers'),
('payment', 'Payment', 'Payment processing providers')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Insert default providers
INSERT INTO `providers` (`provider_type_id`, `code`, `name`, `description`, `features`, `config_schema`) VALUES
-- SMS Providers
((SELECT id FROM provider_types WHERE code = 'sms'), 'twilio', 'Twilio', 'Cloud communications platform',
 '["sms", "mms", "voice"]',
 '{"account_sid": {"type": "string", "required": true}, "auth_token": {"type": "string", "required": true, "secret": true}, "from_number": {"type": "string", "required": true}}'),
((SELECT id FROM provider_types WHERE code = 'sms'), 'textgrid', 'TextGrid', 'SMS messaging platform',
 '["sms"]',
 '{"api_key": {"type": "string", "required": true, "secret": true}, "from_number": {"type": "string", "required": true}}'),

-- Email Providers
((SELECT id FROM provider_types WHERE code = 'email'), 'sendgrid', 'SendGrid', 'Email delivery service',
 '["transactional", "marketing", "templates"]',
 '{"api_key": {"type": "string", "required": true, "secret": true}, "from_email": {"type": "string", "required": true}, "from_name": {"type": "string"}}'),
((SELECT id FROM provider_types WHERE code = 'email'), 'aws_ses', 'Amazon SES', 'AWS Simple Email Service',
 '["transactional", "bulk"]',
 '{"access_key": {"type": "string", "required": true}, "secret_key": {"type": "string", "required": true, "secret": true}, "region": {"type": "string", "required": true}, "from_email": {"type": "string", "required": true}}'),
((SELECT id FROM provider_types WHERE code = 'email'), 'smtp', 'SMTP', 'Generic SMTP server',
 '["transactional"]',
 '{"host": {"type": "string", "required": true}, "port": {"type": "number", "required": true}, "username": {"type": "string"}, "password": {"type": "string", "secret": true}, "encryption": {"type": "string"}, "from_email": {"type": "string", "required": true}}'),

-- Payment Providers
((SELECT id FROM provider_types WHERE code = 'payment'), 'stripe', 'Stripe', 'Online payment processing',
 '["card", "ach", "recurring", "refunds"]',
 '{"publishable_key": {"type": "string", "required": true}, "secret_key": {"type": "string", "required": true, "secret": true}, "webhook_secret": {"type": "string", "secret": true}}'),
((SELECT id FROM provider_types WHERE code = 'payment'), 'forte', 'CSG Forte', 'Payment processing platform',
 '["card", "ach", "recurring"]',
 '{"organization_id": {"type": "string", "required": true}, "location_id": {"type": "string", "required": true}, "api_access_id": {"type": "string", "required": true}, "api_secure_key": {"type": "string", "required": true, "secret": true}}')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `features` = VALUES(`features`);
