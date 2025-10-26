<?php
defined('BASEPATH') or exit('No direct script access allowed');
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: AccessToken");
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');

require APPPATH . '/libraries/REST_Controller.php';

class Mailbox extends REST_Controller
{
    protected $jsonarr = array();
    protected $headers;
    protected $urlAuth;
    protected $controller;

    function __construct()
    {
        parent::__construct();
        $this->load->model("mailbox_model");
        $this->load->model("common_model");

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
            'v1/mailbox/sendMessage',
            'v1/mailbox/update',
            'v1/mailbox/listMessages',
            'v1/mailbox/getMessageCount'
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
        $this->excludeRoutes = array();
        foreach ($this->excludeRoutes as $routeString) {
            if ($this->controller == $routeString) {
                return true;
            }
        }
    }
    public function sendMessage_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $headers = $this->input->request_headers();
        $this->common_model->checkPermission($this->controller,$params,$headers);
        if ($params['platform'] != "web" && $params['platform'] != "ios") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Role_Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } elseif (count($params['to_id']) == 0) {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Receiver should not be empty";
        } else {
            $messageData = array(
                'parent_message_id' => isset($params['message_id']) && $params['message_id'] != '' ? $params['message_id'] : NULL,
                'class_id' => $params['class_id'],
                'from_id' => $params['user_id'],
                'to_id' => is_array($params['to_id']) ? implode(',', $params['to_id']) : $params['to_id'],
                'body' => $params['body'],
                'status' => 0,
                'created_by' => $params['user_id'],
                'created_date' => date('Y-m-d H:i:s')
            );
            $messageId = $this->common_model->insert('mailbox', $messageData);
            if ($messageId > 0) {
                foreach ($params['to_id'] as $key => $value) {
                    $messageDetails[] = array(
                        'message_id' => $messageId,
                        'user_id' => $value,
                        'created_by' => $params['user_id'],
                        'created_date' => date('Y-m-d H:i:s')
                    );
                }
                $messageDetail = $this->common_model->bulkInsert('mailbox_details', $messageDetails);
                if ($messageDetail > 0) {
                    if (isset($params['attachment']) && $params['attachment'] > 0) {
                        foreach ($params['attachment'] as $attachmentKey => $attachmentValue) {
                            $attachment = array(
                                'message_id' => $messageId,
                                'attachment' => json_encode($attachmentValue),
                                'type' => 0,
                                'created_by' => $params['user_id'],
                                'created_date' => date('Y-m-d H:i:s')
                            );
                            $this->common_model->insert('mailbox_attachment', $attachment);
                        }
                    }
                    $this->jsonarr["IsSuccess"] = true;
                    $this->jsonarr["ResponseObject"] = "Message Sent successfully";
                } else {
                    $this->jsonarr["IsSuccess"] = false;
                    $this->jsonarr["ErrorObject"] = "Failed to Send Message";
                }
            }
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }

    public function update_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $headers = $this->input->request_headers();
        $this->common_model->checkPermission($this->controller,$params,$headers);
        if ($params['platform'] != "web" && $params['platform'] != "ios") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Platform should not be empty";
        } elseif ($params['class_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Class Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } else {
            $updateMessageStatus = [];
            $condition = "WHERE m.class_id = {$params['class_id']} AND md.user_id = {$params['user_id']}";
            $getMessage = $this->mailbox_model->getMailBox($condition);
            foreach ($getMessage as $key => $value) {
                $condition = array('message_detail_id' => $value['message_detail_id']);
                if (isset($params['is_read']) && $params['is_read'] != '') {
                    $updateMessageStatus['is_read'] = $params['is_read'];
                }
                $this->common_model->update('mailbox_details', $updateMessageStatus, $condition);
            }
            if (count($updateMessageStatus) > 0) {
                $this->common_model->update('mailbox_details', $updateMessageStatus, $condition);
                $this->jsonarr["IsSuccess"] = true;
                $this->jsonarr["ResponseObject"] = "Message Updated Successfully";
            } else {
                $this->jsonarr["IsSuccess"] = false;
                $this->jsonarr["ErrorObject"] = "Failed To Update";
            }
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }

    public function listMessages_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $headers = $this->input->request_headers();
        $this->common_model->checkPermission($this->controller,$params,$headers);
        if ($params['platform'] != "web" && $params['platform'] != "ios") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Role_Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } else {
            $condition = "WHERE (m.from_id = {$params['user_id']} OR md.user_id = {$params['user_id']}) AND m.class_id = {$params['class_id']}";
            $getMessage = $this->mailbox_model->getMessages($condition);

            $messageList = [];
            $newMessage = 0;
            foreach ($getMessage as $key => $value) {
                $messageId = $value['message_id'];
                if (!isset($messageList[$messageId])) {
                    if ($value['user_id'] == $params['user_id'] && $value['is_read'] == 0) {
                        $newMessage++;
                    }
                    $messageList[$messageId] = [
                        'message_id' => $value['message_id'],
                        'class_id' => $value['class_id'],
                        'from_id' => $value['from_id'],
                        'from_name' => $value['from_name'],
                        'profile_url' => $value['profile_url'],
                        'to_id' => explode(',', $value['to_id']),
                        'body' => $value['body'],
                        'status' => $value['status'],
                        'created_date' => $value['created_date'],
                        'attachments' => [],
                        'details' => [],
                        'parent_message_id' => $value['parent_message_id']
                    ];
                }

                if ($value['attachment_id'] && $value['attachment']) {
                    $attachment = [
                        'attachment_id' => $value['attachment_id'],
                        'attachment' => json_decode($value['attachment'], true),
                        'type' => $value['type']
                    ];

                    if (!in_array($attachment, $messageList[$messageId]['attachments'])) {
                        $messageList[$messageId]['attachments'][] = $attachment;
                    }
                }

                $receptients = [
                    'message_detail_id' => $value['message_detail_id'],
                    'user_id' => $value['user_id'],
                    'is_read' => $value['is_read']
                ];

                if (!in_array($receptients, $messageList[$messageId]['details'])) {
                    $messageList[$messageId]['details'][] = $receptients;
                }
            }
            if (isset($params['response_type']) && $params['response_type'] == 'SSE') {
                $data = [];
                $data['messageList'] = array_values($messageList);
                $data['NewMessage'] = $newMessage;
                echo 'data:' . json_encode($data);
                exit;
            } else {
                $this->jsonarr["IsSuccess"] = true;
                $this->jsonarr["ResponseObject"] = array_values($messageList);
                $this->jsonarr["NewMessage"] = $newMessage;
            }
        }

        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }
    public function getMessageCount_post()
    {
        $params = json_decode(file_get_contents('php://input'), true);
        if ($params['platform'] != "web" && $params['platform'] != "ios") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Platform Should not be Empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Role Id Should not be Empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id Should not be Empty";
        } elseif (count($params['class_id']) == 0) {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Class id Should not be Empty";
        } elseif (count($this->jsonarr) == 0) {
            $messageCount = [];
            $classId = implode(',', $params['class_id']);
            $condition = "WHERE m.class_id IN ({$classId}) AND md.user_id = {$params['user_id']} AND md.is_read = 0";
            $getMessageCount = $this->mailbox_model->getMailBox($condition);

            foreach ($params['class_id'] as $key => $classId) {
                foreach ($getMessageCount as $key1 => $value) {
                    if ($value['class_id'] == $classId) {
                        $messageCount[$key]['class_id'] = $classId;
                        $messageCount[$key]['newMessage'][] = $value;
                    }
                }
                $messageCount[$key]['class_id'] = isset($messageCount[$key]['class_id']) ? $messageCount[$key]['class_id'] : $classId;
                $messageCount[$key]['newMessage'] = isset($messageCount[$key]['newMessage']) ? count($messageCount[$key]['newMessage']) : 0;
            }
            echo 'data:' . json_encode($messageCount);
        }
    }


    private function printjson($jsonarr)
    {
        echo json_encode($jsonarr);
    }
}
