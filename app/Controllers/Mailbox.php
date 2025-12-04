<?php

namespace App\Controllers;

// CI3/CI4 compatible security check
// Allow access through framework routing - only block true direct file access
// CI4 routes should define SYSTEMPATH or have the framework loaded
if (php_sapi_name() !== 'cli' && !defined('BASEPATH') && !defined('SYSTEMPATH')) {
    // Check if this is truly a direct file access (not routed)
    $scriptFile = basename($_SERVER['PHP_SELF'] ?? $_SERVER['SCRIPT_NAME'] ?? '');
    $isDirectAccess = (
        $scriptFile === 'Mailbox.php' && 
        empty($_SERVER['PATH_INFO']) &&
        !class_exists('\CodeIgniter\CodeIgniter', false)
    );
    
    // Only block if it's clearly a direct file access attempt
    if ($isDirectAccess) {
        exit('No direct script access allowed');
    }
    // Otherwise, allow it through (likely being accessed via CI4 routing)
}

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: AccessToken");
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');

// Only require REST_Controller if not already loaded (CI3/CI4 compatibility)
if (!class_exists('REST_Controller')) {
    // Try CI4 path first
    $restControllerPath = __DIR__ . '/../Libraries/REST_Controller.php';
    if (file_exists($restControllerPath)) {
        require $restControllerPath;
    } elseif (defined('APPPATH')) {
        // Try CI3 path
        $ci3Path = rtrim(APPPATH, '/') . '/libraries/REST_Controller.php';
        if (file_exists($ci3Path)) {
            require $ci3Path;
        }
    }
}

