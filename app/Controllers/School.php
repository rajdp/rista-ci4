<?php
namespace App\Controllers;

use App\Libraries\LegacyBenchmarkTimer;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: AccessToken");
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');

class School extends ResourceController
{
    protected $jsonarr = array();
    protected $headers;
    protected $urlAuth;
    protected $controller;
    protected $format = 'json';
    protected $school_model;
    protected $common_model;
    protected $settings = [];
    protected $benchmark;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        
        $this->school_model = new \App\Models\V1\SchoolModel();
        $this->common_model = new \App\Models\V1\CommonModel();
        $this->benchmark = new LegacyBenchmarkTimer();
        $adminSettings = $this->adminSettings('');
        foreach ($adminSettings as $key => $details) {
            $this->settings[$details['setting_name']] = $details['setting_value'];
        }


        header("Access-Control-Allow-Origin: *");
        $this->controller = uri_string();
        $urlAuth = $this->verifyAuthUrl();
        
        if ($urlAuth) {
            $excludeurl = $this->excludefunction();
            if ($excludeurl != 'true') {
                $accessToken = $this->request->getHeaderLine('Accesstoken');
                if ($accessToken && !empty($accessToken)) {
                    $this->response->setStatusCode(200);
                } else {
                    $this->jsonarr['ErrorObject'] = "Unauthorized User";
                    $this->jsonarr['IsSuccess'] = false;
                    $this->response->setJSON($this->jsonarr);
                    $this->response->setStatusCode(401);
                    exit();
                }

            } else {
                $this->response->setStatusCode(200);
                return true;
            }
        } else {
            $this->response->setStatusCode(200);
            $this->jsonarr['ErrorObject'] = "The requested url is not found.";
            $this->jsonarr['IsSuccess'] = false;
            $this->response->setJSON($this->jsonarr);
            exit();
        }
    }

    public function adminSettings($name)
    {
        $db = \Config\Database::connect();
        $adminSettings = $db->query("SELECT id as setting_id, value as setting_value, name as setting_name   FROM  admin_settings WHERE 
                             status = 1")->getResultArray();
        if ($name != '') {
            $data = [];
            for ($i = 0; $i < count($adminSettings); $i++) {
                if ($name = $adminSettings[$i]['setting_name']) {
                    array_push($data, $adminSettings[$i]);
                    break;
                }
            }
            return $data;
        }
        return $adminSettings;

    }

    public function verifyAuthUrl()
    {
        $this->allowedRoutes = array(
            'v1/school/registration',
            'v1/school/list',
            'v1/school/edit',
            'v1/school/detail',
            'v1/school/addHolidayCalendar',
            'v1/school/calendarList',
            'v1/school/editHolidayCalendar',
            'v1/school/deleteHolidayCalendar',
            'v1/school/addAdmin',
            'v1/school/staticSiteSchoolRegistration',
            'v1/school/testEmail',
            'v1/school/studentUpgrade',
            'v1/school/updradeStudents',
            'v1/school/studentUpgradeList',
            'v1/school/studentGradeList',
            'v1/school/testEmail',
            'v1/school/dateformat',
            'v1/school/timeZoneList',
            'v1/school/addAcademyAdmin',
            'v1/school/addAcademySchool',
            'v1/school/announcementList',
            'v1/school/addAnnouncement',
            'v1/school/editAnnouncement',
            // CI4 routes without v1 prefix
            'school/registration',
            'school/list',
            'school/edit',
            'school/detail',
            'school/addHolidayCalendar',
            'school/calendarList',
            'school/editHolidayCalendar',
            'school/deleteHolidayCalendar',
            'school/addAdmin',
            'school/staticSiteSchoolRegistration',
            'school/testEmail',
            'school/studentUpgrade',
            'school/updradeStudents',
            'school/studentUpgradeList',
            'school/studentGradeList',
            'school/dateformat',
            'school/timeZoneList',
            'school/addAcademyAdmin',
            'school/addAcademySchool',
            'school/announcementList',
            'school/addAnnouncement',
            'school/editAnnouncement'
        );
        foreach ($this->allowedRoutes as $routeString) {
            if ($this->controller == $routeString) {
                return true;
            }
        }
        return false;
    }

    public function excludefunction()
    {
        $this->excludeRoutes = array(
            'v1/school/registration',
            'v1/school/staticSiteSchoolRegistration',
            'v1/school/timeZoneList',
            'school/registration',
            'school/staticSiteSchoolRegistration',
            'school/timeZoneList',
            'school/announcementList',
            'school/addAnnouncement'
        );
        foreach ($this->excludeRoutes as $routeString) {
            if ($this->controller == $routeString) {
                return 'true';
            }
        }
    }

    public function registration_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $headers = $this->request->getHeaders();
        $this->common_model->checkPermission($this->controller, $params, $headers);
        if ($params['platform'] != "web" && $params['platform'] != "ios") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Role Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } elseif ($params['first_name'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "First Name should not be empty";
        } elseif ($params['last_name'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Second Name should not be empty";
        } elseif ($params['email_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Email Address should not be empty";
        } elseif ($params['mobile'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Mobile Number should not be empty";
        } elseif ($params['name'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "School Name should not be empty";
        } elseif ($params['address1'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Address1 should not be empty";
        } elseif ($params['city'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "City should not be empty";
        } elseif ($params['state'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "State should not be empty";
        } elseif ($params['country'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Country should not be empty";
        } elseif ($params['postal_code'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Postal code should not be empty";
        } if(count($this->jsonarr)==0){
        $this->common_model->createLog($params,'v1/school/registration','only request','registration');
        $userExists = $this->school_model->checkAdmin($params);
            if (count($userExists) == 0) {
                $schoolExists = $this->school_model->checkSchoolName($params['name'], $params['branch_name']);
                if (count($schoolExists) == 0) {
                    $school = array(
                        'name' => $params['name'],
                        'tax_id' => $params['tax_id'],
                        'address1' => $params['address1'],
                        'address2' => $params['address2'],
                        'city' => $params['city'],
                        'state' => $params['state'],
                        'country' => $params['country'],
                        'postal_code' => $params['postal_code'],
                        'has_branch' => 1,
                        'branch_name' => $params['branch_name'],
                        'status' => 1,
                        'trial'=> isset($params['trial']) ? $params['trial'] : 0,
                        'validity'=> isset($params['validity']) ? $params['validity'] : '',
                        'created_by' => $params['user_id'],
                        'created_date' => date('Y-m-d H:i:s'),
                    );
                    if (isset($params['profile_url']) && isset($params['profile_thumb_url'])) {
                        $school['profile_url'] = $params['profile_url'];
                        $school['profile_thumb_url'] = $params['profile_thumb_url'];
                    }
                    if (isset($params['domain_name']) && $params['domain_name'] != '') {
                        $school['domain_name'] = $params['domain_name'];
                    }
                    $schoolId = $this->common_model->insert('school', $school);
                    if ($schoolId > 0) {
                        $setting[0] = array(
                            'name' => 'teacher_activity_email',
                            'description' => 'Activity Notification "To" Email ID',
                            'value' => $params['email_id'],
                            'school_id' => $schoolId,
                            'settings' => '2',
                            'status' => '1'
                        );
                        $setting[1] = array(
                            'name' => 'teacher_notify_email_admin',
                            'description' => 'Activity Notification "CC" Email ID',
                            'value' => '',
                            'school_id' => $schoolId,
                            'settings' => '2',
                            'status' => '1'
                        );
                        $setting[2] = array(
                            'name' => 'allow_autograde_settings',
                            'description' => '',
                            'value' => '0',
                            'school_id' => $schoolId,
                            'settings' => '2',
                            'status' => '1'
                        );
                        $setting[3] = array(
                           'name' => 'allow_autoassign_student_for_class',
                           'description' => '',
                           'value' => '1',
                            'school_id' => $schoolId,
                            'settings' => '2',
                            'status' => '1'
                        );
                        $setting[4] = array(
                            'name' => 'answer_key_upload',
                            'description' => '',
                            'value' => '1',
                            'school_id' => $schoolId,
                            'settings' => '2',
                            'status' => '1'
                        );
                        $setting[5] = array(
                            'name' => 'file_size_restriction',
                            'description' => '',
                            'value' => '5',
                            'school_id' => $schoolId,
                            'settings' => '2',
                            'status' => '1'
                        );
                        $setting[6] = array(
                            'name' => 'support_email',
                            'description' => '',
                            'value' => '',
                            'school_id' => $schoolId,
                            'settings' => '2',
                            'status' => '1'
                        );
                        $setting[7] = array(
                            'name' => 'support_email_password',
                            'description' => '',
                            'value' => '',
                            'school_id' => $schoolId,
                            'settings' => '2',
                            'status' => '1'
                        );
                        $setting[8] = array(
                            'name' => 'below_score_cutoff_email',
                            'description' => '',
                            'value' => '',
                            'school_id' => $schoolId,
                            'settings' => '2',
                            'status' => '1'
                        );
                        $setting[9] = array(
                            'name' => 'zoom_apikey',
                            'description' => '',
                            'value' => '',
                            'school_id' => $schoolId,
                            'settings' => '2',
                            'status' => '1'
                        );
                        $setting[10] = array(
                            'name' => 'zoom_secretkey',
                            'description' => '',
                            'value' => '',
                            'school_id' => $schoolId,
                            'settings' => '2',
                            'status' => '1'
                        );
                        $setting[11] = array(
                            'name' => 'zoom_user_email',
                            'description' => '',
                            'value' => '',
                            'school_id' => $schoolId,
                            'settings' => '2',
                            'status' => '1'
                        );
                        $setting[12] = array(
                            'name' => 'allow_zoom_api',
                            'description' => '',
                            'value' => '',
                            'school_id' => $schoolId,
                            'settings' => '2',
                            'status' => '1'
                        );
                        $setting[13] = array(
                            'name' => 'timezone',
                            'description' => '',
                            'value' => '251',
                            'school_id' => $schoolId,
                            'settings' => '2',
                            'status' => '1'
                        );
                        $setting[14] = array(
                            'name' => 'date_format',
                            'description' => '',
                            'value' => '7',
                            'school_id' => $schoolId,
                            'settings' => '2',
                            'status' => '1'
                        );
                        $setting[15] = array(
                            'name' => 'teacher_zoom_view',
                            'description' => '0 -> In App, 1 -> External Tab, 2 -> Both',
                            'value' => '1',
                            'school_id' => $schoolId,
                            'settings' => '2',
                            'status' => '1'
                        );
                        $setting[16] = array(
                            'name' => 'student_zoom_view',
                            'description' => '0 -> In App, 1 -> External Tab, 2 -> Both',
                            'value' => '1',
                            'school_id' => $schoolId,
                            'settings' => '2',
                            'status' => '1'
                        );
                        $this->common_model->bulkInsert('admin_settings_school', $setting);

                        $gradeData = array();
                        $gradeName = array("PKG", "KG", 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12);
                        foreach ($gradeName as $key => $value) {
                            $gradeData[$key] = array(
                                'grade_name' => $value,
                                'description' => '',
                                'school_id' => $schoolId,
                                'status' => 1
                            );
                        }
                        $this->common_model->bulkInsert('grade', $gradeData);
                        $subjectData = array();
                        $subjectName = array("Math", "English");
                        foreach ($subjectName as $key => $value) {
                            $subjectData[$key] = array(
                                'subject_Name' => $value,
                                'description' => '',
                                'school_id' => $schoolId,
                                'status' => 1
                            );
                        }
                        $this->common_model->bulkInsert('subject', $subjectData);
                        $this->jsonarr = $this->addAdmin($params, $schoolId);
                        $this->jsonarr["IsSuccess"] = true;
                        $this->jsonarr["ResponseObject"] = "School added successfully";
                    } else {
                        $this->jsonarr["IsSuccess"] = false;
                        $this->jsonarr["ErrorObject"] = "Failed to add School";
                    }
                } else {
                    $this->jsonarr["IsSuccess"] = false;
                    $this->jsonarr["ErrorObject"] = "school already exists";
                }
            } else {
                $this->jsonarr["IsSuccess"] = false;
                $this->jsonarr["ErrorObject"] = "Email-Id already Exists";
                }
            }
        $this->common_model->createLog($params,'v1/school/registration',$this->jsonarr,'registration');
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }

    public function staticSiteSchoolRegistration_post() {
        $this->benchmark->mark('code_start'); 
        $params = json_decode(file_get_contents('php://input'), true);
        if ($params['platform'] != "web" && $params['platform'] != "ios") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Role Id should not be empty";
        } elseif ($params['first_name'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "First Name should not be empty";
        } elseif ($params['last_name'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Second Name should not be empty";
        } elseif ($params['email_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Email Address should not be empty";
        } elseif ($params['mobile'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Mobile Number should not be empty";
        } elseif ($params['name'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "School Name should not be empty";
        } elseif ($params['country'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Country should not be empty";
        } elseif ($params['postal_code'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Postal code should not be empty";
        } elseif(count($this->jsonarr) == 0) {
            $this->common_model->createLog($params,'v1/school/registration','only request','registration');
            $userExists = $this->school_model->checkAdmin($params);
            if (count($userExists) == 0) {
                $schoolExists = $this->school_model->checkSchoolName($params['name'], $params['branch_name']);
                if (count($schoolExists) == 0) {
                    $school = array(
                        'name' => $params['name'],
                        'tax_id' => '',
                        'address1' => '',
                        'address2' => '',
                        'city' => '',
                        'state' => 0,
                        'country' => $params['country'],
                        'postal_code' => $params['postal_code'],
                        'has_branch' => 0,
                        'branch_name' => '',
                        'status' => 1,
                        'trial'=> isset($params['trial']) ? $params['trial'] : 0,
                        'validity'=> isset($params['validity']) ? $params['validity'] : '',
                        'created_by' => 0,
                        'created_date' => date('Y-m-d H:i:s'),
                    );
                    if (isset($params['profile_url']) && isset($params['profile_thumb_url'])) {
                        $school['profile_url'] = $params['profile_url'];
                        $school['profile_thumb_url'] = $params['profile_thumb_url'];
                    }
                    $schoolId = $this->common_model->insert('school', $school);
                    if ($schoolId > 0) {
                        $setting[0] = array(
                            'name' => 'teacher_activity_email',
                            'description' => 'Activity Notification "To" Email ID',
                            'value' => $params['email_id'],
                            'school_id' => $schoolId,
                            'settings' => '2',
                            'status' => '1'
                        );
                        $setting[1] = array(
                            'name' => 'teacher_notify_email_admin',
                            'description' => 'Activity Notification "CC" Email ID',
                            'value' => '',
                            'school_id' => $schoolId,
                            'settings' => '2',
                            'status' => '1'
                        );
                        $setting[2] = array(
                            'name' => 'allow_autograde_settings',
                            'description' => '',
                            'value' => '0',
                            'school_id' => $schoolId,
                            'settings' => '2',
                            'status' => '1'
                        );
                        $setting[3] = array(
                            'name' => 'allow_autoassign_student_for_class',
                            'description' => '',
                            'value' => '1',
                            'school_id' => $schoolId,
                            'settings' => '2',
                            'status' => '1'
                        );
                        $this->common_model->bulkInsert('admin_settings_school', $setting);

                        $gradeData = array();
                        $gradeName = array("PKG", "KG", 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12);
                        foreach ($gradeName as $key => $value) {
                            $gradeData[$key] = array(
                                'grade_name' => $value,
                                'description' => '',
                                'school_id' => $schoolId,
                                'status' => 1
                            );
                        }
                        $this->common_model->bulkInsert('grade', $gradeData);
                        $subjectData = array();
                        $subjectName = array("Maths", "English");
                        foreach ($subjectName as $key => $value) {
                            $subjectData[$key] = array(
                                'subject_Name' => $value,
                                'description' => '',
                                'school_id' => $schoolId,
                                'status' => 1
                            );
                        }
                        $this->common_model->bulkInsert('subject', $subjectData);
                        $params['user_id'] = 0;
                        $this->jsonarr = $this->addAdmin($params, $schoolId);
                        $this->jsonarr["IsSuccess"] = true;
                        $this->jsonarr["ResponseObject"] = "School added successfully";
                    } else {
                        $this->jsonarr["IsSuccess"] = false;
                        $this->jsonarr["ErrorObject"] = "Failed to add School";
                    }
                } else {
                    $this->jsonarr["IsSuccess"] = false;
                    $this->jsonarr["ErrorObject"] = "school already exists";
                }
            } else {
                $this->jsonarr["IsSuccess"] = false;
                $this->jsonarr["ErrorObject"] = "Email-Id already Exists";
            }
        }
        $this->common_model->createLog($params,'v1/school/registration',$this->jsonarr,'registration');
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }

    public function addAdmin($params,$schoolId) {
        $data = [];
        $data['role_id'] = 2;
        $data['school_id'] = is_array($schoolId) ? implode(',',$schoolId) : $schoolId;
        $data['status'] = 1;
        $data['login_type'] = 'WEB';
        $data['created_by'] = $params['user_id'];
        $data['created_date'] = date('Y-m-d H:i:s');
        $data['email_id'] = $params['email_id'];
        $data['mobile'] = $params['mobile'];
        $data['academy_user_id'] = isset($params['academy_admin_id']) ? $params['academy_admin_id'] : 0;
        if(isset($params['password']) && $params['password'] != ''){
            $salt = $this->config->item('salt');
            $password = md5($salt . $params['password'] . $salt);
            $data['password'] = $password;
        }
        $userId = $this->common_model->insert('user', $data);
        if ($userId > 0) {
            $userProfile = [];
            $userProfile['user_id'] = $userId;
            $userProfile['first_name'] = $params['first_name'];
            $userProfile['last_name'] = $params['last_name'];
            if (isset($params['gender'])) {
                $userProfile['gender'] = $params['gender'];
            }
            if (isset($params['birthday'])) {
                $userProfile['birthday'] = $params['birthday'];
            }
            $userProfile['created_by'] = $params['user_id'];
            $userProfile['created_date'] = date('Y-m-d H:i:s');
            $userProfileId = $this->common_model->insert('user_profile', $userProfile);
            if(is_array($schoolId)) {
                foreach ($schoolId as $key => $value) {
                    $checkSchoolId = $this->school_model->checkSchoolId($value, $userId);
                    if (count($checkSchoolId) == 0) {
                        $userProfileDetails = array('user_id' => $userId,
                            'school_id' => $value,
                            'status' => 1,
                            'allow_dashboard' => isset($params['allow_dashboard']) ? $params['allow_dashboard'] : 1,
                            'created_by' => $params['user_id'],
                            'created_date' => date('Y-m-d H:i:s'));
                        $this->common_model->insert('user_profile_details', $userProfileDetails);
                    }
                }
            } else {
                $userProfileDetails = array('user_id' => $userId,
                    'school_id' => $schoolId,
                    'status' => 1,
                    'allow_dashboard' => isset($params['allow_dashboard']) ? $params['allow_dashboard'] : 1,
                    'created_by' => $params['user_id'],
                    'created_date' => date('Y-m-d H:i:s'));
                $this->common_model->insert('user_profile_details', $userProfileDetails);
            }
            if ($userProfileId > 0) {
                $messageTemplates = $this->common_model->smsEmailTemplate('password_generate_link', 'email');
                $emailMsg = $messageTemplates['template'];
                $userLink = base64_encode($userId);
                $userLink = base64_encode($userLink);
                $this->load->library('bitly');
                $url = $this->config->item('user_password_url') . '/' . $userLink;
              //  $urlLink = $this->bitly->shorten($url);
                $emailMsg = str_replace('%URL%', $url, $emailMsg);
                $emailMsg = str_replace('%USER%', $params['first_name'] . ' ' . $params['last_name'], $emailMsg);
                if ($this->config->item('user_send_email') == true) {
                    $mailSent = $this->common_model->sendEmail($messageTemplates['subject'], $params['email_id'], $emailMsg, '','');
                }
                $this->jsonarr["IsSuccess"] = true;
                $this->jsonarr["ResponseObject"] = "Admin added successfully";
                $this->jsonarr["admin_id"] = $userId;
            } else {
                $this->jsonarr["IsSuccess"] = false;
                $this->jsonarr["ErrorObject"] = "Failed to add UserProfile";
            }
        } else {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Failed to add Admin";
        }
        return $this->jsonarr;
    }

    public function addAdmin_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $headers = $this->request->getHeaders();
        $this->common_model->checkPermission($this->controller, $params, $headers);
        if ($params['platform'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Role Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } elseif ($params['first_name'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "First Name should not be empty";
        } elseif ($params['last_name'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Second Name should not be empty";
        } elseif ($params['email_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Email Address should not be empty";
        } elseif ($params['mobile'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Mobile Number should not be empty";
        } elseif ($params['selected_school_id'] == "" && $params['selected_school_id'] == 0) {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Selected School Id should not be empty";
        }
        if (count($this->jsonarr) == 0) {
            $this->common_model->createLog($params,'v1/school/addAdmin','only request','addAdmin');
            $userExists = $this->school_model->checkUser($params['email_id'], $params['selected_school_id']);
            if (count($userExists) == 0) {
                if ($this->config->item('multiple_school') == true) {
                    $checkSchool = $this->school_model->checkAdminSchool($params);
                } else {
                    $checkSchool = array();
                }
                if (count($checkSchool) == 0) {
                    $this->jsonarr = $this->addAdmin($params, $params['selected_school_id']);
                } elseif (count($checkSchool) > 0) {
                    $condition = array('user_id' => $checkSchool[0]['user_id']);
                    $school = array();
                    $schoolIds = explode(",", $checkSchool[0]['school_id']);
                    $userSchoolId = $this->school_model->getUserSchoolId($checkSchool[0]['user_id']);
                    $userSchoolId = explode(',',$userSchoolId['school_id']);
                    $schoolArrayId = [];
                    foreach ($params['selected_school_id'] as $key => $value) {
                        foreach ($userSchoolId as $key1 => $value1) {
                            $a = array_search($value, $userSchoolId, true);
                            if ($a === FALSE) {
                                array_push($schoolArrayId, implode(',', $params['selected_school_id']));
                            }
                        }
                    }
                    $schoolArrayId = array_unique($schoolArrayId);
                    if(count($schoolArrayId) > 0) {
                        $schoolMerge = array_merge($schoolIds,$schoolArrayId);
                    }
                    $school['school_id'] = isset($schoolMerge) ? implode(",", $schoolMerge) : implode(',',$schoolIds);
                    $schoolIds = isset($schoolMerge) ? $schoolMerge : $schoolIds;
                    foreach ($schoolIds as $key => $value) {
                        $checkSchoolId = $this->school_model->checkSchoolId($value,$checkSchool[0]['user_id']);
                        if (count($checkSchoolId) == 0) {
                            $userProfileDetials = array('user_id' => $checkSchool[0]['user_id'],
                                'status' => 1,
                                'school_id' => $value,
                                'created_by' => $params['user_id'],
                                'created_date' => date('Y-m-d H:i:s'));
                            $this->common_model->insert('user_profile_details', $userProfileDetials);
                        }
                    }
                    $this->common_model->update('user', $school, $condition);
                    $this->jsonarr["IsSuccess"] = true;
                    $this->jsonarr["ResponseObject"] = "Admin added successfully";
                }
            } else {
                $this->jsonarr["IsSuccess"] = false;
                $this->jsonarr["ErrorObject"] = "Email-Id already Exists";
            }
        }
        $this->common_model->createLog($params,'v1/school/addAdmin',$this->jsonarr,'addAdmin');
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }

    public function list_post(){
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $headers = $this->request->getHeaders();
        $this->common_model->checkPermission($this->controller, $params, $headers);
        if ($params['platform'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Role Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } else {
            $this->common_model->createLog($params,'v1/school/list','only request','list');
            $schoolList = [];
            $allSchoolList = $this->school_model->schoolList($params);
            for ($i = 0; $i < count($allSchoolList); $i++) {
                $dataAvailable = false;
                for ($j = 0; $j < count($schoolList); $j++) {
                    if ($schoolList[$j]['school_id'] == $allSchoolList[$i]['school_id']) {
                        $dataAvailable = true;
                    }
                }
                if (!$dataAvailable) {
                    $c = count($schoolList);
                    $schoolList[$c]['school_id'] = $allSchoolList[$i]['school_id'];
                    $schoolList[$c]['name'] = $allSchoolList[$i]['name'];
                    $schoolList[$c]['tax_id'] = $allSchoolList[$i]['tax_id'];
                    $schoolList[$c]['school_website'] = $allSchoolList[$i]['school_website'];
                    $schoolList[$c]['address_1'] = $allSchoolList[$i]['address1'];
                    $schoolList[$c]['address_2'] = $allSchoolList[$i]['address2'];
                    $schoolList[$c]['city'] = $allSchoolList[$i]['city'];
                    $schoolList[$c]['state_id'] = $allSchoolList[$i]['state'];
                    $schoolList[$c]['state_name'] = $allSchoolList[$i]['state_name'];
                    $schoolList[$c]['country_id'] = $allSchoolList[$i]['country'];
                    $schoolList[$c]['country_name'] = $allSchoolList[$i]['country_name'];
                    $schoolList[$c]['postal_code'] = $allSchoolList[$i]['postal_code'];
                    $schoolList[$c]['has_branch'] = $allSchoolList[$i]['has_branch'];
                    $schoolList[$c]['branch_name'] = $allSchoolList[$i]['branch_name'];
                    $schoolList[$c]['corporate_name'] = $allSchoolList[$i]['corporate_name'];
                    $schoolList[$c]['institution_type'] = $allSchoolList[$i]['institution_type'];
                    $schoolList[$c]['status'] = $allSchoolList[$i]['status'];
                    $schoolList[$c]['profile_thumb_url'] = $allSchoolList[$i]['profile_thumb_url'];
                    $schoolList[$c]['profile_url'] = $allSchoolList[$i]['profile_url'];
                    $schoolList[$c]['created_by'] = $allSchoolList[$i]['created_by'];
                    $schoolList[$c]['created_date'] = $allSchoolList[$i]['created_date'];
                    $schoolList[$c]['modified_by'] = $allSchoolList[$i]['modified_by'];
                    $schoolList[$c]['modified_date'] = $allSchoolList[$i]['modified_date'];
                    $schoolList[$c]['admin_details'][0]['first_name'] = $allSchoolList[$i]['first_name'];
                    $schoolList[$c]['admin_details'][0]['last_name'] = $allSchoolList[$i]['last_name'];
                    $schoolList[$c]['admin_details'][0]['email_id'] = $allSchoolList[$i]['email_id'];
                    $schoolList[$c]['admin_details'][0]['mobile'] = $allSchoolList[$i]['mobile'];
                } else {
                    for ($k = 0; $k < count($schoolList); $k++) {
                        if ($schoolList[$k]['school_id'] == $allSchoolList[$i]['school_id']) {
                            $cnt = count($schoolList[$k]['admin_details']);
                            //$studentList[$k]['address'][$cnt]['name'] = $allStudentList[$i]['name'];
                            $schoolList[$k]['admin_details'][$cnt]['first_name'] = $allSchoolList[$i]['first_name'];
                            $schoolList[$k]['admin_details'][$cnt]['last_name'] = $allSchoolList[$i]['last_name'];
                            $schoolList[$k]['admin_details'][$cnt]['email_id'] = $allSchoolList[$i]['email_id'];
                            $schoolList[$k]['admin_details'][$cnt]['mobile'] = $allSchoolList[$i]['mobile'];
                        }
                    }

                }
            }
            $this->jsonarr["IsSuccess"] = true;
            $this->jsonarr["ResponseObject"] = $schoolList;
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->common_model->createLog($params,'v1/school/list',$this->jsonarr,'list');
        return $this->printjson($this->jsonarr);
    }

    public function edit_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $headers = $this->request->getHeaders();
        $this->common_model->checkPermission($this->controller, $params, $headers);
        if ($params['platform'] != "web" && $params['platform'] != "ios") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Role Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } elseif ($params['school_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "School Id should not be empty";
        } elseif ($params['name'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Name Id should not be empty";
        } elseif ($params['address1'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Address1 should not be empty";
        } elseif ($params['city'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "City Id should not be empty";
        } elseif ($params['state'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "State should not be empty";
        } elseif ($params['country'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Country should not be empty";
        } elseif ($params['postal_code'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Postal code should not be empty";
        } elseif ($params['status'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "status should not be empty";
        } if (count($this->jsonarr) == 0) {
                $this->common_model->createLog($params,'v1/school/edit','only request','edit');
                $schoolExists = $this->school_model->checkSchool($params['school_id']);
                    if (count($schoolExists) > 0) {
                      $condition = array("school_id" => $schoolExists['school_id']);
                        $school = array(
                            'name' => $params['name'],
                            'tax_id' => $params['tax_id'],
                            'address1' => $params['address1'],
                            'address2' => $params['address2'],
                            'city' => $params['city'],
                            'state' => $params['state'],
                            'country' => $params['country'],
                            'postal_code' => $params['postal_code'],
                            'has_branch' => $params['has_branch'],
                            'branch_name' => $params['branch_name'],
                            'status' => $params['status'],
                            'modified_by' => $params['user_id'],
                            'modified_date' => date('Y-m-d H:i:s')
                        );
                        if(isset($params['profile_url']) && isset($params['profile_thumb_url'])) {
                            $school['profile_url'] = $params['profile_url'];
                            $school['profile_thumb_url'] = $params['profile_thumb_url'];
                        }
                if (isset($params['domain_name']) && $params['domain_name'] != '') {
                    $school['domain_name'] = $params['domain_name'];
                }

                        $updated = $this->common_model->update('school', $school, $condition);
                        if ($updated) {
                            $this->jsonarr["IsSuccess"] = true;
                            $this->jsonarr["ResponseObject"] = "School updated successfully";
                        } else {
                            $this->jsonarr["IsSuccess"] = false;
                            $this->jsonarr["ErrorObject"] = "Failed to update School";
                        }
                    } else {
                        $this->jsonarr["IsSuccess"] = false;
                        $this->jsonarr["ErrorObject"] = "school not found";
                    }
                }
        $this->common_model->createLog($params,'v1/school/edit',$this->jsonarr,'edit');
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }

    public function detail_post() {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $headers = $this->request->getHeaders();
        $this->common_model->checkPermission($this->controller, $params, $headers);
        if ($params['platform'] != "web" && $params['platform'] != "ios") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Role Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } elseif ($params['school_id'] == 0) {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "School id should not be empty";
        } else {
            $this->common_model->createLog($params,'v1/school/detail','only request','detail');
            $schoolDetail = $this->school_model->schoolDetail($params);
                $this->jsonarr["IsSuccess"] = true;
                $this->jsonarr["ResponseObject"] = $schoolDetail;
               }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->common_model->createLog($params,'v1/school/detail',$this->jsonarr,'detail');
        return $this->printjson($this->jsonarr);
    }

    private function printjson($jsonarr)
    {
        header('Content-Type: application/json');
        echo json_encode($jsonarr);
    }

    public function addHolidayCalendar_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $headers = $this->request->getHeaders();
        $this->common_model->checkPermission($this->controller, $params, $headers);
        if ($params['platform'] != "web" && $params['platform'] != "ios") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Role Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } elseif ($params['school_id'] == 0) {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "School id should not be empty";
        } elseif (count($this->jsonarr) == 0) {
            $this->common_model->createLog($params,'v1/school/addHolidayCalendar','only request','addHolidayCalendar');
            $data = [];
            if ($params['from_date'] != $params['to_date'] && $params['to_date'] != '') {
                $dates = $this->getDates($params);
                foreach ($dates as $key => $value) {
                    $value['school_id'] = $params['school_id'];
                    $value['from_date'] = $value['dates'];
                    $value['to_date'] = $value['dates'];
                    $calendarExists = $this->school_model->checkCalendarExists($value);
                    if (count($calendarExists) > 0) {
                        unset($dates[$key]);
                    }
                }
                foreach ($dates as $key => $value) {
                    $data[] = array(
                        'school_id' => $params['school_id'],
                        'from_date' => $value['dates'],
                        'to_date' => $value['dates'],
                        'festival_name' => $params['festival_name'],
                        'created_by' => $params['user_id'],
                        'created_date' => date('Y-m-d H:i:s')
                    );
                }
                if (count($data) > 0) {
                    $addCalendar = $this->common_model->bulkInsert('holiday_calendar', $data);
                } else {
                    $addCalendar = 0;
                }
                if ($addCalendar) {
                    $calendarList = $this->school_model->getCalendar($params);
                    $this->jsonarr['IsSuccess'] = true;
                    $this->jsonarr['Calendar'] = $calendarList;
                    $this->jsonarr['ResponseObject'] = "Holiday Added Successfully";
                } else {
                    $this->jsonarr['IsSuccess'] = false;
                    $this->jsonarr['ErrorObject'] = "Date already Exists";
                }
            } else if ($params['to_date'] == '' || $params['from_date'] == $params['to_date']) {
                $params['to_date'] = $params['from_date'];
                $calendarExists = $this->school_model->checkCalendarExists($params);
                if (count($calendarExists) == 0) {
                    $data["school_id"] = $params['school_id'];
                    $data["from_date"] = $params['from_date'];
                    $data["to_date"] = $params['to_date'];
                    $data["festival_name"] = $params['festival_name'];
                    $data["created_by"] = $params['user_id'];
                    $data["created_date"] = date('Y-m-d H:i:s');
                }
                if(count($data) > 0) {
                    $addCalendar = $this->common_model->insert('holiday_calendar', $data);
                } else {
                    $addCalendar = 0;
                }
                if ($addCalendar > 0) {
                    $calendarList = $this->school_model->getCalendar($params);
                    $this->jsonarr['IsSuccess'] = true;
                    $this->jsonarr['Calendar'] = $calendarList;
                    $this->jsonarr['ResponseObject'] = "Holiday Added Successfully";
                } else {
                    $this->jsonarr['IsSuccess'] = false;
                    $this->jsonarr['ErrorObject'] = "Date already Exists";
                }
            }
        }
        $this->common_model->createLog($params,'v1/school/addHolidayCalendar',$this->jsonarr,'addHolidayCalendar');
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }

    public function getDates($params) {
        $dates = array();
        $format = 'Y-m-d';
        $day = 'N';
        $fromDate = strtotime($params['from_date']);
        $toDate = strtotime($params['to_date']);
        $addDate = '+1 day';
        $i = 0;
        while($fromDate <= $toDate) {
            $dates[$i]['dates'] = date($format, $fromDate);
            $dates[$i]['slotday'] = date($day, $fromDate);
            $fromDate = strtotime($addDate, $fromDate);
            $i++;
        }
        return $dates;
    }

    public function deleteHolidayCalendar_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $headers = $this->request->getHeaders();
        $this->common_model->checkPermission($this->controller, $params, $headers);
        if ($params['platform'] != "web" && $params['platform'] != "ios") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Role Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } elseif ($params['school_id'] == 0) {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "School id should not be empty";
        } elseif (count($this->jsonarr) == 0) {
            $this->common_model->createLog($params,'v1/school/deleteHolidayCalendar','only request','deleteHolidayCalendar');
            if($params['to_date'] == '') {
                $params['to_date'] = $params['from_date'];
            }
            $id = $this->school_model->checkCalendar($params);
            if(count($id) > 0) {
                $condition = array("id" => $id[0]['id']);
                $delete = $this->common_model->delete('holiday_calendar', $condition);
                if($delete) {
                    $this->jsonarr['IsSuccess'] = true;
                    $this->jsonarr['ResponseObject'] = "Holiday Date Deleted";
                }
            }
            else {
                $this->jsonarr['IsSuccess'] = false;
                $this->jsonarr['ErrorObject'] = "The Selected Date Not Found";
            }
        }
        $this->common_model->createLog($params,'v1/school/deleteHolidayCalendar',$this->jsonarr,'deleteHolidayCalendar');
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }

    public function editHolidayCalendar_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $headers = $this->request->getHeaders();
        $this->common_model->checkPermission($this->controller, $params, $headers);
        if ($params['platform'] != "web" && $params['platform'] != "ios") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Role Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } elseif ($params['school_id'] == 0) {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "School id should not be empty";
        } elseif (count($this->jsonarr) == 0) {
            $this->common_model->createLog($params,'v1/school/editHolidayCalendar','only request','editHolidayCalendar');
            $calendarExists = $this->school_model->checkCalendar($params);
            if (count($calendarExists) > 0) {
                $condition = array("id" => $calendarExists[0]['id']);
                $data = [];
                $data["from_date"] = $params['from_date'];
                if ($params['to_date'] == '') {
                    $data["to_date"] = $params['from_date'];
                } else {
                    $data["to_date"] = $params['to_date'];
                }
                $data["festival_name"] = $params['festival_name'];
                $data["modified_by"] = $params['user_id'];
                $updated = $this->common_model->update('holiday_calendar', $data, $condition);
            }
            if($updated) {
                $this->jsonarr['IsSuccess'] = true;
                $this->jsonarr['ResponseObject'] = "Holiday Date Updated";
            } else {
                $this->jsonarr['IsSuccess'] = false;
                $this->jsonarr['ErrorObject'] = "Holiday Date Not Updated";
            }
        }
        $this->common_model->createLog($params,'v1/school/editHolidayCalendar',$this->jsonarr,'editHolidayCalendar');
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }

    public function calendarList_post() {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $headers = $this->request->getHeaders();
        $this->common_model->checkPermission($this->controller, $params, $headers);
        if ($params['platform'] != "web" && $params['platform'] != "ios") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Role Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } elseif ($params['school_id'] == 0) {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "School id should not be empty";
        } elseif(count($this->jsonarr) == 0) {
            $this->common_model->createLog($params,'v1/school/calendarList','only request','calendarList');
            $calendarList = $this->school_model->getCalendar($params);
            if(count($calendarList) > 0) {
                $this->jsonarr['IsSuccess'] = true;
                $this->jsonarr['ResponseObject'] = $calendarList;
            } else {
                $this->jsonarr['IsSuccess'] = true;
                $this->jsonarr['ResponseObject'] = array();
            }
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->common_model->createLog($params,'v1/school/calendarList',$this->jsonarr,'calendarList');
        return $this->printjson($this->jsonarr);
    }

    public function studentUpgrade_post(){
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $headers = $this->request->getHeaders();
        $this->common_model->checkPermission($this->controller, $params, $headers);
        if ($params['platform'] != "web" && $params['platform'] != "ios") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Role Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } elseif ($params['school_id'] == 0) {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "School id should not be empty";
        } elseif(count($this->jsonarr) == 0) {
            $this->common_model->createLog($params,'v1/school/studentUpgrade','only request','studentUpgrade');
            $notUpgrade = [];
            $upgradeData = [];
            $getStudentRecord = $this->school_model->getStudentRecord($params);
            foreach ($getStudentRecord as $key => $value) {
                $nextGrade = $this->school_model->getNextGrade($params['school_id'], $value['grade_id']);
                if (count($nextGrade) > 0) {
                    $upgradeData[] = array(
                        'school_id' => $value['school_id'],
                        'student_id' => $value['user_id'],
                        'grade_id' => $nextGrade[0]['grade_id'],
                        'active_date' => date('Y-m-d H:i:s'),
                        'created_by' => $params['user_id'],
                        'created_date' => date('Y-m-d H:i:s')
                    );
                    $updateUpgradedDate = array('upgrade_date' => date('Y-m-d H:i:s'),'grade_id' => $nextGrade[0]['grade_id']);
                    $condition = array('user_id' => $value['user_id'],'school_id' => $value['school_id']);
                    $this->common_model->update('user_profile_details', $updateUpgradedDate, $condition);
                    $date = date('Y-m-d');
                    $studentFutureContent = $this->school_model->getFutureContent($value['user_id'],$date);
                    if(count($studentFutureContent) > 0){
                        foreach ($studentFutureContent as $key1 => $value1){
                            $updateData = array('grade_id' => $nextGrade[0]['grade_id']);
                            $updateCondition = array('student_id' => $value1['student_id'],
                                                     'class_id' => $value1['class_id'],
                                                     'content_id' => $value1['content_id']);
                            $this->common_model->update('student_content', $updateData, $updateCondition);
                        }
                    }
                } else {
                    $notUpgrade[] = $value['student_name'];
                }
            }
            if (count($upgradeData) > 0) {
                $this->common_model->bulkInsert('upgrade',$upgradeData);
            }

            $this->jsonarr['IsSuccess'] = true;
            $this->jsonarr['ResponseObject'] = "Student Upgraded Successfully";

        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->common_model->createLog($params,'v1/school/studentUpgrade',$this->jsonarr,'studentUpgrade');
        return $this->printjson($this->jsonarr );
    }

    public function updradeStudents_post(){
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $headers = $this->request->getHeaders();
//        $this->common_model->checkPermission($this->controller, $params, $headers);
        if ($params['platform'] != "web" && $params['platform'] != "ios") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Role Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } elseif ($params['school_id'] == 0) {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "School id should not be empty";
        } elseif(count($this->jsonarr) == 0) {
            foreach ($params['student_id'] as $value) {
                $upgradeStudents[] = array(
                    'school_id' => $params['school_id'],
                    'student_id' => $value,
                    'joining_date' => $params['joining_date'],
                    'status' => 0,
                    'created_by' => $params['user_id'],
                    'created_date' => date('Y-m-d H:i:s')
                );
            }
            if(count($upgradeStudents) > 0) {
                $this->common_model->bulkInsert('student_upgrade', $upgradeStudents);
            }
            $this->jsonarr['IsSuccess'] = true;
            $this->jsonarr['ResponseObject'] = "Student Upgraded Successfully";

        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr );
    }
    public function studentUpgradeList_post(){
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $headers = $this->request->getHeaders();
//        $this->common_model->checkPermission($this->controller, $params, $headers);
        if ($params['platform'] != "web" && $params['platform'] != "ios") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Role Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } elseif ($params['school_id'] == 0) {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "School id should not be empty";
        } else {
            $getUpgradedList = $this->school_model->studentUpgradeList($params);
            $data = [];
            for ($i = 0; $i < count($getUpgradedList); $i++) {
                $dataAvailable = false;
                for ($j = 0; $j < count($data); $j++) {
                    if ($data[$j]['student_id'] == $getUpgradedList[$i]['student_id']) {
                        $dataAvailable = true;
                    }
                }
                if (!$dataAvailable) {
                    $c = count($data);
                    $data[$c]['student_id'] = $getUpgradedList[$i]['student_id'];
                    $data[$c]['student_name'] = $getUpgradedList[$i]['student_name'];
                    $data[$c]['grade_details'][0]['grade_id'] = $getUpgradedList[$i]['grade_id'];
                    $data[$c]['grade_details'][0]['grade_name'] = $getUpgradedList[$i]['grade_name'];
                    $data[$c]['grade_details'][0]['joining_date'] = $getUpgradedList[$i]['joining_date'];
                } else {
                    for ($k = 0; $k < count($data); $k++) {
                        if ($data[$k]['student_id'] == $getUpgradedList[$i]['student_id']) {
                            $cnt = count($data[$k]['grade_details']);
                            $data[$k]['grade_details'][$cnt]['grade_id'] = $getUpgradedList[$i]['grade_id'];
                            $data[$k]['grade_details'][$cnt]['grade_name'] = $getUpgradedList[$i]['grade_name'];
                            $data[$k]['grade_details'][$cnt]['joining_date'] = $getUpgradedList[$i]['joining_date'];
                        }
                    }
                }
            }
            $this->jsonarr['IsSuccess'] = true;
            $this->jsonarr['ResponseObject'] = $data;
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr );
    }
    public function studentGradeList_post(){
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $headers = $this->request->getHeaders();
        $this->common_model->checkPermission($this->controller, $params, $headers);
        if ($params['platform'] != "web" && $params['platform'] != "ios") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Role Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } elseif ($params['school_id'] == 0) {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "School id should not be empty";
        } elseif ($params['student_id'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Student id should not be empty";
        } else {
            $gradeList = $this->school_model->studentGradeList($params);
            $this->jsonarr['IsSuccess']= true ;
            $this->jsonarr['ResponseObject'] = $gradeList;
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr );
    }

    public function dateformat_post(){
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $headers = $this->request->getHeaders();
        $this->common_model->checkPermission($this->controller, $params, $headers);
        if ($params['platform'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Platform Should not be Empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Role Id Should not be Empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id Should not be Empty";
        } else {
            $date = $this->school_model->dateformat();
            $this->jsonarr["IsSuccess"] = true;
            $this->jsonarr["ResponseObject"] = $date;
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }

    /**
     * Get timezone list
     * CI4 migrated version
     */
    public function timeZoneList(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            $db = \Config\Database::connect();
            
            // Get timezone list
            $builder = $db->table('time_zone');
            $builder->select('time_zone_id, time_zone, utc_timezone, status');
            $builder->where('status', 1);
            $builder->orderBy('time_zone', 'ASC');
            
            $timezones = $builder->get()->getResultArray();
            
            // Format timezone display with UTC offset
            foreach ($timezones as $key => $value) {
                $timezones[$key]['time_zone'] = $value['time_zone'] . ' (' . $value['utc_timezone'] . ')';
            }

            return $this->response->setJSON([
                'IsSuccess' => true,
                'ResponseObject' => $timezones,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Timezone list error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return $this->response->setJSON([
                'IsSuccess' => false,
                'ResponseObject' => [],
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    public function addAcademyAdmin_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $prop = parse_ini_file('../properties.ini', true, INI_SCANNER_RAW);
        $params['access_token'] = base64_decode(base64_decode($params['access_token']));
      //  if ($params['access_token'] == $prop['access_token']) {
            if ($params['platform'] == "") {
                $this->jsonarr["IsSuccess"] = false;
                $this->jsonarr["ErrorObject"] = "Platform should not be empty";
            } elseif ($params['role_id'] == "") {
                $this->jsonarr["IsSuccess"] = false;
                $this->jsonarr["ErrorObject"] = "Role Id should not be empty";
            } elseif ($params['user_id'] == "") {
                $this->jsonarr["IsSuccess"] = false;
                $this->jsonarr["ErrorObject"] = "User Id should not be empty";
            } elseif ($params['first_name'] == "") {
                $this->jsonarr["IsSuccess"] = false;
                $this->jsonarr["ErrorObject"] = "First Name should not be empty";
            } elseif ($params['last_name'] == "") {
                $this->jsonarr["IsSuccess"] = false;
                $this->jsonarr["ErrorObject"] = "Second Name should not be empty";
            } elseif ($params['email_id'] == "") {
                $this->jsonarr["IsSuccess"] = false;
                $this->jsonarr["ErrorObject"] = "Email Address should not be empty";
            } elseif ($params['mobile'] == "") {
                $this->jsonarr["IsSuccess"] = false;
                $this->jsonarr["ErrorObject"] = "Mobile Number should not be empty";
            } elseif ($params['selected_school_id'] == "" && $params['selected_school_id'] == 0) {
                $this->jsonarr["IsSuccess"] = false;
                $this->jsonarr["ErrorObject"] = "Selected School Id should not be empty";
            }
            if (count($this->jsonarr) == 0) {
                $this->common_model->createLog($params, 'v1/school/addAdmin', 'only request', 'addAdmin');
                $userExists = $this->school_model->checkUser($params['email_id'], $params['selected_school_id']);
                if (count($userExists) == 0) {
                    if ($this->config->item('multiple_school') == true) {
                        $checkSchool = $this->school_model->checkAdminSchool($params);
                    } else {
                        $checkSchool = array();
                    }
                    if (count($checkSchool) == 0) {
                        $this->jsonarr = $this->addAdmin($params, $params['selected_school_id']);
                    } elseif (count($checkSchool) > 0) {
                        $condition = array('user_id' => $checkSchool[0]['user_id']);
                        $school = array();
                        $schoolIds = explode(",", $checkSchool[0]['school_id']);
                        $userSchoolId = $this->school_model->getUserSchoolId($checkSchool[0]['user_id']);
                        $userSchoolId = explode(',', $userSchoolId['school_id']);
                        $schoolArrayId = [];
                        foreach ($params['selected_school_id'] as $key => $value) {
                            foreach ($userSchoolId as $key1 => $value1) {
                                $a = array_search($value, $userSchoolId, true);
                                if ($a === FALSE) {
                                    array_push($schoolArrayId, implode(',', $params['selected_school_id']));
                                }
                            }
                        }
                        $schoolArrayId = array_unique($schoolArrayId);
                        if (count($schoolArrayId) > 0) {
                            $schoolMerge = array_merge($schoolIds, $schoolArrayId);
                        }
                        $school['school_id'] = isset($schoolMerge) ? implode(",", $schoolMerge) : implode(',', $schoolIds);
                        $schoolIds = isset($schoolMerge) ? $schoolMerge : $schoolIds;
                        foreach ($schoolIds as $key => $value) {
                            $checkSchoolId = $this->school_model->checkSchoolId($value, $checkSchool[0]['user_id']);
                            if (count($checkSchoolId) == 0) {
                                $userProfileDetials = array(
                                    'user_id' => $checkSchool[0]['user_id'],
                                    'status' => 1,
                                    'school_id' => $value,
                                    'created_by' => $params['user_id'],
                                    'created_date' => date('Y-m-d H:i:s')
                                );
                                $this->common_model->insert('user_profile_details', $userProfileDetials);
                            }
                        }
                        $this->common_model->update('user', $school, $condition);
                        $this->jsonarr["IsSuccess"] = true;
                        $this->jsonarr["ResponseObject"] = "Admin added successfully";
                        $this->jsonarr["admin_id"] = $checkSchool[0]['user_id'];
                    }
                } else {
                    $this->jsonarr["IsSuccess"] = false;
                    $this->jsonarr["ErrorObject"] = "Email-Id already Exists";
                }
            }
        // } else {
        //     $this->jsonarr["IsSuccess"] = false;
        //     $this->jsonarr["ErrorObject"] = "Unauthorised User";
        // }
        $this->common_model->createLog($params, 'v1/school/addAdmin', $this->jsonarr, 'addAdmin');
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }

    public function addAcademySchool_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $headers = $this->request->getHeaders();
      //  $this->common_model->checkPermission($this->controller, $params, $headers);
        if ($params['platform'] != "web" && $params['platform'] != "ios") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Role Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } elseif ($params['name'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "School Name should not be empty";
        } elseif ($params['address1'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Address1 should not be empty";
        } elseif ($params['city'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "City should not be empty";
        } elseif ($params['state'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "State should not be empty";
        } elseif ($params['country'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Country should not be empty";
        } elseif ($params['postal_code'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Postal code should not be empty";
        }
        if (count($this->jsonarr) == 0) {
            $this->common_model->createLog($params, 'v1/school/addAcademySchool', 'only request', 'addAcademySchool');
            $schoolExists = $this->school_model->checkSchoolName($params['name'], $params['branch_name']);
            if (count($schoolExists) == 0) {
                $school = array(
                    'name' => $params['name'],
                    'tax_id' => $params['tax_id'],
                    'address1' => $params['address1'],
                    'address2' => $params['address2'],
                    'city' => $params['city'],
                    'state' => $params['state'],
                    'country' => $params['country'],
                    'postal_code' => $params['postal_code'],
                    'has_branch' => 1,
                    'branch_name' => $params['branch_name'],
                    'status' => 1,
                    'trial' => isset($params['trial']) ? $params['trial'] : 0,
                    'validity' => isset($params['validity']) ? $params['validity'] : '',
                    'created_by' => $params['user_id'],
                    'created_date' => date('Y-m-d H:i:s'),
                );
                if (isset($params['profile_url']) && isset($params['profile_thumb_url'])) {
                    $school['profile_url'] = $params['profile_url'];
                    $school['profile_thumb_url'] = $params['profile_thumb_url'];
                }
                $schoolId = $this->common_model->insert('school', $school);
                if ($schoolId > 0) {
                    $setting[0] = array(
                        'name' => 'teacher_activity_email',
                        'description' => 'Activity Notification "To" Email ID',
                        'value' =>  '',
                        'school_id' => $schoolId,
                        'settings' => '2',
                        'status' => '1'
                    );
                    $setting[1] = array(
                        'name' => 'teacher_notify_email_admin',
                        'description' => 'Activity Notification "CC" Email ID',
                        'value' => '',
                        'school_id' => $schoolId,
                        'settings' => '2',
                        'status' => '1'
                    );
                    $setting[2] = array(
                        'name' => 'allow_autograde_settings',
                        'description' => '',
                        'value' => '0',
                        'school_id' => $schoolId,
                        'settings' => '2',
                        'status' => '1'
                    );
                    $setting[3] = array(
                        'name' => 'allow_autoassign_student_for_class',
                        'description' => '',
                        'value' => '1',
                        'school_id' => $schoolId,
                        'settings' => '2',
                        'status' => '1'
                    );
                    $setting[4] = array(
                        'name' => 'answer_key_upload',
                        'description' => '',
                        'value' => '1',
                        'school_id' => $schoolId,
                        'settings' => '2',
                        'status' => '1'
                    );
                    $setting[5] = array(
                        'name' => 'file_size_restriction',
                        'description' => '',
                        'value' => '5',
                        'school_id' => $schoolId,
                        'settings' => '2',
                        'status' => '1'
                    );
                    $setting[6] = array(
                        'name' => 'support_email',
                        'description' => '',
                        'value' => '',
                        'school_id' => $schoolId,
                        'settings' => '2',
                        'status' => '1'
                    );
                    $setting[7] = array(
                        'name' => 'support_email_password',
                        'description' => '',
                        'value' => '',
                        'school_id' => $schoolId,
                        'settings' => '2',
                        'status' => '1'
                    );
                    $setting[8] = array(
                        'name' => 'below_score_cutoff_email',
                        'description' => '',
                        'value' => '',
                        'school_id' => $schoolId,
                        'settings' => '2',
                        'status' => '1'
                    );
                    $setting[9] = array(
                        'name' => 'zoom_apikey',
                        'description' => '',
                        'value' => '',
                        'school_id' => $schoolId,
                        'settings' => '2',
                        'status' => '1'
                    );
                    $setting[10] = array(
                        'name' => 'zoom_secretkey',
                        'description' => '',
                        'value' => '',
                        'school_id' => $schoolId,
                        'settings' => '2',
                        'status' => '1'
                    );
                    $setting[11] = array(
                        'name' => 'zoom_user_email',
                        'description' => '',
                        'value' => '',
                        'school_id' => $schoolId,
                        'settings' => '2',
                        'status' => '1'
                    );
                    $setting[12] = array(
                        'name' => 'allow_zoom_api',
                        'description' => '',
                        'value' => '',
                        'school_id' => $schoolId,
                        'settings' => '2',
                        'status' => '1'
                    );
                    $setting[13] = array(
                        'name' => 'timezone',
                        'description' => '',
                        'value' => '251',
                        'school_id' => $schoolId,
                        'settings' => '2',
                        'status' => '1'
                    );
                    $setting[14] = array(
                        'name' => 'date_format',
                        'description' => '',
                        'value' => '7',
                        'school_id' => $schoolId,
                        'settings' => '2',
                        'status' => '1'
                    );
                    $setting[15] = array(
                        'name' => 'teacher_zoom_view',
                        'description' => '0 -> In App, 1 -> External Tab, 2 -> Both',
                        'value' => '1',
                        'school_id' => $schoolId,
                        'settings' => '2',
                        'status' => '1'
                    );
                    $setting[16] = array(
                        'name' => 'student_zoom_view',
                        'description' => '0 -> In App, 1 -> External Tab, 2 -> Both',
                        'value' => '1',
                        'school_id' => $schoolId,
                        'settings' => '2',
                        'status' => '1'
                    );
                    $this->common_model->bulkInsert('admin_settings_school', $setting);

                    $gradeData = array();
                    $gradeName = array("PKG", "KG", 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12);
                    foreach ($gradeName as $key => $value) {
                        $gradeData[$key] = array(
                            'grade_name' => $value,
                            'description' => '',
                            'school_id' => $schoolId,
                            'status' => 1
                        );
                    }
                    $this->common_model->bulkInsert('grade', $gradeData);
                    $subjectData = array();
                    $subjectName = array("Math", "English");
                    foreach ($subjectName as $key => $value) {
                        $subjectData[$key] = array(
                            'subject_Name' => $value,
                            'description' => '',
                            'school_id' => $schoolId,
                            'status' => 1
                        );
                    }
                    $this->common_model->bulkInsert('subject', $subjectData);
                    $this->jsonarr["IsSuccess"] = true;
                    $this->jsonarr["ResponseObject"] = "School added successfully";
                    $this->jsonarr['school_id'] = $schoolId;
                } else {
                    $this->jsonarr["IsSuccess"] = false;
                    $this->jsonarr["ErrorObject"] = "Failed to add School";
                }
            } else {
                $this->jsonarr["IsSuccess"] = false;
                $this->jsonarr["ErrorObject"] = "school already exists";
                $this->jsonarr['school_id'] = $schoolExists[0]['school_id'];
            }
        }
        $this->common_model->createLog($params, 'v1/school/addAcademySchool', $this->jsonarr, 'addAcademySchool');
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }

    public function announcementList_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $headers = $this->request->getHeaders();
       // $this->common_model->checkPermission($this->controller, $params, $headers);
        if ($params['platform'] != "web" && $params['platform'] != "ios") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Role Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } elseif ($params['school_id'] == 0) {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "School id should not be empty";
        } elseif (count($this->jsonarr) == 0) {
            $this->common_model->createLog($params, 'v1/school/announcementList', 'only request', 'announcementList');
            $current_date = date('Y-m-d');
            if($params['role_id'] == 2){
                $condition = "WHERE school_id = {$params['school_id']}";
            } else {
                $condition = "WHERE school_id = {$params['school_id']} AND status = 1 AND '{$current_date}' BETWEEN from_date AND to_date";
            }
            $announcementList = $this->school_model->announcementList($condition);
            if (count($announcementList) > 0) {
                $this->jsonarr['IsSuccess'] = true;
                $this->jsonarr['ResponseObject'] = $announcementList;
            } else {
                $this->jsonarr['IsSuccess'] = true;
                $this->jsonarr['ResponseObject'] = array();
            }
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->common_model->createLog($params, 'v1/school/announcementList', $this->jsonarr, 'announcementList');
        return $this->printjson($this->jsonarr);
    }

    public function addAnnouncement_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $headers = $this->request->getHeaders();
       // $this->common_model->checkPermission($this->controller, $params, $headers);
        if ($params['platform'] != "web" && $params['platform'] != "ios") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Platform Should not be Empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Role Id Should not be Empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id Should not be Empty";
        } elseif ($params['school_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "School id Should not be Empty";
        } elseif (count($this->jsonarr) == 0) {
            $this->common_model->createLog($params, 'v1/school/addAnnouncement', 'only request', 'addAnnouncement');
            $announcement = [];
            $announcement['school_id'] = $params['school_id'];
            $announcement['title'] = $params['title'];
            $announcement['description'] = $params['description'];
            $announcement['from_date'] = $params['from_date'];
            $announcement['to_date'] = $params['to_date'];
            $announcement['status'] = $params['status'];
            $announcement['created_by'] = $params['user_id'];
            $announcement['created_date'] = date('Y-m-d H:i:s');
            $id = $this->common_model->insert('institution_announcement', $announcement);

            if ($id > 0) {
                $this->jsonarr["IsSuccess"] = true;
                $this->jsonarr["ResponseObject"] = "Announcement Added Successfully";
            } else {
                $this->jsonarr["IsSuccess"] = false;
                $this->jsonarr["ErrorObject"] = "Failed to add";
            }
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->common_model->createLog($params, 'v1/school/addAnnouncement', $this->jsonarr, 'addAnnouncement');
        return $this->printjson($this->jsonarr);
    }

    public function editAnnouncement_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $headers = $this->request->getHeaders();
        // $this->common_model->checkPermission($this->controller, $params, $headers);
        if ($params['platform'] != "web" && $params['platform'] != "ios") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Platform Should not be Empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Role Id Should not be Empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id Should not be Empty";
        } elseif ($params['school_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "School id Should not be Empty";
        } elseif ($params['id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Announcement id Should not be Empty";
        } elseif (count($this->jsonarr) == 0) {
            $this->common_model->createLog($params, 'v1/school/editAnnouncement', 'only request', 'editAnnouncement');
            $announcement = [];
            $announcement['school_id'] = $params['school_id'];
            $announcement['title'] = $params['title'];
            $announcement['description'] = $params['description'];
            $announcement['from_date'] = $params['from_date'];
            $announcement['to_date'] = $params['to_date'];
            $announcement['status'] = $params['status'];
            $announcement['modified_by'] = $params['user_id'];
            $announcement['modified_date'] = date('Y-m-d H:i:s');
            $condition = array('id' => $params['id']);
            $update = $this->common_model->update('institution_announcement', $announcement, $condition);

            if ($update) {
                $this->jsonarr["IsSuccess"] = true;
                $this->jsonarr["ResponseObject"] = "Announcement Updated Successfully";
            } else {
                $this->jsonarr["IsSuccess"] = false;
                $this->jsonarr["ErrorObject"] = "Failed to update";
            }
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->common_model->createLog($params, 'v1/school/addAnnouncement', $this->jsonarr, 'addAnnouncement');
        return $this->printjson($this->jsonarr);
    }

    /**
     * Get announcement list for school
     * CI4 migrated version
     */
    public function announcementList()
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            $current_date = date('Y-m-d');
            $schoolId = $params['school_id'] ?? 0;
            $roleId = $params['role_id'] ?? 0;

            $db = \Config\Database::connect();
            $builder = $db->table('institution_announcement');
            $builder->select('id, school_id, title, description, from_date, to_date, status');
            $builder->where('school_id', $schoolId);

            // If not admin (role_id != 2), only show active announcements within date range
            if ($roleId != 2) {
                $builder->where('status', 1);
                $builder->where("'$current_date' BETWEEN from_date AND to_date", null, false);
            }

            $announcements = $builder->get()->getResultArray();

            return $this->response->setJSON([
                'IsSuccess' => true,
                'ResponseObject' => $announcements,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'IsSuccess' => false,
                'ResponseObject' => [],
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Add new announcement
     * CI4 migrated version
     */
    public function addAnnouncement()
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            $announcement = [
                'school_id' => $params['school_id'] ?? 0,
                'title' => $params['title'] ?? '',
                'description' => $params['description'] ?? '',
                'from_date' => $params['from_date'] ?? date('Y-m-d'),
                'to_date' => $params['to_date'] ?? date('Y-m-d'),
                'status' => $params['status'] ?? 1,
                'created_by' => $params['user_id'] ?? 0,
                'created_date' => date('Y-m-d H:i:s')
            ];

            $db = \Config\Database::connect();
            $builder = $db->table('institution_announcement');
            $result = $builder->insert($announcement);

            if ($result) {
                return $this->response->setJSON([
                    'IsSuccess' => true,
                    'ResponseObject' => 'Announcement Added Successfully',
                    'ErrorObject' => ''
                ]);
            } else {
                return $this->response->setJSON([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Failed to add announcement'
                ]);
            }

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get grade list for a student (CI4-compatible method)
     */
    public function studentGradeList()
    {
        header('Content-Type: application/json');
        
        $params = json_decode(file_get_contents('php://input'), true);
        
        // Validation
        if (empty($params['platform']) || ($params['platform'] != "web" && $params['platform'] != "ios")) {
            echo json_encode([
                "IsSuccess" => false,
                "ResponseObject" => [],
                "ErrorObject" => "Platform should not be empty"
            ]);
            exit;
        }
        
        if (empty($params['student_id'])) {
            echo json_encode([
                "IsSuccess" => false,
                "ResponseObject" => [],
                "ErrorObject" => "Student ID should not be empty"
            ]);
            exit;
        }

        try {
            $db = \Config\Database::connect();
            
            // Get unique grades from student's classes
            $builder = $db->table('student_class sc');
            $builder->select('g.grade_id, g.grade_name');
            $builder->distinct();
            $builder->join('class c', 'sc.class_id = c.class_id', 'left');
            $builder->join('grade g', 'FIND_IN_SET(g.grade_id, c.grade) > 0', 'left', false);
            $builder->where('sc.student_id', $params['student_id']);
            $builder->where('sc.status', '1');
            $builder->where('g.grade_id IS NOT NULL', null, false);
            $builder->orderBy('g.grade_name', 'ASC');
            
            $gradeList = $builder->get()->getResultArray();
            
            echo json_encode([
                "IsSuccess" => true,
                "ResponseObject" => $gradeList,
                "ErrorObject" => ""
            ]);
            exit;
        } catch (Exception $e) {
            echo json_encode([
                "IsSuccess" => false,
                "ResponseObject" => [],
                "ErrorObject" => $e->getMessage()
            ]);
            exit;
        }
    }

}
