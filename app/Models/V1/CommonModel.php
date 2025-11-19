<?php

namespace App\Models\V1;

use CodeIgniter\Model;
use CodeIgniter\API\ResponseTrait;
use Config\Services;
use App\Libraries\Authorization;

class CommonModel extends BaseModel
{
    use ResponseTrait;

    protected $table = 'admin_settings';
    protected $allowedFields = [
        'id',
        'name',
        'value',
        'status'
    ];

    private $jsonarr = [];
    private $settings = [];

    public function __construct()
    {
        parent::__construct();
        helper(['authorization', 'jwt']);
        
        // Get request parameters
        $request = Services::request();

        $getParams = [];
        $rawBody = $request->getBody();

        if ($rawBody !== null) {
            $rawBody = trim($rawBody);
        }

        if (!empty($rawBody)) {
            $decoded = json_decode($rawBody, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $getParams = $decoded;
            }
        }
        
        // Set timezone based on school
        if (isset($getParams['school_id']) && $getParams['school_id'] != 0) {
            $getSchoolTimeZone = $this->getSchoolTimeZone($getParams['school_id']);
            if (!empty($getSchoolTimeZone) && isset($getSchoolTimeZone['time_zone'])) {
                date_default_timezone_set($getSchoolTimeZone['time_zone']);
                try {
                    $this->db->query("SET SESSION time_zone = '{$getSchoolTimeZone['utc_timezone']}'");
                } catch (\Exception $e) {
                    // Ignore timezone errors
                }
            }
        } else {
            // Use default timezone
            $defaultTimezone = '+00:00'; // Default timezone in MySQL format
            try {
                $this->db->query("SET SESSION time_zone = '{$defaultTimezone}'");
            } catch (\Exception $e) {
                // Ignore timezone errors for now
            }
        }

        // Load admin settings
        $adminSettings = $this->adminSettings('');
        foreach ($adminSettings as $key => $details) {
            $this->settings[$details['setting_name']] = $details['setting_value'];
        }
    }

    public function adminSettings($name)
    {
        $builder = $this->getBuilder();
        $builder->select('id as setting_id, value as setting_value, name as setting_name');
        $builder->where('status', 1);
        $adminSettings = $this->getResult($builder);

        if ($name != '') {
            foreach ($adminSettings as $setting) {
                if ($name == $setting['setting_name']) {
                    return [$setting];
                }
            }
        }
        return $adminSettings;
    }

    public function getSchoolTimeZone($schoolId)
    {
        $builder = $this->getBuilder('time_zone tz');
        $builder->select('tz.time_zone, tz.utc_timezone');
        $builder->join('admin_settings_school ass', "tz.id = ass.value AND ass.name = 'timezone' AND ass.school_id = {$schoolId}", 'inner');
        return $this->getResult($builder);
    }

    public function random_strings($length_of_string)
    {
        $str_result = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        return substr(str_shuffle($str_result), 0, $length_of_string);
    }

    public function decodeToken($token)
    {
        if (is_object($token) && property_exists($token, 'user')) {
            $tokenArray = explode('|', (string) $token->user);
            return $tokenArray[0] ?? false;
        }

        if (is_object($token) && property_exists($token, 'id')) {
            return $token->id;
        }

        return false;
    }

    public function smsEmailTemplate($templateName, $templateType)
    {
        $builder = $this->getBuilder('sms_templates');
        $builder->select('template, subject');
        $builder->where('template_name', $templateName);
        $builder->where('template_type', $templateType);
        return $this->getResult($builder);
    }

    public function checkPermissions($controller, $roleId)
    {
        $builder = $this->getBuilder('permission');
        $builder->select('permission_id');
        $builder->where('controller', $controller);
        $builder->where('status', 1);
        $permissionIdArray = $this->getResult($builder);

        if (count($permissionIdArray) > 0) {
            $permissionId = $permissionIdArray[0]['permission_id'];
            
            $builder = $this->getBuilder('role_permission');
            $builder->select('permission_id');
            $builder->where('role_id', $roleId);
            $builder->where('permission_id', $permissionId);
            $permissionsArray = $this->getResult($builder);

            return count($permissionsArray) > 0;
        }
        return false;
    }

