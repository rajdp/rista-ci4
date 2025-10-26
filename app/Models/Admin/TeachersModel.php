<?php

namespace App\Models\Admin;

use CodeIgniter\Model;

class TeachersModel extends Model
{
    protected $table = 'teachers';
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
        'subject_id',
        'qualification',
        'experience_years',
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
     * Get teachers for admin with filters
     */
    public function getAdminTeachers($filters = [])
    {
        $builder = $this->builder();
        
        // Join with schools and subjects tables
        $builder->select('teachers.*, schools.school_name, subjects.subject_name');
        $builder->join('schools', 'schools.id = teachers.school_id', 'left');
        $builder->join('subjects', 'subjects.id = teachers.subject_id', 'left');
        
        // Apply filters
        if (isset($filters['search']) && !empty($filters['search'])) {
            $builder->groupStart()
                    ->like('teachers.first_name', $filters['search'])
                    ->orLike('teachers.last_name', $filters['search'])
                    ->orLike('teachers.email', $filters['search'])
                    ->orLike('schools.school_name', $filters['search'])
                    ->groupEnd();
        }
        
        if (isset($filters['school_id']) && $filters['school_id'] > 0) {
            $builder->where('teachers.school_id', $filters['school_id']);
        }
        
        if (isset($filters['subject_id']) && $filters['subject_id'] > 0) {
            $builder->where('teachers.subject_id', $filters['subject_id']);
        }
        
        if (isset($filters['status']) && $filters['status'] !== '') {
            $builder->where('teachers.status', $filters['status']);
        }
        
        if (isset($filters['gender']) && !empty($filters['gender'])) {
            $builder->where('teachers.gender', $filters['gender']);
        }
        
        if (isset($filters['qualification']) && !empty($filters['qualification'])) {
            $builder->like('teachers.qualification', $filters['qualification']);
        }
        
        if (isset($filters['experience_min']) && $filters['experience_min'] > 0) {
            $builder->where('teachers.experience_years >=', $filters['experience_min']);
        }
        
        if (isset($filters['experience_max']) && $filters['experience_max'] > 0) {
            $builder->where('teachers.experience_years <=', $filters['experience_max']);
        }
        
        if (isset($filters['city']) && !empty($filters['city'])) {
            $builder->like('teachers.city', $filters['city']);
        }
        
        if (isset($filters['state']) && !empty($filters['state'])) {
            $builder->like('teachers.state', $filters['state']);
        }
        
        if (isset($filters['country']) && !empty($filters['country'])) {
            $builder->like('teachers.country', $filters['country']);
        }
        
        // Date range filters
        if (isset($filters['created_from']) && !empty($filters['created_from'])) {
            $builder->where('teachers.created_date >=', $filters['created_from']);
        }
        
        if (isset($filters['created_to']) && !empty($filters['created_to'])) {
            $builder->where('teachers.created_date <=', $filters['created_to']);
        }
        
        // Order by
        $orderBy = $filters['order_by'] ?? 'teachers.created_date';
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
     * Get teacher by ID
     */
    public function getTeacherById($teacherId)
    {
        $builder = $this->builder();
        $builder->select('teachers.*, schools.school_name, subjects.subject_name');
        $builder->join('schools', 'schools.id = teachers.school_id', 'left');
        $builder->join('subjects', 'subjects.id = teachers.subject_id', 'left');
        $builder->where('teachers.id', $teacherId);
        
        return $builder->get()->getRowArray();
    }

    /**
     * Create new teacher
     */
    public function createTeacher($data)
    {
        $teacherData = [
            'first_name' => $data->first_name ?? '',
            'last_name' => $data->last_name ?? '',
            'email' => $data->email ?? '',
            'phone' => $data->phone ?? '',
            'date_of_birth' => $data->date_of_birth ?? null,
            'gender' => $data->gender ?? '',
            'school_id' => $data->school_id ?? 0,
            'subject_id' => $data->subject_id ?? null,
            'qualification' => $data->qualification ?? '',
            'experience_years' => $data->experience_years ?? 0,
            'address' => $data->address ?? '',
            'city' => $data->city ?? '',
            'state' => $data->state ?? '',
            'country' => $data->country ?? '',
            'zip_code' => $data->zip_code ?? '',
            'status' => $data->status ?? 1
        ];
        
        return $this->insert($teacherData);
    }

    /**
     * Update teacher
     */
    public function updateTeacher($teacherId, $data)
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
        if (isset($data->subject_id)) {
            $updateData['subject_id'] = $data->subject_id;
        }
        if (isset($data->qualification)) {
            $updateData['qualification'] = $data->qualification;
        }
        if (isset($data->experience_years)) {
            $updateData['experience_years'] = $data->experience_years;
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
        
        return $this->update($teacherId, $updateData);
    }

    /**
     * Delete teacher
     */
    public function deleteTeacher($teacherId)
    {
        return $this->delete($teacherId);
    }

    /**
     * Get teacher statistics
     */
    public function getTeacherStats($filters = [])
    {
        $builder = $this->builder();
        
        // Apply basic filters
        if (isset($filters['school_id']) && $filters['school_id'] > 0) {
            $builder->where('school_id', $filters['school_id']);
        }
        
        if (isset($filters['subject_id']) && $filters['subject_id'] > 0) {
            $builder->where('subject_id', $filters['subject_id']);
        }
        
        $totalTeachers = $builder->countAllResults();
        
        // Active teachers
        $activeTeachers = $builder->where('status', 1)->countAllResults();
        
        // Inactive teachers
        $inactiveTeachers = $totalTeachers - $activeTeachers;
        
        // Teachers by gender
        $genderStats = $this->builder()
                           ->select('gender, COUNT(*) as count')
                           ->groupBy('gender')
                           ->get()
                           ->getResultArray();
        
        // Teachers by subject
        $subjectStats = $this->builder()
                            ->select('subject_id, COUNT(*) as count')
                            ->where('subject_id IS NOT NULL')
                            ->groupBy('subject_id')
                            ->get()
                            ->getResultArray();
        
        // Teachers by experience
        $experienceStats = $this->builder()
                               ->select('
                                   CASE 
                                       WHEN experience_years < 2 THEN "0-2 years"
                                       WHEN experience_years < 5 THEN "2-5 years"
                                       WHEN experience_years < 10 THEN "5-10 years"
                                       ELSE "10+ years"
                                   END as experience_range,
                                   COUNT(*) as count
                               ')
                               ->groupBy('experience_range')
                               ->get()
                               ->getResultArray();
        
        return [
            'total_teachers' => $totalTeachers,
            'active_teachers' => $activeTeachers,
            'inactive_teachers' => $inactiveTeachers,
            'gender_stats' => $genderStats,
            'subject_stats' => $subjectStats,
            'experience_stats' => $experienceStats
        ];
    }

    /**
     * Assign teacher to class
     */
    public function assignToClass($teacherId, $classId)
    {
        // This would typically involve updating a teacher_classes table
        // For now, we'll just return true as a placeholder
        return true;
    }

    /**
     * Remove teacher from class
     */
    public function removeFromClass($teacherId, $classId)
    {
        // This would typically involve removing from a teacher_classes table
        // For now, we'll just return true as a placeholder
        return true;
    }

    /**
     * Search teachers
     */
    public function searchTeachers($query, $limit = 10)
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
     * Get teachers by school
     */
    public function getTeachersBySchool($schoolId, $filters = [])
    {
        $builder = $this->builder();
        $builder->where('school_id', $schoolId);
        
        if (isset($filters['status']) && $filters['status'] !== '') {
            $builder->where('status', $filters['status']);
        }
        
        if (isset($filters['subject_id']) && $filters['subject_id'] > 0) {
            $builder->where('subject_id', $filters['subject_id']);
        }
        
        if (isset($filters['limit']) && $filters['limit'] > 0) {
            $builder->limit($filters['limit']);
        }
        
        return $builder->get()->getResultArray();
    }

    /**
     * Get teachers by subject
     */
    public function getTeachersBySubject($subjectId, $filters = [])
    {
        $builder = $this->builder();
        $builder->where('subject_id', $subjectId);
        
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
}
