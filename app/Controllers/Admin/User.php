<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Traits\RestTrait;
use App\Models\Admin\UserModel;
use CodeIgniter\HTTP\ResponseInterface;

class User extends BaseController
{
    use RestTrait;

    protected $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    /**
     * Get admin user list
     */
    public function adminList(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            $users = $this->userModel->getAdminUsers($data ?? []);
            
            return $this->successResponse($users, 'Users retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve users: ' . $e->getMessage());
        }
    }

    /**
     * Add new admin user
     */
    public function addAdmin(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            if (!$data) {
                return $this->errorResponse('Invalid request data');
            }

            // Validate required fields
            $requiredFields = ['first_name', 'last_name', 'email', 'password', 'role_id'];
            if (!$this->validateRequiredFields((array)$data, $requiredFields)) {
                $missing = $this->getMissingFields((array)$data, $requiredFields);
                return $this->errorResponse('Missing required fields: ' . implode(', ', $missing));
            }

            $userId = $this->userModel->createAdminUser($data);
            
            if ($userId) {
                return $this->successResponse(['user_id' => $userId], 'Admin user created successfully');
            } else {
                return $this->errorResponse('Failed to create admin user');
            }

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create admin user: ' . $e->getMessage());
        }
    }

    /**
     * Update admin user
     */
    public function updateAdmin(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            if (!$data) {
                return $this->errorResponse('Invalid request data');
            }

            // Validate required fields
            $requiredFields = ['user_id'];
            if (!$this->validateRequiredFields((array)$data, $requiredFields)) {
                $missing = $this->getMissingFields((array)$data, $requiredFields);
                return $this->errorResponse('Missing required fields: ' . implode(', ', $missing));
            }

            $result = $this->userModel->updateAdminUser($data->user_id, $data);
            
            if ($result) {
                return $this->successResponse($result, 'Admin user updated successfully');
            } else {
                return $this->errorResponse('Failed to update admin user');
            }

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update admin user: ' . $e->getMessage());
        }
    }

    /**
     * Get user details
     */
    public function adminDetail(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            if (!$data) {
                return $this->errorResponse('Invalid request data');
            }

            // Validate required fields
            $requiredFields = ['user_id'];
            if (!$this->validateRequiredFields((array)$data, $requiredFields)) {
                $missing = $this->getMissingFields((array)$data, $requiredFields);
                return $this->errorResponse('Missing required fields: ' . implode(', ', $missing));
            }

            $user = $this->userModel->getUserById($data->user_id);
            
            if (!$user) {
                return $this->errorResponse('User not found', 404);
            }

            return $this->successResponse($user, 'User details retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve user details: ' . $e->getMessage());
        }
    }

    /**
     * Delete admin user
     */
    public function adminRemove(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            if (!$data) {
                return $this->errorResponse('Invalid request data');
            }

            // Validate required fields
            $requiredFields = ['user_id'];
            if (!$this->validateRequiredFields((array)$data, $requiredFields)) {
                $missing = $this->getMissingFields((array)$data, $requiredFields);
                return $this->errorResponse('Missing required fields: ' . implode(', ', $missing));
            }

            $result = $this->userModel->deleteUser($data->user_id);
            
            if ($result) {
                return $this->successResponse(null, 'User deleted successfully');
            } else {
                return $this->errorResponse('Failed to delete user');
            }

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete user: ' . $e->getMessage());
        }
    }

    /**
     * Get user statistics
     */
    public function adminStats(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            $stats = $this->userModel->getUserStats($data ?? []);
            
            return $this->successResponse($stats, 'User statistics retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve user statistics: ' . $e->getMessage());
        }
    }

    /**
     * Change user status
     */
    public function changeStatus(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            if (!$data) {
                return $this->errorResponse('Invalid request data');
            }

            // Validate required fields
            $requiredFields = ['user_id', 'status'];
            if (!$this->validateRequiredFields((array)$data, $requiredFields)) {
                $missing = $this->getMissingFields((array)$data, $requiredFields);
                return $this->errorResponse('Missing required fields: ' . implode(', ', $missing));
            }

            $result = $this->userModel->changeUserStatus($data->user_id, $data->status);
            
            if ($result) {
                return $this->successResponse($result, 'User status updated successfully');
            } else {
                return $this->errorResponse('Failed to update user status');
            }

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update user status: ' . $e->getMessage());
        }
    }

    /**
     * Reset user password
     */
    public function resetPassword(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            if (!$data) {
                return $this->errorResponse('Invalid request data');
            }

            // Validate required fields
            $requiredFields = ['user_id', 'new_password'];
            if (!$this->validateRequiredFields((array)$data, $requiredFields)) {
                $missing = $this->getMissingFields((array)$data, $requiredFields);
                return $this->errorResponse('Missing required fields: ' . implode(', ', $missing));
            }

            $result = $this->userModel->resetUserPassword($data->user_id, $data->new_password);
            
            if ($result) {
                return $this->successResponse(null, 'Password reset successfully');
            } else {
                return $this->errorResponse('Failed to reset password');
            }

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to reset password: ' . $e->getMessage());
        }
    }

    /**
     * Get user permissions
     */
    public function getPermissions(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            if (!$data) {
                return $this->errorResponse('Invalid request data');
            }

            // Validate required fields
            $requiredFields = ['user_id'];
            if (!$this->validateRequiredFields((array)$data, $requiredFields)) {
                $missing = $this->getMissingFields((array)$data, $requiredFields);
                return $this->errorResponse('Missing required fields: ' . implode(', ', $missing));
            }

            $permissions = $this->userModel->getUserPermissions($data->user_id);
            
            return $this->successResponse($permissions, 'User permissions retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve user permissions: ' . $e->getMessage());
        }
    }

    /**
     * Update user permissions
     */
    public function updatePermissions(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            if (!$data) {
                return $this->errorResponse('Invalid request data');
            }

            // Validate required fields
            $requiredFields = ['user_id', 'permissions'];
            if (!$this->validateRequiredFields((array)$data, $requiredFields)) {
                $missing = $this->getMissingFields((array)$data, $requiredFields);
                return $this->errorResponse('Missing required fields: ' . implode(', ', $missing));
            }

            $result = $this->userModel->updateUserPermissions($data->user_id, $data->permissions);
            
            if ($result) {
                return $this->successResponse(null, 'User permissions updated successfully');
            } else {
                return $this->errorResponse('Failed to update user permissions');
            }

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update user permissions: ' . $e->getMessage());
        }
    }
}