    /**
     * Legacy permission guard used by the CI3-era controllers.
     *
     * @param string|null $controller Route string as stored in the permission table.
     * @param array|null  $params     Decoded request payload.
     * @param array|null  $headers    Request headers as provided by IncomingRequest::getHeaders().
     *
     * @return bool
     */
    public function checkPermission($controller = null, $params = null, $headers = null)
    {
        $params = is_array($params) ? $params : [];
        $roleId = isset($params['role_id']) ? (int) $params['role_id'] : null;
        $userId = isset($params['user_id']) ? (int) $params['user_id'] : null;

        $accessToken = Services::request()->getHeaderLine('Accesstoken');

        if (! $accessToken) {
            $accessToken = $this->extractAccessTokenFromInput($headers, $params);
        }

        if (! $accessToken) {
            $this->denyRequest(401, 'Unauthorized User');
        }

        if (! $userId) {
            $this->denyRequest(401, 'User Id should not be empty');
        }

        $tokenStatus = $this->verifyAccessToken($userId, $accessToken);
        if (! ($tokenStatus['success'] ?? false)) {
            $message = $tokenStatus['message'] ?? 'Unauthorized User';
            $this->denyRequest(401, $message);
        }

        if ($roleId && $controller) {
            $controllerKeys = $this->normaliseControllerKeys($controller);
            $hasPermission = false;
            foreach ($controllerKeys as $controllerKey) {
                if ($this->checkPermissions($controllerKey, $roleId)) {
                    $hasPermission = true;
                    break;
                }
            }

            if (! $hasPermission) {
                $this->denyRequest(403, 'You do not have permission to access this resource');
            }
        }

        return true;
    }

    private function extractAccessTokenFromInput($headers, array $params): ?string
    {
        $headerCandidates = ['Accesstoken', 'AccessToken', 'Access-Token'];

        foreach ($headerCandidates as $candidate) {
            if (! isset($headers[$candidate])) {
                continue;
            }

            $header = $headers[$candidate];

            if (is_array($header)) {
                $header = $header[0] ?? null;
            }

            if (is_object($header) && method_exists($header, 'getValue')) {
                $value = $header->getValue();
                if (! empty($value)) {
                    return $value;
                }
            } elseif (is_string($header) && $header !== '') {
                return $header;
            }
        }

        foreach (['Accesstoken', 'AccessToken', 'accessToken'] as $paramKey) {
            if (! empty($params[$paramKey])) {
                return (string) $params[$paramKey];
            }
        }

        return null;
    }

    private function normaliseControllerKeys(string $controller): array
    {
        $normalized = ltrim($controller, '/');
        $candidates = [$normalized];

        if (strpos($normalized, 'v1/') === 0) {
            $candidates[] = substr($normalized, 3);
        } else {
            $candidates[] = 'v1/' . $normalized;
        }

        return array_values(array_unique(array_filter($candidates)));
    }

    private function denyRequest(int $statusCode, string $message): void
    {
        $response = Services::response();
        $response->setStatusCode($statusCode);
        $response->setJSON([
            'IsSuccess' => false,
            'ErrorObject' => $message,
        ]);
        $response->send();

        exit;
    }

