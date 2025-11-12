-- =====================================================================
--  EdQuill LMS / CRM
--  Schema updates for School Self-Registration Portal MVP
--  Date: 2025-11-02
--
--  Notes:
--    * Target server: MySQL 5.7+ (uses JSON types).
--    * Execute during a maintenance window with a verified backup.
--    * Helper procedures emulate IF NOT EXISTS guards for incremental rollout.
-- =====================================================================

DELIMITER $$

DROP PROCEDURE IF EXISTS add_column_if_not_exists$$
CREATE PROCEDURE add_column_if_not_exists(
  IN tbl VARCHAR(64),
  IN col VARCHAR(64),
  IN definition TEXT
)
BEGIN
  IF NOT EXISTS (
    SELECT 1
      FROM information_schema.COLUMNS
     WHERE table_schema = DATABASE()
       AND table_name = tbl
       AND column_name = col
  ) THEN
    SET @ddl := CONCAT('ALTER TABLE `', tbl, '` ADD COLUMN ', definition);
    PREPARE stmt FROM @ddl;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END$$

DROP PROCEDURE IF EXISTS add_unique_key_if_not_exists$$
CREATE PROCEDURE add_unique_key_if_not_exists(
  IN tbl VARCHAR(64),
  IN idx VARCHAR(64),
  IN definition TEXT
)
BEGIN
  IF NOT EXISTS (
    SELECT 1
      FROM information_schema.statistics
     WHERE table_schema = DATABASE()
       AND table_name = tbl
       AND index_name = idx
  ) THEN
    SET @ddl := CONCAT('ALTER TABLE `', tbl, '` ADD UNIQUE KEY `', idx, '` ', definition);
    PREPARE stmt FROM @ddl;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END$$

DELIMITER ;

-- ---------------------------------------------------------------------
-- School metadata used for branded portals
-- ---------------------------------------------------------------------

CALL add_column_if_not_exists(
  'school',
  'school_key',
  '`school_key` VARCHAR(64) NULL AFTER `school_id`'
);

CALL add_unique_key_if_not_exists(
  'school',
  'ux_school_school_key',
  '(`school_key`)'
);

CALL add_column_if_not_exists(
  'school',
  'portal_domain',
  '`portal_domain` VARCHAR(150) NULL DEFAULT NULL AFTER `school_key`'
);

CALL add_column_if_not_exists(
  'school',
  'portal_contact_email',
  '`portal_contact_email` VARCHAR(190) NULL DEFAULT NULL AFTER `portal_domain`'
);

CALL add_column_if_not_exists(
  'school',
  'portal_contact_phone',
  '`portal_contact_phone` VARCHAR(32) NULL DEFAULT NULL AFTER `portal_contact_email`'
);

