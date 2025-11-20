<?php

namespace App\Services;

use App\Models\StudentCourseModel;
use App\Models\CourseFeePlanModel;
use App\Models\CourseClassMappingModel;
use App\Models\Admin\StudentFeePlanModel;
use App\Models\Admin\FeePlanModel;
use App\Models\V1\StudentModel;
use App\Models\V1\ClassesModel;
use App\Services\BillingScheduler;

class CourseEnrollmentService
{
    protected $studentCourseModel;
    protected $courseFeePlanModel;
    protected $courseClassMappingModel;
    protected $studentFeePlanModel;
    protected $feePlanModel;
    protected $studentModel;
    protected $classesModel;
    protected $billingScheduler;
    protected $db;

    public function __construct()
    {
        $this->studentCourseModel = new StudentCourseModel();
        $this->courseFeePlanModel = new CourseFeePlanModel();
        $this->courseClassMappingModel = new CourseClassMappingModel();
        $this->studentFeePlanModel = new StudentFeePlanModel();
        $this->feePlanModel = new FeePlanModel();
        $this->studentModel = new StudentModel();
        $this->classesModel = new ClassesModel();
        $this->billingScheduler = new BillingScheduler();
        $this->db = \Config\Database::connect();
    }

    /**
     * Enroll student in a course with automatic fee assignment and class enrollment
     *
     * @param int $studentId Student user ID
     * @param int $courseId Course ID
     * @param int $schoolId School ID
     * @param array $options Additional options
     * @return array Result with success status and details
     */
    public function enrollStudentInCourse(int $studentId, int $courseId, int $schoolId, array $options = []): array
    {
        $this->db->transStart();

        try {
            // 1. Determine fee amount
            $feeAmount = $options['fee_amount'] ?? null;
            $studentFeePlanId = $options['student_fee_plan_id'] ?? null;

            if ($feeAmount === null) {
                // Auto-calculate fee from course fee mapping
                $feeAmount = $this->courseFeePlanModel->calculateFeeAmount($courseId, $schoolId);
            }

            // 2. Create or assign fee plan if fee amount exists
            if ($feeAmount !== null && $feeAmount > 0) {
                $studentFeePlanId = $this->assignFeePlan(
                    $studentId,
                    $courseId,
                    $schoolId,
                    $feeAmount,
                    $options
                );
            }

            // 3. Enroll in course
            $enrollmentData = [
                'student_id' => $studentId,
                'course_id' => $courseId,
                'school_id' => $schoolId,
                'registration_id' => $options['registration_id'] ?? null,
                'enrollment_date' => $options['enrollment_date'] ?? date('Y-m-d'),
                'fee_amount' => $feeAmount,
                'student_fee_plan_id' => $studentFeePlanId,
                'added_by' => $options['added_by'] ?? null,
                'notes' => $options['notes'] ?? null
            ];

            $enrollmentResult = $this->studentCourseModel->enrollStudent($enrollmentData);

            if (!$enrollmentResult['success']) {
                throw new \Exception($enrollmentResult['message']);
            }

            // 4. Auto-enroll in linked classes (only if explicitly requested)
            // Course enrollment and class enrollment are separate
            $classEnrollments = [];
            if (isset($options['auto_enroll_classes']) && $options['auto_enroll_classes'] === true) {
                $classEnrollments = $this->enrollInLinkedClasses(
                    $studentId,
                    $courseId,
                    $schoolId,
                    $options['class_ids'] ?? null
                );
            }

            // 5. Seed billing schedule if fee exists
            $billingScheduleId = null;
            if ($feeAmount !== null && $feeAmount > 0) {
                $enrollmentId = $enrollmentResult['student_course_id'];
                $startDate = $options['enrollment_date'] ?? date('Y-m-d');
                
                $billingResult = $this->billingScheduler->seedSchedule(
                    $enrollmentId,
                    $studentId,
                    $courseId,
                    $schoolId,
                    $startDate,
                    [
                        'deposit_policy' => $options['deposit_policy'] ?? 'none',
                        'deposit_cents' => isset($options['deposit_cents']) ? (int)round($options['deposit_cents'] * 100) : 0,
                        'anchor_day' => $options['anchor_day'] ?? null,
                        'anchor_month' => $options['anchor_month'] ?? null,
                    ]
                );

                if ($billingResult['success']) {
                    $billingScheduleId = $billingResult['schedule_id'];
                } else {
                    // Log error but don't fail enrollment
                    log_message('warning', 'Failed to seed billing schedule: ' . ($billingResult['message'] ?? 'Unknown error'));
                }
            }

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                throw new \Exception('Transaction failed');
            }

            return [
                'success' => true,
                'student_course_id' => $enrollmentResult['student_course_id'],
                'fee_amount' => $feeAmount,
                'student_fee_plan_id' => $studentFeePlanId,
                'billing_schedule_id' => $billingScheduleId,
                'class_enrollments' => $classEnrollments,
                'message' => 'Student enrolled in course successfully'
            ];

        } catch (\Exception $e) {
            $this->db->transRollback();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Assign fee plan to student for a course
     */
    protected function assignFeePlan(int $studentId, int $courseId, int $schoolId, float $feeAmount, array $options = []): ?int
    {
        // Get course fee configuration
        $courseFee = $this->courseFeePlanModel->getFeeForCourse($courseId, $schoolId);

        // Determine billing cycle and plan type from fee_term
        // fee_term: 1 = one-time, 2 = recurring monthly
        $billingCycleDays = $courseFee['billing_cycle_days'] ?? null;
        $feeTerm = $courseFee['fee_term'] ?? 1;
        $planType = ($feeTerm == 2) ? 'recurring' : 'one_time';

        // Get or create course-specific fee plan
        $courseName = $courseFee['course_name'] ?? "Course $courseId";

        // Create course-specific fee plan
        $feePlanId = $this->feePlanModel->insert([
            'school_id' => $schoolId,
            'name' => "Course: $courseName",
            'plan_type' => $planType,
            'amount' => $feeAmount,
            'billing_cycle_days' => $billingCycleDays,
            'auto_payment_enabled' => 0,
            'is_active' => 1
        ]);

        // Assign fee plan to student
        $assignmentData = [
            'student_id' => $studentId,
            'fee_plan_id' => $feePlanId,
            'school_id' => $schoolId,
            'start_date' => $options['fee_start_date'] ?? date('Y-m-d'),
            'end_date' => $options['fee_end_date'] ?? null,
            'custom_amount' => $feeAmount,
            'discount_amount' => $options['discount_amount'] ?? 0,
            'status' => 'active',
            'auto_payment_override' => $options['auto_payment_override'] ?? null
        ];

        $studentFeePlanId = $this->studentFeePlanModel->insert($assignmentData);

        return $studentFeePlanId;
    }

    /**
     * Enroll student in classes linked to the course
     */
    protected function enrollInLinkedClasses(int $studentId, int $courseId, int $schoolId, ?array $classIds = null): array
    {
        $enrollments = [];

        // Get classes to enroll in
        if ($classIds !== null) {
            // Use specified class IDs
            $targetClassIds = $classIds;
        } else {
            // Get auto-enroll classes from course mapping
            $targetClassIds = $this->courseClassMappingModel->getAutoEnrollClassIds($courseId, $schoolId);
        }

        if (empty($targetClassIds)) {
            return $enrollments;
        }

        // Enroll student in each class
        foreach ($targetClassIds as $classId) {
            $result = $this->enrollStudentInClass($studentId, $classId, $schoolId);
            $enrollments[] = [
                'class_id' => $classId,
                'success' => $result['success'],
                'message' => $result['message']
            ];
        }

        return $enrollments;
    }

    /**
     * Enroll student in a single class
     */
    protected function enrollStudentInClass(int $studentId, int $classId, int $schoolId): array
    {
        try {
            // Check if already enrolled
            $existing = $this->db->table('student_class')
                ->where([
                    'student_id' => $studentId,
                    'class_id' => $classId
                ])
                ->get()
                ->getRowArray();

            if ($existing) {
                return [
                    'success' => true,
                    'message' => 'Student already enrolled in class',
                    'enrollment_id' => $existing['id'] ?? null
                ];
            }

            // Add student to class
            $enrollmentId = $this->db->table('student_class')->insert([
                'class_id' => $classId,
                'student_id' => $studentId,
                'status' => '1',
                'joining_date' => date('Y-m-d'),
                'validity' => '2099-12-31',
                'class_type' => 1,
                'created_date' => date('Y-m-d H:i:s')
            ]);

            return [
                'success' => true,
                'message' => 'Student enrolled in class',
                'enrollment_id' => $enrollmentId
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Enroll multiple students in multiple courses (bulk enrollment)
     */
    public function bulkEnrollStudents(array $studentIds, array $courseIds, int $schoolId, array $options = []): array
    {
        $results = [];
        $successCount = 0;
        $failCount = 0;

        foreach ($studentIds as $studentId) {
            foreach ($courseIds as $courseId) {
                $result = $this->enrollStudentInCourse($studentId, $courseId, $schoolId, $options);

                $results[] = [
                    'student_id' => $studentId,
                    'course_id' => $courseId,
                    'success' => $result['success'],
                    'message' => $result['message']
                ];

                if ($result['success']) {
                    $successCount++;
                } else {
                    $failCount++;
                }
            }
        }

        return [
            'success' => $failCount === 0,
            'total' => count($results),
            'success_count' => $successCount,
            'fail_count' => $failCount,
            'results' => $results
        ];
    }

    /**
     * Get enrollment summary for a student
     */
    public function getStudentEnrollmentSummary(int $studentId, int $schoolId): array
    {
        $courses = $this->studentCourseModel->getStudentCourses($studentId, $schoolId);

        $totalFees = 0;
        $activeCourses = 0;

        foreach ($courses as $course) {
            if ($course['status'] === 'active') {
                $activeCourses++;
                $totalFees += $course['fee_amount'] ?? 0;
            }
        }

        return [
            'student_id' => $studentId,
            'total_courses' => count($courses),
            'active_courses' => $activeCourses,
            'total_fees' => $totalFees,
            'courses' => $courses
        ];
    }
}
