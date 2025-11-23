-- Migration SQL to update legacy schema to newdb schema
-- Generated for MySQL 5.7
-- WARNING: Review and test this migration on a backup database first!
--

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';

-- Table: academic_calendar
ALTER TABLE `academic_calendar` MODIFY COLUMN `id` bigint(20) NOT NULL;
ALTER TABLE `academic_calendar` MODIFY COLUMN `school_id` bigint(20) NOT NULL;
ALTER TABLE `academic_calendar` MODIFY COLUMN `academic_month` int(11) NOT NULL;
ALTER TABLE `academic_calendar` MODIFY COLUMN `academic_week` int(11) NOT NULL;
ALTER TABLE `academic_calendar` MODIFY COLUMN `status` int(11) NOT NULL DEFAULT '1' COMMENT '1->active, 2->inactive';
ALTER TABLE `academic_calendar` MODIFY COLUMN `created_by` bigint(20) NOT NULL;
ALTER TABLE `academic_calendar` MODIFY COLUMN `modified_by` bigint(20) NOT NULL;

-- Table: address_master
ALTER TABLE `address_master` MODIFY COLUMN `address_type_id` int(11) NOT NULL;

-- Table: admin_mail_notification
ALTER TABLE `admin_mail_notification` MODIFY COLUMN `id` int(11) NOT NULL;
ALTER TABLE `admin_mail_notification` MODIFY COLUMN `student_id` bigint(20) NOT NULL;
ALTER TABLE `admin_mail_notification` MODIFY COLUMN `class_id` bigint(20) NOT NULL;
ALTER TABLE `admin_mail_notification` MODIFY COLUMN `school_id` bigint(20) NOT NULL;
ALTER TABLE `admin_mail_notification` MODIFY COLUMN `grade_id` bigint(20) NOT NULL;
ALTER TABLE `admin_mail_notification` MODIFY COLUMN `status` int(11) NOT NULL DEFAULT '0' COMMENT '0-> Not Sent, 1->Sent';
ALTER TABLE `admin_mail_notification` MODIFY COLUMN `created_by` bigint(20) NOT NULL;
ALTER TABLE `admin_mail_notification` MODIFY COLUMN `modified_by` bigint(20) NOT NULL;

-- Table: admin_settings
ALTER TABLE `admin_settings` MODIFY COLUMN `id` bigint(20) NOT NULL;
ALTER TABLE `admin_settings` MODIFY COLUMN `settings` int(11) NOT NULL DEFAULT '0';
ALTER TABLE `admin_settings` MODIFY COLUMN `status` int(11) NOT NULL COMMENT '2->testweb, 3->testadmin, 4->testuatweb, 5->testuatadmin, 6->liveuatweb, 7->liveuatadmin, 8->liveweb, 9-> liveadmin, 10->demoweb, 11->demoadmin, 12-> livetestweb, 13-> livetestadmin';

-- Table: admin_settings_school
ALTER TABLE `admin_settings_school` MODIFY COLUMN `id` bigint(20) NOT NULL;
ALTER TABLE `admin_settings_school` MODIFY COLUMN `school_id` bigint(20) NOT NULL;
ALTER TABLE `admin_settings_school` MODIFY COLUMN `settings` int(11) NOT NULL DEFAULT '0';
ALTER TABLE `admin_settings_school` MODIFY COLUMN `status` int(11) NOT NULL;

-- Table: answers
ALTER TABLE `answers` MODIFY COLUMN `answer_id` bigint(20) NOT NULL;
ALTER TABLE `answers` MODIFY COLUMN `display_order` int(11) NOT NULL DEFAULT '0';
ALTER TABLE `answers` MODIFY COLUMN `section_id` int(11) NOT NULL DEFAULT '0';
ALTER TABLE `answers` MODIFY COLUMN `content_id` bigint(20) NOT NULL;
ALTER TABLE `answers` MODIFY COLUMN `question_type_id` bigint(20) NOT NULL;
ALTER TABLE `answers` MODIFY COLUMN `has_sub_question` int(11) NOT NULL;
ALTER TABLE `answers` MODIFY COLUMN `old_answer` longtext NOT NULL;
ALTER TABLE `answers` MODIFY COLUMN `answer` longtext NOT NULL;
ALTER TABLE `answers` MODIFY COLUMN `auto_grade` int(11) NOT NULL DEFAULT '0';
ALTER TABLE `answers` MODIFY COLUMN `points` int(11) NOT NULL DEFAULT '0';
ALTER TABLE `answers` MODIFY COLUMN `difficulty` int(11) NOT NULL;
ALTER TABLE `answers` MODIFY COLUMN `allow_exact_match` int(11) DEFAULT NULL;
ALTER TABLE `answers` MODIFY COLUMN `allow_any_text` int(11) DEFAULT NULL;
ALTER TABLE `answers` MODIFY COLUMN `match_case` int(11) DEFAULT NULL;
ALTER TABLE `answers` MODIFY COLUMN `minimum_line` int(11) DEFAULT NULL;
ALTER TABLE `answers` MODIFY COLUMN `status` int(11) NOT NULL DEFAULT '1' COMMENT '0->inactive, 1->active';
ALTER TABLE `answers` MODIFY COLUMN `page_no` int(11) NOT NULL;
ALTER TABLE `answers` MODIFY COLUMN `created_by` bigint(20) NOT NULL;
ALTER TABLE `answers` MODIFY COLUMN `modified_by` bigint(20) NOT NULL;

-- Table: batch
ALTER TABLE `batch` MODIFY COLUMN `batch_id` bigint(20) NOT NULL;
ALTER TABLE `batch` MODIFY COLUMN `school_id` bigint(20) NOT NULL;
ALTER TABLE `batch` MODIFY COLUMN `corporate_id` int(11) NOT NULL;
ALTER TABLE `batch` MODIFY COLUMN `status` bigint(20) NOT NULL COMMENT '1-Active,2-In Active,3->Suspended, 4->Deleted';
ALTER TABLE `batch` MODIFY COLUMN `batch_type` bigint(20) NOT NULL;
ALTER TABLE `batch` MODIFY COLUMN `edquill_batch_id` int(11) NOT NULL;
ALTER TABLE `batch` MODIFY COLUMN `parent_batch_id` int(11) NOT NULL DEFAULT '0';
ALTER TABLE `batch` MODIFY COLUMN `created_by` bigint(20) NOT NULL;
ALTER TABLE `batch` MODIFY COLUMN `modified_by` bigint(20) NOT NULL;

-- Table: blogger
ALTER TABLE `blogger` MODIFY COLUMN `blog_id` bigint(20) NOT NULL;
ALTER TABLE `blogger` MODIFY COLUMN `name` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `blogger` MODIFY COLUMN `name_slug` text COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `blogger` MODIFY COLUMN `short_description` longtext COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `blogger` MODIFY COLUMN `long_description` longtext COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `blogger` MODIFY COLUMN `author` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `blogger` MODIFY COLUMN `image` text COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `blogger` MODIFY COLUMN `status` int(11) NOT NULL COMMENT '1->active,2->inactive';
ALTER TABLE `blogger` MODIFY COLUMN `display_type` int(11) NOT NULL COMMENT '1 -> general, 2 -> learing center, 3 -> tutors, 4 -> publishers';
ALTER TABLE `blogger` MODIFY COLUMN `views` int(11) NOT NULL DEFAULT '0';
ALTER TABLE `blogger` MODIFY COLUMN `created_by` bigint(20) NOT NULL;
ALTER TABLE `blogger` MODIFY COLUMN `modified_by` bigint(20) NOT NULL;

-- Table: book
ALTER TABLE `book` MODIFY COLUMN `book_id` bigint(20) NOT NULL;
ALTER TABLE `book` MODIFY COLUMN `school_id` bigint(20) NOT NULL DEFAULT '0';

-- Table: career
ALTER TABLE `career` MODIFY COLUMN `id` bigint(20) NOT NULL;
ALTER TABLE `career` MODIFY COLUMN `status` int(11) NOT NULL;
ALTER TABLE `career` MODIFY COLUMN `created_by` bigint(20) NOT NULL;
ALTER TABLE `career` MODIFY COLUMN `modified_by` bigint(20) NOT NULL;

-- Table: career_application
ALTER TABLE `career_application` MODIFY COLUMN `id` bigint(20) NOT NULL;
ALTER TABLE `career_application` MODIFY COLUMN `job_id` bigint(20) NOT NULL;
ALTER TABLE `career_application` MODIFY COLUMN `status` int(11) NOT NULL;
ALTER TABLE `career_application` MODIFY COLUMN `created_by` bigint(20) NOT NULL;
ALTER TABLE `career_application` MODIFY COLUMN `modified_by` bigint(20) NOT NULL;

-- Table: cfs_reports
ALTER TABLE `cfs_reports` MODIFY COLUMN `id` int(11) NOT NULL;
ALTER TABLE `cfs_reports` MODIFY COLUMN `class_id` bigint(20) NOT NULL;
ALTER TABLE `cfs_reports` MODIFY COLUMN `content_id` bigint(20) NOT NULL;
ALTER TABLE `cfs_reports` MODIFY COLUMN `student_id` bigint(20) NOT NULL;
ALTER TABLE `cfs_reports` MODIFY COLUMN `student_content_id` bigint(20) NOT NULL;
ALTER TABLE `cfs_reports` MODIFY COLUMN `question_id` int(11) NOT NULL;
ALTER TABLE `cfs_reports` MODIFY COLUMN `question_no` int(11) NOT NULL;
ALTER TABLE `cfs_reports` MODIFY COLUMN `is_correct` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `cfs_reports` MODIFY COLUMN `time_taken` int(11) NOT NULL;
ALTER TABLE `cfs_reports` MODIFY COLUMN `predicted_time` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `cfs_reports` MODIFY COLUMN `subject_id` bigint(20) DEFAULT NULL;
ALTER TABLE `cfs_reports` MODIFY COLUMN `question_topic_id` bigint(20) DEFAULT NULL;
ALTER TABLE `cfs_reports` MODIFY COLUMN `question_sub_topic_id` bigint(20) DEFAULT NULL;
ALTER TABLE `cfs_reports` MODIFY COLUMN `question_standard_id` bigint(20) DEFAULT NULL;
ALTER TABLE `cfs_reports` MODIFY COLUMN `skill` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `cfs_reports` MODIFY COLUMN `module_id` int(11) DEFAULT NULL;
ALTER TABLE `cfs_reports` MODIFY COLUMN `created_by` int(11) NOT NULL;
ALTER TABLE `cfs_reports` MODIFY COLUMN `modified_by` int(11) DEFAULT NULL;

-- Table: class
ALTER TABLE `class` MODIFY COLUMN `class_id` bigint(20) NOT NULL;
ALTER TABLE `class` MODIFY COLUMN `teacher_id` bigint(20) NOT NULL;
ALTER TABLE `class` MODIFY COLUMN `school_id` bigint(20) NOT NULL;
ALTER TABLE `class` MODIFY COLUMN `telephone_number` varchar(200) DEFAULT NULL;
ALTER TABLE `class` MODIFY COLUMN `time_zone_id` int(11) NOT NULL DEFAULT '0';
ALTER TABLE `class` MODIFY COLUMN `status` int(11) NOT NULL COMMENT '1-Active,2-Inactive,3-remove';
ALTER TABLE `class` MODIFY COLUMN `class_status` int(11) NOT NULL DEFAULT '0' COMMENT '0->active 1->save';
ALTER TABLE `class` MODIFY COLUMN `class_type` int(11) NOT NULL COMMENT '1-> Online, 2-> In person';
ALTER TABLE `class` MODIFY COLUMN `edquill_schedule_id` int(11) NOT NULL;
ALTER TABLE `class` MODIFY COLUMN `edquill_classroom_id` bigint(20) NOT NULL DEFAULT '0';
ALTER TABLE `class` MODIFY COLUMN `academy_schedule_id` int(11) DEFAULT NULL;
ALTER TABLE `class` MODIFY COLUMN `academy_course_id` int(11) DEFAULT NULL;
ALTER TABLE `class` MODIFY COLUMN `course_id` int(11) NOT NULL DEFAULT '0';
ALTER TABLE `class` MODIFY COLUMN `total_slots` int(11) DEFAULT NULL;
ALTER TABLE `class` MODIFY COLUMN `slots_booked` int(11) NOT NULL DEFAULT '0';
ALTER TABLE `class` MODIFY COLUMN `created_by` bigint(20) NOT NULL;
ALTER TABLE `class` MODIFY COLUMN `modified_by` bigint(20) NOT NULL DEFAULT '0';

-- Table: classroom_content
ALTER TABLE `classroom_content` MODIFY COLUMN `id` int(11) NOT NULL;
ALTER TABLE `classroom_content` MODIFY COLUMN `batch_id` bigint(20) NOT NULL;
ALTER TABLE `classroom_content` MODIFY COLUMN `school_id` bigint(20) NOT NULL;
ALTER TABLE `classroom_content` MODIFY COLUMN `content_id` bigint(20) NOT NULL;
ALTER TABLE `classroom_content` MODIFY COLUMN `status` int(11) NOT NULL COMMENT '1-> Added, 2-> Deleted';
ALTER TABLE `classroom_content` MODIFY COLUMN `auto_review` int(11) NOT NULL COMMENT '0-> manually, 1-> after completing test , 2-> after completing each question';
ALTER TABLE `classroom_content` MODIFY COLUMN `created_by` bigint(20) NOT NULL;
ALTER TABLE `classroom_content` MODIFY COLUMN `modified_by` bigint(20) NOT NULL;

-- Table: class_attendance
ALTER TABLE `class_attendance` MODIFY COLUMN `id` int(11) NOT NULL;
ALTER TABLE `class_attendance` MODIFY COLUMN `slot_day` int(11) NOT NULL;
ALTER TABLE `class_attendance` MODIFY COLUMN `schedule_id` bigint(20) NOT NULL;
ALTER TABLE `class_attendance` MODIFY COLUMN `class_id` bigint(20) NOT NULL;
ALTER TABLE `class_attendance` MODIFY COLUMN `student_id` bigint(20) NOT NULL;
ALTER TABLE `class_attendance` MODIFY COLUMN `attendance` int(11) DEFAULT NULL COMMENT '0 - Absent, 1 - Present';
ALTER TABLE `class_attendance` MODIFY COLUMN `created_by` bigint(20) NOT NULL;
ALTER TABLE `class_attendance` MODIFY COLUMN `modified_by` bigint(20) NOT NULL;

-- Table: class_content
ALTER TABLE `class_content` MODIFY COLUMN `id` bigint(20) NOT NULL;
ALTER TABLE `class_content` MODIFY COLUMN `class_id` bigint(20) DEFAULT NULL;
ALTER TABLE `class_content` MODIFY COLUMN `content_id` bigint(20) NOT NULL DEFAULT '0';
ALTER TABLE `class_content` MODIFY COLUMN `school_id` bigint(20) DEFAULT NULL;
ALTER TABLE `class_content` MODIFY COLUMN `status` int(11) NOT NULL DEFAULT '1' COMMENT '1-> active, 2-> inactive';
ALTER TABLE `class_content` MODIFY COLUMN `all_student` int(11) NOT NULL DEFAULT '1' COMMENT '1->all_student,0->specific_students';
ALTER TABLE `class_content` MODIFY COLUMN `release_score` int(11) NOT NULL DEFAULT '0' COMMENT '0->No_Release, 1->Release';
ALTER TABLE `class_content` MODIFY COLUMN `auto_review` int(11) NOT NULL DEFAULT '0' COMMENT '0-> manually, 1-> after completing test , 2-> after completing each question';
ALTER TABLE `class_content` MODIFY COLUMN `downloadable` int(11) NOT NULL DEFAULT '0' COMMENT '0-> Not downloadable, 2-> downloadable';
ALTER TABLE `class_content` MODIFY COLUMN `topic_id` int(11) NOT NULL DEFAULT '0';
ALTER TABLE `class_content` MODIFY COLUMN `created_by` bigint(20) NOT NULL;
ALTER TABLE `class_content` MODIFY COLUMN `modified_by` bigint(20) NOT NULL;

-- Table: class_content_log
ALTER TABLE `class_content_log` MODIFY COLUMN `id` bigint(20) NOT NULL;
ALTER TABLE `class_content_log` MODIFY COLUMN `class_id` bigint(20) DEFAULT NULL;
ALTER TABLE `class_content_log` MODIFY COLUMN `content_id` bigint(20) NOT NULL DEFAULT '0';
ALTER TABLE `class_content_log` MODIFY COLUMN `school_id` bigint(20) NOT NULL;
ALTER TABLE `class_content_log` MODIFY COLUMN `status` int(11) NOT NULL DEFAULT '1' COMMENT '1-> active, 2-> inactive';
ALTER TABLE `class_content_log` MODIFY COLUMN `all_student` int(11) NOT NULL DEFAULT '1' COMMENT '1->all_student,0->specific_students';
ALTER TABLE `class_content_log` MODIFY COLUMN `release_score` int(11) NOT NULL DEFAULT '0' COMMENT '0->No_Release, 1->Release';
ALTER TABLE `class_content_log` MODIFY COLUMN `auto_review` int(11) NOT NULL DEFAULT '0' COMMENT '0-> manually, 1-> after completing test , 2-> after completing each question';
ALTER TABLE `class_content_log` MODIFY COLUMN `downloadable` int(11) NOT NULL DEFAULT '0' COMMENT '0-> Not downloadable, 2-> downloadable';
ALTER TABLE `class_content_log` MODIFY COLUMN `topic_id` int(11) NOT NULL;
ALTER TABLE `class_content_log` MODIFY COLUMN `created_by` bigint(20) NOT NULL;
ALTER TABLE `class_content_log` MODIFY COLUMN `modified_by` bigint(20) NOT NULL;

-- Table: class_dec_mig
ALTER TABLE `class_dec_mig` MODIFY COLUMN `class_id` bigint(20) NOT NULL;
ALTER TABLE `class_dec_mig` MODIFY COLUMN `teacher_id` bigint(20) NOT NULL;
ALTER TABLE `class_dec_mig` MODIFY COLUMN `school_id` bigint(20) NOT NULL;
ALTER TABLE `class_dec_mig` MODIFY COLUMN `telephone_number` varchar(200) DEFAULT NULL;
ALTER TABLE `class_dec_mig` MODIFY COLUMN `status` int(11) NOT NULL COMMENT '1-Active,2-Inactive,3-remove';
ALTER TABLE `class_dec_mig` MODIFY COLUMN `class_status` int(11) NOT NULL DEFAULT '0' COMMENT '0->active 1->save';
ALTER TABLE `class_dec_mig` MODIFY COLUMN `class_type` int(11) NOT NULL COMMENT '1-> Online, 2-> In person';
ALTER TABLE `class_dec_mig` MODIFY COLUMN `edquill_schedule_id` int(11) NOT NULL;
ALTER TABLE `class_dec_mig` MODIFY COLUMN `edquill_classroom_id` bigint(20) NOT NULL DEFAULT '0';
ALTER TABLE `class_dec_mig` MODIFY COLUMN `created_by` bigint(20) NOT NULL;
ALTER TABLE `class_dec_mig` MODIFY COLUMN `modified_by` bigint(20) NOT NULL DEFAULT '0';

-- Table: class_mail_notification
ALTER TABLE `class_mail_notification` MODIFY COLUMN `id` bigint(20) NOT NULL;
ALTER TABLE `class_mail_notification` MODIFY COLUMN `class_id` bigint(20) NOT NULL;
ALTER TABLE `class_mail_notification` MODIFY COLUMN `email_id` longtext COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `class_mail_notification` MODIFY COLUMN `mail_sent` int(11) NOT NULL DEFAULT '0' COMMENT '0->mail not sent,1->mail sent';
ALTER TABLE `class_mail_notification` MODIFY COLUMN `provider_id` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `class_mail_notification` MODIFY COLUMN `googleid_token` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `class_mail_notification` MODIFY COLUMN `created_by` bigint(20) NOT NULL DEFAULT '0';

