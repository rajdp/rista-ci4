-- EdQuill V2 Indexes
-- Run this after creating tables
-- Note: MySQL 5.7 doesn't support IF NOT EXISTS for indexes
-- This script checks for table existence before adding indexes

-- t_session index (only if table exists)
SET @table_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES 
  WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 't_session');
SET @index_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
  WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 't_session' 
  AND INDEX_NAME = 'ix_session_school');
SET @sql = IF(@table_exists > 0 AND @index_exists = 0, 
  'ALTER TABLE t_session ADD INDEX ix_session_school (school_id, starts_at)', 
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- t_invoice index (only if table exists)
SET @table_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES 
  WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 't_invoice');
SET @index_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
  WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 't_invoice' 
  AND INDEX_NAME = 'ix_invoice_school');
SET @sql = IF(@table_exists > 0 AND @index_exists = 0, 
  'ALTER TABLE t_invoice ADD INDEX ix_invoice_school (school_id, status, due_date)', 
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- t_submission index (only if table exists)
SET @table_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES 
  WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 't_submission');
SET @index_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
  WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 't_submission' 
  AND INDEX_NAME = 'ix_subm_school');
SET @sql = IF(@table_exists > 0 AND @index_exists = 0, 
  'ALTER TABLE t_submission ADD INDEX ix_subm_school (school_id, submitted_at)', 
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Lead queue index for fast registrar list (only if table exists)
SET @table_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES 
  WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'student_self_registrations');
SET @index_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
  WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'student_self_registrations' 
  AND INDEX_NAME = 'ix_selfreg_queue');
SET @sql = IF(@table_exists > 0 AND @index_exists = 0, 
  'ALTER TABLE student_self_registrations ADD INDEX ix_selfreg_queue (school_id, status, submitted_at)', 
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Double-booking protection (if table and columns exist)
-- Teacher slot unique constraint
SET @table_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES 
  WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 't_session');
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 't_session' 
  AND COLUMN_NAME = 'teacher_id');
SET @index_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
  WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 't_session' 
  AND INDEX_NAME = 'ux_teacher_slot');
SET @sql = IF(@table_exists > 0 AND @col_exists > 0 AND @index_exists = 0, 
  'ALTER TABLE t_session ADD UNIQUE KEY ux_teacher_slot (school_id, teacher_id, starts_at, ends_at)', 
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Room slot unique constraint
SET @table_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES 
  WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 't_session');
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 't_session' 
  AND COLUMN_NAME = 'room_id');
SET @index_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
  WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 't_session' 
  AND INDEX_NAME = 'ux_room_slot');
SET @sql = IF(@table_exists > 0 AND @col_exists > 0 AND @index_exists = 0, 
  'ALTER TABLE t_session ADD UNIQUE KEY ux_room_slot (school_id, room_id, starts_at, ends_at)', 
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

