<?php

namespace App\Models\Admin;

use CodeIgniter\Model;

class StudentsModel extends Model
{
    protected $table = 'students';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'date_of_birth',
        'gender',
        'school_id',
        'grade_id',
        'parent_name',
        'parent_email',
        'parent_phone',
        'address',
        'city',
        'state',
        'country',
        'zip_code',
        'status',
        'created_date',
        'modified_date'
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_date';
    protected $updatedField = 'modified_date';

    protected $validationRules = [
        'first_name' => 'required|max_length[100]',
        'last_name' => 'required|max_length[100]',
        'email' => 'required|valid_email|max_length[255]',
        'school_id' => 'required|integer',
        'status' => 'required|in_list[0,1]'
    ];

    protected $validationMessages = [
        'first_name' => [
            'required' => 'First name is required',
            'max_length' => 'First name cannot exceed 100 characters'
        ],
        'last_name' => [
            'required' => 'Last name is required',
            'max_length' => 'Last name cannot exceed 100 characters'
        ],
        'email' => [
            'required' => 'Email is required',
            'valid_email' => 'Please provide a valid email address',
            'max_length' => 'Email cannot exceed 255 characters'
        ],
        'school_id' => [
            'required' => 'School ID is required',
            'integer' => 'School ID must be an integer'
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
     * Get students for admin with filters
     */
    public function getAdminStudents($filters = [])
    {
        $builder = $this->builder();
        
        // Join with schools table for school information
        $builder->select('students.*, schools.school_name, grades.grade_name');
        $builder->join('schools', 'schools.id = students.school_id', 'left');
        $builder->join('grades', 'grades.id = students.grade_id', 'left');
        
        // Apply filters
        if (isset($filters['search']) && !empty($filters['search'])) {
            $builder->groupStart()
                    ->like('students.first_name', $filters['search'])
                    ->orLike('students.last_name', $filters['search'])
                    ->orLike('students.email', $filters['search'])
                    ->orLike('schools.school_name', $filters['search'])
                    ->groupEnd();
        }
        
        if (isset($filters['school_id']) && $filters['school_id'] > 0) {
            $builder->where('students.school_id', $filters['school_id']);
        }
        
        if (isset($filters['grade_id']) && $filters['grade_id'] > 0) {
            $builder->where('students.grade_id', $filters['grade_id']);
        }
        
        if (isset($filters['status']) && $filters['status'] !== '') {
            $builder->where('students.status', $filters['status']);
        }
        
        if (isset($filters['gender']) && !empty($filters['gender'])) {
            $builder->where('students.gender', $filters['gender']);
        }
        
        if (isset($filters['city']) && !empty($filters['city'])) {
            $builder->like('students.city', $filters['city']);
        }
        
        if (isset($filters['state']) && !empty($filters['state'])) {
            $builder->like('students.state', $filters['state']);
        }
        
        if (isset($filters['country']) && !empty($filters['country'])) {
            $builder->like('students.country', $filters['country']);
        }
        
        // Date range filters
        if (isset($filters['created_from']) && !empty($filters['created_from'])) {
            $builder->where('students.created_date >=', $filters['created_from']);
        }
        
        if (isset($filters['created_to']) && !empty($filters['created_to'])) {
            $builder->where('students.created_date <=', $filters['created_to']);
        }
        
        // Order by
        $orderBy = $filters['order_by'] ?? 'students.created_date';
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
     * Get student by ID
     */
    public function getStudentById($studentId)
    {
        $builder = $this->builder();
        $builder->select('students.*, schools.school_name, grades.grade_name');
        $builder->join('schools', 'schools.id = students.school_id', 'left');
        $builder->join('grades', 'grades.id = students.grade_id', 'left');
        $builder->where('students.id', $studentId);
        
        return $builder->get()->getRowArray();
    }

    /**
     * Create new student
     */
    public function createStudent($data)
    {
        $studentData = [
            'first_name' => $data->first_name ?? '',
            'last_name' => $data->last_name ?? '',
            'email' => $data->email ?? '',
            'phone' => $data->phone ?? '',
            'date_of_birth' => $data->date_of_birth ?? null,
            'gender' => $data->gender ?? '',
            'school_id' => $data->school_id ?? 0,
            'grade_id' => $data->grade_id ?? null,
            'parent_name' => $data->parent_name ?? '',
            'parent_email' => $data->parent_email ?? '',
            'parent_phone' => $data->parent_phone ?? '',
            'address' => $data->address ?? '',
            'city' => $data->city ?? '',
            'state' => $data->state ?? '',
            'country' => $data->country ?? '',
            'zip_code' => $data->zip_code ?? '',
            'status' => $data->status ?? 1
        ];
        
        return $this->insert($studentData);
    }

    /**
     * Update student
     */
    public function updateStudent($studentId, $data)
    {
        $updateData = [];
        
        if (isset($data->first_name)) {
            $updateData['first_name'] = $data->first_name;
        }
        if (isset($data->last_name)) {
            $updateData['last_name'] = $data->last_name;
        }
        if (isset($data->email)) {
            $updateData['email'] = $data->email;
        }
        if (isset($data->phone)) {
            $updateData['phone'] = $data->phone;
        }
        if (isset($data->date_of_birth)) {
            $updateData['date_of_birth'] = $data->date_of_birth;
        }
        if (isset($data->gender)) {
            $updateData['gender'] = $data->gender;
        }
        if (isset($data->school_id)) {
            $updateData['school_id'] = $data->school_id;
        }
        if (isset($data->grade_id)) {
            $updateData['grade_id'] = $data->grade_id;
        }
        if (isset($data->parent_name)) {
            $updateData['parent_name'] = $data->parent_name;
        }
        if (isset($data->parent_email)) {
            $updateData['parent_email'] = $data->parent_email;
        }
        if (isset($data->parent_phone)) {
            $updateData['parent_phone'] = $data->parent_phone;
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
        if (isset($data->status)) {
            $updateData['status'] = $data->status;
        }
        
        if (empty($updateData)) {
            return false;
        }
        
        return $this->update($studentId, $updateData);
    }

    /**
     * Delete student
     */
    public function deleteStudent($studentId)
    {
        return $this->delete($studentId);
    }

    /**
     * Get student statistics
     */
    public function getStudentStats($filters = [])
    {
        $builder = $this->builder();
        
        // Apply basic filters
        if (isset($filters['school_id']) && $filters['school_id'] > 0) {
            $builder->where('school_id', $filters['school_id']);
        }
        
        if (isset($filters['grade_id']) && $filters['grade_id'] > 0) {
            $builder->where('grade_id', $filters['grade_id']);
        }
        
        $totalStudents = $builder->countAllResults();
        
        // Active students
        $activeStudents = $builder->where('status', 1)->countAllResults();
        
        // Inactive students
        $inactiveStudents = $totalStudents - $activeStudents;
        
        // Students by gender
        $genderStats = $this->builder()
                           ->select('gender, COUNT(*) as count')
                           ->groupBy('gender')
                           ->get()
                           ->getResultArray();
        
        // Students by grade
        $gradeStats = $this->builder()
                          ->select('grade_id, COUNT(*) as count')
                          ->where('grade_id IS NOT NULL')
                          ->groupBy('grade_id')
                          ->get()
                          ->getResultArray();
        
        return [
            'total_students' => $totalStudents,
            'active_students' => $activeStudents,
            'inactive_students' => $inactiveStudents,
            'gender_stats' => $genderStats,
            'grade_stats' => $gradeStats
        ];
    }

    /**
     * Bulk update students
     */
    public function bulkUpdateStudents($studentIds, $updateData)
    {
        if (empty($studentIds) || empty($updateData)) {
            return false;
        }
        
        $builder = $this->builder();
        $builder->whereIn('id', $studentIds);
        
        return $builder->update($updateData);
    }

    /**
     * Search students
     */
    public function searchStudents($query, $limit = 10)
    {
        return $this->builder()
                    ->groupStart()
                    ->like('first_name', $query)
                    ->orLike('last_name', $query)
                    ->orLike('email', $query)
                    ->groupEnd()
                    ->where('status', 1)
                    ->limit($limit)
                    ->get()
                    ->getResultArray();
    }

    /**
     * Get students by school
     */
    public function getStudentsBySchool($schoolId, $filters = [])
    {
        $builder = $this->builder();
        $builder->where('school_id', $schoolId);
        
        if (isset($filters['status']) && $filters['status'] !== '') {
            $builder->where('status', $filters['status']);
        }
        
        if (isset($filters['grade_id']) && $filters['grade_id'] > 0) {
            $builder->where('grade_id', $filters['grade_id']);
        }
        
        if (isset($filters['limit']) && $filters['limit'] > 0) {
            $builder->limit($filters['limit']);
        }
        
        return $builder->get()->getResultArray();
    }
}