-- Table: class_notes
ALTER TABLE `class_notes` MODIFY COLUMN `id` bigint(20) NOT NULL;
ALTER TABLE `class_notes` MODIFY COLUMN `class_id` bigint(20) NOT NULL;
ALTER TABLE `class_notes` MODIFY COLUMN `note` text NOT NULL;
ALTER TABLE `class_notes` MODIFY COLUMN `status` int(11) NOT NULL DEFAULT '1' COMMENT '1->active, 2->deleted';
ALTER TABLE `class_notes` MODIFY COLUMN `created_by` bigint(20) NOT NULL;
ALTER TABLE `class_notes` MODIFY COLUMN `modified_by` bigint(20) NOT NULL;

-- Table: class_schedule
ALTER TABLE `class_schedule` MODIFY COLUMN `id` bigint(20) NOT NULL;
ALTER TABLE `class_schedule` MODIFY COLUMN `class_id` bigint(20) NOT NULL;
ALTER TABLE `class_schedule` MODIFY COLUMN `teacher_id` text NOT NULL;
ALTER TABLE `class_schedule` MODIFY COLUMN `school_id` bigint(20) NOT NULL;
ALTER TABLE `class_schedule` MODIFY COLUMN `slot_days` int(11) NOT NULL;
ALTER TABLE `class_schedule` MODIFY COLUMN `meeting_link` text;
ALTER TABLE `class_schedule` MODIFY COLUMN `meeting_id` text;
ALTER TABLE `class_schedule` MODIFY COLUMN `teacher_link` text NOT NULL;
ALTER TABLE `class_schedule` MODIFY COLUMN `student_link` text NOT NULL;
ALTER TABLE `class_schedule` MODIFY COLUMN `zoom_response` longtext NOT NULL;
ALTER TABLE `class_schedule` MODIFY COLUMN `passcode` varchar(200) DEFAULT NULL;
ALTER TABLE `class_schedule` MODIFY COLUMN `telephone_number` varchar(20) DEFAULT NULL;
ALTER TABLE `class_schedule` MODIFY COLUMN `created_by` bigint(20) NOT NULL;
ALTER TABLE `class_schedule` MODIFY COLUMN `modified_by` int(11) NOT NULL;

-- Table: content
ALTER TABLE `content` MODIFY COLUMN `content_id` bigint(20) NOT NULL;
ALTER TABLE `content` MODIFY COLUMN `school_id` bigint(20) NOT NULL;
ALTER TABLE `content` MODIFY COLUMN `corporate_id` bigint(20) NOT NULL DEFAULT '0';
ALTER TABLE `content` MODIFY COLUMN `testcode_id` int(11) NOT NULL DEFAULT '0';
ALTER TABLE `content` MODIFY COLUMN `content_type` int(11) NOT NULL COMMENT '1->Resources,2->Assignment,3->Assessment';
ALTER TABLE `content` MODIFY COLUMN `editor_type` int(11) NOT NULL DEFAULT '1' COMMENT '1-> KeyBoard, 2-> Text, 3-> Math, 4->Diagram';
ALTER TABLE `content` MODIFY COLUMN `content_format` int(11) NOT NULL COMMENT '1-> pdf 2-> links 3-> text 4-> HW';
ALTER TABLE `content` MODIFY COLUMN `total_questions` int(11) NOT NULL DEFAULT '0';
ALTER TABLE `content` MODIFY COLUMN `access` int(11) NOT NULL COMMENT '1->private(within school),2->private(within user),3->public, 4->private(within corporate)';
ALTER TABLE `content` MODIFY COLUMN `status` bigint(20) NOT NULL COMMENT '1->Active,2->Inactive,3->Suspended,4->Deleted,5->Draft';
ALTER TABLE `content` MODIFY COLUMN `download` int(11) NOT NULL DEFAULT '0';
ALTER TABLE `content` MODIFY COLUMN `allow_answer_key` int(11) DEFAULT '0' COMMENT '0->not allow, 1->allow';
ALTER TABLE `content` MODIFY COLUMN `content_duration` int(11) NOT NULL DEFAULT '0';
ALTER TABLE `content` MODIFY COLUMN `test_type_id` int(11) NOT NULL DEFAULT '1';
ALTER TABLE `content` MODIFY COLUMN `created_by` bigint(20) NOT NULL;
ALTER TABLE `content` MODIFY COLUMN `modified_by` bigint(20) NOT NULL;

-- Table: content_copy_22
ALTER TABLE `content_copy_22` MODIFY COLUMN `content_id` bigint(20) NOT NULL;
ALTER TABLE `content_copy_22` MODIFY COLUMN `school_id` bigint(20) NOT NULL;
ALTER TABLE `content_copy_22` MODIFY COLUMN `corporate_id` bigint(20) NOT NULL DEFAULT '0';
ALTER TABLE `content_copy_22` MODIFY COLUMN `testcode_id` int(11) NOT NULL DEFAULT '0';
ALTER TABLE `content_copy_22` MODIFY COLUMN `content_type` int(11) NOT NULL COMMENT '1->Resources,2->Assignment,3->Assessment';
ALTER TABLE `content_copy_22` MODIFY COLUMN `editor_type` int(11) NOT NULL DEFAULT '1' COMMENT '1-> KeyBoard, 2-> Text, 3-> Math, 4->Diagram';
ALTER TABLE `content_copy_22` MODIFY COLUMN `content_format` int(11) NOT NULL COMMENT '1-> pdf 2-> links 3-> text 4-> HW';
ALTER TABLE `content_copy_22` MODIFY COLUMN `total_questions` int(11) NOT NULL DEFAULT '0';
ALTER TABLE `content_copy_22` MODIFY COLUMN `access` int(11) NOT NULL COMMENT '1->private(within school),2->private(within user),3->public, 4->private(within corporate)';
ALTER TABLE `content_copy_22` MODIFY COLUMN `status` bigint(20) NOT NULL COMMENT '1->Active,2->Inactive,3->Suspended,4->Deleted,5->Draft';
ALTER TABLE `content_copy_22` MODIFY COLUMN `download` int(11) NOT NULL DEFAULT '0';
ALTER TABLE `content_copy_22` MODIFY COLUMN `allow_answer_key` int(11) DEFAULT '0' COMMENT '0->not allow, 1->allow';
ALTER TABLE `content_copy_22` MODIFY COLUMN `created_by` bigint(20) NOT NULL;
ALTER TABLE `content_copy_22` MODIFY COLUMN `modified_by` bigint(20) NOT NULL;

-- Table: content_master
ALTER TABLE `content_master` MODIFY COLUMN `content_id` int(11) NOT NULL;

-- Table: content_test_detail
ALTER TABLE `content_test_detail` MODIFY COLUMN `content_detail_id` int(11) NOT NULL;
ALTER TABLE `content_test_detail` MODIFY COLUMN `test_id` int(11) NOT NULL;
ALTER TABLE `content_test_detail` MODIFY COLUMN `content_id` int(11) NOT NULL;
ALTER TABLE `content_test_detail` MODIFY COLUMN `module_name` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `content_test_detail` MODIFY COLUMN `solving_time` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `content_test_detail` MODIFY COLUMN `interval_time` int(11) DEFAULT NULL;
ALTER TABLE `content_test_detail` MODIFY COLUMN `subject` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `content_test_detail` MODIFY COLUMN `created_by` int(11) NOT NULL;
ALTER TABLE `content_test_detail` MODIFY COLUMN `modified_by` int(11) DEFAULT NULL;

-- Table: content_test_detail_log
ALTER TABLE `content_test_detail_log` MODIFY COLUMN `content_detail_id` int(11) NOT NULL;
ALTER TABLE `content_test_detail_log` MODIFY COLUMN `test_id` int(11) NOT NULL;
ALTER TABLE `content_test_detail_log` MODIFY COLUMN `content_id` int(11) NOT NULL;
ALTER TABLE `content_test_detail_log` MODIFY COLUMN `module_name` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `content_test_detail_log` MODIFY COLUMN `solving_time` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `content_test_detail_log` MODIFY COLUMN `interval_time` int(11) DEFAULT NULL;
ALTER TABLE `content_test_detail_log` MODIFY COLUMN `subject` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `content_test_detail_log` MODIFY COLUMN `created_by` int(11) NOT NULL;
ALTER TABLE `content_test_detail_log` MODIFY COLUMN `modified_by` int(11) DEFAULT NULL;

-- Table: corporate
ALTER TABLE `corporate` MODIFY COLUMN `corporate_id` bigint(20) NOT NULL;
ALTER TABLE `corporate` MODIFY COLUMN `status` int(11) NOT NULL COMMENT '1 - active 2 - inactive 3->suspended 4-> delete';
ALTER TABLE `corporate` MODIFY COLUMN `created_by` bigint(20) NOT NULL;
ALTER TABLE `corporate` MODIFY COLUMN `modified_by` bigint(20) NOT NULL;

-- Table: corporate_request
ALTER TABLE `corporate_request` MODIFY COLUMN `request_id` bigint(20) NOT NULL;
ALTER TABLE `corporate_request` MODIFY COLUMN `corporate_id` bigint(20) NOT NULL;
ALTER TABLE `corporate_request` MODIFY COLUMN `school_id` bigint(20) NOT NULL;
ALTER TABLE `corporate_request` MODIFY COLUMN `status` int(11) NOT NULL DEFAULT '2' COMMENT '1->approved, 2->pending, 3->rejected';
ALTER TABLE `corporate_request` MODIFY COLUMN `created_by` bigint(20) NOT NULL;
ALTER TABLE `corporate_request` MODIFY COLUMN `modified_by` bigint(20) NOT NULL;

-- Table: country
ALTER TABLE `country` MODIFY COLUMN `id` int(11) NOT NULL;
ALTER TABLE `country` MODIFY COLUMN `dial_code` int(11) NOT NULL;

-- Table: date_format
ALTER TABLE `date_format` MODIFY COLUMN `date_id` bigint(20) NOT NULL;

-- Table: essay_rubric
ALTER TABLE `essay_rubric` MODIFY COLUMN `rubricID` int(11) NOT NULL;
ALTER TABLE `essay_rubric` MODIFY COLUMN `studentGrade` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `essay_rubric` MODIFY COLUMN `essayCriteria` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `essay_rubric` MODIFY COLUMN `maxScore` int(11) NOT NULL;
ALTER TABLE `essay_rubric` MODIFY COLUMN `Status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL;

-- Table: grade
ALTER TABLE `grade` MODIFY COLUMN `grade_id` bigint(20) NOT NULL;
ALTER TABLE `grade` MODIFY COLUMN `school_id` bigint(20) NOT NULL DEFAULT '0';
ALTER TABLE `grade` MODIFY COLUMN `sorting_no` int(11) NOT NULL;

-- Table: graph_answers
ALTER TABLE `graph_answers` MODIFY COLUMN `id` int(11) NOT NULL;
ALTER TABLE `graph_answers` MODIFY COLUMN `answer_id` bigint(20) NOT NULL;
ALTER TABLE `graph_answers` MODIFY COLUMN `question_no` bigint(20) NOT NULL;
ALTER TABLE `graph_answers` MODIFY COLUMN `content_id` bigint(20) NOT NULL;
ALTER TABLE `graph_answers` MODIFY COLUMN `class_id` bigint(20) NOT NULL;
ALTER TABLE `graph_answers` MODIFY COLUMN `student_id` bigint(20) NOT NULL;
ALTER TABLE `graph_answers` MODIFY COLUMN `actual_points` int(11) NOT NULL;
ALTER TABLE `graph_answers` MODIFY COLUMN `earned_points` int(11) DEFAULT '0';
ALTER TABLE `graph_answers` MODIFY COLUMN `answer_status` int(11) NOT NULL COMMENT '0->yet to start,1->incorrect,2->correct,3->partially correct,4->skipped,5-> Pending Verification';
ALTER TABLE `graph_answers` MODIFY COLUMN `auto_grade` int(11) NOT NULL DEFAULT '0';
ALTER TABLE `graph_answers` MODIFY COLUMN `created_by` bigint(20) NOT NULL;
ALTER TABLE `graph_answers` MODIFY COLUMN `modified_by` bigint(20) NOT NULL;

-- Table: holiday_calendar
ALTER TABLE `holiday_calendar` MODIFY COLUMN `id` bigint(20) NOT NULL;
ALTER TABLE `holiday_calendar` MODIFY COLUMN `school_id` bigint(20) NOT NULL;
ALTER TABLE `holiday_calendar` MODIFY COLUMN `created_by` bigint(20) NOT NULL;
ALTER TABLE `holiday_calendar` MODIFY COLUMN `modified_by` bigint(20) NOT NULL;

-- Table: institution_announcement
ALTER TABLE `institution_announcement` MODIFY COLUMN `id` int(11) NOT NULL;
ALTER TABLE `institution_announcement` MODIFY COLUMN `school_id` int(11) NOT NULL;
ALTER TABLE `institution_announcement` MODIFY COLUMN `title` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `institution_announcement` MODIFY COLUMN `description` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `institution_announcement` MODIFY COLUMN `created_by` int(11) NOT NULL;
ALTER TABLE `institution_announcement` MODIFY COLUMN `modified_by` int(11) DEFAULT NULL;

-- Table: invite_users
ALTER TABLE `invite_users` MODIFY COLUMN `id` bigint(20) NOT NULL;
ALTER TABLE `invite_users` MODIFY COLUMN `user_id` bigint(20) NOT NULL;
ALTER TABLE `invite_users` MODIFY COLUMN `status` int(11) NOT NULL DEFAULT '0';

-- Table: mailbox
ALTER TABLE `mailbox` MODIFY COLUMN `message_id` bigint(20) NOT NULL;
ALTER TABLE `mailbox` MODIFY COLUMN `parent_message_id` bigint(20) DEFAULT NULL;
ALTER TABLE `mailbox` MODIFY COLUMN `class_id` bigint(20) NOT NULL;
ALTER TABLE `mailbox` MODIFY COLUMN `from_id` bigint(20) NOT NULL;
ALTER TABLE `mailbox` MODIFY COLUMN `to_id` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `mailbox` MODIFY COLUMN `body` longtext COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `mailbox` MODIFY COLUMN `status` int(11) NOT NULL DEFAULT '0' COMMENT '1->draft';
ALTER TABLE `mailbox` MODIFY COLUMN `created_by` bigint(20) NOT NULL;
ALTER TABLE `mailbox` MODIFY COLUMN `modified_by` bigint(20) DEFAULT NULL;

-- Table: mailbox_attachment
ALTER TABLE `mailbox_attachment` MODIFY COLUMN `attachment_id` bigint(20) NOT NULL;
ALTER TABLE `mailbox_attachment` MODIFY COLUMN `message_id` bigint(20) NOT NULL;
ALTER TABLE `mailbox_attachment` MODIFY COLUMN `attachment` longtext COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `mailbox_attachment` MODIFY COLUMN `type` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '1-> Image, 2-> Link, 3->document';
ALTER TABLE `mailbox_attachment` MODIFY COLUMN `created_by` bigint(20) NOT NULL;

-- Table: mailbox_details
ALTER TABLE `mailbox_details` MODIFY COLUMN `message_detail_id` bigint(20) NOT NULL;
ALTER TABLE `mailbox_details` MODIFY COLUMN `message_id` bigint(20) NOT NULL;
ALTER TABLE `mailbox_details` MODIFY COLUMN `user_id` bigint(20) NOT NULL;
ALTER TABLE `mailbox_details` MODIFY COLUMN `is_read` int(11) NOT NULL DEFAULT '0';
ALTER TABLE `mailbox_details` MODIFY COLUMN `mail_sent` int(11) NOT NULL DEFAULT '0' COMMENT '0->mail not sent,1->mail sent';
ALTER TABLE `mailbox_details` MODIFY COLUMN `created_by` bigint(20) NOT NULL;

-- Table: note_comments
ALTER TABLE `note_comments` MODIFY COLUMN `id` bigint(20) NOT NULL;
ALTER TABLE `note_comments` MODIFY COLUMN `note_id` bigint(20) NOT NULL;
ALTER TABLE `note_comments` MODIFY COLUMN `comment` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `note_comments` MODIFY COLUMN `created_by` bigint(20) NOT NULL;
ALTER TABLE `note_comments` MODIFY COLUMN `modified_by` bigint(20) DEFAULT NULL;

-- Table: notify_parents_requests
ALTER TABLE `notify_parents_requests` MODIFY COLUMN `id` bigint(20) NOT NULL;
ALTER TABLE `notify_parents_requests` MODIFY COLUMN `school_id` bigint(20) NOT NULL;
ALTER TABLE `notify_parents_requests` MODIFY COLUMN `student_id` bigint(20) NOT NULL;
ALTER TABLE `notify_parents_requests` MODIFY COLUMN `class_id` bigint(20) NOT NULL;
ALTER TABLE `notify_parents_requests` MODIFY COLUMN `content_id` bigint(20) NOT NULL;
ALTER TABLE `notify_parents_requests` MODIFY COLUMN `student_content_id` bigint(20) NOT NULL;
ALTER TABLE `notify_parents_requests` MODIFY COLUMN `status` int(11) NOT NULL DEFAULT '0' COMMENT '0->mail_not_sent, 1->mail_sent';
ALTER TABLE `notify_parents_requests` MODIFY COLUMN `created_by` bigint(20) NOT NULL;

-- Table: page_master
ALTER TABLE `page_master` MODIFY COLUMN `id` int(11) NOT NULL;
ALTER TABLE `page_master` MODIFY COLUMN `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL;

-- Table: passage
ALTER TABLE `passage` MODIFY COLUMN `passage_id` bigint(20) NOT NULL;
ALTER TABLE `passage` MODIFY COLUMN `title` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `passage` MODIFY COLUMN `passage` longtext COLLATE utf8mb4_unicode_ci;
ALTER TABLE `passage` MODIFY COLUMN `status` int(11) NOT NULL DEFAULT '1' COMMENT '1->Active,2->inactive';
ALTER TABLE `passage` MODIFY COLUMN `created_by` int(11) NOT NULL;
ALTER TABLE `passage` MODIFY COLUMN `modified_by` int(11) DEFAULT NULL;

-- Table: permission
ALTER TABLE `permission` MODIFY COLUMN `permission_id` int(11) NOT NULL;
ALTER TABLE `permission` MODIFY COLUMN `status` int(11) NOT NULL;

-- Table: question_skill
ALTER TABLE `question_skill` MODIFY COLUMN `id` bigint(20) NOT NULL;
ALTER TABLE `question_skill` MODIFY COLUMN `skill` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `question_skill` MODIFY COLUMN `status` int(11) NOT NULL DEFAULT '1' COMMENT '1-> active, 2-> inactive';
ALTER TABLE `question_skill` MODIFY COLUMN `created_by` int(11) NOT NULL;

-- Table: question_standard
ALTER TABLE `question_standard` MODIFY COLUMN `id` bigint(20) NOT NULL;
ALTER TABLE `question_standard` MODIFY COLUMN `question_standard` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `question_standard` MODIFY COLUMN `status` int(11) NOT NULL DEFAULT '1' COMMENT '1-> active, 2-> inactive';
ALTER TABLE `question_standard` MODIFY COLUMN `created_by` int(11) NOT NULL;