    /**
     * Lightweight controller permission check used by the migrated CI4 controllers.
     */
    public function checkControllerPermission(string $controller, int $roleId): bool
    {
        $builder = $this->getBuilder('permission');
        $builder->select('permission_id');
        $builder->where('controller', $controller);
        $builder->where('status', 1);
        $permissionRow = $builder->get()->getRowArray();

        if (! $permissionRow) {
            return false;
        }

        $permissionId = (int) $permissionRow['permission_id'];

        // Admin/teacher hybrid permissions (role_id === 4) honour user overrides.
        if ($roleId === 4) {
            $userPermissionBuilder = $this->getBuilder('user_permission up');
            $userPermissionBuilder->select('up.id');
            $userPermissionBuilder->join('user_role_permission urp', 'up.id = urp.user_permission_id');
            $userPermissionBuilder->where('up.permission_id', $permissionId);
            $userPermissionBuilder->where('up.status', 1);
            if ($userPermissionBuilder->countAllResults(false) > 0) {
                return true;
            }
        }

        $rolePermissionBuilder = $this->getBuilder('role_permission');
        $rolePermissionBuilder->select('permission_id');
        $rolePermissionBuilder->where('role_id', $roleId);
        $rolePermissionBuilder->where('permission_id', $permissionId);

        return $rolePermissionBuilder->countAllResults() > 0;
    }

    /**
     * Validate an access token against the user_token table.
     */
    public function verifyAccessToken(int $userId, string $accessToken): array
    {
        // Ensure authorization helper is loaded
        if (!class_exists('AUTHORIZATION')) {
            helper('authorization');
        }
        $decoded = \AUTHORIZATION::validateToken($accessToken);
        if (! $decoded) {
            return [
                'success' => false,
                'message' => "Unauthorised User",
            ];
        }

        $decodedUserId = $this->decodeToken($decoded);
        if ($decodedUserId === false || (int) $decodedUserId !== $userId) {
            return [
                'success' => false,
                'message' => "Unauthorised User",
            ];
        }

        $builder = $this->getBuilder('user_token');
        $builder->select('status');
        $builder->where('user_id', $userId);
        $builder->where('access_token', $accessToken);
        $tokenRow = $builder->get()->getRowArray();

        if ($tokenRow && (int) $tokenRow['status'] !== 1) {
            return [
                'success' => false,
                'message' => "Your session has expired. Kindly logout and relogin",
            ];
        }

        return [
            'success' => true,
            'payload' => $decoded,
        ];
    }

    public function insert($data = null, bool $returnID = true)
    {
        // If $data is an array with 'tablename' key, use the old behavior
        if (is_array($data) && isset($data['tablename'])) {
            $tablename = $data['tablename'];
            unset($data['tablename']);
            $builder = $this->getBuilder($tablename);
            $builder->insert($data);
            return $this->db->insertID();
        }
        
        // Otherwise use the parent class behavior
        return parent::insert($data, $returnID);
    }

    public function insertIntoTable($tablename, $data)
    {
        $builder = $this->getBuilder($tablename);
        $builder->insert($data);
        return $this->db->insertID();
    }

    public function bulkInsert($tablename, $data)
    {
        $builder = $this->getBuilder($tablename);
        return $builder->insertBatch($data);
    }

    public function update($id = null, $data = null): bool
    {
        // If $data is an array with 'tablename' and 'condition' keys, use the old behavior
        if (is_array($data) && isset($data['tablename']) && isset($data['condition'])) {
            $tablename = $data['tablename'];
            $condition = $data['condition'];
            unset($data['tablename'], $data['condition']);
            
            $builder = $this->getBuilder($tablename);
            $builder->where($condition);
            return $builder->update($data);
        }
        
        // If $id is a string (table name) and $data is an array with condition, use the old behavior
        if (is_string($id) && is_array($data) && isset($data['condition'])) {
            $tablename = $id;
            $condition = $data['condition'];
            unset($data['condition']);
            
            $builder = $this->getBuilder($tablename);
            $builder->where($condition);
            return $builder->update($data);
        }
        
        // Otherwise use the parent class behavior
        return parent::update($id, $data);
    }

    public function updateTable($tablename, $data, $condition)
    {
        $builder = $this->getBuilder($tablename);
        $builder->where($condition);
        return $builder->update($data);
    }

    public function updateBatchMultiple($tablename, $data, $condition, $studentContentId)
    {
        $builder = $this->getBuilder($tablename);
        $builder->where('student_content_id', $studentContentId);
        return $builder->updateBatch($data, $condition);
    }

