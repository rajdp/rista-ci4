<?php
defined('BASEPATH') OR exit('No direct script access allowed');
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: AccessToken");
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');

require APPPATH . '/libraries/REST_Controller.php';

class Corporate extends REST_Controller
{
    protected $jsonarr = array();
    protected $headers;
    protected $urlAuth;
    protected $controller;
    private $settings = array();


    function __construct()
    {
        parent::__construct();
        $this->load->model("batch_model");
        $this->load->model("common_model");
        $this->load->model("corporate_model");
        $adminSettings = $this->adminSettings('');
        foreach ($adminSettings as $key => $details) {
            $this->settings[$details['setting_name']] = $details['setting_value'];
        }

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

    public function adminSettings($name) {
        $adminSettings = $this->db->query("SELECT id as setting_id, value as setting_value, name as setting_name   FROM  admin_settings WHERE 
                             status = 1")->result_array();
        if($name != '') {
            $data = [];
            for($i =0;$i < count($adminSettings);$i++) {
                if($name = $adminSettings[$i]['setting_name']) {
                    array_push($data,$adminSettings[$i]);
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
            'v1/corporate/list',
            'v1/corporate/add',
            'v1/corporate/edit',
            'v1/corporate/delete',
            'v1/corporate/login',
            'v1/corporate/request',
            'v1/corporate/response',
            'v1/corporate/requestList'
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
            'v1/corporate/login'
        );
        foreach ($this->excludeRoutes as $routeString) {
            if ($this->controller == $routeString) {
                return true;
            }
        }
    }

    private function printjson($jsonarr)
    {
        echo json_encode($jsonarr);
    }

    public function login_post(){
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'),true);
        if($params['platform'] == ""){
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Platform should not be empty";
        } elseif($params['role_id'] == ""){
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Role id should not be empty";
        } elseif($params['username'] == ""){
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Username should not be empty";
        } elseif($params['password'] == ""){
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Password should not be empty";
        } else{
            $this->common_model->createLog($params,'v1/corporate/login','only request','login');
            $salt = $this->config->item('salt');
            $params['password'] = $salt . $params['password'] . $salt;
            $checkPassword = $this->corporate_model->checkPassword($params);
            $user = $this->corporate_model->login($params);
            if( count($user) > 0 && $user[0]['status'] == 1) {
                if ($checkPassword['mobile'] == $user[0]['mobile']) {
                    $this->jsonarr['IsSuccess'] = true;
                    $this->jsonarr['ResponseObject']['message'] = "Login Successfully";
                    $this->jsonarr['ResponseObject']['role_id'] = $user[0]["role_id"];
                    $this->jsonarr['ResponseObject']['user_id'] = $user[0]["user_id"];
                    $this->jsonarr['ResponseObject']['email_id'] = $user[0]["email_id"];
                    $this->jsonarr['ResponseObject']['mobile'] = $user[0]["mobile"];
                    $this->jsonarr['ResponseObject']['first_name'] = $user[0]["first_name"];
                    $this->jsonarr['ResponseObject']['last_name'] = $user [0] ["last_name"];
                    $this->jsonarr['ResponseObject']['profile_url'] = $user [0] ["profile_url"];
                    $this->jsonarr['ResponseObject']['profile_thumb_url'] = $user [0] ["profile_thumb_url"];
                    $this->jsonarr['ResponseObject']['corporate_id'] = $user [0] ["corporate_id"];
                    $this->jsonarr['ResponseObject']['corporate_name'] = $user [0] ["corporate_name"];
                    $this->jsonarr['ResponseObject']['corporate_code'] = $user [0] ["corporate_code"];
                    if ($this->jsonarr["ResponseObject"]['user_id'] != null) {
                        //$tokenString = $this->jsonarr["ResponseObject"]['user_id'] . '|corporate';
                        //$this->jsonarr['ResponseObject']['Accesstoken'] = AUTHORIZATION::generateToken($tokenString);
                        $generateToken =  AUTHORIZATION::generateToken((object)array('user' => $this->jsonarr["ResponseObject"]['user_id'].'|corporate','timestamp' => time()));
                        $this->jsonarr['ResponseObject']['Accesstoken'] = $generateToken;
                        $tokenInsert = array(
                            'user_id' => $user[0]['user_id'],
                            'access_token' => $generateToken,
                            'status' => 1,
                            'ip_address' => $_SERVER['REMOTE_ADDR'],
                            'created_date' => date('Y-m-d H:i:s')
                        );
                        $this->common_model->insert('user_token',$tokenInsert);
                        $userPermission = $this->settings['no_of_users_for_corporate'];
                        $getToken = $this->corporate_model->getUserToken($user[0]['user_id']);
                        if($userPermission != 0) {
                            if ($userPermission < count($getToken)) {
                                foreach ($getToken as $key => $value) {
                                    if ($key > $userPermission - 1) {
                                        $updateData = array('status' => 0);
                                        $condition = array('id' => $value['id']);
                                        $this->common_model->update('user_token', $updateData, $condition);
                                    }
                                }
                            }
                        }
                    } else {
                        $this->jsonarr['IsSuccess'] = "false";
                        $this->jsonarr['ErrorObject'] = "Unauthorised User";
                    }
                } else {
                    $this->jsonarr['IsSuccess'] = false;
                    $this->jsonarr['ErrorObject'] = "Invalid login Details";
                }
            } else {
                        $this->jsonarr['IsSuccess'] = false;
                        $this->jsonarr['ErrorObject'] = "Invalid login Details";
                }
            }
        $this->common_model->createLog($params,'v1/corporate/login',$this->jsonarr,'login');
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }

    public function list_post(){
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $headers = $this->input->request_headers();
        $this->common_model->checkPermission($this->controller,$params,$headers);
          if($params['platform']!="web"){
              $this->jsonarr['IsSuccess']= false;
              $this->jsonarr['ErrorObject']= "platform should not be empty";
          } elseif($params['role_id']==""){
              $this->jsonarr['IsSuccess']=false;
              $this->jsonarr['ErrorObject']="role id should not be empty";
          } elseif ($params['user_id']==""){
              $this->jsonarr['IsSuccess']=false;
              $this->jsonarr['ErrorObject']="user id should not be empty";
          } else {
              $this->common_model->createLog($params,'v1/corporate/list','only request','list');
              $corporateList = $this->corporate_model->corporateList($params);
              $this->jsonarr['IsSuccess'] = true;
              $this->jsonarr["ResponseObject"] = $corporateList;
          }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->common_model->createLog($params,'v1/corporate/list',$this->jsonarr,'list');
        return $this->printjson($this->jsonarr);
    }

    public function request_post() {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $headers = $this->input->request_headers();
        $this->common_model->checkPermission($this->controller,$params,$headers);
        if ($params['platform'] != "web") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "role id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "user id should not be empty";
        } elseif ($params['school_id'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "School id should not be empty";
        } elseif ($params['corporate_code'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "corporate code should not be empty";
        } else {
            $this->common_model->createLog($params,'v1/corporate/request','only request','request');
            $getCode = $this->corporate_model->getCorporateId($params);
            if ($getCode) {
                $params['corporate_id'] = implode(',', $getCode);
                $checkRequestExists = $this->corporate_model->checkRequestExists($params);
                if (count($checkRequestExists) == 0) {
                    $data = array(
                        'corporate_id' => $params['corporate_id'],
                        'school_id' => $params['school_id'],
                        'status' => 2,
                        'created_by' => $params['user_id'],
                        'created_date' => date('Y-m-d H:i:s')
                    );
                    $requestId = $this->common_model->insert('corporate_request', $data);
                    if ($requestId > 0) {
                        $messageTemplates = $this->common_model->smsEmailTemplate('corporate_request', 'email');
                        $emailMsg = $messageTemplates['template'];
                        $getEmailId = $this->corporate_model->getCorporateMail($params['corporate_id']);
                        $schoolName = $this->common_model->schoolName($params['school_id']);
                        $emailMsg = str_replace('%SCHOOL%', $schoolName['name'], $emailMsg);
                        $this->common_model->sendEmail($messageTemplates['subject'], $getEmailId['email_id'], $emailMsg, '', '');
                        $this->jsonarr['IsSuccess'] = true;
                        $this->jsonarr['ResponseObject'] = "Request Sent";
                    } else {
                        $this->jsonarr['IsSuccess'] = false;
                        $this->jsonarr['ErrorObject'] = "Unable to Send Request";
                    }
                } else {
                    $data = array(
                        'status' => 2,
                        'modified_by' => $params['user_id']
                    );
                    $condition = array('request_id' => $checkRequestExists[0]['request_id']);
                    $updateRequest = $this->common_model->update('corporate_request', $data, $condition);
                    if ($updateRequest) {
                        $messageTemplates = $this->common_model->smsEmailTemplate('corporate_request', 'email');
                        $emailMsg = $messageTemplates['template'];
                        $getEmailId = $this->corporate_model->getCorporateMail($params['corporate_id']);
                        $schoolName = $this->common_model->schoolName($params['school_id']);
                        $emailMsg = str_replace('%SCHOOL%', $schoolName['name'], $emailMsg);
                        $this->common_model->sendEmail($messageTemplates['subject'], $getEmailId['email_id'], $emailMsg, '', '');
                        $this->jsonarr['IsSuccess'] = true;
                        $this->jsonarr['ResponseObject'] = "Request Sent";
                    } else {
                        $this->jsonarr['IsSuccess'] = false;
                        $this->jsonarr['ErrorObject'] = "Unable to Send Request";
                    }
                }
            } else {
                $this->jsonarr['IsSuccess'] = false;
                $this->jsonarr['ErrorObject'] = "Invalid Code";
            }
        }
        $this->common_model->createLog($params,'v1/corporate/request',$this->jsonarr,'request');
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }

    public function requestList_post() {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $headers = $this->input->request_headers();
        $this->common_model->checkPermission($this->controller,$params,$headers);
        if ($params['platform'] != "web") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "role id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "user id should not be empty";
        } else {
            $this->common_model->createLog($params,'v1/corporate/requestList','only request','requestList');
            $requestList = $this->corporate_model->requestList($params);
            if (count($requestList) > 0) {
                $this->jsonarr['IsSuccess'] = true;
                $this->jsonarr['ResponseObject']['data'] = $requestList;
                $this->jsonarr['ResponseObject']['count'] = count($requestList);
            } else {
                $this->jsonarr['IsSuccess'] = false;
                $this->jsonarr['ResponseObject'] = "No data found";
            }
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->common_model->createLog($params,'v1/corporate/requestList',$this->jsonarr,'requestList');
        return $this->printjson($this->jsonarr);
    }

    public function response_post() {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $headers = $this->input->request_headers();
        $this->common_model->checkPermission($this->controller,$params,$headers);
        if ($params['platform'] != "web") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "role id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "user id should not be empty";
        } elseif ($params['school_id'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "School id should not be empty";
        } elseif ($params['corporate_id'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "corporate code should not be empty";
        } elseif ($params['status'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "corporate code should not be empty";
        } else {
            $this->common_model->createLog($params,'v1/corporate/response','only request','response');
            $data = array('status' => $params['status'],'validity' => $params['validity']);
            $condition = array('school_id' => $params['school_id'],'corporate_id' => $params['corporate_id']);
            $update = $this->common_model->update('corporate_request',$data,$condition);
            if ($update) {
                $this->jsonarr['IsSuccess'] = true;
                $this->jsonarr['ResponseObject'] = "Request Updated";
            } else {
                $this->jsonarr['IsSuccess'] = false;
                $this->jsonarr['ErrorObject'] = "Unable to Update Request";
            }
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->common_model->createLog($params,'v1/corporate/response',$this->jsonarr,'response');
        return $this->printjson($this->jsonarr);
    }

}
