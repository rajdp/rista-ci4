<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\StudentCourseModel;
use App\Models\CourseFeePlanModel;
use App\Services\CourseEnrollmentService;
use App\Traits\RestTrait;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Student Course Management Controller
 * Handles adding/removing courses from student profiles
 */
class StudentCourses extends BaseController
{
    use RestTrait;

    protected StudentCourseModel $studentCourseModel;
    protected CourseFeePlanModel $courseFeePlanModel;
    protected CourseEnrollmentService $enrollmentService;

    public function __construct()
    {
        $this->studentCourseModel = new StudentCourseModel();
        $this->courseFeePlanModel = new CourseFeePlanModel();
        $this->enrollmentService = new CourseEnrollmentService();
    }

    /**
     * Get all courses for a student
     */
    public function list(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);
            $studentId = (int) ($payload['student_id'] ?? 0);

            if ($studentId <= 0) {
                return $this->errorResponse('student_id is required');
            }

            $token = $this->validateToken();
            if (!$token) {
                return $this->unauthorizedResponse('Access token required');
            }

            $schoolId = (int) $this->getSchoolId($token);
            if (!$schoolId) {
                return $this->errorResponse('School ID not found');
            }

            $status = $payload['status'] ?? null;
            $courses = $this->studentCourseModel->getStudentCourses($studentId, $schoolId, $status);

            // Ensure each course has student_course_id field (alias for id)
            foreach ($courses as &$course) {
                if (!isset($course['student_course_id']) && isset($course['id'])) {
                    $course['student_course_id'] = $course['id'];
                }
                // Ensure all required fields have default values
                if (!isset($course['course_name'])) {
                    $course['course_name'] = '';
                }
                if (!isset($course['course_description'])) {
                    $course['course_description'] = '';
                }
                if (!isset($course['fee_plan_name'])) {
                    $course['fee_plan_name'] = '';
                }
            }

