-- Course Enrollment System Tables
-- Run this SQL script directly in your database
--
-- SIMPLIFIED APPROACH:
-- - Uses existing tbl_course.fees for default fee amounts
-- - Uses existing class.course_id for course-to-class relationships
-- - Only creates student_courses table for course-level enrollment tracking

CREATE TABLE IF NOT EXISTS `student_courses` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT(11) UNSIGNED NOT NULL COMMENT 'User ID of student',
  `course_id` INT(11) UNSIGNED NOT NULL COMMENT 'References tbl_course.course_id',
  `school_id` INT(11) UNSIGNED NOT NULL,
  `registration_id` INT(11) UNSIGNED NULL COMMENT 'Link to registration if enrolled via registration',
  `enrollment_date` DATE NULL,
  `completion_date` DATE NULL,
  `status` ENUM('active', 'completed', 'dropped', 'suspended') NOT NULL DEFAULT 'active',
  `fee_amount` DECIMAL(10,2) NULL COMMENT 'Actual fee charged for this student',
  `student_fee_plan_id` INT(11) UNSIGNED NULL COMMENT 'Link to student_fee_plans table',
  `added_by` INT(11) UNSIGNED NULL,
  `notes` TEXT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `student_id_school_id` (`student_id`, `school_id`),
  KEY `course_id` (`course_id`),
  KEY `registration_id` (`registration_id`),
  UNIQUE KEY `unique_student_course` (`student_id`, `course_id`, `school_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Verify table was created
SELECT 'student_courses' as table_name, COUNT(*) as row_count FROM student_courses;