    public function updateBatchMultiple1($tablename, $data, $condition, $classId, $studentId, $contentId)
    {
        $builder = $this->getBuilder($tablename);
        $builder->where('class_id', $classId);
        $builder->where('content_id', $contentId);
        $builder->where('student_id', $studentId);
        return $builder->updateBatch($data, $condition);
    }

    public function delete($id = null, bool $purge = false)
    {
        // If $id is a string (table name) and $purge is an array (condition), use the old behavior
        if (is_string($id) && is_array($purge)) {
            $tablename = $id;
            $condition = $purge;
            
            $builder = $this->getBuilder($tablename);
            $builder->where($condition);
            return $builder->delete();
        }
        
        // Otherwise use the parent class behavior
        return parent::delete($id, $purge);
    }

    public function deleteFromTable($tablename, $condition)
    {
        $builder = $this->getBuilder($tablename);
        $builder->where($condition);
        return $builder->delete();
    }

    public function multipleDelete($tablename, $key, $condition)
    {
        $builder = $this->getBuilder($tablename);
        $builder->whereIn($key, $condition);
        return $builder->delete();
    }

    public function checkToken($params)
    {
        $request = Services::request();
        $headers = $request->getHeaders();
        
        if (isset($headers['Accesstoken']) && !empty($headers['Accesstoken'])) {
            $userId = $this->decodeToken(\AUTHORIZATION::validateToken($headers['Accesstoken']));
            return $userId;
        }
        return false;
    }

    public function zoomKeys($schoolId, $type)
    {
        $builder = $this->getBuilder('zoom_keys');
        $builder->select('api_key, api_secret');
        $builder->where('school_id', $schoolId);
        $builder->where('type', $type);
        return $this->getResult($builder);
    }

    public function getZoomKeys($schoolId)
    {
        $builder = $this->getBuilder('zoom_keys');
        $builder->select('api_key, api_secret');
        $builder->where('school_id', $schoolId);
        return $this->getResult($builder);
    }

    public function getMeetingid($params, $slotId, $scheduleId)
    {
        $builder = $this->getBuilder('zoom_meetings');
        $builder->select('meeting_id');
        $builder->where('slot_id', $slotId);
        $builder->where('schedule_id', $scheduleId);
        return $this->getResult($builder);
    }

    public function dateFormat($dateId)
    {
        return date('Y-m-d H:i:s', strtotime($dateId));
    }

    public function timezoneList()
    {
        $builder = $this->getBuilder('time_zone');
        $builder->select('id, time_zone, utc_timezone');
        return $this->getResult($builder);
    }

    public function annotation($fileName)
    {
        // Implement annotation functionality
        // This is a placeholder for the actual annotation implementation
        return true;
    }

    public function createFlatFile($folder, $file, $annotation)
    {
        // Implement flat file creation
        // This is a placeholder for the actual flat file creation implementation
        return true;
    }

    public function iosVersion()
    {
        $builder = $this->getBuilder('app_versions');
        $builder->select('version');
        $builder->where('platform', 'ios');
        $builder->orderBy('id', 'DESC');
        $builder->limit(1);
        return $this->getResult($builder);
    }

    public function className($classId)
    {
        $builder = $this->getBuilder('class');
        $builder->select('class_name');
        $builder->where('class_id', $classId);
        return $this->getResult($builder);
    }

    public function countryList()
    {
        $builder = $this->getBuilder('country');
        $builder->select('id, name');
        $builder->orderBy('name', 'ASC');
        return $this->getResult($builder);
    }

    public function stateList($countryId)
    {
        $builder = $this->getBuilder('state');
        $builder->select('id, name');
        $builder->where('country_id', $countryId);
        $builder->orderBy('name', 'ASC');
        return $this->getResult($builder);
    }

    /**
     * Helper used by the new Common controller.
     */
    public function getCountries($params = [])
    {
        return $this->countryList();
    }