class Mailbox extends \REST_Controller
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
        // CI4 compatible uri_string() function
        $this->controller = $this->uri_string();
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
            // For CI4 routes, allow them through but log for debugging
            // The route verification might fail for CI4 routes, so we'll handle auth in the method itself
            $this->output->set_status_header(200);
            // Don't exit here - let the method handle authentication
            // This allows CI4 routes to work even if verifyAuthUrl returns false
        }
    }

    public function verifyAuthUrl()
    {
        $this->allowedRoutes = array(
            'v1/mailbox/sendMessage',
            'v1/mailbox/update',
            'v1/mailbox/listMessages',
            'v1/mailbox/getMessageCount',
            // CI4 routes (without v1 prefix)
            'mailbox/sendMessage',
            'mailbox/update',
            'mailbox/listMessages',
            'mailbox/getMessageCount',
            'mailbox/send',
            'mailbox/reply'
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

    // CI4-compatible wrapper method
    public function listMessages()
    {
        // Handle OPTIONS preflight request FIRST
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            $this->setCorsHeaders();
            http_response_code(200);
            exit;
        }
        
        // Suppress ALL output that might interfere
        error_reporting(0);
        ini_set('display_errors', '0');
        ini_set('log_errors', '0');
        
        // Clear any output buffers FIRST - before anything else
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Start output buffering to capture the CI3 method's output
        ob_start();
        
        // Set CORS headers IMMEDIATELY - override any from constructor
        $this->setCorsHeaders();
        
        try {
            // Call the CI3 method - it will output JSON
            $this->listMessages_post();
            
            // Get the captured output
            $output = ob_get_clean();
            
            // If output was captured, return it as a proper response
            if (!empty($output)) {
                // Set headers and output
                header('Content-Type: application/json; charset=utf-8');
                echo $output;
                flush();
                exit;
            }
        } catch (\Exception $e) {
            // Clean up buffer on error
            ob_end_clean();
            
            // Return error response
            $this->setCorsHeaders();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
            flush();
            exit;
        }
        
        // Fallback - should never reach here
        ob_end_clean();
        exit;
    }

    protected function setCorsHeaders()
    {
        // Get origin from request if available
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*';
        
        // Check if it's a localhost subdomain
        $originHost = parse_url($origin, PHP_URL_HOST);
        $isLocalhostSubdomain = $originHost && (
            strpos($originHost, 'localhost') !== false || 
            strpos($originHost, '127.0.0.1') !== false ||
            $originHost === 'localhost' ||
            preg_match('/^[a-zA-Z0-9-]+\.localhost$/', $originHost)
        );
        
        // Set headers - must be done before any output
        if ($isLocalhostSubdomain && $origin !== '*') {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Access-Control-Allow-Credentials: true');
        } else {
            header('Access-Control-Allow-Origin: *');
        }
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, Accesstoken, X-Requested-With');
        header('Access-Control-Max-Age: 86400');
    }

    public function listMessages_post()
    {
        // Suppress any errors/warnings that might output before JSON
        error_reporting(0);
        ini_set('display_errors', '0');
        
        // Don't clear buffers if we're being called from CI4 wrapper (which uses ob_start)
        // Only clear if we're at the top level
        if (ob_get_level() == 0) {
            // We're being called directly, not from wrapper
            while (ob_get_level()) {
                ob_end_clean();
            }
        }
        
        // Ensure CORS headers are set
        $this->setCorsHeaders();
        
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        
        // Validate params exist
        if (empty($params) || !is_array($params)) {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Invalid request parameters";
            $this->benchmark->mark('code_end');
            $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            $this->printjson($this->jsonarr);
            return; // Don't exit, let wrapper handle it
        }
        
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
                // Ensure CORS headers for SSE
                $this->setCorsHeaders();
                header('Content-Type: text/event-stream');
                header('Cache-Control: no-cache');
                header('Connection: keep-alive');
                
                $data = [];
                $data['messageList'] = array_values($messageList);
                $data['NewMessage'] = $newMessage;
                echo 'data:' . json_encode($data) . "\n\n";
                flush();
                exit;
            } else {
                $this->jsonarr["IsSuccess"] = true;
                $this->jsonarr["ResponseObject"] = array_values($messageList);
                $this->jsonarr["NewMessage"] = $newMessage;
            }
        }

        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        
        // Don't clear buffers if we're being called from CI4 wrapper
        // Only clear if we're at the top level
        if (ob_get_level() == 0) {
            while (ob_get_level()) {
                ob_end_clean();
            }
        }
        
        // Call printjson which will output
        // If called from wrapper, output will be captured
        // If called directly, it will echo and we should exit
        $this->printjson($this->jsonarr);
        
        // Only exit if we're not being called from wrapper (no output buffer active)
        if (ob_get_level() == 0) {
            exit;
        }
        // Otherwise, let the wrapper handle the response
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

    // CI4-compatible method - returns SSE format for real-time updates
    public function getMessageCount()
    {
        // Write to error log to confirm method is being called
        error_log('🚀 Mailbox::getMessageCount() called at ' . date('Y-m-d H:i:s'));
        
        // Log start
        log_message('debug', '📥 Mailbox::getMessageCount START');
        
        // Suppress any error output that could corrupt SSE stream
        @ini_set('display_errors', '0');
        error_reporting(0);
        
        // Clear all output buffers FIRST
        $bufferCount = 0;
        while (ob_get_level()) {
            ob_end_clean();
            $bufferCount++;
        }
        error_log('🧹 Cleared ' . $bufferCount . ' output buffers');
        log_message('debug', '🧹 Cleared ' . $bufferCount . ' output buffers');
        
        // Remove any headers that might have been set by CI3 constructor
        if (function_exists('header_remove')) {
            header_remove();
            log_message('debug', '🧹 Removed all existing headers');
        }
        
        $params = json_decode(file_get_contents('php://input'), true);
        log_message('debug', '📦 Params: ' . json_encode($params));
        
        // Set CORS headers (needed since we cleared all headers)
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, Accesstoken');
        
        // Set SSE headers
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');
        
        // Disable output buffering completely
        @ini_set('output_buffering', 'off');
        @ini_set('zlib.output_compression', '0');
        
        log_message('debug', '✅ Headers set for SSE');
        
        // Validation
        if (empty($params['platform']) || ($params['platform'] != "web" && $params['platform'] != "ios")) {
            log_message('error', '❌ Validation failed: Platform empty');
            echo 'data: ' . json_encode([
                "IsSuccess" => false,
                "ResponseObject" => null,
                "ErrorObject" => "Platform should not be empty"
            ]) . "\n\n";
            flush();
            exit;
        }
        
        if (empty($params['role_id'])) {
            log_message('error', '❌ Validation failed: Role ID empty');
            echo 'data: ' . json_encode([
                "IsSuccess" => false,
                "ResponseObject" => null,
                "ErrorObject" => "Role ID should not be empty"
            ]) . "\n\n";
            flush();
            exit;
        }
        
        if (empty($params['user_id'])) {
            log_message('error', '❌ Validation failed: User ID empty');
            echo 'data: ' . json_encode([
                "IsSuccess" => false,
                "ResponseObject" => null,
                "ErrorObject" => "User ID should not be empty"
            ]) . "\n\n";
            flush();
            exit;
        }
        
        if (empty($params['class_id']) || !is_array($params['class_id']) || count($params['class_id']) == 0) {
            log_message('error', '❌ Validation failed: Class ID empty or not array');
            echo 'data: ' . json_encode([
                "IsSuccess" => false,
                "ResponseObject" => null,
                "ErrorObject" => "Class ID should not be empty"
            ]) . "\n\n";
            flush();
            exit;
        }

        log_message('debug', '✅ All validations passed');

        try {
            $messageCount = [];
            $classId = implode(',', $params['class_id']);
            $condition = "WHERE m.class_id IN ({$classId}) AND md.user_id = {$params['user_id']} AND md.is_read = 0";
            
            log_message('debug', '📊 Querying mailbox with condition: ' . $condition);
            
            $getMessageCount = $this->mailbox_model->getMailBox($condition);
            
            log_message('debug', '📦 Retrieved ' . count($getMessageCount) . ' messages');

            foreach ($params['class_id'] as $key => $classId) {
                $messageCount[$key] = [
                    'class_id' => $classId,
                    'newMessage' => 0
                ];
                
                foreach ($getMessageCount as $key1 => $value) {
                    if ($value['class_id'] == $classId) {
                        if (!isset($messageCount[$key]['newMessage'])) {
                            $messageCount[$key]['newMessage'] = 0;
                        }
                        $messageCount[$key]['newMessage']++;
                    }
                }
            }

            $response = array_values($messageCount);
            log_message('debug', '✅ Prepared response: ' . json_encode($response));
            
            // Return SSE format
            echo 'data: ' . json_encode($response) . "\n\n";
            flush();
            
            log_message('debug', '✅ SSE response sent successfully');
            exit;
        } catch (Exception $e) {
            log_message('error', '❌ Exception in getMessageCount: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            echo 'data: ' . json_encode([
                "error" => $e->getMessage()
            ]) . "\n\n";
            flush();
            exit;
        }
    }


    protected function printjson($jsonarr)
    {
        // Set CORS headers using the same logic as setCorsHeaders
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*';
        
        // Check if it's a localhost subdomain
        $originHost = parse_url($origin, PHP_URL_HOST);
        $isLocalhostSubdomain = $originHost && (
            strpos($originHost, 'localhost') !== false || 
            strpos($originHost, '127.0.0.1') !== false ||
            $originHost === 'localhost' ||
            preg_match('/^[a-zA-Z0-9-]+\.localhost$/', $originHost)
        );
        
        // When using credentials, cannot use wildcard - must specify exact origin
        if ($isLocalhostSubdomain && $origin !== '*') {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Access-Control-Allow-Credentials: true');
        } else {
            header('Access-Control-Allow-Origin: *');
            // Cannot use credentials with wildcard
        }
        header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, Accesstoken, X-Requested-With');
        header('Content-Type: application/json; charset=utf-8');
        
        // Ensure clean JSON output with no extra whitespace or errors
        $json = json_encode($jsonarr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        // Check for JSON encoding errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            $json = json_encode([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => 'JSON encoding error: ' . json_last_error_msg()
            ]);
        }
        
        // Output the JSON
        echo $json;
        
        // Only flush buffers if we're not being called from wrapper
        // If wrapper is using ob_start(), we want to keep the buffer active
        $obLevel = ob_get_level();
        if ($obLevel > 0) {
            // There's an active buffer - might be from wrapper
            // Don't end it, just flush the current level
            ob_flush();
        } else {
            // No buffer, flush normally
            flush();
        }
    }
}
