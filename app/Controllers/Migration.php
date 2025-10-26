<?php
defined('BASEPATH') or exit('No direct script access allowed');
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: AccessToken");
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');

require APPPATH . '/libraries/REST_Controller.php';

class Migration extends REST_Controller
{
    protected $jsonarr = array();
    protected $headers;
    protected $urlAuth;
    protected $controller;

    function __construct()
    {
        parent::__construct();
        $this->load->model("migration_model");
        $this->load->model("common_model");
        $this->load->model("classes_model");

        header("Access-Control-Allow-Origin: *");
        $this->controller = uri_string();
        $urlAuth = $this->verifyAuthUrl();
        $headers = $this->input->request_headers();
        if ($urlAuth) {
            $excludeurl = $this->excludefunction();
            if ($excludeurl != 'true') {
                if (isset($headers['Accesstoken'])) {
                    $this->output->set_status_header(200);
                    $headers['Accesstoken'];
                } else {
                    $this->jsonarr['ErrorObject'] = "Unauthorized User";
                    $this->jsonarr['IsSuccess'] = false;
                    $this->printjson($this->jsonarr);
                    $this->output->set_status_header(401);
                    exit();
                }
            } else {
                $this->output->set_status_header(200);
                return true;
            }
        } else {
            $this->output->set_status_header(200);
            $this->jsonarr['ErrorObject'] = "The requested url is not found.";
            $this->jsonarr['IsSuccess'] = false;
            $this->printjson($this->jsonarr);
            exit();
        }
    }

    public function verifyAuthUrl()
    {
        $this->allowedRoutes = array(
            'v1/migration/teacherUploadExcel',
            'v1/migration/batchUploadExcel',
            'v1/migration/classUploadExcel',
            'v1/migration/subjectUploadExcel',
            'v1/migration/setPasswordMail',
            'v1/migration/insertStudents',
            'v1/migration/studentMappedClass',
            'v1/migration/weeklyPlan',
            'v1/migration/classBatchExcel',
            'v1/migration/getQuestionWiseData'
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
            'v1/migration/teacherUploadExcel',
            'v1/migration/batchUploadExcel',
            'v1/migration/classUploadExcel',
            'v1/migration/subjectUploadExcel',
            'v1/migration/setPasswordMail',
            'v1/migration/insertStudents',
            'v1/migration/studentMappedClass',
            'v1/migration/weeklyPlan',
            'v1/migration/classBatchExcel',
            'v1/migration/getQuestionWiseData'
        );
        foreach ($this->excludeRoutes as $routeString) {
            if ($this->controller == $routeString) {
                return true;
            }
        }
    }