    /**
     * Helper used by the new Common controller.
     */
    public function getStates($params = [])
    {
        $countryId = $params['country_id'] ?? $params['countryId'] ?? null;
        if (empty($countryId)) {
            return [];
        }
        return $this->stateList($countryId);
    }

    /**
     * Helper used by the new Common controller.
     */
    public function getCities($params = [])
    {
        if (! $this->db->tableExists('cities')) {
            return [];
        }

        $builder = $this->getBuilder('cities');
        $builder->select('cities.id, cities.name');

        if (! empty($params['state_id'])) {
            $builder->where('cities.state_id', $params['state_id']);
        } elseif (! empty($params['stateId'])) {
            $builder->where('cities.state_id', $params['stateId']);
        }

        if (! empty($params['country_id'])) {
            $builder->join('states', 'states.id = cities.state_id', 'inner');
            $builder->where('states.country_id', $params['country_id']);
        }

        $builder->orderBy('cities.name', 'ASC');
        return $this->getResult($builder);
    }

    public function schoolName($schoolName)
    {
        $builder = $this->getBuilder('school');
        $builder->select('school_id, name');
        $builder->like('name', $schoolName);
        return $this->getResult($builder);
    }

    public function emailId($userId)
    {
        $builder = $this->getBuilder('user');
        $builder->select('email_id');
        $builder->where('user_id', $userId);
        return $this->getResult($builder);
    }

    public function searchGrade($data, $schoolId)
    {
        $builder = $this->getBuilder('grade');
        $builder->select('grade_id, grade_name');
        $builder->where('school_id', $schoolId);
        $builder->like('grade_name', $data);
        return $this->getResult($builder);
    }

    public function invitedRecords($Id)
    {
        $builder = $this->getBuilder('invited_records');
        $builder->select('*');
        $builder->where('id', $Id);
        return $this->getResult($builder);
    }

    public function searchState($state)
    {
        $builder = $this->getBuilder('states');
        $builder->select('id, name');
        $builder->like('name', $state);
        return $this->getResult($builder);
    }

    public function searchCountry($country)
    {
        $builder = $this->getBuilder('countries');
        $builder->select('id, name');
        $builder->like('name', $country);
        return $this->getResult($builder);
    }

    public function searchCountry1($id)
    {
        $builder = $this->getBuilder('countries');
        $builder->select('id, name');
        $builder->where('id', $id);
        return $this->getResult($builder);
    }

    public function checkUser($email)
    {
        $builder = $this->getBuilder('user');
        $builder->select('user_id, role_id');
        $builder->where('email_id', $email);
        return $this->getResult($builder);
    }

    public function checkClassEnrollNotification($email)
    {
        $builder = $this->getBuilder('user');
        $builder->select('user_id, role_id');
        $builder->where('email_id', $email);
        return $this->getResult($builder);
    }

    public function schoolDetail($data)
    {
        $builder = $this->getBuilder('school');
        $builder->select('school_id, name');
        $builder->where('school_id', $data);
        return $this->getResult($builder);
    }

    public function getUserId($id, $schoolId)
    {
        $builder = $this->getBuilder('user');
        $builder->select('user_id');
        $builder->where('user_id', $id);
        $builder->where("FIND_IN_SET(?, school_id)", [$schoolId]);
        return $this->getResult($builder);
    }

    public function checkSchool($params)
    {
        $builder = $this->getBuilder('school');
        $builder->select('school_id');
        $builder->where('school_id', $params['school_id']);
        return $this->getResult($builder);
    }

    public function tagsList($params)
    {
        $builder = $this->getBuilder('tags');
        $builder->select('tag_id, tag_name');
        $builder->where('school_id', $params['school_id']);
        return $this->getResult($builder);
    }

    public function getAdmin($schoolId)
    {
        $builder = $this->getBuilder('user u');
        $builder->select('u.user_id, u.email_id');
        $builder->join('user_profile up', 'u.user_id = up.user_id', 'left');
        $builder->where('u.role_id', 1);
        $builder->where("FIND_IN_SET(?, u.school_id)", [$schoolId]);
        return $this->getResult($builder);
    }

