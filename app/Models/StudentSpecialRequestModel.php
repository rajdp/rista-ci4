<?php

namespace App\Models;

use App\Models\V1\BaseModel;

class StudentSpecialRequestModel extends BaseModel
{
    protected $table = 't_student_special_request';
    protected $primaryKey = 'request_id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'school_id',
        'student_id',
        'status',
        'request_type',
        'title',
        'description',
        'priority',
        'related_class_id',
        'related_course_id',
        'assigned_to',
        'assigned_at',
        'reviewed_by',
        'reviewed_at',
        'resolution_notes',
        'due_date',
        'completed_at',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'school_id' => 'required|integer',
        'student_id' => 'required|integer',
        'status' => 'required|in_list[pending,in_review,approved,rejected,needs_info,completed,cancelled]',
        'request_type' => 'required|max_length[100]',
        'title' => 'required|max_length[255]',
        'description' => 'required',
        'priority' => 'required|in_list[low,normal,high,urgent]',
    ];

    protected $validationMessages = [
        'school_id' => [
            'required' => 'School ID is required',
        ],
        'student_id' => [
            'required' => 'Student ID is required',
        ],
        'title' => [
            'required' => 'Request title is required',
        ],
    ];

    /**
     * Get requests by student
     */
    public function getStudentRequests($schoolId, $studentId, $filters = [], $limit = 50, $offset = 0)
    {
        $builder = $this->where('school_id', $schoolId)
                       ->where('student_id', $studentId);

        if (isset($filters['status'])) {
            $builder->where('status', $filters['status']);
        }

        if (isset($filters['request_type'])) {
            $builder->where('request_type', $filters['request_type']);
        }

        if (isset($filters['priority'])) {
            $builder->where('priority', $filters['priority']);
        }

        return $builder->orderBy('created_at', 'DESC')
                      ->limit($limit, $offset)
                      ->findAll();
    }

    /**
     * Get pending requests for admin
     */
    public function getPendingRequests($schoolId, $limit = 50, $offset = 0)
    {
        return $this->where('school_id', $schoolId)
                    ->where('status', 'pending')
                    ->orderBy('priority', 'DESC')
                    ->orderBy('created_at', 'ASC')
                    ->limit($limit, $offset)
                    ->findAll();
    }

    /**
     * Get requests by status
     */
    public function getRequestsByStatus($schoolId, $status, $limit = 50, $offset = 0)
    {
        return $this->where('school_id', $schoolId)
                    ->where('status', $status)
                    ->orderBy('created_at', 'DESC')
                    ->limit($limit, $offset)
                    ->findAll();
    }

    /**
     * Get requests assigned to admin
     */
    public function getAssignedRequests($adminId, $schoolId, $status = null)
    {
        $builder = $this->where('school_id', $schoolId)
                       ->where('assigned_to', $adminId);

        if ($status !== null) {
            $builder->where('status', $status);
        }

        return $builder->orderBy('due_date', 'ASC')
                      ->orderBy('priority', 'DESC')
                      ->findAll();
    }

    /**
     * Count requests by status
     */
    public function countByStatus($schoolId, $status = null)
    {
        $builder = $this->where('school_id', $schoolId);

        if ($status !== null) {
            $builder->where('status', $status);
        }

        return $builder->countAllResults();
    }

    /**
     * Count by request type
     */
    public function countByType($schoolId, $requestType = null)
    {
        $builder = $this->where('school_id', $schoolId);

        if ($requestType !== null) {
            $builder->where('request_type', $requestType);
        }

        return $builder->countAllResults();
    }

    /**
     * Update request status
     */
    public function updateStatus($requestId, $status, $reviewedBy, $notes = null)
    {
        $data = [
            'status' => $status,
            'reviewed_by' => $reviewedBy,
            'reviewed_at' => date('Y-m-d H:i:s'),
        ];

        if ($notes !== null) {
            $data['resolution_notes'] = $notes;
        }

        if ($status === 'completed') {
            $data['completed_at'] = date('Y-m-d H:i:s');
        }

        return $this->update($requestId, $data);
    }

    /**
     * Assign request to admin
     */
    public function assignRequest($requestId, $adminId)
    {
        return $this->update($requestId, [
            'assigned_to' => $adminId,
            'assigned_at' => date('Y-m-d H:i:s'),
            'status' => 'in_review',
        ]);
    }
}
