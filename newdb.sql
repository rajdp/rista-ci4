
CREATE TABLE `academic_calendar` (
  `id` bigint(20) NOT NULL,
  `school_id` bigint(20) NOT NULL,
  `academic_year` varchar(250) NOT NULL,
  `academic_month` int(11) NOT NULL,
  `academic_week` int(11) NOT NULL,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `status` int(11) NOT NULL DEFAULT '1' COMMENT '1->active, 2->inactive',
  `created_by` bigint(20) NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint(20) NOT NULL,
  `modified_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `address_master`
--

CREATE TABLE `address_master` (
  `address_type_id` int(11) NOT NULL,
  `address` varchar(100) NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `admin_mail_notification`
--

CREATE TABLE `admin_mail_notification` (
  `id` int(11) NOT NULL,
  `student_id` bigint(20) NOT NULL,
  `class_id` bigint(20) NOT NULL,
  `school_id` bigint(20) NOT NULL,
  `grade_id` bigint(20) NOT NULL,
  `content_id` text NOT NULL,
  `status` int(11) NOT NULL DEFAULT '0' COMMENT '0-> Not Sent, 1->Sent',
  `created_by` bigint(20) NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint(20) NOT NULL,
  `modified_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `admin_settings`
--

CREATE TABLE `admin_settings` (
  `id` bigint(20) NOT NULL,
  `name` varchar(250) NOT NULL,
  `description` text NOT NULL,
  `value` text NOT NULL,
  `settings` int(11) NOT NULL DEFAULT '0',
  `status` int(11) NOT NULL COMMENT '2->testweb, 3->testadmin, 4->testuatweb, 5->testuatadmin, 6->liveuatweb, 7->liveuatadmin, 8->liveweb, 9-> liveadmin, 10->demoweb, 11->demoadmin, 12-> livetestweb, 13-> livetestadmin',
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `admin_settings_school`
--

CREATE TABLE `admin_settings_school` (
  `id` bigint(20) NOT NULL,
  `name` varchar(250) NOT NULL,
  `description` text NOT NULL,
  `value` text NOT NULL,
  `school_id` bigint(20) NOT NULL,
  `settings` int(11) NOT NULL DEFAULT '0',
  `status` int(11) NOT NULL,
  `modified_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `answers`
--

CREATE TABLE `answers` (
  `answer_id` bigint(20) NOT NULL,
  `question_no` varchar(50) NOT NULL,
  `section_heading` varchar(200) DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT '0',
  `section_id` int(11) NOT NULL DEFAULT '0',
  `question` longtext,
  `answer_instructions` longtext,
  `content_id` bigint(20) NOT NULL,
  `question_type_id` bigint(20) NOT NULL,
  `has_sub_question` int(11) NOT NULL,
  `sub_question_no` varchar(10) DEFAULT NULL,
  `options` text,
  `array` text,
  `mob_options` longtext NOT NULL,
  `old_answer` longtext NOT NULL,
  `answer` longtext NOT NULL,
  `answer_explanation` longtext,
  `editor_answer` longtext,
  `auto_grade` int(11) NOT NULL DEFAULT '0',
  `points` int(11) NOT NULL DEFAULT '0',
  `difficulty` int(11) NOT NULL,
  `allow_exact_match` int(11) DEFAULT NULL,
  `allow_any_text` int(11) DEFAULT NULL,
  `match_case` int(11) DEFAULT NULL,
  `minimum_line` int(11) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT '1' COMMENT '0->inactive, 1->active',
  `page_no` int(11) NOT NULL,
  `created_by` bigint(20) NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint(20) NOT NULL,
  `modified_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `batch`
--

CREATE TABLE `batch` (
  `batch_id` bigint(20) NOT NULL,
  `batch_name` varchar(200) NOT NULL,
  `school_id` bigint(20) NOT NULL,
  `corporate_id` int(11) NOT NULL,
  `status` bigint(20) NOT NULL COMMENT '1-Active,2-In Active,3->Suspended, 4->Deleted',
  `batch_type` bigint(20) NOT NULL,
  `edquill_batch_id` int(11) NOT NULL,
  `parent_batch_id` int(11) NOT NULL DEFAULT '0',
  `created_by` bigint(20) NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint(20) NOT NULL,
  `modified_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `blogger`
--

CREATE TABLE `blogger` (
  `blog_id` bigint(20) NOT NULL,
  `name` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_slug` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `short_description` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `long_description` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `author` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` int(11) NOT NULL COMMENT '1->active,2->inactive',
  `display_type` int(11) NOT NULL COMMENT '1 -> general, 2 -> learing center, 3 -> tutors, 4 -> publishers',
  `views` int(11) NOT NULL DEFAULT '0',
  `display_from` datetime NOT NULL,
  `display_until` datetime NOT NULL,
  `created_by` bigint(20) NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint(20) NOT NULL,
  `modified_date` date NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `book`
--

CREATE TABLE `book` (
  `book_id` bigint(20) NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` text NOT NULL,
  `school_id` bigint(20) NOT NULL DEFAULT '0',
  `description` text NOT NULL,
  `status` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `career`
--

CREATE TABLE `career` (
  `id` bigint(20) NOT NULL,
  `title` varchar(200) NOT NULL,
  `department` varchar(200) NOT NULL,
  `address1` text NOT NULL,
  `address2` text NOT NULL,
  `description` longtext NOT NULL,
  `basic_qualification` longtext NOT NULL,
  `prefered_qualification` longtext NOT NULL,
  `status` int(11) NOT NULL,
  `created_by` bigint(20) NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint(20) NOT NULL,
  `modified_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `career_application`
--

CREATE TABLE `career_application` (
  `id` bigint(20) NOT NULL,
  `job_id` bigint(20) NOT NULL,
  `name` varchar(200) NOT NULL,
  `email` varchar(200) NOT NULL,
  `resume_url` varchar(200) NOT NULL,
  `portfolio` varchar(200) NOT NULL,
  `status` int(11) NOT NULL,
  `created_by` bigint(20) NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint(20) NOT NULL,
  `modified_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `cfs_reports`
--

CREATE TABLE `cfs_reports` (
  `id` int(11) NOT NULL,
  `class_id` bigint(20) NOT NULL,
  `content_id` bigint(20) NOT NULL,
  `student_id` bigint(20) NOT NULL,
  `student_content_id` bigint(20) NOT NULL,
  `question_id` int(11) NOT NULL,
  `question_no` int(11) NOT NULL,
  `is_correct` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `time_taken` int(11) NOT NULL,
  `predicted_time` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_id` bigint(20) DEFAULT NULL,
  `question_topic_id` bigint(20) DEFAULT NULL,
  `question_sub_topic_id` bigint(20) DEFAULT NULL,
  `question_standard_id` bigint(20) DEFAULT NULL,
  `skill` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assigned_date` datetime NOT NULL,
  `answered_date` datetime NOT NULL,
  `status` tinyint(1) NOT NULL COMMENT '0->Inactive,1->Active',
  `module_id` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `class`
--

CREATE TABLE `class` (
  `class_id` bigint(20) NOT NULL,
  `teacher_id` bigint(20) NOT NULL,
  `school_id` bigint(20) NOT NULL,
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
  `telephone_number` varchar(200) DEFAULT NULL,
  `class_code` varchar(20) NOT NULL,
  `time_zone_id` int(11) NOT NULL DEFAULT '0',
  `status` int(11) NOT NULL COMMENT '1-Active,2-Inactive,3-remove',
  `class_status` int(11) NOT NULL DEFAULT '0' COMMENT '0->active 1->save',
  `class_type` int(11) NOT NULL COMMENT '1-> Online, 2-> In person',
  `announcement_type` tinyint(1) NOT NULL DEFAULT '2' COMMENT '1 -> do not allow,2-> allow only,3-> allow announcement and comments',
  `video_link` longtext,
  `profile_url` text,
  `profile_thumb_url` text,
  `notes` longtext,
  `edquill_schedule_id` int(11) NOT NULL,
  `edquill_classroom_id` bigint(20) NOT NULL DEFAULT '0',
  `academy_schedule_id` int(11) DEFAULT NULL,
  `academy_course_id` int(11) DEFAULT NULL,
  `course_id` int(11) NOT NULL DEFAULT '0',
  `registration_start_date` date DEFAULT NULL,
  `registration_end_date` date DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT NULL,
  `actual_cost` decimal(10,2) DEFAULT NULL,
  `total_slots` int(11) DEFAULT NULL,
  `slots_booked` int(11) NOT NULL DEFAULT '0',
  `location_id` varchar(100) DEFAULT NULL,
  `payment_type` char(1) DEFAULT NULL COMMENT 'O-onetime,R-recurring',
  `payment_sub_type` char(1) DEFAULT NULL COMMENT 'W->weekly,M->monthly,Q->quarterly,H->half-yearly,A->annually',
  `created_date` datetime NOT NULL,
  `created_by` bigint(20) NOT NULL,
  `modified_date` datetime NOT NULL,
  `modified_by` bigint(20) NOT NULL DEFAULT '0',
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `classroom_content`
--

CREATE TABLE `classroom_content` (
  `id` int(11) NOT NULL,
  `batch_id` bigint(20) NOT NULL,
  `school_id` bigint(20) NOT NULL,
  `content_id` bigint(20) NOT NULL,
  `status` int(11) NOT NULL COMMENT '1-> Added, 2-> Deleted',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `auto_review` int(11) NOT NULL COMMENT '0-> manually, 1-> after completing test , 2-> after completing each question',
  `created_by` bigint(20) NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint(20) NOT NULL,
  `modified_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `class_attendance`
--

CREATE TABLE `class_attendance` (
  `id` int(11) NOT NULL,
  `start_time` varchar(20) NOT NULL,
  `end_time` varchar(20) NOT NULL,
  `slot_day` int(11) NOT NULL,
  `schedule_id` bigint(20) NOT NULL,
  `class_id` bigint(20) NOT NULL,
  `student_id` bigint(20) NOT NULL,
  `attendance` int(11) DEFAULT NULL COMMENT '0 - Absent, 1 - Present',
  `date` date NOT NULL,
  `request_json` text NOT NULL,
  `created_date` datetime NOT NULL,
  `created_by` bigint(20) NOT NULL,
  `modified_date` datetime NOT NULL,
  `modified_by` bigint(20) NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `class_content`
--

CREATE TABLE `class_content` (
  `id` bigint(20) NOT NULL,
  `class_id` bigint(20) DEFAULT NULL,
  `content_id` bigint(20) NOT NULL DEFAULT '0',
  `school_id` bigint(20) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT '1' COMMENT '1-> active, 2-> inactive',
  `all_student` int(11) NOT NULL DEFAULT '1' COMMENT '1->all_student,0->specific_students',
  `release_score` int(11) NOT NULL DEFAULT '0' COMMENT '0->No_Release, 1->Release',
  `auto_review` int(11) NOT NULL DEFAULT '0' COMMENT '0-> manually, 1-> after completing test , 2-> after completing each question',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `notes` text,
  `downloadable` int(11) NOT NULL DEFAULT '0' COMMENT '0-> Not downloadable, 2-> downloadable',
  `topic_id` int(11) NOT NULL DEFAULT '0',
  `is_accessible` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0 -> not accessible,1 -> accessible after end date',
  `allow_feedback` tinyint(1) NOT NULL DEFAULT '0',
  `allow_workspace` tinyint(1) NOT NULL DEFAULT '0',
  `show_timer` tinyint(1) NOT NULL DEFAULT '0',
  `created_by` bigint(20) NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint(20) NOT NULL,
  `modified_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `class_content_log`
--

CREATE TABLE `class_content_log` (
  `id` bigint(20) NOT NULL,
  `class_id` bigint(20) DEFAULT NULL,
  `content_id` bigint(20) NOT NULL DEFAULT '0',
  `school_id` bigint(20) NOT NULL,
  `status` int(11) NOT NULL DEFAULT '1' COMMENT '1-> active, 2-> inactive',
  `all_student` int(11) NOT NULL DEFAULT '1' COMMENT '1->all_student,0->specific_students',
  `release_score` int(11) NOT NULL DEFAULT '0' COMMENT '0->No_Release, 1->Release',
  `auto_review` int(11) NOT NULL DEFAULT '0' COMMENT '0-> manually, 1-> after completing test , 2-> after completing each question',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `notes` text,
  `downloadable` int(11) NOT NULL DEFAULT '0' COMMENT '0-> Not downloadable, 2-> downloadable',
  `topic_id` int(11) NOT NULL,
  `is_accessible` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0 -> not accessible,1 -> accessible after end date',
  `allow_feedback` tinyint(1) NOT NULL DEFAULT '0',
  `allow_workspace` tinyint(1) NOT NULL DEFAULT '0',
  `show_timer` tinyint(1) NOT NULL DEFAULT '0',
  `created_by` bigint(20) NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint(20) NOT NULL,
  `modified_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `class_dec_mig`
--

CREATE TABLE `class_dec_mig` (
  `class_id` bigint(20) NOT NULL,
  `teacher_id` bigint(20) NOT NULL,
  `school_id` bigint(20) NOT NULL,
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
  `telephone_number` varchar(200) DEFAULT NULL,
  `class_code` varchar(20) NOT NULL,
  `status` int(11) NOT NULL COMMENT '1-Active,2-Inactive,3-remove',
  `class_status` int(11) NOT NULL DEFAULT '0' COMMENT '0->active 1->save',
  `class_type` int(11) NOT NULL COMMENT '1-> Online, 2-> In person',
  `video_link` longtext,
  `profile_url` text,
  `profile_thumb_url` text,
  `notes` longtext,
  `edquill_schedule_id` int(11) NOT NULL,
  `edquill_classroom_id` bigint(20) NOT NULL DEFAULT '0',
  `created_date` datetime NOT NULL,
  `created_by` bigint(20) NOT NULL,
  `modified_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` bigint(20) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `class_mail_notification`
--

CREATE TABLE `class_mail_notification` (
  `id` bigint(20) NOT NULL,
  `class_id` bigint(20) NOT NULL,
  `email_id` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `mail_sent` int(11) NOT NULL DEFAULT '0' COMMENT '0->mail not sent,1->mail sent',
  `provider_id` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `googleid_token` text COLLATE utf8mb4_unicode_ci,
  `is_makeup` tinyint(1) NOT NULL DEFAULT '0',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_by` bigint(20) NOT NULL DEFAULT '0',
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `class_notes`
--

CREATE TABLE `class_notes` (
  `id` bigint(20) NOT NULL,
  `class_id` bigint(20) NOT NULL,
  `note` text NOT NULL,
  `add_date` datetime NOT NULL,
  `status` int(11) NOT NULL DEFAULT '1' COMMENT '1->active, 2->deleted',
  `created_by` bigint(20) NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint(20) NOT NULL,
  `modified_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `class_schedule`
--

CREATE TABLE `class_schedule` (
  `id` bigint(20) NOT NULL,
  `class_id` bigint(20) NOT NULL,
  `teacher_id` text NOT NULL,
  `start_time` varchar(20) NOT NULL,
  `end_time` varchar(20) NOT NULL,
  `school_id` bigint(20) NOT NULL,
  `slot_days` int(11) NOT NULL,
  `slotselected` tinyint(1) NOT NULL DEFAULT '1',
  `meeting_link` text,
  `meeting_id` text,
  `teacher_link` text NOT NULL,
  `student_link` text NOT NULL,
  `zoom_response` longtext NOT NULL,
  `passcode` varchar(200) DEFAULT NULL,
  `telephone_number` varchar(20) DEFAULT NULL,
  `created_by` bigint(20) NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` int(11) NOT NULL,
  `modified_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `content`
--

CREATE TABLE `content` (
  `content_id` bigint(20) NOT NULL,
  `name` varchar(200) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `description` text,
  `grade` varchar(200) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `school_id` bigint(20) NOT NULL,
  `corporate_id` bigint(20) NOT NULL DEFAULT '0',
  `testcode_id` int(11) NOT NULL DEFAULT '0',
  `score_path` varchar(250) DEFAULT NULL,
  `file_path` longtext,
  `base64_data` longtext NOT NULL,
  `links` longtext,
  `file_text` longtext,
  `answerkey_path` text,
  `teacher_version` longtext,
  `annotation` longtext NOT NULL,
  `questionAnnotation` longtext NOT NULL,
  `content_type` int(11) NOT NULL COMMENT '1->Resources,2->Assignment,3->Assessment',
  `editor_type` int(11) NOT NULL DEFAULT '1' COMMENT '1-> KeyBoard, 2-> Text, 3-> Math, 4->Diagram',
  `tags` varchar(200) DEFAULT NULL,
  `content_format` int(11) NOT NULL COMMENT '1-> pdf 2-> links 3-> text 4-> HW',
  `total_questions` int(11) NOT NULL DEFAULT '0',
  `access` int(11) NOT NULL COMMENT '1->private(within school),2->private(within user),3->public, 4->private(within corporate)',
  `status` bigint(20) NOT NULL COMMENT '1->Active,2->Inactive,3->Suspended,4->Deleted,5->Draft',
  `profile_url` text,
  `profile_thumb_url` text,
  `publication_code` varchar(20) DEFAULT '1' COMMENT '1->Not Book',
  `download` int(11) NOT NULL DEFAULT '0',
  `allow_answer_key` int(11) DEFAULT '0' COMMENT '0->not allow, 1->allow',
  `content_duration` int(11) NOT NULL DEFAULT '0',
  `is_test` tinyint(1) NOT NULL DEFAULT '0',
  `test_type_id` int(11) NOT NULL DEFAULT '1',
  `created_by` bigint(20) NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint(20) NOT NULL,
  `modified_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `content_copy_22`
--

CREATE TABLE `content_copy_22` (
  `content_id` bigint(20) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text,
  `grade` varchar(200) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `school_id` bigint(20) NOT NULL,
  `corporate_id` bigint(20) NOT NULL DEFAULT '0',
  `testcode_id` int(11) NOT NULL DEFAULT '0',
  `score_path` varchar(250) DEFAULT NULL,
  `file_path` longtext,
  `base64_data` longtext NOT NULL,
  `links` longtext,
  `file_text` longtext,
  `answerkey_path` text,
  `teacher_version` longtext,
  `annotation` longtext NOT NULL,
  `questionAnnotation` longtext NOT NULL,
  `content_type` int(11) NOT NULL COMMENT '1->Resources,2->Assignment,3->Assessment',
  `editor_type` int(11) NOT NULL DEFAULT '1' COMMENT '1-> KeyBoard, 2-> Text, 3-> Math, 4->Diagram',
  `tags` varchar(200) DEFAULT NULL,
  `content_format` int(11) NOT NULL COMMENT '1-> pdf 2-> links 3-> text 4-> HW',
  `total_questions` int(11) NOT NULL DEFAULT '0',
  `access` int(11) NOT NULL COMMENT '1->private(within school),2->private(within user),3->public, 4->private(within corporate)',
  `status` bigint(20) NOT NULL COMMENT '1->Active,2->Inactive,3->Suspended,4->Deleted,5->Draft',
  `profile_url` text,
  `profile_thumb_url` text,
  `publication_code` varchar(20) DEFAULT '1' COMMENT '1->Not Book',
  `download` int(11) NOT NULL DEFAULT '0',
  `allow_answer_key` int(11) DEFAULT '0' COMMENT '0->not allow, 1->allow',
  `created_by` bigint(20) NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint(20) NOT NULL,
  `modified_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `content_master`
--

CREATE TABLE `content_master` (
  `content_id` int(11) NOT NULL,
  `content_type` varchar(100) NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `content_test_detail`
--

CREATE TABLE `content_test_detail` (
  `content_detail_id` int(11) NOT NULL,
  `test_id` int(11) NOT NULL,
  `content_id` int(11) NOT NULL,
  `module_name` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `solving_time` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `interval_time` int(11) DEFAULT NULL,
  `subject` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(1) NOT NULL COMMENT '0->Inactive,1->Active',
  `created_by` int(11) NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `content_test_detail_log`
--

CREATE TABLE `content_test_detail_log` (
  `content_detail_id` int(11) NOT NULL,
  `test_id` int(11) NOT NULL,
  `content_id` int(11) NOT NULL,
  `module_name` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `solving_time` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `interval_time` int(11) DEFAULT NULL,
  `subject` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(1) NOT NULL COMMENT '0->Inactive,1->Active',
  `created_by` int(11) NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `log_sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `corporate`
--

CREATE TABLE `corporate` (
  `corporate_id` bigint(20) NOT NULL,
  `corporate_name` varchar(50) NOT NULL,
  `corporate_code` varchar(200) DEFAULT NULL,
  `status` int(11) NOT NULL COMMENT '1 - active 2 - inactive 3->suspended 4-> delete',
  `created_by` bigint(20) NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint(20) NOT NULL,
  `modified_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `corporate_request`
--

CREATE TABLE `corporate_request` (
  `request_id` bigint(20) NOT NULL,
  `corporate_id` bigint(20) NOT NULL,
  `school_id` bigint(20) NOT NULL,
  `status` int(11) NOT NULL DEFAULT '2' COMMENT '1->approved, 2->pending, 3->rejected',
  `validity` date DEFAULT NULL,
  `created_by` bigint(20) NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint(20) NOT NULL,
  `modified_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `country`
--

CREATE TABLE `country` (
  `id` int(11) NOT NULL,
  `code` varchar(3) NOT NULL,
  `name` varchar(150) NOT NULL,
  `dial_code` int(11) NOT NULL,
  `currency_name` varchar(20) NOT NULL,
  `currency_symbol` varchar(255) NOT NULL,
  `currency_code` varchar(20) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `course_class_mapping`
--

CREATE TABLE `course_class_mapping` (
  `id` int(11) UNSIGNED NOT NULL,
  `course_id` int(11) UNSIGNED NOT NULL,
  `class_id` int(11) UNSIGNED NOT NULL,
  `school_id` int(11) UNSIGNED NOT NULL,
  `auto_enroll` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Auto-enroll students in this class when course is added',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `crm_followups`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `crm_notes`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `date_format`
--

CREATE TABLE `date_format` (
  `date_id` bigint(20) NOT NULL,
  `date_format` longtext NOT NULL,
  `display_name` varchar(200) DEFAULT NULL,
  `example` varchar(200) DEFAULT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `email_attachments`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `essay_rubric`
--

CREATE TABLE `essay_rubric` (
  `rubricID` int(11) NOT NULL,
  `studentGrade` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `essayCriteria` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `maxScore` int(11) NOT NULL,
  `Status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exams`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `exam_scores`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `fee_plans`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `grade`
--

CREATE TABLE `grade` (
  `grade_id` bigint(20) NOT NULL,
  `grade_name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `school_id` bigint(20) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL,
  `sorting_no` int(11) NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `graph_answers`
--

CREATE TABLE `graph_answers` (
  `id` int(11) NOT NULL,
  `answer_id` bigint(20) NOT NULL,
  `question_no` bigint(20) NOT NULL,
  `content_id` bigint(20) NOT NULL,
  `class_id` bigint(20) NOT NULL,
  `student_id` bigint(20) NOT NULL,
  `correct_answer` longtext NOT NULL,
  `student_answer` longtext NOT NULL,
  `options` longtext,
  `actual_points` int(11) NOT NULL,
  `earned_points` int(11) DEFAULT '0',
  `answer_status` int(11) NOT NULL COMMENT '0->yet to start,1->incorrect,2->correct,3->partially correct,4->skipped,5-> Pending Verification',
  `auto_grade` int(11) NOT NULL DEFAULT '0',
  `feedback` text NOT NULL,
  `annotation` longtext,
  `created_by` bigint(20) NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint(20) NOT NULL,
  `modified_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `guardians`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `holiday_calendar`
--

CREATE TABLE `holiday_calendar` (
  `id` bigint(20) NOT NULL,
  `school_id` bigint(20) NOT NULL,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `festival_name` text NOT NULL,
  `created_by` bigint(20) NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint(20) NOT NULL,
  `modified_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `institution_announcement`
--

CREATE TABLE `institution_announcement` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `title` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `status` tinyint(1) NOT NULL COMMENT '1-Active,2-Inactive',
  `created_by` int(11) NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invite_users`
--

CREATE TABLE `invite_users` (
  `id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `path` longtext NOT NULL,
  `format` enum('Excel','Email') NOT NULL DEFAULT 'Excel',
  `user_type` varchar(100) NOT NULL,
  `status` int(11) NOT NULL DEFAULT '0',
  `created_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `knowledge_base_articles`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `knowledge_base_categories`
--

CREATE TABLE `knowledge_base_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `knowledge_base_links`
--

CREATE TABLE `knowledge_base_links` (
  `id` bigint(20) NOT NULL,
  `article_id` bigint(20) NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(2048) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mailbox`
--

CREATE TABLE `mailbox` (
  `message_id` bigint(20) NOT NULL,
  `parent_message_id` bigint(20) DEFAULT NULL,
  `class_id` bigint(20) NOT NULL,
  `from_id` bigint(20) NOT NULL,
  `to_id` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` int(11) NOT NULL DEFAULT '0' COMMENT '1->draft',
  `created_by` bigint(20) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` bigint(20) DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mailbox_attachment`
--

CREATE TABLE `mailbox_attachment` (
  `attachment_id` bigint(20) NOT NULL,
  `message_id` bigint(20) NOT NULL,
  `attachment` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '1-> Image, 2-> Link, 3->document',
  `created_by` bigint(20) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mailbox_details`
--

CREATE TABLE `mailbox_details` (
  `message_detail_id` bigint(20) NOT NULL,
  `message_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `is_read` int(11) NOT NULL DEFAULT '0',
  `mail_sent` int(11) NOT NULL DEFAULT '0' COMMENT '0->mail not sent,1->mail sent',
  `created_by` bigint(20) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `version` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL,
  `group` varchar(255) NOT NULL,
  `namespace` varchar(255) NOT NULL,
  `time` int(11) NOT NULL,
  `batch` int(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `note_comments`
--

CREATE TABLE `note_comments` (
  `id` bigint(20) NOT NULL,
  `note_id` bigint(20) NOT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `comment_date` datetime DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1->active, 2->deleted',
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_by` bigint(20) NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint(20) DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `notification_optouts`
--

CREATE TABLE `notification_optouts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `contact_type` enum('guardian','student') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'guardian',
  `contact_id` bigint(20) UNSIGNED NOT NULL,
  `channel` enum('sms','email') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'email',
  `reason` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_templates`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `notify_parents_requests`
--

CREATE TABLE `notify_parents_requests` (
  `id` bigint(20) NOT NULL,
  `school_id` bigint(20) NOT NULL,
  `student_id` bigint(20) NOT NULL,
  `class_id` bigint(20) NOT NULL,
  `content_id` bigint(20) NOT NULL,
  `student_content_id` bigint(20) NOT NULL,
  `status` int(11) NOT NULL DEFAULT '0' COMMENT '0->mail_not_sent, 1->mail_sent',
  `created_by` bigint(20) NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `page_master`
--

CREATE TABLE `page_master` (
  `id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint(1) NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `passage`
--

CREATE TABLE `passage` (
  `passage_id` bigint(20) NOT NULL,
  `title` text COLLATE utf8mb4_unicode_ci,
  `passage` longtext COLLATE utf8mb4_unicode_ci,
  `status` int(11) NOT NULL DEFAULT '1' COMMENT '1->Active,2->inactive',
  `created_by` int(11) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `payment_transactions`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `permission`
--

CREATE TABLE `permission` (
  `permission_id` int(11) NOT NULL,
  `controller` varchar(1000) NOT NULL,
  `status` int(11) NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `prereg_rate_limit`
--

CREATE TABLE `prereg_rate_limit` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempt_time` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `providers`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `provider_types`
--

CREATE TABLE `provider_types` (
  `id` int(11) NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'sms, email, payment',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `provider_usage_log`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `provider_usage_logs`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `question_skill`
--

CREATE TABLE `question_skill` (
  `id` bigint(20) NOT NULL,
  `skill` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` int(11) NOT NULL DEFAULT '1' COMMENT '1-> active, 2-> inactive',
  `created_by` int(11) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `question_standard`
--

CREATE TABLE `question_standard` (
  `id` bigint(20) NOT NULL,
  `question_standard` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` int(11) NOT NULL DEFAULT '1' COMMENT '1-> active, 2-> inactive',
  `created_by` int(11) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `question_topic`
--

CREATE TABLE `question_topic` (
  `question_topic_id` bigint(20) NOT NULL,
  `question_topic` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_id` int(11) NOT NULL,
  `status` int(11) NOT NULL DEFAULT '1' COMMENT '1-> active, 2-> inactive',
  `created_by` int(11) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `question_types`
--

CREATE TABLE `question_types` (
  `question_type_id` bigint(20) NOT NULL,
  `resource_type_id` bigint(20) NOT NULL,
  `question_type` varchar(200) NOT NULL,
  `image_path` text NOT NULL,
  `question_uploads` int(11) NOT NULL DEFAULT '0',
  `icon_path` text,
  `status` int(11) NOT NULL COMMENT '0->Inactive,1->Active',
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `ref_timezones`
--

CREATE TABLE `ref_timezones` (
  `id` int(10) UNSIGNED NOT NULL,
  `timezone` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `report_cards`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `resource_type_master`
--

CREATE TABLE `resource_type_master` (
  `resource_type_id` bigint(20) NOT NULL,
  `resource_type` varchar(200) NOT NULL,
  `status` int(11) NOT NULL COMMENT '0->Inactive,1->Active',
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `role_master`
--

CREATE TABLE `role_master` (
  `role_id` int(11) NOT NULL,
  `role` varchar(100) NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `role_permission`
--

CREATE TABLE `role_permission` (
  `id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `scheduled_payments`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `school`
--

CREATE TABLE `school` (
  `school_id` bigint(20) NOT NULL,
  `school_key` varchar(64) DEFAULT NULL,
  `portal_domain` varchar(150) DEFAULT NULL,
  `portal_contact_email` varchar(190) DEFAULT NULL,
  `portal_contact_phone` varchar(32) DEFAULT NULL,
  `name` varchar(1000) NOT NULL,
  `tax_id` varchar(100) DEFAULT NULL,
  `address1` text NOT NULL,
  `address2` text NOT NULL,
  `city` varchar(100) NOT NULL,
  `state` int(11) NOT NULL,
  `country` int(11) NOT NULL,
  `postal_code` varchar(20) NOT NULL,
  `has_branch` tinyint(1) NOT NULL,
  `branch_name` text NOT NULL,
  `school_website` longtext,
  `domain_name` varchar(300) DEFAULT NULL,
  `email_id` varchar(150) DEFAULT NULL,
  `profile_url` text,
  `profile_thumb_url` text,
  `status` tinyint(1) NOT NULL COMMENT '1->active, 2->Inactive, 3->payment pending',
  `institution_type` int(11) DEFAULT '1' COMMENT '1-> Public School, 2-> Coaching Center, 3-> Private School, 4->Learning Center, 5-> Tutoring',
  `trial` int(11) NOT NULL DEFAULT '0',
  `validity` date NOT NULL,
  `payment_status` char(1) DEFAULT 'Y',
  `display_until` date DEFAULT NULL,
  `created_by` bigint(20) NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint(20) NOT NULL,
  `modified_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `school_features`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `school_portal_settings`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `school_provider_config`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `school_provider_configs`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `school_registration_attribute_configs`
--

CREATE TABLE `school_registration_attribute_configs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `school_id` bigint(20) NOT NULL,
  `definition` json NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sms_templates`
--

CREATE TABLE `sms_templates` (
  `id` bigint(20) NOT NULL,
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
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `country_id` int(11) NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `static_website`
--

CREATE TABLE `static_website` (
  `id` bigint(20) NOT NULL,
  `name` varchar(50) NOT NULL,
  `email_id` varchar(50) NOT NULL,
  `mobile` varchar(50) NOT NULL,
  `school_name` varchar(50) NOT NULL,
  `state` varchar(50) NOT NULL,
  `city` varchar(50) NOT NULL,
  `requirement_message` longtext NOT NULL,
  `type` int(11) NOT NULL COMMENT '1-> contact us, 2-> demo',
  `status` int(11) NOT NULL COMMENT '1->mail sent, 2->mail_not _sent',
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `static_website_email_subscription`
--

CREATE TABLE `static_website_email_subscription` (
  `id` bigint(20) NOT NULL,
  `email_id` varchar(250) NOT NULL,
  `type` int(11) NOT NULL COMMENT '1->palssouthplainfield , 2->palsnortherns, 3->palsmarlboro.com, 4->palseastbrunswick, 5->palsmonroe.com, 6->palsoldbridge, 7->palsfreehold, 8->palspiscataway,9->edquill.com',
  `status` int(11) NOT NULL COMMENT '1->subscribed, 2->unsubscribed',
  `mail` int(11) NOT NULL COMMENT '1->sent,2->not_sent',
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `student_answerkey_request`
--

CREATE TABLE `student_answerkey_request` (
  `id` bigint(20) NOT NULL,
  `student_id` bigint(20) NOT NULL,
  `content_id` bigint(20) NOT NULL,
  `class_id` bigint(20) NOT NULL,
  `status` bigint(20) NOT NULL DEFAULT '0' COMMENT '0->Default, 1->Requested, 2->Rejected, 3->Accepted',
  `created_by` bigint(20) NOT NULL,
  `created_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `student_answers`
--

CREATE TABLE `student_answers` (
  `id` int(11) NOT NULL,
  `answer_id` bigint(20) NOT NULL,
  `question_no` bigint(20) NOT NULL,
  `content_id` bigint(20) NOT NULL,
  `class_id` bigint(20) NOT NULL,
  `student_id` bigint(20) NOT NULL,
  `class_content_id` bigint(20) NOT NULL,
  `student_content_id` bigint(20) NOT NULL,
  `correct_answer` longtext NOT NULL,
  `student_answer` longtext NOT NULL,
  `editor_answer` longtext,
  `student_answer_image` text,
  `options` longtext,
  `optionsCopy` longtext,
  `actual_points` int(11) NOT NULL,
  `earned_points` int(11) DEFAULT '0',
  `answer_status` int(11) NOT NULL COMMENT '0->yet to start,1->incorrect,2->correct,3->partially correct,4->skipped,5-> Pending Verification',
  `answer_attended` int(11) NOT NULL DEFAULT '0' COMMENT '0-> yet to start, 1-> answer, 2-> answered',
  `correction_status` int(11) NOT NULL DEFAULT '0' COMMENT '0 -> Not Corrected, 1 -> Corrected',
  `auto_grade` int(11) NOT NULL DEFAULT '0',
  `feedback` text NOT NULL,
  `annotation` longtext,
  `jiixdata` longtext,
  `roughdata` longtext,
  `workarea` longtext,
  `student_roughdata` text,
  `rough_image_url` text,
  `rough_image_thumb_url` text,
  `is_correct` varchar(100) NOT NULL,
  `no_of_attempt` int(11) NOT NULL DEFAULT '1',
  `time_taken` int(11) NOT NULL,
  `marked_review` tinyint(1) DEFAULT NULL,
  `module_id` int(11) DEFAULT NULL,
  `created_by` bigint(20) NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint(20) NOT NULL,
  `modified_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `student_answers_backup`
--

CREATE TABLE `student_answers_backup` (
  `id` int(11) NOT NULL,
  `answer_id` bigint(20) NOT NULL,
  `question_no` bigint(20) NOT NULL,
  `content_id` bigint(20) NOT NULL,
  `class_id` bigint(20) NOT NULL,
  `student_id` bigint(20) NOT NULL,
  `class_content_id` bigint(20) NOT NULL,
  `student_content_id` bigint(20) NOT NULL,
  `correct_answer` longtext NOT NULL,
  `student_answer` longtext NOT NULL,
  `editor_answer` longtext,
  `student_answer_image` text,
  `options` longtext,
  `optionsCopy` longtext,
  `actual_points` int(11) NOT NULL,
  `earned_points` int(11) DEFAULT '0',
  `answer_status` int(11) NOT NULL COMMENT '0->yet to start,1->incorrect,2->correct,3->partially correct,4->skipped,5-> Pending Verification',
  `answer_attended` int(11) NOT NULL DEFAULT '0' COMMENT '0-> yet to start, 1-> answer, 2-> answered',
  `correction_status` int(11) NOT NULL DEFAULT '0' COMMENT '0 -> Not Corrected, 1 -> Corrected',
  `auto_grade` int(11) NOT NULL DEFAULT '0',
  `feedback` text NOT NULL,
  `annotation` longtext,
  `jiixdata` longtext,
  `roughdata` longtext,
  `workarea` longtext,
  `student_roughdata` text,
  `rough_image_url` text,
  `rough_image_thumb_url` text,
  `is_correct` varchar(100) NOT NULL,
  `no_of_attempt` int(11) NOT NULL DEFAULT '1',
  `time_taken` int(11) NOT NULL,
  `created_by` bigint(20) NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint(20) NOT NULL,
  `modified_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `student_assign_content`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `student_class`
--

CREATE TABLE `student_class` (
  `id` bigint(20) NOT NULL,
  `class_id` bigint(20) NOT NULL,
  `from_class` bigint(20) DEFAULT '0',
  `student_id` bigint(20) NOT NULL,
  `validity` date NOT NULL,
  `status` int(11) NOT NULL COMMENT '0->Inactive, 1->Active, 2-> Saved,3->draft',
  `joining_date` date NOT NULL,
  `drafted_date` date NOT NULL,
  `notify_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 -> Send Notification, 0 -> Do not send',
  `class_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0->Normal,1->Transfer,2->Makeup',
  `created_by` bigint(20) NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint(20) NOT NULL,
  `modified_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `student_class_transfer`
--

CREATE TABLE `student_class_transfer` (
  `id` bigint(20) NOT NULL,
  `class_id` bigint(20) NOT NULL,
  `from_class` bigint(20) DEFAULT '0',
  `to_class` bigint(20) DEFAULT '0',
  `student_id` bigint(20) NOT NULL,
  `validity` date NOT NULL,
  `status` int(11) NOT NULL COMMENT '0->Inactive, 1->Active, 2-> Saved,3->draft',
  `joining_date` date NOT NULL,
  `drafted_date` date NOT NULL,
  `type` char(1) DEFAULT NULL COMMENT 'M->makeUpClass,T->TransferClass',
  `absent_date` date DEFAULT NULL,
  `created_by` bigint(20) NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint(20) NOT NULL,
  `modified_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `student_content`
--

CREATE TABLE `student_content` (
  `id` bigint(20) NOT NULL,
  `class_id` bigint(20) NOT NULL,
  `student_id` bigint(20) NOT NULL,
  `content_id` bigint(20) NOT NULL,
  `class_content_id` bigint(20) NOT NULL,
  `grade_id` bigint(20) NOT NULL,
  `laq_id` int(11) NOT NULL COMMENT 'Last Answered Question Id',
  `status` int(11) NOT NULL COMMENT '1->Yet_To_Start, 2->Inprogress, 3->Verified, 4-> Completed, 5-> Corrected,6->pending Verification',
  `draft_status` int(11) NOT NULL DEFAULT '0' COMMENT '1->draft_content,2->undrafted',
  `release_score` int(11) NOT NULL DEFAULT '0' COMMENT '0- No Release, 1-> Release',
  `parents_notify_count` int(11) NOT NULL DEFAULT '0',
  `annotation` longtext,
  `teacher_annotation` longtext,
  `answer_sheet_annotation` longtext,
  `feedback` text,
  `student_feedback` text,
  `upload_answer` longtext,
  `points` int(11) NOT NULL DEFAULT '0',
  `earned_points` int(11) NOT NULL DEFAULT '0',
  `sat_score` int(11) DEFAULT NULL,
  `rw_score` int(11) DEFAULT NULL,
  `math_score` int(11) DEFAULT NULL,
  `answer_request` bigint(20) NOT NULL DEFAULT '0' COMMENT '0->Default, 1->Requested, 2->Rejected, 3->Accepted',
  `answer_completed_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `correction_completed_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `score_release_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `drafted_date` datetime NOT NULL,
  `redo_test` tinyint(4) NOT NULL COMMENT 'redo_test_status -> 0 ,redo_test_status->1',
  `platform` int(11) NOT NULL DEFAULT '0' COMMENT '1->web,2->ios,3->mixed',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `content_started_at` datetime DEFAULT NULL,
  `content_time_taken` int(11) NOT NULL DEFAULT '0',
  `created_by` bigint(20) NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint(20) NOT NULL,
  `modified_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `student_content_class_access`
--

CREATE TABLE `student_content_class_access` (
  `id` bigint(20) NOT NULL,
  `student_content_id` bigint(20) NOT NULL,
  `class_id` bigint(20) NOT NULL,
  `class_content_id` bigint(20) NOT NULL,
  `created_date` datetime NOT NULL,
  `created_by` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `student_content_feedback`
--

CREATE TABLE `student_content_feedback` (
  `id` bigint(20) NOT NULL,
  `content_id` bigint(20) NOT NULL,
  `student_id` bigint(20) NOT NULL,
  `class_id` bigint(20) NOT NULL,
  `school_id` bigint(20) NOT NULL DEFAULT '0',
  `notes` longtext NOT NULL,
  `notes_type` int(11) NOT NULL DEFAULT '1' COMMENT '1->notes, 2 -email',
  `created_by` bigint(20) NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint(20) NOT NULL,
  `modified_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `student_content_module`
--

CREATE TABLE `student_content_module` (
  `id` bigint(20) NOT NULL,
  `student_content_id` bigint(20) NOT NULL,
  `module_id` int(11) NOT NULL,
  `laq_id` int(11) NOT NULL COMMENT 'Last Answered Question Id',
  `status` int(11) NOT NULL COMMENT '1->Yet_To_Start, 2->Inprogress, 3->Verified, 4-> Completed, 5-> Corrected,6->pending Verification',
  `draft_status` int(11) NOT NULL DEFAULT '0' COMMENT '1->draft_content,2->undrafted',
  `release_score` int(11) NOT NULL DEFAULT '0' COMMENT '0- No Release, 1-> Release',
  `parents_notify_count` int(11) NOT NULL DEFAULT '0',
  `feedback` text COLLATE utf8mb4_unicode_ci,
  `student_feedback` text COLLATE utf8mb4_unicode_ci,
  `upload_answer` longtext COLLATE utf8mb4_unicode_ci,
  `points` int(11) NOT NULL DEFAULT '0',
  `earned_points` int(11) NOT NULL DEFAULT '0',
  `answer_request` bigint(20) NOT NULL DEFAULT '0' COMMENT '0->Default, 1->Requested, 2->Rejected, 3->Accepted',
  `answer_completed_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `correction_completed_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `score_release_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `drafted_date` datetime NOT NULL,
  `redo_test` tinyint(4) NOT NULL COMMENT 'redo_test_status -> 0 ,redo_test_status->1',
  `platform` int(11) NOT NULL DEFAULT '0' COMMENT '1->web,2->ios,3->mixed',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `content_started_at` datetime DEFAULT NULL,
  `content_time_taken` int(11) NOT NULL DEFAULT '0',
  `created_by` bigint(20) NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint(20) NOT NULL,
  `modified_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_courses`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `student_custom_items`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `student_essays`
--

CREATE TABLE `student_essays` (
  `student_essay_id` bigint(20) NOT NULL,
  `student_content_id` bigint(20) NOT NULL,
  `question_id` bigint(20) NOT NULL,
  `question` varchar(10000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_answer` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `feedback` mediumtext COLLATE utf8mb4_unicode_ci,
  `essay_embedding` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_score` int(11) NOT NULL,
  `total_score` int(11) NOT NULL,
  `feedback_received` datetime NOT NULL,
  `prompt_token` int(11) DEFAULT NULL,
  `completion_token` int(11) DEFAULT NULL,
  `total_token` int(11) DEFAULT NULL,
  `total_cost` decimal(12,8) DEFAULT NULL,
  `time_taken` int(11) NOT NULL,
  `status` int(11) NOT NULL COMMENT '1->Active,0->InActive',
  `created_by` bigint(20) NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint(20) NOT NULL,
  `modified_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_fee_plans`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `student_guardians`
--

CREATE TABLE `student_guardians` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `guardian_id` bigint(20) UNSIGNED NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `relationship_override` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_overdue_notification`
--

CREATE TABLE `student_overdue_notification` (
  `id` int(11) NOT NULL,
  `school_id` bigint(20) NOT NULL,
  `class_id` bigint(20) NOT NULL,
  `content_id` bigint(20) NOT NULL,
  `student_id` bigint(20) NOT NULL,
  `status` int(11) NOT NULL,
  `mail_count` int(11) NOT NULL,
  `created_date` datetime NOT NULL,
  `created_by` int(11) NOT NULL,
  `modified_by` int(11) NOT NULL,
  `modified_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `student_payment_methods`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `student_self_registrations`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `student_self_registration_courses`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `student_self_registration_documents`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `student_self_registration_messages`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `student_self_registration_notes`
--

CREATE TABLE `student_self_registration_notes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `registration_id` bigint(20) UNSIGNED NOT NULL,
  `note_type` enum('internal','request','response','history') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'internal',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `metadata` json DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_suggestions`
--

CREATE TABLE `student_suggestions` (
  `id` bigint(20) NOT NULL,
  `student_id` bigint(20) NOT NULL,
  `content_id` bigint(20) NOT NULL,
  `class_id` bigint(20) NOT NULL,
  `answer_id` bigint(20) NOT NULL,
  `school_id` bigint(20) NOT NULL,
  `suggestion_query` text NOT NULL,
  `created_by` bigint(20) NOT NULL,
  `created_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `student_upgrade`
--

CREATE TABLE `student_upgrade` (
  `id` bigint(20) NOT NULL,
  `school_id` bigint(20) NOT NULL,
  `student_id` bigint(20) NOT NULL,
  `grade_id` varchar(50) NOT NULL,
  `joining_date` date NOT NULL,
  `dropped_date` datetime NOT NULL,
  `status` int(11) DEFAULT NULL,
  `created_by` bigint(20) NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint(20) NOT NULL,
  `modified_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `student_work`
--

CREATE TABLE `student_work` (
  `id` bigint(20) NOT NULL,
  `student_content_id` bigint(20) NOT NULL,
  `student_id` bigint(20) NOT NULL,
  `student_name` varchar(250) NOT NULL,
  `content_id` bigint(20) NOT NULL,
  `content_name` varchar(250) NOT NULL,
  `content_type` int(11) NOT NULL COMMENT '1->resource, 2-> Assignment, 3-> Assessment',
  `class_id` bigint(20) NOT NULL,
  `class_name` varchar(250) NOT NULL,
  `content_start_date` date NOT NULL,
  `content_end_date` date NOT NULL,
  `student_content_status` int(11) NOT NULL COMMENT '1->Yet_To_Start, 2->Inprogress, 3->Verified, 4-> Completed, 5-> Corrected,6->pending Verification',
  `draft_status` int(11) DEFAULT '0' COMMENT '1->draft_content,2->undrafted',
  `student_profile` text NOT NULL,
  `content_format` int(11) NOT NULL,
  `total_score` int(11) NOT NULL,
  `obtained_score` int(11) NOT NULL,
  `answer_completed_date` datetime NOT NULL,
  `correction_completed_date` datetime NOT NULL,
  `score_release_date` datetime NOT NULL,
  `status` int(11) NOT NULL DEFAULT '1' COMMENT '0->inactive, 1-> active',
  `score_released` int(11) NOT NULL COMMENT '0- No Release, 1-> Release',
  `created_by` bigint(20) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` bigint(20) NOT NULL,
  `modified_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `subject`
--

CREATE TABLE `subject` (
  `subject_id` bigint(20) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `school_id` bigint(20) NOT NULL DEFAULT '0',
  `fee_amount` decimal(10,2) DEFAULT NULL COMMENT 'Subject fee amount',
  `deposit_amount` decimal(10,2) DEFAULT NULL COMMENT 'Subject deposit amount',
  `status` tinyint(1) NOT NULL,
  `edquill_subject_id` int(11) NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `sub_topic`
--

CREATE TABLE `sub_topic` (
  `sub_topic_id` bigint(20) NOT NULL,
  `question_topic_id` bigint(20) NOT NULL,
  `sub_topic` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT '1' COMMENT '1-> active, 2-> inactive',
  `created_by` int(11) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tags`
--

CREATE TABLE `tags` (
  `id` bigint(20) NOT NULL,
  `tag_name` varchar(200) NOT NULL,
  `school_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `content_id` bigint(20) NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_career`
--

CREATE TABLE `tbl_career` (
  `id` int(11) NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address1` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `address2` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `basic_qualification` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `prefered_qualification` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A->active,I->inactive',
  `created_by` int(11) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_career_application`
--

CREATE TABLE `tbl_career_application` (
  `id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `resume_url` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `portfolio` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A->active,I->inactive',
  `created_by` int(11) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_cart`
--

CREATE TABLE `tbl_cart` (
  `id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `cart_data` text COLLATE utf8mb4_unicode_ci,
  `cart_type` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '''1'' -> cart, ''2''-> wishlist',
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_cart_details`
--

CREATE TABLE `tbl_cart_details` (
  `id` int(11) NOT NULL,
  `registration_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `cost` decimal(10,2) NOT NULL,
  `status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A-> active, I ->inactive',
  `created_by` int(11) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_cart_details_log`
--

CREATE TABLE `tbl_cart_details_log` (
  `id` int(11) NOT NULL,
  `registration_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `cost` decimal(10,2) NOT NULL,
  `status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A-> active, I ->inactive',
  `created_by` int(11) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_contact_us`
--

CREATE TABLE `tbl_contact_us` (
  `id` int(11) NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mobile` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `state` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `city` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` int(11) NOT NULL COMMENT '1-> contact us, 2-> demo',
  `status` int(11) NOT NULL COMMENT '1->mail sent, 2->mail_not_sent',
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_content`
--

CREATE TABLE `tbl_content` (
  `content_id` int(11) NOT NULL,
  `name` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_slug` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_id` int(11) NOT NULL,
  `category_id` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_id` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `short_description` longtext COLLATE utf8mb4_unicode_ci,
  `long_description` longtext COLLATE utf8mb4_unicode_ci,
  `author` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image` text COLLATE utf8mb4_unicode_ci,
  `status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A-> Active, I -> Inactive',
  `views` int(11) NOT NULL DEFAULT '0',
  `display_from` datetime NOT NULL,
  `display_until` datetime NOT NULL,
  `display_order` int(11) NOT NULL,
  `redirect_url` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `timing` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event_date` date DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_content_category`
--

CREATE TABLE `tbl_content_category` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entity_id` int(11) NOT NULL,
  `status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A-> Active, I -> Inactive',
  `path` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `display_order` int(11) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_content_seo`
--

CREATE TABLE `tbl_content_seo` (
  `seo_id` int(11) NOT NULL,
  `content_id` int(11) NOT NULL,
  `entity_id` int(11) NOT NULL,
  `meta_author` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_title` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_keywords` varchar(3000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_keyphrase` varchar(3000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_topic` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_subject` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_classification` varchar(3000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_robots` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_rating` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_audience` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `og_title` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `og_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `og_site_name` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `og_description` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `og_site_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `twitter_title` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `twitter_site` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `twitter_card` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `twitter_description` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `twitter_creator` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A-> Active, I -> Inactive',
  `created_by` int(11) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_content_seo_log`
--

CREATE TABLE `tbl_content_seo_log` (
  `seo_id` int(11) NOT NULL,
  `content_id` int(11) NOT NULL,
  `entity_id` int(11) NOT NULL,
  `meta_author` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_title` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_keywords` varchar(3000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_keyphrase` varchar(3000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_topic` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_subject` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_classification` varchar(3000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_robots` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_rating` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_audience` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `og_title` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `og_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `og_site_name` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `og_description` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `og_site_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `twitter_title` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `twitter_site` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `twitter_card` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `twitter_description` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `twitter_creator` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A-> Active, I -> Inactive',
  `created_by` int(11) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_coupon`
--

CREATE TABLE `tbl_coupon` (
  `coupon_id` int(11) NOT NULL,
  `entity_id` int(11) NOT NULL,
  `coupon_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `validity_from` date NOT NULL,
  `validity_to` date NOT NULL,
  `discount_type` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'P-percentage, A- Amount',
  `discount` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `course_based` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Y-yes,N->no',
  `course_id` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `no_of_users` int(11) NOT NULL,
  `status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A->Active,I->Inactive',
  `created_by` int(11) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_coupon_log`
--

CREATE TABLE `tbl_coupon_log` (
  `coupon_id` int(11) NOT NULL,
  `entity_id` int(11) NOT NULL,
  `coupon_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `validity_from` date NOT NULL,
  `validity_to` date NOT NULL,
  `discount_type` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'P-percentage, A- Amount',
  `discount` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `course_based` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Y-yes,N->no',
  `course_id` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `no_of_users` int(11) NOT NULL,
  `status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A->Active,I->Inactive',
  `created_by` int(11) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_course`
--

CREATE TABLE `tbl_course` (
  `course_id` int(11) NOT NULL,
  `course_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `seo_title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_id` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_id` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `grade_id` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `short_description` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `path` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `validity_start_date` date NOT NULL,
  `validity_end_date` date NOT NULL,
  `status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'D->draft,P->Ready for review,A->Approved,R->rework,C-cancel',
  `lessons` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `overview_content` text COLLATE utf8mb4_unicode_ci,
  `course_content` text COLLATE utf8mb4_unicode_ci,
  `prerequisites` text COLLATE utf8mb4_unicode_ci,
  `other_details` text COLLATE utf8mb4_unicode_ci,
  `documentation_requirements` text COLLATE utf8mb4_unicode_ci COMMENT 'Documentation requirements for student registration (e.g., Birth Certificate, Report Card, etc.)',
  `author` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fees` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fee_amount` double NOT NULL,
  `fee_term` int(2) NOT NULL,
  `billing_cycle_days` int(11) DEFAULT NULL COMMENT 'Billing frequency in days (null = one-time, positive = recurring)',
  `certified_course` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Y-yes, N- no',
  `multiple_schedule` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Y -> user can choose multiple schedule,N-> only one schedule can be chosen and registered',
  `schedule` tinyint(1) NOT NULL COMMENT '0->display course without schedule,1-> display course with schedule only',
  `entity_id` int(11) NOT NULL,
  `redirect_url` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_popular` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'N',
  `is_exclusive` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'N',
  `button_name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event` tinyint(1) NOT NULL COMMENT '0->not event,1->event',
  `display_order` int(11) NOT NULL,
  `contact_info` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_course_category`
--

CREATE TABLE `tbl_course_category` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_id` text COLLATE utf8mb4_unicode_ci,
  `entity_id` int(11) NOT NULL,
  `status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A-> Active, I -> Inactive',
  `path` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `display_order` int(11) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_course_category_log`
--

CREATE TABLE `tbl_course_category_log` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entity_id` int(11) NOT NULL,
  `status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A-> Active, I -> Inactive',
  `path` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `display_order` int(11) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_course_faq`
--

CREATE TABLE `tbl_course_faq` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `title` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `answer` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A-> Active, I -> Inactive',
  `entity_id` bigint(20) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_course_faq_log`
--

CREATE TABLE `tbl_course_faq_log` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `title` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `answer` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A-> Active, I -> Inactive',
  `entity_id` bigint(20) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_course_log`
--

CREATE TABLE `tbl_course_log` (
  `course_id` int(11) NOT NULL,
  `course_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `seo_title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_id` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_id` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `grade_id` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `short_description` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `path` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `validity_start_date` date NOT NULL,
  `validity_end_date` date NOT NULL,
  `status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'D->draft,P->Ready for review,A->Approved,R->rework,C-cancel',
  `lessons` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `overview_content` text COLLATE utf8mb4_unicode_ci,
  `course_content` text COLLATE utf8mb4_unicode_ci,
  `prerequisites` text COLLATE utf8mb4_unicode_ci,
  `other_details` text COLLATE utf8mb4_unicode_ci,
  `author` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fees` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `certified_course` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Y-yes, N- no',
  `multiple_schedule` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Y -> user can choose multiple schedule,N-> only one schedule can be chosen and registered',
  `schedule` tinyint(1) NOT NULL COMMENT '0->display course without schedule,1-> display course with schedule only',
  `entity_id` int(11) NOT NULL,
  `redirect_url` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_popular` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_exclusive` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `button_name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event` tinyint(1) NOT NULL COMMENT '0->not event,1->event',
  `display_order` int(11) NOT NULL,
  `contact_info` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_course_ratings`
--

CREATE TABLE `tbl_course_ratings` (
  `rating_id` int(11) NOT NULL,
  `course_detail_id` int(11) NOT NULL,
  `rating` decimal(10,2) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_course_reviews`
--

CREATE TABLE `tbl_course_reviews` (
  `review_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `review` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_course_schedule`
--

CREATE TABLE `tbl_course_schedule` (
  `schedule_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `schedule_title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `course_start_date` date DEFAULT NULL,
  `course_end_date` date DEFAULT NULL,
  `registration_start_date` date DEFAULT NULL,
  `registration_end_date` date DEFAULT NULL,
  `program_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_type` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'O-onetime,R-recurring',
  `payment_sub_type` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'W->weekly,M->monthly,Q->quarterly,H->half-yearly,A->annually',
  `course_type` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'O->online,I->inperson ',
  `location_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cost` decimal(10,2) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `actual_cost` decimal(10,2) NOT NULL,
  `total_slots` int(11) DEFAULT NULL,
  `slots_booked` int(11) NOT NULL DEFAULT '0',
  `status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A-> Active, I -> Inactive',
  `entity_id` int(11) NOT NULL,
  `edquill_class_id` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_course_seo`
--

CREATE TABLE `tbl_course_seo` (
  `seo_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `meta_author` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_title` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_keywords` varchar(3000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_keyphrase` varchar(3000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_topic` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_subject` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_classification` varchar(3000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_robots` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_rating` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_audience` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `og_title` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `og_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `og_site_name` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `og_description` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `og_site_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `twitter_title` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `twitter_site` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `twitter_card` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `twitter_description` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `twitter_creator` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A-> Active, I -> Inactive',
  `created_by` int(11) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_course_seo_log`
--

CREATE TABLE `tbl_course_seo_log` (
  `seo_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `meta_author` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_title` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_keywords` varchar(3000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_keyphrase` varchar(3000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_topic` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_subject` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_classification` varchar(3000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_robots` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_rating` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_audience` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `og_title` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `og_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `og_site_name` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `og_description` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `og_site_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `twitter_title` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `twitter_site` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `twitter_card` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `twitter_description` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `twitter_creator` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A-> Active, I -> Inactive',
  `created_by` int(11) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_entity_orders`
--

CREATE TABLE `tbl_entity_orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `entity_id` int(11) NOT NULL,
  `entity_branch_id` int(11) NOT NULL,
  `edquill_class_id` int(11) NOT NULL,
  `student_class_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_event`
--

CREATE TABLE `tbl_event` (
  `event_id` int(11) NOT NULL,
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `location` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_time` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `end_time` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `path` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_popular` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'N',
  `status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A-> Active, I -> Inactive',
  `created_by` int(11) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_location`
--

CREATE TABLE `tbl_location` (
  `location_id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A-> active, I ->inactive',
  `created_by` int(11) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_order`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `tbl_payment_details`
--

CREATE TABLE `tbl_payment_details` (
  `payment_id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL,
  `payment_date` datetime DEFAULT NULL,
  `currency_code` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_status` tinytext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '1->Success,2->Failed ,3->Cancelled',
  `transaction_details` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_response` varchar(3000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_payment_details_log`
--

CREATE TABLE `tbl_payment_details_log` (
  `payment_id` int(11) NOT NULL,
  `registration_id` int(11) NOT NULL,
  `payment_date` datetime NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_mode` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_details` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_registration`
--

CREATE TABLE `tbl_registration` (
  `registration_id` int(11) NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mobile` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gender` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'M-> male, F->female, O->others',
  `grade_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `cost` decimal(10,2) NOT NULL,
  `location_id` int(11) NOT NULL,
  `dob` date NOT NULL,
  `state_id` int(11) NOT NULL,
  `country_id` int(11) NOT NULL,
  `zipcode` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address1` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address2` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `refered_by` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A-> active, I ->inactive',
  `payment_status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'N -> not paid,F ->, free cost, P->partially paid, C -> payment completed, R -> refunded',
  `created_by` int(11) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_registration_details`
--

CREATE TABLE `tbl_registration_details` (
  `id` int(11) NOT NULL,
  `registration_id` int(11) NOT NULL,
  `relationship_id` int(11) NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mobile` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) NOT NULL,
  `modified_date` int(11) DEFAULT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_registration_details_log`
--

CREATE TABLE `tbl_registration_details_log` (
  `id` int(11) NOT NULL,
  `registration_id` int(11) NOT NULL,
  `relationship_id` int(11) NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mobile` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) NOT NULL,
  `modified_date` int(11) DEFAULT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_registration_log`
--

CREATE TABLE `tbl_registration_log` (
  `registration_id` int(11) NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mobile` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gender` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'M-> male, F->female, O->others',
  `grade_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `cost` decimal(10,2) NOT NULL,
  `location_id` int(11) NOT NULL,
  `dob` date NOT NULL,
  `state_id` int(11) NOT NULL,
  `country_id` int(11) NOT NULL,
  `zipcode` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address1` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address2` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `refered_by` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A-> active, I ->inactive',
  `payment_status` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'N -> not paid,F ->, free cost, P->partially paid, C -> payment completed, R -> refunded',
  `created_by` int(11) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_subscription`
--

CREATE TABLE `tbl_subscription` (
  `id` int(11) NOT NULL,
  `email_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_user_cart_details`
--

CREATE TABLE `tbl_user_cart_details` (
  `cart_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `cart_data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `shipping_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `sub_total` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `invoice_url` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teacher_action_notification`
--

CREATE TABLE `teacher_action_notification` (
  `action_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `role_id` bigint(20) NOT NULL,
  `class_id` bigint(20) NOT NULL DEFAULT '0',
  `content_id` bigint(20) NOT NULL DEFAULT '0',
  `school_id` bigint(20) NOT NULL,
  `action` text NOT NULL,
  `request_data` longtext NOT NULL,
  `edited_data` longtext NOT NULL,
  `message_content` text,
  `status` int(11) NOT NULL DEFAULT '0',
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `teacher_class_annotation`
--

CREATE TABLE `teacher_class_annotation` (
  `id` bigint(20) NOT NULL,
  `teacher_id` bigint(20) NOT NULL,
  `class_id` bigint(20) NOT NULL,
  `content_id` bigint(20) NOT NULL,
  `school_id` bigint(20) NOT NULL,
  `annotation` longtext NOT NULL,
  `created_by` bigint(20) NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint(20) NOT NULL,
  `modified_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `teacher_overall_feedback`
--

CREATE TABLE `teacher_overall_feedback` (
  `id` int(11) NOT NULL,
  `student_content_id` bigint(20) NOT NULL,
  `feedback` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `feedback_type` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A->Automatic,M->Manual',
  `version` int(11) DEFAULT NULL,
  `status` tinyint(4) NOT NULL COMMENT '1->Active,0->Inactive',
  `created_by` int(11) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `testimonial`
--

CREATE TABLE `testimonial` (
  `id` bigint(20) NOT NULL,
  `name` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `image` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `rating` int(11) NOT NULL,
  `status` int(11) NOT NULL COMMENT '1->active,2->inactive',
  `display_type` int(11) NOT NULL COMMENT '1 -> general, 2 -> learing center, 3 -> tutors, 4 -> publishers',
  `display_from` datetime NOT NULL,
  `display_until` datetime NOT NULL,
  `created_by` bigint(20) NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint(20) NOT NULL,
  `modified_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `test_type_master`
--

CREATE TABLE `test_type_master` (
  `test_type_id` int(11) NOT NULL,
  `test_type` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint(1) NOT NULL COMMENT '0->Inactive,1->Active',
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `text_questions`
--

CREATE TABLE `text_questions` (
  `question_id` int(11) NOT NULL,
  `content_id` bigint(20) NOT NULL,
  `question_type_id` bigint(20) NOT NULL,
  `sub_question_type_id` bigint(20) NOT NULL DEFAULT '0',
  `editor_context` longtext,
  `editor_type` int(11) NOT NULL DEFAULT '1' COMMENT '1-> KeyBoard, 2-> Text, 3-> Math, 4->Diagram',
  `question_no` bigint(20) NOT NULL,
  `sub_question_no` varchar(10) NOT NULL,
  `has_sub_question` int(11) NOT NULL,
  `question` longtext NOT NULL,
  `answer_instructions` longtext,
  `editor_answer` longtext,
  `options` longtext NOT NULL,
  `answer` longtext NOT NULL,
  `level` int(11) NOT NULL COMMENT '1->Easy, 2-> Medium, 3-> Hard',
  `heading_option` text NOT NULL,
  `multiple_response` int(11) NOT NULL DEFAULT '0',
  `audo_grade` int(11) NOT NULL DEFAULT '0',
  `points` decimal(10,0) NOT NULL,
  `exact_match` tinyint(1) NOT NULL,
  `hint` text NOT NULL,
  `explanation` longtext NOT NULL,
  `word_limit` bigint(20) NOT NULL,
  `scoring_instruction` longtext NOT NULL,
  `source` text,
  `target` text,
  `passage_id` int(11) NOT NULL,
  `subject_id` int(11) DEFAULT '0',
  `question_topic_id` int(11) DEFAULT '0',
  `question_sub_topic_id` int(11) DEFAULT '0',
  `question_standard` int(11) DEFAULT '0',
  `predicted_solving_time` varchar(50) DEFAULT NULL,
  `resource` text,
  `skill` varchar(1000) DEFAULT NULL,
  `created_by` bigint(20) NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint(20) NOT NULL,
  `modified_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `time_zone`
--

CREATE TABLE `time_zone` (
  `id` bigint(20) NOT NULL,
  `continents_id` bigint(20) NOT NULL,
  `time_zone` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `utc_timezone` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` int(11) NOT NULL DEFAULT '1',
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `time_zone_master`
--

CREATE TABLE `time_zone_master` (
  `id` bigint(20) NOT NULL,
  `continents_name` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` int(11) NOT NULL DEFAULT '1' COMMENT 'status - >1 active,0->inactive',
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `token`
--

CREATE TABLE `token` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `client_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token_prefix` char(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token_hash` varbinary(32) NOT NULL,
  `allowed_domain` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `last_used_at` timestamp NULL DEFAULT NULL,
  `revoked` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `topic`
--

CREATE TABLE `topic` (
  `topic_id` bigint(20) NOT NULL,
  `topic` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `class_id` bigint(20) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `display_order` int(11) NOT NULL,
  `status` int(11) NOT NULL DEFAULT '1' COMMENT '1-> active, 2-> inactive',
  `created_by` int(11) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tutor_applications`
--

CREATE TABLE `tutor_applications` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) CHARACTER SET utf8 NOT NULL,
  `last_name` varchar(100) CHARACTER SET utf8 NOT NULL,
  `email` varchar(255) CHARACTER SET utf8 NOT NULL,
  `phone` varchar(20) CHARACTER SET utf8 NOT NULL,
  `bio` text CHARACTER SET utf8 NOT NULL,
  `profile_image` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `degree` varchar(100) CHARACTER SET utf8 NOT NULL,
  `institution` varchar(255) CHARACTER SET utf8 NOT NULL,
  `graduation_year` varchar(4) CHARACTER SET utf8 NOT NULL,
  `subjects` json NOT NULL,
  `availability_days` json NOT NULL,
  `availability_slots` json NOT NULL,
  `teaching_subjects` json NOT NULL,
  `teaching_levels` json NOT NULL,
  `experience` text CHARACTER SET utf8 NOT NULL,
  `certifications` json NOT NULL,
  `status` varchar(20) CHARACTER SET utf8 NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `t_appt_availability`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `t_appt_booking`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `t_appt_exception`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `t_appt_guest`
--

CREATE TABLE `t_appt_guest` (
  `guest_id` bigint(20) UNSIGNED NOT NULL,
  `appt_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(120) DEFAULT NULL,
  `email` varchar(190) DEFAULT NULL,
  `role` enum('student','parent','other') NOT NULL DEFAULT 'other'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `t_appt_notification`
--

CREATE TABLE `t_appt_notification` (
  `notif_id` bigint(20) UNSIGNED NOT NULL,
  `appt_id` bigint(20) UNSIGNED NOT NULL,
  `channel` enum('email','sms') NOT NULL,
  `purpose` enum('confirmation','reschedule','cancel','reminder24h','reminder1h') NOT NULL,
  `status` enum('queued','sent','failed') NOT NULL DEFAULT 'queued',
  `provider_id` varchar(128) DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `t_audit_log`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `t_event_outbox`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `t_feature_flag`
--

CREATE TABLE `t_feature_flag` (
  `school_id` bigint(20) UNSIGNED NOT NULL,
  `flag_key` varchar(64) NOT NULL,
  `flag_value` text NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `t_marketing_kpi_daily`
--

CREATE TABLE `t_marketing_kpi_daily` (
  `school_id` bigint(20) UNSIGNED NOT NULL,
  `day` date NOT NULL,
  `source` varchar(64) NOT NULL DEFAULT '',
  `leads` int(11) NOT NULL DEFAULT '0',
  `enrollments` int(11) NOT NULL DEFAULT '0',
  `revenue_cents` bigint(20) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `t_message_log`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `t_message_template`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `t_revenue_daily`
--

CREATE TABLE `t_revenue_daily` (
  `school_id` bigint(20) UNSIGNED NOT NULL,
  `day` date NOT NULL,
  `mrr_cents` bigint(20) NOT NULL DEFAULT '0',
  `arr_cents` bigint(20) NOT NULL DEFAULT '0',
  `on_time_pay_pct` decimal(5,2) NOT NULL DEFAULT '0.00',
  `ar_overdue_cents` bigint(20) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `t_teacher_availability`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `updated_class_schedule`
--

CREATE TABLE `updated_class_schedule` (
  `id` bigint(20) NOT NULL,
  `class_id` bigint(20) NOT NULL,
  `teacher_id` text NOT NULL,
  `date` date NOT NULL,
  `start_time` varchar(20) NOT NULL,
  `end_time` varchar(20) NOT NULL,
  `school_id` bigint(20) NOT NULL,
  `slot_days` int(11) NOT NULL,
  `meeting_link` text NOT NULL,
  `meeting_id` text NOT NULL,
  `passcode` varchar(200) NOT NULL,
  `telephone_number` varchar(20) NOT NULL,
  `status` int(11) NOT NULL COMMENT '1-> Added, 2-> Deleted',
  `created_by` bigint(20) NOT NULL,
  `created_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `upgrade`
--

CREATE TABLE `upgrade` (
  `id` bigint(20) NOT NULL,
  `school_id` bigint(20) NOT NULL,
  `student_id` bigint(20) NOT NULL,
  `grade_id` varchar(50) NOT NULL,
  `active_date` datetime NOT NULL,
  `dropped_date` datetime NOT NULL,
  `created_by` bigint(20) NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint(20) NOT NULL,
  `modified_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `user_id` bigint(20) NOT NULL,
  `role_id` int(11) NOT NULL COMMENT '1-> SuperAdmin, 2->Admin, 3->ContentCreater, 4->Teacher, 5->Students, 6->Corporate, 7->Grader',
  `default_password` tinyint(1) DEFAULT '1',
  `random_token` varchar(200) DEFAULT NULL,
  `email_id` varchar(200) DEFAULT NULL,
  `mobile` varchar(50) DEFAULT NULL,
  `password` varchar(200) DEFAULT NULL,
  `status` int(11) NOT NULL COMMENT '1- Active, 2- Inactive, 3- Suspended, 4- Deleted',
  `school_id` varchar(100) NOT NULL DEFAULT '0',
  `corporate_id` bigint(20) NOT NULL DEFAULT '0',
  `individual_teacher` int(11) NOT NULL DEFAULT '0',
  `login_type` varchar(50) NOT NULL COMMENT 'web, google, facebook',
  `provider_id` varchar(200) DEFAULT NULL,
  `googleid_token` text,
  `token` text,
  `tc_status` int(11) NOT NULL DEFAULT '0' COMMENT '0-> T&C Not Accepted, 1-> T&C Accepted',
  `edquill_teacher_id` int(11) NOT NULL,
  `auto_generate_email_edquill` bigint(20) NOT NULL DEFAULT '0',
  `student_id` varchar(200) NOT NULL,
  `academy_user_id` int(11) NOT NULL,
  `source` varchar(50) DEFAULT NULL,
  `created_by` bigint(20) DEFAULT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint(20) DEFAULT NULL,
  `modified_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `user_address`
--

CREATE TABLE `user_address` (
  `address_id` bigint(20) NOT NULL,
  `address_type` int(11) NOT NULL COMMENT '1->teacher ,2-> student parent 1, 3-> student parent 2, 4-> Content Creater',
  `user_id` bigint(20) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email_ids` varchar(50) DEFAULT NULL,
  `address1` text,
  `address2` text,
  `city` varchar(128) DEFAULT NULL,
  `state` int(11) DEFAULT NULL,
  `country` int(11) NOT NULL DEFAULT '0',
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
  `id` bigint(20) NOT NULL,
  `role_id` int(11) NOT NULL,
  `permission_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `display_order` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `group_name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` int(11) NOT NULL DEFAULT '1',
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_permission_backup`
--

CREATE TABLE `user_permission_backup` (
  `id` bigint(20) NOT NULL,
  `role_id` int(11) NOT NULL,
  `permission_id` bigint(20) NOT NULL,
  `group_id` int(11) NOT NULL,
  `group_name` text NOT NULL,
  `description` text NOT NULL,
  `status` int(11) NOT NULL DEFAULT '1',
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `user_profile`
--

CREATE TABLE `user_profile` (
  `profile_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `first_name` varchar(200) NOT NULL,
  `last_name` varchar(200) DEFAULT NULL,
  `profile_url` text,
  `profile_thumb_url` text,
  `gender` varchar(20) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `currency` varchar(50) DEFAULT NULL,
  `created_by` bigint(20) NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` bigint(20) DEFAULT NULL,
  `modified_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `user_profile_details`
--

CREATE TABLE `user_profile_details` (
  `user_details_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `school_id` bigint(20) NOT NULL,
  `status` int(11) DEFAULT '1' COMMENT '1->active, 2->inactive',
  `individual_teacher` int(11) NOT NULL DEFAULT '0',
  `individual_role` int(11) NOT NULL DEFAULT '0' COMMENT '0->teacher,1->parent',
  `doj` date NOT NULL,
  `dropped_date` date NOT NULL,
  `next_billing_date` date DEFAULT NULL COMMENT 'Next billing date for automatic/manual billing at student level',
  `designation` varchar(100) DEFAULT NULL,
  `school_idno` varchar(20) DEFAULT NULL,
  `subject` varchar(50) NOT NULL,
  `grade_id` varchar(50) NOT NULL,
  `profile_subject` varchar(100) DEFAULT NULL,
  `profile_grade` varchar(100) DEFAULT NULL,
  `batch_id` bigint(20) NOT NULL,
  `batch_type` varchar(50) NOT NULL,
  `edit_status` int(11) NOT NULL DEFAULT '0' COMMENT '0 -> updated 1-> Not Updated',
  `upgrade_date` datetime NOT NULL,
  `allow_dashboard` int(11) NOT NULL DEFAULT '1',
  `created_by` int(11) NOT NULL,
  `created_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `user_role_permission`
--

CREATE TABLE `user_role_permission` (
  `id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `role_id` bigint(20) NOT NULL,
  `school_id` bigint(20) NOT NULL,
  `user_permission_id` bigint(20) NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `user_role_permission_backup`
--

CREATE TABLE `user_role_permission_backup` (
  `id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `role_id` bigint(20) NOT NULL,
  `school_id` bigint(20) NOT NULL,
  `user_permission_id` bigint(20) NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `user_security`
--

CREATE TABLE `user_security` (
  `security_id` bigint(20) NOT NULL,
  `login_time` datetime NOT NULL,
  `login_location` text,
  `login_device` int(11) DEFAULT NULL,
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
  `id` int(11) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `access_token` varchar(250) NOT NULL,
  `ip_address` varchar(100) NOT NULL,
  `status` int(11) NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_date` datetime DEFAULT NULL COMMENT 'Last modification timestamp',
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `user_uri_detail`
--

CREATE TABLE `user_uri_detail` (
  `id` int(11) NOT NULL,
  `uri_path` text COLLATE utf8mb4_unicode_ci,
  `front_end_url` text COLLATE utf8mb4_unicode_ci,
  `user_id` bigint(20) NOT NULL,
  `role_id` int(11) NOT NULL,
  `user_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `website_contact_us`
--

CREATE TABLE `website_contact_us` (
  `id` bigint(20) NOT NULL,
  `name` varchar(200) NOT NULL,
  `email_id` varchar(200) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `type` int(11) NOT NULL COMMENT '2-> palsnortherns, 1->palssouthplainfield, 3->palsmarlboro.com, 4->palseastbrunswick, 5->palsmonroe.com, 6->palsoldbridge, 7->palsfreehold, 8->palspiscataway',
  `sub_type` char(2) DEFAULT NULL COMMENT '1->Home,2->K-6 Math,3->Pre-Algebra,4->Algebra 1,5->Geometry,6->Algebra 2,7->Pre-Calculus,8->English,9->Reading-And-Writing,10->SAT-Prep,11->PSAT8-Prep,12->High-School-Prep,13->Physics-Honors,14->Chemistry-Honors,15->Biology-Honors,16->AP-Biology,17->AP-Chemistry,18->AP-Physics,19->AP-Calculus AB-BC,20->AP-Statistics',
  `status` int(11) NOT NULL COMMENT '1-> mail sent, 2-> mail not sent',
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `zoom_creation_email`
--

CREATE TABLE `zoom_creation_email` (
  `id` bigint(20) NOT NULL,
  `user_email` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `class_id` bigint(20) NOT NULL,
  `schedule_id` bigint(20) NOT NULL,
  `school_id` bigint(20) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `start_time` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `end_time` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slot_days` int(11) NOT NULL,
  `meeting_id` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `teacher_link` longtext COLLATE utf8mb4_unicode_ci,
  `student_link` longtext COLLATE utf8mb4_unicode_ci,
  `zoom_response` longtext COLLATE utf8mb4_unicode_ci,
  `created_date` datetime NOT NULL,
  `created_by` bigint(20) NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `zoom_recording`
--

CREATE TABLE `zoom_recording` (
  `id` bigint(20) NOT NULL,
  `class_id` bigint(20) NOT NULL,
  `school_id` bigint(20) NOT NULL,
  `meeting_id` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` bigint(20) NOT NULL,
  `created_date` datetime NOT NULL,
  `sys_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `zoom_token`
--

CREATE TABLE `zoom_token` (
  `id` int(11) NOT NULL,
  `access_token` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiry_date` datetime NOT NULL,
  `school_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD KEY `subject_id` (`subject_id`);

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
-- Indexes for table `course_class_mapping`
--
ALTER TABLE `course_class_mapping`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id_class_id` (`course_id`,`class_id`),
  ADD KEY `school_id` (`school_id`);

--
-- Indexes for table `crm_followups`
--
ALTER TABLE `crm_followups`
  ADD PRIMARY KEY (`followup_id`),
  ADD KEY `ix_followup_by_owner_due` (`school_id`,`owner_user_id`,`due_date`),
  ADD KEY `ix_followup_by_status_due` (`school_id`,`status`,`due_date`),
  ADD KEY `ix_followup_related` (`related_type`,`related_id`);

--
-- Indexes for table `crm_notes`
--
ALTER TABLE `crm_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_crm_notes_registration` (`registration_id`),
  ADD KEY `idx_crm_notes_student` (`student_user_id`),
  ADD KEY `idx_crm_notes_contact` (`contact_id`),
  ADD KEY `idx_crm_notes_entity` (`entity_type`,`entity_id`);

--
-- Indexes for table `email_attachments`
--
ALTER TABLE `email_attachments`
  ADD PRIMARY KEY (`AttachmentID`);

--
-- Indexes for table `essay_rubric`
--
ALTER TABLE `essay_rubric`
  ADD PRIMARY KEY (`rubricID`);

--
-- Indexes for table `exams`
--
ALTER TABLE `exams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_exams_school` (`school_id`),
  ADD KEY `idx_exams_term` (`school_id`,`term`);

--
-- Indexes for table `exam_scores`
--
ALTER TABLE `exam_scores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_exam_scores_exam_student` (`exam_id`,`student_id`);

--
-- Indexes for table `fee_plans`
--
ALTER TABLE `fee_plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_fee_plans_school` (`school_id`);

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
-- Indexes for table `guardians`
--
ALTER TABLE `guardians`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_guardians_school_email` (`school_id`,`email`),
  ADD UNIQUE KEY `ux_guardians_school_phone` (`school_id`,`phone`),
  ADD KEY `idx_guardians_school` (`school_id`);

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
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_invoices_number` (`invoice_number`),
  ADD KEY `idx_invoices_student` (`student_id`),
  ADD KEY `idx_invoices_assignment` (`student_fee_plan_id`);

--
-- Indexes for table `knowledge_base_articles`
--
ALTER TABLE `knowledge_base_articles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_title` (`title`);

--
-- Indexes for table `knowledge_base_categories`
--
ALTER TABLE `knowledge_base_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_name` (`name`);

--
-- Indexes for table `knowledge_base_links`
--
ALTER TABLE `knowledge_base_links`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_article_id` (`article_id`);

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
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `note_comments`
--
ALTER TABLE `note_comments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifications_school_status` (`school_id`,`status`),
  ADD KEY `idx_notifications_recipient` (`recipient_type`,`recipient_id`);

--
-- Indexes for table `notification_optouts`
--
ALTER TABLE `notification_optouts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_optouts_contact_channel` (`contact_type`,`contact_id`,`channel`);

--
-- Indexes for table `notification_templates`
--
ALTER TABLE `notification_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_templates_school_name` (`school_id`,`name`),
  ADD KEY `idx_templates_school` (`school_id`);

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
  ADD KEY `status` (`status`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_payments_receipt` (`receipt_number`),
  ADD KEY `idx_payments_student` (`student_id`),
  ADD KEY `idx_payments_plan` (`student_fee_plan_id`);

--
-- Indexes for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payment_method_id` (`payment_method_id`),
  ADD KEY `provider_id` (`provider_id`),
  ADD KEY `parent_transaction_id` (`parent_transaction_id`),
  ADD KEY `idx_student` (`student_id`,`created_at`),
  ADD KEY `idx_school` (`school_id`,`created_at`),
  ADD KEY `idx_status` (`status`,`created_at`),
  ADD KEY `idx_gateway` (`gateway_transaction_id`),
  ADD KEY `idx_type` (`transaction_type`,`status`),
  ADD KEY `idx_invoice` (`invoice_id`);

--
-- Indexes for table `permission`
--
ALTER TABLE `permission`
  ADD PRIMARY KEY (`permission_id`),
  ADD UNIQUE KEY `UNQ_controller` (`controller`),
  ADD KEY `IDX_status` (`status`);

--
-- Indexes for table `prereg_rate_limit`
--
ALTER TABLE `prereg_rate_limit`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip_time` (`ip_address`,`attempt_time`);

--
-- Indexes for table `providers`
--
ALTER TABLE `providers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_provider` (`provider_type_id`,`code`),
  ADD KEY `idx_active` (`is_active`,`provider_type_id`);

--
-- Indexes for table `provider_types`
--
ALTER TABLE `provider_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `provider_usage_log`
--
ALTER TABLE `provider_usage_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_school_date` (`school_id`,`created_at`),
  ADD KEY `idx_provider_date` (`provider_id`,`created_at`),
  ADD KEY `idx_action` (`action_type`,`status`,`created_at`),
  ADD KEY `idx_related` (`related_type`,`related_id`);

--
-- Indexes for table `provider_usage_logs`
--
ALTER TABLE `provider_usage_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pul_school` (`school_id`),
  ADD KEY `idx_pul_provider` (`provider_id`),
  ADD KEY `idx_pul_created` (`created_at`);

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
-- Indexes for table `ref_timezones`
--
ALTER TABLE `ref_timezones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_ref_timezones_timezone` (`timezone`);

--
-- Indexes for table `report_cards`
--
ALTER TABLE `report_cards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_report_cards_exam_student` (`exam_id`,`student_id`),
  ADD KEY `idx_report_cards_share` (`share_token`);

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
-- Indexes for table `scheduled_payments`
--
ALTER TABLE `scheduled_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payment_method_id` (`payment_method_id`),
  ADD KEY `idx_next_charge` (`next_charge_date`,`status`),
  ADD KEY `idx_student` (`student_id`,`status`),
  ADD KEY `idx_school` (`school_id`,`status`);

--
-- Indexes for table `school`
--
ALTER TABLE `school`
  ADD PRIMARY KEY (`school_id`),
  ADD UNIQUE KEY `ux_school_school_key` (`school_key`),
  ADD KEY `IDX_status` (`status`),
  ADD KEY `IDX_institution_type` (`institution_type`),
  ADD KEY `FK_school_country_idx` (`country`),
  ADD KEY `FK_school_state_idx` (`state`);

--
-- Indexes for table `school_features`
--
ALTER TABLE `school_features`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_school_feature` (`school_id`,`provider_type_id`),
  ADD KEY `provider_type_id` (`provider_type_id`),
  ADD KEY `idx_enabled` (`school_id`,`is_enabled`);

--
-- Indexes for table `school_portal_settings`
--
ALTER TABLE `school_portal_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_school_portal_settings_school` (`school_id`);

--
-- Indexes for table `school_provider_config`
--
ALTER TABLE `school_provider_config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_school_provider` (`school_id`,`provider_id`),
  ADD KEY `provider_id` (`provider_id`),
  ADD KEY `idx_enabled` (`school_id`,`is_enabled`),
  ADD KEY `idx_priority` (`school_id`,`provider_id`,`priority`);

--
-- Indexes for table `school_provider_configs`
--
ALTER TABLE `school_provider_configs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_school_provider` (`school_id`,`provider_id`),
  ADD KEY `fk_spc_provider` (`provider_id`);

--
-- Indexes for table `school_registration_attribute_configs`
--
ALTER TABLE `school_registration_attribute_configs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_school_registration_attribute_configs_school` (`school_id`);

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
-- Indexes for table `student_assign_content`
--
ALTER TABLE `student_assign_content`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_class_id` (`class_id`),
  ADD KEY `idx_content_id` (`content_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_class_content` (`class_id`,`content_id`);

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
  ADD UNIQUE KEY `unique_student_content` (`student_id`,`content_id`,`class_content_id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `content_id` (`content_id`),
  ADD KEY `idx_student_content_start_date` (`start_date`),
  ADD KEY `student_content_id_field_update_idx` (`id`),
  ADD KEY `FK_student_content_content_id_idx` (`content_id`),
  ADD KEY `FK_student_content_class_content_id_idx` (`class_content_id`),
  ADD KEY `IDX_status` (`status`),
  ADD KEY `IDX_draft_status` (`draft_status`),
  ADD KEY `idx_student_id_content_id` (`student_id`,`content_id`),
  ADD KEY `idx_class_content_id` (`class_content_id`);

--
-- Indexes for table `student_content_class_access`
--
ALTER TABLE `student_content_class_access`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_access` (`student_content_id`,`class_id`),
  ADD KEY `idx_student_content_id` (`student_content_id`),
  ADD KEY `idx_class_id` (`class_id`),
  ADD KEY `idx_class_content_id` (`class_content_id`);

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
-- Indexes for table `student_courses`
--
ALTER TABLE `student_courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_course` (`student_id`,`course_id`,`school_id`),
  ADD KEY `student_id_school_id` (`student_id`,`school_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `registration_id` (`registration_id`);

--
-- Indexes for table `student_custom_items`
--
ALTER TABLE `student_custom_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_school` (`student_id`,`school_id`),
  ADD KEY `idx_dates` (`start_date`,`end_date`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `student_essays`
--
ALTER TABLE `student_essays`
  ADD PRIMARY KEY (`student_essay_id`);

--
-- Indexes for table `student_fee_plans`
--
ALTER TABLE `student_fee_plans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_student_fee_plans_unique` (`student_id`,`fee_plan_id`,`start_date`),
  ADD KEY `idx_student_fee_plans_student` (`student_id`),
  ADD KEY `idx_student_fee_plans_plan` (`fee_plan_id`);

--
-- Indexes for table `student_guardians`
--
ALTER TABLE `student_guardians`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_student_guardians_unique` (`student_id`,`guardian_id`),
  ADD KEY `idx_student_guardians_guardian` (`guardian_id`),
  ADD KEY `idx_student_guardians_student` (`student_id`);

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
-- Indexes for table `student_payment_methods`
--
ALTER TABLE `student_payment_methods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `provider_id` (`provider_id`),
  ADD KEY `idx_student` (`student_id`,`is_active`),
  ADD KEY `idx_school` (`school_id`,`is_active`),
  ADD KEY `idx_default` (`student_id`,`is_default`),
  ADD KEY `idx_expires` (`expires_at`,`expiry_notification_sent`);

--
-- Indexes for table `student_self_registrations`
--
ALTER TABLE `student_self_registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_student_self_registrations_code` (`registration_code`),
  ADD KEY `idx_student_self_registrations_school` (`school_id`),
  ADD KEY `idx_student_self_registrations_email` (`email`),
  ADD KEY `ix_selfreg_queue` (`school_id`,`status`,`submitted_at`);

--
-- Indexes for table `student_self_registration_courses`
--
ALTER TABLE `student_self_registration_courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_registration_course` (`registration_id`,`course_id`,`schedule_id`),
  ADD KEY `idx_registration_courses_registration` (`registration_id`);

--
-- Indexes for table `student_self_registration_documents`
--
ALTER TABLE `student_self_registration_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_registration_documents_registration` (`registration_id`);

--
-- Indexes for table `student_self_registration_messages`
--
ALTER TABLE `student_self_registration_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_registration_messages_registration` (`registration_id`);

--
-- Indexes for table `student_self_registration_notes`
--
ALTER TABLE `student_self_registration_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_registration_notes_registration` (`registration_id`);

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
-- Indexes for table `tbl_order`
--
ALTER TABLE `tbl_order`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_course_id` (`course_id`),
  ADD KEY `idx_school_id` (`school_id`),
  ADD KEY `idx_payment_id` (`payment_id`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_created_date` (`created_date`),
  ADD KEY `idx_status` (`status`);

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
-- Indexes for table `t_appt_availability`
--
ALTER TABLE `t_appt_availability`
  ADD PRIMARY KEY (`availability_id`),
  ADD KEY `school_id_admin_user_id_dow` (`school_id`,`admin_user_id`,`dow`);

--
-- Indexes for table `t_appt_booking`
--
ALTER TABLE `t_appt_booking`
  ADD PRIMARY KEY (`appt_id`),
  ADD UNIQUE KEY `ux_host_slot` (`school_id`,`admin_user_id`,`start_at_utc`,`end_at_utc`),
  ADD KEY `school_id_start_at_utc` (`school_id`,`start_at_utc`),
  ADD KEY `school_id_admin_user_id_start_at_utc` (`school_id`,`admin_user_id`,`start_at_utc`);

--
-- Indexes for table `t_appt_exception`
--
ALTER TABLE `t_appt_exception`
  ADD PRIMARY KEY (`exception_id`),
  ADD KEY `school_id_admin_user_id_date` (`school_id`,`admin_user_id`,`date`);

--
-- Indexes for table `t_appt_guest`
--
ALTER TABLE `t_appt_guest`
  ADD PRIMARY KEY (`guest_id`),
  ADD UNIQUE KEY `ux_appt_email` (`appt_id`,`email`),
  ADD KEY `appt_id` (`appt_id`);

--
-- Indexes for table `t_appt_notification`
--
ALTER TABLE `t_appt_notification`
  ADD PRIMARY KEY (`notif_id`),
  ADD KEY `appt_id_purpose_sent_at` (`appt_id`,`purpose`,`sent_at`);

--
-- Indexes for table `t_audit_log`
--
ALTER TABLE `t_audit_log`
  ADD PRIMARY KEY (`audit_id`),
  ADD KEY `ix_audit_lookup` (`school_id`,`entity_type`,`entity_id`,`created_at`);

--
-- Indexes for table `t_event_outbox`
--
ALTER TABLE `t_event_outbox`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_outbox_lookup` (`school_id`,`event_type`,`created_at`),
  ADD KEY `ix_outbox_claim` (`processed_at`,`claimed_by`,`created_at`);

--
-- Indexes for table `t_feature_flag`
--
ALTER TABLE `t_feature_flag`
  ADD PRIMARY KEY (`school_id`,`flag_key`);

--
-- Indexes for table `t_marketing_kpi_daily`
--
ALTER TABLE `t_marketing_kpi_daily`
  ADD PRIMARY KEY (`school_id`,`day`,`source`);

--
-- Indexes for table `t_message_log`
--
ALTER TABLE `t_message_log`
  ADD PRIMARY KEY (`msg_id`),
  ADD KEY `ix_msg_by_parent` (`school_id`,`to_parent_id`,`sent_at`),
  ADD KEY `ix_msg_by_channel` (`school_id`,`channel`,`sent_at`);

--
-- Indexes for table `t_message_template`
--
ALTER TABLE `t_message_template`
  ADD PRIMARY KEY (`template_id`),
  ADD UNIQUE KEY `ux_tpl` (`school_id`,`channel`,`purpose`,`version`);

--
-- Indexes for table `t_revenue_daily`
--
ALTER TABLE `t_revenue_daily`
  ADD PRIMARY KEY (`school_id`,`day`);

--
-- Indexes for table `t_teacher_availability`
--
ALTER TABLE `t_teacher_availability`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_teacher_availability_unique` (`teacher_id`,`school_id`,`is_recurring`,`normalized_day_of_week`,`normalized_availability_date`,`start_time_utc`,`end_time_utc`),
  ADD KEY `idx_teacher_availability_teacher` (`teacher_id`),
  ADD KEY `idx_teacher_availability_school` (`school_id`),
  ADD KEY `idx_teacher_availability_recurring` (`is_recurring`,`day_of_week`);

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
  MODIFY `address_type_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_mail_notification`
--
ALTER TABLE `admin_mail_notification`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_settings`
--
ALTER TABLE `admin_settings`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_settings_school`
--
ALTER TABLE `admin_settings_school`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `answers`
--
ALTER TABLE `answers`
  MODIFY `answer_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `batch`
--
ALTER TABLE `batch`
  MODIFY `batch_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `blogger`
--
ALTER TABLE `blogger`
  MODIFY `blog_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `book`
--
ALTER TABLE `book`
  MODIFY `book_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `career`
--
ALTER TABLE `career`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `career_application`
--
ALTER TABLE `career_application`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cfs_reports`
--
ALTER TABLE `cfs_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `class`
--
ALTER TABLE `class`
  MODIFY `class_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `classroom_content`
--
ALTER TABLE `classroom_content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `class_attendance`
--
ALTER TABLE `class_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `class_content`
--
ALTER TABLE `class_content`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `class_dec_mig`
--
ALTER TABLE `class_dec_mig`
  MODIFY `class_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `class_mail_notification`
--
ALTER TABLE `class_mail_notification`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `class_notes`
--
ALTER TABLE `class_notes`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `class_schedule`
--
ALTER TABLE `class_schedule`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `content`
--
ALTER TABLE `content`
  MODIFY `content_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `content_copy_22`
--
ALTER TABLE `content_copy_22`
  MODIFY `content_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `content_master`
--
ALTER TABLE `content_master`
  MODIFY `content_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `content_test_detail`
--
ALTER TABLE `content_test_detail`
  MODIFY `content_detail_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `corporate`
--
ALTER TABLE `corporate`
  MODIFY `corporate_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `corporate_request`
--
ALTER TABLE `corporate_request`
  MODIFY `request_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `country`
--
ALTER TABLE `country`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_class_mapping`
--
ALTER TABLE `course_class_mapping`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `crm_followups`
--
ALTER TABLE `crm_followups`
  MODIFY `followup_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `crm_notes`
--
ALTER TABLE `crm_notes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_attachments`
--
ALTER TABLE `email_attachments`
  MODIFY `AttachmentID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `essay_rubric`
--
ALTER TABLE `essay_rubric`
  MODIFY `rubricID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exams`
--
ALTER TABLE `exams`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exam_scores`
--
ALTER TABLE `exam_scores`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fee_plans`
--
ALTER TABLE `fee_plans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grade`
--
ALTER TABLE `grade`
  MODIFY `grade_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `graph_answers`
--
ALTER TABLE `graph_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `guardians`
--
ALTER TABLE `guardians`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `holiday_calendar`
--
ALTER TABLE `holiday_calendar`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `institution_announcement`
--
ALTER TABLE `institution_announcement`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invite_users`
--
ALTER TABLE `invite_users`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `knowledge_base_articles`
--
ALTER TABLE `knowledge_base_articles`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `knowledge_base_categories`
--
ALTER TABLE `knowledge_base_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `knowledge_base_links`
--
ALTER TABLE `knowledge_base_links`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mailbox`
--
ALTER TABLE `mailbox`
  MODIFY `message_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mailbox_attachment`
--
ALTER TABLE `mailbox_attachment`
  MODIFY `attachment_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mailbox_details`
--
ALTER TABLE `mailbox_details`
  MODIFY `message_detail_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `note_comments`
--
ALTER TABLE `note_comments`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_optouts`
--
ALTER TABLE `notification_optouts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_templates`
--
ALTER TABLE `notification_templates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notify_parents_requests`
--
ALTER TABLE `notify_parents_requests`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `page_master`
--
ALTER TABLE `page_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `passage`
--
ALTER TABLE `passage`
  MODIFY `passage_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permission`
--
ALTER TABLE `permission`
  MODIFY `permission_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prereg_rate_limit`
--
ALTER TABLE `prereg_rate_limit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `providers`
--
ALTER TABLE `providers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `provider_types`
--
ALTER TABLE `provider_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `provider_usage_log`
--
ALTER TABLE `provider_usage_log`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `provider_usage_logs`
--
ALTER TABLE `provider_usage_logs`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `question_skill`
--
ALTER TABLE `question_skill`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `question_standard`
--
ALTER TABLE `question_standard`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `question_topic`
--
ALTER TABLE `question_topic`
  MODIFY `question_topic_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `question_types`
--
ALTER TABLE `question_types`
  MODIFY `question_type_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ref_timezones`
--
ALTER TABLE `ref_timezones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `report_cards`
--
ALTER TABLE `report_cards`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `resource_type_master`
--
ALTER TABLE `resource_type_master`
  MODIFY `resource_type_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `role_master`
--
ALTER TABLE `role_master`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `role_permission`
--
ALTER TABLE `role_permission`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `scheduled_payments`
--
ALTER TABLE `scheduled_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `school`
--
ALTER TABLE `school`
  MODIFY `school_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `school_features`
--
ALTER TABLE `school_features`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `school_portal_settings`
--
ALTER TABLE `school_portal_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `school_provider_config`
--
ALTER TABLE `school_provider_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `school_provider_configs`
--
ALTER TABLE `school_provider_configs`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `school_registration_attribute_configs`
--
ALTER TABLE `school_registration_attribute_configs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sms_templates`
--
ALTER TABLE `sms_templates`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `state`
--
ALTER TABLE `state`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `static_website`
--
ALTER TABLE `static_website`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `static_website_email_subscription`
--
ALTER TABLE `static_website_email_subscription`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_answerkey_request`
--
ALTER TABLE `student_answerkey_request`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_answers`
--
ALTER TABLE `student_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_answers_backup`
--
ALTER TABLE `student_answers_backup`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_assign_content`
--
ALTER TABLE `student_assign_content`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_class`
--
ALTER TABLE `student_class`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_class_transfer`
--
ALTER TABLE `student_class_transfer`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_content`
--
ALTER TABLE `student_content`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_content_class_access`
--
ALTER TABLE `student_content_class_access`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_content_feedback`
--
ALTER TABLE `student_content_feedback`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_content_module`
--
ALTER TABLE `student_content_module`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_courses`
--
ALTER TABLE `student_courses`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_custom_items`
--
ALTER TABLE `student_custom_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_essays`
--
ALTER TABLE `student_essays`
  MODIFY `student_essay_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_fee_plans`
--
ALTER TABLE `student_fee_plans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_guardians`
--
ALTER TABLE `student_guardians`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_overdue_notification`
--
ALTER TABLE `student_overdue_notification`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_payment_methods`
--
ALTER TABLE `student_payment_methods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_self_registrations`
--
ALTER TABLE `student_self_registrations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_self_registration_courses`
--
ALTER TABLE `student_self_registration_courses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_self_registration_documents`
--
ALTER TABLE `student_self_registration_documents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_self_registration_messages`
--
ALTER TABLE `student_self_registration_messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_self_registration_notes`
--
ALTER TABLE `student_self_registration_notes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_suggestions`
--
ALTER TABLE `student_suggestions`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_upgrade`
--
ALTER TABLE `student_upgrade`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_work`
--
ALTER TABLE `student_work`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subject`
--
ALTER TABLE `subject`
  MODIFY `subject_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sub_topic`
--
ALTER TABLE `sub_topic`
  MODIFY `sub_topic_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tags`
--
ALTER TABLE `tags`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_career`
--
ALTER TABLE `tbl_career`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_career_application`
--
ALTER TABLE `tbl_career_application`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_cart`
--
ALTER TABLE `tbl_cart`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_cart_details`
--
ALTER TABLE `tbl_cart_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_content`
--
ALTER TABLE `tbl_content`
  MODIFY `content_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_content_category`
--
ALTER TABLE `tbl_content_category`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_content_seo`
--
ALTER TABLE `tbl_content_seo`
  MODIFY `seo_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_coupon`
--
ALTER TABLE `tbl_coupon`
  MODIFY `coupon_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_course`
--
ALTER TABLE `tbl_course`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_course_category`
--
ALTER TABLE `tbl_course_category`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_course_faq`
--
ALTER TABLE `tbl_course_faq`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_course_ratings`
--
ALTER TABLE `tbl_course_ratings`
  MODIFY `rating_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_course_reviews`
--
ALTER TABLE `tbl_course_reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_course_schedule`
--
ALTER TABLE `tbl_course_schedule`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_course_seo`
--
ALTER TABLE `tbl_course_seo`
  MODIFY `seo_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_entity_orders`
--
ALTER TABLE `tbl_entity_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_event`
--
ALTER TABLE `tbl_event`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_location`
--
ALTER TABLE `tbl_location`
  MODIFY `location_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_order`
--
ALTER TABLE `tbl_order`
  MODIFY `order_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_payment_details`
--
ALTER TABLE `tbl_payment_details`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_registration`
--
ALTER TABLE `tbl_registration`
  MODIFY `registration_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_registration_details`
--
ALTER TABLE `tbl_registration_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_subscription`
--
ALTER TABLE `tbl_subscription`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_user_cart_details`
--
ALTER TABLE `tbl_user_cart_details`
  MODIFY `cart_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teacher_action_notification`
--
ALTER TABLE `teacher_action_notification`
  MODIFY `action_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teacher_class_annotation`
--
ALTER TABLE `teacher_class_annotation`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teacher_overall_feedback`
--
ALTER TABLE `teacher_overall_feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `testimonial`
--
ALTER TABLE `testimonial`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `test_type_master`
--
ALTER TABLE `test_type_master`
  MODIFY `test_type_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `text_questions`
--
ALTER TABLE `text_questions`
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `token`
--
ALTER TABLE `token`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `topic`
--
ALTER TABLE `topic`
  MODIFY `topic_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tutor_applications`
--
ALTER TABLE `tutor_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `t_appt_availability`
--
ALTER TABLE `t_appt_availability`
  MODIFY `availability_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `t_appt_booking`
--
ALTER TABLE `t_appt_booking`
  MODIFY `appt_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `t_appt_exception`
--
ALTER TABLE `t_appt_exception`
  MODIFY `exception_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `t_appt_guest`
--
ALTER TABLE `t_appt_guest`
  MODIFY `guest_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `t_appt_notification`
--
ALTER TABLE `t_appt_notification`
  MODIFY `notif_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `t_audit_log`
--
ALTER TABLE `t_audit_log`
  MODIFY `audit_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `t_event_outbox`
--
ALTER TABLE `t_event_outbox`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `t_message_log`
--
ALTER TABLE `t_message_log`
  MODIFY `msg_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `t_message_template`
--
ALTER TABLE `t_message_template`
  MODIFY `template_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `t_teacher_availability`
--
ALTER TABLE `t_teacher_availability`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `updated_class_schedule`
--
ALTER TABLE `updated_class_schedule`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `upgrade`
--
ALTER TABLE `upgrade`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `user_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_address`
--
ALTER TABLE `user_address`
  MODIFY `address_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_permission`
--
ALTER TABLE `user_permission`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_permission_backup`
--
ALTER TABLE `user_permission_backup`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_profile`
--
ALTER TABLE `user_profile`
  MODIFY `profile_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_profile_details`
--
ALTER TABLE `user_profile_details`
  MODIFY `user_details_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_role_permission`
--
ALTER TABLE `user_role_permission`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_role_permission_backup`
--
ALTER TABLE `user_role_permission_backup`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_security`
--
ALTER TABLE `user_security`
  MODIFY `security_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_token`
--
ALTER TABLE `user_token`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_uri_detail`
--
ALTER TABLE `user_uri_detail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `website_contact_us`
--
ALTER TABLE `website_contact_us`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `zoom_creation_email`
--
ALTER TABLE `zoom_creation_email`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `zoom_recording`
--
ALTER TABLE `zoom_recording`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `zoom_token`
--
ALTER TABLE `zoom_token`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `knowledge_base_links`
--
ALTER TABLE `knowledge_base_links`
  ADD CONSTRAINT `knowledge_base_links_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `knowledge_base_articles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD CONSTRAINT `payment_transactions_ibfk_1` FOREIGN KEY (`payment_method_id`) REFERENCES `student_payment_methods` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payment_transactions_ibfk_2` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`),
  ADD CONSTRAINT `payment_transactions_ibfk_3` FOREIGN KEY (`parent_transaction_id`) REFERENCES `payment_transactions` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `providers`
--
ALTER TABLE `providers`
  ADD CONSTRAINT `fk_providers_type` FOREIGN KEY (`provider_type_id`) REFERENCES `provider_types` (`id`),
  ADD CONSTRAINT `providers_ibfk_1` FOREIGN KEY (`provider_type_id`) REFERENCES `provider_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `provider_usage_log`
--
ALTER TABLE `provider_usage_log`
  ADD CONSTRAINT `provider_usage_log_ibfk_1` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `scheduled_payments`
--
ALTER TABLE `scheduled_payments`
  ADD CONSTRAINT `scheduled_payments_ibfk_1` FOREIGN KEY (`payment_method_id`) REFERENCES `student_payment_methods` (`id`);

--
-- Constraints for table `school_features`
--
ALTER TABLE `school_features`
  ADD CONSTRAINT `school_features_ibfk_1` FOREIGN KEY (`provider_type_id`) REFERENCES `provider_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `school_portal_settings`
--
ALTER TABLE `school_portal_settings`
  ADD CONSTRAINT `fk_school_portal_settings_school` FOREIGN KEY (`school_id`) REFERENCES `school` (`school_id`) ON DELETE CASCADE;

--
-- Constraints for table `school_provider_config`
--
ALTER TABLE `school_provider_config`
  ADD CONSTRAINT `school_provider_config_ibfk_1` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `school_registration_attribute_configs`
--
ALTER TABLE `school_registration_attribute_configs`
  ADD CONSTRAINT `fk_school_registration_attribute_configs_school` FOREIGN KEY (`school_id`) REFERENCES `school` (`school_id`) ON DELETE CASCADE;

--
-- Constraints for table `student_payment_methods`
--
ALTER TABLE `student_payment_methods`
  ADD CONSTRAINT `student_payment_methods_ibfk_1` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`);

--
-- Constraints for table `student_self_registrations`
--
ALTER TABLE `student_self_registrations`
  ADD CONSTRAINT `fk_student_self_registrations_school` FOREIGN KEY (`school_id`) REFERENCES `school` (`school_id`) ON DELETE CASCADE;

--
-- Constraints for table `student_self_registration_courses`
--
ALTER TABLE `student_self_registration_courses`
  ADD CONSTRAINT `fk_registration_courses_registration` FOREIGN KEY (`registration_id`) REFERENCES `student_self_registrations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_self_registration_documents`
--
ALTER TABLE `student_self_registration_documents`
  ADD CONSTRAINT `fk_registration_documents_registration` FOREIGN KEY (`registration_id`) REFERENCES `student_self_registrations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_self_registration_messages`
--
ALTER TABLE `student_self_registration_messages`
  ADD CONSTRAINT `fk_registration_messages_registration` FOREIGN KEY (`registration_id`) REFERENCES `student_self_registrations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_self_registration_notes`
--
ALTER TABLE `student_self_registration_notes`
  ADD CONSTRAINT `fk_registration_notes_registration` FOREIGN KEY (`registration_id`) REFERENCES `student_self_registrations` (`id`) ON DELETE CASCADE;
COMMIT;