    public function settingList($params)
    {
        $builder = $this->getBuilder('admin_settings_school');
        $builder->select('id, name, description, value');
        $builder->where('status', 1);
        
        // Filter by school_id if provided
        if (isset($params['school_id']) && !empty($params['school_id'])) {
            $builder->where('school_id', $params['school_id']);
        }
        
        return $this->getResult($builder);
    }

    public function getTeacher($userId, $schoolId)
    {
        $builder = $this->getBuilder('user u');
        $builder->select('u.user_id, u.email_id');
        $builder->join('user_profile up', 'u.user_id = up.user_id', 'left');
        $builder->where('u.role_id', 2);
        $builder->where('u.user_id', $userId);
        $builder->where("FIND_IN_SET(?, u.school_id)", [$schoolId]);
        return $this->getResult($builder);
    }

    public function sendEmail($subject, $emailid, $message, $attachment = null, $cc = null)
    {
        $email = Services::email();
        $email->setFrom(config('Email')->fromEmail, config('Email')->fromName);
        $email->setTo($emailid);
        
        if ($cc) {
            $email->setCC($cc);
        }
        
        $email->setSubject($subject);
        $email->setMessage($message);
        
        if ($attachment) {
            $email->attach($attachment);
        }
        
        return $email->send();
    }

    public function sendEmail1($subject, $emailid, $message, $attachment = null)
    {
        return $this->sendEmail($subject, $emailid, $message, $attachment);
    }

    public function sendEmailParent($subject, $To, $message, $attachment = null)
    {
        return $this->sendEmail($subject, $To, $message, $attachment);
    }

    public function sendEmailParent1($subject, $To, $message, $attachment = null)
    {
        return $this->sendEmail($subject, $To, $message, $attachment);
    }

    public function smsgates($code, $mobileno, $msg)
    {
        // Implement SMS gateway integration
        // This is a placeholder for the actual SMS gateway implementation
        return true;
    }

    public function smsgates_otp($code, $mobileno, $msg, $otp, $email)
    {
        // Implement OTP SMS gateway integration
        // This is a placeholder for the actual OTP SMS gateway implementation
        return true;
    }

    public function validatePincode($details)
    {
        // Implement pincode validation
        // This is a placeholder for the actual pincode validation implementation
        return true;
    }

    public function createLog($data, $url, $responseArray, $usage)
    {
        try {
            // Check if api_logs table exists before trying to insert
            if (!$this->db->tableExists('api_logs')) {
                // Table doesn't exist, skip logging
                return false;
            }
            
            $logData = [
                'request_data' => json_encode($data),
                'url' => $url,
                'response_data' => json_encode($responseArray),
                'usage' => $usage,
                'created_date' => date('Y-m-d H:i:s')
            ];
            
            // Use builder directly to insert into api_logs table
            $builder = $this->getBuilder('api_logs');
            $builder->insert($logData);
            return $this->db->insertID();
        } catch (\Exception $e) {
            // Log the error but don't fail the request
            log_message('error', 'createLog failed: ' . $e->getMessage());
            return false;
        }
    }

    public function insertLog($table, $logTable, $condition)
    {
        $builder = $this->getBuilder($table);
        $data = $builder->where($condition)->get()->getResultArray();
        
        if (!empty($data)) {
            return $this->bulkInsert($logTable, $data);
        }
        return false;
    }

    public function validateGoogleLogin($type)
    {
        // Implement Google login validation
        // This is a placeholder for the actual Google login validation implementation
        return true;
    }

    private function printjson($jsonarr)
    {
        echo json_encode($jsonarr);
        exit;
    }

    /**
     * Get list of tags
     */
    public function getTagsList($data)
    {
        // Return empty array for now - tags are typically used for content categorization
        // which may not be critical for initial testing
        return [];
    }
} 
