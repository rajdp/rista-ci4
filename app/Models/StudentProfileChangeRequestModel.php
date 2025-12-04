<?php

namespace App\Models;

use App\Models\V1\BaseModel;

class StudentProfileChangeRequestModel extends BaseModel
{
    protected $table = 't_student_profile_change_request';
    protected $primaryKey = 'request_id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'school_id',
        'student_id',
        'status',
        'request_type',
        'changes_json',
        'reason',
        'reviewed_by',
        'reviewed_at',
        'admin_notes',
        'rejection_reason',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'school_id' => 'required|integer',
        'student_id' => 'required|integer',
        'status' => 'required|in_list[pending,in_review,approved,rejected,needs_info,cancelled]',
        'request_type' => 'required|in_list[personal_info,contact_info,address,emergency_contact,other]',
        'changes_json' => 'required',
    ];

    protected $validationMessages = [
        'school_id' => [
            'required' => 'School ID is required',
        ],
        'student_id' => [
            'required' => 'Student ID is required',
        ],
    ];

    /**
     * Get requests by student
     */
    public function getStudentRequests($schoolId, $studentId, $status = null, $limit = 50, $offset = 0)
    {
        $builder = $this->where('school_id', $schoolId)
                       ->where('student_id', $studentId);

        if ($status !== null) {
            $builder->where('status', $status);
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
     * Update request status
     */
    public function updateStatus($requestId, $status, $reviewedBy, $notes = null, $rejectionReason = null)
    {
        $data = [
            'status' => $status,
            'reviewed_by' => $reviewedBy,
            'reviewed_at' => date('Y-m-d H:i:s'),
        ];

        if ($notes !== null) {
            $data['admin_notes'] = $notes;
        }

        if ($rejectionReason !== null) {
            $data['rejection_reason'] = $rejectionReason;
        }

        return $this->update($requestId, $data);
    }
}
