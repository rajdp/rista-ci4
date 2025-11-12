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
}
