-- Add next_billing_date column to user_profile_details table
-- Run this SQL directly in your MySQL database

-- Check if column exists first, then add it
SET @dbname = DATABASE();
SET @tablename = 'user_profile_details';
SET @columnname = 'next_billing_date';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  'SELECT 1', -- Column exists, do nothing
  CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` DATE NULL COMMENT ''Next billing date for automatic/manual billing at student level'' AFTER `dropped_date`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Alternative simpler version (if you know the column doesn't exist):
-- ALTER TABLE `user_profile_details` 
-- ADD COLUMN `next_billing_date` DATE NULL 
-- COMMENT 'Next billing date for automatic/manual billing at student level' 
-- AFTER `dropped_date`;

-- Verify the column was added
SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'user_profile_details'
  AND COLUMN_NAME = 'next_billing_date';