CREATE TABLE IF NOT EXISTS school_portal_settings (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  school_id BIGINT NOT NULL,
  primary_color VARCHAR(16) NULL,
  secondary_color VARCHAR(16) NULL,
  accent_color VARCHAR(16) NULL,
  hero_title VARCHAR(150) NULL,
  hero_subtitle VARCHAR(255) NULL,
  support_email VARCHAR(190) NULL,
  support_phone VARCHAR(32) NULL,
  terms_url VARCHAR(255) NULL,
  privacy_url VARCHAR(255) NULL,
  options JSON NULL,
  portal_enabled TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY ux_school_portal_settings_school (school_id),
  CONSTRAINT fk_school_portal_settings_school
    FOREIGN KEY (school_id) REFERENCES school(school_id)
      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Student self-registration capture tables
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS student_self_registrations (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  school_id BIGINT UNSIGNED NOT NULL,
  school_key VARCHAR(64) NULL,
  registration_code VARCHAR(36) NOT NULL,
  student_first_name VARCHAR(100) NOT NULL,
  student_last_name VARCHAR(100) NOT NULL,
  date_of_birth DATE NULL,
  email VARCHAR(190) NOT NULL,
  mobile VARCHAR(32) NOT NULL,
  address_line1 VARCHAR(255) NULL,
  address_line2 VARCHAR(255) NULL,
  city VARCHAR(100) NULL,
  state VARCHAR(100) NULL,
  postal_code VARCHAR(20) NULL,
  country VARCHAR(100) NULL,
  is_minor TINYINT(1) NOT NULL DEFAULT 0,
  guardian1_name VARCHAR(150) NULL,
  guardian1_email VARCHAR(190) NULL,
  guardian1_phone VARCHAR(32) NULL,
  guardian2_name VARCHAR(150) NULL,
  guardian2_email VARCHAR(190) NULL,
  guardian2_phone VARCHAR(32) NULL,
  schedule_preference VARCHAR(255) NULL,
  payment_method ENUM('card','ach','cash','check','waived','pending') NOT NULL DEFAULT 'pending',
  autopay_authorized TINYINT(1) NOT NULL DEFAULT 0,
  payment_reference VARCHAR(190) NULL,
  status ENUM('pending','in_review','needs_info','approved','rejected','converted','archived') NOT NULL DEFAULT 'pending',
  metadata JSON NULL,
  converted_student_user_id BIGINT NULL,
  converted_primary_guardian_id BIGINT UNSIGNED NULL,
  converted_secondary_guardian_id BIGINT UNSIGNED NULL,
  converted_at TIMESTAMP NULL,
  converted_by BIGINT UNSIGNED NULL,
  conversion_notes TEXT NULL,
  conversion_payload JSON NULL,
  submitted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY ux_student_self_registrations_code (registration_code),
  KEY idx_student_self_registrations_school (school_id),
  KEY idx_student_self_registrations_email (email),
  CONSTRAINT fk_student_self_registrations_school
    FOREIGN KEY (school_id) REFERENCES school(school_id)
      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CALL add_column_if_not_exists(
  'student_self_registrations',
  'assigned_to_user_id',
  '`assigned_to_user_id` BIGINT UNSIGNED NULL AFTER `conversion_payload`'
);

CALL add_column_if_not_exists(
  'student_self_registrations',
  'last_status_at',
  '`last_status_at` TIMESTAMP NULL AFTER `assigned_to_user_id`'
);

CALL add_column_if_not_exists(
  'student_self_registrations',
  'last_contacted_at',
  '`last_contacted_at` TIMESTAMP NULL AFTER `last_status_at`'
);

CALL add_column_if_not_exists(
  'student_self_registrations',
  'priority',
  "`priority` ENUM('normal','high','low') NOT NULL DEFAULT 'normal' AFTER `last_contacted_at`"
);

CREATE TABLE IF NOT EXISTS student_self_registration_courses (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  registration_id BIGINT UNSIGNED NOT NULL,
  course_id BIGINT UNSIGNED NULL,
  schedule_id BIGINT UNSIGNED NULL,
  course_name VARCHAR(150) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY ux_registration_course (registration_id, course_id, schedule_id),
  KEY idx_registration_courses_registration (registration_id),
  CONSTRAINT fk_registration_courses_registration
    FOREIGN KEY (registration_id) REFERENCES student_self_registrations(id)
      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CALL add_column_if_not_exists(
  'student_self_registration_courses',
  'schedule_title',
  '`schedule_title` VARCHAR(150) NULL AFTER `schedule_id`'
);

CALL add_column_if_not_exists(
  'student_self_registration_courses',
  'fee_amount',
  '`fee_amount` DECIMAL(12,2) NULL AFTER `schedule_title`'
);

CREATE TABLE IF NOT EXISTS student_self_registration_documents (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  registration_id BIGINT UNSIGNED NOT NULL,
  storage_path VARCHAR(255) NOT NULL,
  original_name VARCHAR(190) NOT NULL,
  mime_type VARCHAR(120) NULL,
  file_size INT UNSIGNED NULL,
  review_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  review_notes VARCHAR(255) NULL,
  uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_registration_documents_registration (registration_id),
  CONSTRAINT fk_registration_documents_registration
    FOREIGN KEY (registration_id) REFERENCES student_self_registrations(id)
      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CALL add_column_if_not_exists(
  'student_self_registration_documents',
  'review_status',
  "`review_status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending' AFTER `file_size`"
);

CALL add_column_if_not_exists(
  'student_self_registration_documents',
  'review_notes',
  '`review_notes` VARCHAR(255) NULL AFTER `review_status`'
);

CREATE TABLE IF NOT EXISTS student_self_registration_notes (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  registration_id BIGINT UNSIGNED NOT NULL,
  note_type ENUM('internal','request','response','history') NOT NULL DEFAULT 'internal',
  message TEXT NOT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  metadata JSON NULL,
  PRIMARY KEY (id),
  KEY idx_registration_notes_registration (registration_id),
  CONSTRAINT fk_registration_notes_registration
    FOREIGN KEY (registration_id) REFERENCES student_self_registrations(id)
      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Guardian directory supporting self-registration conversion
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS guardians (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  school_id BIGINT NOT NULL,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NULL,
  email VARCHAR(190) NULL,
  phone VARCHAR(32) NULL,
  relationship VARCHAR(64) NULL,
  communication_preference ENUM('email','sms','both') NOT NULL DEFAULT 'both',
  notes TEXT NULL,
  status TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY ux_guardians_school_email (school_id, email),
  UNIQUE KEY ux_guardians_school_phone (school_id, phone),
  KEY idx_guardians_school (school_id),
  CONSTRAINT fk_guardians_school
    FOREIGN KEY (school_id) REFERENCES school(school_id)
      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS student_guardians (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_user_id BIGINT NOT NULL,
  guardian_id BIGINT UNSIGNED NOT NULL,
  is_primary TINYINT(1) NOT NULL DEFAULT 0,
  relationship_override VARCHAR(64) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY ux_student_guardians_unique (student_user_id, guardian_id),
  KEY idx_student_guardians_guardian (guardian_id),
  KEY idx_student_guardians_student (student_user_id),
  CONSTRAINT fk_student_guardians_guardian
    FOREIGN KEY (guardian_id) REFERENCES guardians(id)
      ON DELETE CASCADE,
  CONSTRAINT fk_student_guardians_student
    FOREIGN KEY (student_user_id) REFERENCES user(user_id)
      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Communication log for self-registration workflow
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS student_self_registration_messages (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  registration_id BIGINT UNSIGNED NOT NULL,
  channel ENUM('email','sms') NOT NULL,
  recipient VARCHAR(190) NOT NULL,
  subject VARCHAR(190) NULL,
  message TEXT NOT NULL,
  status ENUM('queued','sent','failed') NOT NULL DEFAULT 'sent',
  error_message VARCHAR(255) NULL,
  metadata JSON NULL,
  sent_by BIGINT UNSIGNED NULL,
  sent_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_registration_messages_registration (registration_id),
  CONSTRAINT fk_registration_messages_registration
    FOREIGN KEY (registration_id) REFERENCES student_self_registrations(id)
      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;

DROP PROCEDURE IF EXISTS add_column_if_not_exists;
DROP PROCEDURE IF EXISTS add_unique_key_if_not_exists;
