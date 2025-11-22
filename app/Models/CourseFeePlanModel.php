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
        'billing_cycle_days',
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
        try {
            $builder = $this->select('course_id, course_name, fee_amount, fee_term, billing_cycle_days')
                ->where('course_id', $courseId)
                ->where('entity_id', $schoolId)
                ->where('status', 'A'); // Only approved courses
            
            $result = $builder->get();
            
            // Check if query failed
            if ($result === false) {
                log_message('error', 'CourseFeePlanModel::getFeeForCourse - Query failed for course_id: ' . $courseId . ', school_id: ' . $schoolId);
                return null;
            }
            
            $course = $result->getRowArray();
        } catch (\Exception $e) {
            log_message('error', 'CourseFeePlanModel::getFeeForCourse - Exception: ' . $e->getMessage());
            return null;
        } catch (\Throwable $e) {
            log_message('error', 'CourseFeePlanModel::getFeeForCourse - Throwable: ' . $e->getMessage());
            return null;
        }

        if (!$course) {
            return null;
        }

        // Convert to expected format
        // fee_term: 1 = one-time, 2 = recurring
        // Get billing_cycle_days from tbl_course
        $billingCycleDays = null;
        if (isset($course['billing_cycle_days']) && $course['billing_cycle_days'] !== null) {
            $billingCycleDays = (int)$course['billing_cycle_days'];
        } elseif (isset($course['fee_term']) && $course['fee_term'] == 2) {
            // Default to monthly if recurring but no billing_cycle_days set
            $billingCycleDays = 30;
        }
        
        // Debug logging
        log_message('debug', sprintf(
            'CourseFeePlanModel::getFeeForCourse - course_id=%d, school_id=%d, fee_term=%s, billing_cycle_days=%s',
            $courseId,
            $schoolId,
            $course['fee_term'] ?? 'null',
            $billingCycleDays !== null ? (string)$billingCycleDays : 'null'
        ));

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
            ->select('course_id, course_name, fee_amount, fee_term, billing_cycle_days, status')
            ->where('entity_id', $schoolId)
            ->where('status', 'A'); // Only approved courses

        if (!empty($filters['course_id'])) {
            $builder->where('course_id', $filters['course_id']);
        }

        // Filter by courses that have fee configuration (fee_amount is not null)
        // This effectively filters by "is_active" fee configurations
        if (isset($filters['is_active']) && $filters['is_active'] !== null) {
            if ($filters['is_active']) {
                // Only show courses with fee configuration
                $builder->where('fee_amount IS NOT NULL');
            } else {
                // Show courses without fee configuration
                $builder->where('fee_amount IS NULL');
            }
        }

        $courses = $builder->get()->getResultArray();

        // Convert to expected format
        return array_map(function($course) {
            // fee_term: 1 = one-time, 2 = recurring
            // Get billing_cycle_days from tbl_course
            $billingCycleDays = null;
            if (isset($course['billing_cycle_days']) && $course['billing_cycle_days'] !== null) {
                $billingCycleDays = (int)$course['billing_cycle_days'];
            } elseif (isset($course['fee_term']) && $course['fee_term'] == 2) {
                $billingCycleDays = 30; // Default to monthly for recurring
            }

            return [
                'course_id' => $course['course_id'],
                'course_name' => $course['course_name'],
                'default_amount' => $course['fee_amount'] ? (float) $course['fee_amount'] : null,
                'billing_cycle_days' => $billingCycleDays,
                'fee_term' => $course['fee_term'],
                'is_active' => $course['fee_amount'] ? 1 : 0
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

        // Allow setting fee_amount to null to clear it
        // But if it's explicitly set, validate it
        if ($feeAmount !== null && $feeAmount < 0) {
            return false;
        }

        // Determine fee_term from billing_cycle_days
        // null or 0 = one-time (1), any positive value = recurring (2)
        $feeTerm = 1; // Default to one-time
        $billingCycleDays = null;
        if (isset($feeData['billing_cycle_days']) && $feeData['billing_cycle_days'] > 0) {
            $feeTerm = 2; // Recurring
            $billingCycleDays = (int)$feeData['billing_cycle_days'];
        }

        $updateData = [
            'fee_term' => $feeTerm
        ];
        
        // Only update fee_amount if it's provided (can be null to clear)
        if (isset($feeData['default_amount'])) {
            $updateData['fee_amount'] = $feeAmount !== null ? (float)$feeAmount : null;
        }
        
        // Update billing_cycle_days in tbl_course
        if (isset($feeData['billing_cycle_days'])) {
            $updateData['billing_cycle_days'] = $billingCycleDays;
        }

        // Update tbl_course with all fee-related fields
        $result = $this->where('course_id', $courseId)
            ->where('entity_id', $schoolId)
            ->set($updateData)
            ->update();

        return $result;
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
