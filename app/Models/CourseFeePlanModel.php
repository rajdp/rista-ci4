<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * CourseFeePlanModel
 *
 * Provides fee information for courses by querying the tbl_course table.
 * This model serves as an adapter to maintain compatibility with the
 * CourseEnrollmentService while using the existing tbl_course fee columns.
 */
class CourseFeePlanModel extends Model
{
    protected $table = 'tbl_course';
    protected $primaryKey = 'course_id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'course_name',
        'fee_amount',
        'fee_term',
        'status'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_date';
    protected $updatedField = 'modified_date';

    /**
     * Get fee information for a specific course
     *
     * @param int $courseId
     * @param int $schoolId Filter by entity_id (school_id)
     * @return array|null
     */
    public function getFeeForCourse(int $courseId, int $schoolId)
    {
        $course = $this->select('course_id, course_name, fee_amount, fee_term')
            ->where('course_id', $courseId)
            ->where('entity_id', $schoolId)
            ->where('status', 'A') // Only approved courses
            ->first();

        if (!$course) {
            return null;
        }

        // Convert to expected format
        // fee_term: 1 = one-time, 2 = recurring monthly
        $billingCycleDays = null;
        if (isset($course['fee_term']) && $course['fee_term'] == 2) {
            $billingCycleDays = 30; // Monthly
        }

        return [
            'course_id' => $course['course_id'],
            'course_name' => $course['course_name'],
            'default_amount' => $course['fee_amount'] ? (float) $course['fee_amount'] : null,
            'billing_cycle_days' => $billingCycleDays,
            'fee_term' => $course['fee_term']
        ];
    }

    /**
     * Get all courses with their fee information for a school
     *
     * @param int $schoolId
     * @param array $filters
     * @return array
     */
    public function getCoursesWithFees(int $schoolId, array $filters = [])
    {
        $builder = $this->db->table($this->table)
            ->select('course_id, course_name, fee_amount, fee_term, status')
            ->where('entity_id', $schoolId)
            ->where('status', 'A'); // Only approved courses

        if (!empty($filters['course_id'])) {
            $builder->where('course_id', $filters['course_id']);
        }

        $courses = $builder->get()->getResultArray();

        // Convert to expected format
        return array_map(function($course) {
            // fee_term: 1 = one-time, 2 = recurring monthly
            $billingCycleDays = null;
            if (isset($course['fee_term']) && $course['fee_term'] == 2) {
                $billingCycleDays = 30; // Monthly
            }

            return [
                'course_id' => $course['course_id'],
                'course_name' => $course['course_name'],
                'default_amount' => $course['fee_amount'] ? (float) $course['fee_amount'] : null,
                'billing_cycle_days' => $billingCycleDays,
                'fee_term' => $course['fee_term']
            ];
        }, $courses);
    }

    /**
     * Calculate fee amount for a course
     * Returns the fee amount from tbl_course.fee_amount column
     *
     * @param int $courseId
     * @param int $schoolId Filter by entity_id
     * @return float|null
     */
    public function calculateFeeAmount(int $courseId, int $schoolId): ?float
    {
        $courseFee = $this->getFeeForCourse($courseId, $schoolId);

        if (!$courseFee || !isset($courseFee['default_amount'])) {
            return null;
        }

        return (float) $courseFee['default_amount'];
    }

    /**
     * Set or update course fee
     * Updates tbl_course.fee_amount and fee_term columns
     *
     * @param int $courseId
     * @param int $schoolId Filter by entity_id
     * @param array $feeData
     * @return bool
     */
    public function setCourseFee(int $courseId, int $schoolId, array $feeData)
    {
        $feeAmount = $feeData['default_amount'] ?? $feeData['amount'] ?? null;

        if ($feeAmount === null) {
            return false;
        }

        // Determine fee_term from billing_cycle_days
        // null or 0 = one-time (1), 30 = monthly (2)
        $feeTerm = 1; // Default to one-time
        if (isset($feeData['billing_cycle_days']) && $feeData['billing_cycle_days'] == 30) {
            $feeTerm = 2; // Monthly recurring
        }

        return $this->where('course_id', $courseId)
            ->where('entity_id', $schoolId)
            ->set([
                'fee_amount' => (float)$feeAmount,
                'fee_term' => $feeTerm
            ])
            ->update();
    }

    /**
     * Get linked classes for a course
     * Uses the course_class_mapping table
     *
     * @param int $courseId
     * @param int $schoolId
     * @return array
     */
    public function getLinkedClasses(int $courseId, int $schoolId): array
    {
        return $this->db->table('course_class_mapping')
            ->select('course_class_mapping.*, class.class_name')
            ->join('class', 'class.class_id = course_class_mapping.class_id', 'left')
            ->where([
                'course_class_mapping.course_id' => $courseId,
                'course_class_mapping.school_id' => $schoolId,
                'course_class_mapping.is_active' => 1
            ])
            ->get()
            ->getResultArray();
    }
}
