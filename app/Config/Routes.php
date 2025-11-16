<?php

namespace Config;

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();

// Global OPTIONS handler for CORS preflight requests
$routes->options('(:any)', function() {
    $response = service('response');
    $response->setStatusCode(200);
    return $response;
});

// Test API route
$routes->get('test-api', 'TestApi::index');

// Test Routes
$routes->get('test', 'TestController::index');
$routes->get('test/hello', 'TestController::hello');
$routes->get('test/hello/(:segment)', 'TestController::hello/$1');

// Public routes (no authentication required)
$routes->group('', function($routes) {
    // Authentication endpoints
    $routes->match(['GET', 'POST', 'OPTIONS'], 'auth/token', '\App\Controllers\Admin\Auth::token');
    
    // User registration and password reset
    $routes->match(['POST', 'OPTIONS'], 'user/login', 'User::login');
    $routes->match(['POST', 'OPTIONS'], 'user/create', 'User::create');
    $routes->match(['POST', 'OPTIONS'], 'user/logout', 'User::logout');
    $routes->match(['POST', 'OPTIONS'], 'user/refreshToken', 'User::refreshToken');
    $routes->match(['POST', 'OPTIONS'], 'user/register', 'User::register');
    $routes->match(['POST', 'OPTIONS'], 'user/forgotPassword', 'User::forgotPassword');
    $routes->match(['POST', 'OPTIONS'], 'user/resetPassword', 'User::resetPassword');
    
    // School registration
    $routes->post('school/registration', 'School::registration');
    $routes->post('school/timeZoneList', 'School::timeZoneList');
    
    // Common endpoints
    $routes->get('common/countries', 'Common::countries');
    $routes->get('common/states', 'Common::states');
    $routes->get('common/cities', 'Common::cities');
    $routes->get('common/timezones', 'Common::timezones');
    $routes->post('common/tagsList', 'Common::tagsList');
    $routes->post('common/country', 'Common::country');
    
    // Subject endpoints
    $routes->post('subject/list', 'Subject::list');
    
    // Student endpoints
    $routes->post('student/StudentFromClassList', 'Student::StudentFromClassList');
    
    // Self-registration portal (public)
    $routes->get('self-registration/config/(:segment)', 'SelfRegistration::config/$1');
    $routes->post('self-registration/submit', 'SelfRegistration::submit');
    $routes->post('self-registration/lookup', 'SelfRegistration::lookup');
    
    // Teacher endpoints
    $routes->post('teacher/list', 'Teacher::list');
    
    // Content Creator endpoints
    $routes->post('contentcreator/list', 'Contentcreator::list');
    
    // Content endpoints
    $routes->post('content/sortMaster', 'Content::sortMaster');
    $routes->post('content/testType', 'Content::testType');
    $routes->post('content/list', 'Content::list');
    $routes->post('content/contentDetail', 'Content::contentDetail');
    $routes->post('content/detail', 'Content::contentDetail'); // Alias for frontend compatibility
    $routes->post('content/listPassage', 'Content::listPassage');
    $routes->post('content/specifiedClassList', 'Content::specifiedClassList');
    $routes->post('content/question_types', 'Content::questionTypes');
    $routes->post('content/questiontypes', 'Content::questionTypes'); // Alias for frontend compatibility
    $routes->post('content/questionStandard', 'Content::questionStandard');
    $routes->post('content/questionTopic', 'Content::questionTopic');
    $routes->post('content/questionSubTopic', 'Content::questionSubTopic');
    $routes->post('content/addQuestion', 'Content::addQuestion');
    $routes->post('content/editQuestion', 'Content::editQuestion');
    $routes->post('content/deleteQuestion', 'Content::deleteQuestion');

    // CRM public endpoints
    $routes->get('crm/report/view/(:segment)', 'Admin\ReportCards::viewByToken/$1');

    $routes->group('api/appt', function($routes) {
        $routes->get('hosts', 'Appt\HostsController::index');
        $routes->get('availability', 'Appt\AvailabilityController::index');
        $routes->post('availability', 'Appt\AvailabilityController::save');
        $routes->get('exceptions', 'Appt\ExceptionsController::index');
        $routes->post('exceptions', 'Appt\ExceptionsController::save');
        $routes->get('slots', 'Appt\SlotsController::index');
        $routes->get('bookings', 'Appt\BookingsController::index');
        $routes->post('book', 'Appt\BookingsController::book');
        $routes->match(['GET', 'POST'], 'policy', 'Appt\SettingsController::policy');
        $routes->post('(:num)/reschedule', 'Appt\BookingsController::reschedule/$1');
        $routes->post('(:num)/cancel', 'Appt\BookingsController::cancel/$1');
    });
});