-- Table: question_topic
ALTER TABLE `question_topic` MODIFY COLUMN `question_topic_id` bigint(20) NOT NULL;
ALTER TABLE `question_topic` MODIFY COLUMN `question_topic` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `question_topic` MODIFY COLUMN `subject_id` int(11) NOT NULL;
ALTER TABLE `question_topic` MODIFY COLUMN `status` int(11) NOT NULL DEFAULT '1' COMMENT '1-> active, 2-> inactive';
ALTER TABLE `question_topic` MODIFY COLUMN `created_by` int(11) NOT NULL;
ALTER TABLE `question_topic` MODIFY COLUMN `modified_by` int(11) DEFAULT NULL;

-- Table: question_types
ALTER TABLE `question_types` MODIFY COLUMN `question_type_id` bigint(20) NOT NULL;
ALTER TABLE `question_types` MODIFY COLUMN `resource_type_id` bigint(20) NOT NULL;
ALTER TABLE `question_types` MODIFY COLUMN `question_uploads` int(11) NOT NULL DEFAULT '0';
ALTER TABLE `question_types` MODIFY COLUMN `status` int(11) NOT NULL COMMENT '0->Inactive,1->Active';

-- Table: resource_type_master
ALTER TABLE `resource_type_master` MODIFY COLUMN `resource_type_id` bigint(20) NOT NULL;
ALTER TABLE `resource_type_master` MODIFY COLUMN `status` int(11) NOT NULL COMMENT '0->Inactive,1->Active';

-- Table: role_master
ALTER TABLE `role_master` MODIFY COLUMN `role_id` int(11) NOT NULL;

-- Table: role_permission
ALTER TABLE `role_permission` MODIFY COLUMN `id` int(11) NOT NULL;
ALTER TABLE `role_permission` MODIFY COLUMN `role_id` int(11) NOT NULL;
ALTER TABLE `role_permission` MODIFY COLUMN `permission_id` int(11) NOT NULL;

-- Table: school
ALTER TABLE `school` ADD COLUMN `school_key` varchar(64) DEFAULT NULL;
ALTER TABLE `school` ADD COLUMN `portal_domain` varchar(150) DEFAULT NULL;
ALTER TABLE `school` ADD COLUMN `portal_contact_email` varchar(190) DEFAULT NULL;
ALTER TABLE `school` ADD COLUMN `portal_contact_phone` varchar(32) DEFAULT NULL;
ALTER TABLE `school` MODIFY COLUMN `school_id` bigint(20) NOT NULL;
ALTER TABLE `school` MODIFY COLUMN `state` int(11) NOT NULL;
ALTER TABLE `school` MODIFY COLUMN `country` int(11) NOT NULL;
ALTER TABLE `school` MODIFY COLUMN `institution_type` int(11) DEFAULT '1' COMMENT '1-> Public School, 2-> Coaching Center, 3-> Private School, 4->Learning Center, 5-> Tutoring';
ALTER TABLE `school` MODIFY COLUMN `trial` int(11) NOT NULL DEFAULT '0';
ALTER TABLE `school` MODIFY COLUMN `payment_status` char(1) DEFAULT 'Y';
ALTER TABLE `school` MODIFY COLUMN `created_by` bigint(20) NOT NULL;
ALTER TABLE `school` MODIFY COLUMN `modified_by` bigint(20) NOT NULL;

-- Table: sms_templates
ALTER TABLE `sms_templates` MODIFY COLUMN `id` bigint(20) NOT NULL;

-- Table: state
ALTER TABLE `state` MODIFY COLUMN `id` int(11) NOT NULL;
ALTER TABLE `state` MODIFY COLUMN `country_id` int(11) NOT NULL;

-- Table: static_website
ALTER TABLE `static_website` MODIFY COLUMN `id` bigint(20) NOT NULL;
ALTER TABLE `static_website` MODIFY COLUMN `type` int(11) NOT NULL COMMENT '1-> contact us, 2-> demo';
ALTER TABLE `static_website` MODIFY COLUMN `status` int(11) NOT NULL COMMENT '1->mail sent, 2->mail_not _sent';

-- Table: static_website_email_subscription
ALTER TABLE `static_website_email_subscription` MODIFY COLUMN `id` bigint(20) NOT NULL;
ALTER TABLE `static_website_email_subscription` MODIFY COLUMN `type` int(11) NOT NULL COMMENT '1->palssouthplainfield , 2->palsnortherns, 3->palsmarlboro.com, 4->palseastbrunswick, 5->palsmonroe.com, 6->palsoldbridge, 7->palsfreehold, 8->palspiscataway,9->edquill.com';
ALTER TABLE `static_website_email_subscription` MODIFY COLUMN `status` int(11) NOT NULL COMMENT '1->subscribed, 2->unsubscribed';
ALTER TABLE `static_website_email_subscription` MODIFY COLUMN `mail` int(11) NOT NULL COMMENT '1->sent,2->not_sent';

-- Table: student_answerkey_request
ALTER TABLE `student_answerkey_request` MODIFY COLUMN `id` bigint(20) NOT NULL;
ALTER TABLE `student_answerkey_request` MODIFY COLUMN `student_id` bigint(20) NOT NULL;
ALTER TABLE `student_answerkey_request` MODIFY COLUMN `content_id` bigint(20) NOT NULL;
ALTER TABLE `student_answerkey_request` MODIFY COLUMN `class_id` bigint(20) NOT NULL;
ALTER TABLE `student_answerkey_request` MODIFY COLUMN `status` bigint(20) NOT NULL DEFAULT '0' COMMENT '0->Default, 1->Requested, 2->Rejected, 3->Accepted';
ALTER TABLE `student_answerkey_request` MODIFY COLUMN `created_by` bigint(20) NOT NULL;

-- Table: student_answers
ALTER TABLE `student_answers` MODIFY COLUMN `id` int(11) NOT NULL;
ALTER TABLE `student_answers` MODIFY COLUMN `answer_id` bigint(20) NOT NULL;
ALTER TABLE `student_answers` MODIFY COLUMN `question_no` bigint(20) NOT NULL;
ALTER TABLE `student_answers` MODIFY COLUMN `content_id` bigint(20) NOT NULL;
ALTER TABLE `student_answers` MODIFY COLUMN `class_id` bigint(20) NOT NULL;
ALTER TABLE `student_answers` MODIFY COLUMN `student_id` bigint(20) NOT NULL;
ALTER TABLE `student_answers` MODIFY COLUMN `class_content_id` bigint(20) NOT NULL;
ALTER TABLE `student_answers` MODIFY COLUMN `student_content_id` bigint(20) NOT NULL;
ALTER TABLE `student_answers` MODIFY COLUMN `actual_points` int(11) NOT NULL;
ALTER TABLE `student_answers` MODIFY COLUMN `earned_points` int(11) DEFAULT '0';
ALTER TABLE `student_answers` MODIFY COLUMN `answer_status` int(11) NOT NULL COMMENT '0->yet to start,1->incorrect,2->correct,3->partially correct,4->skipped,5-> Pending Verification';
ALTER TABLE `student_answers` MODIFY COLUMN `answer_attended` int(11) NOT NULL DEFAULT '0' COMMENT '0-> yet to start, 1-> answer, 2-> answered';
ALTER TABLE `student_answers` MODIFY COLUMN `correction_status` int(11) NOT NULL DEFAULT '0' COMMENT '0 -> Not Corrected, 1 -> Corrected';
ALTER TABLE `student_answers` MODIFY COLUMN `auto_grade` int(11) NOT NULL DEFAULT '0';
ALTER TABLE `student_answers` MODIFY COLUMN `no_of_attempt` int(11) NOT NULL DEFAULT '1';
ALTER TABLE `student_answers` MODIFY COLUMN `time_taken` int(11) NOT NULL;
ALTER TABLE `student_answers` MODIFY COLUMN `module_id` int(11) DEFAULT NULL;
ALTER TABLE `student_answers` MODIFY COLUMN `created_by` bigint(20) NOT NULL;
ALTER TABLE `student_answers` MODIFY COLUMN `modified_by` bigint(20) NOT NULL;

-- Table: student_answers_backup
ALTER TABLE `student_answers_backup` MODIFY COLUMN `id` int(11) NOT NULL;
ALTER TABLE `student_answers_backup` MODIFY COLUMN `answer_id` bigint(20) NOT NULL;
ALTER TABLE `student_answers_backup` MODIFY COLUMN `question_no` bigint(20) NOT NULL;
ALTER TABLE `student_answers_backup` MODIFY COLUMN `content_id` bigint(20) NOT NULL;
ALTER TABLE `student_answers_backup` MODIFY COLUMN `class_id` bigint(20) NOT NULL;
ALTER TABLE `student_answers_backup` MODIFY COLUMN `student_id` bigint(20) NOT NULL;
ALTER TABLE `student_answers_backup` MODIFY COLUMN `class_content_id` bigint(20) NOT NULL;
ALTER TABLE `student_answers_backup` MODIFY COLUMN `student_content_id` bigint(20) NOT NULL;
ALTER TABLE `student_answers_backup` MODIFY COLUMN `actual_points` int(11) NOT NULL;
ALTER TABLE `student_answers_backup` MODIFY COLUMN `earned_points` int(11) DEFAULT '0';
ALTER TABLE `student_answers_backup` MODIFY COLUMN `answer_status` int(11) NOT NULL COMMENT '0->yet to start,1->incorrect,2->correct,3->partially correct,4->skipped,5-> Pending Verification';
ALTER TABLE `student_answers_backup` MODIFY COLUMN `answer_attended` int(11) NOT NULL DEFAULT '0' COMMENT '0-> yet to start, 1-> answer, 2-> answered';
ALTER TABLE `student_answers_backup` MODIFY COLUMN `correction_status` int(11) NOT NULL DEFAULT '0' COMMENT '0 -> Not Corrected, 1 -> Corrected';
ALTER TABLE `student_answers_backup` MODIFY COLUMN `auto_grade` int(11) NOT NULL DEFAULT '0';
ALTER TABLE `student_answers_backup` MODIFY COLUMN `no_of_attempt` int(11) NOT NULL DEFAULT '1';
ALTER TABLE `student_answers_backup` MODIFY COLUMN `time_taken` int(11) NOT NULL;
ALTER TABLE `student_answers_backup` MODIFY COLUMN `created_by` bigint(20) NOT NULL;
ALTER TABLE `student_answers_backup` MODIFY COLUMN `modified_by` bigint(20) NOT NULL;

-- Table: student_class
ALTER TABLE `student_class` MODIFY COLUMN `id` bigint(20) NOT NULL;
ALTER TABLE `student_class` MODIFY COLUMN `class_id` bigint(20) NOT NULL;
ALTER TABLE `student_class` MODIFY COLUMN `from_class` bigint(20) DEFAULT '0';
ALTER TABLE `student_class` MODIFY COLUMN `student_id` bigint(20) NOT NULL;
ALTER TABLE `student_class` MODIFY COLUMN `status` int(11) NOT NULL COMMENT '0->Inactive, 1->Active, 2-> Saved,3->draft';
ALTER TABLE `student_class` MODIFY COLUMN `created_by` bigint(20) NOT NULL;
ALTER TABLE `student_class` MODIFY COLUMN `modified_by` bigint(20) NOT NULL;

-- Table: student_class_transfer
ALTER TABLE `student_class_transfer` MODIFY COLUMN `id` bigint(20) NOT NULL;
ALTER TABLE `student_class_transfer` MODIFY COLUMN `class_id` bigint(20) NOT NULL;
ALTER TABLE `student_class_transfer` MODIFY COLUMN `from_class` bigint(20) DEFAULT '0';
ALTER TABLE `student_class_transfer` MODIFY COLUMN `to_class` bigint(20) DEFAULT '0';
ALTER TABLE `student_class_transfer` MODIFY COLUMN `student_id` bigint(20) NOT NULL;
ALTER TABLE `student_class_transfer` MODIFY COLUMN `status` int(11) NOT NULL COMMENT '0->Inactive, 1->Active, 2-> Saved,3->draft';
ALTER TABLE `student_class_transfer` MODIFY COLUMN `created_by` bigint(20) NOT NULL;
ALTER TABLE `student_class_transfer` MODIFY COLUMN `modified_by` bigint(20) NOT NULL;

-- Table: student_content
ALTER TABLE `student_content` MODIFY COLUMN `id` bigint(20) NOT NULL;
ALTER TABLE `student_content` MODIFY COLUMN `class_id` bigint(20) NOT NULL;
ALTER TABLE `student_content` MODIFY COLUMN `student_id` bigint(20) NOT NULL;
ALTER TABLE `student_content` MODIFY COLUMN `content_id` bigint(20) NOT NULL;
ALTER TABLE `student_content` MODIFY COLUMN `class_content_id` bigint(20) NOT NULL;
ALTER TABLE `student_content` MODIFY COLUMN `grade_id` bigint(20) NOT NULL;
ALTER TABLE `student_content` MODIFY COLUMN `laq_id` int(11) NOT NULL COMMENT 'Last Answered Question Id';
ALTER TABLE `student_content` MODIFY COLUMN `status` int(11) NOT NULL COMMENT '1->Yet_To_Start, 2->Inprogress, 3->Verified, 4-> Completed, 5-> Corrected,6->pending Verification';
ALTER TABLE `student_content` MODIFY COLUMN `draft_status` int(11) NOT NULL DEFAULT '0' COMMENT '1->draft_content,2->undrafted';
ALTER TABLE `student_content` MODIFY COLUMN `release_score` int(11) NOT NULL DEFAULT '0' COMMENT '0- No Release, 1-> Release';
ALTER TABLE `student_content` MODIFY COLUMN `parents_notify_count` int(11) NOT NULL DEFAULT '0';
ALTER TABLE `student_content` MODIFY COLUMN `points` int(11) NOT NULL DEFAULT '0';
ALTER TABLE `student_content` MODIFY COLUMN `earned_points` int(11) NOT NULL DEFAULT '0';
ALTER TABLE `student_content` MODIFY COLUMN `sat_score` int(11) DEFAULT NULL;
ALTER TABLE `student_content` MODIFY COLUMN `rw_score` int(11) DEFAULT NULL;
ALTER TABLE `student_content` MODIFY COLUMN `math_score` int(11) DEFAULT NULL;
ALTER TABLE `student_content` MODIFY COLUMN `answer_request` bigint(20) NOT NULL DEFAULT '0' COMMENT '0->Default, 1->Requested, 2->Rejected, 3->Accepted';
ALTER TABLE `student_content` MODIFY COLUMN `redo_test` tinyint(4) NOT NULL COMMENT 'redo_test_status -> 0 ,redo_test_status->1';
ALTER TABLE `student_content` MODIFY COLUMN `platform` int(11) NOT NULL DEFAULT '0' COMMENT '1->web,2->ios,3->mixed';
ALTER TABLE `student_content` MODIFY COLUMN `content_time_taken` int(11) NOT NULL DEFAULT '0';
ALTER TABLE `student_content` MODIFY COLUMN `created_by` bigint(20) NOT NULL;
ALTER TABLE `student_content` MODIFY COLUMN `modified_by` bigint(20) NOT NULL;

-- Table: student_content_feedback
ALTER TABLE `student_content_feedback` MODIFY COLUMN `id` bigint(20) NOT NULL;
ALTER TABLE `student_content_feedback` MODIFY COLUMN `content_id` bigint(20) NOT NULL;
ALTER TABLE `student_content_feedback` MODIFY COLUMN `student_id` bigint(20) NOT NULL;
ALTER TABLE `student_content_feedback` MODIFY COLUMN `class_id` bigint(20) NOT NULL;
ALTER TABLE `student_content_feedback` MODIFY COLUMN `school_id` bigint(20) NOT NULL DEFAULT '0';
ALTER TABLE `student_content_feedback` MODIFY COLUMN `notes_type` int(11) NOT NULL DEFAULT '1' COMMENT '1->notes, 2 -email';
ALTER TABLE `student_content_feedback` MODIFY COLUMN `created_by` bigint(20) NOT NULL;
ALTER TABLE `student_content_feedback` MODIFY COLUMN `modified_by` bigint(20) NOT NULL;

-- Table: student_content_module
ALTER TABLE `student_content_module` MODIFY COLUMN `id` bigint(20) NOT NULL;
ALTER TABLE `student_content_module` MODIFY COLUMN `student_content_id` bigint(20) NOT NULL;
ALTER TABLE `student_content_module` MODIFY COLUMN `module_id` int(11) NOT NULL;
ALTER TABLE `student_content_module` MODIFY COLUMN `laq_id` int(11) NOT NULL COMMENT 'Last Answered Question Id';
ALTER TABLE `student_content_module` MODIFY COLUMN `status` int(11) NOT NULL COMMENT '1->Yet_To_Start, 2->Inprogress, 3->Verified, 4-> Completed, 5-> Corrected,6->pending Verification';
ALTER TABLE `student_content_module` MODIFY COLUMN `draft_status` int(11) NOT NULL DEFAULT '0' COMMENT '1->draft_content,2->undrafted';
ALTER TABLE `student_content_module` MODIFY COLUMN `release_score` int(11) NOT NULL DEFAULT '0' COMMENT '0- No Release, 1-> Release';
ALTER TABLE `student_content_module` MODIFY COLUMN `parents_notify_count` int(11) NOT NULL DEFAULT '0';
ALTER TABLE `student_content_module` MODIFY COLUMN `feedback` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `student_content_module` MODIFY COLUMN `student_feedback` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `student_content_module` MODIFY COLUMN `upload_answer` longtext COLLATE utf8mb4_unicode_ci;
ALTER TABLE `student_content_module` MODIFY COLUMN `points` int(11) NOT NULL DEFAULT '0';
ALTER TABLE `student_content_module` MODIFY COLUMN `earned_points` int(11) NOT NULL DEFAULT '0';
ALTER TABLE `student_content_module` MODIFY COLUMN `answer_request` bigint(20) NOT NULL DEFAULT '0' COMMENT '0->Default, 1->Requested, 2->Rejected, 3->Accepted';
ALTER TABLE `student_content_module` MODIFY COLUMN `redo_test` tinyint(4) NOT NULL COMMENT 'redo_test_status -> 0 ,redo_test_status->1';
ALTER TABLE `student_content_module` MODIFY COLUMN `platform` int(11) NOT NULL DEFAULT '0' COMMENT '1->web,2->ios,3->mixed';
ALTER TABLE `student_content_module` MODIFY COLUMN `content_time_taken` int(11) NOT NULL DEFAULT '0';
ALTER TABLE `student_content_module` MODIFY COLUMN `created_by` bigint(20) NOT NULL;
ALTER TABLE `student_content_module` MODIFY COLUMN `modified_by` bigint(20) NOT NULL;

-- Table: student_essays
ALTER TABLE `student_essays` MODIFY COLUMN `student_essay_id` bigint(20) NOT NULL;
ALTER TABLE `student_essays` MODIFY COLUMN `student_content_id` bigint(20) NOT NULL;
ALTER TABLE `student_essays` MODIFY COLUMN `question_id` bigint(20) NOT NULL;
ALTER TABLE `student_essays` MODIFY COLUMN `question` varchar(10000) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `student_essays` MODIFY COLUMN `student_answer` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `student_essays` MODIFY COLUMN `feedback` mediumtext COLLATE utf8mb4_unicode_ci;
ALTER TABLE `student_essays` MODIFY COLUMN `essay_embedding` longtext COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `student_essays` MODIFY COLUMN `student_score` int(11) NOT NULL;
ALTER TABLE `student_essays` MODIFY COLUMN `total_score` int(11) NOT NULL;
ALTER TABLE `student_essays` MODIFY COLUMN `prompt_token` int(11) DEFAULT NULL;
ALTER TABLE `student_essays` MODIFY COLUMN `completion_token` int(11) DEFAULT NULL;
ALTER TABLE `student_essays` MODIFY COLUMN `total_token` int(11) DEFAULT NULL;
ALTER TABLE `student_essays` MODIFY COLUMN `time_taken` int(11) NOT NULL;
ALTER TABLE `student_essays` MODIFY COLUMN `status` int(11) NOT NULL COMMENT '1->Active,0->InActive';
ALTER TABLE `student_essays` MODIFY COLUMN `created_by` bigint(20) NOT NULL;
ALTER TABLE `student_essays` MODIFY COLUMN `modified_by` bigint(20) NOT NULL;

