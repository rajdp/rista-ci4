-- EdQuill V2 Tables Creation Script
-- Run this directly if migrations are blocked

-- Event Outbox (school-scoped)
CREATE TABLE IF NOT EXISTS t_event_outbox (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  school_id BIGINT UNSIGNED NOT NULL,
  event_type VARCHAR(80) NOT NULL,
  payload_json TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  claimed_by VARCHAR(64) NULL,
  claimed_at TIMESTAMP NULL,
  processed_at TIMESTAMP NULL,
  KEY ix_outbox_lookup (school_id, event_type, created_at),
  KEY ix_outbox_claim (processed_at, claimed_by, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Audit log (school-scoped)
CREATE TABLE IF NOT EXISTS t_audit_log (
  audit_id BIGINT AUTO_INCREMENT PRIMARY KEY,
  school_id BIGINT UNSIGNED NOT NULL,
  actor_user_id BIGINT NULL,
  entity_type VARCHAR(40),
  entity_id BIGINT NULL,
  action VARCHAR(40),
  before_json TEXT NULL,
  after_json TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY ix_audit_lookup (school_id, entity_type, entity_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Feature flags (school-scoped)
CREATE TABLE IF NOT EXISTS t_feature_flag (
  school_id BIGINT UNSIGNED NOT NULL,
  flag_key VARCHAR(64) NOT NULL,
  flag_value TEXT NOT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (school_id, flag_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Messaging templates (school-scoped)
CREATE TABLE IF NOT EXISTS t_message_template (
  template_id BIGINT AUTO_INCREMENT PRIMARY KEY,
  school_id BIGINT UNSIGNED NOT NULL,
  channel ENUM('email','sms','whatsapp') NOT NULL,
  purpose VARCHAR(64) NOT NULL,
  subject VARCHAR(160) NULL,
  body MEDIUMTEXT NOT NULL,
  locale VARCHAR(8) NOT NULL DEFAULT 'en',
  version INT NOT NULL DEFAULT 1,
  UNIQUE KEY ux_tpl (school_id, channel, purpose, version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Message logs (school-scoped)
CREATE TABLE IF NOT EXISTS t_message_log (
  msg_id BIGINT AUTO_INCREMENT PRIMARY KEY,
  school_id BIGINT UNSIGNED NOT NULL,
  channel ENUM('email','sms','whatsapp') NOT NULL,
  to_parent_id BIGINT NULL,
  to_student_id BIGINT NULL,
  template_id BIGINT NULL,
  rendered_body MEDIUMTEXT NOT NULL,
  status ENUM('queued','sent','failed','bounced','delivered','opened','clicked') NOT NULL DEFAULT 'queued',
  provider_id VARCHAR(128) NULL,
  sent_at TIMESTAMP NULL,
  opened_at TIMESTAMP NULL,
  clicked_at TIMESTAMP NULL,
  KEY ix_msg_by_parent (school_id, to_parent_id, sent_at),
  KEY ix_msg_by_channel (school_id, channel, sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Marketing KPI Daily (school-scoped)
CREATE TABLE IF NOT EXISTS t_marketing_kpi_daily (
  school_id BIGINT UNSIGNED NOT NULL,
  day DATE NOT NULL,
  source VARCHAR(64) NOT NULL DEFAULT '',
  leads INT NOT NULL DEFAULT 0,
  enrollments INT NOT NULL DEFAULT 0,
  revenue_cents BIGINT NOT NULL DEFAULT 0,
  PRIMARY KEY (school_id, day, source)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Revenue Daily (school-scoped)
CREATE TABLE IF NOT EXISTS t_revenue_daily (
  school_id BIGINT UNSIGNED NOT NULL,
  day DATE NOT NULL,
  mrr_cents BIGINT NOT NULL DEFAULT 0,
  arr_cents BIGINT NOT NULL DEFAULT 0,
  on_time_pay_pct DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  ar_overdue_cents BIGINT NOT NULL DEFAULT 0,
  PRIMARY KEY (school_id, day)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

