<?php
namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use Config\Services;

class Api extends ResourceController
{
    use ResponseTrait;

    protected $jsonarr = [];
    protected $headers;
    protected $urlAuth;
    protected $controller;
    protected $commonModel;

    public function __construct()
    {
        helper(['url', 'form']);
        $this->commonModel = new \App\Models\V1\CommonModel();
        $this->controller = uri_string();
        
        // CORS is handled globally by CorsFilter - removed from here to avoid duplicate headers
        // header('Access-Control-Allow-Origin: *');
        // header("Access-Control-Allow-Headers: AccessToken");
        // header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
        
        $urlAuth = $this->verifyAuthUrl();
        $headers = $this->request->getHeaders();
        
        if ($urlAuth) {
            $excludeurl = $this->excludefunction();
            if ($excludeurl != 'true') {
                if (isset($headers['Accesstoken'])) {
                    $this->response->setStatusCode(200);
                } else {
                    $this->jsonarr['ErrorObject'] = "Unauthorized User";
                    $this->jsonarr['IsSuccess'] = false;
                    return $this->respond($this->jsonarr, 401);
                }
            } else {
                $this->response->setStatusCode(200);
                return true;
            }
        } else {
            $this->response->setStatusCode(200);
            $this->jsonarr['ErrorObject'] = "The requested url is not found.";
            $this->jsonarr['IsSuccess'] = false;
            return $this->respond($this->jsonarr, 404);
        }
    }

    public function verifyAuthUrl()
    {
        $this->allowedRoutes = array(
            'v1/api/zoomTokenGeneration',
            'v1/api/ZoomMeetingCreate'
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
            'v1/api/zoomTokenGeneration',
            'v1/api/ZoomMeetingCreate'
        );
        foreach ($this->excludeRoutes as $routeString) {
            if ($this->controller == $routeString) {
                return true;
            }
        }
    }

    public function ZoomMeetingCreate_post()
    {
        $this->benchmark->mark('code_start');

        $params = json_decode(file_get_contents('php://input'), true);
        $zoomKey = [
            [
                'name' => 'zoom_apikey',
                'value' => $params['zoom_apikey']
            ],
            [
                'name' => 'zoom_secretkey',
                'value' => $params['zoom_secretkey']
            ],
            [
                'name' => 'zoom_user_email',
                'value' => $params['zoom_user_email']
            ],
            [
                'name' => 'timezone',
                'value' => $params['timezone']
            ]
        ];
        $this->load->library('ZoomMeeting', $zoomKey);

        $data = [
            'agenda' => $params['agenda'],
            'default_password' => false,
            "pre_schedule" => false,
            "password" => '',
            "duration" => $params['duration'],
            "schedule_for" => $params['zoom_user_email'],
            'settings' => [
                'auto_recording' => "cloud",
                'host_video' => false,
                'participant_video' => false,
                'password' => '',
                'join_before_host' => false,
                'audio' => true,
                'approval_type' => "2",
            ],
            "start_time" => $params['start_time'],
            'topic' => $params['topic'],
            "type" => "2"
        ];
        $this->meeting = new ZoomMeeting($zoomKey);
        $url = "https://api.zoom.us/v2/users/{$params['zoom_user_email']}/meetings";
        $meeting =  $this->curlCall($url, $params, 'POST', 'create_meeting', $data);
        $this->jsonarr["IsSuccess"] = false;
        if ($meeting) {
            $this->jsonarr['ResponseObject'] = $meeting;
            $this->jsonarr["IsSuccess"] = true;
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->respond($this->jsonarr, 200);
    }

    public function zoomTokenGeneration_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $headers = $this->request->getHeaders();
        $zoom_account_id = $params['zoom_account_id'];
        $zoom_secretkey = $params['zoom_secretkey'];
        $zoom_apikey = $params['zoom_apikey'];
        $url = "https://zoom.us/oauth/token?grant_type=account_credentials&account_id=$zoom_account_id";
        $response = $this->curlCall($url, $params, 'POST', 'token_generation',[]);
        $this->jsonarr['ResponseObject'] = $response;
        $this->jsonarr["IsSuccess"] = true;
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->respond($this->jsonarr, 200);
    }

    public function curlCall($url, $params, $method, $type, $data)
    {
        $url = trim($url);
        if ($type == 'token_generation') {
            $token = base64_encode($params['zoom_secretkey'] . ':' . $params['zoom_apikey']);
            $headers = array(
                "Accept: application/json",
                "Content-type: application/x-www-form-urlencoded",
                "Authorization: Basic $token",
            );
        }
        else {
            $token = $params['token'];
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
            curl_setopt($crl, CURLOPT_POSTFIELDS, json_encode($data));
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

        $this->createLog($headers, $url, $response, "$type", "curl_log");
        $httpCode = curl_getinfo($crl, CURLINFO_HTTP_CODE);
        $responseArray = [];
        if ($httpCode == 200 || $httpCode == 400 || $httpCode == 201) {
            $responseArray = json_decode($response, true);
        }
        if ($httpCode != 200) {
            $httpResponse = "Return code is {$httpCode} \n" . curl_error($crl);
        }
        return $responseArray;
    }

    public function createLog($data, $url, $responseArray, $usage, $logType)
    {
        $controller_name =  "Zoom";
        $log_filename = "./application/logs/v1";
        $log_msg = [];
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

    public function index()
    {
        return $this->response->setJSON(['message' => 'API working']);
        return $this->respond([
            'status' => true,
            'message' => 'API is running',
            'version' => '1.0.0',
            'endpoints' => [
                'user' => [
                    'login' => 'POST /api/user/login',
                    'register' => 'POST /api/user/register',
                    'profile' => 'GET /api/user/profile'
                ],
                'category' => [
                    'list' => 'GET /api/category',
                    'create' => 'POST /api/category',
                    'update' => 'PUT /api/category/{id}',
                    'delete' => 'DELETE /api/category/{id}'
                ]
            ]
        ]);
    }

    public function zoomTokenGeneration()
    {
        // Implementation for Zoom token generation
    }

    public function ZoomMeetingCreate()
    {
        // Implementation for Zoom meeting creation
    }
}