-- Table: student_overdue_notification
ALTER TABLE `student_overdue_notification` MODIFY COLUMN `id` int(11) NOT NULL;
ALTER TABLE `student_overdue_notification` MODIFY COLUMN `school_id` bigint(20) NOT NULL;
ALTER TABLE `student_overdue_notification` MODIFY COLUMN `class_id` bigint(20) NOT NULL;
ALTER TABLE `student_overdue_notification` MODIFY COLUMN `content_id` bigint(20) NOT NULL;
ALTER TABLE `student_overdue_notification` MODIFY COLUMN `student_id` bigint(20) NOT NULL;
ALTER TABLE `student_overdue_notification` MODIFY COLUMN `status` int(11) NOT NULL;
ALTER TABLE `student_overdue_notification` MODIFY COLUMN `mail_count` int(11) NOT NULL;
ALTER TABLE `student_overdue_notification` MODIFY COLUMN `created_by` int(11) NOT NULL;
ALTER TABLE `student_overdue_notification` MODIFY COLUMN `modified_by` int(11) NOT NULL;

-- Table: student_suggestions
ALTER TABLE `student_suggestions` MODIFY COLUMN `id` bigint(20) NOT NULL;
ALTER TABLE `student_suggestions` MODIFY COLUMN `student_id` bigint(20) NOT NULL;
ALTER TABLE `student_suggestions` MODIFY COLUMN `content_id` bigint(20) NOT NULL;
ALTER TABLE `student_suggestions` MODIFY COLUMN `class_id` bigint(20) NOT NULL;
ALTER TABLE `student_suggestions` MODIFY COLUMN `answer_id` bigint(20) NOT NULL;
ALTER TABLE `student_suggestions` MODIFY COLUMN `school_id` bigint(20) NOT NULL;
ALTER TABLE `student_suggestions` MODIFY COLUMN `created_by` bigint(20) NOT NULL;

-- Table: student_upgrade
ALTER TABLE `student_upgrade` MODIFY COLUMN `id` bigint(20) NOT NULL;
ALTER TABLE `student_upgrade` MODIFY COLUMN `school_id` bigint(20) NOT NULL;
ALTER TABLE `student_upgrade` MODIFY COLUMN `student_id` bigint(20) NOT NULL;
ALTER TABLE `student_upgrade` MODIFY COLUMN `status` int(11) DEFAULT NULL;
ALTER TABLE `student_upgrade` MODIFY COLUMN `created_by` bigint(20) NOT NULL;
ALTER TABLE `student_upgrade` MODIFY COLUMN `modified_by` bigint(20) NOT NULL;

-- Table: student_work
ALTER TABLE `student_work` MODIFY COLUMN `id` bigint(20) NOT NULL;
ALTER TABLE `student_work` MODIFY COLUMN `student_content_id` bigint(20) NOT NULL;
ALTER TABLE `student_work` MODIFY COLUMN `student_id` bigint(20) NOT NULL;
ALTER TABLE `student_work` MODIFY COLUMN `content_id` bigint(20) NOT NULL;
ALTER TABLE `student_work` MODIFY COLUMN `content_type` int(11) NOT NULL COMMENT '1->resource, 2-> Assignment, 3-> Assessment';
ALTER TABLE `student_work` MODIFY COLUMN `class_id` bigint(20) NOT NULL;
ALTER TABLE `student_work` MODIFY COLUMN `student_content_status` int(11) NOT NULL COMMENT '1->Yet_To_Start, 2->Inprogress, 3->Verified, 4-> Completed, 5-> Corrected,6->pending Verification';
ALTER TABLE `student_work` MODIFY COLUMN `draft_status` int(11) DEFAULT '0' COMMENT '1->draft_content,2->undrafted';
ALTER TABLE `student_work` MODIFY COLUMN `content_format` int(11) NOT NULL;
ALTER TABLE `student_work` MODIFY COLUMN `total_score` int(11) NOT NULL;
ALTER TABLE `student_work` MODIFY COLUMN `obtained_score` int(11) NOT NULL;
ALTER TABLE `student_work` MODIFY COLUMN `status` int(11) NOT NULL DEFAULT '1' COMMENT '0->inactive, 1-> active';
ALTER TABLE `student_work` MODIFY COLUMN `score_released` int(11) NOT NULL COMMENT '0- No Release, 1-> Release';
ALTER TABLE `student_work` MODIFY COLUMN `created_by` bigint(20) NOT NULL;
ALTER TABLE `student_work` MODIFY COLUMN `modified_by` bigint(20) NOT NULL;

-- Table: subject
ALTER TABLE `subject` ADD COLUMN `fee_amount` decimal(10,2) DEFAULT NULL COMMENT 'Subject fee amount';
ALTER TABLE `subject` ADD COLUMN `deposit_amount` decimal(10,2) DEFAULT NULL COMMENT 'Subject deposit amount';
ALTER TABLE `subject` MODIFY COLUMN `subject_id` bigint(20) NOT NULL;
ALTER TABLE `subject` MODIFY COLUMN `school_id` bigint(20) NOT NULL DEFAULT '0';
ALTER TABLE `subject` MODIFY COLUMN `edquill_subject_id` int(11) NOT NULL;

-- Table: sub_topic
ALTER TABLE `sub_topic` MODIFY COLUMN `sub_topic_id` bigint(20) NOT NULL;
ALTER TABLE `sub_topic` MODIFY COLUMN `question_topic_id` bigint(20) NOT NULL;
ALTER TABLE `sub_topic` MODIFY COLUMN `sub_topic` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `sub_topic` MODIFY COLUMN `status` int(11) NOT NULL DEFAULT '1' COMMENT '1-> active, 2-> inactive';
ALTER TABLE `sub_topic` MODIFY COLUMN `created_by` int(11) NOT NULL;
ALTER TABLE `sub_topic` MODIFY COLUMN `modified_by` int(11) DEFAULT NULL;

-- Table: tags
ALTER TABLE `tags` MODIFY COLUMN `id` bigint(20) NOT NULL;
ALTER TABLE `tags` MODIFY COLUMN `school_id` bigint(20) NOT NULL;
ALTER TABLE `tags` MODIFY COLUMN `user_id` bigint(20) NOT NULL;
ALTER TABLE `tags` MODIFY COLUMN `content_id` bigint(20) NOT NULL;

