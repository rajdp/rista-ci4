<?php

namespace App\Models\Admin;

use CodeIgniter\Model;

class SchoolModel extends Model
{
    protected $table = 'schools';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'school_name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'zip_code',
        'website',
        'status',
        'created_date',
        'modified_date'
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_date';
    protected $updatedField = 'modified_date';

    protected $validationRules = [
        'school_name' => 'required|max_length[255]',
        'email' => 'required|valid_email|max_length[255]',
        'phone' => 'required|max_length[20]',
        'address' => 'required|max_length[500]',
        'status' => 'required|in_list[0,1]'
    ];

    protected $validationMessages = [
        'school_name' => [
            'required' => 'School name is required',
            'max_length' => 'School name cannot exceed 255 characters'
        ],
        'email' => [
            'required' => 'Email is required',
            'valid_email' => 'Please provide a valid email address',
            'max_length' => 'Email cannot exceed 255 characters'
        ],
        'phone' => [
            'required' => 'Phone number is required',
            'max_length' => 'Phone number cannot exceed 20 characters'
        ],
        'address' => [
            'required' => 'Address is required',
            'max_length' => 'Address cannot exceed 500 characters'
        ],
        'status' => [
            'required' => 'Status is required',
            'in_list' => 'Status must be either 0 or 1'
        ]
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    protected $beforeInsert = [];
    protected $afterInsert = [];
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    /**
     * Get schools for admin with filters
     */
    public function getAdminSchools($filters = [])
    {
        $builder = $this->builder();
        
        // Apply filters
        if (isset($filters['search']) && !empty($filters['search'])) {
            $builder->groupStart()
                    ->like('school_name', $filters['search'])
                    ->orLike('email', $filters['search'])
                    ->orLike('phone', $filters['search'])
                    ->groupEnd();
        }
        
        if (isset($filters['status']) && $filters['status'] !== '') {
            $builder->where('status', $filters['status']);
        }
        
        if (isset($filters['city']) && !empty($filters['city'])) {
            $builder->like('city', $filters['city']);
        }
        
        if (isset($filters['state']) && !empty($filters['state'])) {
            $builder->like('state', $filters['state']);
        }
        
        if (isset($filters['country']) && !empty($filters['country'])) {
            $builder->like('country', $filters['country']);
        }
        
        // Order by
        $orderBy = $filters['order_by'] ?? 'created_date';
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
     * Get school by ID
     */
    public function getSchoolById($schoolId)
    {
        return $this->where('id', $schoolId)->first();
    }

    /**
     * Create new school
     */
    public function createSchool($data)
    {
        $schoolData = [
            'school_name' => $data->school_name ?? '',
            'email' => $data->email ?? '',
            'phone' => $data->phone ?? '',
            'address' => $data->address ?? '',
            'city' => $data->city ?? '',
            'state' => $data->state ?? '',
            'country' => $data->country ?? '',
            'zip_code' => $data->zip_code ?? '',
            'website' => $data->website ?? '',
            'status' => $data->status ?? 1
        ];
        
        return $this->insert($schoolData);
    }

    /**
     * Update school
     */
    public function updateSchool($schoolId, $data)
    {
        $updateData = [];
        
        if (isset($data->school_name)) {
            $updateData['school_name'] = $data->school_name;
        }
        if (isset($data->email)) {
            $updateData['email'] = $data->email;
        }
        if (isset($data->phone)) {
            $updateData['phone'] = $data->phone;
        }
        if (isset($data->address)) {
            $updateData['address'] = $data->address;
        }
        if (isset($data->city)) {
            $updateData['city'] = $data->city;
        }
        if (isset($data->state)) {
            $updateData['state'] = $data->state;
        }
        if (isset($data->country)) {
            $updateData['country'] = $data->country;
        }
        if (isset($data->zip_code)) {
            $updateData['zip_code'] = $data->zip_code;
        }
        if (isset($data->website)) {
            $updateData['website'] = $data->website;
        }
        if (isset($data->status)) {
            $updateData['status'] = $data->status;
        }
        
        if (empty($updateData)) {
            return false;
        }
        
        return $this->update($schoolId, $updateData);
    }

    /**
     * Delete school
     */
    public function deleteSchool($schoolId)
    {
        return $this->delete($schoolId);
    }

    /**
     * Add admin to school
     */
    public function addAdminToSchool($schoolId, $userId, $roleId)
    {
        // This would typically involve updating a user_school_roles table
        // For now, we'll just return true as a placeholder
        return true;
    }

    /**
     * Get school statistics
     */
    public function getSchoolStats($schoolId)
    {
        // This would typically involve counting students, teachers, classes, etc.
        // For now, return empty stats
        return [
            'total_students' => 0,
            'total_teachers' => 0,
            'total_classes' => 0,
            'active_classes' => 0
        ];
    }

    /**
     * Search schools
     */
    public function searchSchools($query, $limit = 10)
    {
        return $this->builder()
                    ->groupStart()
                    ->like('school_name', $query)
                    ->orLike('email', $query)
                    ->orLike('phone', $query)
                    ->groupEnd()
                    ->where('status', 1)
                    ->limit($limit)
                    ->get()
                    ->getResultArray();
    }
}
