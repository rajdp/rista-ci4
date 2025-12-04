-- Remove course_fee_plans table and move billing_cycle_days to tbl_course
-- 
-- This script:
-- 1. Adds billing_cycle_days column to tbl_course
-- 2. Drops the course_fee_plans table

-- Step 1: Add billing_cycle_days column to tbl_course
ALTER TABLE `tbl_course` 
ADD COLUMN `billing_cycle_days` INT(11) NULL 
COMMENT 'Billing frequency in days (null = one-time, positive = recurring)' 
AFTER `fee_term`;

-- Step 2: Drop course_fee_plans table if it exists
DROP TABLE IF EXISTS `course_fee_plans`;










