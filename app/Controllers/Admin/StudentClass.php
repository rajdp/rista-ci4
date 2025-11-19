<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Traits\RestTrait;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Student Class Management Controller
 * Manages student-class assignments
 */
class StudentClass extends BaseController
{
    use RestTrait;

    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * Get all classes for a student
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

            // Get student's classes with class details
            $classes = $this->db->table('student_class sc')
                ->select('
                    sc.id,
                    sc.class_id,
                    sc.student_id,
                    sc.status,
                    sc.joining_date,
                    sc.validity,
                    sc.class_type,
                    c.class_name,
                    c.class_code,
                    c.grade,
                    c.subject,
                    c.start_date,
                    c.end_date,
                    c.start_time,
                    c.end_time,
                    c.teacher_id,
                    u.first_name as teacher_first_name,
                    u.last_name as teacher_last_name
                ')
                ->join('class c', 'c.class_id = sc.class_id', 'left')
                ->join('user u', 'u.id = c.teacher_id', 'left')
                ->where('sc.student_id', $studentId)
                ->where('c.school_id', $schoolId)
                ->where('sc.status', '1')
                ->orderBy('c.class_name', 'ASC')
                ->get()
                ->getResultArray();

            return $this->successResponse([
                'classes' => $classes,
                'total' => count($classes)
            ]);

        } catch (\Throwable $e) {
            log_message('error', 'StudentClass::list - ' . $e->getMessage());
            return $this->errorResponse('Unable to load student classes');
        }
    }

    /**
     * Add a class to a student
     */
    public function add(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);
            $studentId = (int) ($payload['student_id'] ?? 0);
            $classId = (int) ($payload['class_id'] ?? 0);
            $joiningDate = $payload['joining_date'] ?? date('Y-m-d');

            if ($studentId <= 0) {
                return $this->errorResponse('student_id is required');
            }

            if ($classId <= 0) {
                return $this->errorResponse('class_id is required');
            }

            $token = $this->validateToken();
            if (!$token) {
                return $this->unauthorizedResponse('Access token required');
            }

            $schoolId = (int) $this->getSchoolId($token);
            $userId = (int) $this->getUserId($token);

            if (!$schoolId) {
                return $this->errorResponse('School ID not found');
            }

            // Verify class exists and belongs to this school
            $class = $this->db->table('class')
                ->where('class_id', $classId)
                ->where('school_id', $schoolId)
                ->get()
                ->getRowArray();

            if (!$class) {
                return $this->errorResponse('Class not found or does not belong to this school');
            }

            // Check if student is already enrolled
            $existing = $this->db->table('student_class')
                ->where('student_id', $studentId)
                ->where('class_id', $classId)
                ->get()
                ->getRowArray();

            if ($existing) {
                if ($existing['status'] == '1') {
                    return $this->errorResponse('Student is already enrolled in this class');
                } else {
                    // Reactivate enrollment with new joining date
                    $this->db->table('student_class')
                        ->where('id', $existing['id'])
                        ->update([
                            'status' => '1',
                            'joining_date' => $joiningDate,
                            'modified_by' => $userId,
                            'modified_date' => date('Y-m-d H:i:s')
                        ]);

                    return $this->successResponse(
                        ['enrollment_id' => $existing['id']],
                        'Student re-enrolled in class successfully'
                    );
                }
            }

            // Add student to class
            $enrollmentData = [
                'class_id' => $classId,
                'student_id' => $studentId,
                'status' => '1',
                'joining_date' => $joiningDate,
                'validity' => $class['end_date'] != '0000-00-00' ? $class['end_date'] : '2099-12-31',
                'class_type' => 1,
                'created_by' => $userId,
                'created_date' => date('Y-m-d H:i:s')
            ];

            $enrollmentId = $this->db->table('student_class')->insert($enrollmentData);

            if (!$enrollmentId) {
                return $this->errorResponse('Failed to enroll student in class');
            }

            // Handle course enrollment if courses are selected
            $enrolledCourses = [];
            if (isset($payload['course_ids']) && is_array($payload['course_ids']) && !empty($payload['course_ids'])) {
                $courseIds = array_filter(array_map('intval', $payload['course_ids']));
                
                if (!empty($courseIds)) {
                    // Check if CourseEnrollmentService exists
                    if (class_exists('\App\Services\CourseEnrollmentService')) {
                        $enrollmentService = new \App\Services\CourseEnrollmentService();
                        
                        foreach ($courseIds as $courseId) {
                            if ($courseId > 0) {
                                try {
                                    $options = [
                                        'added_by' => $userId,
                                        'enrollment_date' => $joiningDate,
                                        'skip_class_enrollment' => true // Skip auto class enrollment since we're already enrolling
                                    ];
                                    
                                    $result = $enrollmentService->enrollStudentInCourse(
                                        $studentId,
                                        $courseId,
                                        $schoolId,
                                        $options
                                    );
                                    
                                    if ($result['success']) {
                                        $enrolledCourses[] = [
                                            'course_id' => $courseId,
                                            'student_course_id' => $result['student_course_id'] ?? null,
                                            'fee_amount' => $result['fee_amount'] ?? null
                                        ];
                                    }
                                } catch (\Exception $e) {
                                    log_message('error', 'StudentClass::add - Course enrollment error: ' . $e->getMessage());
                                }
                            }
                        }
                    }
                }
            }

            return $this->successResponse(
                [
                    'enrollment_id' => $enrollmentId,
                    'enrolled_courses' => $enrolledCourses
                ],
                'Student enrolled in class successfully'
            );

        } catch (\Throwable $e) {
            log_message('error', 'StudentClass::add - ' . $e->getMessage());
            return $this->errorResponse('Unable to add class to student');
        }
    }

    /**
     * Remove a class from a student (soft delete - sets status to 0 and validity date)
     */
    public function remove(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);
            $studentId = (int) ($payload['student_id'] ?? 0);
            $classId = (int) ($payload['class_id'] ?? 0);
            $effectiveDate = $payload['drop_date'] ?? $payload['effective_date'] ?? date('Y-m-d');

            if ($studentId <= 0) {
                return $this->errorResponse('student_id is required');
            }

            if ($classId <= 0) {
                return $this->errorResponse('class_id is required');
            }

            $token = $this->validateToken();
            if (!$token) {
                return $this->unauthorizedResponse('Access token required');
            }

            $userId = (int) $this->getUserId($token);

            // Prepare update data - set status to 0 (inactive) and validity to effective date
            $updateData = [
                'status' => '0',
                'validity' => $effectiveDate,
                'modified_by' => $userId,
                'modified_date' => date('Y-m-d H:i:s')
            ];

            // Update status to 0 (inactive) and set validity date
            $updated = $this->db->table('student_class')
                ->where('student_id', $studentId)
                ->where('class_id', $classId)
                ->update($updateData);

            if (!$updated) {
                return $this->errorResponse('Student enrollment in class not found');
            }

            // Handle course dropping if courses are selected
            $droppedCourses = [];
            if (isset($payload['course_ids']) && is_array($payload['course_ids']) && !empty($payload['course_ids'])) {
                $courseIds = array_filter(array_map('intval', $payload['course_ids']));
                
                if (!empty($courseIds)) {
                    // Get student course IDs for these courses
                    $studentCourses = $this->db->table('student_courses')
                        ->select('id, course_id, status')
                        ->where('student_id', $studentId)
                        ->where('school_id', $schoolId)
                        ->whereIn('course_id', $courseIds)
                        ->where('status', 'active')
                        ->get()
                        ->getResultArray();
                    
                    if (!empty($studentCourses)) {
                        $studentCourseIds = array_column($studentCourses, 'id');
                        
                        // Update course status to 'dropped'
                        $this->db->table('student_courses')
                            ->whereIn('id', $studentCourseIds)
                            ->update([
                                'status' => 'dropped',
                                'completion_date' => $effectiveDate,
                                'updated_at' => date('Y-m-d H:i:s')
                            ]);
                        
                        foreach ($studentCourses as $studentCourse) {
                            $droppedCourses[] = [
                                'course_id' => (int)$studentCourse['course_id'],
                                'student_course_id' => (int)$studentCourse['id']
                            ];
                        }
                    }
                }
            }

            return $this->successResponse(
                ['dropped_courses' => $droppedCourses],
                'Student dropped from class successfully'
            );

        } catch (\Throwable $e) {
            log_message('error', 'StudentClass::remove - ' . $e->getMessage());
            return $this->errorResponse('Unable to remove class from student');
        }
    }

    /**
     * Get available classes for enrollment (not already enrolled)
     */
    public function availableClasses(): ResponseInterface
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

            // Get all active classes excluding those already enrolled
            $classes = $this->db->table('class c')
                ->select('
                    c.class_id,
                    c.class_name,
                    c.class_code,
                    c.grade,
                    c.subject,
                    c.start_date,
                    c.end_date,
                    c.start_time,
                    c.end_time,
                    c.teacher_id,
                    u.first_name as teacher_first_name,
                    u.last_name as teacher_last_name,
                    tbl_course.course_name
                ')
                ->join('user u', 'u.id = c.teacher_id', 'left')
                ->join('tbl_course', 'tbl_course.course_id = c.course_id', 'left')
                ->where('c.school_id', $schoolId)
                ->where('c.status', '1')
                ->where("c.class_id NOT IN (
                    SELECT class_id FROM student_class
                    WHERE student_id = {$studentId} AND status = '1'
                )")
                ->orderBy('c.class_name', 'ASC')
                ->get()
                ->getResultArray();

            return $this->successResponse([
                'classes' => $classes,
                'total' => count($classes)
            ]);

        } catch (\Throwable $e) {
            log_message('error', 'StudentClass::availableClasses - ' . $e->getMessage());
            return $this->errorResponse('Unable to load available classes');
        }
    }

    /**
     * Get courses for a class
     */
    public function getClassCourses(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);
            $classId = (int) ($payload['class_id'] ?? 0);

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

            // Get course for this class
            $class = $this->db->table('class')
                ->select('class.class_id, class.course_id, tbl_course.course_id, tbl_course.course_name, tbl_course.fees, tbl_course.fee_term')
                ->join('tbl_course', 'tbl_course.course_id = class.course_id AND tbl_course.entity_id = ' . (int)$schoolId, 'left')
                ->where('class.class_id', $classId)
                ->where('class.school_id', $schoolId)
                ->get()
                ->getRowArray();

            $courses = [];
            if ($class && !empty($class['course_id'])) {
                $courses[] = [
                    'course_id' => (int)$class['course_id'],
                    'course_name' => $class['course_name'] ?? '',
                    'fees' => $class['fees'] ?? 0,
                    'fee_term' => $class['fee_term'] ?? 1
                ];
            }

            return $this->successResponse([
                'class_id' => $classId,
                'courses' => $courses
            ]);

        } catch (\Throwable $e) {
            log_message('error', 'StudentClass::getClassCourses - ' . $e->getMessage());
            return $this->errorResponse('Unable to load class courses');
        }
    }

    /**
     * Get active courses for a student
     */
    public function getStudentCourses(): ResponseInterface
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

            // Get active courses for the student
            $courses = $this->db->table('student_courses sc')
                ->select('sc.id as student_course_id, sc.course_id, sc.fee_amount, sc.enrollment_date, sc.status,
                         tbl_course.course_name, tbl_course.fees, tbl_course.fee_term')
                ->join('tbl_course', 'tbl_course.course_id = sc.course_id AND tbl_course.entity_id = ' . (int)$schoolId, 'left')
                ->where('sc.student_id', $studentId)
                ->where('sc.school_id', $schoolId)
                ->where('sc.status', 'active') // Only active courses
                ->orderBy('sc.enrollment_date', 'DESC')
                ->get()
                ->getResultArray();

            return $this->successResponse([
                'student_id' => $studentId,
                'courses' => $courses ?? []
            ]);

        } catch (\Throwable $e) {
            log_message('error', 'StudentClass::getStudentCourses - ' . $e->getMessage());
            return $this->errorResponse('Unable to load student courses');
        }
    }
}