    // send default email for the students asked by the client
    public function setPasswordMail_get(){
        $this->benchmark->mark('code_start');
        $fileLocation = "../uploads/student_list_uniqprep.xlsx";
        $this->load->library('PHPExcel');
        $objPHPExcel = PHPExcel_IOFactory::load($fileLocation);
        $sheet = $objPHPExcel->getSheet(0);
        $highestRow = $sheet->getHighestRow();
        $mailSent = false;
        for($row = 2; $row <= $highestRow; $row++) {
            $rowData = $sheet->rangeToArray('A'.$row,NULL,TRUE,FALSE);
            if($rowData [0][0] != ''){
                $userDetail = $this->migration_model->getUserDetail($rowData[0][0]);
                $messageTemplates = $this->common_model->smsEmailTemplate('password_generate_link', 'email');
                $emailMsg = $messageTemplates['template'];
                $userLink = base64_encode($userDetail[0]['user_id']);
                $userLink = base64_encode($userLink);
                $this->load->library('bitly');
                $url = $this->config->item('user_password_url') . '/' . $userLink;
                $urlLink = $this->bitly->shorten($url);
                $emailMsg = str_replace('%URL%', $urlLink, $emailMsg);
                $emailMsg = str_replace('%USER%', $userDetail[0]['first_name'] . ' ' . $userDetail[0]['last_name'], $emailMsg);
                $mailSent = $this->common_model->sendEmail($messageTemplates['subject'], $rowData[0][0], $emailMsg, '', '');
            }
        }
        if($mailSent){
            $this->jsonarr["IsSuccess"] = true;
            $this->jsonarr["ResponseObject"] = "Mail Send Successfully";
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }

    public function insertStudents_post() {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $this->load->library('PHPExcel');
        $file = "../uploads/Migration.xlsx";
        $objPHPExcel = PHPExcel_IOFactory::load($file);
        $sheet = $objPHPExcel->getSheet(4);
        $highestRow = $sheet->getHighestRow();
        $salt = $this->config->item('salt');
        for ($row = 2; $row <= $highestRow; $row++) {
            $rowData = $sheet->rangeToArray('A' . $row . ':' . 'V' . $row, NULL, TRUE, FALSE);
            if ($rowData[0][0] != '' && strtolower($rowData[0][6]) == 'active') {
                $user = array();
                $user['role_id'] = 5;
                if(strtolower($rowData[0][6])=='active') {
                    $user['status'] = 1;
                }
//                elseif ($rowData[0][6]=='Pending') {
//                    $user['status'] = 2;
//                } elseif ($rowData[0][6]=='To Drop') {
//                    $user['status'] = 3;
//                }
                $checkStudentEmail = $this->migration_model->checkStudentEmail($rowData[0][14]);
                if(count($checkStudentEmail) > 0) {
                    if($rowData[0][15] != '') {
                        $user['email_id'] = $rowData[0][15];
                    } else {
                        $user['email_id'] = "stud.". $rowData[0][1]. "@palsnesp.com";
                        $user['auto_generate_email_edquill'] = 1;
                    }
                } else {
                    $user['email_id'] = $rowData[0][14];
                }
                if ($rowData[0][21] != '') {
                    $password = md5($salt . trim($rowData[0][21]) . $salt);
                } else {
                    $password = md5($salt.'Welcome1'.$salt);
                }
                $user['school_id'] = $params['school_id'];
                $user['login_type'] = 'WEB';
                $user['created_by'] = $params['user_id'];
                $user['created_date'] = date('Y-m-d H:i:s');
                $user['student_id'] = $rowData[0][0];
                $user['password'] = $password;
                if ($rowData[0][11] != "" && $rowData[0][12] != "" && $rowData[0][13] != "") {
                    $user['mobile'] = $rowData[0][11] . "," . $rowData[0][12] . "," . $rowData[0][13];
                } elseif ($rowData[0][12] != "" && $rowData[0][13] != "") {
                    $user['mobile'] = $rowData[0][12] . "," . $rowData[0][13] . ",";
                } elseif ($rowData[0][11] != "") {
                    $user['mobile'] = $rowData[0][11] . "," . ",";
                } elseif ($rowData[0][11] == "" && $rowData[0][12] == "" && $rowData[0][13] == "") {
                    $user['mobile'] = "," . ",";
                }
                $id = $this->common_model->insert('user', $user);
                if ($id > 0) {
                    $userProfile = array();
                    $userProfile['user_id'] = $id;
                    $userProfile['created_date'] = date('Y-m-d H:i:s');
                    $userProfile['first_name'] = $rowData[0][1];
                    $userProfile['last_name'] = $rowData[0][2];
                    if($rowData[0][5] != ''){
                        $data = explode('/',$rowData[0][5]);
                        if(!is_numeric($data[0])){
                            $userProfile['birthday'] = $data[2].'-'.$data[1].'-'.$data[0];
                        } else {
                            $userProfile['birthday'] = date("Y-m-d", PHPExcel_Shared_Date::ExcelToPHP($rowData[0][5]));
                        }

                    } else {
                        $userProfile['birthday'] = '0000-00-00';
                    }
                    $userProfile['created_by'] = $params['user_id'];
                    $userProfile['created_date'] = date('Y-m-d H:i:s');
                    $profileId = $this->common_model->insert('user_profile', $userProfile);
                    if ($profileId > 0) {
                        $userProfileDetails = array();
                        $userProfileDetails['user_id'] = $id;
                        $userProfileDetails['school_id'] =$params['school_id'];
                        $userProfileDetails['edit_status'] = 1;
                        /*if ($rowData[0][7] != "") {
                            $data = explode('/',$rowData[0][7]);
                            if(!is_numeric($data[0])){
                                $userProfileDetails['doj'] = $data[2].'-'.$data[1].'-'.$data[0];
                            } else {
                                $userProfileDetails['doj'] = date("Y-m-d", PHPExcel_Shared_Date::ExcelToPHP($rowData[0][7]));
                            }*/

                       // } else {
                            $userProfileDetails['doj'] = '0000-00-00';
                        //}
                        /*if ($rowData[0][8] == "0000-00-00" ||$rowData[0][8] == "NULL" ) {
                            $userProfileDetails['dropped_date'] = '0000-00-00';
                        } else {
                            $data = explode('/',$rowData[0][8]);
                            $userProfileDetails['dropped_date'] = $data[2].'-'.$data[1].'-'.$data[0];
                        }*/
                        $userProfileDetails['dropped_date'] = '0000-00-00';
                        $grade = $this->common_model->searchGrade($rowData[0][4], $params['school_id']);
                        if (count($grade) > 0) {
                            $userProfileDetails['grade_id'] = $grade[0]['grade_id'];
                        } else {
                            $userProfileDetails['grade_id'] = 0;
                        }
                        $userProfileDetails['created_by'] = $params['user_id'];
                        $userProfileDetails['created_date'] = date('Y-m-d H:i:s');
                        $profileDetailId = $this->common_model->insert('user_profile_details', $userProfileDetails);
                        if ($profileDetailId > 0) {
                            $userAddress1 = array();
                            $userAddress1['state'] = $rowData[0][19];
                            $userAddress1['country'] = 231;
                            $userAddress1['user_id'] = $id;
                            $userAddress1['address_type'] = 2;
                            $userAddress1['email_ids'] = $rowData[0][15]. ',' .$rowData[0][16];
                            $userAddress1['name'] = $rowData[0][7] . ',' . $rowData[0][8];
                            $userAddress1['address1'] = $rowData[0][17];
                            $userAddress1['address2'] = "";
                            $userAddress1['city'] = $rowData[0][18];
                            $userAddress1['postal_code'] = $rowData[0][20];
                            $userAddress1['created_date'] = date('Y-m-d H:i:s');
                            $this->common_model->insert('user_address', $userAddress1);
                            $userAddress2 = array();
                            $userAddress2['state'] = "";
                            $userAddress2['country'] = "";
                            $userAddress2['user_id'] = $id;
                            $userAddress2['address_type'] = 3;
                            $userAddress2['email_ids'] = "";
                            $userAddress2['name'] = $rowData[0][9] . ',' . $rowData[0][10];
                            $userAddress2['address1'] = "";
                            $userAddress2['address2'] = "";
                            $userAddress2['city'] = "";
                            $userAddress2['postal_code'] = "";
                            $userAddress2['created_date'] = date('Y-m-d H:i:s');
                            $this->common_model->insert('user_address', $userAddress2);
                            if ( $id > 0 && $profileId > 0 && $profileDetailId > 0 ) {
                                $this->jsonarr["IsSuccess"] = true;
                                $this->jsonarr["ResponseObject"] = "Data Inserted Successfully";
                            }
                        }
                    }
                }
            }
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }

    public function batchUploadExcel_post(){
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'),true);
        $fileLocation = "../uploads/Migration.xlsx";
        $this->load->library('PHPExcel');
        if(file_exists($fileLocation)){
            $objPHPExcel = PHPExcel_IOFactory::load($fileLocation);
            $sheet = $objPHPExcel->getsheet(2);
            $highestRow = $sheet->getHighestRow();
            $array = [];
            for($row = 2; $row <= $highestRow; $row ++){
                $rowData = $sheet->rangeToArray('A'.$row. ':' .'C'. $row,NULL,TRUE,FALSE);
                if($rowData[0][0] != '' && $rowData[0][1] != '') {
                    $status = strtolower($rowData[0][2]);
                    //$checkBatch = $this->migration_model->checkBatchExist($params,$rowData[0][1]);
                    if ($rowData[0][1] != '') {
                        $array = array(
                            'batch_name' => $rowData[0][1],
                            'school_id' => $params['school_id'],
                            'status' => $status == "active" ? 1 : 0,
                            'edquill_batch_id' => $rowData[0][0],
                            'created_by' => $params['user_id'],
                            'created_date' => date('Y-m-d H:i:s')
                        );
                        $batchInsert = $this->common_model->insert('batch', $array);
                        if ($batchInsert) {
                            $this->jsonarr["IsSuccess"] = true;
                            $this->jsonarr["ResponseObject"] = "Data Inserted Successfully";
                        }
                    }
                }
            }
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }

    public function classUploadExcel_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $fileLocation = "../uploads/Migration.xlsx";
        $this->load->library('PHPExcel');
        if (file_exists($fileLocation)) {
            $objPHPExcel = PHPExcel_IOFactory::load($fileLocation);
            $sheet = $objPHPExcel->getsheet(3);
            $highestRow = $sheet->getHighestRow();
            $array = [];
            $array1 = [];
            for ($row = 2; $row <= $highestRow; $row++) {
                $rowData = $sheet->rangeToArray('A' . $row . ":" . 'L' . $row, NULL, TRUE, FALSE);
                //$getTeacher = $this->migration_model->getTeacherName($rowData[0][1]);
                $teacherId = $this->migration_model->checkTeacherExists($rowData[0][1]);
                $timeExplode = explode('to', $rowData[0][3]);
                $batchName = $this->migration_model->checkBatchExists($rowData[0][4], $params['school_id']);
                $status = strtolower($rowData[0][6]);
                $startTime = date('h:i A', strtotime($timeExplode[0]));
                $endTime = date('h:i A', strtotime($timeExplode[1]));
                $slotDays = '';
                if (strtolower($rowData[0][2]) == 1) {
                    $slotDays = "monday";
                } elseif (strtolower($rowData[0][2]) == 2) {
                    $slotDays = "tuesday";
                } elseif (strtolower($rowData[0][2]) == 3) {
                    $slotDays = "wednesday";
                } elseif (strtolower($rowData[0][2]) == 4) {
                    $slotDays = "thursday";
                } elseif (strtolower($rowData[0][2]) == 5) {
                    $slotDays = "friday";
                } elseif (strtolower($rowData[0][2]) == 6) {
                    $slotDays = "saturday";
                } elseif (strtolower($rowData[0][2]) == 7) {
                    $slotDays = "sunday";
                }
                if ($rowData[0][0] != '' && $rowData[0][1] != 179 && $rowData[0][1] != 304) {
                    $classCode = strtoupper($this->common_model->random_strings(10));
                    $array = array(
                        'teacher_id' => $params['user_id'],
                        'school_id' => $params['school_id'],
                        'class_name' => ucfirst($slotDays) ." - ". $rowData[0][3] ." - ". $batchName[0]['batch_name'],
                        'subject' => 0,
                        'class_code' => $classCode,
                        'batch_id' => $batchName[0]['batch_id'],
                        'start_date' => '2021-01-01',
                        'end_date' => '2022-06-01',
                        'meeting_link' => trim($rowData[0][8]),
                        'meeting_id' => $rowData[0][9],
                        'passcode' => $rowData[0][10],
                        'telephone_number' => trim($rowData[0][11]),
                        'edquill_classroom_id' => $rowData[0][4],
                        'status' => $status == "active" ? 1 : 2,
                        'class_type' => 1,
                        'class_status' => 0,
                        'edquill_schedule_id' => $rowData[0][0],
                        'grade' => 0,
                        'created_date' => date('Y-m-d H:i:s'),
                        'created_by' => $params['user_id']
                    );
                    $classInsert = $this->common_model->insert('class', $array);
                    $array1 = array(
                        'class_id' => $classInsert,
                        'teacher_id' => $teacherId[0]['user_id'],
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'school_id' => $params['school_id'],
                        'slot_days' => $rowData[0][2],
                        'meeting_link' => trim($rowData[0][8]),
                        'meeting_id' => $rowData[0][9],
                        'passcode' => $rowData[0][10],
                        'telephone_number' => trim($rowData[0][11]),
                        'slotselected' => 1,
                        'created_by' => $params['user_id'],
                        'created_date' => date('Y-m-d H:i:s')
                    );
                    $classSchedule = $this->common_model->insert('class_schedule', $array1);
                    if ($classInsert > 0 && $classSchedule > 0) {
                        $this->jsonarr["IsSuccess"] = true;
                        $this->jsonarr["ResponseObject"] = "Inserted Successfully";
                    }
                }
            }
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }

    public function teacherUploadExcel_post(){
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'),true);
        $fileLocation = "../uploads/Migration.xlsx";
        $this->load->library('PHPExcel');
        if(file_exists($fileLocation)){
            $objPHPExcel = PHPExcel_IOFactory::load($fileLocation);
            $sheet = $objPHPExcel->getsheet(1);
            $highestRow = $sheet->getHighestRow();
            $salt = $this->config->item('salt');
            $array = [];
            $array1 = [];
            $array2 = [];
            $array3 = [];
            for($row = 2; $row <= $highestRow; $row++){
                $rowData = $sheet->rangeToArray('A'. $row . ':' . 'E' . $row,NULL,TRUE,FALSE);
                if($rowData[0][0]!="" && $rowData[0][1]!= "") {
                    if($rowData[0][4] != '' && $rowData[0][4] != "NULL") {
                        $password = md5($salt.$rowData[0][4].$salt);
                    } else {
                        $password =md5($salt."Welcome1".$salt);
                    }
                        $firstName = explode(' ',$rowData[0][1]);
                        $lastName = isset($firstName[1]) ? $firstName[1] : $rowData[0][2];
                        $email = $firstName[0] . "@palsnesp.com";
                        $array = array(
                        'role_id' => 4,
                        'email_id' => $email,
                        'password' => $password,
                        'status' => 1,
                        'school_id' => $params['school_id'],
                        'edquill_teacher_id' => $rowData[0][0],
                        'created_by' => $params['user_id'],
                        'created_date' => date('Y-m-d H:i:s')
                    );
                    $userId = $this->common_model->insert('user', $array);
                    $array1 = array(
                        'user_id' => $userId,
                        'first_name' => $firstName[0],
                        'last_name' => $lastName != '' ? $lastName : ' ',
                        'created_by' => $params['user_id'],
                        'created_date' => date('Y-m-d H:i:s')
                    );
                    $profileId = $this->common_model->insert('user_profile', $array1);
                    $array2 = array(
                        'user_id' => $userId,
                        'school_id' => $params['school_id'],
                        'status' => 1,
                        'subject' => 0,
                        'grade_id' => 0,
                        'batch_id' => 0,
                        'created_by' => $params['user_id'],
                        'created_date' => date('Y-m-d H:i:s')
                    );
                    $profileDetails = $this->common_model->insert('user_profile_details', $array2);
                    $array3 = array(
                        'address_type' => 1,
                        'user_id' => $userId,
                        'name' => $firstName[0],
                        'country' => 231,
                        'created_date' => date('Y-m-d H:i:s')
                    );
                    $userAddress = $this->common_model->insert('user_address', $array3);
                    if ($userId > 0 && $profileId > 0 && $profileDetails > 0 && $userAddress > 0) {
                        $this->jsonarr["IsSuccess"] = true;
                        $this->jsonarr["ResponseObject"] = "Data Inserted Successfully";
                    }
                }
            }
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);

    }

    public function subjectUploadExcel_post(){
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'),true);
        $fileLocation = "../uploads/Migration.xlsx";
        $this->load->library('PHPExcel');
        if (file_exists($fileLocation)) {
            $objPHPExcel = PHPExcel_IOFactory::load($fileLocation);
            $sheet = $objPHPExcel->getsheet(0);
            $highestRow = $sheet->getHighestRow();
            $data = array();
            for ($row = 2; $row <= $highestRow; $row++) {
                $rowData = $sheet->rangeToArray('A'. $row . ':' . 'B' . $row,NULL,TRUE,FALSE);
                if($rowData[0][0] != "" && $rowData[0][1] != "") {
                    $checkSubjectExists = $this->migration_model->checkSubjectExists($rowData[0][1], $params['school_id']);
                    if (!$checkSubjectExists) {
                        $data[] = array(
                            'subject_name' => $rowData[0][1],
                            'school_id' => $params['school_id'],
                            'status' => 1,
                            'edquill_subject_id' => $rowData[0][0],
                        );
                    }
                }
            }
            if(count($data) > 0) {
               $this->common_model->bulkInsert('subject', $data);
            }
            $this->jsonarr["IsSuccess"] = true;
            $this->jsonarr["ResponseObject"] = "Data Inserted Successfully";
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }

  public function studentMappedClass_post(){
      $this->benchmark->mark('code_start');
      $params = json_decode(file_get_contents('php://input'), true);
      $this->load->library('PHPExcel');
      $file = "../uploads/Migration.xlsx";
      $objPHPExcel = PHPExcel_IOFactory::load($file);
      $sheet = $objPHPExcel->getSheet(5);
      $highestRow = $sheet->getHighestRow();
      $studentClass = array();
      for ($row = 2; $row <= $highestRow; $row++) {
          $rowData = $sheet->rangeToArray('A' . $row . ':' . 'C' . $row, NULL, TRUE, FALSE);
          if ($rowData[0][0] != '') {
              $getUser = $this->migration_model->getUser($rowData[0][1]);
              if ($getUser) {
                  $getClass = $this->migration_model->getClass($rowData[0][0]);
                  $studentId = $getUser[0]['user_id'];
                  foreach ($getClass as $key => $value) {
                      $checkExists = $this->migration_model->studentExists($studentId, $value['class_id']);
                      if (count($checkExists) == 0) {
                          $studentClass = array(
                              'class_id' => $value['class_id'],
                              'student_id' => $studentId,
                              'validity' => '',
                              'status' => 1,
                              'created_by' => $params['user_id'],
                              'created_date' => date('Y-m-d H:i:s')
                          );
                          $this->common_model->insert('student_class', $studentClass);
                      }
                  }
//                  if (1 == 1) {
//                      $this->jsonarr["IsSuccess"] = true;
//                      $this->jsonarr["ResponseObject"] = "Data Inserted Successfully";
//                  }

              }
          }
      }
//      if (count($studentClass) > 0) {
//          $this->common_model->bulkInsert('student_class', $studentClass);
//      }
      $this->jsonarr["IsSuccess"] = true;
      $this->jsonarr["ResponseObject"] = "Student Mapped to classes";
      $this->benchmark->mark('code_end');
      $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
      return $this->printjson($this->jsonarr);
  }

    public function weeklyPlan_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $this->load->library('PHPExcel');
        $file = "../uploads/weeklyplan_oct_2023.xlsx";
        $objPHPExcel = PHPExcel_IOFactory::load($file);
        $sheet = $objPHPExcel->getSheet(0);
        $highestRow = $sheet->getHighestRow();
        for ($row = 2; $row <= $highestRow; $row++) {
            $rowData = $sheet->rangeToArray('A' . $row . ':' . 'N' . $row, NULL, TRUE, FALSE);
            if ($rowData[0][1] != '') {
                $startDate = date("Y-m-d", PHPExcel_Shared_Date::ExcelToPHP($rowData[0][11]));
                $endDate = date("Y-m-d", PHPExcel_Shared_Date::ExcelToPHP($rowData[0][12]));
                $checkBookExists = $this->migration_model->checkBook($rowData[0][10], $params['school_id']);
                $getBatchId = $this->migration_model->checkBatchExist($params, $rowData[0][0]);
                //              $getClass = $this->migration_model->getClassId($rowData[0][1]);
                //              if(count($getClass) == 0) {
                if (count($getBatchId) > 0) {
                    $getClass = $this->migration_model->getClassIdBatch($getBatchId[0]['batch_id']);
                } else {
                    $getClass = array();
                }                
                //              }
                if (count($checkBookExists) > 0 && count($getClass) > 0) {
                    foreach ($getClass as $key => $value) {
                        $checkClassContent = $this->migration_model->checkClassContent($value['class_id'], $checkBookExists[0]['content_id'], $startDate, $endDate);
                        $classContentId = count($checkClassContent) > 0 ? $checkClassContent[0]['id'] : 0;
                        if (count($checkClassContent) == 0) {
                            $data = array(
                                'class_id' => $value['class_id'],
                                'content_id' => $checkBookExists[0]['content_id'],
                                'school_id' => $params['school_id'],
                                'status' => 1,
                                'start_date' => date("Y-m-d", PHPExcel_Shared_Date::ExcelToPHP($rowData[0][11])),
                                'end_date' => date("Y-m-d", PHPExcel_Shared_Date::ExcelToPHP($rowData[0][12])),
                                'start_time' => '00:00:00',
                                'end_time' => '23:59:00',
                                'created_by' => $params['user_id'],
                                'created_date' => date('Y-m-d H:i:s')
                            );
                            $classContentId = $this->common_model->insert('class_content', $data);
                        } elseif ($checkClassContent[0]['status'] == 2) {
                            $updateData = array(
                                'status' => 1,
                                'start_date' => date("Y-m-d", PHPExcel_Shared_Date::ExcelToPHP($rowData[0][11])),
                                'end_date' => date("Y-m-d", PHPExcel_Shared_Date::ExcelToPHP($rowData[0][12])),
                                'start_time' => '00:00:00',
                                'end_time' => '23:59:00',
                                'modified_by' => $params['user_id'],
                                'modified_date' => date('Y-m-d H:i:s')
                            );
                            $condition = array('id' => $classContentId);
                            $this->common_model->update('class_content', $updateData, $condition);
                        }
                        $getClassStudents = $this->migration_model->getClassStudents($value['class_id']);
                        foreach ($getClassStudents as $key1 => $value1) {
                            $getStudentGrade = $this->classes_model->studentGrade($params['school_id'], $value1['student_id']);
                            $studentContentExists = $this->migration_model->checkStudentContent($value['class_id'], $value1['student_id'], $checkBookExists[0]['content_id'], $startDate, $endDate);
                            if (count($studentContentExists) == 0) {
                            $studentContent = array(
                                'class_id' => $value['class_id'],
                                'student_id' =>  $value1['student_id'],
                                'content_id' => $checkBookExists[0]['content_id'],
                                'class_content_id' => $classContentId,
                                'grade_id' => $getStudentGrade[0]['grade_id'],
                                'status' => 1,
                                'start_date' => date("Y-m-d", PHPExcel_Shared_Date::ExcelToPHP($rowData[0][11])),
                                'end_date' => date("Y-m-d", PHPExcel_Shared_Date::ExcelToPHP($rowData[0][12])),
                                'created_by' => $params['user_id'],
                                'created_date' => date('Y-m-d H:i:s')
                            );
                            $this->common_model->insert('student_content', $studentContent);
                              }
                        }
                    }
                }
            }
        }
        $this->jsonarr["IsSuccess"] = true;
        $this->jsonarr["ResponseObject"] = "Content Assigned Successfully";
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }
    public function classBatchExcel_post() {
        $this->benchmark->mark('code_start');
        $this->load->library('PHPExcel');
        $file = "../uploads/upgrade_to_next_folder.xlsx";
        $objPHPExcel = PHPExcel_IOFactory::load($file);
        $sheet = $objPHPExcel->getSheet(0);
        $highestRow = $sheet->getHighestRow();
        for ($row = 2; $row <= $highestRow; $row++) {
            $rowData = $sheet->rangeToArray('A' . $row . ':' . 'S' . $row, NULL, TRUE, FALSE);
            if ($rowData[0][5] != '') {
                $data = array('batch_id' => $rowData[0][5]);
                $condition = array('class_id' => $rowData[0][0]);
                $this->common_model->update('class', $data, $condition);
            }
        }
        $this->jsonarr["IsSuccess"] = true;
        $this->jsonarr["ResponseObject"] = "Batch Updated Successfully";
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }

    private function printjson($jsonarr)
    {
        echo json_encode($jsonarr);
    }

    public function getSheetAlphabets($data, $type, $range)
    {
        $alphabet = array();
        if ($type == 1) {
            $i = 0;
            if (count($data) >= 22) {
                foreach (range('A', 'Z') as $key => $value) {
                    foreach (range('A', 'Z') as $key1 => $value1) {
                        if (count($data) + 4 == $i) {
                            $alphabet = 'A' . $value1;
                        }
                        $i++;
                    }
                }
            } else {
                foreach (range('A', 'Z') as $key => $value) {
                    if (count($data) + 1 == $key) {
                        $alphabet = $value;
                    }
                }
            }
        } elseif ($type == 2) {
            $alphabet = array();
            foreach (range('B', $range) as $value) {
                $alphabet[] = $value;
            }
        } elseif ($type == 3) {
            $alphabet = array();
            if (count($data) > 0) {
                $range++;
                for ($i = 'C'; $i < 'ZZ'; $i++) {
                    if ($i != $range) {
                        $alphabet[] = $i;
                    } else {
                        return $alphabet;
                    }
                }
            } else {
                foreach (range('F', $range) as $value) {
                    $alphabet[] = $value;
                }
            }
        }
        return $alphabet;
    }

    public function getQuestionWiseData_post() {
        $params = json_decode(file_get_contents('php://input'), true);
        require APPPATH . 'libraries/PHPExcel.php';
        $object_excel = new PHPExcel();
        $getContentQuestions = $this->migration_model->getContentQuestions($params['content_id']);
        $getAlphabets = $this->getSheetAlphabets($getContentQuestions,1,'');
        $getSpecificAlphabets = $this->getSheetAlphabets($getContentQuestions, 3, $getAlphabets);
        $getWalkin = $this->migration_model->getQuestions($params['class_id'],$params['content_id']);
        $getStudentNames = $this->migration_model->getStudentNames($params['class_id']);
        if ($getWalkin) {
            $totalRow = count($getContentQuestions) + 1;
            $object_excel->getActiveSheet(0)->getStyle('A1:' . $getAlphabets . '1')
                ->getFont()
                ->setSize(10)
                ->setBold(true);
            $object_excel->getActiveSheet(0)->getStyle('A2:' . $getAlphabets . '2' . $totalRow)
                ->getFont()
                ->setSize(10)
                ->setBold(false);
            $object_excel->getActiveSheet(0)->getStyle('A1:' . $getAlphabets . '1')
                ->getFill()
                ->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('FBEF3D');
            $object_excel->getActiveSheet(0)->getRowDimension('1')->setRowHeight(17);
            for ($col = 'A'; $col !== $getAlphabets; $col++) {
                $object_excel->getActiveSheet(0)->getColumnDimension($col)->setAutoSize(true);
            }
            $object_excel->getActiveSheet(0)
                ->getStyle($getAlphabets . '1:' . $getAlphabets . $totalRow)
                ->getNumberFormat()
                ->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER);
            $object_excel->setActiveSheetIndex(0)
                ->setCellValue('A1', 'S.NO')
                ->setCellValue('B1', 'Student Name');
            foreach ($getSpecificAlphabets as $key => $value) {
                $object_excel->setActiveSheetIndex(0)
                    ->setCellValue($value . '1', $getWalkin[$key]['question_no']);
            }
            $body = 2;
            $sno = 0;
            foreach ($getStudentNames as $key => $value) {
                $sno++;
                $object_excel->getActiveSheet(0)
                    ->setCellValue('A' . "$body", "$sno")
                    ->setCellValue('B' . "$body", $getStudentNames[$key]['student_name']);
                    foreach ($getContentQuestions as $questionkey => $questionvalue) {
                        $getAnswerStatus = $this->migration_model->getQuestionAnswerStatus($questionvalue['question_no'],$getStudentNames[$key]['student_id'],$params['content_id'],$params['class_id']);
                        if($getAnswerStatus) {
                            $object_excel->setActiveSheetIndex(0)
                            ->setCellValue($getSpecificAlphabets[$questionkey] . "$body", $getAnswerStatus['answer_status']);
                        } else {
                            $object_excel->setActiveSheetIndex(0)
                            ->setCellValue($getSpecificAlphabets[$questionkey] . "$body", "");
                        }
                    }

                $body++;
            }
            $excelName = "Walkin Detail";
            $object_excel->getActiveSheet(0)->setTitle($excelName);
            $object_excel->setActiveSheetIndex(0);
            $objWriter = new PHPExcel_Writer_Excel2007($object_excel);
            $fileName = 'Report' .$params['content_id'].$params['class_id'] . '.xlsx';
            $path = '../uploads/';
            //$filePath = $path . $fileName;
            $filePath = $path . $fileName;
            $objWriter->save($filePath);
            chmod($filePath, 0777);
        }
    }

}
