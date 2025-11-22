<?php

namespace App\Models;

use CodeIgniter\Model;

class StudentCourseModel extends Model
{
    protected $table = 'student_courses';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'student_id',
        'course_id',
        'school_id',
        'registration_id',
        'enrollment_date',
        'completion_date',
        'status',
        'fee_amount',
        'student_fee_plan_id',
        'added_by',
        'notes',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'student_id' => 'required|integer',
        'course_id' => 'required|integer',
        'school_id' => 'required|integer',
    ];

    /**
     * Get all courses for a student
     */
    public function getStudentCourses(int $studentId, int $schoolId, ?string $status = null)
    {
        try {
            $db = \Config\Database::connect();
            
            // Check if tables exist before joining
            $tables = $db->listTables();
            $hasFeePlans = in_array('fee_plans', $tables);
            $hasStudentFeePlans = in_array('student_fee_plans', $tables);
            
            // Build select statement
            $selectFields = '
                student_courses.*,
                tbl_course.course_id,
                tbl_course.course_name,
                tbl_course.description as course_description';
            
            if ($hasFeePlans && $hasStudentFeePlans) {
                $selectFields .= ', COALESCE(fee_plans.name, "") as fee_plan_name';
            } else {
                $selectFields .= ', "" as fee_plan_name';
            }
            
            $builder = $this->select($selectFields);
            
            // Join course table
            $builder->join('tbl_course', 'tbl_course.course_id = student_courses.course_id', 'left');
            
            // Only add entity_id filter if column exists
            if ($db->fieldExists('entity_id', 'tbl_course')) {
                $builder->where('tbl_course.entity_id', $schoolId);
            }
            
            // Join fee plan tables if they exist
            if ($hasStudentFeePlans) {
                $builder->join('student_fee_plans sfp', 'sfp.id = student_courses.student_fee_plan_id', 'left');
                if ($hasFeePlans) {
                    $builder->join('fee_plans', 'fee_plans.id = sfp.fee_plan_id', 'left');
                }
            }
            
            $builder->where([
                'student_courses.student_id' => $studentId,
                'student_courses.school_id' => $schoolId
            ]);

            if ($status) {
                $builder->where('student_courses.status', $status);
            }

            return $builder->orderBy('student_courses.enrollment_date', 'DESC')
                ->findAll();
        } catch (\Throwable $e) {
            log_message('error', 'StudentCourseModel::getStudentCourses - ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            // Return empty array on error instead of throwing
            return [];
        }
    }

    /**
     * Enroll student in a course
     */
    public function enrollStudent(array $data)
    {
        // Check if already enrolled
        $existing = $this->where([
            'student_id' => $data['student_id'],
            'course_id' => $data['course_id'],
            'school_id' => $data['school_id']
        ])->first();

        if ($existing && $existing['status'] === 'active') {
            return [
                'success' => false,
                'message' => 'Student is already enrolled in this course'
            ];
        }

        // If previously dropped/suspended, reactivate
        if ($existing) {
            $this->update($existing['id'], [
                'status' => 'active',
                'enrollment_date' => $data['enrollment_date'] ?? date('Y-m-d'),
                'fee_amount' => $data['fee_amount'] ?? $existing['fee_amount'],
                'student_fee_plan_id' => $data['student_fee_plan_id'] ?? $existing['student_fee_plan_id'],
                'notes' => $data['notes'] ?? $existing['notes']
            ]);

            return [
                'success' => true,
                'student_course_id' => $existing['id'],
                'message' => 'Student re-enrolled in course'
            ];
        }

        // New enrollment
        $enrollmentData = [
            'student_id' => $data['student_id'],
            'course_id' => $data['course_id'],
            'school_id' => $data['school_id'],
            'registration_id' => $data['registration_id'] ?? null,
            'enrollment_date' => $data['enrollment_date'] ?? date('Y-m-d'),
            'status' => 'active',
            'fee_amount' => $data['fee_amount'] ?? null,
            'student_fee_plan_id' => $data['student_fee_plan_id'] ?? null,
            'added_by' => $data['added_by'] ?? null,
            'notes' => $data['notes'] ?? null
        ];

        $id = $this->insert($enrollmentData);

        return [
            'success' => true,
            'student_course_id' => $id,
            'message' => 'Student enrolled successfully'
        ];
    }

    /**
     * Update course status
     */
    public function updateCourseStatus(int $studentCourseId, string $status, array $additionalData = [])
    {
        $allowedStatuses = ['active', 'completed', 'dropped', 'suspended'];

        if (!in_array($status, $allowedStatuses)) {
            return false;
        }

        $updateData = ['status' => $status];

        if ($status === 'completed' && empty($additionalData['completion_date'])) {
            $updateData['completion_date'] = date('Y-m-d');
        }

        $updateData = array_merge($updateData, $additionalData);

        return $this->update($studentCourseId, $updateData);
    }

    /**
     * Get active course count for student
     */
    public function getActiveCourseCount(int $studentId, int $schoolId): int
    {
        return $this->where([
            'student_id' => $studentId,
            'school_id' => $schoolId,
            'status' => 'active'
        ])->countAllResults();
    }

    /**
     * Check if student is enrolled in a specific course
     */
    public function isEnrolled(int $studentId, int $courseId, int $schoolId): bool
    {
        $result = $this->where([
            'student_id' => $studentId,
            'course_id' => $courseId,
            'school_id' => $schoolId,
            'status' => 'active'
        ])->first();

        return !empty($result);
    }
}
