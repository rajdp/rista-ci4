<?php
defined('BASEPATH') OR exit('No direct script access allowed');
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: AccessToken");
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');

require APPPATH . '/libraries/REST_Controller.php';

class testing extends REST_Controller
{
    protected $jsonarr = array();
    protected $headers;
    protected $urlAuth;
    protected $controller;

    function __construct()
    {
        parent::__construct();
        //$this->load->model("testing_model");
        $this->load->model("common_model");
        //$this->load->model("content_model");
        //$this->load->model("corporate_model");
        $this->load->database();
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

            'v1/testing/delete',
            'v1/testing/test',
            'v1/testing/getParam',
            'v1/testing/insertAdmin',
            'v1/testing/sendEmailss',
            'v1/testing/tests',
            'v1/testing/insertAutograde',
            'v1/testing/testingfordates',
            'v1/testing/insertStudents',
            'v1/testing/generatePassword',
            'v1/testing/updateSortingNumber',
            'v1/testing/batchSortingNumber',
            'v1/testing/zoomTokenGeneration',
            'v1/testing/ZoomMeetingCreate',
            'v1/testing/updateAnswers'
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
            'v1/testing/delete',
            'v1/testing/sendEmailss',
            'v1/testing/insertAdmin',
            'v1/testing/insertAutograde',
            'v1/testing/testingfordates',
            'v1/testing/insertStudents',
            'v1/testing/generatePassword',
            'v1/testing/updateAnswers'
        );
        foreach ($this->excludeRoutes as $routeString) {
            if ($this->controller == $routeString) {
                return true;
            }
        }
    }

    public function generatePassword_get() {
        $password = 'medplus@321';
        $salt = 'a39';
        $password = sha1($salt . sha1($salt . sha1($password)));
        echo $password;
    }

    public function delete_post()
    {
        $params = json_decode(file_get_contents('php://input'), true);
//        $headers = $this->input->request_headers();
//        $this->common_model->checkPermission($this->controller, $params, $headers);
        if ($params['platform'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Role Id should not be empty";
        } elseif ($params['email_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Email Id should not be empty";
        } else {
            $userId = $this->testing_model->GetuserId($params);
            if($userId) {
                $this->testing_model->deleteAllUser($userId);
                $this->jsonarr["IsSuccess"] = true;
                $this->jsonarr["ResponseObject"] = "Delete successfully";
                }else{
                    $this->jsonarr["IsSuccess"] = false;
                    $this->jsonarr["ErrorObject"] = "already deleted";

                }
            }
        $this->printjson($this->jsonarr);
        }

    public function test_post() {
            //date_default_timezone_set('Asia/Calcutta');
            //$this->printjson(date_default_timezone_get());
            //$this->printjson(date('H:i:s'));
            //echo phpinfo();
            //print_r($this->config->item('server_url'));
//            $params['name'] = "simbu";
//            $params['class'] = "as";
//            $records = "ClassName: {$params['name']}<br>Classcode: {$params['class']}";
//            print_r($records);
//            exit();
            $getFilePath = $this->db->query("SELECT content_id, file_path FROM content where base64_data = ''")->result_array();
            //print_r($getFilePath);
            foreach ($getFilePath as $value) {
                if ($value['file_path'] != '') {
                    $get = json_decode($value['file_path']);
                    //print_r(var_dump($get));
                    if (count($get) > 0) {
                        //print_r($get);
                        $getpath = get_object_vars($get[0]);
                        //print_R($getpath['base64_data']);exit();
                        //$r['base64_data'] = base64_encode(file_get_contents('var/www/rista/uploads/content/' . $getpath['original_image_url']));
                        $r = base64_encode(file_get_contents('var/www/rista/' . $getpath['original_image_url']));
                        $result = json_encode(array((object)array_merge((array)$get[0], $r)));
                        $this->db->query("update content set base64_data = '{$r}' WHERE content_id = {$value['content_id']}");
                    }
                }
            }

        //echo readfile("http://localhost/rista/uploads/content/roughimage/pexels-photo-771742.jpeg");

//            $get = $this->db->query("select points from student_content where student_id = 1387 and content_id = 1579")->result_array();
//            $data = array("status" => 1);
//            print_r(number_format());
            //$this->printjson($get);
        }

    public function insertAdmin12_post() {
            $getSchools = $this->db->query("SELECT s.school_id, (SELECT email_id from user where FIND_IN_SET(s.school_id,school_id) and role_id = 2 limit 0,1) as email_id from school s WHERE s.institution_type != 2")->result_array();
            foreach($getSchools as $key => $value) {
                if ($value['email_id'] != '') {
                    $this->db->query("insert into admin_settings_school (name,description,value,school_id,settings,status) values ('teacher_activity_email','','{$value['email_id']}','{$value['school_id']}','2','1'),('teacher_notify_email_admin','','','{$value['school_id']}','2','1')");
                }
            }
        }

    public function insertAutograde12_get() {
            $getSchools = $this->db->query("SELECT s.school_id, (SELECT email_id from user where FIND_IN_SET(s.school_id,school_id) and role_id = 2 limit 0,1) as email_id from school s WHERE s.institution_type != 2")->result_array();
            foreach($getSchools as $key => $value) {
                if ($value['email_id'] != '') {
                    $this->db->query("insert into admin_settings_school (name,description,value,school_id,settings,status) values ('allow_autograde_settings','','1','{$value['school_id']}','2','1')");
                }
            }
        }

    public function tests_post() {
        print_r($this->ip_info());
        $timezone = geoip_time_zone_by_country_and_region('CA', 'QC');
        print_r($timezone);
        $date = new DateTime(null, new DateTimeZone($timezone));
        $tz = $date->getTimezone();
        print_r($tz);
        exit();
            $checkRequestExists = $this->db->query("SELECT corporate_id FROM corporate")->result_array();
            foreach($checkRequestExists as $key => $value) {
                $data = array('corporate_code' => strtoupper($this->common_model->random_strings(10)));
                $condition = array('corporate_id' => $value['corporate_id']);
                $this->common_model->update('corporate', $data, $condition);
            }
        }

    public function sendEmailss_post() {

        $params = json_decode(file_get_contents('php://input',true));
        print_R($params);
        foreach($params as $key => $value) {
            print_R($value->user);
        }
        return $this->printjson($this->jsonarr);
        }

    public function testingfordates_get()
        {
            $dates = array('from_date' => date('Y-m-01'),'to_date' => date('Y-m-d',strtotime('-1 days')));
            $getDates = $this->getDates($dates);
            $concat = '';
            foreach($getDates as $key => $value) {
                if (count($getDates) == $key + 1) {
                    $concat .= "(CASE WHEN (SELECT COUNT(prescriptionid) FROM mt_medical_records WHERE doctorid=mm.doctor_id and prescriptiondate = '{$value['dates']}' AND primary_branch_id = a.branch_id)= 0 THEN 0 ELSE (SELECT COUNT(prescriptionid) FROM mt_medical_records WHERE doctorid=mm.doctor_id and prescriptiondate = '{$value['dates']}' AND primary_branch_id = a.branch_id) END) as '{$value['dates']}'";
                } else {
                    $concat .= "(CASE WHEN (SELECT COUNT(prescriptionid) FROM mt_medical_records WHERE doctorid=mm.doctor_id and prescriptiondate = '{$value['dates']}' AND primary_branch_id = a.branch_id)= 0 THEN 0 ELSE (SELECT COUNT(prescriptionid) FROM mt_medical_records WHERE doctorid=mm.doctor_id and prescriptiondate = '{$value['dates']}' AND primary_branch_id = a.branch_id) END) as '{$value['dates']}',";
                }
            }
            $result = $this->db->query("select (SELECT hospital_name FROM mt_hospital WHERE hospital_id = (SELECT hospital_id FROM mt_hospital_branch WHERE branch_id=mm.primary_branch_id)) as HOSPITAL_NAME,a.branch_name as BRANCH_NAME,mm.doctorname as DOCTOR_NAME,
                                        mm.speciality as SPECIALITY, DATE_FORMAT(mm.createddate,'%d/%m/%Y') as DATE_OF_JOINING,
                                        $concat from mt_medical_doctor as mm left join mt_hospital h on h.hospital_id=mm.primary_branch_id left join mt_hospital_branch a on a.branch_id=mm.primary_branch_id GROUP by mm.doctor_id")->result_array();
            $total = 0;
            foreach($result as $key => $value) {
                foreach($value as $key1 =>  $value1) {
                    if ($key1 > 3) {
                        $total += $value1;
                    }
                }
            }
            array_push($result,$total);
            print_R($result);

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
            $fromDate = strtotime($addDate, $fromDate);
            $i++;
        }
        return $dates;
    }

    public function insertStudents_get() {
        $this->load->library('PHPExcel');
        $file = "../uploads/edquillstudents.xlsx";
        $objPHPExcel = PHPExcel_IOFactory::load($file);
        $sheet = $objPHPExcel->getSheet(0);
        $highestRow = $sheet->getHighestRow();
        for ($row = 1; $row <= $highestRow; $row++) {
            $rowData = $sheet->rangeToArray('A' . $row . ':' . 'S' . $row, NULL, TRUE, FALSE);
            if ($rowData[0][0] != '') {
                $user['role_id'] = 5;
                $user['status'] = 1;
                $user['school_id'] = 2;
                $user['login_type'] = 'WEB';
                $user['created_by'] = 1;
                $user['created_date'] = date('Y-m-d H:i:s');
                $user['email_id'] = $rowData[0][5];
                if ($rowData[0][7] != "" && $rowData[0][8] != "" && $rowData[0][9] != "") {
                    $user['mobile'] = $rowData[0][7] . "," . $rowData[0][8] . "," . $rowData[0][9];
                } elseif ($rowData[0][7] != "" && $rowData[0][8] != "") {
                    $user['mobile'] = $rowData[0][7] . "," . $rowData[0][8] . ",";
                } elseif ($rowData[0][7] != "") {
                    $user['mobile'] = $rowData[0][7] . "," . ",";
                } elseif ($rowData[0][7] == "" && $rowData[0][8] == "" && $rowData[0][9] == "") {
                    $user['mobile'] = "," . ",";
                }
                $id = $this->common_model->insert('user', $user);
                if ($id > 0) {
                    $userProfile['user_id'] = $id;
                    $userProfile['created_date'] = date('Y-m-d H:i:s');
                    $userProfile['first_name'] = $rowData[0][0];
                    $userProfile['last_name'] = $rowData[0][1];
                    if($rowData[0][14] != ''){
                        $data = explode('/',$rowData[0][14]);
                        $userProfile['birthday'] = $data[2].'-'.$data[1].'-'.$data[0];
                    } else {
                        $userProfile['birthday'] = '0000-00-00';
                    }
                    $userProfile['created_by'] = 1;
                    $userProfile['created_date'] = date('Y-m-d H:i:s');
                    $profileId = $this->common_model->insert('user_profile', $userProfile);
                    if ($profileId > 0) {
                        $userProfileDetails['user_id'] = $id;
                        $userProfileDetails['school_id'] = 2;
                        $userProfileDetails['edit_status'] = 1;
                        if ($rowData[0][16] != "") {
                            $date = explode("-", date("d-m-Y", PHPExcel_Shared_Date::ExcelToPHP($rowData[0][16])));
                            $doj = $date[2] . "-" . $date[0] . "-" . $date[1];
                            $userProfileDetails['doj'] = $doj;
                        }
                        if ($rowData[0][17] != "" && $rowData[0][17] != '0000-00-00') {
                            $date = explode("-", date("d-m-Y", PHPExcel_Shared_Date::ExcelToPHP($rowData[0][17])));
                            $dropped = $date[2] . "-" . $date[1] . "-" . $date[0];
                            $userProfileDetails['dropped_date'] = $dropped;
                        }
                        $grade = $this->common_model->searchGrade($rowData[0][2], 2);
                        if (count($grade) > 0) {
                            $userProfileDetails['grade_id'] = $grade[0]['grade_id'];
                        } else {
                            $userProfileDetails['grade_id'] = 0;
                        }
                        $userProfileDetails['created_by'] = 1;
                        $userProfileDetails['created_date'] = date('Y-m-d H:i:s');
                        $profileDetailId = $this->common_model->insert('user_profile_details', $userProfileDetails);
                        if ($profileDetailId > 0) {
                            $userAddress1['state'] = $rowData[0][12];
                            $userAddress1['country'] = 231;
                            $userAddress1['user_id'] = $id;
                            $userAddress1['address_type'] = 2;
                            $userAddress1['email_ids'] = $rowData[0][6];
                            $userAddress1['name'] = $rowData[0][3] . ' ' . $rowData[0][4];
                            $userAddress1['address1'] = $rowData[0][10];
                            $userAddress1['address2'] = "";
                            $userAddress1['city'] = $rowData[0][11];
                            $userAddress1['postal_code'] = $rowData[0][13];
                            $userAddress1['created_date'] = date('Y-m-d H:i:s');
                            $this->common_model->insert('user_address', $userAddress1);
                        }
                    }
                }
            }
        }
    }

    public function ip_info($ip = NULL, $purpose = "location", $deep_detect = TRUE) {
    $output = NULL;
    if (filter_var($ip, FILTER_VALIDATE_IP) === FALSE) {
        $ip = $_SERVER["REMOTE_ADDR"];
        if ($deep_detect) {
            if (filter_var(@$_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP))
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            if (filter_var(@$_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP))
                $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
    }
    $purpose    = str_replace(array("name", "\n", "\t", " ", "-", "_"), NULL, strtolower(trim($purpose)));
    $support    = array("country", "countrycode", "state", "region", "city", "location", "address");
    $continents = array(
        "AF" => "Africa",
        "AN" => "Antarctica",
        "AS" => "Asia",
        "EU" => "Europe",
        "OC" => "Australia (Oceania)",
        "NA" => "North America",
        "SA" => "South America"
    );
    if (filter_var($ip, FILTER_VALIDATE_IP) && in_array($purpose, $support)) {
        $ipdat = @json_decode(file_get_contents("http://www.geoplugin.net/json.gp?ip=" . $ip));
        if (@strlen(trim($ipdat->geoplugin_countryCode)) == 2) {
            switch ($purpose) {
                case "location":
                    $output = array(
                        "city"           => @$ipdat->geoplugin_city,
                        "state"          => @$ipdat->geoplugin_regionName,
                        "country"        => @$ipdat->geoplugin_countryName,
                        "country_code"   => @$ipdat->geoplugin_countryCode,
                        "continent"      => @$continents[strtoupper($ipdat->geoplugin_continentCode)],
                        "continent_code" => @$ipdat->geoplugin_continentCode
                    );
                    break;
                case "address":
                    $address = array($ipdat->geoplugin_countryName);
                    if (@strlen($ipdat->geoplugin_regionName) >= 1)
                        $address[] = $ipdat->geoplugin_regionName;
                    if (@strlen($ipdat->geoplugin_city) >= 1)
                        $address[] = $ipdat->geoplugin_city;
                    $output = implode(", ", array_reverse($address));
                    break;
                case "city":
                    $output = @$ipdat->geoplugin_city;
                    break;
                case "state":
                    $output = @$ipdat->geoplugin_regionName;
                    break;
                case "region":
                    $output = @$ipdat->geoplugin_regionName;
                    break;
                case "country":
                    $output = @$ipdat->geoplugin_countryName;
                    break;
                case "countrycode":
                    $output = @$ipdat->geoplugin_countryCode;
                    break;
            }
        }
    }
    return $output;
}

public function updateSortingNumber_post(){
    $getSchool = $this->db->query("SELECT DISTINCT(school_id) from grade WHERE school_id != 0")->result_array();
    foreach( $getSchool as $key => $value){
      $gradeList =  $this->db->query("SELECT grade_id from grade WHERE school_id = {$value['school_id']}")->result_array();
      foreach($gradeList as $key1 => $value1){
         $data = array('sorting_no' => $key1 + 1);
         $condition = array('grade_id' => $value1['grade_id']);
         $this->common_model->update('grade', $data, $condition);    
      }
    }
}

    public function batchSortingNumber_post()
    {
        $getSchool = $this->db->query("SELECT DISTINCT(school_id) from batch WHERE school_id != 0 AND school_id != 1")->result_array();
        foreach ($getSchool as $key => $value) {
            $batchList =  $this->db->query("SELECT batch_id from batch WHERE school_id = {$value['school_id']}")->result_array();
            foreach ($batchList as $key1 => $value1) {
                $data = array('display_order' => $key1 + 1);
                $condition = array('batch_id' => $value1['batch_id']);
                $this->common_model->update('batch', $data, $condition);
            }
        }
    }
    public function ZoomMeetingCreate_post()
    {
            $zoomParams = [
                [
                    'name' => 'zoom_apikey',
                    'value' => 'aLaLVl6A8wS83hI7amfz6uqHMJ3FhxST'
                ],
                [
                    'name' => 'zoom_secretkey',
                    'value' => 'NlRBESZYREiwhNXMzTTQQ'
                ],
                [
                    'name' => 'zoom_user_email',
                    'value' => 'info@xtracurriculum.com'
                ],
                [
                    'name' => 'timezone',
                    'value' => 'America/Los_Angeles'
                ]
            ];
            $zoomKey = [];
            $email = '';
       // ZoomMeeting
            $timeZone = '';
            for ($a = 0; $a < count($zoomParams); $a++) {
                if ($zoomParams[$a]['name'] == 'zoom_apikey') {
                    array_push($zoomKey, $zoomParams[$a]);
                } elseif ($zoomParams[$a]['name'] == 'zoom_secretkey') {
                    array_push($zoomKey, $zoomParams[$a]);
                } elseif ($zoomParams[$a]['name'] == 'zoom_user_email') {
                    $explode = explode(',', $zoomParams[$a]['value']);
                    $email = $explode[0];
                    $zoomParams[$a]['value'] = $explode[0];
                    array_push($zoomKey, $zoomParams[$a]);
                } elseif ($zoomParams[$a]['name'] == "timezone") {
                    $timeZone = $this->getTimeZone($zoomParams[$a]['value']);
                }
            }
            $this->load->library('ZoomMeeting', $zoomKey);
        //   $className = $this->common_model->className($params['class_id']);
            $className[0]['class_name'] = "kishore zoom";
        //  $params['school_id'] = 1;
            $params['duration'] = 30;
            $value['slotstarttime'] = '19:30';
            $value['slotendtime'] = '20:30';
            $params['start_date'] = '2024:05:31';
            $value['slotday']  = 2;
            $value['email_id'] = "info@xtracurriculum.com";
            $data = [

                'agenda' => $className[0]['class_name'],
                'default_password' => false,
                "pre_schedule" => false,
                "password" => '',
                "duration" => $params['duration'],
                "schedule_for" => $email,
                'settings' => [
                'auto_recording' => "cloud",
                'host_video' => false,
                    'participant_video' => false,
                    'password' => '',
                    'join_before_host' => false,
                    'audio' => true,
                    'approval_type' => "2",
                ],
                "start_time" => "2022-03-21T15:00:Z",
                'topic' => $className[0]['class_name'],
                "type" => "2"
            ];
            $startTime = date('h:i', strtotime($value['slotstarttime']));
            $endTime = date('h:i:s', strtotime($value['slotendtime']));
            $slotDayZoom = ($value['slotday'] + 1);
            $slotDay = $slotDayZoom % 7;

            if ($timeZone != '') {
                $date = $params['start_date'] . $startTime;
                $d = date('Y-m-dTh:i:s', strtotime($date));
            }
            $data['start_time'] = $params['start_date'] . 'T' . $startTime . ':Z';
            if ($timeZone != '') {
                $data['start_time'] = $d;
            }
            $data['topic'] = $params['start_date'] . 'T' . $startTime . ':Z';
            $data['topic'] = $className[0]['class_name'];
            $this->meeting = new ZoomMeeting($zoomKey);
            $url = "https://api.zoom.us/v2/users/{$value['email_id']}/meetings";
            $meeting =  $this->curlCall($url, $data, 'POST', 'create_meeting', $params['school_id']);
            print_r(json_encode($meeting));exit;
            if ($meeting) {
                return $meeting;
            }
    }

    public function ZoomMeetingUpdate($params, $value)
    {
        if ($params['allow_zoom_api'] == 1 && $params['class_type'] == 1) {
            $zoomParams = $this->common_model->zoomKeys($params['school_id'], 1);
            //print_r($zoomParams); exit();
            $zoomKey = [];
            $email = '';
            $timeZone = '';
            for ($a = 0; $a < count($zoomParams); $a++) {
                if ($zoomParams[$a]['name'] == 'zoom_apikey') {
                    array_push($zoomKey, $zoomParams[$a]);
                } elseif ($zoomParams[$a]['name'] == 'zoom_secretkey') {
                    array_push($zoomKey, $zoomParams[$a]);
                } elseif ($zoomParams[$a]['name'] == 'zoom_user_email') {
                    $explode = explode(',', $zoomParams[$a]['value']);
                    $email = $explode[0];
                    $zoomParams[$a]['value'] = $explode[0];
                    if ($value['email_id'] != '') {
                        $email = $value['email_id'];
                        $zoomParams[$a]['value'] = $value['email_id'];
                    }
                    array_push($zoomKey, $zoomParams[$a]);
                    //                array_push($zoomKey, $zoomParams[$a]);
                    //                $email = $zoomParams[$a]['value'];
                } elseif ($zoomParams[$a]['name'] == "timezone") {
                    $timeZone = $this->getTimeZone($zoomParams[$a]['value']);
                }
            }
            $className = $this->common_model->className($params['class_id']);
            $data = [
                'agenda' => $className[0]['class_name'],
                'default_password' => false,
                "pre_schedule" => false,
                "password" => '',
                "duration" => $params['duration'],
                //            "recurrence" => [
                //                "end_date_time" => "2022-03-23T16:00:00Z",
                //                "type" => "2",
                //                "weekly_days" => "1,2,3"
                //            ],
                "schedule_for" => $email,
                'settings' => [
                    // audo_recording cloud ,local
                    'auto_recording' => "cloud",
                    'host_video' => false,
                    'participant_video' => false,
                    'password' => '',
                    'join_before_host' => false,

                    'audio' => true,
                    'approval_type' => "2",


                ],
                "start_time" => "2022-03-21T15:00:Z",
                'topic' => $className[0]['class_name'],
                "type" => "2"
            ];

            $startTime = date('h:i', strtotime($value['slotstarttime']));
            $endTime = date('h:i:s', strtotime($value['slotendtime']));
            $slotDayZoom = ($value['slotday'] + 1);
            $slotDay = $slotDayZoom % 7;
            if ($timeZone != '') {
                $date = $params['start_date'] . $startTime;
                date_default_timezone_set($timeZone[0]['time_zone']);
                $d = date('Y-m-dTh:i:s', strtotime(date('Y-m-d H:i:s')));
            }
            //print_r($slotDay); //exit();
            //            $data['recurrence']['end_date_time'] = $params['end_date'] . 'T' . $endTime . 'Z';
            //            $data['recurrence']['weekly_days'] = $slotDay;
            $data['start_time'] = $params['start_date'] . 'T' . $startTime . ':Z';
            if ($timeZone != '') {
                $data['start_time'] = $d;
            }
            $data['topic'] = $params['start_date'] . 'T' . $startTime . ':Z';
            $data['topic'] = $className[0]['class_name'] . $className[0]['grade_name'] . $className[0]['subject_name'];
            $count = count($zoomKey);
            $meetingId = $this->common_model->getMeetingid($params, $value['slotday'], $params['schedule_id']);
            $zoomKey[$count]['name'] = 'meeting_id';
            $zoomKey[$count]['value'] = $meetingId['meeting_id'];

            $meetingId = $meetingId['meeting_id'];
            $this->load->library('ZoomMeeting', $zoomKey); //exit();
            $this->meeting = new ZoomMeeting($zoomKey);
            $url = "https://api.zoom.us/v2/meetings/{$meetingId}";
            $meeting =  $this->curlCall($url, $data, 'PATCH', 'update_meeting', $params['school_id']);
            //  $meeting = $this->meeting->update($data,$meetingId);
            if ($meeting) {
                $data = array('start_time' => $value['slotstarttime'], 'end_time' => $value['slotendtime']);
                $condition = array('class_id' => $params['class_id'], 'id' => $value['schedule_id']);
                $this->common_model->update('class_schedule', $data, $condition);
                return true;
            } else {
                return false;
            }

            //        foreach ($params['availabilityDate'] as $value) {
            //            $startTime = date('h:i', strtotime($value['slotstarttime']));
            //            $endTime = date('h:i:s', strtotime($value['slotendtime']));
            //            $slotDayZoom = ($value['slotday'] + 1);
            //            $slotDay = $slotDayZoom % 7;
            //
            //            //print_r($slotDay); //exit();
            //            $data['recurrence']['end_date_time'] = $params['end_date'] . 'T' . $endTime . 'Z';
            //            $data['recurrence']['weekly_days'] = $slotDay;
            //            $data['start_time'] = $params['start_date'] . 'T' . $startTime . ':Z';
            //            $data['topic'] = $params['start_date'] . 'T' . $startTime . ':Z';
            //            $data['topic'] = $className[0]['class_name'] . $className[0]['grade_name'] . $className[0]['subject_name'];
            //            $count = count($zoomKey);
            //            $meetingId = $this->common_model->getMeetingid($params,$value['slotday']);
            //            $zoomKey[$count]['name'] = 'meeting_id';
            //            $zoomKey[$count]['value'] = $meetingId['meeting_id'];
            //
            //            $meetingId = $meetingId['meeting_id'];
            //            $this->load->library('ZoomMeeting',$zoomKey); //exit();
            //            $this->meeting = new ZoomMeeting($zoomKey);
            //            $meeting = $this->meeting->update($data,$meetingId);
            //            //print_r($meeting); exit();
            //             }
        }
    }

    public function zoomrecordings($params, $meetingId, $scheduleId)
    {
        //$params = array('school_id' => 303,'class_id' => 1835,'slot_days' => 2);
        $zoomParams = $this->common_model->zoomKeys($params['school_id'], 1);
        if ($scheduleId != '') {
            $userEmail = $this->getZoomEmail($params, $scheduleId, 0);
        }
        $zoomPerm = '';
        $zoomKey = [];
        $email = '';
        for ($a = 0; $a < count($zoomParams); $a++) {
            if ($zoomParams[$a]['name'] == 'zoom_apikey') {
                array_push($zoomKey, $zoomParams[$a]);
            } elseif ($zoomParams[$a]['name'] == 'zoom_secretkey') {
                array_push($zoomKey, $zoomParams[$a]);
            } elseif ($zoomParams[$a]['name'] == 'zoom_user_email') {
                $zoomParams[$a]['value'] = $userEmail[0]['user_email'];
                $email = $userEmail[0]['user_email'];
                array_push($zoomKey, $zoomParams[$a]);
                //                array_push($zoomKey, $zoomParams[$a]);
                //                $email = $zoomParams[$a]['value'];
            } elseif ($zoomParams[$a]['name'] == 'allow_zoom_api') {
                $zoomPerm = $zoomParams[$a]['value'];
            }
        }

        if ($zoomPerm == 1) {

            $mId = $meetingId;

            //print_r($zoomKey); exit();
            $this->load->library('ZoomMeeting', $zoomKey);
            $this->meeting = new ZoomMeeting($zoomKey); //exit();
            $data = [
                'agenda' => 'test',
                'default_password' => false,
                "pre_schedule" => false,
                "password" => '',
                "recurrence" => [
                    "end_date_time" => "2022-03-31T04:00:00Z",
                    "type" => "2",
                    "weekly_days" => "1,2,3"
                ],
                "schedule_for" => $email,
                'settings' => [
                    // audo_recording cloud ,local
                    'auto_recording' => "cloud",
                    'host_video' => false,
                    'participant_video' => false,
                    'password' => '',
                    'join_before_host' => false,

                    'audio' => true,
                    'approval_type' => "2",


                ],
                "start_time" => "2022-03-22T10:05:00Z",
                'topic' => 'recurring testdddd',
                "type" => "8"
            ];
            $url = "https://api.zoom.us/v2/meetings/{$mId}/recordings";
            $meeting =  $this->curlCall($url, '', 'GET', 'recordings', $params['school_id']);
            // $meeting = $this->meeting->recordings($data, $mId, $zoomKey);
            //print_r($meeting);
            if ($meeting) {
                return $meeting;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function listOfZoomInstances($params, $meetingId, $scheduleId)
    {
        $zoomParams = $this->common_model->zoomKeys($params['school_id'], 1);
        if ($scheduleId != '') {
            $userEmail = $this->getZoomEmail($params, $scheduleId, 0);
        }
        $zoomPerm = '';
        $zoomKey = [];
        $email = '';
        for ($a = 0; $a < count($zoomParams); $a++) {
            if ($zoomParams[$a]['name'] == 'zoom_apikey') {
                array_push($zoomKey, $zoomParams[$a]);
            } elseif ($zoomParams[$a]['name'] == 'zoom_secretkey') {
                array_push($zoomKey, $zoomParams[$a]);
            } elseif ($zoomParams[$a]['name'] == 'zoom_user_email') {
                //$email = $zoomParams[$a]['value'];
                if ($userEmail) {
                    $zoomParams[$a]['value'] = $userEmail[0]['user_email'];
                    $email = $userEmail[0]['user_email'];
                }
                array_push($zoomKey, $zoomParams[$a]);
            } elseif ($zoomParams[$a]['name'] == 'allow_zoom_api') {
                $zoomPerm = $zoomParams[$a]['value'];
            }
        }
        if ($zoomPerm == 1) {
            $mId = $meetingId;
            $this->load->library('ZoomMeeting', $zoomKey);
            $this->meeting = new ZoomMeeting($zoomKey); //exit();
            $data = [
                'agenda' => 'test',
                'default_password' => false,
                "pre_schedule" => false,
                "password" => '',
                "recurrence" => [
                    "end_date_time" => "2022-03-31T04:00:00Z",
                    "type" => "2",
                    "weekly_days" => "1,2,3"
                ],
                "schedule_for" => $email,
                'settings' => [
                    // audo_recording cloud ,local
                    'auto_recording' => "cloud",
                    'host_video' => false,
                    'participant_video' => false,
                    'password' => '',
                    'join_before_host' => false,

                    'audio' => true,
                    'approval_type' => "2",


                ],
                "start_time" => "2022-03-22T10:05:00Z",
                'topic' => 'recurring testdddd',
                "type" => "8"
            ];
            //  $meeting = $this->meeting->listMeetingInstances($data, $mId, $zoomKey);
            $url = "https://api.zoom.us/v2/past_meetings/{$mId}/instances";
            $meeting =  $this->curlCall($url, $data, 'GET', 'list_meeting', $params['school_id']);

            if ($meeting) {
                return $meeting;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function getMeetingStatus($scheduleId, $meetingId, $params, $classId)
    {
        $zoomParams = $this->common_model->zoomKeys($params['school_id'], 1);
        if ($scheduleId != '') {
            $params['class_id'] = $classId;
            $userEmail = $this->getZoomEmail($params, $scheduleId, 1);
        }
        $zoomKey = [];
        for ($a = 0; $a < count($zoomParams); $a++) {
            if ($zoomParams[$a]['name'] == 'zoom_apikey') {
                array_push($zoomKey, $zoomParams[$a]);
            } elseif ($zoomParams[$a]['name'] == 'zoom_secretkey') {
                array_push($zoomKey, $zoomParams[$a]);
            } elseif ($zoomParams[$a]['name'] == 'zoom_user_email') {
                if ($userEmail) {
                    $zoomParams[$a]['value'] = $userEmail[0]['user_email'];
                    $email = $userEmail[0]['user_email'];
                    array_push($zoomKey, $zoomParams[$a]);
                }
            } elseif ($zoomParams[$a]['name'] == 'allow_zoom_api') {
                $zoomPerm = $zoomParams[$a]['value'];
            }
        }
        if ($zoomPerm == 1) {
            $this->load->library('ZoomMeeting', $zoomKey);
            $this->meeting = new ZoomMeeting($zoomKey);
            $data = [
                'meeting_id' => $meetingId
            ];
            $url = "https://api.zoom.us/v2/meetings/{$meetingId}";
            $meeting =  $this->curlCall($url, '', 'GET', 'update_meeting_status', $params['school_id']);
            // $meeting = $this->meeting->getMeetingStatus($data, $meetingId, $zoomKey);
            if ($meeting) {
                return $meeting;
            } else {
                return false;
            }
        }
    }

    public function checkMeetingStatus($userEmail, $meetingId, $params)
    {
        $zoomParams = $this->common_model->zoomKeys($params['school_id'], 1);
        $zoomKey = [];
        for ($a = 0; $a < count($zoomParams); $a++) {
            if ($zoomParams[$a]['name'] == 'zoom_apikey') {
                array_push($zoomKey, $zoomParams[$a]);
            } elseif ($zoomParams[$a]['name'] == 'zoom_secretkey') {
                array_push($zoomKey, $zoomParams[$a]);
            } elseif ($zoomParams[$a]['name'] == 'zoom_user_email') {
                $zoomParams[$a]['value'] = $userEmail;
                array_push($zoomKey, $zoomParams[$a]);
            } elseif ($zoomParams[$a]['name'] == 'allow_zoom_api') {
                $zoomPerm = $zoomParams[$a]['value'];
            }
        }
        if ($zoomPerm == 1) {
            $this->load->library('ZoomMeeting', $zoomKey);
            $this->meeting = new ZoomMeeting($zoomKey);
            $data = [
                'meeting_id' => $meetingId
            ];
            $url = "https://api.zoom.us/v2/meetings/{$meetingId}";
            $meeting =  $this->curlCall($url, '', 'GET', 'update_meeting_status', $params['school_id']);
            // $meeting = $this->meeting->getMeetingStatus($data, $meetingId, $zoomKey);
            if ($meeting) {
                return $meeting;
            } else {
                return false;
            }
        }
    }

    public function getZoomEmail($params, $scheduleId, $type)
    {
        $condition = "";
        if ($type == 1) {
            $condition = "AND start_date = '{$params['start_date']}'";
        }
        $data = $this->db->query("SELECT user_email from zoom_creation_email where class_id = '{$params['class_id']}' AND schedule_id = '$scheduleId' $condition")->result_array();
        return $data;
    }

    public function getTimeZone($id)
    {
        $data = $this->db->query("SELECT time_zone FROM time_zone where id = '$id'")->result_array();
        return $data;
    }

    public function getZoomToken($schoolId)
    {
        $getToken = $this->getToken($schoolId);
        if (count($getToken) > 0) {
            $currentDate = date('Y-m-d H:i:s');
            $token = $getToken[0]['access_token'];
            if ($getToken[0]['expiry_date'] <= $currentDate) {
                $condition = "WHERE name = 'zoom_account_id' AND school_id = {$schoolId} ";
                $zoomConfig = $this->getZoomKeys($condition);
                $url = "https://zoom.us/oauth/token?grant_type=account_credentials&account_id={$zoomConfig[0]['value']}";
                $response = $this->curlCall($url, '', 'POST', 'token_generation', $schoolId);
                $seconds =   $response['expires_in'] - 599 . ' seconds';
                $expiryDate = date("Y-m-d H:i:s", strtotime('+' . $seconds));
                $zoomToken['access_token'] = $response['access_token'];
                $zoomToken['expiry_date'] = $expiryDate;
                $condition = array('id' => $getToken[0]['id']);
                $this->common_model->update('zoom_token', $zoomToken, $condition);
                $token =  $zoomToken['access_token'];
            }
        }
        return $token;
    }

    public function zoomTokenGeneration_post()
    {
       // $condition = "WHERE name = 'zoom_account_id' AND school_id = {$params['school_id']} ";
      //  $zoomConfig = $this->getZoomKeys($condition);
        $zoom_account_id = 'qTrs7JVLRK2ckvz9hQDjOg';
        $params['school_id'] = 1;
        $url = "https://zoom.us/oauth/token?grant_type=account_credentials&account_id=$zoom_account_id";
        $response = $this->curlCall($url, '', 'POST', 'token_generation', $params['school_id']);
        print_r($response);exit;
        $seconds =   $response['expires_in'] - 599 . ' seconds';
        $expiryDate = date("Y-m-d H:i:s", strtotime('+' . $seconds));
        $zoomToken['access_token'] = $response['access_token'];
        $zoomToken['expiry_date'] = $expiryDate;
        $zoomToken['school_id'] = $params['school_id'];
        $zoomToken['created_by'] = 1;
        $zoomToken['created_date'] = date('Y-m-d H:i:s');
        $this->common_model->insert('zoom_token', $zoomToken);
        return $response;
    }

    public function curlCall($url, $params, $method, $type, $schoolId)
    {
        $url = trim($url);
        if ($type == 'token_generation') {
            $zoom_secretkey = 'NlRBESZYREiwhNXMzTTQQ';
            $zoom_apikey = 'aLaLVl6A8wS83hI7amfz6uqHMJ3FhxST';
           // $condition = "WHERE school_id = {$schoolId} AND name = 'zoom_secretkey' OR name = 'zoom_apikey' ORDER BY id";
           // $zoomConfig = $this->getZoomKeys($condition);
            $token = base64_encode($zoom_secretkey . ':' . $zoom_apikey);
            $headers = array(
                "Accept: application/json",
                "Content-type: application/x-www-form-urlencoded",
                "Authorization: Basic $token",
            );
        } else {
          //  $token = $this->getZoomToken($schoolId);
            $token = "eyJzdiI6IjAwMDAwMSIsImFsZyI6IkhTNTEyIiwidiI6IjIuMCIsImtpZCI6IjZiYzZjZjVmLWFjZTMtNDVkNy1iYTU3LWNlNWFmOGZhYTAxMiJ9.eyJhdWQiOiJodHRwczovL29hdXRoLnpvb20udXMiLCJ1aWQiOiJLTXpCaHJrUVNrdXVOSE1rRUxNTEtBIiwidmVyIjo5LCJhdWlkIjoiMWZlMmNkMzRiNmRkZGJjNWNjZTcxODM2NzJjN2U1N2IiLCJuYmYiOjE3MTcxNDk1NjEsImNvZGUiOiJYU0Y1NzJYWlJWeWJsakJuc0RmRkh3RXYzMjVVa2VQa1AiLCJpc3MiOiJ6bTpjaWQ6TmxSQkVTWllSRWl3aE5YTXpUVFFRIiwiZ25vIjowLCJleHAiOjE3MTcxNTMxNjEsInR5cGUiOjMsImlhdCI6MTcxNzE0OTU2MSwiYWlkIjoicVRyczdKVkxSSzJja3Z6OWhRRGpPZyJ9.AdvoHW8NePUxRZhe95N8koV4y1jvKZVUNJ0FVp85PE3Vqqfu2tapyXF6PABdtfjoRMw-_K-y_bSUR0jah6rdSg";
            $headers = array(
                "Accept: application/json",
                "Content-type: application/json",
                "Authorization: Bearer $token",
            );
        }

        $crl = curl_init($url);
        if ($method == 'GET') {
            curl_setopt($crl, CURLOPT_URL, $url);
            curl_setopt($crl, CURLOPT_FRESH_CONNECT, true);
        } else if ($method == 'POST') {
            curl_setopt($crl, CURLOPT_POST, true);
            curl_setopt($crl, CURLOPT_POSTFIELDS, json_encode($params));
        } elseif ($method == 'DELETE' || $method == 'PATCH') {
            curl_setopt($crl, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($crl, CURLOPT_POSTFIELDS, json_encode($params));
        } else {
            curl_setopt($crl, CURLOPT_POST, true);
            curl_setopt($crl, CURLOPT_POSTFIELDS, $params);
        }
        curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($crl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($crl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($crl, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($crl);
        $this->createLog($params, $url, $response, 'token', "curl_log");
        $this->createLog($headers, $url, $response, "$type", "curl_log");
        $httpCode = curl_getinfo($crl, CURLINFO_HTTP_CODE);
        $responseArray = [];
        if ($httpCode == 200 || $httpCode == 400 || $httpCode == 201) {
            $responseArray = json_decode($response, true);
        }
        if ($httpCode != 200) {
            $httpResponse = "Return code is {$httpCode} \n" . curl_error($crl);
            //  $this->createLog($params, $url, $httpResponse, "Error While Running CURL", "curl_log");
        }
        return $responseArray;
    }

    public function createLog($data, $url, $responseArray, $usage, $logType)
    {
        $controller_name =  "Zoom";
        $log_filename = "./application/logs/v1";
        $log_msg = array();
        $log_msg['http_referer'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        $log_msg['request'] = $data;
        $log_msg['url'] = $url;
        $log_msg['response'] = $responseArray;
        $log_msg['usage'] = $usage;
        $log_msg['date'] = date("Y-m-d H:i:s");
        $log_msg = json_encode($log_msg, JSON_UNESCAPED_SLASHES);
        $log_msg = stripslashes(stripslashes(stripslashes($log_msg)));
        if (isset($logType) && $logType == "curl_log") {
            $log_filename = $log_filename . '/' . $controller_name;
        }
        if (!file_exists($log_filename)) {
            mkdir($log_filename, 0777, true);
        }
        $log_file_data = $log_filename . '/' . $controller_name . '_' . date('d-M-Y') . '.php';
        file_put_contents($log_file_data, $log_msg . "\n" . "\n", FILE_APPEND);
        return;
    }

    public function getToken($schoolId)
    {
        return "eyJzdiI6IjAwMDAwMSIsImFsZyI6IkhTNTEyIiwidiI6IjIuMCIsImtpZCI6IjZiYzZjZjVmLWFjZTMtNDVkNy1iYTU3LWNlNWFmOGZhYTAxMiJ9.eyJhdWQiOiJodHRwczovL29hdXRoLnpvb20udXMiLCJ1aWQiOiJLTXpCaHJrUVNrdXVOSE1rRUxNTEtBIiwidmVyIjo5LCJhdWlkIjoiMWZlMmNkMzRiNmRkZGJjNWNjZTcxODM2NzJjN2U1N2IiLCJuYmYiOjE3MTcxNDk1NjEsImNvZGUiOiJYU0Y1NzJYWlJWeWJsakJuc0RmRkh3RXYzMjVVa2VQa1AiLCJpc3MiOiJ6bTpjaWQ6TmxSQkVTWllSRWl3aE5YTXpUVFFRIiwiZ25vIjowLCJleHAiOjE3MTcxNTMxNjEsInR5cGUiOjMsImlhdCI6MTcxNzE0OTU2MSwiYWlkIjoicVRyczdKVkxSSzJja3Z6OWhRRGpPZyJ9.AdvoHW8NePUxRZhe95N8koV4y1jvKZVUNJ0FVp85PE3Vqqfu2tapyXF6PABdtfjoRMw-_K-y_bSUR0jah6rdSg";
        // $query = $this->db->query("SELECT id,access_token,expiry_date FROM zoom_token WHERE school_id = {$schoolId}")->result_array();
        // return $query;
    }
    public function zoomConfig($condition)
    {
        $query = $this->db->query("SELECT name,value FROM zoom_configuration $condition")->result_array();
        return $query;
    }
    public function getZoomKeys($condition)
    {
        $result = $this->db->query("SELECT name,value FROM admin_settings_school $condition")->result_array();
        return $result;
    }

    public function updateAnswers_post()
    {

        $get_answers = $this->db->query("SELECT * FROM answers")->result_array();
        foreach ($get_answers as $key => $value) {
            $answer = [];
            $answer[] = $value['answer'];
            $data['new_answer'] = json_encode($answer);
            $condition = array('answer_id' => $value['answer_id']);
            $this->common_model->update('answers', $data, $condition);
        }
        $this->jsonarr["IsSuccess"] = true;
        $this->jsonarr["ResponseObject"] = "Answer Updated Successfully";
        return $this->printjson($this->jsonarr);
    }



    private function printjson($jsonarr)
    {
        echo json_encode($jsonarr);
    }

}
