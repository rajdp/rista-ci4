<?php
defined('BASEPATH') or exit('No direct script access allowed');
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: AccessToken");
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');

require APPPATH . '/libraries/REST_Controller.php';

class Staticsite extends REST_Controller
{
    protected $jsonarr = array();
    protected $headers;
    protected $urlAuth;
    protected $controller;

    function __construct()
    {
        parent::__construct();
        $this->load->model("batch_model");
        $this->load->model("common_model");
        $this->load->model("staticsite_model");

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
            'v1/staticsite/sendEmail',
            'v1/staticsite/contactUs',
            'v1/staticsite/subscription'

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
            'v1/staticsite/sendEmail',
            'v1/staticsite/contactUs',
            'v1/staticsite/subscription'

        );
        foreach ($this->excludeRoutes as $routeString) {
            if ($this->controller == $routeString) {
                return true;
            }
        }
    }

    public function sendEmail_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $this->common_model->createLog($params, 'v1/staticsite/sendEmail', 'only request', 'sendEmail');
        $data = array(
            'name' => $params['name'],
            'email_id' => $params['email_id'],
            'mobile' => $params['mobile'],
            'school_name' => $params['school_name'],
            'state' => $params['state'],
            'city' => $params['city'],
            'requirement_message' => $params['requirement_message'],
            'type' => $params['type']
        );
        $insertdata = $this->common_model->insert('static_website', $data);
        if ($insertdata > 1) {
            $getMail = $this->staticsite_model->getReceiver(1, $params);
            $emailid = $getMail['value'];
            $msg = $this->load->view('email_send', '', true);
            $messageTemplates = $this->common_model->smsEmailTemplate('request_demo', 'email', 'user');
            $msg = str_replace('%NAME%', $params['name'], $msg);
            $msg = str_replace('%SCHOOLNAME%', $params['school_name'], $msg);
            $msg = str_replace('%EMAIL%', $params['email_id'], $msg);
            $msg = str_replace('%NUMBER%', $params['mobile'], $msg);
            $msg = str_replace('%STATE%', $params['state'], $msg);
            $msg = str_replace('%CITY%', $params['city'], $msg);
            $msg = str_replace('%MESSAGE%', $params['requirement_message'], $msg);
            $sendMail = $this->common_model->sendEmail($messageTemplates['subject'], $emailid, $msg, '', '');
            if ($sendMail) {
                $updatedata = array(
                    'status' => 1,
                );
            } else {
                $updatedata = array(
                    'status' => 2,
                );
            }
            $condition = array('id' => $insertdata);
            $this->common_model->update("static_website", $updatedata, $condition);
            if ($updatedata['status'] == 1) {
                $this->jsonarr['IsSuccess'] = true;
                $this->jsonarr['ResponseObject'] = "Mail Sent";
            } elseif ($updatedata['status'] == 2) {
                $this->jsonarr['IsSuccess'] = true;
                $this->jsonarr['ResponseObject'] = "Unable to Send Mail";
            }
        }
        $this->common_model->createLog($params, 'v1/staticsite/sendEmail', $this->jsonarr, 'sendEmail');
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }

    public function contactUs_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $this->common_model->createLog($params, 'v1/staticsite/contactUs', 'only request', 'contactUs');
        $data = array(
            'name' => $params['name'],
            'email_id' => $params['email_id'],
            'phone' => $params['phone'],
            'message' => $params['message'],
            'type' => $params['type'],
            'sub_type' => isset($params['sub_type']) && $params['sub_type'] != '' ? $params['sub_type'] : ''
        );
        $insertdata = $this->common_model->insert('website_contact_us', $data);
        $getMail = $this->staticsite_model->getReceiver(2, $params);
        $emailid = $getMail['value'];
        $subject = "Message From User";
        if (isset($params['sub_type']) && $params['sub_type'] != '') {
            if ($params['sub_type'] == '1') {
                $sub_type = 'Home';
            } elseif ($params['sub_type'] == '2') {
                $sub_type = 'K-6 Math';
            } elseif ($params['sub_type'] == '3') {
                $sub_type = 'Pre-Algebra';
            } elseif ($params['sub_type'] == '4') {
                $sub_type = 'Algebra 1';
            } elseif ($params['sub_type'] == '5') {
                $sub_type = 'Geometry';
            } elseif ($params['sub_type'] == '6') {
                $sub_type = 'Algebra 2';
            } elseif ($params['sub_type'] == '7') {
                $sub_type = 'Pre-Calculus';
            } elseif ($params['sub_type'] == '8') {
                $sub_type = 'English';
            } elseif ($params['sub_type'] == '9') {
                $sub_type = 'Reading-And-Writing';
            } elseif ($params['sub_type'] == '10') {
                $sub_type = 'SAT-Prep';
            } elseif ($params['sub_type'] == '11') {
                $sub_type = 'PSAT8-Prep';
            } elseif ($params['sub_type'] == '12') {
                $sub_type = 'High-School-Prep';
            } elseif ($params['sub_type'] == '13') {
                $sub_type = 'Physics-Honors';
            } elseif ($params['sub_type'] == '14') {
                $sub_type = 'Chemistry-Honors';
            } elseif ($params['sub_type'] == '15') {
                $sub_type = 'Biology-Honors';
            } elseif ($params['sub_type'] == '16') {
                $sub_type = 'AP-Biology';
            } elseif ($params['sub_type'] == '17') {
                $sub_type = 'AP-Chemistry';
            } elseif ($params['sub_type'] == '18') {
                $sub_type = 'AP-Physics';
            } elseif ($params['sub_type'] == '19') {
                $sub_type = 'AP-Calculus AB-BC';
            } elseif ($params['sub_type'] == '20') {
                $sub_type = 'AP-Statistics';
            }
            $subject = "Message From User from {$sub_type} page";
        }
        $emailMsg = "Dear Admin, <br><br>A user have sent a message. <br><br> Name: %NAME% <br> Email Id: %EMAIL% <br> Contact No.: %NUMBER% <br> Message: %MESSAGE% <br><br> Thanks <br> Team EdQuill International";
        $emailMsg = str_replace('%NAME%', $params['name'], $emailMsg);
        $emailMsg = str_replace('%EMAIL%', $params['email_id'], $emailMsg);
        $emailMsg = str_replace('%NUMBER%', $params['phone'], $emailMsg);
        $emailMsg = str_replace('%MESSAGE%', $params['message'], $emailMsg);
        $sendMail = $this->common_model->sendEmail($subject, $emailid, $emailMsg, '', '');
        if ($sendMail) {
            $update = array(
                'status' => 1,
            );
        } else {
            $update = array(
                'status' => 2,
            );
        }
        $condition = array('id' => $insertdata);
        $this->common_model->update('website_contact_us', $update, $condition);
        if ($update['status'] == 1) {
            $this->jsonarr['IsSuccess'] = true;
            $this->jsonarr['ResponseObject'] = "Mail Sent";
        } elseif ($update['status'] == 2) {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ResponseObject'] = "Unable to Send Mail";
        }
        $this->common_model->createLog($params, 'v1/staticsite/contactUs', $this->jsonarr, 'contactUs');
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }

    public function subscription_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $this->common_model->createLog($params, 'v1/staticsite/subscription', 'only request', 'subscription');
        $checkSubscription = $this->staticsite_model->checkSubscription($params);
        if (count($checkSubscription) == 0) {
            $data = array(
                'email_id' => $params['email_id'],
                'status' => 1,
                'type' => $params['type']
            );
            $insert = $this->common_model->insert('static_website_email_subscription', $data);
            $getMail = $this->staticsite_model->getReceiver(2, $params);
            $emailid = $getMail['value'];
            //sending mail to admin
            $messageTemplate = $this->common_model->smsEmailTemplate('static_site_subscription_admin', 'email');
            $subject = $messageTemplate['subject'];
            $emailMsg = $messageTemplate['template'];
            $emailMsg = str_replace('%EMAIL%', $params['email_id'], $emailMsg);
            $sendMailAdmin = $this->common_model->sendEmail($subject, $emailid, $emailMsg, '', '');
            //sending mail to user
            $messageTemplate = $this->common_model->smsEmailTemplate('static_site_subscription_user', 'email');
            $subject = $messageTemplate['subject'];
            $emailMsg = $messageTemplate['template'];
            $emailMsg = str_replace('%WEBSITE%', $getMail['webname'], $emailMsg);
            $sendMailUser = $this->common_model->sendEmail($subject, $params['email_id'], $emailMsg, '', '');
            if ($sendMailAdmin && $sendMailUser) {
                $update = array(
                    'mail' => 1,
                );
            } else {
                $update = array(
                    'mail' => 2,
                );
            }
            $condition = array('id' => $insert);
            $this->common_model->update('static_website_email_subscription', $update, $condition);
            $this->jsonarr['IsSuccess'] = true;
            $this->jsonarr['ResponseObject'] = "Subscribed";
        } else {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ResponseObject'] = "Already Subscribed";
        }
        $this->common_model->createLog($params, 'v1/staticsite/subscription', $this->jsonarr, 'subscription');
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }

    private function printjson($jsonarr)
    {
        echo json_encode($jsonarr);
    }
}
