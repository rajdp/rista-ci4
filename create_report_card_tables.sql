-- EdQuill Report Cards Tables
-- Run this with: mysql -u root -proot -P 8889 -h 127.0.0.1 edquill_production < create_report_card_tables.sql

-- Table 1: t_rc_template (Report Card Templates - versioned)
CREATE TABLE IF NOT EXISTS t_rc_template (
  template_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  school_id BIGINT(20) UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  version INT(11) NOT NULL DEFAULT 1,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  schema_json TEXT NOT NULL COMMENT 'JSON defining sections/fields/scales (stored as TEXT)',
  created_by BIGINT(20) UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (template_id),
  UNIQUE KEY ux_school_name_version (school_id, name, version),
  KEY ix_school_active (school_id, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table 2: t_rc_scale (Grading Scales)
CREATE TABLE IF NOT EXISTS t_rc_scale (
  scale_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  school_id BIGINT(20) UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  scale_json TEXT NOT NULL COMMENT 'e.g., [{min:90,max:100,letter:A,gpa:4.0}, ...]',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (scale_id),
  UNIQUE KEY ux_school_name (school_id, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table 3: t_report_card (Report Card Master)
CREATE TABLE IF NOT EXISTS t_report_card (
  rc_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  school_id BIGINT(20) UNSIGNED NOT NULL,
  student_id BIGINT(20) UNSIGNED NOT NULL,
  class_id BIGINT(20) UNSIGNED NULL COMMENT 'References class table',
  term VARCHAR(60) NOT NULL COMMENT 'e.g., Fall, Q1, 2025 Spring',
  academic_year VARCHAR(20) NOT NULL COMMENT 'e.g., 2025-26',
  template_id BIGINT(20) UNSIGNED NOT NULL,
  template_version INT(11) NOT NULL,
  status ENUM('draft','ready','issued','revised','revoked') NOT NULL DEFAULT 'draft',
  issued_at DATETIME NULL,
  issued_by BIGINT(20) UNSIGNED NULL,
  current_version INT(11) NOT NULL DEFAULT 1 COMMENT 'increments on revision',
  created_by BIGINT(20) UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (rc_id),
  KEY ix_school_student_term (school_id, student_id, term, academic_year),
  KEY ix_school_status (school_id, status, issued_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table 4: t_report_card_version (Immutable Version Snapshots)
CREATE TABLE IF NOT EXISTS t_report_card_version (
  rc_ver_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  rc_id BIGINT(20) UNSIGNED NOT NULL,
  version INT(11) NOT NULL COMMENT '1..n',
  payload_json LONGTEXT NOT NULL COMMENT 'full rendered content (sections, grades, comments)',
  summary_json TEXT NULL COMMENT 'cached aggregates (GPA, totals) for search/perf',
  created_by BIGINT(20) UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (rc_ver_id),
  UNIQUE KEY ux_rc_version (rc_id, version),
  KEY ix_rc (rc_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table 5: t_rc_event (Email Events & Access Audit)
CREATE TABLE IF NOT EXISTS t_rc_event (
  event_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  rc_id BIGINT(20) UNSIGNED NOT NULL,
  event_type ENUM('email_sent','email_failed','portal_view','revoked','reissued') NOT NULL,
  actor_id BIGINT(20) UNSIGNED NULL,
  meta_json TEXT NULL COMMENT 'provider refs, error message, ip/ua for views',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (event_id),
  KEY ix_rc_event (rc_id, event_type, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Show results
SELECT 'Tables created successfully!' as Status;
SHOW TABLES LIKE 't_rc_%';