// Protected routes (authentication required)
$routes->group('', ['filter' => 'auth'], function($routes) {
    // User endpoints
    $routes->get('user/profile', 'User::profile');
    $routes->post('user/update', 'User::update');
    $routes->post('user/changePassword', 'User::changePassword');
    $routes->post('user/editProfile', 'User::editProfile');
    $routes->post('user/myProfile', 'User::myProfile');
    $routes->post('user/checkDetails', 'User::checkDetails');
    $routes->post('user/getUserDetail', 'User::getUserDetail');
    $routes->post('user/tcUpdate', 'User::tcUpdate');
    $routes->post('user/editStatus', 'User::editStatus');
    $routes->post('user/remove', 'User::remove');
    $routes->post('user/list', 'User::list');
    $routes->post('user/configValues', 'User::configValues');
    $routes->post('user/setPassword', 'User::setPassword');
    $routes->post('user/dashBoard', 'User::dashBoard');
    $routes->post('user/records', 'User::records');
    $routes->post('user/content', 'User::content');
    $routes->post('user/myProfile', 'User::myProfile');

    // Common endpoints
    $routes->post('common/settingList', 'Common::settingList');
    $routes->post('common/settingEdit', 'Common::settingEdit');
    
    // Student endpoints
    $routes->get('student', 'Student::index');
    $routes->post('student', 'Student::create');
    $routes->post('student/list', 'Student::list');
    $routes->post('student/add', 'Student::add');
    $routes->post('student/edit', 'Student::edit');
    $routes->post('student/update', 'Student::update');
    $routes->post('student/remove', 'Student::remove');
    $routes->post('student/detail', 'Student::detail');
    $routes->post('student/curriculumList', 'Student::curriculumList');
    $routes->post('student/classList', 'Student::classList');
    $routes->post('student/assessmentList', 'Student::assessmentList');
    $routes->post('student/assignmentList', 'Student::assignmentList');
    $routes->post('student/resourcesList', 'Student::resourcesList');
    $routes->post('student/studentAllClassList', 'Student::studentAllClassList');
    $routes->post('student/attendanceDetail', 'Student::attendanceDetail');
    
    // Self-registration admin actions
    $routes->post('admin/self-registration/list', 'Admin\SelfRegistration::list');
    $routes->post('admin/self-registration/detail', 'Admin\SelfRegistration::detail');
    $routes->post('admin/self-registration/status', 'Admin\SelfRegistration::updateStatus');
    $routes->post('admin/self-registration/summary', 'Admin\SelfRegistration::updateSummary');
    $routes->post('admin/self-registration/student', 'Admin\SelfRegistration::updateStudent');
    $routes->post('admin/self-registration/guardians', 'Admin\SelfRegistration::updateGuardians');
    $routes->post('admin/self-registration/note', 'Admin\SelfRegistration::addNote');
    $routes->post('admin/self-registration/message', 'Admin\SelfRegistration::sendMessage');
    $routes->post('admin/self-registration/promote', 'Admin\SelfRegistration::promote');
    $routes->post('admin/self-registration/document/review', 'Admin\SelfRegistration::reviewDocument');
    $routes->post('admin/self-registration/assignees', 'Admin\SelfRegistration::assignees');
    $routes->post('admin/self-registration/course-decisions', 'Admin\SelfRegistration::updateCourseDecisions');
    $routes->post('admin/self-registration/assign-class', 'Admin\SelfRegistration::assignClass');
    $routes->post('admin/self-registration/approve', 'Admin\SelfRegistration::approve');
    
    // Dashboard endpoints
    $routes->post('api/dashboard', 'Admin\Dashboard::getDashboard');
    $routes->get('api/dashboard', 'Admin\Dashboard::getDashboard');
    
    // CRM - Guardian management
    $routes->post('crm/guardians/list', 'Admin\Guardians::list');
    $routes->post('crm/guardians/save', 'Admin\Guardians::save');
    $routes->post('crm/guardians/assign', 'Admin\Guardians::assign');
    $routes->post('crm/guardians/remove', 'Admin\Guardians::remove');
    
    // Teacher endpoints
    $routes->get('teacher', 'Teacher::index');
    $routes->post('teacher', 'Teacher::create');
    $routes->post('teacher/list', 'Teacher::list');
    $routes->post('teacher/classList', 'Teacher::classList');
    $routes->post('teacher/add', 'Teacher::add');
    $routes->post('teacher/edit', 'Teacher::edit');
    $routes->post('teacher/update', 'Teacher::update');
    $routes->post('teacher/remove', 'Teacher::remove');
    $routes->post('teacher/detail', 'Teacher::detail');
    $routes->post('teacher/assignStudent', 'Teacher::assignStudent');
$routes->post('teacher/teacherassignStudent', 'Teacher::teacherassignStudent');
$routes->post('teacher/teacherassignStudentPrint', 'Teacher::teacherassignStudentPrint');
$routes->post('classes/viewAssignments', 'Classes::viewAssignments');
    $routes->post('teacher/studentAssessment', 'Teacher::studentAssessment');
    $routes->post('teacher/assessmentList', 'Teacher::assessmentList');
    $routes->post('teacher/assignmentList', 'Teacher::assignmentList');
    $routes->post('teacher/studentCorrectionList', 'Teacher::studentCorrectionList');
    $routes->post('teacher/studentAnswerList', 'Teacher::studentAnswerList');

    // Teacher availability endpoints
    $routes->group('availability', function($routes) {
        $routes->get('/', 'Availability::index');
        $routes->post('/', 'Availability::create');
        $routes->put('(:num)', 'Availability::update/$1');
        $routes->delete('(:num)', 'Availability::delete/$1');
        $routes->get('admin-view', 'Availability::adminView');
    });

    // CRM - Fees & billing
    $routes->post('crm/fees/plans', 'Admin\Fees::listPlans');
    $routes->post('crm/fees/plan/save', 'Admin\Fees::savePlan');
    $routes->post('crm/fees/assign', 'Admin\Fees::assignPlan');
    $routes->post('crm/fees/payment', 'Admin\Fees::recordPayment');
    $routes->post('crm/fees/student-overview', 'Admin\Fees::studentOverview');
    $routes->post('crm/fees/invoice', 'Admin\Fees::generateInvoice');
    
    // School endpoints
    $routes->get('school', 'School::index');
    $routes->post('school', 'School::create');
    $routes->post('school/list', 'School::list_post');
    $routes->post('school/add', 'School::registration_post');
    $routes->post('school/edit', 'School::edit_post');
    $routes->post('school/update', 'School::update');
    $routes->post('school/remove', 'School::remove');
    $routes->post('school/detail', 'School::detail');
    $routes->post('school/addAdmin', 'School::addAdmin');
    $routes->post('school/studentGradeList', 'School::studentGradeList');
    $routes->post('school/announcementList', 'School::announcementList');
    $routes->post('school/addAnnouncement', 'School::addAnnouncement');
    $routes->post('school/editAnnouncement', 'School::editAnnouncement');
    $routes->post('school/calendarList', 'School::calendarList_post');
    $routes->post('school/addHolidayCalendar', 'School::addHolidayCalendar_post');
    $routes->post('school/editHolidayCalendar', 'School::editHolidayCalendar_post');
    $routes->post('school/deleteHolidayCalendar', 'School::deleteHolidayCalendar_post');
    $routes->post('school/dateformat', 'School::dateformat_post');
    
    // Class endpoints
    $routes->get('class', 'Classes::index');
    $routes->post('class', 'Classes::create');
    $routes->post('class/list', 'Classes::list');
    $routes->post('class/add', 'Classes::add');
    $routes->post('classes/add', 'Classes::create');
    $routes->post('classes/create', 'Classes::create');
    $routes->post('classes/teacherList', 'Classes::teacherList');
    $routes->post('classes/list', 'Classes::list');
    $routes->post('classes/getCommentCount', 'Classes::getCommentCount');
    $routes->post('classes/classDetail', 'Classes::classDetail');
    $routes->post('classes/overallClassAttendance', 'Classes::overallClassAttendance');
    $routes->post('classes/zoomInstantCreation', 'Classes::zoomInstantCreation');
    $routes->post('classes/zoomPermission', 'Classes::zoomPermission');
    $routes->post('classes/attendance', 'Classes::attendance');
    $routes->post('classes/addStudent', 'Classes::addStudent');
    $routes->post('classes/removeStudent', 'Classes::removeStudent');
    $routes->post('classes/delete', 'Classes::deleteClass');
    $routes->post('classes/curriculumList', 'Classes::curriculumList');
    $routes->post('classes/topicList', 'Classes::topicList');
    $routes->post('classes/addTopic', 'Classes::addTopic');
    $routes->post('classes/updateTopic', 'Classes::updateTopic');
    $routes->post('classes/updateTopicOrder', 'Classes::updateTopicOrder');
    $routes->post('classes/addCurriculumTopic', 'Classes::addCurriculumTopic');
    $routes->post('classes/getClassNotes', 'Classes::getClassNotes');
    $routes->post('classes/classAddNotes', 'Classes::classAddNotes');
    $routes->post('classes/enrollStudent', 'Classes::enrollStudent');
    $routes->post('classes/slotList', 'Classes::slotList');
    $routes->post('classes/updateClass', 'Classes::updateClass');
    $routes->post('classes/edit', 'Classes::edit');
    $routes->post('class/edit', 'Classes::edit');
    $routes->post('class/update', 'Classes::update');
    $routes->post('class/remove', 'Classes::remove');
    $routes->post('class/detail', 'Classes::detail');
    $routes->post('class/teacherList', 'Classes::teacherList');
    $routes->post('class/getCommentCount', 'Classes::getCommentCount');
    
    // Sitecontent endpoints (Website Content Management) - Using CI4 controller
    $routes->post('sitecontent/categoryList', 'SitecontentCI4::categoryList');
    $routes->post('sitecontent/categoryAdd', 'SitecontentCI4::categoryAdd');
    $routes->post('sitecontent/categoryEdit', 'SitecontentCI4::categoryEdit');
    $routes->post('sitecontent/listContent', 'SitecontentCI4::listContent');
    $routes->post('sitecontent/addContent', 'SitecontentCI4::addContent');
    $routes->post('sitecontent/editContent', 'SitecontentCI4::editContent');
    $routes->post('sitecontent/seoList', 'SitecontentCI4::seoList');
    $routes->post('sitecontent/addSeo', 'SitecontentCI4::addSeo');
    $routes->post('sitecontent/editSeo', 'SitecontentCI4::editSeo');

    // CRM - Notifications
    $routes->post('crm/notifications/templates', 'Admin\Notifications::templates');
    $routes->post('crm/notifications/template/save', 'Admin\Notifications::saveTemplate');
    $routes->post('crm/notifications/queue', 'Admin\Notifications::queue');
    $routes->post('crm/notifications/list', 'Admin\Notifications::list');
    $routes->post('crm/notifications/optout', 'Admin\Notifications::setOptout');

    // Course endpoints
    $routes->get('course', 'Course::index');
    $routes->post('course', 'Course::create');
    $routes->post('course/list', 'Course::list');
    $routes->post('course/add', 'Course::add');
    $routes->post('course/edit', 'Course::edit');
    $routes->post('course/update', 'Course::update');
    $routes->post('course/remove', 'Course::remove');
    $routes->post('course/detail', 'Course::detail');
    $routes->post('course/seoList', 'Course::seoList');
    $routes->post('course/addSeo', 'Course::addSeo');
    $routes->post('course/updateSeo', 'Course::updateSeo');
    $routes->post('course/faqList', 'Course::faqList');
    $routes->post('course/addFaq', 'Course::addFaq');
    $routes->post('course/updateFaq', 'Course::updateFaq');
    $routes->post('course/orderList', 'Course::orderList');
    
    // Subject endpoints
    $routes->get('subject', 'Subject::index');
    $routes->post('subject', 'Subject::create');
    $routes->post('subject/list', 'Subject::list');
    $routes->post('subject/add', 'Subject::add');
    $routes->post('subject/edit', 'Subject::edit');
    $routes->post('subject/update', 'Subject::update');
    $routes->post('subject/remove', 'Subject::remove');
    
    // Category endpoints
    $routes->get('category', 'Category::index');
    $routes->post('category', 'Category::create');
    $routes->post('category/list', 'Category::list');
    $routes->post('category/add', 'Category::addCategory');
    $routes->post('category/edit', 'Category::editCategory');
    $routes->post('category/update', 'Category::update');
    $routes->post('category/remove', 'Category::remove');
    
    // Content endpoints
    $routes->get('content', 'Content::index');
    $routes->post('content', 'Content::create');
$routes->post('content/list', 'Content::list');
$routes->post('content/add', 'Content::add');
$routes->post('content/edit', 'Content::getEditContent');
$routes->post('content/update', 'Content::updateContent');
$routes->post('content/remove', 'Content::remove');
$routes->post('content/deleteContent', 'Content::deleteClassContent'); // Legacy alias for class content removal
$routes->post('content/detail', 'Content::detail');
    
    // Grade endpoints
    $routes->get('grade', 'Grade::index');
    $routes->post('grade', 'Grade::create');
    $routes->post('grade/list', 'Grade::list');
    $routes->post('grade/allStudentList', 'Grade::allStudentList');
    $routes->post('grade/add', 'Grade::add');
    $routes->post('grade/edit', 'Grade::edit');
    $routes->post('grade/update', 'Grade::update');
    $routes->post('grade/remove', 'Grade::remove');
    
    // Batch endpoints
    $routes->get('batch', 'Batch::index');
    $routes->post('batch', 'Batch::create');
    $routes->post('batch/list', 'Batch::list');
    $routes->post('batch/add', 'Batch::add');
    $routes->post('batch/edit', 'Batch::edit');
    $routes->post('batch/update', 'Batch::update');
    $routes->post('batch/remove', 'Batch::remove');
    
    // Book endpoints
    $routes->get('book', 'Book::index');
    $routes->post('book', 'Book::create');
    $routes->post('book/list', 'Book::list');
    $routes->post('book/add', 'Book::add');
    $routes->post('book/edit', 'Book::edit');
    $routes->post('book/update', 'Book::update');
    $routes->post('book/remove', 'Book::remove');
    
    // Content Creator endpoints
    $routes->get('contentcreator', 'Contentcreator::index');
    $routes->post('contentcreator', 'Contentcreator::create');
    $routes->post('contentcreator/list', 'Contentcreator::list');
    $routes->post('contentcreator/add', 'Contentcreator::add');
    $routes->post('contentcreator/edit', 'Contentcreator::edit');
    $routes->post('contentcreator/update', 'Contentcreator::update');
    $routes->post('contentcreator/remove', 'Contentcreator::remove');
    
    // Corporate endpoints
    $routes->get('corporate', 'Corporate::index');
    $routes->post('corporate', 'Corporate::create');
    $routes->post('corporate/list', 'Corporate::list');
    $routes->post('corporate/add', 'Corporate::add');
    $routes->post('corporate/edit', 'Corporate::edit');
    $routes->post('corporate/update', 'Corporate::update');
    $routes->post('corporate/remove', 'Corporate::remove');
    
    // Report endpoints
    $routes->get('report/student', 'Report::student');
    $routes->get('report/teacher', 'Report::teacher');
    $routes->get('report/school', 'Report::school');
    $routes->post('report/studentReportClassPrint', 'Report::studentReportClassPrint');
    $routes->post('report/classPerformanceList', 'Report::classPerformanceList');
    $routes->post('report/classList', 'Report::classList');
    $routes->post('report/assessmentList', 'Report::assessmentList');
    $routes->post('report/assignmentList', 'Report::assignmentList');
    $routes->post('report/assessmentReports', 'Report::assessmentReports');
    $routes->post('report/assignmentReports', 'Report::assignmentReports');
    $routes->post('report/gradeReport', 'Report::gradeReport');
    $routes->post('report/studentPerformanceContent', 'Report::studentPerformanceContent');
    
    // CRM - Report cards
    $routes->post('crm/report/exams', 'Admin\ReportCards::listExams');
    $routes->post('crm/report/exam/save', 'Admin\ReportCards::saveExam');
    $routes->post('crm/report/scores', 'Admin\ReportCards::scores');
    $routes->post('crm/report/scores/save', 'Admin\ReportCards::saveScores');
    $routes->post('crm/report/generate', 'Admin\ReportCards::generate');
    $routes->post('crm/report/share', 'Admin\ReportCards::share');
    
    // Feedback endpoints
    $routes->get('feedback', 'Feedback::index');
    $routes->post('feedback', 'Feedback::create');
    $routes->post('feedback/list', 'Feedback::list');
    $routes->post('feedback/add', 'Feedback::add');
    $routes->post('feedback/edit', 'Feedback::edit');
    $routes->post('feedback/update', 'Feedback::update');
    $routes->post('feedback/remove', 'Feedback::remove');
    
    // Mailbox endpoints
    $routes->get('mailbox', 'Mailbox::index');
    $routes->post('mailbox', 'Mailbox::create');
    $routes->post('mailbox/listMessages', 'Mailbox::listMessages');
    $routes->post('mailbox/getMessageCount', 'MailboxCI4::getMessageCount');  // Use CI4 controller
    $routes->post('mailbox/send', 'Mailbox::send');
    $routes->post('mailbox/reply', 'Mailbox::reply');
    
    // Common protected endpoints
    $routes->post('common/fileUpload', 'Common::fileUpload');
    $routes->post('common/availabilityTimeCheck', 'Common::availabilityTimeCheck');
    
    // Zoom API endpoints
    $routes->post('zoom/token', 'Api::zoomTokenGeneration');
    $routes->post('zoom/meeting/create', 'Api::ZoomMeetingCreate');
    
    // Essay Grader endpoints
    $routes->post('essaygrader/grade', 'EssayGrader::grade');
    $routes->get('essaygrader/models', 'EssayGrader::getModels');
    $routes->post('essaygrader/validate-model', 'EssayGrader::validateModel');
    $routes->post('essaygrader/history', 'EssayGrader::getHistory');
    $routes->post('essaygrader/stats', 'EssayGrader::getStats');
    
    // LMS endpoints
    $routes->post('lms/integrations', 'Lms::getIntegrations');
    $routes->post('lms/add-integration', 'Lms::addIntegration');
    $routes->post('lms/update-integration', 'Lms::updateIntegration');
    $routes->post('lms/delete-integration', 'Lms::deleteIntegration');
    $routes->post('lms/test-connection', 'Lms::testConnection');
    $routes->post('lms/sync-data', 'Lms::syncData');
    $routes->post('lms/courses', 'Lms::getCourses');
    $routes->post('lms/students', 'Lms::getStudents');
    $routes->post('lms/assignments', 'Lms::getAssignments');
    $routes->get('lms/supported-types', 'Lms::getSupportedTypes');
    
    // Model Config endpoints
    $routes->post('modelconfig/configs', 'ModelConfig::getConfigs');
    $routes->post('modelconfig/update', 'ModelConfig::updateConfig');
    $routes->get('modelconfig/available-models', 'ModelConfig::getAvailableModels');
    $routes->post('modelconfig/test', 'ModelConfig::testConfig');
    $routes->post('modelconfig/stats', 'ModelConfig::getStats');
    $routes->post('modelconfig/reset', 'ModelConfig::resetConfig');
});

