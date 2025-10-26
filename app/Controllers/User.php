<?php

namespace App\Controllers;

use App\Models\V1\UserModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;

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
        try {
            $data = $this->request->getJSON();
            $salt = 'ristainternational';
            $data->password = md5($salt . $data->password . $salt);
            
            $userId = $this->model->createUser([
                'email_id' => $data->email_id,
                'password' => $data->password,
                'role_id' => $data->role_id,
                'school_id' => $data->school_id ?? '',
                'status' => 1
            ]);

            if (!$userId) {
                return $this->fail('Failed to create user');
            }
            return $this->respondCreated(['message' => 'User created successfully', 'user_id' => $userId]);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function update($id = null)
    {
        try {
            $data = $this->request->getJSON();
            
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

        } catch (\Exception $e) {
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
}