            return $this->successResponse([
                'student_id' => $studentId,
                'courses' => $courses,
                'total' => count($courses)
            ]);

        } catch (\Throwable $e) {
            log_message('error', 'StudentCourses::list - ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            return $this->errorResponse('Unable to load student courses: ' . $e->getMessage());
        }
    }

    /**
     * Get course fee preview before adding to student
     */
    public function feePreview(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);
            $courseId = (int) ($payload['course_id'] ?? 0);

            if ($courseId <= 0) {
                return $this->errorResponse('course_id is required');
            }

            $token = $this->validateToken();
            if (!$token) {
                return $this->unauthorizedResponse('Access token required');
            }

            $schoolId = (int) $this->getSchoolId($token);
            if (!$schoolId) {
                return $this->errorResponse('School ID not found');
            }

            // Get course fee configuration
            $courseFee = $this->courseFeePlanModel->getFeeForCourse($courseId, $schoolId);

            if ($courseFee && $courseFee['default_amount'] !== null) {
                return $this->successResponse([
                    'course_id' => $courseId,
                    'course_name' => $courseFee['course_name'],
                    'has_fee_configured' => true,
                    'default_amount' => $courseFee['default_amount'],
                    'calculated_fee' => $courseFee['default_amount'],
                    'billing_cycle_days' => $courseFee['billing_cycle_days'],
                    'fee_term' => $courseFee['fee_term'],
                    'can_override' => true
                ]);
            } else {
                return $this->successResponse([
                    'course_id' => $courseId,
                    'course_name' => $courseFee['course_name'] ?? null,
                    'has_fee_configured' => false,
                    'default_amount' => null,
                    'calculated_fee' => null,
                    'billing_cycle_days' => null,
                    'can_override' => true,
                    'message' => 'No fee configuration found for this course. You can set a custom amount.'
                ]);
            }

        } catch (\Throwable $e) {
            log_message('error', 'StudentCourses::feePreview - ' . $e->getMessage());
            return $this->errorResponse('Unable to calculate fee preview');
        }
    }

    /**
     * Add course to student with fee assignment and class enrollment
     */
    public function add(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);
            $studentId = (int) ($payload['student_id'] ?? 0);
            $courseId = (int) ($payload['course_id'] ?? 0);

            if ($studentId <= 0) {
                return $this->errorResponse('student_id is required');
            }

            if ($courseId <= 0) {
                return $this->errorResponse('course_id is required');
            }

            $token = $this->validateToken();
            if (!$token) {
                return $this->unauthorizedResponse('Access token required');
            }

            $actorId = (int) $this->getUserId($token);
            $schoolId = (int) $this->getSchoolId($token);

            if (!$schoolId) {
                return $this->errorResponse('School ID not found');
            }

            // Build enrollment options
            $options = [
                'added_by' => $actorId,
                'enrollment_date' => $payload['enrollment_date'] ?? date('Y-m-d'),
                'notes' => $payload['notes'] ?? null,
            ];

            // Handle fee amount (use custom or auto-calculated)
            if (isset($payload['fee_amount']) && $payload['fee_amount'] !== null) {
                $options['fee_amount'] = (float) $payload['fee_amount'];
            }

            // Handle discount
            if (isset($payload['discount_amount']) && $payload['discount_amount'] > 0) {
                $options['discount_amount'] = (float) $payload['discount_amount'];
            }

            // Handle class assignments
            if (isset($payload['class_ids']) && is_array($payload['class_ids'])) {
                $options['class_ids'] = $payload['class_ids'];
            }

            if (isset($payload['skip_class_enrollment'])) {
                $options['skip_class_enrollment'] = (bool) $payload['skip_class_enrollment'];
            }

            // Enroll student in course
            $result = $this->enrollmentService->enrollStudentInCourse(
                $studentId,
                $courseId,
                $schoolId,
                $options
            );

            if (!$result['success']) {
                return $this->errorResponse($result['message']);
            }

            // Get updated course list
            $courses = $this->studentCourseModel->getStudentCourses($studentId, $schoolId);

            return $this->successResponse([
                'student_course_id' => $result['student_course_id'],
                'fee_amount' => $result['fee_amount'],
                'student_fee_plan_id' => $result['student_fee_plan_id'],
                'class_enrollments' => $result['class_enrollments'],
                'courses' => $courses
            ], 'Course added successfully');

        } catch (\Throwable $e) {
            log_message('error', 'StudentCourses::add - ' . $e->getMessage());
            return $this->errorResponse('Unable to add course to student');
        }
    }

    /**
     * Update course status (complete, drop, suspend)
     */
    public function updateStatus(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);
            $studentCourseId = (int) ($payload['student_course_id'] ?? 0);
            $status = strtolower(trim((string) ($payload['status'] ?? '')));

            if ($studentCourseId <= 0) {
                return $this->errorResponse('student_course_id is required');
            }

            if (!in_array($status, ['active', 'completed', 'dropped', 'suspended'])) {
                return $this->errorResponse('Invalid status. Use: active, completed, dropped, or suspended');
            }

            $token = $this->validateToken();
            if (!$token) {
                return $this->unauthorizedResponse('Access token required');
            }

            $additionalData = [];
            if (isset($payload['completion_date'])) {
                $additionalData['completion_date'] = $payload['completion_date'];
            }
            if (isset($payload['notes'])) {
                $additionalData['notes'] = $payload['notes'];
            }

            $updated = $this->studentCourseModel->updateCourseStatus(
                $studentCourseId,
                $status,
                $additionalData
            );

            if (!$updated) {
                return $this->errorResponse('Failed to update course status');
            }

            return $this->successResponse(null, 'Course status updated successfully');

        } catch (\Throwable $e) {
            log_message('error', 'StudentCourses::updateStatus - ' . $e->getMessage());
            return $this->errorResponse('Unable to update course status');
        }
    }

    /**
     * Update course enrollment details (enrollment date, fee amount, notes)
     */
    public function update($id = null): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);
            // Use $id parameter if provided, otherwise use student_course_id from payload
            $studentCourseId = $id ? (int)$id : (int) ($payload['student_course_id'] ?? 0);

            if ($studentCourseId <= 0) {
                return $this->errorResponse('student_course_id is required');
            }

            $token = $this->validateToken();
            if (!$token) {
                return $this->unauthorizedResponse('Access token required');
            }

            $schoolId = (int) $this->getSchoolId($token);
            if (!$schoolId) {
                return $this->errorResponse('School ID not found');
            }

            // Verify the student course belongs to this school
            $studentCourse = $this->studentCourseModel->find($studentCourseId);
            if (!$studentCourse) {
                return $this->errorResponse('Student course not found');
            }

            if ($studentCourse['school_id'] != $schoolId) {
                return $this->errorResponse('Unauthorized access to this student course');
            }

            // Build update data
            $updateData = [];
            if (isset($payload['enrollment_date']) && !empty($payload['enrollment_date'])) {
                $updateData['enrollment_date'] = $payload['enrollment_date'];
            }
            if (isset($payload['fee_amount'])) {
                $updateData['fee_amount'] = $payload['fee_amount'] !== null && $payload['fee_amount'] !== '' 
                    ? (float) $payload['fee_amount'] 
                    : null;
            }
            if (isset($payload['notes'])) {
                $updateData['notes'] = $payload['notes'];
            }

            if (empty($updateData)) {
                return $this->errorResponse('No fields to update');
            }

            $updated = $this->studentCourseModel->update($studentCourseId, $updateData);

            if (!$updated) {
                return $this->errorResponse('Failed to update course enrollment details');
            }

            // Get updated course data
            $updatedCourse = $this->studentCourseModel->getStudentCourses(
                $studentCourse['student_id'],
                $schoolId
            );

            return $this->successResponse([
                'student_course_id' => $studentCourseId,
                'course' => $updatedCourse
            ], 'Course enrollment details updated successfully');

        } catch (\Throwable $e) {
            log_message('error', 'StudentCourses::update - ' . $e->getMessage());
            return $this->errorResponse('Unable to update course enrollment details');
        }
    }

    /**
     * Get enrollment summary for a student
     */
    public function summary(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);
            $studentId = (int) ($payload['student_id'] ?? 0);

            if ($studentId <= 0) {
                return $this->errorResponse('student_id is required');
            }

            $token = $this->validateToken();
            if (!$token) {
                return $this->unauthorizedResponse('Access token required');
            }

            $schoolId = (int) $this->getSchoolId($token);
            if (!$schoolId) {
                return $this->errorResponse('School ID not found');
            }

            $summary = $this->enrollmentService->getStudentEnrollmentSummary($studentId, $schoolId);

            return $this->successResponse($summary);

        } catch (\Throwable $e) {
            log_message('error', 'StudentCourses::summary - ' . $e->getMessage());
            return $this->errorResponse('Unable to load enrollment summary');
        }
    }

    /**
     * Bulk add courses to multiple students
     */
    public function bulkAdd(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);
            $studentIds = $payload['student_ids'] ?? [];
            $courseIds = $payload['course_ids'] ?? [];

            if (empty($studentIds) || !is_array($studentIds)) {
                return $this->errorResponse('student_ids array is required');
            }

            if (empty($courseIds) || !is_array($courseIds)) {
                return $this->errorResponse('course_ids array is required');
            }

            $token = $this->validateToken();
            if (!$token) {
                return $this->unauthorizedResponse('Access token required');
            }

            $actorId = (int) $this->getUserId($token);
            $schoolId = (int) $this->getSchoolId($token);

            if (!$schoolId) {
                return $this->errorResponse('School ID not found');
            }

            $options = [
                'added_by' => $actorId,
                'enrollment_date' => $payload['enrollment_date'] ?? date('Y-m-d'),
                'notes' => $payload['notes'] ?? null,
            ];

            if (isset($payload['fee_amount'])) {
                $options['fee_amount'] = (float) $payload['fee_amount'];
            }

            $result = $this->enrollmentService->bulkEnrollStudents(
                $studentIds,
                $courseIds,
                $schoolId,
                $options
            );

            return $this->successResponse($result,
                sprintf('%d enrollments completed successfully', $result['success_count'])
            );

        } catch (\Throwable $e) {
            log_message('error', 'StudentCourses::bulkAdd - ' . $e->getMessage());
            return $this->errorResponse('Unable to complete bulk enrollment');
        }
    }
}
