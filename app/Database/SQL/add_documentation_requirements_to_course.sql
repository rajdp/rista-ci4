-- Add documentation_requirements column to tbl_course table
-- This allows admins to specify documentation requirements for courses
-- which students can see and upload during self-registration

ALTER TABLE `tbl_course` 
ADD COLUMN `documentation_requirements` TEXT NULL 
AFTER `other_details`;

-- Add comment to the column
ALTER TABLE `tbl_course` 
MODIFY COLUMN `documentation_requirements` TEXT NULL 
COMMENT 'Documentation requirements for student registration (e.g., Birth Certificate, Report Card, etc.)';




