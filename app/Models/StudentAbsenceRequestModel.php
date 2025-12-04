<?php

namespace App\Models;

use App\Models\V1\BaseModel;

class StudentAbsenceRequestModel extends BaseModel
{
    protected $table = 't_student_absence_request';
    protected $primaryKey = 'absence_id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'school_id',
        'student_id',
        'status',
        'start_date',
        'end_date',
        'absence_type',
        'reason',
        'is_advance_notice',
        'class_ids',
        'has_documentation',
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
        'status' => 'required|in_list[pending,approved,rejected,cancelled]',
        'start_date' => 'required|valid_date',
        'end_date' => 'required|valid_date',
        'absence_type' => 'required|in_list[illness,family_emergency,medical_appointment,religious,school_activity,other]',
        'reason' => 'required',
    ];

    protected $validationMessages = [
        'school_id' => [
            'required' => 'School ID is required',
        ],
        'student_id' => [
            'required' => 'Student ID is required',
        ],
        'start_date' => [
            'required' => 'Start date is required',
        ],
        'end_date' => [
            'required' => 'End date is required',
        ],
    ];

    /**
     * Get absences by student
     */
    public function getStudentAbsences($schoolId, $studentId, $status = null, $limit = 50, $offset = 0)
    {
        $builder = $this->where('school_id', $schoolId)
                       ->where('student_id', $studentId);

        if ($status !== null) {
            $builder->where('status', $status);
        }

        return $builder->orderBy('start_date', 'DESC')
                      ->limit($limit, $offset)
                      ->findAll();
    }

    /**
     * Get pending absences for admin
     */
    public function getPendingAbsences($schoolId, $limit = 50, $offset = 0)
    {
        return $this->where('school_id', $schoolId)
                    ->where('status', 'pending')
                    ->orderBy('created_at', 'ASC')
                    ->limit($limit, $offset)
                    ->findAll();
    }

    /**
     * Get absences by date range
     */
    public function getAbsencesByDateRange($schoolId, $startDate, $endDate, $status = 'approved')
    {
        return $this->where('school_id', $schoolId)
                    ->where('status', $status)
                    ->groupStart()
                        ->where('start_date <=', $endDate)
                        ->where('end_date >=', $startDate)
                    ->groupEnd()
                    ->findAll();
    }

    /**
     * Get absences for specific class
     */
    public function getAbsencesForClass($classId, $startDate, $endDate)
    {
        return $this->where('status', 'approved')
                    ->groupStart()
                        ->where('class_ids', null)
                        ->orLike('class_ids', $classId)
                    ->groupEnd()
                    ->where('start_date <=', $endDate)
                    ->where('end_date >=', $startDate)
                    ->orderBy('start_date', 'ASC')
                    ->findAll();
    }

    /**
     * Count absences by status
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
     * Update absence status
     */
    public function updateStatus($absenceId, $status, $reviewedBy, $notes = null, $rejectionReason = null)
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

        return $this->update($absenceId, $data);
    }
}
