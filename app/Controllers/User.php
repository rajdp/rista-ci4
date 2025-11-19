<?php

namespace App\Controllers;

use App\Models\V1\UserModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;

class User extends ResourceController
{
    use ResponseTrait;

    protected $modelName = 'App\Models\V1\UserModel';
    protected $format = 'json';

    public function __construct()
    {
        $this->model = new UserModel();
    }

    public function index()
    {
        try {
            $data = $this->model->userList([]);
            return $this->respond($data);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function show($id = null)
    {
        try {
            $data = $this->model->getUserById($id);
            if (!$data) {
                return $this->failNotFound('User not found');
            }
            return $this->respond($data);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function create()
    {
        $db = \Config\Database::connect();

        try {
            $payload = $this->request->getJSON();

            if (! $payload) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Invalid request payload'
                ], ResponseInterface::HTTP_BAD_REQUEST);
            }

            $email = strtolower(trim($payload->email_id ?? $payload->email ?? ''));
            $password = $payload->password ?? null;
            $roleId = isset($payload->role_id) ? (int) $payload->role_id : 0;

            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'A valid email is required'
                ], ResponseInterface::HTTP_BAD_REQUEST);
            }

            if (empty($password)) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Password is required'
                ], ResponseInterface::HTTP_BAD_REQUEST);
            }

            if ($roleId <= 0) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Role is required'
                ], ResponseInterface::HTTP_BAD_REQUEST);
            }

            if ($this->model->getUserByEmail($email)) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Email already registered'
                ], ResponseInterface::HTTP_CONFLICT);
            }

            $salt = 'ristainternational';
            $hashedPassword = md5($salt . $password . $salt);

            $schoolId = $payload->school_id ?? null;
            $corporateId = $payload->corporate_id ?? null;

            $userData = [
                'email_id' => $email,
                'password' => $hashedPassword,
                'role_id' => $roleId,
                'school_id' => $schoolId && $schoolId !== '' ? (string) $schoolId : '0',
                'corporate_id' => $corporateId && $corporateId !== '' ? (string) $corporateId : '0',
                'status' => 1,
                'default_password' => 1,
                'created_date' => date('Y-m-d H:i:s'),
                'individual_teacher' => 0,
                'login_type' => '',
                'tc_status' => 0,
                'edquill_teacher_id' => 0,
                'auto_generate_email_edquill' => 0,
                'student_id' => '',
                'academy_user_id' => 0,
            ];

            $firstName = $payload->first_name ?? '';
            $lastName = $payload->last_name ?? '';

            $db->transBegin();

            $userId = $this->model->createUser($userData);

            if (! $userId) {
                $db->transRollback();

                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => $this->model->getLastDbError()['message'] ?? 'Failed to create user'
                ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }

            if ($firstName !== '' || $lastName !== '') {
                $this->model->saveUserProfile($userId, [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                ]);
            }

            if (is_numeric($schoolId) && (int) $schoolId > 0) {
                $this->model->syncUserProfileDetails($userId, $schoolId);
            }

            $db->transCommit();

            return $this->respondCreated([
                'IsSuccess' => true,
                'ResponseObject' => [
                    'user_id' => $userId,
                    'message' => 'User created successfully',
                ],
                'ErrorObject' => null,
            ]);
        } catch (\Throwable $e) {
            if ($db->transStatus() === false) {
                $db->transRollback();
            }

            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage(),
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update($id = null)
    {
        try {
            error_log('[LOGIN] Request received');
            $data = $this->request->getJSON();
            error_log('[LOGIN] Parsed JSON: ' . json_encode($data));
            
            $userData = [
                'email_id' => $data->email_id,
                'role_id' => $data->role_id,
                'school_id' => $data->school_id,
                'modified_date' => date('Y-m-d H:i:s')
            ];

            $profileData = [
                'first_name' => $data->first_name,
                'last_name' => $data->last_name,
                'modified_date' => date('Y-m-d H:i:s')
            ];

            if (!$this->model->updateUser($id, $userData, $profileData)) {
                return $this->fail('Failed to update user');
            }
            return $this->respond(['message' => 'User updated successfully']);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function delete($id = null)
    {
        try {
            if (!$this->model->deleteUser($id, $this->request->getJSON()->school_id)) {
                return $this->fail('Failed to delete user');
            }
            return $this->respondDeleted(['message' => 'User deleted successfully']);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function login()
    {
        try {
            $data = $this->request->getJSON();
            
            if (!isset($data->username) || !isset($data->password)) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Username and password are required'
                ], 400);
            }

            if (!isset($data->platform) || ($data->platform != 'web' && $data->platform != 'ios')) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Platform should not be empty'
                ], 400);
            }

            // Salt the password before passing to model (CI3 format)
            $salt = 'ristainternational';
            $saltedPassword = $salt . $data->password . $salt;

            $result = $this->model->login([
                'username' => $data->username,
                'password' => $saltedPassword,
                'platform' => $data->platform
            ]);

            if (!$result || empty($result)) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Invalid credentials'
                ], 401);
            }

            // Return in CI3 format
            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $result,
                'ErrorObject' => ''
            ]);

        } catch (\Throwable $e) {
            log_message('error', '[LOGIN] ' . $e->getMessage());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ], 500);
        }
    }

    public function register()
    {
        try {
            $data = $this->request->getJSON();
            $salt = 'ristainternational';
            $data->password = md5($salt . $data->password . $salt);
            
            // Check if user already exists
            $existingUser = $this->model->getUserByEmail($data->email);
            if ($existingUser) {
                return $this->fail('Email already registered', 409);
            }

            $userId = $this->model->createUser([
                'email_id' => $data->email,
                'password' => $data->password,
                'role_id' => $data->role_id ?? 5, // Default to student
                'school_id' => $data->school_id ?? '',
                'status' => 1
            ]);

            if (!$userId) {
                return $this->fail('Failed to register user');
            }

            return $this->respondCreated([
                'message' => 'Registration successful',
                'user_id' => $userId
            ]);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function profile()
    {
        try {
            $userId = 1; // Replace with actual user ID from session/token
            $data = $this->model->getUserProfile($userId, $this->request->getJSON()->school_id);
            if (!$data) {
                return $this->failNotFound('User not found');
            }
            return $this->respond($data);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * Get my profile (current user)
     */
    public function myProfile()
    {
        try {
            $data = $this->request->getJSON();
            
            if (!isset($data->user_id)) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'User ID is required'
                ], 400);
            }

            $profile = $this->model->getMyProfile($data->user_id);
            
            if (!$profile) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'User not found'
                ], 404);
            }

            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $profile,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user detail by user ID
     * Accepts selected_user_id or user_id in request
     */
    public function getUserDetail()
    {
        try {
            $data = $this->request->getJSON(true) ?? [];
            
            if (empty($data)) {
                $data = $this->request->getPost() ?? [];
            }

            // Accept either selected_user_id or user_id
            $userId = $data['selected_user_id'] ?? $data['user_id'] ?? null;
            
            if (!$userId) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'User ID is required'
                ], 400);
            }

            $profile = $this->model->getMyProfile($userId);
            
            if (!$profile) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'User not found'
                ], 404);
            }

            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $profile,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get dashboard statistics
     */
    public function dashBoard()
    {
        try {
            $data = $this->request->getJSON();
            
            if (!$data) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Invalid request data'
                ], 400);
            }

            $schoolId = $data->school_id ?? null;
            
            if (!$schoolId) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'School ID is required'
                ], 400);
            }

            // Get dashboard counts and statistics
            $dashboardData = $this->model->getDashboardData($schoolId);
            
            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $dashboardData,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user records
     */
    public function records()
    {
        try {
            $data = $this->request->getJSON();
            
            if (!$data) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Invalid request data'
                ], 400);
            }

            $schoolId = $data->school_id ?? null;
            
            if (!$schoolId) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'School ID is required'
                ], 400);
            }

            // Get user records data
            $records = $this->model->getUserRecords($schoolId);
            
            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $records,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user content (monthly content statistics)
     */
    public function content()
    {
        try {
            $data = $this->request->getJSON();
            
            if (!$data) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Invalid request data'
                ], 400);
            }

            $schoolId = $data->school_id ?? null;
            
            if (!$schoolId) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'School ID is required'
                ], 400);
            }

            // Get content statistics for the last 6 months
            $contentStats = $this->model->getContentStatistics($schoolId);
            
            // Format data for last 6 months
            $months = [];
            for ($i = 0; $i <= 6; $i++) {
                $monthName = date("M", strtotime(date('Y-m-d') . " -$i months"));
                $months[] = [
                    'monthname' => $monthName,
                    'content' => 0,
                    'percentage' => 0
                ];
            }

            // Merge with actual data
            foreach ($months as $key => $month) {
                foreach ($contentStats as $stat) {
                    if ($month['monthname'] == $stat['monthname']) {
                        $months[$key]['content'] = $stat['content'];
                        $months[$key]['percentage'] = $stat['percentage'];
                    }
                }
            }
            
            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $months,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get configuration values (stub - needs full implementation)
     */
    public function configValues(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            // For now, return empty config - needs full implementation from CI3
            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => [],
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Logout user by invalidating their token
     */
    public function logout(): ResponseInterface
    {
        try {
            $token = $this->request->getHeaderLine('Accesstoken');
            
            if (empty($token)) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Access token required'
                ], 400);
            }

            $db = \Config\Database::connect();
            
            // Invalidate the current token
            $updated = $db->table('user_token')
                ->where('access_token', $token)
                ->update([
                    'status' => 0,
                    'modified_date' => date('Y-m-d H:i:s')
                ]);

            if ($updated) {
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => ['message' => 'Logged out successfully'],
                    'ErrorObject' => ''
                ]);
            } else {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Token not found or already invalidated'
                ], 400);
            }

        } catch (\Exception $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Refresh access token when it expires
     * This allows users to continue working without being logged out
     */
    public function refreshToken(): ResponseInterface
    {
        try {
            $oldToken = $this->request->getHeaderLine('Accesstoken');
            
            if (empty($oldToken)) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Access token required'
                ], 400);
            }

            $db = \Config\Database::connect();
            
            // Decode the token to get user info (ignore expiration for refresh)
            try {
                $tokenPayload = \App\Libraries\Authorization::validateToken($oldToken);
                if (!$tokenPayload) {
                    return $this->respond([
                        'IsSuccess' => false,
                        'ResponseObject' => null,
                        'ErrorObject' => 'Invalid token format'
                    ], 401);
                }
            } catch (\Throwable $e) {
                log_message('error', 'Token refresh - decode failed: ' . $e->getMessage());
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Invalid token format'
                ], 401);
            }

            // Get user info from token
            $userId = \App\Libraries\Authorization::getUserId($tokenPayload);
            $roleId = $tokenPayload->role_id ?? null;
            $schoolId = \App\Libraries\Authorization::getSchoolId($tokenPayload);

            if (!$userId || !$roleId || !$schoolId) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Invalid token payload'
                ], 401);
            }

            // Check if the old token exists and is still active in database
            $tokenStatus = $db->table('user_token')
                ->select('status, user_id')
                ->where('access_token', $oldToken)
                ->get()
                ->getRowArray();

            if (!$tokenStatus) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Token not found in database'
                ], 401);
            }

            // Check if token is still active (status = 1)
            if ((int)$tokenStatus['status'] !== 1) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Token has been invalidated. Please log in again.'
                ], 401);
            }

            // Verify user_id matches
            if ((int)$tokenStatus['user_id'] !== (int)$userId) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Token user mismatch'
                ], 401);
            }

            // Check how long the token has been expired
            // Allow refresh only if token expired within the last 24 hours (for security)
            // This prevents indefinite refresh of very old tokens
            $tokenTimestamp = property_exists($tokenPayload, 'timestamp') ? (int)$tokenPayload->timestamp : null;
            if ($tokenTimestamp) {
                $tokenTimeout = config('Jwt')->tokenTimeout * 60; // Convert to seconds
                $tokenExpiryTime = $tokenTimestamp + $tokenTimeout;
                $timeSinceExpiry = time() - $tokenExpiryTime;
                $maxRefreshWindow = 24 * 60 * 60; // 24 hours in seconds
                
                if ($timeSinceExpiry > $maxRefreshWindow) {
                    log_message('info', 'Token refresh rejected - token expired too long ago. Time since expiry: ' . round($timeSinceExpiry / 3600, 2) . ' hours');
                    return $this->respond([
                        'IsSuccess' => false,
                        'ResponseObject' => null,
                        'ErrorObject' => 'Token has expired too long ago. Please log in again.'
                    ], 401);
                }
            }

            // Generate new token
            $newTokenPayload = [
                'user_id' => $userId,
                'role_id' => $roleId,
                'school_id' => $schoolId,
                'timestamp' => time()
            ];
            
            $newAccessToken = \App\Libraries\Authorization::generateToken($newTokenPayload);

            // Update database: invalidate old token and create new one
            $db->transStart();
            
            // Invalidate old token
            $db->table('user_token')
                ->where('access_token', $oldToken)
                ->update([
                    'status' => 0,
                    'modified_date' => date('Y-m-d H:i:s')
                ]);

            // Insert new token
            $db->table('user_token')->insert([
                'user_id' => $userId,
                'access_token' => $newAccessToken,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                'status' => 1,
                'created_date' => date('Y-m-d H:i:s')
            ]);

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Failed to refresh token'
                ], 500);
            }

            // Return new token in CI3 format
            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => [
                    'Accesstoken' => $newAccessToken
                ],
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Token refresh error: ' . $e->getMessage());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => 'Token refresh failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Change user password
     */
    public function changePassword(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            if (!$data) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Invalid request data'
                ], 400);
            }

            // Validate required fields
            if (!isset($data->user_id) || !isset($data->old_password) || !isset($data->password)) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'User ID, old password, and new password are required'
                ], 400);
            }

            // Validate password match
            if (isset($data->confirm_password) && $data->password !== $data->confirm_password) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'New password and confirm password do not match'
                ], 400);
            }

            // Change password
            $result = $this->model->changePassword(
                $data->user_id,
                $data->old_password,
                $data->password
            );

            if ($result === true) {
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => ['message' => 'Password changed successfully'],
                    'ErrorObject' => ''
                ]);
            } else {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => $result ?: 'Failed to change password'
                ], 400);
            }

        } catch (\Exception $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * List users matching provided filters.
     */
    public function list(): ResponseInterface
    {
        try {
            $payload = $this->request->getJSON(true) ?? [];

            if (empty($payload)) {
                $payload = $this->request->getPost() ?? [];
            }

            $users = $this->model->userList($payload ?? []);

            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $users,
                'ErrorObject' => ''
            ]);
        } catch (\Throwable $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => [],
                'ErrorObject' => $e->getMessage()
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Check student details for annotation purposes
     * Returns student and teacher annotations along with PDF path
     */
    public function checkDetails(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            // Validate required fields
            if (empty($params['student_content_id']) || $params['student_content_id'] === '0' || $params['student_content_id'] === 0) {
                log_message('debug', '⚠️ checkDetails: student_content_id is missing or invalid. Received: ' . json_encode($params['student_content_id'] ?? 'NOT SET'));
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Student content ID is required'
                ], 400);
            }

            $db = \Config\Database::connect();
            $studentContentId = (int)$params['student_content_id'];

            // Get student content with annotations
            $query = "SELECT 
                        sc.annotation AS student_annotation, 
                        sc.teacher_annotation,
                        sc.answer_sheet_annotation,
                        c.file_path,
                        c.annotation AS content_annotation,
                        c.questionAnnotation
                      FROM student_content sc
                      LEFT JOIN content c ON c.content_id = sc.content_id
                      WHERE sc.id = {$studentContentId}
                      LIMIT 1";

            $result = $db->query($query)->getRowArray();

            if (empty($result)) {
                // Return empty annotations instead of 404 to allow frontend to handle gracefully
                log_message('debug', '⚠️ checkDetails: Student content not found for ID: ' . $studentContentId);
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => [
                        'pdfpath' => '',
                        'student_annotation' => [],
                        'teacher_annotation' => []
                    ],
                    'ErrorObject' => ''
                ]);
            }

            // Helper function to decode annotation (similar to TeacherModel)
            $decodeAnnotation = function($data) {
                if (empty($data) || $data === '[]') {
                    return '[]';
                }
                try {
                    // Decode 4 times as per CI3 logic (stored as base64(base64(base64(base64(JSON)))))
                    $decoded = base64_decode($data);
                    $decoded = base64_decode($decoded);
                    $decoded = base64_decode($decoded);
                    $decoded = base64_decode($decoded);
                    return $decoded ?: '[]';
                } catch (\Exception $e) {
                    log_message('error', 'Annotation decode error: ' . $e->getMessage());
                    return '[]';
                }
            };

            // Process annotations
            $studentAnnotation = $result['student_annotation'] ?? '';
            if (!empty($studentAnnotation) && $studentAnnotation !== '[]') {
                // Check if it's a file path (starts with "uploads/")
                if (strpos($studentAnnotation, 'uploads/') === 0) {
                    // It's a file path - read the file
                    $filePath = FCPATH . $studentAnnotation;
                    log_message('debug', '🟢 checkDetails: Reading annotation from file: ' . $filePath);
                    
                    if (file_exists($filePath)) {
                        $fileContent = file_get_contents($filePath);
                        if ($fileContent !== false) {
                            $studentAnnotation = json_decode($fileContent, true);
                            if (!is_array($studentAnnotation)) {
                                log_message('warning', '⚠️ checkDetails: Failed to decode annotation JSON from file');
                                $studentAnnotation = [];
                            } else {
                                log_message('debug', '🟢 checkDetails: Successfully loaded ' . count($studentAnnotation) . ' annotations from file');
                            }
                        } else {
                            log_message('error', '❌ checkDetails: Failed to read annotation file: ' . $filePath);
                            $studentAnnotation = [];
                        }
                    } else {
                        log_message('warning', '⚠️ checkDetails: Annotation file not found: ' . $filePath);
                        $studentAnnotation = [];
                    }
                } else {
                    // It's base64-encoded data - decode it
                    $decoded = $decodeAnnotation($studentAnnotation);
                    $studentAnnotation = json_decode($decoded, true);
                    if (!is_array($studentAnnotation)) {
                        $studentAnnotation = [];
                    }
                }
            } else {
                $studentAnnotation = [];
            }

            $teacherAnnotation = $result['teacher_annotation'] ?? '';
            if (!empty($teacherAnnotation) && $teacherAnnotation !== '[]') {
                $decoded = $decodeAnnotation($teacherAnnotation);
                $teacherAnnotation = json_decode($decoded, true);
                if (!is_array($teacherAnnotation)) {
                    $teacherAnnotation = [];
                }
            } else {
                $teacherAnnotation = [];
            }

            // Process file_path (PDF path)
            $pdfPath = $result['file_path'] ?? '';
            if (!empty($pdfPath) && $pdfPath !== '[]') {
                $filePath = json_decode($pdfPath, true);
                if (!is_array($filePath)) {
                    $pdfPath = '';
                } else {
                    // Keep as JSON string for frontend to decode
                    $pdfPath = $pdfPath;
                }
            } else {
                $pdfPath = '';
            }

            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => [
                    'pdfpath' => $pdfPath,
                    'student_annotation' => $studentAnnotation,
                    'teacher_annotation' => $teacherAnnotation
                ],
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            log_message('error', 'User checkDetails error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ], 500);
        }
    }
}