-- Table: tbl_career
ALTER TABLE `tbl_career` MODIFY COLUMN `id` int(11) NOT NULL;
ALTER TABLE `tbl_career` MODIFY COLUMN `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_career` MODIFY COLUMN `department` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_career` MODIFY COLUMN `address1` text COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_career` MODIFY COLUMN `address2` text COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_career` MODIFY COLUMN `description` longtext COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_career` MODIFY COLUMN `basic_qualification` longtext COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_career` MODIFY COLUMN `prefered_qualification` longtext COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_career` MODIFY COLUMN `status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A->active,I->inactive';
ALTER TABLE `tbl_career` MODIFY COLUMN `created_by` int(11) NOT NULL;
ALTER TABLE `tbl_career` MODIFY COLUMN `modified_by` int(11) DEFAULT NULL;

-- Table: tbl_career_application
ALTER TABLE `tbl_career_application` MODIFY COLUMN `id` int(11) NOT NULL;
ALTER TABLE `tbl_career_application` MODIFY COLUMN `job_id` int(11) NOT NULL;
ALTER TABLE `tbl_career_application` MODIFY COLUMN `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_career_application` MODIFY COLUMN `email` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_career_application` MODIFY COLUMN `resume_url` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_career_application` MODIFY COLUMN `portfolio` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_career_application` MODIFY COLUMN `status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A->active,I->inactive';
ALTER TABLE `tbl_career_application` MODIFY COLUMN `created_by` int(11) NOT NULL;
ALTER TABLE `tbl_career_application` MODIFY COLUMN `modified_by` int(11) DEFAULT NULL;

-- Table: tbl_cart
ALTER TABLE `tbl_cart` MODIFY COLUMN `id` bigint(20) NOT NULL;
ALTER TABLE `tbl_cart` MODIFY COLUMN `user_id` bigint(20) NOT NULL;
ALTER TABLE `tbl_cart` MODIFY COLUMN `cart_data` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tbl_cart` MODIFY COLUMN `cart_type` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '''1'' -> cart, ''2''-> wishlist';

-- Table: tbl_cart_details
ALTER TABLE `tbl_cart_details` MODIFY COLUMN `id` int(11) NOT NULL;
ALTER TABLE `tbl_cart_details` MODIFY COLUMN `registration_id` int(11) NOT NULL;
ALTER TABLE `tbl_cart_details` MODIFY COLUMN `order_id` int(11) NOT NULL;
ALTER TABLE `tbl_cart_details` MODIFY COLUMN `course_id` int(11) NOT NULL;
ALTER TABLE `tbl_cart_details` MODIFY COLUMN `schedule_id` int(11) NOT NULL;
ALTER TABLE `tbl_cart_details` MODIFY COLUMN `quantity` int(11) NOT NULL;
ALTER TABLE `tbl_cart_details` MODIFY COLUMN `status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A-> active, I ->inactive';
ALTER TABLE `tbl_cart_details` MODIFY COLUMN `created_by` int(11) NOT NULL;
ALTER TABLE `tbl_cart_details` MODIFY COLUMN `modified_by` int(11) DEFAULT NULL;

-- Table: tbl_cart_details_log
ALTER TABLE `tbl_cart_details_log` MODIFY COLUMN `id` int(11) NOT NULL;
ALTER TABLE `tbl_cart_details_log` MODIFY COLUMN `registration_id` int(11) NOT NULL;
ALTER TABLE `tbl_cart_details_log` MODIFY COLUMN `order_id` int(11) NOT NULL;
ALTER TABLE `tbl_cart_details_log` MODIFY COLUMN `course_id` int(11) NOT NULL;
ALTER TABLE `tbl_cart_details_log` MODIFY COLUMN `schedule_id` int(11) NOT NULL;
ALTER TABLE `tbl_cart_details_log` MODIFY COLUMN `quantity` int(11) NOT NULL;
ALTER TABLE `tbl_cart_details_log` MODIFY COLUMN `status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A-> active, I ->inactive';
ALTER TABLE `tbl_cart_details_log` MODIFY COLUMN `created_by` int(11) NOT NULL;
ALTER TABLE `tbl_cart_details_log` MODIFY COLUMN `modified_by` int(11) DEFAULT NULL;

-- Table: tbl_contact_us
ALTER TABLE `tbl_contact_us` MODIFY COLUMN `id` int(11) NOT NULL;
ALTER TABLE `tbl_contact_us` MODIFY COLUMN `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_contact_us` MODIFY COLUMN `email_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_contact_us` MODIFY COLUMN `mobile` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_contact_us` MODIFY COLUMN `state` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_contact_us` MODIFY COLUMN `city` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_contact_us` MODIFY COLUMN `message` text COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_contact_us` MODIFY COLUMN `type` int(11) NOT NULL COMMENT '1-> contact us, 2-> demo';
ALTER TABLE `tbl_contact_us` MODIFY COLUMN `status` int(11) NOT NULL COMMENT '1->mail sent, 2->mail_not_sent';

-- Table: tbl_content
ALTER TABLE `tbl_content` MODIFY COLUMN `content_id` int(11) NOT NULL;
ALTER TABLE `tbl_content` MODIFY COLUMN `name` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_content` MODIFY COLUMN `name_slug` text COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_content` MODIFY COLUMN `entity_id` int(11) NOT NULL;
ALTER TABLE `tbl_content` MODIFY COLUMN `category_id` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_content` MODIFY COLUMN `subject_id` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_content` MODIFY COLUMN `short_description` longtext COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tbl_content` MODIFY COLUMN `long_description` longtext COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tbl_content` MODIFY COLUMN `author` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_content` MODIFY COLUMN `image` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tbl_content` MODIFY COLUMN `status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A-> Active, I -> Inactive';
ALTER TABLE `tbl_content` MODIFY COLUMN `views` int(11) NOT NULL DEFAULT '0';
ALTER TABLE `tbl_content` MODIFY COLUMN `display_order` int(11) NOT NULL;
ALTER TABLE `tbl_content` MODIFY COLUMN `redirect_url` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_content` MODIFY COLUMN `location` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_content` MODIFY COLUMN `timing` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_content` MODIFY COLUMN `created_by` int(11) NOT NULL;
ALTER TABLE `tbl_content` MODIFY COLUMN `modified_by` int(11) DEFAULT NULL;

-- Table: tbl_content_category
ALTER TABLE `tbl_content_category` MODIFY COLUMN `category_id` int(11) NOT NULL;
ALTER TABLE `tbl_content_category` MODIFY COLUMN `category_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_content_category` MODIFY COLUMN `description` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_content_category` MODIFY COLUMN `entity_id` int(11) NOT NULL;
ALTER TABLE `tbl_content_category` MODIFY COLUMN `status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A-> Active, I -> Inactive';
ALTER TABLE `tbl_content_category` MODIFY COLUMN `path` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_content_category` MODIFY COLUMN `display_order` int(11) NOT NULL;
ALTER TABLE `tbl_content_category` MODIFY COLUMN `created_by` int(11) DEFAULT NULL;
ALTER TABLE `tbl_content_category` MODIFY COLUMN `modified_by` int(11) DEFAULT NULL;

-- Table: tbl_content_seo
ALTER TABLE `tbl_content_seo` MODIFY COLUMN `seo_id` int(11) NOT NULL;
ALTER TABLE `tbl_content_seo` MODIFY COLUMN `content_id` int(11) NOT NULL;
ALTER TABLE `tbl_content_seo` MODIFY COLUMN `entity_id` int(11) NOT NULL;
ALTER TABLE `tbl_content_seo` MODIFY COLUMN `meta_author` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_content_seo` MODIFY COLUMN `meta_title` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_content_seo` MODIFY COLUMN `meta_description` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_content_seo` MODIFY COLUMN `meta_keywords` varchar(3000) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_content_seo` MODIFY COLUMN `meta_keyphrase` varchar(3000) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_content_seo` MODIFY COLUMN `meta_topic` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_content_seo` MODIFY COLUMN `meta_subject` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_content_seo` MODIFY COLUMN `meta_classification` varchar(3000) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_content_seo` MODIFY COLUMN `meta_robots` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_content_seo` MODIFY COLUMN `meta_rating` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_content_seo` MODIFY COLUMN `meta_audience` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_content_seo` MODIFY COLUMN `og_title` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_content_seo` MODIFY COLUMN `og_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_content_seo` MODIFY COLUMN `og_site_name` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_content_seo` MODIFY COLUMN `og_description` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_content_seo` MODIFY COLUMN `og_site_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_content_seo` MODIFY COLUMN `twitter_title` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_content_seo` MODIFY COLUMN `twitter_site` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_content_seo` MODIFY COLUMN `twitter_card` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_content_seo` MODIFY COLUMN `twitter_description` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_content_seo` MODIFY COLUMN `twitter_creator` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_content_seo` MODIFY COLUMN `status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A-> Active, I -> Inactive';
ALTER TABLE `tbl_content_seo` MODIFY COLUMN `created_by` int(11) NOT NULL;
ALTER TABLE `tbl_content_seo` MODIFY COLUMN `modified_by` int(11) DEFAULT NULL;

-- Table: tbl_content_seo_log
ALTER TABLE `tbl_content_seo_log` MODIFY COLUMN `seo_id` int(11) NOT NULL;
ALTER TABLE `tbl_content_seo_log` MODIFY COLUMN `content_id` int(11) NOT NULL;
ALTER TABLE `tbl_content_seo_log` MODIFY COLUMN `entity_id` int(11) NOT NULL;
ALTER TABLE `tbl_content_seo_log` MODIFY COLUMN `meta_author` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_content_seo_log` MODIFY COLUMN `meta_title` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_content_seo_log` MODIFY COLUMN `meta_description` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_content_seo_log` MODIFY COLUMN `meta_keywords` varchar(3000) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_content_seo_log` MODIFY COLUMN `meta_keyphrase` varchar(3000) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_content_seo_log` MODIFY COLUMN `meta_topic` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_content_seo_log` MODIFY COLUMN `meta_subject` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_content_seo_log` MODIFY COLUMN `meta_classification` varchar(3000) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_content_seo_log` MODIFY COLUMN `meta_robots` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_content_seo_log` MODIFY COLUMN `meta_rating` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_content_seo_log` MODIFY COLUMN `meta_audience` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_content_seo_log` MODIFY COLUMN `og_title` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_content_seo_log` MODIFY COLUMN `og_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_content_seo_log` MODIFY COLUMN `og_site_name` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_content_seo_log` MODIFY COLUMN `og_description` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_content_seo_log` MODIFY COLUMN `og_site_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_content_seo_log` MODIFY COLUMN `twitter_title` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_content_seo_log` MODIFY COLUMN `twitter_site` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_content_seo_log` MODIFY COLUMN `twitter_card` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_content_seo_log` MODIFY COLUMN `twitter_description` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_content_seo_log` MODIFY COLUMN `twitter_creator` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_content_seo_log` MODIFY COLUMN `status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A-> Active, I -> Inactive';
ALTER TABLE `tbl_content_seo_log` MODIFY COLUMN `created_by` int(11) NOT NULL;
ALTER TABLE `tbl_content_seo_log` MODIFY COLUMN `modified_by` int(11) DEFAULT NULL;

-- Table: tbl_coupon
ALTER TABLE `tbl_coupon` MODIFY COLUMN `coupon_id` int(11) NOT NULL;
ALTER TABLE `tbl_coupon` MODIFY COLUMN `entity_id` int(11) NOT NULL;
ALTER TABLE `tbl_coupon` MODIFY COLUMN `coupon_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_coupon` MODIFY COLUMN `discount_type` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'P-percentage, A- Amount';
ALTER TABLE `tbl_coupon` MODIFY COLUMN `discount` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_coupon` MODIFY COLUMN `course_based` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Y-yes,N->no';
ALTER TABLE `tbl_coupon` MODIFY COLUMN `course_id` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_coupon` MODIFY COLUMN `no_of_users` int(11) NOT NULL;
ALTER TABLE `tbl_coupon` MODIFY COLUMN `status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A->Active,I->Inactive';
ALTER TABLE `tbl_coupon` MODIFY COLUMN `created_by` int(11) NOT NULL;
ALTER TABLE `tbl_coupon` MODIFY COLUMN `modified_by` int(11) DEFAULT NULL;

-- Table: tbl_coupon_log
ALTER TABLE `tbl_coupon_log` MODIFY COLUMN `coupon_id` int(11) NOT NULL;
ALTER TABLE `tbl_coupon_log` MODIFY COLUMN `entity_id` int(11) NOT NULL;
ALTER TABLE `tbl_coupon_log` MODIFY COLUMN `coupon_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_coupon_log` MODIFY COLUMN `discount_type` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'P-percentage, A- Amount';
ALTER TABLE `tbl_coupon_log` MODIFY COLUMN `discount` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_coupon_log` MODIFY COLUMN `course_based` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Y-yes,N->no';
ALTER TABLE `tbl_coupon_log` MODIFY COLUMN `course_id` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_coupon_log` MODIFY COLUMN `no_of_users` int(11) NOT NULL;
ALTER TABLE `tbl_coupon_log` MODIFY COLUMN `status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A->Active,I->Inactive';
ALTER TABLE `tbl_coupon_log` MODIFY COLUMN `created_by` int(11) NOT NULL;
ALTER TABLE `tbl_coupon_log` MODIFY COLUMN `modified_by` int(11) DEFAULT NULL;

-- Table: tbl_course
ALTER TABLE `tbl_course` ADD COLUMN `documentation_requirements` text COLLATE utf8mb4_unicode_ci COMMENT 'Documentation requirements for student registration (e.g., Birth Certificate, Report Card, etc.)';
ALTER TABLE `tbl_course` ADD COLUMN `fee_amount` double NOT NULL;
ALTER TABLE `tbl_course` ADD COLUMN `fee_term` int(2) NOT NULL;
ALTER TABLE `tbl_course` ADD COLUMN `billing_cycle_days` int(11) DEFAULT NULL COMMENT 'Billing frequency in days (null = one-time, positive = recurring)';
ALTER TABLE `tbl_course` MODIFY COLUMN `course_id` int(11) NOT NULL;
ALTER TABLE `tbl_course` MODIFY COLUMN `course_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_course` MODIFY COLUMN `seo_title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_course` MODIFY COLUMN `category_id` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_course` MODIFY COLUMN `subject_id` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course` MODIFY COLUMN `grade_id` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course` MODIFY COLUMN `description` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tbl_course` MODIFY COLUMN `short_description` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course` MODIFY COLUMN `path` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course` MODIFY COLUMN `status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'D->draft,P->Ready for review,A->Approved,R->rework,C-cancel';
ALTER TABLE `tbl_course` MODIFY COLUMN `lessons` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course` MODIFY COLUMN `overview_content` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tbl_course` MODIFY COLUMN `course_content` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tbl_course` MODIFY COLUMN `prerequisites` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tbl_course` MODIFY COLUMN `other_details` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tbl_course` MODIFY COLUMN `author` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course` MODIFY COLUMN `fees` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course` MODIFY COLUMN `certified_course` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Y-yes, N- no';
ALTER TABLE `tbl_course` MODIFY COLUMN `multiple_schedule` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Y -> user can choose multiple schedule,N-> only one schedule can be chosen and registered';
ALTER TABLE `tbl_course` MODIFY COLUMN `entity_id` int(11) NOT NULL;
ALTER TABLE `tbl_course` MODIFY COLUMN `redirect_url` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course` MODIFY COLUMN `is_popular` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'N';
ALTER TABLE `tbl_course` MODIFY COLUMN `is_exclusive` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'N';
ALTER TABLE `tbl_course` MODIFY COLUMN `button_name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course` MODIFY COLUMN `display_order` int(11) NOT NULL;
ALTER TABLE `tbl_course` MODIFY COLUMN `contact_info` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course` MODIFY COLUMN `created_by` int(11) NOT NULL;
ALTER TABLE `tbl_course` MODIFY COLUMN `modified_by` int(11) DEFAULT NULL;

-- Table: tbl_course_category
ALTER TABLE `tbl_course_category` MODIFY COLUMN `category_id` int(11) NOT NULL;
ALTER TABLE `tbl_course_category` MODIFY COLUMN `category_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_course_category` MODIFY COLUMN `description` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_category` MODIFY COLUMN `subject_id` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tbl_course_category` MODIFY COLUMN `entity_id` int(11) NOT NULL;
ALTER TABLE `tbl_course_category` MODIFY COLUMN `status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A-> Active, I -> Inactive';
ALTER TABLE `tbl_course_category` MODIFY COLUMN `path` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_category` MODIFY COLUMN `display_order` int(11) NOT NULL;
ALTER TABLE `tbl_course_category` MODIFY COLUMN `created_by` int(11) DEFAULT NULL;
ALTER TABLE `tbl_course_category` MODIFY COLUMN `modified_by` int(11) DEFAULT NULL;

-- Table: tbl_course_category_log
ALTER TABLE `tbl_course_category_log` MODIFY COLUMN `category_id` int(11) NOT NULL;
ALTER TABLE `tbl_course_category_log` MODIFY COLUMN `category_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_course_category_log` MODIFY COLUMN `description` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_category_log` MODIFY COLUMN `subject_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_category_log` MODIFY COLUMN `entity_id` int(11) NOT NULL;
ALTER TABLE `tbl_course_category_log` MODIFY COLUMN `status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A-> Active, I -> Inactive';
ALTER TABLE `tbl_course_category_log` MODIFY COLUMN `path` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_category_log` MODIFY COLUMN `display_order` int(11) NOT NULL;
ALTER TABLE `tbl_course_category_log` MODIFY COLUMN `created_by` int(11) DEFAULT NULL;
ALTER TABLE `tbl_course_category_log` MODIFY COLUMN `modified_by` int(11) DEFAULT NULL;

-- Table: tbl_course_faq
ALTER TABLE `tbl_course_faq` MODIFY COLUMN `id` int(11) NOT NULL;
ALTER TABLE `tbl_course_faq` MODIFY COLUMN `course_id` int(11) NOT NULL;
ALTER TABLE `tbl_course_faq` MODIFY COLUMN `title` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_course_faq` MODIFY COLUMN `description` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_faq` MODIFY COLUMN `answer` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_course_faq` MODIFY COLUMN `status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A-> Active, I -> Inactive';
ALTER TABLE `tbl_course_faq` MODIFY COLUMN `entity_id` bigint(20) NOT NULL;
ALTER TABLE `tbl_course_faq` MODIFY COLUMN `created_by` int(11) NOT NULL;
ALTER TABLE `tbl_course_faq` MODIFY COLUMN `modified_by` int(11) DEFAULT NULL;

-- Table: tbl_course_faq_log
ALTER TABLE `tbl_course_faq_log` MODIFY COLUMN `id` int(11) NOT NULL;
ALTER TABLE `tbl_course_faq_log` MODIFY COLUMN `course_id` int(11) NOT NULL;
ALTER TABLE `tbl_course_faq_log` MODIFY COLUMN `title` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_course_faq_log` MODIFY COLUMN `description` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_faq_log` MODIFY COLUMN `answer` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_course_faq_log` MODIFY COLUMN `status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A-> Active, I -> Inactive';
ALTER TABLE `tbl_course_faq_log` MODIFY COLUMN `entity_id` bigint(20) NOT NULL;
ALTER TABLE `tbl_course_faq_log` MODIFY COLUMN `created_by` int(11) NOT NULL;
ALTER TABLE `tbl_course_faq_log` MODIFY COLUMN `modified_by` int(11) DEFAULT NULL;

-- Table: tbl_course_log
ALTER TABLE `tbl_course_log` MODIFY COLUMN `course_id` int(11) NOT NULL;
ALTER TABLE `tbl_course_log` MODIFY COLUMN `course_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_course_log` MODIFY COLUMN `seo_title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_course_log` MODIFY COLUMN `category_id` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_course_log` MODIFY COLUMN `subject_id` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_log` MODIFY COLUMN `grade_id` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_log` MODIFY COLUMN `description` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tbl_course_log` MODIFY COLUMN `short_description` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_log` MODIFY COLUMN `path` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_log` MODIFY COLUMN `status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'D->draft,P->Ready for review,A->Approved,R->rework,C-cancel';
ALTER TABLE `tbl_course_log` MODIFY COLUMN `lessons` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_log` MODIFY COLUMN `overview_content` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tbl_course_log` MODIFY COLUMN `course_content` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tbl_course_log` MODIFY COLUMN `prerequisites` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tbl_course_log` MODIFY COLUMN `other_details` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tbl_course_log` MODIFY COLUMN `author` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_log` MODIFY COLUMN `fees` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_log` MODIFY COLUMN `certified_course` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Y-yes, N- no';
ALTER TABLE `tbl_course_log` MODIFY COLUMN `multiple_schedule` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Y -> user can choose multiple schedule,N-> only one schedule can be chosen and registered';
ALTER TABLE `tbl_course_log` MODIFY COLUMN `entity_id` int(11) NOT NULL;
ALTER TABLE `tbl_course_log` MODIFY COLUMN `redirect_url` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_log` MODIFY COLUMN `is_popular` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_log` MODIFY COLUMN `is_exclusive` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_log` MODIFY COLUMN `button_name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_log` MODIFY COLUMN `display_order` int(11) NOT NULL;
ALTER TABLE `tbl_course_log` MODIFY COLUMN `contact_info` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_log` MODIFY COLUMN `created_by` int(11) NOT NULL;
ALTER TABLE `tbl_course_log` MODIFY COLUMN `modified_by` int(11) DEFAULT NULL;

-- Table: tbl_course_ratings
ALTER TABLE `tbl_course_ratings` MODIFY COLUMN `rating_id` int(11) NOT NULL;
ALTER TABLE `tbl_course_ratings` MODIFY COLUMN `course_detail_id` int(11) NOT NULL;
ALTER TABLE `tbl_course_ratings` MODIFY COLUMN `user_id` int(11) NOT NULL;
ALTER TABLE `tbl_course_ratings` MODIFY COLUMN `created_by` int(11) NOT NULL;
ALTER TABLE `tbl_course_ratings` MODIFY COLUMN `modified_by` int(11) DEFAULT NULL;

-- Table: tbl_course_reviews
ALTER TABLE `tbl_course_reviews` MODIFY COLUMN `review_id` int(11) NOT NULL;
ALTER TABLE `tbl_course_reviews` MODIFY COLUMN `course_id` int(11) NOT NULL;
ALTER TABLE `tbl_course_reviews` MODIFY COLUMN `review` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_course_reviews` MODIFY COLUMN `user_id` int(11) NOT NULL;
ALTER TABLE `tbl_course_reviews` MODIFY COLUMN `created_by` int(11) NOT NULL;
ALTER TABLE `tbl_course_reviews` MODIFY COLUMN `modified_by` int(11) DEFAULT NULL;

-- Table: tbl_course_schedule
ALTER TABLE `tbl_course_schedule` MODIFY COLUMN `schedule_id` int(11) NOT NULL;
ALTER TABLE `tbl_course_schedule` MODIFY COLUMN `course_id` int(11) NOT NULL;
ALTER TABLE `tbl_course_schedule` MODIFY COLUMN `schedule_title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_course_schedule` MODIFY COLUMN `program_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_schedule` MODIFY COLUMN `payment_type` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'O-onetime,R-recurring';
ALTER TABLE `tbl_course_schedule` MODIFY COLUMN `payment_sub_type` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'W->weekly,M->monthly,Q->quarterly,H->half-yearly,A->annually';
ALTER TABLE `tbl_course_schedule` MODIFY COLUMN `course_type` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'O->online,I->inperson ';
ALTER TABLE `tbl_course_schedule` MODIFY COLUMN `location_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_schedule` MODIFY COLUMN `total_slots` int(11) DEFAULT NULL;
ALTER TABLE `tbl_course_schedule` MODIFY COLUMN `slots_booked` int(11) NOT NULL DEFAULT '0';
ALTER TABLE `tbl_course_schedule` MODIFY COLUMN `status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A-> Active, I -> Inactive';
ALTER TABLE `tbl_course_schedule` MODIFY COLUMN `entity_id` int(11) NOT NULL;
ALTER TABLE `tbl_course_schedule` MODIFY COLUMN `edquill_class_id` int(11) DEFAULT NULL;
ALTER TABLE `tbl_course_schedule` MODIFY COLUMN `created_by` int(11) NOT NULL;
ALTER TABLE `tbl_course_schedule` MODIFY COLUMN `modified_by` int(11) DEFAULT NULL;

-- Table: tbl_course_seo
ALTER TABLE `tbl_course_seo` MODIFY COLUMN `seo_id` int(11) NOT NULL;
ALTER TABLE `tbl_course_seo` MODIFY COLUMN `course_id` int(11) NOT NULL;
ALTER TABLE `tbl_course_seo` MODIFY COLUMN `meta_author` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_seo` MODIFY COLUMN `meta_title` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_seo` MODIFY COLUMN `meta_description` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_seo` MODIFY COLUMN `meta_keywords` varchar(3000) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_seo` MODIFY COLUMN `meta_keyphrase` varchar(3000) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_seo` MODIFY COLUMN `meta_topic` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_seo` MODIFY COLUMN `meta_subject` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_seo` MODIFY COLUMN `meta_classification` varchar(3000) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_seo` MODIFY COLUMN `meta_robots` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_seo` MODIFY COLUMN `meta_rating` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_seo` MODIFY COLUMN `meta_audience` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_seo` MODIFY COLUMN `og_title` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_seo` MODIFY COLUMN `og_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_seo` MODIFY COLUMN `og_site_name` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_seo` MODIFY COLUMN `og_description` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_seo` MODIFY COLUMN `og_site_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_seo` MODIFY COLUMN `twitter_title` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_seo` MODIFY COLUMN `twitter_site` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_seo` MODIFY COLUMN `twitter_card` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_seo` MODIFY COLUMN `twitter_description` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_seo` MODIFY COLUMN `twitter_creator` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_seo` MODIFY COLUMN `status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A-> Active, I -> Inactive';
ALTER TABLE `tbl_course_seo` MODIFY COLUMN `created_by` int(11) NOT NULL;
ALTER TABLE `tbl_course_seo` MODIFY COLUMN `modified_by` int(11) DEFAULT NULL;

-- Table: tbl_course_seo_log
ALTER TABLE `tbl_course_seo_log` MODIFY COLUMN `seo_id` int(11) NOT NULL;
ALTER TABLE `tbl_course_seo_log` MODIFY COLUMN `course_id` int(11) NOT NULL;
ALTER TABLE `tbl_course_seo_log` MODIFY COLUMN `meta_author` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_seo_log` MODIFY COLUMN `meta_title` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_seo_log` MODIFY COLUMN `meta_description` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_seo_log` MODIFY COLUMN `meta_keywords` varchar(3000) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_seo_log` MODIFY COLUMN `meta_keyphrase` varchar(3000) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_seo_log` MODIFY COLUMN `meta_topic` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_seo_log` MODIFY COLUMN `meta_subject` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_seo_log` MODIFY COLUMN `meta_classification` varchar(3000) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_seo_log` MODIFY COLUMN `meta_robots` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_seo_log` MODIFY COLUMN `meta_rating` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_seo_log` MODIFY COLUMN `meta_audience` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_seo_log` MODIFY COLUMN `og_title` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_seo_log` MODIFY COLUMN `og_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_seo_log` MODIFY COLUMN `og_site_name` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_seo_log` MODIFY COLUMN `og_description` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_seo_log` MODIFY COLUMN `og_site_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_seo_log` MODIFY COLUMN `twitter_title` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_seo_log` MODIFY COLUMN `twitter_site` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_seo_log` MODIFY COLUMN `twitter_card` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_seo_log` MODIFY COLUMN `twitter_description` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_seo_log` MODIFY COLUMN `twitter_creator` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_course_seo_log` MODIFY COLUMN `status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A-> Active, I -> Inactive';
ALTER TABLE `tbl_course_seo_log` MODIFY COLUMN `created_by` int(11) NOT NULL;
ALTER TABLE `tbl_course_seo_log` MODIFY COLUMN `modified_by` int(11) DEFAULT NULL;

-- Table: tbl_entity_orders
ALTER TABLE `tbl_entity_orders` MODIFY COLUMN `id` int(11) NOT NULL;
ALTER TABLE `tbl_entity_orders` MODIFY COLUMN `user_id` int(11) NOT NULL;
ALTER TABLE `tbl_entity_orders` MODIFY COLUMN `cart_id` int(11) NOT NULL;
ALTER TABLE `tbl_entity_orders` MODIFY COLUMN `payment_id` int(11) NOT NULL;
ALTER TABLE `tbl_entity_orders` MODIFY COLUMN `course_id` int(11) NOT NULL;
ALTER TABLE `tbl_entity_orders` MODIFY COLUMN `schedule_id` int(11) NOT NULL;
ALTER TABLE `tbl_entity_orders` MODIFY COLUMN `entity_id` int(11) NOT NULL;
ALTER TABLE `tbl_entity_orders` MODIFY COLUMN `entity_branch_id` int(11) NOT NULL;
ALTER TABLE `tbl_entity_orders` MODIFY COLUMN `edquill_class_id` int(11) NOT NULL;
ALTER TABLE `tbl_entity_orders` MODIFY COLUMN `student_class_id` int(11) NOT NULL;
ALTER TABLE `tbl_entity_orders` MODIFY COLUMN `created_by` int(11) NOT NULL;
ALTER TABLE `tbl_entity_orders` MODIFY COLUMN `modified_by` int(11) DEFAULT NULL;

-- Table: tbl_event
ALTER TABLE `tbl_event` MODIFY COLUMN `event_id` int(11) NOT NULL;
ALTER TABLE `tbl_event` MODIFY COLUMN `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_event` MODIFY COLUMN `description` text COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_event` MODIFY COLUMN `entity_id` int(11) NOT NULL;
ALTER TABLE `tbl_event` MODIFY COLUMN `location` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_event` MODIFY COLUMN `start_time` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_event` MODIFY COLUMN `end_time` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_event` MODIFY COLUMN `path` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_event` MODIFY COLUMN `is_popular` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'N';
ALTER TABLE `tbl_event` MODIFY COLUMN `status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A-> Active, I -> Inactive';
ALTER TABLE `tbl_event` MODIFY COLUMN `created_by` int(11) NOT NULL;
ALTER TABLE `tbl_event` MODIFY COLUMN `modified_by` int(11) DEFAULT NULL;

-- Table: tbl_location
ALTER TABLE `tbl_location` MODIFY COLUMN `location_id` int(11) NOT NULL;
ALTER TABLE `tbl_location` MODIFY COLUMN `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_location` MODIFY COLUMN `status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A-> active, I ->inactive';
ALTER TABLE `tbl_location` MODIFY COLUMN `created_by` int(11) NOT NULL;
ALTER TABLE `tbl_location` MODIFY COLUMN `modified_by` int(11) DEFAULT NULL;

-- Table: tbl_payment_details
ALTER TABLE `tbl_payment_details` MODIFY COLUMN `payment_id` int(11) NOT NULL;
ALTER TABLE `tbl_payment_details` MODIFY COLUMN `cart_id` int(11) NOT NULL;
ALTER TABLE `tbl_payment_details` MODIFY COLUMN `currency_code` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_payment_details` MODIFY COLUMN `payment_status` tinytext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '1->Success,2->Failed ,3->Cancelled';
ALTER TABLE `tbl_payment_details` MODIFY COLUMN `transaction_details` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_payment_details` MODIFY COLUMN `payment_response` varchar(3000) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_payment_details` MODIFY COLUMN `created_by` int(11) NOT NULL;
ALTER TABLE `tbl_payment_details` MODIFY COLUMN `modified_by` int(11) DEFAULT NULL;

-- Table: tbl_payment_details_log
ALTER TABLE `tbl_payment_details_log` MODIFY COLUMN `payment_id` int(11) NOT NULL;
ALTER TABLE `tbl_payment_details_log` MODIFY COLUMN `registration_id` int(11) NOT NULL;
ALTER TABLE `tbl_payment_details_log` MODIFY COLUMN `payment_mode` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_payment_details_log` MODIFY COLUMN `transaction_details` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_payment_details_log` MODIFY COLUMN `created_by` int(11) NOT NULL;
ALTER TABLE `tbl_payment_details_log` MODIFY COLUMN `modified_by` int(11) DEFAULT NULL;

-- Table: tbl_registration
ALTER TABLE `tbl_registration` MODIFY COLUMN `registration_id` int(11) NOT NULL;
ALTER TABLE `tbl_registration` MODIFY COLUMN `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_registration` MODIFY COLUMN `last_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_registration` MODIFY COLUMN `email` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_registration` MODIFY COLUMN `mobile` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_registration` MODIFY COLUMN `gender` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'M-> male, F->female, O->others';
ALTER TABLE `tbl_registration` MODIFY COLUMN `grade_id` int(11) NOT NULL;
ALTER TABLE `tbl_registration` MODIFY COLUMN `course_id` int(11) NOT NULL;
ALTER TABLE `tbl_registration` MODIFY COLUMN `schedule_id` int(11) NOT NULL;
ALTER TABLE `tbl_registration` MODIFY COLUMN `location_id` int(11) NOT NULL;
ALTER TABLE `tbl_registration` MODIFY COLUMN `state_id` int(11) NOT NULL;
ALTER TABLE `tbl_registration` MODIFY COLUMN `country_id` int(11) NOT NULL;
ALTER TABLE `tbl_registration` MODIFY COLUMN `zipcode` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_registration` MODIFY COLUMN `city` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_registration` MODIFY COLUMN `address1` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_registration` MODIFY COLUMN `address2` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_registration` MODIFY COLUMN `refered_by` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_registration` MODIFY COLUMN `status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A-> active, I ->inactive';
ALTER TABLE `tbl_registration` MODIFY COLUMN `payment_status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'N -> not paid,F ->, free cost, P->partially paid, C -> payment completed, R -> refunded';
ALTER TABLE `tbl_registration` MODIFY COLUMN `created_by` int(11) NOT NULL;
ALTER TABLE `tbl_registration` MODIFY COLUMN `modified_by` int(11) DEFAULT NULL;

-- Table: tbl_registration_details
ALTER TABLE `tbl_registration_details` MODIFY COLUMN `id` int(11) NOT NULL;
ALTER TABLE `tbl_registration_details` MODIFY COLUMN `registration_id` int(11) NOT NULL;
ALTER TABLE `tbl_registration_details` MODIFY COLUMN `relationship_id` int(11) NOT NULL;
ALTER TABLE `tbl_registration_details` MODIFY COLUMN `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_registration_details` MODIFY COLUMN `last_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_registration_details` MODIFY COLUMN `email` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_registration_details` MODIFY COLUMN `mobile` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_registration_details` MODIFY COLUMN `created_by` int(11) NOT NULL;
ALTER TABLE `tbl_registration_details` MODIFY COLUMN `modified_by` int(11) NOT NULL;
ALTER TABLE `tbl_registration_details` MODIFY COLUMN `modified_date` int(11) DEFAULT NULL;

-- Table: tbl_registration_details_log
ALTER TABLE `tbl_registration_details_log` MODIFY COLUMN `id` int(11) NOT NULL;
ALTER TABLE `tbl_registration_details_log` MODIFY COLUMN `registration_id` int(11) NOT NULL;
ALTER TABLE `tbl_registration_details_log` MODIFY COLUMN `relationship_id` int(11) NOT NULL;
ALTER TABLE `tbl_registration_details_log` MODIFY COLUMN `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_registration_details_log` MODIFY COLUMN `last_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_registration_details_log` MODIFY COLUMN `email` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_registration_details_log` MODIFY COLUMN `mobile` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_registration_details_log` MODIFY COLUMN `created_by` int(11) NOT NULL;
ALTER TABLE `tbl_registration_details_log` MODIFY COLUMN `modified_by` int(11) NOT NULL;
ALTER TABLE `tbl_registration_details_log` MODIFY COLUMN `modified_date` int(11) DEFAULT NULL;

-- Table: tbl_registration_log
ALTER TABLE `tbl_registration_log` MODIFY COLUMN `registration_id` int(11) NOT NULL;
ALTER TABLE `tbl_registration_log` MODIFY COLUMN `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_registration_log` MODIFY COLUMN `last_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_registration_log` MODIFY COLUMN `email` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_registration_log` MODIFY COLUMN `mobile` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `tbl_registration_log` MODIFY COLUMN `gender` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'M-> male, F->female, O->others';
ALTER TABLE `tbl_registration_log` MODIFY COLUMN `grade_id` int(11) NOT NULL;
ALTER TABLE `tbl_registration_log` MODIFY COLUMN `course_id` int(11) NOT NULL;
ALTER TABLE `tbl_registration_log` MODIFY COLUMN `schedule_id` int(11) NOT NULL;
ALTER TABLE `tbl_registration_log` MODIFY COLUMN `location_id` int(11) NOT NULL;
ALTER TABLE `tbl_registration_log` MODIFY COLUMN `state_id` int(11) NOT NULL;
ALTER TABLE `tbl_registration_log` MODIFY COLUMN `country_id` int(11) NOT NULL;
ALTER TABLE `tbl_registration_log` MODIFY COLUMN `zipcode` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_registration_log` MODIFY COLUMN `city` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_registration_log` MODIFY COLUMN `address1` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_registration_log` MODIFY COLUMN `address2` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_registration_log` MODIFY COLUMN `refered_by` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_registration_log` MODIFY COLUMN `status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A-> active, I ->inactive';
ALTER TABLE `tbl_registration_log` MODIFY COLUMN `payment_status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'N -> not paid,F ->, free cost, P->partially paid, C -> payment completed, R -> refunded';
ALTER TABLE `tbl_registration_log` MODIFY COLUMN `created_by` int(11) NOT NULL;
ALTER TABLE `tbl_registration_log` MODIFY COLUMN `modified_by` int(11) DEFAULT NULL;

-- Table: tbl_subscription
ALTER TABLE `tbl_subscription` MODIFY COLUMN `id` int(11) NOT NULL;
ALTER TABLE `tbl_subscription` MODIFY COLUMN `email_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_subscription` MODIFY COLUMN `created_by` int(11) NOT NULL;

-- Table: tbl_user_cart_details
ALTER TABLE `tbl_user_cart_details` MODIFY COLUMN `cart_id` bigint(20) NOT NULL;
ALTER TABLE `tbl_user_cart_details` MODIFY COLUMN `user_id` bigint(20) NOT NULL;
ALTER TABLE `tbl_user_cart_details` MODIFY COLUMN `cart_data` text COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tbl_user_cart_details` MODIFY COLUMN `invoice_url` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL;

-- Table: teacher_action_notification
ALTER TABLE `teacher_action_notification` MODIFY COLUMN `action_id` bigint(20) NOT NULL;
ALTER TABLE `teacher_action_notification` MODIFY COLUMN `user_id` bigint(20) NOT NULL;
ALTER TABLE `teacher_action_notification` MODIFY COLUMN `role_id` bigint(20) NOT NULL;
ALTER TABLE `teacher_action_notification` MODIFY COLUMN `class_id` bigint(20) NOT NULL DEFAULT '0';
ALTER TABLE `teacher_action_notification` MODIFY COLUMN `content_id` bigint(20) NOT NULL DEFAULT '0';
ALTER TABLE `teacher_action_notification` MODIFY COLUMN `school_id` bigint(20) NOT NULL;
ALTER TABLE `teacher_action_notification` MODIFY COLUMN `status` int(11) NOT NULL DEFAULT '0';

-- Table: teacher_class_annotation
ALTER TABLE `teacher_class_annotation` MODIFY COLUMN `id` bigint(20) NOT NULL;
ALTER TABLE `teacher_class_annotation` MODIFY COLUMN `teacher_id` bigint(20) NOT NULL;
ALTER TABLE `teacher_class_annotation` MODIFY COLUMN `class_id` bigint(20) NOT NULL;
ALTER TABLE `teacher_class_annotation` MODIFY COLUMN `content_id` bigint(20) NOT NULL;
ALTER TABLE `teacher_class_annotation` MODIFY COLUMN `school_id` bigint(20) NOT NULL;
ALTER TABLE `teacher_class_annotation` MODIFY COLUMN `created_by` bigint(20) NOT NULL;
ALTER TABLE `teacher_class_annotation` MODIFY COLUMN `modified_by` bigint(20) NOT NULL;

-- Table: teacher_overall_feedback
ALTER TABLE `teacher_overall_feedback` MODIFY COLUMN `id` int(11) NOT NULL;
ALTER TABLE `teacher_overall_feedback` MODIFY COLUMN `student_content_id` bigint(20) NOT NULL;
ALTER TABLE `teacher_overall_feedback` MODIFY COLUMN `feedback` text COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `teacher_overall_feedback` MODIFY COLUMN `feedback_type` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A->Automatic,M->Manual';
ALTER TABLE `teacher_overall_feedback` MODIFY COLUMN `version` int(11) DEFAULT NULL;
ALTER TABLE `teacher_overall_feedback` MODIFY COLUMN `status` tinyint(4) NOT NULL COMMENT '1->Active,0->Inactive';
ALTER TABLE `teacher_overall_feedback` MODIFY COLUMN `created_by` int(11) NOT NULL;
ALTER TABLE `teacher_overall_feedback` MODIFY COLUMN `modified_by` int(11) DEFAULT NULL;

-- Table: testimonial
ALTER TABLE `testimonial` MODIFY COLUMN `id` bigint(20) NOT NULL;
ALTER TABLE `testimonial` MODIFY COLUMN `name` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `testimonial` MODIFY COLUMN `description` longtext COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `testimonial` MODIFY COLUMN `image` text COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `testimonial` MODIFY COLUMN `rating` int(11) NOT NULL;
ALTER TABLE `testimonial` MODIFY COLUMN `status` int(11) NOT NULL COMMENT '1->active,2->inactive';
ALTER TABLE `testimonial` MODIFY COLUMN `display_type` int(11) NOT NULL COMMENT '1 -> general, 2 -> learing center, 3 -> tutors, 4 -> publishers';
ALTER TABLE `testimonial` MODIFY COLUMN `created_by` bigint(20) NOT NULL;
ALTER TABLE `testimonial` MODIFY COLUMN `modified_by` bigint(20) NOT NULL;

-- Table: test_type_master
ALTER TABLE `test_type_master` MODIFY COLUMN `test_type_id` int(11) NOT NULL;
ALTER TABLE `test_type_master` MODIFY COLUMN `test_type` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL;

-- Table: text_questions
ALTER TABLE `text_questions` MODIFY COLUMN `question_id` int(11) NOT NULL;
ALTER TABLE `text_questions` MODIFY COLUMN `content_id` bigint(20) NOT NULL;
ALTER TABLE `text_questions` MODIFY COLUMN `question_type_id` bigint(20) NOT NULL;
ALTER TABLE `text_questions` MODIFY COLUMN `sub_question_type_id` bigint(20) NOT NULL DEFAULT '0';
ALTER TABLE `text_questions` MODIFY COLUMN `editor_type` int(11) NOT NULL DEFAULT '1' COMMENT '1-> KeyBoard, 2-> Text, 3-> Math, 4->Diagram';
ALTER TABLE `text_questions` MODIFY COLUMN `question_no` bigint(20) NOT NULL;
ALTER TABLE `text_questions` MODIFY COLUMN `has_sub_question` int(11) NOT NULL;
ALTER TABLE `text_questions` MODIFY COLUMN `level` int(11) NOT NULL COMMENT '1->Easy, 2-> Medium, 3-> Hard';
ALTER TABLE `text_questions` MODIFY COLUMN `multiple_response` int(11) NOT NULL DEFAULT '0';
ALTER TABLE `text_questions` MODIFY COLUMN `audo_grade` int(11) NOT NULL DEFAULT '0';
ALTER TABLE `text_questions` MODIFY COLUMN `word_limit` bigint(20) NOT NULL;
ALTER TABLE `text_questions` MODIFY COLUMN `passage_id` int(11) NOT NULL;
ALTER TABLE `text_questions` MODIFY COLUMN `subject_id` int(11) DEFAULT '0';
ALTER TABLE `text_questions` MODIFY COLUMN `question_topic_id` int(11) DEFAULT '0';
ALTER TABLE `text_questions` MODIFY COLUMN `question_sub_topic_id` int(11) DEFAULT '0';
ALTER TABLE `text_questions` MODIFY COLUMN `question_standard` int(11) DEFAULT '0';
ALTER TABLE `text_questions` MODIFY COLUMN `created_by` bigint(20) NOT NULL;
ALTER TABLE `text_questions` MODIFY COLUMN `modified_by` bigint(20) NOT NULL;

-- Table: time_zone
ALTER TABLE `time_zone` MODIFY COLUMN `id` bigint(20) NOT NULL;
ALTER TABLE `time_zone` MODIFY COLUMN `continents_id` bigint(20) NOT NULL;
ALTER TABLE `time_zone` MODIFY COLUMN `time_zone` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `time_zone` MODIFY COLUMN `utc_timezone` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `time_zone` MODIFY COLUMN `status` int(11) NOT NULL DEFAULT '1';

-- Table: time_zone_master
ALTER TABLE `time_zone_master` MODIFY COLUMN `id` bigint(20) NOT NULL;
ALTER TABLE `time_zone_master` MODIFY COLUMN `continents_name` longtext COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `time_zone_master` MODIFY COLUMN `status` int(11) NOT NULL DEFAULT '1' COMMENT 'status - >1 active,0->inactive';

-- Table: token
ALTER TABLE `token` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL;
ALTER TABLE `token` MODIFY COLUMN `client_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `token` MODIFY COLUMN `token_prefix` char(16) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `token` MODIFY COLUMN `allowed_domain` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `token` MODIFY COLUMN `expires_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00';

-- Table: topic
ALTER TABLE `topic` MODIFY COLUMN `topic_id` bigint(20) NOT NULL;
ALTER TABLE `topic` MODIFY COLUMN `topic` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `topic` MODIFY COLUMN `class_id` bigint(20) NOT NULL;
ALTER TABLE `topic` MODIFY COLUMN `display_order` int(11) NOT NULL;
ALTER TABLE `topic` MODIFY COLUMN `status` int(11) NOT NULL DEFAULT '1' COMMENT '1-> active, 2-> inactive';
ALTER TABLE `topic` MODIFY COLUMN `created_by` int(11) NOT NULL;
ALTER TABLE `topic` MODIFY COLUMN `modified_by` int(11) DEFAULT NULL;

-- Table: tutor_applications
ALTER TABLE `tutor_applications` MODIFY COLUMN `id` int(11) NOT NULL;
ALTER TABLE `tutor_applications` MODIFY COLUMN `first_name` varchar(100) CHARACTER SET utf8 NOT NULL;
ALTER TABLE `tutor_applications` MODIFY COLUMN `last_name` varchar(100) CHARACTER SET utf8 NOT NULL;
ALTER TABLE `tutor_applications` MODIFY COLUMN `email` varchar(255) CHARACTER SET utf8 NOT NULL;
ALTER TABLE `tutor_applications` MODIFY COLUMN `phone` varchar(20) CHARACTER SET utf8 NOT NULL;
ALTER TABLE `tutor_applications` MODIFY COLUMN `bio` text CHARACTER SET utf8 NOT NULL;
ALTER TABLE `tutor_applications` MODIFY COLUMN `profile_image` varchar(255) CHARACTER SET utf8 DEFAULT NULL;
ALTER TABLE `tutor_applications` MODIFY COLUMN `degree` varchar(100) CHARACTER SET utf8 NOT NULL;
ALTER TABLE `tutor_applications` MODIFY COLUMN `institution` varchar(255) CHARACTER SET utf8 NOT NULL;
ALTER TABLE `tutor_applications` MODIFY COLUMN `graduation_year` varchar(4) CHARACTER SET utf8 NOT NULL;
ALTER TABLE `tutor_applications` MODIFY COLUMN `experience` text CHARACTER SET utf8 NOT NULL;
ALTER TABLE `tutor_applications` MODIFY COLUMN `status` varchar(20) CHARACTER SET utf8 NOT NULL DEFAULT 'pending';

-- Table: updated_class_schedule
ALTER TABLE `updated_class_schedule` MODIFY COLUMN `id` bigint(20) NOT NULL;
ALTER TABLE `updated_class_schedule` MODIFY COLUMN `class_id` bigint(20) NOT NULL;
ALTER TABLE `updated_class_schedule` MODIFY COLUMN `teacher_id` text NOT NULL;
ALTER TABLE `updated_class_schedule` MODIFY COLUMN `school_id` bigint(20) NOT NULL;
ALTER TABLE `updated_class_schedule` MODIFY COLUMN `slot_days` int(11) NOT NULL;
ALTER TABLE `updated_class_schedule` MODIFY COLUMN `status` int(11) NOT NULL COMMENT '1-> Added, 2-> Deleted';
ALTER TABLE `updated_class_schedule` MODIFY COLUMN `created_by` bigint(20) NOT NULL;

-- Table: upgrade
ALTER TABLE `upgrade` MODIFY COLUMN `id` bigint(20) NOT NULL;
ALTER TABLE `upgrade` MODIFY COLUMN `school_id` bigint(20) NOT NULL;
ALTER TABLE `upgrade` MODIFY COLUMN `student_id` bigint(20) NOT NULL;
ALTER TABLE `upgrade` MODIFY COLUMN `created_by` bigint(20) NOT NULL;
ALTER TABLE `upgrade` MODIFY COLUMN `modified_by` bigint(20) NOT NULL;

-- Table: user
ALTER TABLE `user` MODIFY COLUMN `user_id` bigint(20) NOT NULL;
ALTER TABLE `user` MODIFY COLUMN `role_id` int(11) NOT NULL COMMENT '1-> SuperAdmin, 2->Admin, 3->ContentCreater, 4->Teacher, 5->Students, 6->Corporate, 7->Grader';
ALTER TABLE `user` MODIFY COLUMN `status` int(11) NOT NULL COMMENT '1- Active, 2- Inactive, 3- Suspended, 4- Deleted';
ALTER TABLE `user` MODIFY COLUMN `corporate_id` bigint(20) NOT NULL DEFAULT '0';
ALTER TABLE `user` MODIFY COLUMN `individual_teacher` int(11) NOT NULL DEFAULT '0';
ALTER TABLE `user` MODIFY COLUMN `tc_status` int(11) NOT NULL DEFAULT '0' COMMENT '0-> T&C Not Accepted, 1-> T&C Accepted';
ALTER TABLE `user` MODIFY COLUMN `edquill_teacher_id` int(11) NOT NULL;
ALTER TABLE `user` MODIFY COLUMN `auto_generate_email_edquill` bigint(20) NOT NULL DEFAULT '0';
ALTER TABLE `user` MODIFY COLUMN `academy_user_id` int(11) NOT NULL;
ALTER TABLE `user` MODIFY COLUMN `created_by` bigint(20) DEFAULT NULL;
ALTER TABLE `user` MODIFY COLUMN `modified_by` bigint(20) DEFAULT NULL;

-- Table: user_address
ALTER TABLE `user_address` MODIFY COLUMN `address_id` bigint(20) NOT NULL;
ALTER TABLE `user_address` MODIFY COLUMN `address_type` int(11) NOT NULL COMMENT '1->teacher ,2-> student parent 1, 3-> student parent 2, 4-> Content Creater';
ALTER TABLE `user_address` MODIFY COLUMN `user_id` bigint(20) NOT NULL;
ALTER TABLE `user_address` MODIFY COLUMN `state` int(11) DEFAULT NULL;
ALTER TABLE `user_address` MODIFY COLUMN `country` int(11) NOT NULL DEFAULT '0';

-- Table: user_permission
ALTER TABLE `user_permission` MODIFY COLUMN `id` bigint(20) NOT NULL;
ALTER TABLE `user_permission` MODIFY COLUMN `role_id` int(11) NOT NULL;
ALTER TABLE `user_permission` MODIFY COLUMN `permission_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `user_permission` MODIFY COLUMN `display_order` int(11) NOT NULL;
ALTER TABLE `user_permission` MODIFY COLUMN `group_id` int(11) NOT NULL;
ALTER TABLE `user_permission` MODIFY COLUMN `group_name` text COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `user_permission` MODIFY COLUMN `description` text COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `user_permission` MODIFY COLUMN `status` int(11) NOT NULL DEFAULT '1';

-- Table: user_permission_backup
ALTER TABLE `user_permission_backup` MODIFY COLUMN `id` bigint(20) NOT NULL;
ALTER TABLE `user_permission_backup` MODIFY COLUMN `role_id` int(11) NOT NULL;
ALTER TABLE `user_permission_backup` MODIFY COLUMN `permission_id` bigint(20) NOT NULL;
ALTER TABLE `user_permission_backup` MODIFY COLUMN `group_id` int(11) NOT NULL;
ALTER TABLE `user_permission_backup` MODIFY COLUMN `status` int(11) NOT NULL DEFAULT '1';

-- Table: user_profile
ALTER TABLE `user_profile` MODIFY COLUMN `profile_id` bigint(20) NOT NULL;
ALTER TABLE `user_profile` MODIFY COLUMN `user_id` bigint(20) NOT NULL;
ALTER TABLE `user_profile` MODIFY COLUMN `created_by` bigint(20) NOT NULL;
ALTER TABLE `user_profile` MODIFY COLUMN `modified_by` bigint(20) DEFAULT NULL;

-- Table: user_profile_details
ALTER TABLE `user_profile_details` ADD COLUMN `next_billing_date` date DEFAULT NULL COMMENT 'Next billing date for automatic/manual billing at student level';
ALTER TABLE `user_profile_details` MODIFY COLUMN `user_details_id` bigint(20) NOT NULL;
ALTER TABLE `user_profile_details` MODIFY COLUMN `user_id` bigint(20) NOT NULL;
ALTER TABLE `user_profile_details` MODIFY COLUMN `school_id` bigint(20) NOT NULL;
ALTER TABLE `user_profile_details` MODIFY COLUMN `status` int(11) DEFAULT '1' COMMENT '1->active, 2->inactive';
ALTER TABLE `user_profile_details` MODIFY COLUMN `individual_teacher` int(11) NOT NULL DEFAULT '0';
ALTER TABLE `user_profile_details` MODIFY COLUMN `individual_role` int(11) NOT NULL DEFAULT '0' COMMENT '0->teacher,1->parent';
ALTER TABLE `user_profile_details` MODIFY COLUMN `designation` varchar(100) DEFAULT NULL;
ALTER TABLE `user_profile_details` MODIFY COLUMN `school_idno` varchar(20) DEFAULT NULL;
ALTER TABLE `user_profile_details` MODIFY COLUMN `batch_id` bigint(20) NOT NULL;
ALTER TABLE `user_profile_details` MODIFY COLUMN `edit_status` int(11) NOT NULL DEFAULT '0' COMMENT '0 -> updated 1-> Not Updated';
ALTER TABLE `user_profile_details` MODIFY COLUMN `allow_dashboard` int(11) NOT NULL DEFAULT '1';
ALTER TABLE `user_profile_details` MODIFY COLUMN `created_by` int(11) NOT NULL;

-- Table: user_role_permission
ALTER TABLE `user_role_permission` MODIFY COLUMN `id` bigint(20) NOT NULL;
ALTER TABLE `user_role_permission` MODIFY COLUMN `user_id` bigint(20) NOT NULL;
ALTER TABLE `user_role_permission` MODIFY COLUMN `role_id` bigint(20) NOT NULL;
ALTER TABLE `user_role_permission` MODIFY COLUMN `school_id` bigint(20) NOT NULL;
ALTER TABLE `user_role_permission` MODIFY COLUMN `user_permission_id` bigint(20) NOT NULL;

-- Table: user_role_permission_backup
ALTER TABLE `user_role_permission_backup` MODIFY COLUMN `id` bigint(20) NOT NULL;
ALTER TABLE `user_role_permission_backup` MODIFY COLUMN `user_id` bigint(20) NOT NULL;
ALTER TABLE `user_role_permission_backup` MODIFY COLUMN `role_id` bigint(20) NOT NULL;
ALTER TABLE `user_role_permission_backup` MODIFY COLUMN `school_id` bigint(20) NOT NULL;
ALTER TABLE `user_role_permission_backup` MODIFY COLUMN `user_permission_id` bigint(20) NOT NULL;

-- Table: user_security
ALTER TABLE `user_security` MODIFY COLUMN `security_id` bigint(20) NOT NULL;
ALTER TABLE `user_security` MODIFY COLUMN `login_device` int(11) DEFAULT NULL;

-- Table: user_token
ALTER TABLE `user_token` ADD COLUMN `modified_date` datetime DEFAULT NULL COMMENT 'Last modification timestamp';
ALTER TABLE `user_token` MODIFY COLUMN `id` int(11) NOT NULL;
ALTER TABLE `user_token` MODIFY COLUMN `user_id` bigint(20) NOT NULL;
ALTER TABLE `user_token` MODIFY COLUMN `status` int(11) NOT NULL;

-- Table: user_uri_detail
ALTER TABLE `user_uri_detail` MODIFY COLUMN `id` int(11) NOT NULL;
ALTER TABLE `user_uri_detail` MODIFY COLUMN `uri_path` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `user_uri_detail` MODIFY COLUMN `front_end_url` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `user_uri_detail` MODIFY COLUMN `user_id` bigint(20) NOT NULL;
ALTER TABLE `user_uri_detail` MODIFY COLUMN `role_id` int(11) NOT NULL;
ALTER TABLE `user_uri_detail` MODIFY COLUMN `user_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `user_uri_detail` MODIFY COLUMN `email_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `user_uri_detail` MODIFY COLUMN `created_by` int(11) NOT NULL;

-- Table: website_contact_us
ALTER TABLE `website_contact_us` MODIFY COLUMN `id` bigint(20) NOT NULL;
ALTER TABLE `website_contact_us` MODIFY COLUMN `type` int(11) NOT NULL COMMENT '2-> palsnortherns, 1->palssouthplainfield, 3->palsmarlboro.com, 4->palseastbrunswick, 5->palsmonroe.com, 6->palsoldbridge, 7->palsfreehold, 8->palspiscataway';
ALTER TABLE `website_contact_us` MODIFY COLUMN `sub_type` char(2) DEFAULT NULL COMMENT '1->Home,2->K-6 Math,3->Pre-Algebra,4->Algebra 1,5->Geometry,6->Algebra 2,7->Pre-Calculus,8->English,9->Reading-And-Writing,10->SAT-Prep,11->PSAT8-Prep,12->High-School-Prep,13->Physics-Honors,14->Chemistry-Honors,15->Biology-Honors,16->AP-Biology,17->AP-Chemistry,18->AP-Physics,19->AP-Calculus AB-BC,20->AP-Statistics';
ALTER TABLE `website_contact_us` MODIFY COLUMN `status` int(11) NOT NULL COMMENT '1-> mail sent, 2-> mail not sent';

-- Table: zoom_creation_email
ALTER TABLE `zoom_creation_email` MODIFY COLUMN `id` bigint(20) NOT NULL;
ALTER TABLE `zoom_creation_email` MODIFY COLUMN `user_email` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `zoom_creation_email` MODIFY COLUMN `class_id` bigint(20) NOT NULL;
ALTER TABLE `zoom_creation_email` MODIFY COLUMN `schedule_id` bigint(20) NOT NULL;
ALTER TABLE `zoom_creation_email` MODIFY COLUMN `school_id` bigint(20) NOT NULL;
ALTER TABLE `zoom_creation_email` MODIFY COLUMN `start_time` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `zoom_creation_email` MODIFY COLUMN `end_time` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `zoom_creation_email` MODIFY COLUMN `slot_days` int(11) NOT NULL;
ALTER TABLE `zoom_creation_email` MODIFY COLUMN `meeting_id` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `zoom_creation_email` MODIFY COLUMN `teacher_link` longtext COLLATE utf8mb4_unicode_ci;
ALTER TABLE `zoom_creation_email` MODIFY COLUMN `student_link` longtext COLLATE utf8mb4_unicode_ci;
ALTER TABLE `zoom_creation_email` MODIFY COLUMN `zoom_response` longtext COLLATE utf8mb4_unicode_ci;
ALTER TABLE `zoom_creation_email` MODIFY COLUMN `created_by` bigint(20) NOT NULL;

-- Table: zoom_recording
ALTER TABLE `zoom_recording` MODIFY COLUMN `id` bigint(20) NOT NULL;
ALTER TABLE `zoom_recording` MODIFY COLUMN `class_id` bigint(20) NOT NULL;
ALTER TABLE `zoom_recording` MODIFY COLUMN `school_id` bigint(20) NOT NULL;
ALTER TABLE `zoom_recording` MODIFY COLUMN `meeting_id` text COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `zoom_recording` MODIFY COLUMN `created_by` bigint(20) NOT NULL;

-- Table: zoom_token
ALTER TABLE `zoom_token` MODIFY COLUMN `id` int(11) NOT NULL;
ALTER TABLE `zoom_token` MODIFY COLUMN `access_token` text COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `zoom_token` MODIFY COLUMN `school_id` int(11) NOT NULL;
ALTER TABLE `zoom_token` MODIFY COLUMN `created_by` int(11) NOT NULL;

-- ============================================
-- New tables to be created
-- ============================================

-- CREATE TABLE for categories
CREATE TABLE `categories` (

  `category_id` int(11) UNSIGNED NOT NULL,
  `category_name` varchar(255) NOT NULL,
  `subject_id` int(11) UNSIGNED DEFAULT NULL,
  `description` text,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `path` varchar(255) DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- CREATE TABLE for course_class_mapping
CREATE TABLE `course_class_mapping` (

  `id` int(11) UNSIGNED NOT NULL,
  `course_id` int(11) UNSIGNED NOT NULL,
  `class_id` int(11) UNSIGNED NOT NULL,
  `school_id` int(11) UNSIGNED NOT NULL,
  `auto_enroll` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Auto-enroll students in this class when course is added',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- CREATE TABLE for crm_followups
CREATE TABLE `crm_followups` (

  `followup_id` bigint(20) UNSIGNED NOT NULL,
  `school_id` bigint(20) UNSIGNED NOT NULL,
  `action` varchar(255) NOT NULL COMMENT 'Description of the follow-up action',
  `owner_user_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'User assigned to complete this follow-up',
  `due_date` date DEFAULT NULL COMMENT 'Due date for the follow-up',
  `status` enum('pending','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
  `related_type` varchar(40) DEFAULT NULL COMMENT 'Type of related entity (e.g., registration, course_registration)',
  `related_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'ID of the related entity',
  `notes` text COMMENT 'Additional notes about the follow-up',
  `completed_at` timestamp NULL DEFAULT NULL COMMENT 'When the follow-up was completed',
  `completed_by` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'User who completed the follow-up',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'User who created the follow-up',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- CREATE TABLE for crm_notes
CREATE TABLE `crm_notes` (

  `id` bigint(20) UNSIGNED NOT NULL,
  `entity_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `registration_id` bigint(20) UNSIGNED DEFAULT NULL,
  `student_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `contact_id` bigint(20) UNSIGNED DEFAULT NULL,
  `note_type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'internal',
  `interaction_type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'workflow',
  `channel` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'internal',
  `origin` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `visibility` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'internal',
  `title` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `metadata` json DEFAULT NULL,
  `tags` json DEFAULT NULL,
  `plugin_source` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by_name` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CREATE TABLE for email_attachments
CREATE TABLE `email_attachments` (

  `AttachmentID` int(11) NOT NULL,
  `EmailID` int(11) NOT NULL,
  `FileName` varchar(255) NOT NULL,
  `FilePath` varchar(255) NOT NULL,
  `FileSize` int(11) NOT NULL,
  `FileType` varchar(100) NOT NULL,
  `UploadDate` datetime NOT NULL,
  `school_id` int(11) NOT NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- CREATE TABLE for exams
CREATE TABLE `exams` (

  `id` bigint(20) UNSIGNED NOT NULL,
  `school_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `term` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `class_id` bigint(20) UNSIGNED DEFAULT NULL,
  `exam_date` date DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CREATE TABLE for exam_scores
CREATE TABLE `exam_scores` (

  `id` bigint(20) UNSIGNED NOT NULL,
  `exam_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `subject` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `max_score` decimal(10,2) NOT NULL DEFAULT '100.00',
  `score` decimal(10,2) NOT NULL DEFAULT '0.00',
  `teacher_comments` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CREATE TABLE for fee_plans
CREATE TABLE `fee_plans` (

  `id` bigint(20) UNSIGNED NOT NULL,
  `school_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `plan_type` enum('monthly','prepaid','per_class','custom') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'monthly',
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `billing_cycle_days` int(10) UNSIGNED DEFAULT NULL,
  `auto_payment_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `metadata` json DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CREATE TABLE for guardians
CREATE TABLE `guardians` (

  `id` bigint(20) UNSIGNED NOT NULL,
  `school_id` bigint(20) UNSIGNED NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `relationship` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `communication_preference` enum('email','sms','both') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'both',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CREATE TABLE for invoices
CREATE TABLE `invoices` (

  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `student_fee_plan_id` bigint(20) UNSIGNED DEFAULT NULL,
  `amount_due` decimal(12,2) NOT NULL,
  `amount_paid` decimal(12,2) NOT NULL DEFAULT '0.00',
  `due_date` date NOT NULL,
  `issued_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('draft','sent','paid','void') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sent',
  `invoice_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pdf_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CREATE TABLE for knowledge_base_articles
CREATE TABLE `knowledge_base_articles` (

  `id` bigint(20) NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `summary` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `school_id` int(11) NOT NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CREATE TABLE for knowledge_base_categories
CREATE TABLE `knowledge_base_categories` (

  `id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CREATE TABLE for knowledge_base_links
CREATE TABLE `knowledge_base_links` (

  `id` bigint(20) NOT NULL,
  `article_id` bigint(20) NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(2048) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CREATE TABLE for migrations
CREATE TABLE `migrations` (

  `id` bigint(20) UNSIGNED NOT NULL,
  `version` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL,
  `group` varchar(255) NOT NULL,
  `namespace` varchar(255) NOT NULL,
  `time` int(11) NOT NULL,
  `batch` int(11) UNSIGNED NOT NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- CREATE TABLE for notifications
CREATE TABLE `notifications` (

  `id` bigint(20) UNSIGNED NOT NULL,
  `template_id` bigint(20) UNSIGNED DEFAULT NULL,
  `school_id` bigint(20) UNSIGNED NOT NULL,
  `recipient_type` enum('student','guardian') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'guardian',
  `recipient_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED DEFAULT NULL,
  `channel` enum('email','sms') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'email',
  `status` enum('pending','queued','sent','failed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `scheduled_at` datetime DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `payload` json DEFAULT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CREATE TABLE for notification_optouts
CREATE TABLE `notification_optouts` (

  `id` bigint(20) UNSIGNED NOT NULL,
  `contact_type` enum('guardian','student') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'guardian',
  `contact_id` bigint(20) UNSIGNED NOT NULL,
  `channel` enum('sms','email') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'email',
  `reason` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CREATE TABLE for notification_templates
CREATE TABLE `notification_templates` (

  `id` bigint(20) UNSIGNED NOT NULL,
  `school_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `channel` enum('email','sms','both') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'both',
  `subject` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `placeholders` json DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CREATE TABLE for payments
CREATE TABLE `payments` (

  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `fee_plan_id` bigint(20) UNSIGNED DEFAULT NULL,
  `student_fee_plan_id` bigint(20) UNSIGNED DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_date` datetime NOT NULL,
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receipt_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `recorded_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CREATE TABLE for payment_transactions
CREATE TABLE `payment_transactions` (

  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `payment_method_id` int(11) DEFAULT NULL COMMENT 'NULL for one-time payments',
  `provider_id` int(11) NOT NULL,
  `transaction_type` enum('charge','refund','authorization','capture','void') COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT 'USD',
  `gateway_transaction_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Stripe charge ID, Forte transaction ID',
  `gateway_response` json DEFAULT NULL COMMENT 'Full gateway response',
  `gateway_fee` decimal(10,2) DEFAULT NULL COMMENT 'Processing fee charged by gateway',
  `status` enum('pending','processing','succeeded','failed','refunded','partially_refunded','cancelled','disputed') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status_updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `failure_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `failure_message` text COLLATE utf8mb4_unicode_ci,
  `retry_count` int(11) DEFAULT '0',
  `invoice_id` int(11) DEFAULT NULL,
  `enrollment_id` int(11) DEFAULT NULL,
  `fee_id` int(11) DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `internal_notes` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL COMMENT 'Custom metadata, receipt info, etc.',
  `refunded_amount` decimal(10,2) DEFAULT '0.00',
  `refund_reason` text COLLATE utf8mb4_unicode_ci,
  `parent_transaction_id` int(11) DEFAULT NULL COMMENT 'For refunds, links to original charge',
  `receipt_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receipt_sent` tinyint(1) DEFAULT '0',
  `receipt_sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `processed_by` int(11) DEFAULT NULL COMMENT 'User ID who initiated',
  `processed_by_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CREATE TABLE for prereg_rate_limit
CREATE TABLE `prereg_rate_limit` (

  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempt_time` datetime NOT NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- CREATE TABLE for providers
CREATE TABLE `providers` (

  `id` int(11) NOT NULL,
  `provider_type_id` int(11) NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'twilio, sendgrid, stripe, forte, etc.',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) DEFAULT '1',
  `config_schema` json DEFAULT NULL COMMENT 'Required and optional credential fields',
  `settings_schema` json DEFAULT NULL COMMENT 'Provider-specific settings schema',
  `documentation_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `features` json DEFAULT NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CREATE TABLE for provider_types
CREATE TABLE `provider_types` (

  `id` int(11) NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'sms, email, payment',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CREATE TABLE for provider_usage_log
CREATE TABLE `provider_usage_log` (

  `id` bigint(20) NOT NULL,
  `school_id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `action_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'sms_sent, email_sent, payment_charged, etc.',
  `action_subtype` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'success, failed, pending, refunded',
  `request_data` json DEFAULT NULL COMMENT 'Sanitized request payload',
  `response_data` json DEFAULT NULL COMMENT 'Sanitized response data',
  `status` enum('success','failed','pending','retrying') COLLATE utf8mb4_unicode_ci NOT NULL,
  `error_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `response_time_ms` int(11) DEFAULT NULL COMMENT 'API response time in milliseconds',
  `related_id` int(11) DEFAULT NULL COMMENT 'ID of related record (student_id, transaction_id, etc.)',
  `related_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Type of related record',
  `user_id` int(11) DEFAULT NULL,
  `user_role` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `units_used` int(11) DEFAULT '1' COMMENT 'SMS count, email count, API calls',
  `estimated_cost` decimal(8,4) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CREATE TABLE for provider_usage_logs
CREATE TABLE `provider_usage_logs` (

  `id` int(11) UNSIGNED NOT NULL,
  `school_id` int(11) UNSIGNED NOT NULL,
  `provider_id` int(11) UNSIGNED NOT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('success','failure','pending') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `request_data` json DEFAULT NULL,
  `response_data` json DEFAULT NULL,
  `error_message` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `duration_ms` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CREATE TABLE for ref_timezones
CREATE TABLE `ref_timezones` (

  `id` int(10) UNSIGNED NOT NULL,
  `timezone` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1'

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CREATE TABLE for report_cards
CREATE TABLE `report_cards` (

  `id` bigint(20) UNSIGNED NOT NULL,
  `exam_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('draft','generated','shared') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `generated_at` datetime DEFAULT NULL,
  `pdf_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `share_token` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CREATE TABLE for scheduled_payments
CREATE TABLE `scheduled_payments` (

  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `payment_method_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT 'USD',
  `frequency` enum('one_time','daily','weekly','biweekly','monthly','quarterly','semi_annual','annual') COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL COMMENT 'NULL for indefinite',
  `next_charge_date` date DEFAULT NULL,
  `last_charge_date` date DEFAULT NULL,
  `total_charges` int(11) DEFAULT '0',
  `status` enum('active','paused','completed','cancelled','failed') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `pause_reason` text COLLATE utf8mb4_unicode_ci,
  `cancellation_reason` text COLLATE utf8mb4_unicode_ci,
  `enrollment_id` int(11) DEFAULT NULL,
  `fee_plan_id` int(11) DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `max_retries` int(11) DEFAULT '3',
  `retry_count` int(11) DEFAULT '0',
  `retry_interval_days` int(11) DEFAULT '3',
  `last_failure_reason` text COLLATE utf8mb4_unicode_ci,
  `last_failure_at` timestamp NULL DEFAULT NULL,
  `notify_before_days` int(11) DEFAULT '3',
  `last_notification_sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int(11) DEFAULT NULL,
  `cancelled_by` int(11) DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CREATE TABLE for school_features
CREATE TABLE `school_features` (

  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `provider_type_id` int(11) NOT NULL,
  `is_enabled` tinyint(1) DEFAULT '0',
  `auto_fallback` tinyint(1) DEFAULT '1' COMMENT 'Auto-switch to backup provider on failure',
  `require_verification` tinyint(1) DEFAULT '0' COMMENT 'Require admin verification for transactions',
  `notify_on_failure` tinyint(1) DEFAULT '1',
  `notification_emails` json DEFAULT NULL COMMENT 'Array of email addresses for alerts',
  `settings` json DEFAULT NULL COMMENT 'Feature-specific settings',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CREATE TABLE for school_portal_settings
CREATE TABLE `school_portal_settings` (

  `id` bigint(20) UNSIGNED NOT NULL,
  `school_id` bigint(20) NOT NULL,
  `primary_color` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `secondary_color` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `accent_color` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hero_title` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hero_subtitle` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `support_email` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `support_phone` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `terms_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `privacy_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `options` json DEFAULT NULL,
  `portal_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CREATE TABLE for school_provider_config
CREATE TABLE `school_provider_config` (

  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `is_enabled` tinyint(1) DEFAULT '0',
  `credentials` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Encrypted JSON with provider credentials',
  `settings` json DEFAULT NULL COMMENT 'Provider-specific settings',
  `priority` int(11) DEFAULT '1',
  `webhook_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `webhook_secret` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Encrypted if needed',
  `last_test_at` timestamp NULL DEFAULT NULL,
  `last_test_status` enum('success','failed','pending') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_test_message` text COLLATE utf8mb4_unicode_ci,
  `last_test_by` int(11) DEFAULT NULL,
  `monthly_limit` int(11) DEFAULT NULL COMMENT 'Max transactions per month',
  `monthly_usage` int(11) DEFAULT '0',
  `last_reset_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CREATE TABLE for school_provider_configs
CREATE TABLE `school_provider_configs` (

  `id` int(11) UNSIGNED NOT NULL,
  `school_id` int(11) UNSIGNED NOT NULL,
  `provider_id` int(11) UNSIGNED NOT NULL,
  `credentials` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Encrypted JSON credentials',
  `settings` json DEFAULT NULL COMMENT 'Non-sensitive settings',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_test_mode` tinyint(1) NOT NULL DEFAULT '0',
  `priority` int(11) NOT NULL DEFAULT '0' COMMENT 'For fallback ordering',
  `last_used_at` timestamp NULL DEFAULT NULL,
  `last_error_at` timestamp NULL DEFAULT NULL,
  `error_count` int(11) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CREATE TABLE for school_registration_attribute_configs
CREATE TABLE `school_registration_attribute_configs` (

  `id` bigint(20) UNSIGNED NOT NULL,
  `school_id` bigint(20) NOT NULL,
  `definition` json NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CREATE TABLE for student_assign_content
CREATE TABLE `student_assign_content` (

  `id` bigint(20) UNSIGNED NOT NULL,
  `class_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Foreign key to classes table',
  `content_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Foreign key to content table',
  `start_date` date NOT NULL COMMENT 'Assignment start date',
  `end_date` date NOT NULL DEFAULT '0000-00-00' COMMENT 'Assignment end date (0000-00-00 means no end date)',
  `start_time` time NOT NULL DEFAULT '00:00:00' COMMENT 'Daily start time for content access',
  `end_time` time NOT NULL DEFAULT '23:59:00' COMMENT 'Daily end time for content access',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=Active, 0=Inactive',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'User ID who created the assignment',
  `created_date` datetime NOT NULL COMMENT 'Record creation timestamp'

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Content assignments to classes with scheduling';

-- CREATE TABLE for student_content_class_access
CREATE TABLE `student_content_class_access` (

  `id` bigint(20) NOT NULL,
  `student_content_id` bigint(20) NOT NULL,
  `class_id` bigint(20) NOT NULL,
  `class_content_id` bigint(20) NOT NULL,
  `created_date` datetime NOT NULL,
  `created_by` bigint(20) NOT NULL

) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- CREATE TABLE for student_courses
CREATE TABLE `student_courses` (

  `id` int(11) UNSIGNED NOT NULL,
  `student_id` int(11) UNSIGNED NOT NULL COMMENT 'User ID of student',
  `course_id` int(11) UNSIGNED NOT NULL COMMENT 'References tbl_course.course_id',
  `school_id` int(11) UNSIGNED NOT NULL,
  `registration_id` int(11) UNSIGNED DEFAULT NULL COMMENT 'Link to registration if enrolled via registration',
  `enrollment_date` date DEFAULT NULL,
  `completion_date` date DEFAULT NULL,
  `status` enum('active','completed','dropped','suspended') NOT NULL DEFAULT 'active',
  `fee_amount` decimal(10,2) DEFAULT NULL COMMENT 'Actual fee charged for this student',
  `student_fee_plan_id` int(11) UNSIGNED DEFAULT NULL COMMENT 'Link to student_fee_plans table',
  `added_by` int(11) UNSIGNED DEFAULT NULL,
  `notes` text,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- CREATE TABLE for student_custom_items
CREATE TABLE `student_custom_items` (

  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL COMMENT 'References user.user_id',
  `school_id` bigint(20) UNSIGNED NOT NULL,
  `description` varchar(255) NOT NULL COMMENT 'User-entered description of the item',
  `amount` decimal(12,2) NOT NULL COMMENT 'Amount (positive for charges, negative for discounts)',
  `start_date` date NOT NULL COMMENT 'Validity start date',
  `end_date` date DEFAULT NULL COMMENT 'Validity end date (optional)',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Whether this item is currently active',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'User who created this item',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- CREATE TABLE for student_fee_plans
CREATE TABLE `student_fee_plans` (

  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `fee_plan_id` bigint(20) UNSIGNED NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `custom_amount` decimal(12,2) DEFAULT NULL,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` enum('active','paused','ended') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `auto_payment_override` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CREATE TABLE for student_guardians
CREATE TABLE `student_guardians` (

  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `guardian_id` bigint(20) UNSIGNED NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `relationship_override` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CREATE TABLE for student_payment_methods
CREATE TABLE `student_payment_methods` (

  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `payment_token` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Encrypted: Gateway customer/payment method token',
  `token_type` enum('card','ach','bank_account','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_info` json DEFAULT NULL COMMENT 'last4, brand, bank_name, account_type, etc.',
  `is_default` tinyint(1) DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `is_verified` tinyint(1) DEFAULT '0',
  `billing_address` json DEFAULT NULL COMMENT 'Billing name, address, city, state, zip, country',
  `authorized_at` timestamp NULL DEFAULT NULL,
  `authorized_by` int(11) DEFAULT NULL COMMENT 'User ID who authorized (parent/student)',
  `authorization_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `authorization_user_agent` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gateway_customer_id` text COLLATE utf8mb4_unicode_ci COMMENT 'Encrypted: Stripe customer ID, Forte customer token',
  `gateway_payment_method_id` text COLLATE utf8mb4_unicode_ci COMMENT 'Encrypted: Provider-specific payment method ID',
  `gateway_metadata` json DEFAULT NULL COMMENT 'Additional gateway data',
  `expires_at` date DEFAULT NULL COMMENT 'For cards: expiration date',
  `expiry_notification_sent` tinyint(1) DEFAULT '0',
  `verification_status` enum('pending','in_progress','verified','failed') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `verification_attempts` int(11) DEFAULT '0',
  `verified_at` timestamp NULL DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `total_charges` int(11) DEFAULT '0',
  `total_amount` decimal(10,2) DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CREATE TABLE for student_self_registrations
CREATE TABLE `student_self_registrations` (

  `id` bigint(20) UNSIGNED NOT NULL,
  `school_id` bigint(20) NOT NULL,
  `school_key` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registration_code` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_last_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `email` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mobile` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address_line1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_minor` tinyint(1) NOT NULL DEFAULT '0',
  `guardian1_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guardian1_email` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guardian1_phone` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guardian2_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guardian2_email` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guardian2_phone` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `schedule_preference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_method` enum('card','ach','cash','check','waived','pending') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `autopay_authorized` tinyint(1) NOT NULL DEFAULT '0',
  `payment_reference` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','in_review','needs_info','approved','rejected','converted','archived') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `metadata` json DEFAULT NULL,
  `converted_student_user_id` bigint(20) DEFAULT NULL,
  `converted_primary_guardian_id` bigint(20) UNSIGNED DEFAULT NULL,
  `converted_secondary_guardian_id` bigint(20) UNSIGNED DEFAULT NULL,
  `converted_at` timestamp NULL DEFAULT NULL,
  `converted_by` bigint(20) DEFAULT NULL,
  `conversion_notes` text COLLATE utf8mb4_unicode_ci,
  `conversion_payload` json DEFAULT NULL,
  `assigned_to_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `last_status_at` timestamp NULL DEFAULT NULL,
  `last_contacted_at` timestamp NULL DEFAULT NULL,
  `priority` enum('normal','high','low') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `submitted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CREATE TABLE for student_self_registration_courses
CREATE TABLE `student_self_registration_courses` (

  `id` bigint(20) UNSIGNED NOT NULL,
  `registration_id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED DEFAULT NULL,
  `schedule_id` bigint(20) UNSIGNED DEFAULT NULL,
  `schedule_title` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fee_amount` decimal(12,2) DEFAULT NULL,
  `course_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `decision_status` enum('pending','approved','waitlisted','declined') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `approved_schedule_id` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_schedule_title` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `approved_schedule_start` date DEFAULT NULL,
  `approved_schedule_end` date DEFAULT NULL,
  `approved_fee_amount` decimal(12,2) DEFAULT NULL,
  `decision_notes` text COLLATE utf8mb4_unicode_ci,
  `start_date` date DEFAULT NULL,
  `fee_term` tinyint(1) DEFAULT NULL COMMENT '1 = one-time, 2 = recurring',
  `next_billing_date` date DEFAULT NULL,
  `deposit` decimal(12,2) DEFAULT NULL,
  `onboarding_fee` decimal(12,2) DEFAULT NULL,
  `registration_fee` decimal(12,2) DEFAULT NULL,
  `prorated_fee` decimal(12,2) DEFAULT NULL,
  `class_id` bigint(20) UNSIGNED DEFAULT NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CREATE TABLE for student_self_registration_documents
CREATE TABLE `student_self_registration_documents` (

  `id` bigint(20) UNSIGNED NOT NULL,
  `registration_id` bigint(20) UNSIGNED NOT NULL,
  `storage_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_name` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` int(10) UNSIGNED DEFAULT NULL,
  `review_status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `review_notes` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CREATE TABLE for student_self_registration_messages
CREATE TABLE `student_self_registration_messages` (

  `id` bigint(20) UNSIGNED NOT NULL,
  `registration_id` bigint(20) UNSIGNED NOT NULL,
  `channel` enum('email','sms') COLLATE utf8mb4_unicode_ci NOT NULL,
  `recipient` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('queued','sent','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sent',
  `error_message` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `sent_by` bigint(20) UNSIGNED DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CREATE TABLE for student_self_registration_notes
CREATE TABLE `student_self_registration_notes` (

  `id` bigint(20) UNSIGNED NOT NULL,
  `registration_id` bigint(20) UNSIGNED NOT NULL,
  `note_type` enum('internal','request','response','history') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'internal',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `metadata` json DEFAULT NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CREATE TABLE for tbl_order
CREATE TABLE `tbl_order` (

  `order_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL COMMENT 'Foreign key to user table',
  `course_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Foreign key to tbl_course table',
  `school_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'School identifier',
  `payment_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Payment/Order identifier',
  `payment_date` datetime DEFAULT NULL COMMENT 'Payment date',
  `payment_status` tinyint(1) DEFAULT '0' COMMENT '1=Success, 0=Failed',
  `cart_data` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON data containing course details, schedule, price, quantity',
  `total_amount` decimal(10,2) DEFAULT '0.00' COMMENT 'Total order amount',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=Active, 0=Inactive',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'User ID who created the order',
  `created_date` datetime NOT NULL COMMENT 'Record creation timestamp',
  `modified_date` datetime DEFAULT NULL COMMENT 'Last modification timestamp'

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Course orders and payments';

-- CREATE TABLE for t_appt_availability
CREATE TABLE `t_appt_availability` (

  `availability_id` bigint(20) UNSIGNED NOT NULL,
  `school_id` bigint(20) UNSIGNED NOT NULL,
  `admin_user_id` bigint(20) UNSIGNED NOT NULL,
  `dow` tinyint(4) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `slot_duration_min` smallint(6) NOT NULL DEFAULT '30',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- CREATE TABLE for t_appt_booking
CREATE TABLE `t_appt_booking` (

  `appt_id` bigint(20) UNSIGNED NOT NULL,
  `school_id` bigint(20) UNSIGNED NOT NULL,
  `admin_user_id` bigint(20) UNSIGNED NOT NULL,
  `created_by` enum('admin','student','parent') NOT NULL,
  `student_id` bigint(20) UNSIGNED DEFAULT NULL,
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(120) NOT NULL DEFAULT 'Meeting',
  `topic` text,
  `location_type` enum('in_person','phone','video') NOT NULL DEFAULT 'video',
  `location_details` varchar(255) DEFAULT NULL,
  `start_at_utc` datetime NOT NULL,
  `end_at_utc` datetime NOT NULL,
  `status` enum('pending','confirmed','cancelled','completed','no_show','rescheduled') NOT NULL DEFAULT 'confirmed',
  `reschedule_of_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cancel_reason` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- CREATE TABLE for t_appt_exception
CREATE TABLE `t_appt_exception` (

  `exception_id` bigint(20) UNSIGNED NOT NULL,
  `school_id` bigint(20) UNSIGNED NOT NULL,
  `admin_user_id` bigint(20) UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `type` enum('closed','open_override') NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- CREATE TABLE for t_appt_guest
CREATE TABLE `t_appt_guest` (

  `guest_id` bigint(20) UNSIGNED NOT NULL,
  `appt_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(120) DEFAULT NULL,
  `email` varchar(190) DEFAULT NULL,
  `role` enum('student','parent','other') NOT NULL DEFAULT 'other'

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- CREATE TABLE for t_appt_notification
CREATE TABLE `t_appt_notification` (

  `notif_id` bigint(20) UNSIGNED NOT NULL,
  `appt_id` bigint(20) UNSIGNED NOT NULL,
  `channel` enum('email','sms') NOT NULL,
  `purpose` enum('confirmation','reschedule','cancel','reminder24h','reminder1h') NOT NULL,
  `status` enum('queued','sent','failed') NOT NULL DEFAULT 'queued',
  `provider_id` varchar(128) DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- CREATE TABLE for t_audit_log
CREATE TABLE `t_audit_log` (

  `audit_id` bigint(20) NOT NULL,
  `school_id` bigint(20) UNSIGNED NOT NULL,
  `actor_user_id` bigint(20) DEFAULT NULL,
  `entity_type` varchar(40) DEFAULT NULL,
  `entity_id` bigint(20) DEFAULT NULL,
  `action` varchar(40) DEFAULT NULL,
  `before_json` text,
  `after_json` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- CREATE TABLE for t_event_outbox
CREATE TABLE `t_event_outbox` (

  `id` bigint(20) NOT NULL,
  `school_id` bigint(20) UNSIGNED NOT NULL,
  `event_type` varchar(80) NOT NULL,
  `payload_json` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `claimed_by` varchar(64) DEFAULT NULL,
  `claimed_at` timestamp NULL DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- CREATE TABLE for t_feature_flag
CREATE TABLE `t_feature_flag` (

  `school_id` bigint(20) UNSIGNED NOT NULL,
  `flag_key` varchar(64) NOT NULL,
  `flag_value` text NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- CREATE TABLE for t_marketing_kpi_daily
CREATE TABLE `t_marketing_kpi_daily` (

  `school_id` bigint(20) UNSIGNED NOT NULL,
  `day` date NOT NULL,
  `source` varchar(64) NOT NULL DEFAULT '',
  `leads` int(11) NOT NULL DEFAULT '0',
  `enrollments` int(11) NOT NULL DEFAULT '0',
  `revenue_cents` bigint(20) NOT NULL DEFAULT '0'

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- CREATE TABLE for t_message_log
CREATE TABLE `t_message_log` (

  `msg_id` bigint(20) NOT NULL,
  `school_id` bigint(20) UNSIGNED NOT NULL,
  `channel` enum('email','sms','whatsapp') NOT NULL,
  `to_parent_id` bigint(20) DEFAULT NULL,
  `to_student_id` bigint(20) DEFAULT NULL,
  `template_id` bigint(20) DEFAULT NULL,
  `rendered_body` mediumtext NOT NULL,
  `status` enum('queued','sent','failed','bounced','delivered','opened','clicked') NOT NULL DEFAULT 'queued',
  `provider_id` varchar(128) DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `opened_at` timestamp NULL DEFAULT NULL,
  `clicked_at` timestamp NULL DEFAULT NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- CREATE TABLE for t_message_template
CREATE TABLE `t_message_template` (

  `template_id` bigint(20) NOT NULL,
  `school_id` bigint(20) UNSIGNED NOT NULL,
  `channel` enum('email','sms','whatsapp') NOT NULL,
  `purpose` varchar(64) NOT NULL,
  `subject` varchar(160) DEFAULT NULL,
  `body` mediumtext NOT NULL,
  `locale` varchar(8) NOT NULL DEFAULT 'en',
  `version` int(11) NOT NULL DEFAULT '1'

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- CREATE TABLE for t_revenue_daily
CREATE TABLE `t_revenue_daily` (

  `school_id` bigint(20) UNSIGNED NOT NULL,
  `day` date NOT NULL,
  `mrr_cents` bigint(20) NOT NULL DEFAULT '0',
  `arr_cents` bigint(20) NOT NULL DEFAULT '0',
  `on_time_pay_pct` decimal(5,2) NOT NULL DEFAULT '0.00',
  `ar_overdue_cents` bigint(20) NOT NULL DEFAULT '0'

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- CREATE TABLE for t_teacher_availability
CREATE TABLE `t_teacher_availability` (

  `id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` int(10) UNSIGNED NOT NULL,
  `school_id` int(10) UNSIGNED NOT NULL,
  `date` date DEFAULT NULL,
  `is_recurring` tinyint(1) NOT NULL DEFAULT '0',
  `day_of_week` enum('MON','TUE','WED','THU','FRI','SAT','SUN') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_time_local` time DEFAULT NULL,
  `end_time_local` time DEFAULT NULL,
  `availability_date` date DEFAULT NULL,
  `start_time_utc` datetime NOT NULL,
  `end_time_utc` datetime NOT NULL,
  `duration_minutes` int(11) DEFAULT NULL,
  `timezone` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `recurrence_end` date DEFAULT NULL,
  `spans_midnight` tinyint(1) NOT NULL DEFAULT '0',
  `notes` varchar(512) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `updated_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `normalized_day_of_week` varchar(3) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (ifnull(`day_of_week`,'')) STORED,
  `normalized_availability_date` date GENERATED ALWAYS AS (ifnull(`availability_date`,cast(`start_time_utc` as date))) STORED

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;