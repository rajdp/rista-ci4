
CREATE TABLE `academic_calendar` (
  `id` bigint NOT NULL,
  `school_id` bigint NOT NULL,
  `academic_year` varchar(250) NOT NULL,
  `academic_month` int NOT NULL,
  `academic_week` int NOT NULL,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `status` int NOT NULL DEFAULT '1' COMMENT '1->active, 2->inactive',
  `created_by` bigint NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint NOT NULL,
  `modified_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `address_master`
--

CREATE TABLE `address_master` (
  `address_type_id` int NOT NULL,
  `address` varchar(100) NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `admin_mail_notification`
--

CREATE TABLE `admin_mail_notification` (
  `id` int NOT NULL,
  `student_id` bigint NOT NULL,
  `class_id` bigint NOT NULL,
  `school_id` bigint NOT NULL,
  `grade_id` bigint NOT NULL,
  `content_id` text NOT NULL,
  `status` int NOT NULL DEFAULT '0' COMMENT '0-> Not Sent, 1->Sent',
  `created_by` bigint NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint NOT NULL,
  `modified_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `admin_settings`
--

CREATE TABLE `admin_settings` (
  `id` bigint NOT NULL,
  `name` varchar(250) NOT NULL,
  `description` text NOT NULL,
  `value` text NOT NULL,
  `settings` int NOT NULL DEFAULT '0',
  `status` int NOT NULL COMMENT '2->testweb, 3->testadmin, 4->testuatweb, 5->testuatadmin, 6->liveuatweb, 7->liveuatadmin, 8->liveweb, 9-> liveadmin, 10->demoweb, 11->demoadmin, 12-> livetestweb, 13-> livetestadmin',
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `admin_settings_school`
--

CREATE TABLE `admin_settings_school` (
  `id` bigint NOT NULL,
  `name` varchar(250) NOT NULL,
  `description` text NOT NULL,
  `value` text NOT NULL,
  `school_id` bigint NOT NULL,
  `settings` int NOT NULL DEFAULT '0',
  `status` int NOT NULL,
  `modified_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `answers`
--

CREATE TABLE `answers` (
  `answer_id` bigint NOT NULL,
  `question_no` varchar(50) NOT NULL,
  `section_heading` varchar(200) DEFAULT NULL,
  `display_order` int NOT NULL DEFAULT '0',
  `section_id` int NOT NULL DEFAULT '0',
  `question` longtext,
  `answer_instructions` longtext,
  `content_id` bigint NOT NULL,
  `question_type_id` bigint NOT NULL,
  `has_sub_question` int NOT NULL,
  `sub_question_no` varchar(10) DEFAULT NULL,
  `options` text,
  `array` text,
  `mob_options` longtext NOT NULL,
  `old_answer` longtext CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `answer` longtext CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `answer_explanation` longtext,
  `editor_answer` longtext,
  `auto_grade` int NOT NULL DEFAULT '0',
  `points` int NOT NULL DEFAULT '0',
  `difficulty` int NOT NULL,
  `allow_exact_match` int DEFAULT NULL,
  `allow_any_text` int DEFAULT NULL,
  `match_case` int DEFAULT NULL,
  `minimum_line` int DEFAULT NULL,
  `status` int NOT NULL DEFAULT '1' COMMENT '0->inactive, 1->active',
  `page_no` int NOT NULL,
  `created_by` bigint NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint NOT NULL,
  `modified_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `batch`
--

CREATE TABLE `batch` (
  `batch_id` bigint NOT NULL,
  `batch_name` varchar(200) NOT NULL,
  `school_id` bigint NOT NULL,
  `corporate_id` int NOT NULL,
  `status` bigint NOT NULL COMMENT '1-Active,2-In Active,3->Suspended, 4->Deleted',
  `batch_type` bigint NOT NULL,
  `edquill_batch_id` int NOT NULL,
  `parent_batch_id` int NOT NULL DEFAULT '0',
  `created_by` bigint NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint NOT NULL,
  `modified_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `blogger`
--

CREATE TABLE `blogger` (
  `blog_id` bigint NOT NULL,
  `name` varchar(500) NOT NULL,
  `name_slug` text NOT NULL,
  `short_description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `long_description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `author` varchar(250) NOT NULL,
  `image` text NOT NULL,
  `status` int NOT NULL COMMENT '1->active,2->inactive',
  `display_type` int NOT NULL COMMENT '1 -> general, 2 -> learing center, 3 -> tutors, 4 -> publishers',
  `views` int NOT NULL DEFAULT '0',
  `display_from` datetime NOT NULL,
  `display_until` datetime NOT NULL,
  `created_by` bigint NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint NOT NULL,
  `modified_date` date NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `book`
--

CREATE TABLE `book` (
  `book_id` bigint NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` text NOT NULL,
  `school_id` bigint NOT NULL DEFAULT '0',
  `description` text NOT NULL,
  `status` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `career`
--

CREATE TABLE `career` (
  `id` bigint NOT NULL,
  `title` varchar(200) NOT NULL,
  `department` varchar(200) NOT NULL,
  `address1` text NOT NULL,
  `address2` text NOT NULL,
  `description` longtext NOT NULL,
  `basic_qualification` longtext NOT NULL,
  `prefered_qualification` longtext NOT NULL,
  `status` int NOT NULL,
  `created_by` bigint NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint NOT NULL,
  `modified_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `career_application`
--

CREATE TABLE `career_application` (
  `id` bigint NOT NULL,
  `job_id` bigint NOT NULL,
  `name` varchar(200) NOT NULL,
  `email` varchar(200) NOT NULL,
  `resume_url` varchar(200) NOT NULL,
  `portfolio` varchar(200) NOT NULL,
  `status` int NOT NULL,
  `created_by` bigint NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint NOT NULL,
  `modified_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `cfs_reports`
--

CREATE TABLE `cfs_reports` (
  `id` int NOT NULL,
  `class_id` bigint NOT NULL,
  `content_id` bigint NOT NULL,
  `student_id` bigint NOT NULL,
  `student_content_id` bigint NOT NULL,
  `question_id` int NOT NULL,
  `question_no` int NOT NULL,
  `is_correct` varchar(100) NOT NULL,
  `time_taken` int NOT NULL,
  `predicted_time` varchar(50) DEFAULT NULL,
  `subject_id` bigint DEFAULT NULL,
  `question_topic_id` bigint DEFAULT NULL,
  `question_sub_topic_id` bigint DEFAULT NULL,
  `question_standard_id` bigint DEFAULT NULL,
  `skill` varchar(1000) DEFAULT NULL,
  `assigned_date` datetime NOT NULL,
  `answered_date` datetime NOT NULL,
  `status` tinyint(1) NOT NULL COMMENT '0->Inactive,1->Active',
  `module_id` int DEFAULT NULL,
  `created_by` int NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` int DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `class`
--

CREATE TABLE `class` (
  `class_id` bigint NOT NULL,
  `teacher_id` bigint NOT NULL,
  `school_id` bigint NOT NULL,
  `class_name` varchar(1000) NOT NULL,
  `subject` varchar(1000) NOT NULL,
  `tags` text NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `grade` varchar(1000) NOT NULL,
  `batch_id` text,
  `meeting_link` text,
  `meeting_id` text,
  `passcode` varchar(200) DEFAULT NULL,
  `telephone_number` varchar(200) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `class_code` varchar(20) NOT NULL,
  `time_zone_id` int NOT NULL DEFAULT '0',
  `status` int NOT NULL COMMENT '1-Active,2-Inactive,3-remove',
  `class_status` int NOT NULL DEFAULT '0' COMMENT '0->active 1->save',
  `class_type` int NOT NULL COMMENT '1-> Online, 2-> In person',
  `announcement_type` tinyint(1) NOT NULL DEFAULT '2' COMMENT '1 -> do not allow,2-> allow only,3-> allow announcement and comments',
  `video_link` longtext,
  `profile_url` text,
  `profile_thumb_url` text,
  `notes` longtext,
  `edquill_schedule_id` int NOT NULL,
  `edquill_classroom_id` bigint NOT NULL DEFAULT '0',
  `academy_schedule_id` int DEFAULT NULL,
  `academy_course_id` int DEFAULT NULL,
  `course_id` int NOT NULL DEFAULT '0',
  `registration_start_date` date DEFAULT NULL,
  `registration_end_date` date DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT NULL,
  `actual_cost` decimal(10,2) DEFAULT NULL,
  `total_slots` int DEFAULT NULL,
  `slots_booked` int NOT NULL DEFAULT '0',
  `location_id` varchar(100) DEFAULT NULL,
  `payment_type` char(1) DEFAULT NULL COMMENT 'O-onetime,R-recurring',
  `payment_sub_type` char(1) DEFAULT NULL COMMENT 'W->weekly,M->monthly,Q->quarterly,H->half-yearly,A->annually',
  `created_date` datetime NOT NULL,
  `created_by` bigint NOT NULL,
  `modified_date` datetime NOT NULL,
  `modified_by` bigint NOT NULL DEFAULT '0',
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `classroom_content`
--

CREATE TABLE `classroom_content` (
  `id` int NOT NULL,
  `batch_id` bigint NOT NULL,
  `school_id` bigint NOT NULL,
  `content_id` bigint NOT NULL,
  `status` int NOT NULL COMMENT '1-> Added, 2-> Deleted',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `auto_review` int NOT NULL COMMENT '0-> manually, 1-> after completing test , 2-> after completing each question',
  `created_by` bigint NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint NOT NULL,
  `modified_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `class_attendance`
--

CREATE TABLE `class_attendance` (
  `id` int NOT NULL,
  `start_time` varchar(20) NOT NULL,
  `end_time` varchar(20) NOT NULL,
  `slot_day` int NOT NULL,
  `schedule_id` bigint NOT NULL,
  `class_id` bigint NOT NULL,
  `student_id` bigint NOT NULL,
  `attendance` int DEFAULT NULL COMMENT '0 - Absent, 1 - Present',
  `date` date NOT NULL,
  `request_json` text NOT NULL,
  `created_date` datetime NOT NULL,
  `created_by` bigint NOT NULL,
  `modified_date` datetime NOT NULL,
  `modified_by` bigint NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `class_content`
--

CREATE TABLE `class_content` (
  `id` bigint NOT NULL,
  `class_id` bigint DEFAULT NULL,
  `content_id` bigint NOT NULL DEFAULT '0',
  `school_id` bigint DEFAULT NULL,
  `status` int NOT NULL DEFAULT '1' COMMENT '1-> active, 2-> inactive',
  `all_student` int NOT NULL DEFAULT '1' COMMENT '1->all_student,0->specific_students',
  `release_score` int NOT NULL DEFAULT '0' COMMENT '0->No_Release, 1->Release',
  `auto_review` int NOT NULL DEFAULT '0' COMMENT '0-> manually, 1-> after completing test , 2-> after completing each question',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `notes` text,
  `downloadable` int NOT NULL DEFAULT '0' COMMENT '0-> Not downloadable, 2-> downloadable',
  `topic_id` int NOT NULL DEFAULT '0',
  `is_accessible` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0 -> not accessible,1 -> accessible after end date',
  `allow_feedback` tinyint(1) NOT NULL DEFAULT '0',
  `allow_workspace` tinyint(1) NOT NULL DEFAULT '0',
  `show_timer` tinyint(1) NOT NULL DEFAULT '0',
  `created_by` bigint NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint NOT NULL,
  `modified_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `class_content_log`
--

CREATE TABLE `class_content_log` (
  `id` bigint NOT NULL,
  `class_id` bigint DEFAULT NULL,
  `content_id` bigint NOT NULL DEFAULT '0',
  `school_id` bigint NOT NULL,
  `status` int NOT NULL DEFAULT '1' COMMENT '1-> active, 2-> inactive',
  `all_student` int NOT NULL DEFAULT '1' COMMENT '1->all_student,0->specific_students',
  `release_score` int NOT NULL DEFAULT '0' COMMENT '0->No_Release, 1->Release',
  `auto_review` int NOT NULL DEFAULT '0' COMMENT '0-> manually, 1-> after completing test , 2-> after completing each question',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `notes` text,
  `downloadable` int NOT NULL DEFAULT '0' COMMENT '0-> Not downloadable, 2-> downloadable',
  `topic_id` int NOT NULL,
  `is_accessible` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0 -> not accessible,1 -> accessible after end date',
  `allow_feedback` tinyint(1) NOT NULL DEFAULT '0',
  `allow_workspace` tinyint(1) NOT NULL DEFAULT '0',
  `show_timer` tinyint(1) NOT NULL DEFAULT '0',
  `created_by` bigint NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint NOT NULL,
  `modified_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `class_dec_mig`
--

CREATE TABLE `class_dec_mig` (
  `class_id` bigint NOT NULL,
  `teacher_id` bigint NOT NULL,
  `school_id` bigint NOT NULL,
  `class_name` text NOT NULL,
  `subject` text NOT NULL,
  `tags` text NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `grade` text NOT NULL,
  `batch_id` text,
  `meeting_link` text,
  `meeting_id` text,
  `passcode` varchar(200) DEFAULT NULL,
  `telephone_number` varchar(200) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `class_code` varchar(20) NOT NULL,
  `status` int NOT NULL COMMENT '1-Active,2-Inactive,3-remove',
  `class_status` int NOT NULL DEFAULT '0' COMMENT '0->active 1->save',
  `class_type` int NOT NULL COMMENT '1-> Online, 2-> In person',
  `video_link` longtext,
  `profile_url` text,
  `profile_thumb_url` text,
  `notes` longtext,
  `edquill_schedule_id` int NOT NULL,
  `edquill_classroom_id` bigint NOT NULL DEFAULT '0',
  `created_date` datetime NOT NULL,
  `created_by` bigint NOT NULL,
  `modified_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` bigint NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `class_mail_notification`
--

CREATE TABLE `class_mail_notification` (
  `id` bigint NOT NULL,
  `class_id` bigint NOT NULL,
  `email_id` longtext NOT NULL,
  `mail_sent` int NOT NULL DEFAULT '0' COMMENT '0->mail not sent,1->mail sent',
  `provider_id` varchar(200) DEFAULT NULL,
  `googleid_token` text,
  `is_makeup` tinyint(1) NOT NULL DEFAULT '0',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_by` bigint NOT NULL DEFAULT '0',
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `class_notes`
--

CREATE TABLE `class_notes` (
  `id` bigint NOT NULL,
  `class_id` bigint NOT NULL,
  `note` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `add_date` datetime NOT NULL,
  `status` int NOT NULL DEFAULT '1' COMMENT '1->active, 2->deleted',
  `created_by` bigint NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint NOT NULL,
  `modified_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `class_schedule`
--

CREATE TABLE `class_schedule` (
  `id` bigint NOT NULL,
  `class_id` bigint NOT NULL,
  `teacher_id` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `start_time` varchar(20) NOT NULL,
  `end_time` varchar(20) NOT NULL,
  `school_id` bigint NOT NULL,
  `slot_days` int NOT NULL,
  `slotselected` tinyint(1) NOT NULL DEFAULT '1',
  `meeting_link` text CHARACTER SET latin1 COLLATE latin1_swedish_ci,
  `meeting_id` text CHARACTER SET latin1 COLLATE latin1_swedish_ci,
  `teacher_link` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `student_link` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `zoom_response` longtext CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `passcode` varchar(200) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `telephone_number` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `created_by` bigint NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` int NOT NULL,
  `modified_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `content`
--

CREATE TABLE `content` (
  `content_id` bigint NOT NULL,
  `name` varchar(200) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `description` text,
  `grade` varchar(200) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `school_id` bigint NOT NULL,
  `corporate_id` bigint NOT NULL DEFAULT '0',
  `testcode_id` int NOT NULL DEFAULT '0',
  `score_path` varchar(250) DEFAULT NULL,
  `file_path` longtext,
  `base64_data` longtext NOT NULL,
  `links` longtext,
  `file_text` longtext,
  `answerkey_path` text,
  `teacher_version` longtext,
  `annotation` longtext NOT NULL,
  `questionAnnotation` longtext NOT NULL,
  `content_type` int NOT NULL COMMENT '1->Resources,2->Assignment,3->Assessment',
  `editor_type` int NOT NULL DEFAULT '1' COMMENT '1-> KeyBoard, 2-> Text, 3-> Math, 4->Diagram',
  `tags` varchar(200) DEFAULT NULL,
  `content_format` int NOT NULL COMMENT '1-> pdf 2-> links 3-> text 4-> HW',
  `total_questions` int NOT NULL DEFAULT '0',
  `access` int NOT NULL COMMENT '1->private(within school),2->private(within user),3->public, 4->private(within corporate)',
  `status` bigint NOT NULL COMMENT '1->Active,2->Inactive,3->Suspended,4->Deleted,5->Draft',
  `profile_url` text,
  `profile_thumb_url` text,
  `publication_code` varchar(20) DEFAULT '1' COMMENT '1->Not Book',
  `download` int NOT NULL DEFAULT '0',
  `allow_answer_key` int DEFAULT '0' COMMENT '0->not allow, 1->allow',
  `content_duration` int NOT NULL DEFAULT '0',
  `is_test` tinyint(1) NOT NULL DEFAULT '0',
  `test_type_id` int NOT NULL DEFAULT '1',
  `created_by` bigint NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint NOT NULL,
  `modified_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `content_copy_22`
--

CREATE TABLE `content_copy_22` (
  `content_id` bigint NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text,
  `grade` varchar(200) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `school_id` bigint NOT NULL,
  `corporate_id` bigint NOT NULL DEFAULT '0',
  `testcode_id` int NOT NULL DEFAULT '0',
  `score_path` varchar(250) DEFAULT NULL,
  `file_path` longtext,
  `base64_data` longtext NOT NULL,
  `links` longtext,
  `file_text` longtext,
  `answerkey_path` text,
  `teacher_version` longtext,
  `annotation` longtext NOT NULL,
  `questionAnnotation` longtext NOT NULL,
  `content_type` int NOT NULL COMMENT '1->Resources,2->Assignment,3->Assessment',
  `editor_type` int NOT NULL DEFAULT '1' COMMENT '1-> KeyBoard, 2-> Text, 3-> Math, 4->Diagram',
  `tags` varchar(200) DEFAULT NULL,
  `content_format` int NOT NULL COMMENT '1-> pdf 2-> links 3-> text 4-> HW',
  `total_questions` int NOT NULL DEFAULT '0',
  `access` int NOT NULL COMMENT '1->private(within school),2->private(within user),3->public, 4->private(within corporate)',
  `status` bigint NOT NULL COMMENT '1->Active,2->Inactive,3->Suspended,4->Deleted,5->Draft',
  `profile_url` text,
  `profile_thumb_url` text,
  `publication_code` varchar(20) DEFAULT '1' COMMENT '1->Not Book',
  `download` int NOT NULL DEFAULT '0',
  `allow_answer_key` int DEFAULT '0' COMMENT '0->not allow, 1->allow',
  `created_by` bigint NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint NOT NULL,
  `modified_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `content_master`
--

CREATE TABLE `content_master` (
  `content_id` int NOT NULL,
  `content_type` varchar(100) NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `content_test_detail`
--

CREATE TABLE `content_test_detail` (
  `content_detail_id` int NOT NULL,
  `test_id` int NOT NULL,
  `content_id` int NOT NULL,
  `module_name` varchar(250) DEFAULT NULL,
  `solving_time` varchar(50) DEFAULT NULL,
  `interval_time` int DEFAULT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `status` tinyint(1) NOT NULL COMMENT '0->Inactive,1->Active',
  `created_by` int NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` int DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `content_test_detail_log`
--

CREATE TABLE `content_test_detail_log` (
  `content_detail_id` int NOT NULL,
  `test_id` int NOT NULL,
  `content_id` int NOT NULL,
  `module_name` varchar(250) DEFAULT NULL,
  `solving_time` varchar(50) DEFAULT NULL,
  `interval_time` int DEFAULT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `status` tinyint(1) NOT NULL COMMENT '0->Inactive,1->Active',
  `created_by` int NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` int DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `log_sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `corporate`
--

CREATE TABLE `corporate` (
  `corporate_id` bigint NOT NULL,
  `corporate_name` varchar(50) NOT NULL,
  `corporate_code` varchar(200) DEFAULT NULL,
  `status` int NOT NULL COMMENT '1 - active 2 - inactive 3->suspended 4-> delete',
  `created_by` bigint NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint NOT NULL,
  `modified_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `corporate_request`
--

CREATE TABLE `corporate_request` (
  `request_id` bigint NOT NULL,
  `corporate_id` bigint NOT NULL,
  `school_id` bigint NOT NULL,
  `status` int NOT NULL DEFAULT '2' COMMENT '1->approved, 2->pending, 3->rejected',
  `validity` date DEFAULT NULL,
  `created_by` bigint NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint NOT NULL,
  `modified_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `country`
--

CREATE TABLE `country` (
  `id` int NOT NULL,
  `code` varchar(3) NOT NULL,
  `name` varchar(150) NOT NULL,
  `dial_code` int NOT NULL,
  `currency_name` varchar(20) NOT NULL,
  `currency_symbol` varchar(255) NOT NULL,
  `currency_code` varchar(20) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `date_format`
--

CREATE TABLE `date_format` (
  `date_id` bigint NOT NULL,
  `date_format` longtext NOT NULL,
  `display_name` varchar(200) DEFAULT NULL,
  `example` varchar(200) DEFAULT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `essay_rubric`
--

CREATE TABLE `essay_rubric` (
  `rubricID` int NOT NULL,
  `studentGrade` varchar(10) NOT NULL,
  `essayCriteria` varchar(1000) NOT NULL,
  `maxScore` int NOT NULL,
  `Status` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grade`
--

CREATE TABLE `grade` (
  `grade_id` bigint NOT NULL,
  `grade_name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `school_id` bigint NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL,
  `sorting_no` int NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `graph_answers`
--

CREATE TABLE `graph_answers` (
  `id` int NOT NULL,
  `answer_id` bigint NOT NULL,
  `question_no` bigint NOT NULL,
  `content_id` bigint NOT NULL,
  `class_id` bigint NOT NULL,
  `student_id` bigint NOT NULL,
  `correct_answer` longtext NOT NULL,
  `student_answer` longtext NOT NULL,
  `options` longtext,
  `actual_points` int NOT NULL,
  `earned_points` int DEFAULT '0',
  `answer_status` int NOT NULL COMMENT '0->yet to start,1->incorrect,2->correct,3->partially correct,4->skipped,5-> Pending Verification',
  `auto_grade` int NOT NULL DEFAULT '0',
  `feedback` text NOT NULL,
  `annotation` longtext,
  `created_by` bigint NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint NOT NULL,
  `modified_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `holiday_calendar`
--

CREATE TABLE `holiday_calendar` (
  `id` bigint NOT NULL,
  `school_id` bigint NOT NULL,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `festival_name` text NOT NULL,
  `created_by` bigint NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint NOT NULL,
  `modified_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `institution_announcement`
--

CREATE TABLE `institution_announcement` (
  `id` int NOT NULL,
  `school_id` int NOT NULL,
  `title` varchar(1000) NOT NULL,
  `description` text,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `status` tinyint(1) NOT NULL COMMENT '1-Active,2-Inactive',
  `created_by` int NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` int DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invite_users`
--

CREATE TABLE `invite_users` (
  `id` bigint NOT NULL,
  `user_id` bigint NOT NULL,
  `path` longtext NOT NULL,
  `format` enum('Excel','Email') NOT NULL DEFAULT 'Excel',
  `user_type` varchar(100) NOT NULL,
  `status` int NOT NULL DEFAULT '0',
  `created_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `mailbox`
--

CREATE TABLE `mailbox` (
  `message_id` bigint NOT NULL,
  `parent_message_id` bigint DEFAULT NULL,
  `class_id` bigint NOT NULL,
  `from_id` bigint NOT NULL,
  `to_id` varchar(1000) NOT NULL,
  `body` longtext NOT NULL,
  `status` int NOT NULL DEFAULT '0' COMMENT '1->draft',
  `created_by` bigint NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` bigint DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mailbox_attachment`
--

CREATE TABLE `mailbox_attachment` (
  `attachment_id` bigint NOT NULL,
  `message_id` bigint NOT NULL,
  `attachment` longtext NOT NULL,
  `type` char(1) NOT NULL COMMENT '1-> Image, 2-> Link, 3->document',
  `created_by` bigint NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mailbox_details`
--

CREATE TABLE `mailbox_details` (
  `message_detail_id` bigint NOT NULL,
  `message_id` bigint NOT NULL,
  `user_id` bigint NOT NULL,
  `is_read` int NOT NULL DEFAULT '0',
  `mail_sent` int NOT NULL DEFAULT '0' COMMENT '0->mail not sent,1->mail sent',
  `created_by` bigint NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `note_comments`
--

CREATE TABLE `note_comments` (
  `id` bigint NOT NULL,
  `note_id` bigint NOT NULL,
  `comment` text,
  `comment_date` datetime DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1->active, 2->deleted',
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_by` bigint NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notify_parents_requests`
--

CREATE TABLE `notify_parents_requests` (
  `id` bigint NOT NULL,
  `school_id` bigint NOT NULL,
  `student_id` bigint NOT NULL,
  `class_id` bigint NOT NULL,
  `content_id` bigint NOT NULL,
  `student_content_id` bigint NOT NULL,
  `status` int NOT NULL DEFAULT '0' COMMENT '0->mail_not_sent, 1->mail_sent',
  `created_by` bigint NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `page_master`
--

CREATE TABLE `page_master` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `status` tinyint(1) NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `passage`
--

CREATE TABLE `passage` (
  `passage_id` bigint NOT NULL,
  `title` text,
  `passage` longtext,
  `status` int NOT NULL DEFAULT '1' COMMENT '1->Active,2->inactive',
  `created_by` int NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permission`
--

CREATE TABLE `permission` (
  `permission_id` int NOT NULL,
  `controller` varchar(1000) NOT NULL,
  `status` int NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `question_skill`
--

CREATE TABLE `question_skill` (
  `id` bigint NOT NULL,
  `skill` varchar(500) NOT NULL,
  `status` int NOT NULL DEFAULT '1' COMMENT '1-> active, 2-> inactive',
  `created_by` int NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `question_standard`
--

CREATE TABLE `question_standard` (
  `id` bigint NOT NULL,
  `question_standard` varchar(500) NOT NULL,
  `status` int NOT NULL DEFAULT '1' COMMENT '1-> active, 2-> inactive',
  `created_by` int NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `question_topic`
--

CREATE TABLE `question_topic` (
  `question_topic_id` bigint NOT NULL,
  `question_topic` varchar(200) NOT NULL,
  `subject_id` int NOT NULL,
  `status` int NOT NULL DEFAULT '1' COMMENT '1-> active, 2-> inactive',
  `created_by` int NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `question_types`
--

CREATE TABLE `question_types` (
  `question_type_id` bigint NOT NULL,
  `resource_type_id` bigint NOT NULL,
  `question_type` varchar(200) NOT NULL,
  `image_path` text NOT NULL,
  `question_uploads` int NOT NULL DEFAULT '0',
  `icon_path` text,
  `status` int NOT NULL COMMENT '0->Inactive,1->Active',
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `resource_type_master`
--

CREATE TABLE `resource_type_master` (
  `resource_type_id` bigint NOT NULL,
  `resource_type` varchar(200) NOT NULL,
  `status` int NOT NULL COMMENT '0->Inactive,1->Active',
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `role_master`
--

CREATE TABLE `role_master` (
  `role_id` int NOT NULL,
  `role` varchar(100) NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `role_permission`
--

CREATE TABLE `role_permission` (
  `id` int NOT NULL,
  `role_id` int NOT NULL,
  `permission_id` int NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `school`
--

CREATE TABLE `school` (
  `school_id` bigint NOT NULL,
  `name` varchar(1000) NOT NULL,
  `tax_id` varchar(100) DEFAULT NULL,
  `address1` text NOT NULL,
  `address2` text NOT NULL,
  `city` varchar(100) NOT NULL,
  `state` int NOT NULL,
  `country` int NOT NULL,
  `postal_code` varchar(20) NOT NULL,
  `has_branch` tinyint(1) NOT NULL,
  `branch_name` text NOT NULL,
  `school_website` longtext,
  `domain_name` varchar(300) DEFAULT NULL,
  `email_id` varchar(150) DEFAULT NULL,
  `profile_url` text,
  `profile_thumb_url` text,
  `status` tinyint(1) NOT NULL COMMENT '1->active, 2->Inactive, 3->payment pending',
  `institution_type` int DEFAULT '1' COMMENT '1-> Public School, 2-> Coaching Center, 3-> Private School, 4->Learning Center, 5-> Tutoring',
  `trial` int NOT NULL DEFAULT '0',
  `validity` date NOT NULL,
  `payment_status` char(1) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT 'Y',
  `display_until` date DEFAULT NULL,
  `created_by` bigint NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint NOT NULL,
  `modified_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `sms_templates`
--

CREATE TABLE `sms_templates` (
  `id` bigint NOT NULL,
  `template_name` varchar(500) NOT NULL,
  `subject` text NOT NULL,
  `template` text NOT NULL,
  `template_type` varchar(10) NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `state`
--

CREATE TABLE `state` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `country_id` int NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `static_website`
--

CREATE TABLE `static_website` (
  `id` bigint NOT NULL,
  `name` varchar(50) NOT NULL,
  `email_id` varchar(50) NOT NULL,
  `mobile` varchar(50) NOT NULL,
  `school_name` varchar(50) NOT NULL,
  `state` varchar(50) NOT NULL,
  `city` varchar(50) NOT NULL,
  `requirement_message` longtext NOT NULL,
  `type` int NOT NULL COMMENT '1-> contact us, 2-> demo',
  `status` int NOT NULL COMMENT '1->mail sent, 2->mail_not _sent',
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `static_website_email_subscription`
--

CREATE TABLE `static_website_email_subscription` (
  `id` bigint NOT NULL,
  `email_id` varchar(250) NOT NULL,
  `type` int NOT NULL COMMENT '1->palssouthplainfield , 2->palsnortherns, 3->palsmarlboro.com, 4->palseastbrunswick, 5->palsmonroe.com, 6->palsoldbridge, 7->palsfreehold, 8->palspiscataway,9->edquill.com',
  `status` int NOT NULL COMMENT '1->subscribed, 2->unsubscribed',
  `mail` int NOT NULL COMMENT '1->sent,2->not_sent',
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `student_answerkey_request`
--

CREATE TABLE `student_answerkey_request` (
  `id` bigint NOT NULL,
  `student_id` bigint NOT NULL,
  `content_id` bigint NOT NULL,
  `class_id` bigint NOT NULL,
  `status` bigint NOT NULL DEFAULT '0' COMMENT '0->Default, 1->Requested, 2->Rejected, 3->Accepted',
  `created_by` bigint NOT NULL,
  `created_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `student_answers`
--

CREATE TABLE `student_answers` (
  `id` int NOT NULL,
  `answer_id` bigint NOT NULL,
  `question_no` bigint NOT NULL,
  `content_id` bigint NOT NULL,
  `class_id` bigint NOT NULL,
  `student_id` bigint NOT NULL,
  `class_content_id` bigint NOT NULL,
  `student_content_id` bigint NOT NULL,
  `correct_answer` longtext NOT NULL,
  `student_answer` longtext NOT NULL,
  `editor_answer` longtext,
  `student_answer_image` text,
  `options` longtext,
  `optionsCopy` longtext,
  `actual_points` int NOT NULL,
  `earned_points` int DEFAULT '0',
  `answer_status` int NOT NULL COMMENT '0->yet to start,1->incorrect,2->correct,3->partially correct,4->skipped,5-> Pending Verification',
  `answer_attended` int NOT NULL DEFAULT '0' COMMENT '0-> yet to start, 1-> answer, 2-> answered',
  `correction_status` int NOT NULL DEFAULT '0' COMMENT '0 -> Not Corrected, 1 -> Corrected',
  `auto_grade` int NOT NULL DEFAULT '0',
  `feedback` text NOT NULL,
  `annotation` longtext,
  `jiixdata` longtext,
  `roughdata` longtext,
  `workarea` longtext,
  `student_roughdata` text,
  `rough_image_url` text,
  `rough_image_thumb_url` text,
  `is_correct` varchar(100) NOT NULL,
  `no_of_attempt` int NOT NULL DEFAULT '1',
  `time_taken` int NOT NULL,
  `marked_review` tinyint(1) DEFAULT NULL,
  `module_id` int DEFAULT NULL,
  `created_by` bigint NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint NOT NULL,
  `modified_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `student_answers_backup`
--

CREATE TABLE `student_answers_backup` (
  `id` int NOT NULL,
  `answer_id` bigint NOT NULL,
  `question_no` bigint NOT NULL,
  `content_id` bigint NOT NULL,
  `class_id` bigint NOT NULL,
  `student_id` bigint NOT NULL,
  `class_content_id` bigint NOT NULL,
  `student_content_id` bigint NOT NULL,
  `correct_answer` longtext NOT NULL,
  `student_answer` longtext NOT NULL,
  `editor_answer` longtext,
  `student_answer_image` text,
  `options` longtext,
  `optionsCopy` longtext,
  `actual_points` int NOT NULL,
  `earned_points` int DEFAULT '0',
  `answer_status` int NOT NULL COMMENT '0->yet to start,1->incorrect,2->correct,3->partially correct,4->skipped,5-> Pending Verification',
  `answer_attended` int NOT NULL DEFAULT '0' COMMENT '0-> yet to start, 1-> answer, 2-> answered',
  `correction_status` int NOT NULL DEFAULT '0' COMMENT '0 -> Not Corrected, 1 -> Corrected',
  `auto_grade` int NOT NULL DEFAULT '0',
  `feedback` text NOT NULL,
  `annotation` longtext,
  `jiixdata` longtext,
  `roughdata` longtext,
  `workarea` longtext,
  `student_roughdata` text,
  `rough_image_url` text,
  `rough_image_thumb_url` text,
  `is_correct` varchar(100) NOT NULL,
  `no_of_attempt` int NOT NULL DEFAULT '1',
  `time_taken` int NOT NULL,
  `created_by` bigint NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint NOT NULL,
  `modified_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `student_class`
--

CREATE TABLE `student_class` (
  `id` bigint NOT NULL,
  `class_id` bigint NOT NULL,
  `from_class` bigint DEFAULT '0',
  `student_id` bigint NOT NULL,
  `validity` date NOT NULL,
  `status` int NOT NULL COMMENT '0->Inactive, 1->Active, 2-> Saved,3->draft',
  `joining_date` date NOT NULL,
  `drafted_date` date NOT NULL,
  `notify_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 -> Send Notification, 0 -> Do not send',
  `class_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0->Normal,1->Transfer,2->Makeup',
  `created_by` bigint NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint NOT NULL,
  `modified_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `student_class_transfer`
--

CREATE TABLE `student_class_transfer` (
  `id` bigint NOT NULL,
  `class_id` bigint NOT NULL,
  `from_class` bigint DEFAULT '0',
  `to_class` bigint DEFAULT '0',
  `student_id` bigint NOT NULL,
  `validity` date NOT NULL,
  `status` int NOT NULL COMMENT '0->Inactive, 1->Active, 2-> Saved,3->draft',
  `joining_date` date NOT NULL,
  `drafted_date` date NOT NULL,
  `type` char(1) DEFAULT NULL COMMENT 'M->makeUpClass,T->TransferClass',
  `absent_date` date DEFAULT NULL,
  `created_by` bigint NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint NOT NULL,
  `modified_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `student_content`
--

CREATE TABLE `student_content` (
  `id` bigint NOT NULL,
  `class_id` bigint NOT NULL,
  `student_id` bigint NOT NULL,
  `content_id` bigint NOT NULL,
  `class_content_id` bigint NOT NULL,
  `grade_id` bigint NOT NULL,
  `laq_id` int NOT NULL COMMENT 'Last Answered Question Id',
  `status` int NOT NULL COMMENT '1->Yet_To_Start, 2->Inprogress, 3->Verified, 4-> Completed, 5-> Corrected,6->pending Verification',
  `draft_status` int NOT NULL DEFAULT '0' COMMENT '1->draft_content,2->undrafted',
  `release_score` int NOT NULL DEFAULT '0' COMMENT '0- No Release, 1-> Release',
  `parents_notify_count` int NOT NULL DEFAULT '0',
  `annotation` longtext,
  `teacher_annotation` longtext,
  `answer_sheet_annotation` longtext,
  `feedback` text,
  `student_feedback` text,
  `upload_answer` longtext,
  `points` int NOT NULL DEFAULT '0',
  `earned_points` int NOT NULL DEFAULT '0',
  `sat_score` int DEFAULT NULL,
  `rw_score` int DEFAULT NULL,
  `math_score` int DEFAULT NULL,
  `answer_request` bigint NOT NULL DEFAULT '0' COMMENT '0->Default, 1->Requested, 2->Rejected, 3->Accepted',
  `answer_completed_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `correction_completed_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `score_release_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `drafted_date` datetime NOT NULL,
  `redo_test` tinyint NOT NULL COMMENT 'redo_test_status -> 0 ,redo_test_status->1',
  `platform` int NOT NULL DEFAULT '0' COMMENT '1->web,2->ios,3->mixed',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `content_started_at` datetime DEFAULT NULL,
  `content_time_taken` int NOT NULL DEFAULT '0',
  `created_by` bigint NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint NOT NULL,
  `modified_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `student_content_feedback`
--

CREATE TABLE `student_content_feedback` (
  `id` bigint NOT NULL,
  `content_id` bigint NOT NULL,
  `student_id` bigint NOT NULL,
  `class_id` bigint NOT NULL,
  `school_id` bigint NOT NULL DEFAULT '0',
  `notes` longtext NOT NULL,
  `notes_type` int NOT NULL DEFAULT '1' COMMENT '1->notes, 2 -email',
  `created_by` bigint NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint NOT NULL,
  `modified_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `student_content_module`
--

CREATE TABLE `student_content_module` (
  `id` bigint NOT NULL,
  `student_content_id` bigint NOT NULL,
  `module_id` int NOT NULL,
  `laq_id` int NOT NULL COMMENT 'Last Answered Question Id',
  `status` int NOT NULL COMMENT '1->Yet_To_Start, 2->Inprogress, 3->Verified, 4-> Completed, 5-> Corrected,6->pending Verification',
  `draft_status` int NOT NULL DEFAULT '0' COMMENT '1->draft_content,2->undrafted',
  `release_score` int NOT NULL DEFAULT '0' COMMENT '0- No Release, 1-> Release',
  `parents_notify_count` int NOT NULL DEFAULT '0',
  `feedback` text,
  `student_feedback` text,
  `upload_answer` longtext,
  `points` int NOT NULL DEFAULT '0',
  `earned_points` int NOT NULL DEFAULT '0',
  `answer_request` bigint NOT NULL DEFAULT '0' COMMENT '0->Default, 1->Requested, 2->Rejected, 3->Accepted',
  `answer_completed_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `correction_completed_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `score_release_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `drafted_date` datetime NOT NULL,
  `redo_test` tinyint NOT NULL COMMENT 'redo_test_status -> 0 ,redo_test_status->1',
  `platform` int NOT NULL DEFAULT '0' COMMENT '1->web,2->ios,3->mixed',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `content_started_at` datetime DEFAULT NULL,
  `content_time_taken` int NOT NULL DEFAULT '0',
  `created_by` bigint NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint NOT NULL,
  `modified_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_essays`
--

CREATE TABLE `student_essays` (
  `student_essay_id` bigint NOT NULL,
  `student_content_id` bigint NOT NULL,
  `question_id` bigint NOT NULL,
  `question` varchar(10000) NOT NULL,
  `student_answer` mediumtext NOT NULL,
  `feedback` mediumtext,
  `essay_embedding` longtext NOT NULL,
  `student_score` int NOT NULL,
  `total_score` int NOT NULL,
  `feedback_received` datetime NOT NULL,
  `prompt_token` int DEFAULT NULL,
  `completion_token` int DEFAULT NULL,
  `total_token` int DEFAULT NULL,
  `total_cost` decimal(12,8) DEFAULT NULL,
  `time_taken` int NOT NULL,
  `status` int NOT NULL COMMENT '1->Active,0->InActive',
  `created_by` bigint NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint NOT NULL,
  `modified_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_overdue_notification`
--

CREATE TABLE `student_overdue_notification` (
  `id` int NOT NULL,
  `school_id` bigint NOT NULL,
  `class_id` bigint NOT NULL,
  `content_id` bigint NOT NULL,
  `student_id` bigint NOT NULL,
  `status` int NOT NULL,
  `mail_count` int NOT NULL,
  `created_date` datetime NOT NULL,
  `created_by` int NOT NULL,
  `modified_by` int NOT NULL,
  `modified_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `student_suggestions`
--

CREATE TABLE `student_suggestions` (
  `id` bigint NOT NULL,
  `student_id` bigint NOT NULL,
  `content_id` bigint NOT NULL,
  `class_id` bigint NOT NULL,
  `answer_id` bigint NOT NULL,
  `school_id` bigint NOT NULL,
  `suggestion_query` text NOT NULL,
  `created_by` bigint NOT NULL,
  `created_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `student_upgrade`
--

CREATE TABLE `student_upgrade` (
  `id` bigint NOT NULL,
  `school_id` bigint NOT NULL,
  `student_id` bigint NOT NULL,
  `grade_id` varchar(50) NOT NULL,
  `joining_date` date NOT NULL,
  `dropped_date` datetime NOT NULL,
  `status` int DEFAULT NULL,
  `created_by` bigint NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint NOT NULL,
  `modified_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `student_work`
--

CREATE TABLE `student_work` (
  `id` bigint NOT NULL,
  `student_content_id` bigint NOT NULL,
  `student_id` bigint NOT NULL,
  `student_name` varchar(250) NOT NULL,
  `content_id` bigint NOT NULL,
  `content_name` varchar(250) NOT NULL,
  `content_type` int NOT NULL COMMENT '1->resource, 2-> Assignment, 3-> Assessment',
  `class_id` bigint NOT NULL,
  `class_name` varchar(250) NOT NULL,
  `content_start_date` date NOT NULL,
  `content_end_date` date NOT NULL,
  `student_content_status` int NOT NULL COMMENT '1->Yet_To_Start, 2->Inprogress, 3->Verified, 4-> Completed, 5-> Corrected,6->pending Verification',
  `draft_status` int DEFAULT '0' COMMENT '1->draft_content,2->undrafted',
  `student_profile` text NOT NULL,
  `content_format` int NOT NULL,
  `total_score` int NOT NULL,
  `obtained_score` int NOT NULL,
  `answer_completed_date` datetime NOT NULL,
  `correction_completed_date` datetime NOT NULL,
  `score_release_date` datetime NOT NULL,
  `status` int NOT NULL DEFAULT '1' COMMENT '0->inactive, 1-> active',
  `score_released` int NOT NULL COMMENT '0- No Release, 1-> Release',
  `created_by` bigint NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` bigint NOT NULL,
  `modified_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `subject`
--

CREATE TABLE `subject` (
  `subject_id` bigint NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `school_id` bigint NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL,
  `edquill_subject_id` int NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `sub_topic`
--

CREATE TABLE `sub_topic` (
  `sub_topic_id` bigint NOT NULL,
  `question_topic_id` bigint NOT NULL,
  `sub_topic` varchar(200) DEFAULT NULL,
  `status` int NOT NULL DEFAULT '1' COMMENT '1-> active, 2-> inactive',
  `created_by` int NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tags`
--

CREATE TABLE `tags` (
  `id` bigint NOT NULL,
  `tag_name` varchar(200) NOT NULL,
  `school_id` bigint NOT NULL,
  `user_id` bigint NOT NULL,
  `content_id` bigint NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_career`
--

CREATE TABLE `tbl_career` (
  `id` int NOT NULL,
  `title` varchar(200) NOT NULL,
  `department` varchar(200) NOT NULL,
  `address1` text NOT NULL,
  `address2` text NOT NULL,
  `description` longtext NOT NULL,
  `basic_qualification` longtext NOT NULL,
  `prefered_qualification` longtext NOT NULL,
  `status` char(1) NOT NULL COMMENT 'A->active,I->inactive',
  `created_by` int NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_career_application`
--

CREATE TABLE `tbl_career_application` (
  `id` int NOT NULL,
  `job_id` int NOT NULL,
  `name` varchar(200) NOT NULL,
  `email` varchar(200) NOT NULL,
  `resume_url` varchar(200) NOT NULL,
  `portfolio` varchar(200) NOT NULL,
  `status` char(1) NOT NULL COMMENT 'A->active,I->inactive',
  `created_by` int NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_cart`
--

CREATE TABLE `tbl_cart` (
  `id` bigint NOT NULL,
  `user_id` bigint NOT NULL,
  `cart_data` text,
  `cart_type` char(1) NOT NULL COMMENT '''1'' -> cart, ''2''-> wishlist',
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_cart_details`
--

CREATE TABLE `tbl_cart_details` (
  `id` int NOT NULL,
  `registration_id` int NOT NULL,
  `order_id` int NOT NULL,
  `course_id` int NOT NULL,
  `schedule_id` int NOT NULL,
  `quantity` int NOT NULL,
  `cost` decimal(10,2) NOT NULL,
  `status` char(1) NOT NULL COMMENT 'A-> active, I ->inactive',
  `created_by` int NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_cart_details_log`
--

CREATE TABLE `tbl_cart_details_log` (
  `id` int NOT NULL,
  `registration_id` int NOT NULL,
  `order_id` int NOT NULL,
  `course_id` int NOT NULL,
  `schedule_id` int NOT NULL,
  `quantity` int NOT NULL,
  `cost` decimal(10,2) NOT NULL,
  `status` char(1) NOT NULL COMMENT 'A-> active, I ->inactive',
  `created_by` int NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_contact_us`
--

CREATE TABLE `tbl_contact_us` (
  `id` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `email_id` varchar(50) NOT NULL,
  `mobile` varchar(50) NOT NULL,
  `state` varchar(50) NOT NULL,
  `city` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `type` int NOT NULL COMMENT '1-> contact us, 2-> demo',
  `status` int NOT NULL COMMENT '1->mail sent, 2->mail_not_sent',
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_content`
--

CREATE TABLE `tbl_content` (
  `content_id` int NOT NULL,
  `name` varchar(500) NOT NULL,
  `name_slug` text NOT NULL,
  `entity_id` int NOT NULL,
  `category_id` varchar(200) NOT NULL,
  `subject_id` varchar(200) DEFAULT NULL,
  `short_description` longtext,
  `long_description` longtext,
  `author` varchar(250) DEFAULT NULL,
  `image` text,
  `status` char(1) NOT NULL COMMENT 'A-> Active, I -> Inactive',
  `views` int NOT NULL DEFAULT '0',
  `display_from` datetime NOT NULL,
  `display_until` datetime NOT NULL,
  `display_order` int NOT NULL,
  `redirect_url` varchar(250) DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `timing` varchar(200) DEFAULT NULL,
  `event_date` date DEFAULT NULL,
  `created_by` int NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_content_category`
--

CREATE TABLE `tbl_content_category` (
  `category_id` int NOT NULL,
  `category_name` varchar(200) NOT NULL,
  `description` varchar(1000) DEFAULT NULL,
  `entity_id` int NOT NULL,
  `status` char(1) NOT NULL COMMENT 'A-> Active, I -> Inactive',
  `path` varchar(100) DEFAULT NULL,
  `display_order` int NOT NULL,
  `created_by` int DEFAULT NULL,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_content_seo`
--

CREATE TABLE `tbl_content_seo` (
  `seo_id` int NOT NULL,
  `content_id` int NOT NULL,
  `entity_id` int NOT NULL,
  `meta_author` varchar(200) DEFAULT NULL,
  `meta_title` varchar(200) DEFAULT NULL,
  `meta_description` varchar(1000) DEFAULT NULL,
  `meta_keywords` varchar(3000) DEFAULT NULL,
  `meta_keyphrase` varchar(3000) DEFAULT NULL,
  `meta_topic` varchar(200) DEFAULT NULL,
  `meta_subject` varchar(200) DEFAULT NULL,
  `meta_classification` varchar(3000) DEFAULT NULL,
  `meta_robots` varchar(200) DEFAULT NULL,
  `meta_rating` varchar(200) DEFAULT NULL,
  `meta_audience` varchar(200) DEFAULT NULL,
  `og_title` varchar(200) DEFAULT NULL,
  `og_type` varchar(100) DEFAULT NULL,
  `og_site_name` varchar(500) DEFAULT NULL,
  `og_description` varchar(1000) DEFAULT NULL,
  `og_site_url` varchar(500) DEFAULT NULL,
  `twitter_title` varchar(200) DEFAULT NULL,
  `twitter_site` varchar(500) DEFAULT NULL,
  `twitter_card` varchar(500) DEFAULT NULL,
  `twitter_description` varchar(1000) DEFAULT NULL,
  `twitter_creator` varchar(200) DEFAULT NULL,
  `status` char(1) NOT NULL COMMENT 'A-> Active, I -> Inactive',
  `created_by` int NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_content_seo_log`
--

CREATE TABLE `tbl_content_seo_log` (
  `seo_id` int NOT NULL,
  `content_id` int NOT NULL,
  `entity_id` int NOT NULL,
  `meta_author` varchar(200) DEFAULT NULL,
  `meta_title` varchar(200) DEFAULT NULL,
  `meta_description` varchar(1000) DEFAULT NULL,
  `meta_keywords` varchar(3000) DEFAULT NULL,
  `meta_keyphrase` varchar(3000) DEFAULT NULL,
  `meta_topic` varchar(200) DEFAULT NULL,
  `meta_subject` varchar(200) DEFAULT NULL,
  `meta_classification` varchar(3000) DEFAULT NULL,
  `meta_robots` varchar(200) DEFAULT NULL,
  `meta_rating` varchar(200) DEFAULT NULL,
  `meta_audience` varchar(200) DEFAULT NULL,
  `og_title` varchar(200) DEFAULT NULL,
  `og_type` varchar(100) DEFAULT NULL,
  `og_site_name` varchar(500) DEFAULT NULL,
  `og_description` varchar(1000) DEFAULT NULL,
  `og_site_url` varchar(500) DEFAULT NULL,
  `twitter_title` varchar(200) DEFAULT NULL,
  `twitter_site` varchar(500) DEFAULT NULL,
  `twitter_card` varchar(500) DEFAULT NULL,
  `twitter_description` varchar(1000) DEFAULT NULL,
  `twitter_creator` varchar(200) DEFAULT NULL,
  `status` char(1) NOT NULL COMMENT 'A-> Active, I -> Inactive',
  `created_by` int NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_coupon`
--

CREATE TABLE `tbl_coupon` (
  `coupon_id` int NOT NULL,
  `entity_id` int NOT NULL,
  `coupon_code` varchar(20) NOT NULL,
  `validity_from` date NOT NULL,
  `validity_to` date NOT NULL,
  `discount_type` char(1) NOT NULL COMMENT 'P-percentage, A- Amount',
  `discount` varchar(20) NOT NULL,
  `course_based` char(1) NOT NULL COMMENT 'Y-yes,N->no',
  `course_id` varchar(200) DEFAULT NULL,
  `no_of_users` int NOT NULL,
  `status` char(1) NOT NULL COMMENT 'A->Active,I->Inactive',
  `created_by` int NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_coupon_log`
--

CREATE TABLE `tbl_coupon_log` (
  `coupon_id` int NOT NULL,
  `entity_id` int NOT NULL,
  `coupon_code` varchar(20) NOT NULL,
  `validity_from` date NOT NULL,
  `validity_to` date NOT NULL,
  `discount_type` char(1) NOT NULL COMMENT 'P-percentage, A- Amount',
  `discount` varchar(20) NOT NULL,
  `course_based` char(1) NOT NULL COMMENT 'Y-yes,N->no',
  `course_id` varchar(200) NOT NULL,
  `no_of_users` int NOT NULL,
  `status` char(1) NOT NULL COMMENT 'A->Active,I->Inactive',
  `created_by` int NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_course`
--

CREATE TABLE `tbl_course` (
  `course_id` int NOT NULL,
  `course_name` varchar(150) NOT NULL,
  `seo_title` varchar(150) NOT NULL,
  `category_id` varchar(200) NOT NULL,
  `subject_id` varchar(200) DEFAULT NULL,
  `grade_id` varchar(200) DEFAULT NULL,
  `description` text,
  `short_description` varchar(1000) DEFAULT NULL,
  `path` varchar(100) DEFAULT NULL,
  `validity_start_date` date NOT NULL,
  `validity_end_date` date NOT NULL,
  `status` char(1) NOT NULL COMMENT 'D->draft,P->Ready for review,A->Approved,R->rework,C-cancel',
  `lessons` varchar(100) DEFAULT NULL,
  `overview_content` text,
  `course_content` text,
  `prerequisites` text,
  `other_details` text,
  `author` varchar(500) DEFAULT NULL,
  `fees` varchar(50) DEFAULT NULL,
  `certified_course` char(1) DEFAULT NULL COMMENT 'Y-yes, N- no',
  `multiple_schedule` char(1) DEFAULT NULL COMMENT 'Y -> user can choose multiple schedule,N-> only one schedule can be chosen and registered',
  `schedule` tinyint(1) NOT NULL COMMENT '0->display course without schedule,1-> display course with schedule only',
  `entity_id` int NOT NULL,
  `redirect_url` varchar(250) DEFAULT NULL,
  `is_popular` char(1) NOT NULL DEFAULT 'N',
  `is_exclusive` char(1) NOT NULL DEFAULT 'N',
  `button_name` varchar(200) DEFAULT NULL,
  `event` tinyint(1) NOT NULL COMMENT '0->not event,1->event',
  `display_order` int NOT NULL,
  `contact_info` varchar(200) DEFAULT NULL,
  `created_by` int NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_course_category`
--

CREATE TABLE `tbl_course_category` (
  `category_id` int NOT NULL,
  `category_name` varchar(200) NOT NULL,
  `description` varchar(1000) DEFAULT NULL,
  `subject_id` varchar(100) DEFAULT NULL,
  `entity_id` int NOT NULL,
  `status` char(1) NOT NULL COMMENT 'A-> Active, I -> Inactive',
  `path` varchar(100) DEFAULT NULL,
  `display_order` int NOT NULL,
  `created_by` int DEFAULT NULL,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_course_category_log`
--

CREATE TABLE `tbl_course_category_log` (
  `category_id` int NOT NULL,
  `category_name` varchar(200) NOT NULL,
  `description` varchar(1000) DEFAULT NULL,
  `subject_id` varchar(100) DEFAULT NULL,
  `entity_id` int NOT NULL,
  `status` char(1) NOT NULL COMMENT 'A-> Active, I -> Inactive',
  `path` varchar(100) DEFAULT NULL,
  `display_order` int NOT NULL,
  `created_by` int DEFAULT NULL,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_course_faq`
--

CREATE TABLE `tbl_course_faq` (
  `id` int NOT NULL,
  `course_id` int NOT NULL,
  `title` varchar(1000) NOT NULL,
  `description` varchar(1000) DEFAULT NULL,
  `answer` varchar(1000) NOT NULL,
  `status` char(1) NOT NULL COMMENT 'A-> Active, I -> Inactive',
  `entity_id` bigint NOT NULL,
  `created_by` int NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_course_faq_log`
--

CREATE TABLE `tbl_course_faq_log` (
  `id` int NOT NULL,
  `course_id` int NOT NULL,
  `title` varchar(1000) NOT NULL,
  `description` varchar(1000) DEFAULT NULL,
  `answer` varchar(1000) NOT NULL,
  `status` char(1) NOT NULL COMMENT 'A-> Active, I -> Inactive',
  `entity_id` bigint NOT NULL,
  `created_by` int NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_course_log`
--

CREATE TABLE `tbl_course_log` (
  `course_id` int NOT NULL,
  `course_name` varchar(150) NOT NULL,
  `seo_title` varchar(150) NOT NULL,
  `category_id` varchar(200) NOT NULL,
  `subject_id` varchar(200) DEFAULT NULL,
  `grade_id` varchar(200) DEFAULT NULL,
  `description` text,
  `short_description` varchar(1000) DEFAULT NULL,
  `path` varchar(100) DEFAULT NULL,
  `validity_start_date` date NOT NULL,
  `validity_end_date` date NOT NULL,
  `status` char(1) NOT NULL COMMENT 'D->draft,P->Ready for review,A->Approved,R->rework,C-cancel',
  `lessons` varchar(100) DEFAULT NULL,
  `overview_content` text,
  `course_content` text,
  `prerequisites` text,
  `other_details` text,
  `author` varchar(500) DEFAULT NULL,
  `fees` varchar(50) DEFAULT NULL,
  `certified_course` char(1) DEFAULT NULL COMMENT 'Y-yes, N- no',
  `multiple_schedule` char(1) DEFAULT NULL COMMENT 'Y -> user can choose multiple schedule,N-> only one schedule can be chosen and registered',
  `schedule` tinyint(1) NOT NULL COMMENT '0->display course without schedule,1-> display course with schedule only',
  `entity_id` int NOT NULL,
  `redirect_url` varchar(250) DEFAULT NULL,
  `is_popular` char(1) DEFAULT NULL,
  `is_exclusive` char(1) DEFAULT NULL,
  `button_name` varchar(200) DEFAULT NULL,
  `event` tinyint(1) NOT NULL COMMENT '0->not event,1->event',
  `display_order` int NOT NULL,
  `contact_info` varchar(200) DEFAULT NULL,
  `created_by` int NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_course_ratings`
--

CREATE TABLE `tbl_course_ratings` (
  `rating_id` int NOT NULL,
  `course_detail_id` int NOT NULL,
  `rating` decimal(10,2) NOT NULL,
  `user_id` int NOT NULL,
  `created_by` int NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_course_reviews`
--

CREATE TABLE `tbl_course_reviews` (
  `review_id` int NOT NULL,
  `course_id` int NOT NULL,
  `review` varchar(1000) NOT NULL,
  `user_id` int NOT NULL,
  `created_by` int NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_course_schedule`
--

CREATE TABLE `tbl_course_schedule` (
  `schedule_id` int NOT NULL,
  `course_id` int NOT NULL,
  `schedule_title` varchar(200) NOT NULL,
  `course_start_date` date DEFAULT NULL,
  `course_end_date` date DEFAULT NULL,
  `registration_start_date` date DEFAULT NULL,
  `registration_end_date` date DEFAULT NULL,
  `program_code` varchar(20) DEFAULT NULL,
  `payment_type` char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT 'O-onetime,R-recurring',
  `payment_sub_type` char(1) NOT NULL COMMENT 'W->weekly,M->monthly,Q->quarterly,H->half-yearly,A->annually',
  `course_type` char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT 'O->online,I->inperson ',
  `location_id` varchar(100) DEFAULT NULL,
  `cost` decimal(10,2) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `actual_cost` decimal(10,2) NOT NULL,
  `total_slots` int DEFAULT NULL,
  `slots_booked` int NOT NULL DEFAULT '0',
  `status` char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT 'A-> Active, I -> Inactive',
  `entity_id` int NOT NULL,
  `edquill_class_id` int DEFAULT NULL,
  `created_by` int NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_course_seo`
--

CREATE TABLE `tbl_course_seo` (
  `seo_id` int NOT NULL,
  `course_id` int NOT NULL,
  `meta_author` varchar(200) DEFAULT NULL,
  `meta_title` varchar(200) DEFAULT NULL,
  `meta_description` varchar(1000) DEFAULT NULL,
  `meta_keywords` varchar(3000) DEFAULT NULL,
  `meta_keyphrase` varchar(3000) DEFAULT NULL,
  `meta_topic` varchar(200) DEFAULT NULL,
  `meta_subject` varchar(200) DEFAULT NULL,
  `meta_classification` varchar(3000) DEFAULT NULL,
  `meta_robots` varchar(200) DEFAULT NULL,
  `meta_rating` varchar(200) DEFAULT NULL,
  `meta_audience` varchar(200) DEFAULT NULL,
  `og_title` varchar(200) DEFAULT NULL,
  `og_type` varchar(100) DEFAULT NULL,
  `og_site_name` varchar(500) DEFAULT NULL,
  `og_description` varchar(1000) DEFAULT NULL,
  `og_site_url` varchar(500) DEFAULT NULL,
  `twitter_title` varchar(200) DEFAULT NULL,
  `twitter_site` varchar(500) DEFAULT NULL,
  `twitter_card` varchar(500) DEFAULT NULL,
  `twitter_description` varchar(1000) DEFAULT NULL,
  `twitter_creator` varchar(200) DEFAULT NULL,
  `status` char(1) NOT NULL COMMENT 'A-> Active, I -> Inactive',
  `created_by` int NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_course_seo_log`
--

CREATE TABLE `tbl_course_seo_log` (
  `seo_id` int NOT NULL,
  `course_id` int NOT NULL,
  `meta_author` varchar(200) DEFAULT NULL,
  `meta_title` varchar(200) DEFAULT NULL,
  `meta_description` varchar(1000) DEFAULT NULL,
  `meta_keywords` varchar(3000) DEFAULT NULL,
  `meta_keyphrase` varchar(3000) DEFAULT NULL,
  `meta_topic` varchar(200) DEFAULT NULL,
  `meta_subject` varchar(200) DEFAULT NULL,
  `meta_classification` varchar(3000) DEFAULT NULL,
  `meta_robots` varchar(200) DEFAULT NULL,
  `meta_rating` varchar(200) DEFAULT NULL,
  `meta_audience` varchar(200) DEFAULT NULL,
  `og_title` varchar(200) DEFAULT NULL,
  `og_type` varchar(100) DEFAULT NULL,
  `og_site_name` varchar(500) DEFAULT NULL,
  `og_description` varchar(1000) DEFAULT NULL,
  `og_site_url` varchar(500) DEFAULT NULL,
  `twitter_title` varchar(200) DEFAULT NULL,
  `twitter_site` varchar(500) DEFAULT NULL,
  `twitter_card` varchar(500) DEFAULT NULL,
  `twitter_description` varchar(1000) DEFAULT NULL,
  `twitter_creator` varchar(200) DEFAULT NULL,
  `status` char(1) NOT NULL COMMENT 'A-> Active, I -> Inactive',
  `created_by` int NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_entity_orders`
--

CREATE TABLE `tbl_entity_orders` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `cart_id` int NOT NULL,
  `payment_id` int NOT NULL,
  `course_id` int NOT NULL,
  `schedule_id` int NOT NULL,
  `entity_id` int NOT NULL,
  `entity_branch_id` int NOT NULL,
  `edquill_class_id` int NOT NULL,
  `student_class_id` int NOT NULL,
  `created_by` int NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_event`
--

CREATE TABLE `tbl_event` (
  `event_id` int NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `entity_id` int NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `location` varchar(20) NOT NULL,
  `start_time` varchar(20) NOT NULL,
  `end_time` varchar(20) DEFAULT NULL,
  `path` varchar(100) DEFAULT NULL,
  `is_popular` char(1) NOT NULL DEFAULT 'N',
  `status` char(1) NOT NULL COMMENT 'A-> Active, I -> Inactive',
  `created_by` int NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_location`
--

CREATE TABLE `tbl_location` (
  `location_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `status` char(1) NOT NULL COMMENT 'A-> active, I ->inactive',
  `created_by` int NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_payment_details`
--

CREATE TABLE `tbl_payment_details` (
  `payment_id` int NOT NULL,
  `cart_id` int NOT NULL,
  `payment_date` datetime DEFAULT NULL,
  `currency_code` varchar(5) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_status` tinytext NOT NULL COMMENT '1->Success,2->Failed ,3->Cancelled',
  `transaction_details` varchar(1000) DEFAULT NULL,
  `payment_response` varchar(3000) NOT NULL,
  `created_by` int NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_payment_details_log`
--

CREATE TABLE `tbl_payment_details_log` (
  `payment_id` int NOT NULL,
  `registration_id` int NOT NULL,
  `payment_date` datetime NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_mode` varchar(100) DEFAULT NULL,
  `transaction_details` varchar(1000) DEFAULT NULL,
  `created_by` int NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_registration`
--

CREATE TABLE `tbl_registration` (
  `registration_id` int NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(250) NOT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `gender` char(1) NOT NULL COMMENT 'M-> male, F->female, O->others',
  `grade_id` int NOT NULL,
  `course_id` int NOT NULL,
  `schedule_id` int NOT NULL,
  `cost` decimal(10,2) NOT NULL,
  `location_id` int NOT NULL,
  `dob` date NOT NULL,
  `state_id` int NOT NULL,
  `country_id` int NOT NULL,
  `zipcode` varchar(10) NOT NULL,
  `city` varchar(100) NOT NULL,
  `address1` varchar(500) NOT NULL,
  `address2` varchar(500) NOT NULL,
  `refered_by` varchar(20) NOT NULL,
  `status` char(1) NOT NULL COMMENT 'A-> active, I ->inactive',
  `payment_status` char(1) NOT NULL COMMENT 'N -> not paid,F ->, free cost, P->partially paid, C -> payment completed, R -> refunded',
  `created_by` int NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_registration_details`
--

CREATE TABLE `tbl_registration_details` (
  `id` int NOT NULL,
  `registration_id` int NOT NULL,
  `relationship_id` int NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(250) NOT NULL,
  `mobile` varchar(20) NOT NULL,
  `created_by` int NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int NOT NULL,
  `modified_date` int DEFAULT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_registration_details_log`
--

CREATE TABLE `tbl_registration_details_log` (
  `id` int NOT NULL,
  `registration_id` int NOT NULL,
  `relationship_id` int NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(250) NOT NULL,
  `mobile` varchar(20) NOT NULL,
  `created_by` int NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int NOT NULL,
  `modified_date` int DEFAULT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_registration_log`
--

CREATE TABLE `tbl_registration_log` (
  `registration_id` int NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(250) NOT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `gender` char(1) NOT NULL COMMENT 'M-> male, F->female, O->others',
  `grade_id` int NOT NULL,
  `course_id` int NOT NULL,
  `schedule_id` int NOT NULL,
  `cost` decimal(10,2) NOT NULL,
  `location_id` int NOT NULL,
  `dob` date NOT NULL,
  `state_id` int NOT NULL,
  `country_id` int NOT NULL,
  `zipcode` varchar(10) NOT NULL,
  `city` varchar(100) NOT NULL,
  `address1` varchar(500) NOT NULL,
  `address2` varchar(500) NOT NULL,
  `refered_by` varchar(20) NOT NULL,
  `status` char(1) NOT NULL COMMENT 'A-> active, I ->inactive',
  `payment_status` char(1) NOT NULL COMMENT 'N -> not paid,F ->, free cost, P->partially paid, C -> payment completed, R -> refunded',
  `created_by` int NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_subscription`
--

CREATE TABLE `tbl_subscription` (
  `id` int NOT NULL,
  `email_id` varchar(100) NOT NULL,
  `created_by` int NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_user_cart_details`
--

CREATE TABLE `tbl_user_cart_details` (
  `cart_id` bigint NOT NULL,
  `user_id` bigint NOT NULL,
  `cart_data` text NOT NULL,
  `shipping_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `sub_total` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `invoice_url` varchar(300) NOT NULL,
  `created_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teacher_action_notification`
--

CREATE TABLE `teacher_action_notification` (
  `action_id` bigint NOT NULL,
  `user_id` bigint NOT NULL,
  `role_id` bigint NOT NULL,
  `class_id` bigint NOT NULL DEFAULT '0',
  `content_id` bigint NOT NULL DEFAULT '0',
  `school_id` bigint NOT NULL,
  `action` text NOT NULL,
  `request_data` longtext NOT NULL,
  `edited_data` longtext NOT NULL,
  `message_content` text,
  `status` int NOT NULL DEFAULT '0',
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `teacher_class_annotation`
--

CREATE TABLE `teacher_class_annotation` (
  `id` bigint NOT NULL,
  `teacher_id` bigint NOT NULL,
  `class_id` bigint NOT NULL,
  `content_id` bigint NOT NULL,
  `school_id` bigint NOT NULL,
  `annotation` longtext NOT NULL,
  `created_by` bigint NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint NOT NULL,
  `modified_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `teacher_overall_feedback`
--

CREATE TABLE `teacher_overall_feedback` (
  `id` int NOT NULL,
  `student_content_id` bigint NOT NULL,
  `feedback` text NOT NULL,
  `feedback_type` char(1) NOT NULL COMMENT 'A->Automatic,M->Manual',
  `version` int DEFAULT NULL,
  `status` tinyint NOT NULL COMMENT '1->Active,0->Inactive',
  `created_by` int NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `testimonial`
--

CREATE TABLE `testimonial` (
  `id` bigint NOT NULL,
  `name` varchar(500) NOT NULL,
  `description` longtext NOT NULL,
  `image` text NOT NULL,
  `rating` int NOT NULL,
  `status` int NOT NULL COMMENT '1->active,2->inactive',
  `display_type` int NOT NULL COMMENT '1 -> general, 2 -> learing center, 3 -> tutors, 4 -> publishers',
  `display_from` datetime NOT NULL,
  `display_until` datetime NOT NULL,
  `created_by` bigint NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint NOT NULL,
  `modified_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `test_type_master`
--

CREATE TABLE `test_type_master` (
  `test_type_id` int NOT NULL,
  `test_type` varchar(200) NOT NULL,
  `status` tinyint(1) NOT NULL COMMENT '0->Inactive,1->Active',
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `text_questions`
--

CREATE TABLE `text_questions` (
  `question_id` int NOT NULL,
  `content_id` bigint NOT NULL,
  `question_type_id` bigint NOT NULL,
  `sub_question_type_id` bigint NOT NULL DEFAULT '0',
  `editor_context` longtext,
  `editor_type` int NOT NULL DEFAULT '1' COMMENT '1-> KeyBoard, 2-> Text, 3-> Math, 4->Diagram',
  `question_no` bigint NOT NULL,
  `sub_question_no` varchar(10) NOT NULL,
  `has_sub_question` int NOT NULL,
  `question` longtext NOT NULL,
  `answer_instructions` longtext,
  `editor_answer` longtext,
  `options` longtext NOT NULL,
  `answer` longtext NOT NULL,
  `level` int NOT NULL COMMENT '1->Easy, 2-> Medium, 3-> Hard',
  `heading_option` text NOT NULL,
  `multiple_response` int NOT NULL DEFAULT '0',
  `audo_grade` int NOT NULL DEFAULT '0',
  `points` decimal(10,0) NOT NULL,
  `exact_match` tinyint(1) NOT NULL,
  `hint` text NOT NULL,
  `explanation` longtext NOT NULL,
  `word_limit` bigint NOT NULL,
  `scoring_instruction` longtext NOT NULL,
  `source` text,
  `target` text,
  `passage_id` int NOT NULL,
  `subject_id` int DEFAULT '0',
  `question_topic_id` int DEFAULT '0',
  `question_sub_topic_id` int DEFAULT '0',
  `question_standard` int DEFAULT '0',
  `predicted_solving_time` varchar(50) DEFAULT NULL,
  `resource` text,
  `skill` varchar(1000) DEFAULT NULL,
  `created_by` bigint NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint NOT NULL,
  `modified_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `time_zone`
--

CREATE TABLE `time_zone` (
  `id` bigint NOT NULL,
  `continents_id` bigint NOT NULL,
  `time_zone` varchar(200) NOT NULL,
  `utc_timezone` varchar(200) NOT NULL,
  `status` int NOT NULL DEFAULT '1',
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `time_zone_master`
--

CREATE TABLE `time_zone_master` (
  `id` bigint NOT NULL,
  `continents_name` longtext NOT NULL,
  `status` int NOT NULL DEFAULT '1' COMMENT 'status - >1 active,0->inactive',
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `token`
--

CREATE TABLE `token` (
  `id` bigint UNSIGNED NOT NULL,
  `client_id` varchar(64) NOT NULL,
  `token_prefix` char(16) NOT NULL,
  `token_hash` varbinary(32) NOT NULL,
  `allowed_domain` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NOT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `revoked` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `topic`
--

CREATE TABLE `topic` (
  `topic_id` bigint NOT NULL,
  `topic` varchar(200) NOT NULL,
  `class_id` bigint NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `display_order` int NOT NULL,
  `status` int NOT NULL DEFAULT '1' COMMENT '1-> active, 2-> inactive',
  `created_by` int NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tutor_applications`
--

CREATE TABLE `tutor_applications` (
  `id` int NOT NULL,
  `first_name` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `last_name` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `bio` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `profile_image` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `degree` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `institution` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `graduation_year` varchar(4) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `subjects` json NOT NULL,
  `availability_days` json NOT NULL,
  `availability_slots` json NOT NULL,
  `teaching_subjects` json NOT NULL,
  `teaching_levels` json NOT NULL,
  `experience` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `certifications` json NOT NULL,
  `status` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `updated_class_schedule`
--

CREATE TABLE `updated_class_schedule` (
  `id` bigint NOT NULL,
  `class_id` bigint NOT NULL,
  `teacher_id` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `date` date NOT NULL,
  `start_time` varchar(20) NOT NULL,
  `end_time` varchar(20) NOT NULL,
  `school_id` bigint NOT NULL,
  `slot_days` int NOT NULL,
  `meeting_link` text NOT NULL,
  `meeting_id` text NOT NULL,
  `passcode` varchar(200) NOT NULL,
  `telephone_number` varchar(20) NOT NULL,
  `status` int NOT NULL COMMENT '1-> Added, 2-> Deleted',
  `created_by` bigint NOT NULL,
  `created_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `upgrade`
--

CREATE TABLE `upgrade` (
  `id` bigint NOT NULL,
  `school_id` bigint NOT NULL,
  `student_id` bigint NOT NULL,
  `grade_id` varchar(50) NOT NULL,
  `active_date` datetime NOT NULL,
  `dropped_date` datetime NOT NULL,
  `created_by` bigint NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint NOT NULL,
  `modified_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `user_id` bigint NOT NULL,
  `role_id` int NOT NULL COMMENT '1-> SuperAdmin, 2->Admin, 3->ContentCreater, 4->Teacher, 5->Students, 6->Corporate, 7->Grader',
  `default_password` tinyint(1) DEFAULT '1',
  `random_token` varchar(200) DEFAULT NULL,
  `email_id` varchar(200) DEFAULT NULL,
  `mobile` varchar(50) DEFAULT NULL,
  `password` varchar(200) DEFAULT NULL,
  `status` int NOT NULL COMMENT '1- Active, 2- Inactive, 3- Suspended, 4- Deleted',
  `school_id` varchar(100) NOT NULL DEFAULT '0',
  `corporate_id` bigint NOT NULL DEFAULT '0',
  `individual_teacher` int NOT NULL DEFAULT '0',
  `login_type` varchar(50) NOT NULL COMMENT 'web, google, facebook',
  `provider_id` varchar(200) DEFAULT NULL,
  `googleid_token` text,
  `token` text,
  `tc_status` int NOT NULL DEFAULT '0' COMMENT '0-> T&C Not Accepted, 1-> T&C Accepted',
  `edquill_teacher_id` int NOT NULL,
  `auto_generate_email_edquill` bigint NOT NULL DEFAULT '0',
  `student_id` varchar(200) NOT NULL,
  `academy_user_id` int NOT NULL,
  `source` varchar(50) DEFAULT NULL,
  `created_by` bigint DEFAULT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint DEFAULT NULL,
  `modified_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `user_address`
--

CREATE TABLE `user_address` (
  `address_id` bigint NOT NULL,
  `address_type` int NOT NULL COMMENT '1->teacher ,2-> student parent 1, 3-> student parent 2, 4-> Content Creater',
  `user_id` bigint NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email_ids` varchar(50) DEFAULT NULL,
  `address1` text,
  `address2` text,
  `city` varchar(128) DEFAULT NULL,
  `state` int DEFAULT NULL,
  `country` int NOT NULL DEFAULT '0',
  `postal_code` varchar(10) DEFAULT NULL,
  `created_date` datetime NOT NULL,
  `modified_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `user_permission`
--

CREATE TABLE `user_permission` (
  `id` bigint NOT NULL,
  `role_id` int NOT NULL,
  `permission_id` varchar(50) DEFAULT NULL,
  `display_order` int NOT NULL,
  `group_id` int NOT NULL,
  `group_name` text NOT NULL,
  `description` text NOT NULL,
  `status` int NOT NULL DEFAULT '1',
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_permission_backup`
--

CREATE TABLE `user_permission_backup` (
  `id` bigint NOT NULL,
  `role_id` int NOT NULL,
  `permission_id` bigint NOT NULL,
  `group_id` int NOT NULL,
  `group_name` text NOT NULL,
  `description` text NOT NULL,
  `status` int NOT NULL DEFAULT '1',
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `user_profile`
--

CREATE TABLE `user_profile` (
  `profile_id` bigint NOT NULL,
  `user_id` bigint NOT NULL,
  `first_name` varchar(200) NOT NULL,
  `last_name` varchar(200) DEFAULT NULL,
  `profile_url` text,
  `profile_thumb_url` text,
  `gender` varchar(20) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `currency` varchar(50) DEFAULT NULL,
  `created_by` bigint NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint DEFAULT NULL,
  `modified_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `user_profile_details`
--

CREATE TABLE `user_profile_details` (
  `user_details_id` bigint NOT NULL,
  `user_id` bigint NOT NULL,
  `school_id` bigint NOT NULL,
  `status` int DEFAULT '1' COMMENT '1->active, 2->inactive',
  `individual_teacher` int NOT NULL DEFAULT '0',
  `individual_role` int NOT NULL DEFAULT '0' COMMENT '0->teacher,1->parent',
  `doj` date NOT NULL,
  `dropped_date` date NOT NULL,
  `designation` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `school_idno` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `subject` varchar(50) NOT NULL,
  `grade_id` varchar(50) NOT NULL,
  `profile_subject` varchar(100) DEFAULT NULL,
  `profile_grade` varchar(100) DEFAULT NULL,
  `batch_id` bigint NOT NULL,
  `batch_type` varchar(50) NOT NULL,
  `edit_status` int NOT NULL DEFAULT '0' COMMENT '0 -> updated 1-> Not Updated',
  `upgrade_date` datetime NOT NULL,
  `allow_dashboard` int NOT NULL DEFAULT '1',
  `created_by` int NOT NULL,
  `created_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `user_role_permission`
--

CREATE TABLE `user_role_permission` (
  `id` bigint NOT NULL,
  `user_id` bigint NOT NULL,
  `role_id` bigint NOT NULL,
  `school_id` bigint NOT NULL,
  `user_permission_id` bigint NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `user_role_permission_backup`
--

CREATE TABLE `user_role_permission_backup` (
  `id` bigint NOT NULL,
  `user_id` bigint NOT NULL,
  `role_id` bigint NOT NULL,
  `school_id` bigint NOT NULL,
  `user_permission_id` bigint NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `user_security`
--

CREATE TABLE `user_security` (
  `security_id` bigint NOT NULL,
  `login_time` datetime NOT NULL,
  `login_location` text,
  `login_device` int DEFAULT NULL,
  `device_id` varchar(200) DEFAULT NULL,
  `created_date` datetime NOT NULL,
  `modified_Date` datetime DEFAULT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `user_token`
--

CREATE TABLE `user_token` (
  `id` int NOT NULL,
  `user_id` bigint NOT NULL,
  `access_token` varchar(250) NOT NULL,
  `ip_address` varchar(100) NOT NULL,
  `status` int NOT NULL,
  `created_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `user_uri_detail`
--

CREATE TABLE `user_uri_detail` (
  `id` int NOT NULL,
  `uri_path` text,
  `front_end_url` text,
  `user_id` bigint NOT NULL,
  `role_id` int NOT NULL,
  `user_name` varchar(100) NOT NULL,
  `email_id` varchar(100) NOT NULL,
  `created_by` int NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `website_contact_us`
--

CREATE TABLE `website_contact_us` (
  `id` bigint NOT NULL,
  `name` varchar(200) NOT NULL,
  `email_id` varchar(200) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `type` int NOT NULL COMMENT '2-> palsnortherns, 1->palssouthplainfield, 3->palsmarlboro.com, 4->palseastbrunswick, 5->palsmonroe.com, 6->palsoldbridge, 7->palsfreehold, 8->palspiscataway',
  `sub_type` char(2) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL COMMENT '1->Home,2->K-6 Math,3->Pre-Algebra,4->Algebra 1,5->Geometry,6->Algebra 2,7->Pre-Calculus,8->English,9->Reading-And-Writing,10->SAT-Prep,11->PSAT8-Prep,12->High-School-Prep,13->Physics-Honors,14->Chemistry-Honors,15->Biology-Honors,16->AP-Biology,17->AP-Chemistry,18->AP-Physics,19->AP-Calculus AB-BC,20->AP-Statistics',
  `status` int NOT NULL COMMENT '1-> mail sent, 2-> mail not sent',
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `zoom_creation_email`
--

CREATE TABLE `zoom_creation_email` (
  `id` bigint NOT NULL,
  `user_email` varchar(500) NOT NULL,
  `class_id` bigint NOT NULL,
  `schedule_id` bigint NOT NULL,
  `school_id` bigint NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `start_time` varchar(20) NOT NULL,
  `end_time` varchar(20) NOT NULL,
  `slot_days` int NOT NULL,
  `meeting_id` varchar(200) DEFAULT NULL,
  `teacher_link` longtext,
  `student_link` longtext,
  `zoom_response` longtext,
  `created_date` datetime NOT NULL,
  `created_by` bigint NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `zoom_recording`
--

CREATE TABLE `zoom_recording` (
  `id` bigint NOT NULL,
  `class_id` bigint NOT NULL,
  `school_id` bigint NOT NULL,
  `meeting_id` text NOT NULL,
  `created_by` bigint NOT NULL,
  `created_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `zoom_token`
--

CREATE TABLE `zoom_token` (
  `id` int NOT NULL,
  `access_token` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `expiry_date` datetime NOT NULL,
  `school_id` int NOT NULL,
  `created_by` int NOT NULL,
  `created_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_calendar`
--
ALTER TABLE `academic_calendar`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `address_master`
--
ALTER TABLE `address_master`
  ADD PRIMARY KEY (`address_type_id`);

--
-- Indexes for table `admin_mail_notification`
--
ALTER TABLE `admin_mail_notification`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin_settings`
--
ALTER TABLE `admin_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_status` (`status`),
  ADD KEY `IDX_name` (`name`);

--
-- Indexes for table `admin_settings_school`
--
ALTER TABLE `admin_settings_school`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_school_id` (`school_id`),
  ADD KEY `IDX_name` (`name`);

--
-- Indexes for table `answers`
--
ALTER TABLE `answers`
  ADD PRIMARY KEY (`answer_id`),
  ADD KEY `IDX_content_id` (`content_id`),
  ADD KEY `IDX_status` (`status`);

--
-- Indexes for table `batch`
--
ALTER TABLE `batch`
  ADD PRIMARY KEY (`batch_id`),
  ADD KEY `IDX_school_id` (`school_id`),
  ADD KEY `IDX_parent_batch_id` (`parent_batch_id`),
  ADD KEY `IDX_status` (`status`),
  ADD KEY `IDX_batch_name` (`batch_name`),
  ADD KEY `IDX_created_by` (`created_by`);

--
-- Indexes for table `blogger`
--
ALTER TABLE `blogger`
  ADD PRIMARY KEY (`blog_id`),
  ADD KEY `IDX_status` (`status`),
  ADD KEY `IDX_name` (`name`);

--
-- Indexes for table `book`
--
ALTER TABLE `book`
  ADD PRIMARY KEY (`book_id`);

--
-- Indexes for table `career`
--
ALTER TABLE `career`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_status` (`status`);

--
-- Indexes for table `career_application`
--
ALTER TABLE `career_application`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_job_id` (`job_id`),
  ADD KEY `IDX_status` (`status`);

--
-- Indexes for table `cfs_reports`
--
ALTER TABLE `cfs_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `content_id` (`content_id`),
  ADD KEY `question_topic_id` (`question_topic_id`),
  ADD KEY `question_sub_topic_id` (`question_sub_topic_id`),
  ADD KEY `question_standard_id` (`question_standard_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `idx_student_status` (`student_content_id`,`status`);

--
-- Indexes for table `class`
--
ALTER TABLE `class`
  ADD PRIMARY KEY (`class_id`),
  ADD KEY `IDX_class_code` (`class_code`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `IDX_status` (`status`),
  ADD KEY `IDX_grade` (`grade`),
  ADD KEY `IDX_subject` (`subject`);

--
-- Indexes for table `classroom_content`
--
ALTER TABLE `classroom_content`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_classroom_content_batch_id_idx` (`batch_id`),
  ADD KEY `FK_classroom_content_content_id_idx` (`content_id`),
  ADD KEY `FK_classroom_content_school_id_idx` (`school_id`),
  ADD KEY `IDX_batch_id` (`batch_id`),
  ADD KEY `IDX_content_id` (`content_id`),
  ADD KEY `IDX_status` (`status`);

--
-- Indexes for table `class_attendance`
--
ALTER TABLE `class_attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_start_time_end_time_slot_day_class_id_date` (`start_time`,`end_time`,`slot_day`,`class_id`,`date`),
  ADD KEY `IDX_student_id` (`student_id`),
  ADD KEY `class_id_idx` (`class_id`);

--
-- Indexes for table `class_content`
--
ALTER TABLE `class_content`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_school_id` (`school_id`),
  ADD KEY `IDX_start_date_end_Date` (`start_date`,`end_date`),
  ADD KEY `IDX_status` (`status`),
  ADD KEY `IDX_content_id` (`content_id`),
  ADD KEY `IDX_class_id` (`class_id`);

--
-- Indexes for table `class_dec_mig`
--
ALTER TABLE `class_dec_mig`
  ADD PRIMARY KEY (`class_id`);

--
-- Indexes for table `class_mail_notification`
--
ALTER TABLE `class_mail_notification`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `class_notes`
--
ALTER TABLE `class_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_class_notes_class_id_idx` (`class_id`),
  ADD KEY `IDX_class_id_status` (`class_id`,`status`);

--
-- Indexes for table `class_schedule`
--
ALTER TABLE `class_schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_class_schedule_class_id_idx` (`class_id`),
  ADD KEY `FK_class_schedule_school_id_idx` (`school_id`),
  ADD KEY `IDX_class_id` (`class_id`),
  ADD KEY `IDX_school_id` (`school_id`);

--
-- Indexes for table `content`
--
ALTER TABLE `content`
  ADD PRIMARY KEY (`content_id`),
  ADD KEY `FK_content_content_type_idx` (`content_type`),
  ADD KEY `IDX_name` (`name`),
  ADD KEY `IDX_subject` (`subject`),
  ADD KEY `IDX_grade` (`grade`),
  ADD KEY `content_format` (`content_format`);

--
-- Indexes for table `content_copy_22`
--
ALTER TABLE `content_copy_22`
  ADD PRIMARY KEY (`content_id`),
  ADD KEY `FK_content_content_type_idx` (`content_type`),
  ADD KEY `IDX_name` (`name`),
  ADD KEY `IDX_subject` (`subject`),
  ADD KEY `IDX_grade` (`grade`);

--
-- Indexes for table `content_master`
--
ALTER TABLE `content_master`
  ADD PRIMARY KEY (`content_id`);

--
-- Indexes for table `content_test_detail`
--
ALTER TABLE `content_test_detail`
  ADD PRIMARY KEY (`content_detail_id`),
  ADD KEY `idx_cd_test_id_content_id` (`test_id`,`content_id`);

--
-- Indexes for table `corporate`
--
ALTER TABLE `corporate`
  ADD PRIMARY KEY (`corporate_id`),
  ADD UNIQUE KEY `UNQ_corporate_name` (`corporate_name`),
  ADD UNIQUE KEY `UNQ_corporate_code` (`corporate_code`),
  ADD KEY `IDX_status` (`status`);

--
-- Indexes for table `corporate_request`
--
ALTER TABLE `corporate_request`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `IDX_corporate_id` (`corporate_id`),
  ADD KEY `IDX_status` (`status`),
  ADD KEY `IDX_school_id` (`school_id`),
  ADD KEY `FK_corporate_request_school_id_idx` (`school_id`),
  ADD KEY `FK_corporate_request_corporate_id_idx` (`corporate_id`);

--
-- Indexes for table `country`
--
ALTER TABLE `country`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `essay_rubric`
--
ALTER TABLE `essay_rubric`
  ADD PRIMARY KEY (`rubricID`);

--
-- Indexes for table `grade`
--
ALTER TABLE `grade`
  ADD PRIMARY KEY (`grade_id`),
  ADD KEY `IDX_status` (`status`);

--
-- Indexes for table `graph_answers`
--
ALTER TABLE `graph_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_answer_id_Student_id_class_id` (`answer_id`,`student_id`,`class_id`),
  ADD KEY `FK_graph_answers_class_id_idx` (`class_id`),
  ADD KEY `FK_graph_answers_content_id_idx` (`content_id`),
  ADD KEY `FK_graph_answers_student_id_idx` (`student_id`);

--
-- Indexes for table `holiday_calendar`
--
ALTER TABLE `holiday_calendar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_holiday_calender_school_id_idx` (`school_id`),
  ADD KEY `IDX_school_id_from_date_to_date` (`school_id`,`from_date`,`to_date`);

--
-- Indexes for table `institution_announcement`
--
ALTER TABLE `institution_announcement`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `invite_users`
--
ALTER TABLE `invite_users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_invite_users_user_id_idx` (`user_id`),
  ADD KEY `IDX_status` (`status`);

--
-- Indexes for table `mailbox`
--
ALTER TABLE `mailbox`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `IDX_status` (`status`),
  ADD KEY `IDX_from_id` (`from_id`),
  ADD KEY `IDX_to_id` (`to_id`(768)),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `mailbox_attachment`
--
ALTER TABLE `mailbox_attachment`
  ADD PRIMARY KEY (`attachment_id`),
  ADD KEY `FK_mailbox_attachment_message_id_idx` (`message_id`);

--
-- Indexes for table `mailbox_details`
--
ALTER TABLE `mailbox_details`
  ADD PRIMARY KEY (`message_detail_id`),
  ADD KEY `FK_mailbox_details_message_id_idx` (`message_id`),
  ADD KEY `IDX_is_read` (`is_read`),
  ADD KEY `FK_mailbox_details_user_id_idx` (`user_id`);

--
-- Indexes for table `note_comments`
--
ALTER TABLE `note_comments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notify_parents_requests`
--
ALTER TABLE `notify_parents_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_status` (`status`);

--
-- Indexes for table `page_master`
--
ALTER TABLE `page_master`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `passage`
--
ALTER TABLE `passage`
  ADD PRIMARY KEY (`passage_id`),
  ADD KEY `status` (`status`),
  ADD KEY `idx_passage_status_id` (`status`,`passage_id`);

--
-- Indexes for table `permission`
--
ALTER TABLE `permission`
  ADD PRIMARY KEY (`permission_id`),
  ADD UNIQUE KEY `UNQ_controller` (`controller`),
  ADD KEY `IDX_status` (`status`);

--
-- Indexes for table `question_skill`
--
ALTER TABLE `question_skill`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `question_standard`
--
ALTER TABLE `question_standard`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `question_topic`
--
ALTER TABLE `question_topic`
  ADD PRIMARY KEY (`question_topic_id`);

--
-- Indexes for table `question_types`
--
ALTER TABLE `question_types`
  ADD PRIMARY KEY (`question_type_id`),
  ADD KEY `IDX_status` (`status`),
  ADD KEY `IDX_question_uploads` (`question_uploads`),
  ADD KEY `FK_question_types_resouce_type_id_idx` (`resource_type_id`);

--
-- Indexes for table `resource_type_master`
--
ALTER TABLE `resource_type_master`
  ADD PRIMARY KEY (`resource_type_id`),
  ADD UNIQUE KEY `UNQ_resource_type` (`resource_type`),
  ADD KEY `IDX_status` (`status`);

--
-- Indexes for table `role_master`
--
ALTER TABLE `role_master`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `UNQ_role` (`role`);

--
-- Indexes for table `role_permission`
--
ALTER TABLE `role_permission`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_role_id_permission_id` (`role_id`,`permission_id`),
  ADD KEY `FK_role_permission_permission_id_idx` (`permission_id`);

--
-- Indexes for table `school`
--
ALTER TABLE `school`
  ADD PRIMARY KEY (`school_id`),
  ADD KEY `IDX_status` (`status`),
  ADD KEY `IDX_institution_type` (`institution_type`),
  ADD KEY `FK_school_country_idx` (`country`),
  ADD KEY `FK_school_state_idx` (`state`);

--
-- Indexes for table `sms_templates`
--
ALTER TABLE `sms_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNQ_sms_template_template_name_template_type` (`template_name`,`template_type`);

--
-- Indexes for table `state`
--
ALTER TABLE `state`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `IDX_name_country_id` (`name`,`country_id`),
  ADD KEY `FK_state_country_id_idx` (`country_id`);

--
-- Indexes for table `static_website`
--
ALTER TABLE `static_website`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `static_website_email_subscription`
--
ALTER TABLE `static_website_email_subscription`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_email_id` (`email_id`),
  ADD KEY `IDX_type` (`type`);

--
-- Indexes for table `student_answerkey_request`
--
ALTER TABLE `student_answerkey_request`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_student_answerkey_request_class_id_idx` (`class_id`),
  ADD KEY `FK_student_answerkey_request_content_id_idx` (`content_id`),
  ADD KEY `FK_student_answerkey_request_student_id_idx` (`student_id`),
  ADD KEY `IDX_status` (`status`);

--
-- Indexes for table `student_answers`
--
ALTER TABLE `student_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_answers_update_student_content_id_idx` (`student_id`,`content_id`,`answer_id`,`class_id`,`student_content_id`),
  ADD KEY `student_answers_answer_id_idx` (`answer_id`,`student_content_id`),
  ADD KEY `student_answers_student_content_id_idx` (`student_content_id`),
  ADD KEY `FK_student_answers_content_id_idx` (`content_id`),
  ADD KEY `FK_student_answers_class_id_idx` (`class_id`),
  ADD KEY `FK_student_answers_student_id_idx` (`student_id`),
  ADD KEY `FK_Student_Answers_student_content_id_idx` (`student_content_id`),
  ADD KEY `IDX_auto_grade` (`auto_grade`),
  ADD KEY `IDX_answer_id` (`answer_id`),
  ADD KEY `IDX_answer_status` (`answer_status`);

--
-- Indexes for table `student_answers_backup`
--
ALTER TABLE `student_answers_backup`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_answers_update_student_content_id_idx` (`student_id`,`content_id`,`answer_id`,`class_id`,`student_content_id`),
  ADD KEY `student_answers_answer_id_idx` (`answer_id`,`student_content_id`),
  ADD KEY `student_answers_student_content_id_idx` (`student_content_id`),
  ADD KEY `FK_student_answers_content_id_idx` (`content_id`),
  ADD KEY `FK_student_answers_class_id_idx` (`class_id`),
  ADD KEY `FK_student_answers_student_id_idx` (`student_id`),
  ADD KEY `FK_Student_Answers_student_content_id_idx` (`student_content_id`),
  ADD KEY `IDX_auto_grade` (`auto_grade`),
  ADD KEY `IDX_answer_id` (`answer_id`),
  ADD KEY `IDX_answer_status` (`answer_status`);

--
-- Indexes for table `student_class`
--
ALTER TABLE `student_class`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_student_class_class_id_idx` (`class_id`),
  ADD KEY `FK_student_class_student_id_idx` (`student_id`),
  ADD KEY `IDX_status` (`status`);

--
-- Indexes for table `student_class_transfer`
--
ALTER TABLE `student_class_transfer`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_student_class_transfer_student_id_idx` (`student_id`),
  ADD KEY `FK_student_class_transfer_class_id_idx` (`class_id`),
  ADD KEY `IDX_status` (`status`),
  ADD KEY `IDX_joining_date` (`joining_date`);

--
-- Indexes for table `student_content`
--
ALTER TABLE `student_content`
  ADD PRIMARY KEY (`id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `content_id` (`content_id`),
  ADD KEY `student_content_update_idx` (`student_id`,`content_id`,`class_id`),
  ADD KEY `idx_student_content_start_date` (`start_date`),
  ADD KEY `student_content_id_field_update_idx` (`id`),
  ADD KEY `FK_student_content_class_id_idx` (`class_id`),
  ADD KEY `FK_student_content_content_id_idx` (`content_id`),
  ADD KEY `FK_student_content_class_content_id_idx` (`class_content_id`),
  ADD KEY `IDX_status` (`status`),
  ADD KEY `IDX_draft_status` (`draft_status`);

--
-- Indexes for table `student_content_feedback`
--
ALTER TABLE `student_content_feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_student_content_feedback_class_id_idx` (`class_id`),
  ADD KEY `FK_student_content_feedback_student_id_idx` (`student_id`),
  ADD KEY `FK_student_Content_feedback_school_id_idx` (`school_id`);

--
-- Indexes for table `student_content_module`
--
ALTER TABLE `student_content_module`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_scm_student_content_id` (`student_content_id`),
  ADD KEY `idx_scm_module_id` (`module_id`);

--
-- Indexes for table `student_essays`
--
ALTER TABLE `student_essays`
  ADD PRIMARY KEY (`student_essay_id`);

--
-- Indexes for table `student_overdue_notification`
--
ALTER TABLE `student_overdue_notification`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_student_overdue_notification_class_id_idx` (`class_id`),
  ADD KEY `FK_Student_overdue_notification_school_id_idx` (`school_id`),
  ADD KEY `FK_student_overdue_notification_idx` (`student_id`),
  ADD KEY `FK_student_overdue_notification_content_id_idx` (`content_id`),
  ADD KEY `IDX_status` (`status`);

--
-- Indexes for table `student_suggestions`
--
ALTER TABLE `student_suggestions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_Student_suggestions_class_id_idx` (`class_id`),
  ADD KEY `FK_student_suggestions_student_id_idx` (`student_id`),
  ADD KEY `FK_student_suggestions_idx` (`content_id`),
  ADD KEY `FK_student_suggestions_school_id_idx` (`school_id`),
  ADD KEY `IDX_answer_id` (`answer_id`);

--
-- Indexes for table `student_upgrade`
--
ALTER TABLE `student_upgrade`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_student_upgrade_school_id_idx` (`school_id`),
  ADD KEY `FK_student_upgrade_Student_id_idx` (`student_id`),
  ADD KEY `IDX_status` (`status`);

--
-- Indexes for table `student_work`
--
ALTER TABLE `student_work`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_work_update_idx` (`content_id`,`class_id`,`student_id`),
  ADD KEY `student_work_student_content_id_idx` (`student_content_id`);

--
-- Indexes for table `subject`
--
ALTER TABLE `subject`
  ADD PRIMARY KEY (`subject_id`),
  ADD KEY `IDX_subject_name_school_id` (`subject_name`,`school_id`);

--
-- Indexes for table `sub_topic`
--
ALTER TABLE `sub_topic`
  ADD PRIMARY KEY (`sub_topic_id`);

--
-- Indexes for table `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_tags_school_id_idx` (`school_id`),
  ADD KEY `FK_tags_user_id_idx` (`user_id`);

--
-- Indexes for table `tbl_career`
--
ALTER TABLE `tbl_career`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_career_application`
--
ALTER TABLE `tbl_career_application`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_cart`
--
ALTER TABLE `tbl_cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_user_id_cart_type` (`user_id`,`cart_type`);

--
-- Indexes for table `tbl_cart_details`
--
ALTER TABLE `tbl_cart_details`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_content`
--
ALTER TABLE `tbl_content`
  ADD PRIMARY KEY (`content_id`),
  ADD KEY `idx_display_order` (`display_order`),
  ADD KEY `idx_category_id` (`category_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_display_from` (`display_from`),
  ADD KEY `idx_display_until` (`display_until`),
  ADD KEY `idx_subject_id` (`subject_id`);

--
-- Indexes for table `tbl_content_category`
--
ALTER TABLE `tbl_content_category`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `tbl_content_seo`
--
ALTER TABLE `tbl_content_seo`
  ADD PRIMARY KEY (`seo_id`),
  ADD KEY `idx_content_id` (`content_id`);

--
-- Indexes for table `tbl_coupon`
--
ALTER TABLE `tbl_coupon`
  ADD PRIMARY KEY (`coupon_id`);

--
-- Indexes for table `tbl_course`
--
ALTER TABLE `tbl_course`
  ADD PRIMARY KEY (`course_id`),
  ADD KEY `idx_popular` (`is_popular`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_entity_id` (`entity_id`),
  ADD KEY `idx_category_id` (`category_id`),
  ADD KEY `idx_subject_id` (`subject_id`),
  ADD KEY `idx_course_name` (`course_name`),
  ADD KEY `idx_display_order` (`display_order`);

--
-- Indexes for table `tbl_course_category`
--
ALTER TABLE `tbl_course_category`
  ADD PRIMARY KEY (`category_id`),
  ADD KEY `idx_display_order` (`display_order`);

--
-- Indexes for table `tbl_course_faq`
--
ALTER TABLE `tbl_course_faq`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_course_ratings`
--
ALTER TABLE `tbl_course_ratings`
  ADD PRIMARY KEY (`rating_id`);

--
-- Indexes for table `tbl_course_reviews`
--
ALTER TABLE `tbl_course_reviews`
  ADD PRIMARY KEY (`review_id`);

--
-- Indexes for table `tbl_course_schedule`
--
ALTER TABLE `tbl_course_schedule`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `idx_course_id` (`course_id`);

--
-- Indexes for table `tbl_course_seo`
--
ALTER TABLE `tbl_course_seo`
  ADD PRIMARY KEY (`seo_id`);

--
-- Indexes for table `tbl_entity_orders`
--
ALTER TABLE `tbl_entity_orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_event`
--
ALTER TABLE `tbl_event`
  ADD PRIMARY KEY (`event_id`);

--
-- Indexes for table `tbl_location`
--
ALTER TABLE `tbl_location`
  ADD PRIMARY KEY (`location_id`);

--
-- Indexes for table `tbl_payment_details`
--
ALTER TABLE `tbl_payment_details`
  ADD PRIMARY KEY (`payment_id`);

--
-- Indexes for table `tbl_registration`
--
ALTER TABLE `tbl_registration`
  ADD PRIMARY KEY (`registration_id`);

--
-- Indexes for table `tbl_registration_details`
--
ALTER TABLE `tbl_registration_details`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_subscription`
--
ALTER TABLE `tbl_subscription`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_user_cart_details`
--
ALTER TABLE `tbl_user_cart_details`
  ADD PRIMARY KEY (`cart_id`);

--
-- Indexes for table `teacher_action_notification`
--
ALTER TABLE `teacher_action_notification`
  ADD PRIMARY KEY (`action_id`),
  ADD KEY `IDX_status` (`status`);

--
-- Indexes for table `teacher_class_annotation`
--
ALTER TABLE `teacher_class_annotation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_teacher_class_annotation_teacher_id_idx` (`teacher_id`),
  ADD KEY `FK_teacher_class_annotation_school_id_idx` (`school_id`),
  ADD KEY `FK_teaher_class_annotation_class_id_idx` (`class_id`),
  ADD KEY `FK_teacher_Class_annotation_content_idx` (`content_id`);

--
-- Indexes for table `teacher_overall_feedback`
--
ALTER TABLE `teacher_overall_feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_feedback_content_status_id` (`student_content_id`,`status`,`id`);

--
-- Indexes for table `testimonial`
--
ALTER TABLE `testimonial`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_status_display_from_display_until` (`status`,`display_from`,`display_until`),
  ADD KEY `IDX_name` (`name`);

--
-- Indexes for table `test_type_master`
--
ALTER TABLE `test_type_master`
  ADD PRIMARY KEY (`test_type_id`),
  ADD KEY `IDX_status` (`status`);

--
-- Indexes for table `text_questions`
--
ALTER TABLE `text_questions`
  ADD PRIMARY KEY (`question_id`),
  ADD KEY `FK_text_questions_content_id_idx` (`content_id`),
  ADD KEY `IDX_question_no` (`question_no`),
  ADD KEY `IDX_question_type_id` (`question_type_id`);

--
-- Indexes for table `time_zone`
--
ALTER TABLE `time_zone`
  ADD KEY `FK_time_zone_continents_id_idx` (`continents_id`),
  ADD KEY `IDX_status` (`status`);

--
-- Indexes for table `token`
--
ALTER TABLE `token`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token_prefix` (`token_prefix`),
  ADD KEY `idx_client_expires` (`client_id`,`expires_at`),
  ADD KEY `idx_expires` (`expires_at`),
  ADD KEY `idx_token_prefix_revoked` (`token_prefix`,`revoked`),
  ADD KEY `idx_revoked` (`revoked`);

--
-- Indexes for table `topic`
--
ALTER TABLE `topic`
  ADD PRIMARY KEY (`topic_id`);

--
-- Indexes for table `tutor_applications`
--
ALTER TABLE `tutor_applications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `updated_class_schedule`
--
ALTER TABLE `updated_class_schedule`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `upgrade`
--
ALTER TABLE `upgrade`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_upgrade_school_id_idx` (`school_id`),
  ADD KEY `FK_upgrade_student_id_idx` (`student_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `IDX_role_id` (`role_id`),
  ADD KEY `IDX_email_id` (`email_id`),
  ADD KEY `ISX_status` (`status`),
  ADD KEY `IDX_mobile` (`mobile`),
  ADD KEY `IDX_school_id` (`school_id`),
  ADD KEY `IDX_corporate_id` (`corporate_id`);

--
-- Indexes for table `user_address`
--
ALTER TABLE `user_address`
  ADD PRIMARY KEY (`address_id`),
  ADD KEY `FK_user_address_user_id_idx` (`user_id`);

--
-- Indexes for table `user_permission`
--
ALTER TABLE `user_permission`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_permission_backup`
--
ALTER TABLE `user_permission_backup`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_status` (`status`),
  ADD KEY `IDX_roles_id` (`role_id`);

--
-- Indexes for table `user_profile`
--
ALTER TABLE `user_profile`
  ADD PRIMARY KEY (`profile_id`),
  ADD KEY `FK_user_profile_user_id_idx` (`user_id`);

--
-- Indexes for table `user_profile_details`
--
ALTER TABLE `user_profile_details`
  ADD PRIMARY KEY (`user_details_id`),
  ADD KEY `FK_user_profile_details_user_id_idx` (`user_id`),
  ADD KEY `FK_user_profile_details_school_id_idx` (`school_id`),
  ADD KEY `IDX_status` (`status`);

--
-- Indexes for table `user_role_permission`
--
ALTER TABLE `user_role_permission`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_user_role_permission_user_id_idx` (`user_id`),
  ADD KEY `FK_user_role_permission_school_id_idx` (`school_id`),
  ADD KEY `FK_user_role_permission_permission_id_idx` (`user_permission_id`),
  ADD KEY `IDX_role_id` (`role_id`);

--
-- Indexes for table `user_role_permission_backup`
--
ALTER TABLE `user_role_permission_backup`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_user_role_permission_user_id_idx` (`user_id`),
  ADD KEY `FK_user_role_permission_school_id_idx` (`school_id`),
  ADD KEY `FK_user_role_permission_permission_id_idx` (`user_permission_id`),
  ADD KEY `IDX_role_id` (`role_id`);

--
-- Indexes for table `user_security`
--
ALTER TABLE `user_security`
  ADD PRIMARY KEY (`security_id`);

--
-- Indexes for table `user_token`
--
ALTER TABLE `user_token`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_user_token_user_id_idx` (`user_id`),
  ADD KEY `IDX_status` (`status`),
  ADD KEY `IDX_access_token` (`access_token`);

--
-- Indexes for table `user_uri_detail`
--
ALTER TABLE `user_uri_detail`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `website_contact_us`
--
ALTER TABLE `website_contact_us`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `zoom_creation_email`
--
ALTER TABLE `zoom_creation_email`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_zoom_creation_email_class_id_idx` (`class_id`),
  ADD KEY `FK_zoom_creation_email_schedule_id_idx` (`schedule_id`),
  ADD KEY `FK_zoom_creation_email_school_id_idx` (`school_id`),
  ADD KEY `IDX_slot_days` (`slot_days`),
  ADD KEY `IDX_start_time_end_time` (`start_time`,`end_time`),
  ADD KEY `IDX_start_date` (`start_date`),
  ADD KEY `IDX_end_date` (`end_date`),
  ADD KEY `IDX_user_email` (`user_email`);

--
-- Indexes for table `zoom_recording`
--
ALTER TABLE `zoom_recording`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `zoom_token`
--
ALTER TABLE `zoom_token`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `address_master`
--
ALTER TABLE `address_master`
  MODIFY `address_type_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_mail_notification`
--
ALTER TABLE `admin_mail_notification`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_settings`
--
ALTER TABLE `admin_settings`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_settings_school`
--
ALTER TABLE `admin_settings_school`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `answers`
--
ALTER TABLE `answers`
  MODIFY `answer_id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `batch`
--
ALTER TABLE `batch`
  MODIFY `batch_id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `blogger`
--
ALTER TABLE `blogger`
  MODIFY `blog_id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `book`
--
ALTER TABLE `book`
  MODIFY `book_id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `career`
--
ALTER TABLE `career`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `career_application`
--
ALTER TABLE `career_application`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cfs_reports`
--
ALTER TABLE `cfs_reports`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `class`
--
ALTER TABLE `class`
  MODIFY `class_id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `classroom_content`
--
ALTER TABLE `classroom_content`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `class_attendance`
--
ALTER TABLE `class_attendance`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `class_content`
--
ALTER TABLE `class_content`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `class_dec_mig`
--
ALTER TABLE `class_dec_mig`
  MODIFY `class_id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `class_mail_notification`
--
ALTER TABLE `class_mail_notification`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `class_notes`
--
ALTER TABLE `class_notes`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `class_schedule`
--
ALTER TABLE `class_schedule`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `content`
--
ALTER TABLE `content`
  MODIFY `content_id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `content_copy_22`
--
ALTER TABLE `content_copy_22`
  MODIFY `content_id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `content_master`
--
ALTER TABLE `content_master`
  MODIFY `content_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `content_test_detail`
--
ALTER TABLE `content_test_detail`
  MODIFY `content_detail_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `corporate`
--
ALTER TABLE `corporate`
  MODIFY `corporate_id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `corporate_request`
--
ALTER TABLE `corporate_request`
  MODIFY `request_id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `country`
--
ALTER TABLE `country`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `essay_rubric`
--
ALTER TABLE `essay_rubric`
  MODIFY `rubricID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grade`
--
ALTER TABLE `grade`
  MODIFY `grade_id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `graph_answers`
--
ALTER TABLE `graph_answers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `holiday_calendar`
--
ALTER TABLE `holiday_calendar`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `institution_announcement`
--
ALTER TABLE `institution_announcement`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invite_users`
--
ALTER TABLE `invite_users`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mailbox`
--
ALTER TABLE `mailbox`
  MODIFY `message_id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mailbox_attachment`
--
ALTER TABLE `mailbox_attachment`
  MODIFY `attachment_id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mailbox_details`
--
ALTER TABLE `mailbox_details`
  MODIFY `message_detail_id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `note_comments`
--
ALTER TABLE `note_comments`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notify_parents_requests`
--
ALTER TABLE `notify_parents_requests`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `page_master`
--
ALTER TABLE `page_master`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `passage`
--
ALTER TABLE `passage`
  MODIFY `passage_id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permission`
--
ALTER TABLE `permission`
  MODIFY `permission_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `question_skill`
--
ALTER TABLE `question_skill`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `question_standard`
--
ALTER TABLE `question_standard`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `question_topic`
--
ALTER TABLE `question_topic`
  MODIFY `question_topic_id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `question_types`
--
ALTER TABLE `question_types`
  MODIFY `question_type_id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `resource_type_master`
--
ALTER TABLE `resource_type_master`
  MODIFY `resource_type_id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `role_master`
--
ALTER TABLE `role_master`
  MODIFY `role_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `role_permission`
--
ALTER TABLE `role_permission`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `school`
--
ALTER TABLE `school`
  MODIFY `school_id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sms_templates`
--
ALTER TABLE `sms_templates`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `state`
--
ALTER TABLE `state`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `static_website`
--
ALTER TABLE `static_website`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `static_website_email_subscription`
--
ALTER TABLE `static_website_email_subscription`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_answerkey_request`
--
ALTER TABLE `student_answerkey_request`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_answers`
--
ALTER TABLE `student_answers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_answers_backup`
--
ALTER TABLE `student_answers_backup`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_class`
--
ALTER TABLE `student_class`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_class_transfer`
--
ALTER TABLE `student_class_transfer`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_content`
--
ALTER TABLE `student_content`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_content_feedback`
--
ALTER TABLE `student_content_feedback`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_content_module`
--
ALTER TABLE `student_content_module`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_essays`
--
ALTER TABLE `student_essays`
  MODIFY `student_essay_id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_overdue_notification`
--
ALTER TABLE `student_overdue_notification`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_suggestions`
--
ALTER TABLE `student_suggestions`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_upgrade`
--
ALTER TABLE `student_upgrade`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_work`
--
ALTER TABLE `student_work`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subject`
--
ALTER TABLE `subject`
  MODIFY `subject_id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sub_topic`
--
ALTER TABLE `sub_topic`
  MODIFY `sub_topic_id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tags`
--
ALTER TABLE `tags`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_career`
--
ALTER TABLE `tbl_career`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_career_application`
--
ALTER TABLE `tbl_career_application`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_cart`
--
ALTER TABLE `tbl_cart`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_cart_details`
--
ALTER TABLE `tbl_cart_details`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_content`
--
ALTER TABLE `tbl_content`
  MODIFY `content_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_content_category`
--
ALTER TABLE `tbl_content_category`
  MODIFY `category_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_content_seo`
--
ALTER TABLE `tbl_content_seo`
  MODIFY `seo_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_coupon`
--
ALTER TABLE `tbl_coupon`
  MODIFY `coupon_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_course`
--
ALTER TABLE `tbl_course`
  MODIFY `course_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_course_category`
--
ALTER TABLE `tbl_course_category`
  MODIFY `category_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_course_faq`
--
ALTER TABLE `tbl_course_faq`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_course_ratings`
--
ALTER TABLE `tbl_course_ratings`
  MODIFY `rating_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_course_reviews`
--
ALTER TABLE `tbl_course_reviews`
  MODIFY `review_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_course_schedule`
--
ALTER TABLE `tbl_course_schedule`
  MODIFY `schedule_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_course_seo`
--
ALTER TABLE `tbl_course_seo`
  MODIFY `seo_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_entity_orders`
--
ALTER TABLE `tbl_entity_orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_event`
--
ALTER TABLE `tbl_event`
  MODIFY `event_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_location`
--
ALTER TABLE `tbl_location`
  MODIFY `location_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_payment_details`
--
ALTER TABLE `tbl_payment_details`
  MODIFY `payment_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_registration`
--
ALTER TABLE `tbl_registration`
  MODIFY `registration_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_registration_details`
--
ALTER TABLE `tbl_registration_details`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_subscription`
--
ALTER TABLE `tbl_subscription`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_user_cart_details`
--
ALTER TABLE `tbl_user_cart_details`
  MODIFY `cart_id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teacher_action_notification`
--
ALTER TABLE `teacher_action_notification`
  MODIFY `action_id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teacher_class_annotation`
--
ALTER TABLE `teacher_class_annotation`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teacher_overall_feedback`
--
ALTER TABLE `teacher_overall_feedback`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `testimonial`
--
ALTER TABLE `testimonial`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `test_type_master`
--
ALTER TABLE `test_type_master`
  MODIFY `test_type_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `text_questions`
--
ALTER TABLE `text_questions`
  MODIFY `question_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `token`
--
ALTER TABLE `token`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `topic`
--
ALTER TABLE `topic`
  MODIFY `topic_id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tutor_applications`
--
ALTER TABLE `tutor_applications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `updated_class_schedule`
--
ALTER TABLE `updated_class_schedule`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `upgrade`
--
ALTER TABLE `upgrade`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `user_id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_address`
--
ALTER TABLE `user_address`
  MODIFY `address_id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_permission`
--
ALTER TABLE `user_permission`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_permission_backup`
--
ALTER TABLE `user_permission_backup`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_profile`
--
ALTER TABLE `user_profile`
  MODIFY `profile_id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_profile_details`
--
ALTER TABLE `user_profile_details`
  MODIFY `user_details_id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_role_permission`
--
ALTER TABLE `user_role_permission`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_role_permission_backup`
--
ALTER TABLE `user_role_permission_backup`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_security`
--
ALTER TABLE `user_security`
  MODIFY `security_id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_token`
--
ALTER TABLE `user_token`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_uri_detail`
--
ALTER TABLE `user_uri_detail`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `website_contact_us`
--
ALTER TABLE `website_contact_us`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `zoom_creation_email`
--
ALTER TABLE `zoom_creation_email`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `zoom_recording`
--
ALTER TABLE `zoom_recording`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `zoom_token`
--
ALTER TABLE `zoom_token`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;
COMMIT;

