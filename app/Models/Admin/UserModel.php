<?php

namespace App\Models\Admin;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'email_id',
        'password',
        'role_id',
        'school_id',
        'status',
        'created_date',
        'modified_date'
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_date';
    protected $updatedField = 'modified_date';

    protected $validationRules = [
        'email_id' => 'required|valid_email|max_length[255]',
        'password' => 'required|min_length[6]',
        'role_id' => 'required|integer',
        'status' => 'required|in_list[0,1]'
    ];

    protected $validationMessages = [
        'email_id' => [
            'required' => 'Email is required',
            'valid_email' => 'Please provide a valid email address',
            'max_length' => 'Email cannot exceed 255 characters'
        ],
        'password' => [
            'required' => 'Password is required',
            'min_length' => 'Password must be at least 6 characters long'
        ],
        'role_id' => [
            'required' => 'Role ID is required',
            'integer' => 'Role ID must be an integer'
        ],
        'status' => [
            'required' => 'Status is required',
            'in_list' => 'Status must be either 0 or 1'
        ]
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    protected $beforeInsert = ['hashPassword'];
    protected $afterInsert = [];
    protected $beforeUpdate = ['hashPassword'];
    protected $afterUpdate = [];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    /**
     * Hash password before insert/update
     */
    protected function hashPassword(array $data)
    {
        if (isset($data['data']['password'])) {
            $data['data']['password'] = password_hash($data['data']['password'], PASSWORD_DEFAULT);
        }
        return $data;
    }

    /**
     * Get users for admin with filters
     */
    public function getAdminUsers($filters = [])
    {
        $builder = $this->builder();
        
        // Join with user_profiles, schools, and roles tables
        $builder->select('users.*, user_profiles.first_name, user_profiles.last_name, schools.school_name, roles.role_name');
        $builder->join('user_profiles', 'user_profiles.user_id = users.id', 'left');
        $builder->join('schools', 'schools.id = users.school_id', 'left');
        $builder->join('roles', 'roles.id = users.role_id', 'left');
        
        // Apply filters
        if (isset($filters['search']) && !empty($filters['search'])) {
            $builder->groupStart()
                    ->like('users.email_id', $filters['search'])
                    ->orLike('user_profiles.first_name', $filters['search'])
                    ->orLike('user_profiles.last_name', $filters['search'])
                    ->orLike('schools.school_name', $filters['search'])
                    ->groupEnd();
        }
        
        if (isset($filters['role_id']) && $filters['role_id'] > 0) {
            $builder->where('users.role_id', $filters['role_id']);
        }
        
        if (isset($filters['school_id']) && $filters['school_id'] > 0) {
            $builder->where('users.school_id', $filters['school_id']);
        }
        
        if (isset($filters['status']) && $filters['status'] !== '') {
            $builder->where('users.status', $filters['status']);
        }
        
        // Date range filters
        if (isset($filters['created_from']) && !empty($filters['created_from'])) {
            $builder->where('users.created_date >=', $filters['created_from']);
        }
        
        if (isset($filters['created_to']) && !empty($filters['created_to'])) {
            $builder->where('users.created_date <=', $filters['created_to']);
        }
        
        // Order by
        $orderBy = $filters['order_by'] ?? 'users.created_date';
        $orderDirection = $filters['order_direction'] ?? 'DESC';
        $builder->orderBy($orderBy, $orderDirection);
        
        // Pagination
        if (isset($filters['limit']) && $filters['limit'] > 0) {
            $builder->limit($filters['limit']);
        }
        
        if (isset($filters['offset']) && $filters['offset'] > 0) {
            $builder->offset($filters['offset']);
        }
        
        return $builder->get()->getResultArray();
    }

    /**
     * Get user by ID
     */
    public function getUserById($userId)
    {
        $builder = $this->builder();
        $builder->select('users.*, user_profiles.first_name, user_profiles.last_name, schools.school_name, roles.role_name');
        $builder->join('user_profiles', 'user_profiles.user_id = users.id', 'left');
        $builder->join('schools', 'schools.id = users.school_id', 'left');
        $builder->join('roles', 'roles.id = users.role_id', 'left');
        $builder->where('users.id', $userId);
        
        return $builder->get()->getRowArray();
    }

    /**
     * Create new admin user
     */
    public function createAdminUser($data)
    {
        $userData = [
            'email_id' => $data->email ?? '',
            'password' => $data->password ?? '',
            'role_id' => $data->role_id ?? 1,
            'school_id' => $data->school_id ?? null,
            'status' => $data->status ?? 1
        ];
        
        $userId = $this->insert($userData);
        
        if ($userId) {
            // Create user profile
            $profileData = [
                'user_id' => $userId,
                'first_name' => $data->first_name ?? '',
                'last_name' => $data->last_name ?? '',
                'phone' => $data->phone ?? '',
                'address' => $data->address ?? '',
                'city' => $data->city ?? '',
                'state' => $data->state ?? '',
                'country' => $data->country ?? '',
                'zip_code' => $data->zip_code ?? ''
            ];
            
            $this->db->table('user_profiles')->insert($profileData);
        }
        
        return $userId;
    }

    /**
     * Update admin user
     */
    public function updateAdminUser($userId, $data)
    {
        $updateData = [];
        
        if (isset($data->email)) {
            $updateData['email_id'] = $data->email;
        }
        if (isset($data->password)) {
            $updateData['password'] = $data->password;
        }
        if (isset($data->role_id)) {
            $updateData['role_id'] = $data->role_id;
        }
        if (isset($data->school_id)) {
            $updateData['school_id'] = $data->school_id;
        }
        if (isset($data->status)) {
            $updateData['status'] = $data->status;
        }
        
        $result = false;
        if (!empty($updateData)) {
            $result = $this->update($userId, $updateData);
        }
        
        // Update user profile if provided
        if (isset($data->first_name) || isset($data->last_name) || isset($data->phone) || 
            isset($data->address) || isset($data->city) || isset($data->state) || 
            isset($data->country) || isset($data->zip_code)) {
            
            $profileUpdateData = [];
            
            if (isset($data->first_name)) {
                $profileUpdateData['first_name'] = $data->first_name;
            }
            if (isset($data->last_name)) {
                $profileUpdateData['last_name'] = $data->last_name;
            }
            if (isset($data->phone)) {
                $profileUpdateData['phone'] = $data->phone;
            }
            if (isset($data->address)) {
                $profileUpdateData['address'] = $data->address;
            }
            if (isset($data->city)) {
                $profileUpdateData['city'] = $data->city;
            }
            if (isset($data->state)) {
                $profileUpdateData['state'] = $data->state;
            }
            if (isset($data->country)) {
                $profileUpdateData['country'] = $data->country;
            }
            if (isset($data->zip_code)) {
                $profileUpdateData['zip_code'] = $data->zip_code;
            }
            
            if (!empty($profileUpdateData)) {
                $this->db->table('user_profiles')
                         ->where('user_id', $userId)
                         ->update($profileUpdateData);
            }
        }
        
        return $result;
    }

    /**
     * Delete user
     */
    public function deleteUser($userId)
    {
        // Delete user profile first
        $this->db->table('user_profiles')->where('user_id', $userId)->delete();
        
        // Delete user
        return $this->delete($userId);
    }

    /**
     * Get user statistics
     */
    public function getUserStats($filters = [])
    {
        $builder = $this->builder();
        
        // Apply basic filters
        if (isset($filters['school_id']) && $filters['school_id'] > 0) {
            $builder->where('school_id', $filters['school_id']);
        }
        
        if (isset($filters['role_id']) && $filters['role_id'] > 0) {
            $builder->where('role_id', $filters['role_id']);
        }
        
        $totalUsers = $builder->countAllResults();
        
        // Active users
        $activeUsers = $builder->where('status', 1)->countAllResults();
        
        // Inactive users
        $inactiveUsers = $totalUsers - $activeUsers;
        
        // Users by role
        $roleStats = $this->builder()
                         ->select('role_id, COUNT(*) as count')
                         ->groupBy('role_id')
                         ->get()
                         ->getResultArray();
        
        // Users by school
        $schoolStats = $this->builder()
                           ->select('school_id, COUNT(*) as count')
                           ->where('school_id IS NOT NULL')
                           ->groupBy('school_id')
                           ->get()
                           ->getResultArray();
        
        return [
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'inactive_users' => $inactiveUsers,
            'role_stats' => $roleStats,
            'school_stats' => $schoolStats
        ];
    }

    /**
     * Change user status
     */
    public function changeUserStatus($userId, $status)
    {
        return $this->update($userId, ['status' => $status]);
    }

    /**
     * Reset user password
     */
    public function resetUserPassword($userId, $newPassword)
    {
        return $this->update($userId, ['password' => password_hash($newPassword, PASSWORD_DEFAULT)]);
    }

    /**
     * Get user permissions
     */
    public function getUserPermissions($userId)
    {
        // This would typically involve querying a user_permissions or role_permissions table
        // For now, return empty permissions as placeholder
        return [];
    }

    /**
     * Update user permissions
     */
    public function updateUserPermissions($userId, $permissions)
    {
        // This would typically involve updating a user_permissions table
        // For now, return true as placeholder
        return true;
    }

    /**
     * Search users
     */
    public function searchUsers($query, $limit = 10)
    {
        $builder = $this->builder();
        $builder->select('users.*, user_profiles.first_name, user_profiles.last_name');
        $builder->join('user_profiles', 'user_profiles.user_id = users.id', 'left');
        $builder->groupStart()
                ->like('users.email_id', $query)
                ->orLike('user_profiles.first_name', $query)
                ->orLike('user_profiles.last_name', $query)
                ->groupEnd()
                ->where('users.status', 1)
                ->limit($limit);
        
        return $builder->get()->getResultArray();
    }

    /**
     * Get users by role
     */
    public function getUsersByRole($roleId, $filters = [])
    {
        $builder = $this->builder();
        $builder->where('role_id', $roleId);
        
        if (isset($filters['status']) && $filters['status'] !== '') {
            $builder->where('status', $filters['status']);
        }
        
        if (isset($filters['school_id']) && $filters['school_id'] > 0) {
            $builder->where('school_id', $filters['school_id']);
        }
        
        if (isset($filters['limit']) && $filters['limit'] > 0) {
            $builder->limit($filters['limit']);
        }
        
        return $builder->get()->getResultArray();
    }

    /**
     * Get users by school
     */
    public function getUsersBySchool($schoolId, $filters = [])
    {
        $builder = $this->builder();
        $builder->where('school_id', $schoolId);
        
        if (isset($filters['status']) && $filters['status'] !== '') {
            $builder->where('status', $filters['status']);
        }
        
        if (isset($filters['role_id']) && $filters['role_id'] > 0) {
            $builder->where('role_id', $filters['role_id']);
        }
        
        if (isset($filters['limit']) && $filters['limit'] > 0) {
            $builder->limit($filters['limit']);
        }
        
        return $builder->get()->getResultArray();
    }
}
