<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CourseFeePlanModel;
use App\Models\CourseClassMappingModel;
use App\Traits\RestTrait;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Course Fee Configuration Controller
 * Manages course-to-fee mappings and course-to-class relationships
 */
class CourseFees extends BaseController
{
    use RestTrait;

    protected CourseFeePlanModel $courseFeePlanModel;
    protected CourseClassMappingModel $courseClassMappingModel;

    public function __construct()
    {
        $this->courseFeePlanModel = new CourseFeePlanModel();
        $this->courseClassMappingModel = new CourseClassMappingModel();
    }

    /**
     * Get all courses with their fee configurations
     */
    public function list(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);

            $token = $this->validateToken();
            if (!$token) {
                return $this->unauthorizedResponse('Access token required');
            }

            $schoolId = (int) $this->getSchoolId($token);
            if (!$schoolId) {
                return $this->errorResponse('School ID not found');
            }

            $filters = [
                'is_active' => $payload['is_active'] ?? null,
                'course_id' => $payload['course_id'] ?? null
            ];

            $coursesWithFees = $this->courseFeePlanModel->getCoursesWithFees($schoolId, $filters);

            return $this->successResponse([
                'courses' => $coursesWithFees,
                'total' => count($coursesWithFees)
            ]);

        } catch (\Throwable $e) {
            log_message('error', 'CourseFees::list - ' . $e->getMessage());
            return $this->errorResponse('Unable to load course fee configurations');
        }
    }

    /**
     * Get fee configuration for a specific course
     */
    public function get(): ResponseInterface
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

            $courseFee = $this->courseFeePlanModel->getFeeForCourse($courseId, $schoolId);
            $linkedClasses = $this->courseClassMappingModel->getClassesForCourse($courseId, $schoolId);

            return $this->successResponse([
                'course_id' => $courseId,
                'fee_configuration' => $courseFee,
                'linked_classes' => $linkedClasses
            ]);

        } catch (\Throwable $e) {
            log_message('error', 'CourseFees::get - ' . $e->getMessage());
            return $this->errorResponse('Unable to load course fee configuration');
        }
    }

    /**
     * Set or update course fee mapping
     */
    public function save(): ResponseInterface
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

            $feeData = [
                'fee_plan_id' => isset($payload['fee_plan_id']) && $payload['fee_plan_id'] > 0
                    ? (int) $payload['fee_plan_id']
                    : null,
                'default_amount' => isset($payload['default_amount']) && $payload['default_amount'] !== null
                    ? (float) $payload['default_amount']
                    : null,
                'billing_cycle_days' => isset($payload['billing_cycle_days']) && $payload['billing_cycle_days'] > 0
                    ? (int) $payload['billing_cycle_days']
                    : null,
                'is_active' => isset($payload['is_active']) ? (int) $payload['is_active'] : 1
            ];

            $result = $this->courseFeePlanModel->setCourseFee($courseId, $schoolId, $feeData);

            if (!$result) {
                return $this->errorResponse('Failed to save course fee configuration');
            }

            return $this->successResponse(null, 'Course fee configuration saved successfully');

        } catch (\Throwable $e) {
            log_message('error', 'CourseFees::save - ' . $e->getMessage());
            return $this->errorResponse('Unable to save course fee configuration');
        }
    }

    /**
     * Clear course fee (sets tbl_course.fees to empty/null)
     */
    public function delete(): ResponseInterface
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

            // Clear the fee by setting it to null/empty
            $cleared = $this->courseFeePlanModel
                ->where('course_id', $courseId)
                ->where('entity_id', $schoolId)
                ->set(['fee_amount' => null, 'fee_term' => null])
                ->update();

            if (!$cleared) {
                return $this->errorResponse('Failed to clear course fee');
            }

            return $this->successResponse(null, 'Course fee cleared successfully');

        } catch (\Throwable $e) {
            log_message('error', 'CourseFees::delete - ' . $e->getMessage());
            return $this->errorResponse('Unable to clear course fee');
        }
    }

    /**
     * Link course to classes for auto-enrollment
     */
    public function linkClasses(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);
            $courseId = (int) ($payload['course_id'] ?? 0);
            $classIds = $payload['class_ids'] ?? [];

            if ($courseId <= 0) {
                return $this->errorResponse('course_id is required');
            }

            if (empty($classIds) || !is_array($classIds)) {
                return $this->errorResponse('class_ids array is required');
            }

            $token = $this->validateToken();
            if (!$token) {
                return $this->unauthorizedResponse('Access token required');
            }

            $schoolId = (int) $this->getSchoolId($token);
            if (!$schoolId) {
                return $this->errorResponse('School ID not found');
            }

            $autoEnroll = isset($payload['auto_enroll']) ? (bool) $payload['auto_enroll'] : true;

            // First, remove existing mappings for this course
            $this->courseClassMappingModel->where([
                'course_id' => $courseId,
                'school_id' => $schoolId
            ])->delete();

            // Then, add new mappings
            $result = $this->courseClassMappingModel->bulkLinkClasses(
                $courseId,
                $schoolId,
                $classIds,
                $autoEnroll
            );

            if (!$result) {
                return $this->errorResponse('Failed to link classes to course');
            }

            // Get updated list
            $linkedClasses = $this->courseClassMappingModel->getClassesForCourse($courseId, $schoolId);

            return $this->successResponse([
                'course_id' => $courseId,
                'linked_classes' => $linkedClasses
            ], 'Classes linked successfully');

        } catch (\Throwable $e) {
            log_message('error', 'CourseFees::linkClasses - ' . $e->getMessage());
            return $this->errorResponse('Unable to link classes');
        }
    }

    /**
     * Get classes linked to a course
     */
    public function getLinkedClasses(): ResponseInterface
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

            $autoEnrollOnly = isset($payload['auto_enroll_only']) ? (bool) $payload['auto_enroll_only'] : false;

            $classes = $this->courseClassMappingModel->getClassesForCourse($courseId, $schoolId, $autoEnrollOnly);

            return $this->successResponse([
                'course_id' => $courseId,
                'classes' => $classes,
                'total' => count($classes)
            ]);

        } catch (\Throwable $e) {
            log_message('error', 'CourseFees::getLinkedClasses - ' . $e->getMessage());
            return $this->errorResponse('Unable to load linked classes');
        }
    }

    /**
     * Unlink a specific class from a course
     */
    public function unlinkClass(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);
            $courseId = (int) ($payload['course_id'] ?? 0);
            $classId = (int) ($payload['class_id'] ?? 0);

            if ($courseId <= 0) {
                return $this->errorResponse('course_id is required');
            }

            if ($classId <= 0) {
                return $this->errorResponse('class_id is required');
            }

            $token = $this->validateToken();
            if (!$token) {
                return $this->unauthorizedResponse('Access token required');
            }

            $schoolId = (int) $this->getSchoolId($token);
            if (!$schoolId) {
                return $this->errorResponse('School ID not found');
            }

            $result = $this->courseClassMappingModel->unlinkCourseFromClass($courseId, $classId, $schoolId);

            if (!$result) {
                return $this->errorResponse('Failed to unlink class from course');
            }

            return $this->successResponse(null, 'Class unlinked successfully');

        } catch (\Throwable $e) {
            log_message('error', 'CourseFees::unlinkClass - ' . $e->getMessage());
            return $this->errorResponse('Unable to unlink class');
        }
    }
}
