-- EdQuill V2 Indexes
-- Run this after creating tables

-- Add indexes if they don't exist
ALTER TABLE t_session ADD INDEX IF NOT EXISTS ix_session_school (school_id, starts_at);
ALTER TABLE t_invoice ADD INDEX IF NOT EXISTS ix_invoice_school (school_id, status, due_date);
ALTER TABLE t_submission ADD INDEX IF NOT EXISTS ix_subm_school (school_id, submitted_at);

-- Lead queue index for fast registrar list
ALTER TABLE student_self_registrations 
  ADD INDEX IF NOT EXISTS ix_selfreg_queue (school_id, status, submitted_at);

-- Double-booking protection (if columns exist)
-- Note: These will fail if teacher_id/room_id columns don't exist - that's OK
SET @sql_teacher = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 't_session' 
    AND COLUMN_NAME = 'teacher_id'),
  'ALTER TABLE t_session ADD UNIQUE KEY IF NOT EXISTS ux_teacher_slot (school_id, teacher_id, starts_at, ends_at)',
  'SELECT 1'
);
PREPARE stmt FROM @sql_teacher;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql_room = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 't_session' 
    AND COLUMN_NAME = 'room_id'),
  'ALTER TABLE t_session ADD UNIQUE KEY IF NOT EXISTS ux_room_slot (school_id, room_id, starts_at, ends_at)',
  'SELECT 1'
);
PREPARE stmt FROM @sql_room;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