// Admin routes (admin authentication required)
$routes->group('', ['filter' => 'admin'], function($routes) {
    // Admin Settings
    $routes->post('settings/list', 'Settings::list');
    $routes->post('settings/update', 'Settings::update');
    
    // Admin User Management
    $routes->post('user/adminList', 'User::adminList');
    $routes->post('user/addAdmin', 'User::addAdmin');
    $routes->post('user/updateAdmin', 'User::updateAdmin');
    
    // Admin School Management
    $routes->post('school/adminList', 'School::adminList');
    $routes->post('school/adminAdd', 'School::adminAdd');
    $routes->post('school/adminEdit', 'School::adminEdit');
    $routes->post('school/adminUpdate', 'School::adminUpdate');
    $routes->post('school/adminRemove', 'School::adminRemove');
    
    // Admin Student Management
    $routes->post('student/adminList', 'Student::adminList');
    $routes->post('student/adminAdd', 'Student::adminAdd');
    $routes->post('student/adminEdit', 'Student::adminEdit');
    $routes->post('student/adminUpdate', 'Student::adminUpdate');
    $routes->post('student/adminRemove', 'Student::adminRemove');
    
    // Admin Teacher Management
    $routes->post('teacher/adminList', 'Teacher::adminList');
    $routes->post('teacher/adminAdd', 'Teacher::adminAdd');
    $routes->post('teacher/adminEdit', 'Teacher::adminEdit');
    $routes->post('teacher/adminUpdate', 'Teacher::adminUpdate');
    $routes->post('teacher/adminRemove', 'Teacher::adminRemove');
    
    // Admin Content Management
    $routes->post('content/adminList', 'Content::adminList');
    $routes->post('content/adminAdd', 'Content::adminAdd');
    $routes->post('content/adminEdit', 'Content::adminEdit');
    $routes->post('content/adminUpdate', 'Content::adminUpdate');
    $routes->post('content/adminRemove', 'Content::adminRemove');
    
    // Admin Category Management
    $routes->post('category/adminList', 'Category::adminList');
    $routes->post('category/adminAdd', 'Category::adminAdd');
    $routes->post('category/adminEdit', 'Category::adminEdit');
    $routes->post('category/adminUpdate', 'Category::adminUpdate');
    $routes->post('category/adminRemove', 'Category::adminRemove');
    
    // Admin Subject Management
    $routes->post('subject/adminList', 'Subject::adminList');
    $routes->post('subject/adminAdd', 'Subject::adminAdd');
    $routes->post('subject/adminEdit', 'Subject::adminEdit');
    $routes->post('subject/adminUpdate', 'Subject::adminUpdate');
    $routes->post('subject/adminRemove', 'Subject::adminRemove');
    
    // Admin Grade Management
    $routes->post('grade/adminList', 'Grade::adminList');
    $routes->post('grade/adminAdd', 'Grade::adminAdd');
    $routes->post('grade/adminEdit', 'Grade::adminEdit');
    $routes->post('grade/adminUpdate', 'Grade::adminUpdate');
    $routes->post('grade/adminRemove', 'Grade::adminRemove');
    
    // Admin Batch Management
    $routes->post('batch/adminList', 'Batch::adminList');
    $routes->post('batch/adminAdd', 'Batch::adminAdd');
    $routes->post('batch/adminEdit', 'Batch::adminEdit');
    $routes->post('batch/adminUpdate', 'Batch::adminUpdate');
    $routes->post('batch/adminRemove', 'Batch::adminRemove');
    
    // Admin Book Management
    $routes->post('book/adminList', 'Book::adminList');
    $routes->post('book/adminAdd', 'Book::adminAdd');
    $routes->post('book/adminEdit', 'Book::adminEdit');
    $routes->post('book/adminUpdate', 'Book::adminUpdate');
    $routes->post('book/adminRemove', 'Book::adminRemove');
    
    // Admin Content Creator Management
    $routes->post('contentcreator/adminList', 'Contentcreator::adminList');
    $routes->post('contentcreator/adminAdd', 'Contentcreator::adminAdd');
    $routes->post('contentcreator/adminEdit', 'Contentcreator::adminEdit');
    $routes->post('contentcreator/adminUpdate', 'Contentcreator::adminUpdate');
    $routes->post('contentcreator/adminRemove', 'Contentcreator::adminRemove');
    
    // Admin Corporate Management
    $routes->post('corporate/adminList', 'Corporate::adminList');
    $routes->post('corporate/adminAdd', 'Corporate::adminAdd');
    $routes->post('corporate/adminEdit', 'Corporate::adminEdit');
    $routes->post('corporate/adminUpdate', 'Corporate::adminUpdate');
    $routes->post('corporate/adminRemove', 'Corporate::adminRemove');
    
    // Admin Report Management
    $routes->post('report/adminStudent', 'Report::adminStudent');
    $routes->post('report/adminTeacher', 'Report::adminTeacher');
    $routes->post('report/adminSchool', 'Report::adminSchool');
    
    // Admin Feedback Management
    $routes->post('feedback/adminList', 'Feedback::adminList');
    $routes->post('feedback/adminAdd', 'Feedback::adminAdd');
    $routes->post('feedback/adminEdit', 'Feedback::adminEdit');
    $routes->post('feedback/adminUpdate', 'Feedback::adminUpdate');
    $routes->post('feedback/adminRemove', 'Feedback::adminRemove');
    
    // Admin Mailbox Management
    $routes->post('mailbox/adminList', 'Mailbox::adminList');
    $routes->post('mailbox/adminSend', 'Mailbox::adminSend');
    $routes->post('mailbox/adminReply', 'Mailbox::adminReply');
});

// The Auto Routing (Legacy) is very dangerous. It is easy to create vulnerable apps
// where controller filters or CSRF protection are bypassed.
// If you don't want to define all routes, please use the Auto Routing (Improved).
// Set `$autoRoutesImproved` to true in `app/Config/Feature.php` and set the following to true.
// $routes->setAutoRoute(false);

/*
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */

// We get a performance increase by specifying the default
// route since we don't have to scan directories.
$routes->get('/', 'Home::index');
