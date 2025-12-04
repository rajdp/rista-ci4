<?php

namespace App\Models;

use App\Models\V1\BaseModel;

class ReportCardModel extends BaseModel
{
    protected $table = 't_report_card';
    protected $primaryKey = 'rc_id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'school_id',
        'student_id',
        'class_id',
        'term',
        'academic_year',
        'template_id',
        'template_version',
        'status',
        'issued_at',
        'issued_by',
        'current_version',
        'created_by',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'school_id' => 'required|integer',
        'student_id' => 'required|integer',
        'term' => 'required|max_length[60]',
        'academic_year' => 'required|max_length[20]',
        'template_id' => 'required|integer',
        'template_version' => 'required|integer',
        'created_by' => 'required|integer',
    ];

    protected $validationMessages = [
        'school_id' => [
            'required' => 'School ID is required',
        ],
        'student_id' => [
            'required' => 'Student ID is required',
        ],
        'term' => [
            'required' => 'Term is required',
        ],
        'academic_year' => [
            'required' => 'Academic year is required',
        ],
    ];

    /**
     * Get report cards for a student
     */
    public function getStudentReportCards($studentId, $schoolId, $statusFilter = null)
    {
        $builder = $this->where('student_id', $studentId)
                        ->where('school_id', $schoolId);

        if ($statusFilter) {
            $builder->where('status', $statusFilter);
        } else {
            // By default, exclude revoked cards for students
            $builder->whereNotIn('status', ['revoked']);
        }

        return $builder->orderBy('academic_year', 'DESC')
                       ->orderBy('term', 'DESC')
                       ->findAll();
    }

    /**
     * Get report cards for a class
     */
    public function getClassReportCards($classId, $schoolId, $term = null, $academicYear = null)
    {
        $builder = $this->where('class_id', $classId)
                        ->where('school_id', $schoolId);

        if ($term) {
            $builder->where('term', $term);
        }

        if ($academicYear) {
            $builder->where('academic_year', $academicYear);
        }

        return $builder->orderBy('student_id', 'ASC')->findAll();
    }

    /**
     * Search report cards with filters
     */
    public function searchReportCards($filters)
    {
        $builder = $this->where('school_id', $filters['school_id']);

        if (!empty($filters['student_id'])) {
            $builder->where('student_id', $filters['student_id']);
        }

        if (!empty($filters['class_id'])) {
            $builder->where('class_id', $filters['class_id']);
        }

        if (!empty($filters['term'])) {
            $builder->where('term', $filters['term']);
        }

        if (!empty($filters['academic_year'])) {
            $builder->where('academic_year', $filters['academic_year']);
        }

        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $builder->whereIn('status', $filters['status']);
            } else {
                $builder->where('status', $filters['status']);
            }
        }

        if (!empty($filters['issued_from'])) {
            $builder->where('issued_at >=', $filters['issued_from']);
        }

        if (!empty($filters['issued_to'])) {
            $builder->where('issued_at <=', $filters['issued_to']);
        }

        $limit = $filters['limit'] ?? 50;
        $offset = $filters['offset'] ?? 0;

        return [
            'data' => $builder->limit($limit, $offset)
                             ->orderBy('created_at', 'DESC')
                             ->findAll(),
            'total' => $builder->countAllResults(false), // false to keep builder state
        ];
    }

    /**
     * Update report card status
     */
    public function updateStatus($rcId, $schoolId, $status, $userId)
    {
        $data = ['status' => $status];

        if ($status === 'issued') {
            $data['issued_at'] = date('Y-m-d H:i:s');
            $data['issued_by'] = $userId;
        }

        return $this->where('rc_id', $rcId)
                    ->where('school_id', $schoolId)
                    ->set($data)
                    ->update();
    }

    /**
     * Check if report card exists for student/term/year
     */
    public function checkDuplicate($studentId, $schoolId, $term, $academicYear)
    {
        return $this->where('student_id', $studentId)
                    ->where('school_id', $schoolId)
                    ->where('term', $term)
                    ->where('academic_year', $academicYear)
                    ->first();
    }
}
