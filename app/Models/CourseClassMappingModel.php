<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * CourseClassMappingModel
 *
 * Provides course-to-class mapping using the existing class.course_id relationship.
 * This model serves as an adapter to maintain compatibility with CourseEnrollmentService
 * while using the existing class table structure.
 */
class CourseClassMappingModel extends Model
{
    protected $table = 'class';
    protected $primaryKey = 'class_id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'course_id',
        'class_name',
        'status'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_date';
    protected $updatedField = 'modified_date';

    /**
     * Get all active classes associated with a course
     *
     * @param int $courseId
     * @param int $schoolId
     * @param bool $autoEnrollOnly (ignored - all active classes are considered for enrollment)
     * @return array
     */
    public function getClassesForCourse(int $courseId, int $schoolId, bool $autoEnrollOnly = false)
    {
        return $this->select('
                class_id,
                class_name,
                class_code,
                grade,
                subject,
                start_date,
                end_date,
                start_time,
                end_time,
                status
            ')
            ->where([
                'course_id' => $courseId,
                'school_id' => $schoolId,
                'status' => 1  // Only active classes
            ])
            ->findAll();
    }

    /**
     * Get the course associated with a class
     *
     * @param int $classId
     * @param int $schoolId
     * @return array
     */
    public function getCoursesForClass(int $classId, int $schoolId)
    {
        $class = $this->select('
                class.class_id,
                class.course_id,
                tbl_course.course_name
            ')
            ->join('tbl_course', 'tbl_course.course_id = class.course_id AND tbl_course.entity_id = ' . (int)$schoolId, 'left')
            ->where([
                'class.class_id' => $classId,
                'class.school_id' => $schoolId
            ])
            ->first();

        return $class ? [$class] : [];
    }

    /**
     * Link course to class by updating class.course_id
     *
     * @param int $courseId
     * @param int $classId
     * @param int $schoolId
     * @param bool $autoEnroll (kept for API compatibility)
     * @return bool
     */
    public function linkCourseToClass(int $courseId, int $classId, int $schoolId, bool $autoEnroll = true)
    {
        // Verify class exists and belongs to school
        $class = $this->where([
            'class_id' => $classId,
            'school_id' => $schoolId
        ])->first();

        if (!$class) {
            return false;
        }

        // Update class.course_id
        return $this->update($classId, [
            'course_id' => $courseId
        ]);
    }

    /**
     * Remove course-class link by setting class.course_id to 0
     *
     * @param int $courseId
     * @param int $classId
     * @param int $schoolId
     * @return bool
     */
    public function unlinkCourseFromClass(int $courseId, int $classId, int $schoolId)
    {
        // Verify class belongs to this course and school
        $class = $this->where([
            'class_id' => $classId,
            'school_id' => $schoolId,
            'course_id' => $courseId
        ])->first();

        if (!$class) {
            return false;
        }

        // Set course_id back to 0
        return $this->update($classId, [
            'course_id' => 0
        ]);
    }

    /**
     * Get auto-enroll class IDs for a course
     * Returns all active class IDs linked to the course
     *
     * @param int $courseId
     * @param int $schoolId
     * @return array
     */
    public function getAutoEnrollClassIds(int $courseId, int $schoolId): array
    {
        $classes = $this->select('class_id')
            ->where([
                'course_id' => $courseId,
                'school_id' => $schoolId,
                'status' => 1  // Only active classes
            ])
            ->findAll();

        return array_column($classes, 'class_id');
    }

    /**
     * Bulk link multiple classes to a course
     * Updates class.course_id for each class
     *
     * @param int $courseId
     * @param int $schoolId
     * @param array $classIds
     * @param bool $autoEnroll (kept for API compatibility)
     * @return bool
     */
    public function bulkLinkClasses(int $courseId, int $schoolId, array $classIds, bool $autoEnroll = true)
    {
        if (empty($classIds)) {
            return true;
        }

        // Update all classes at once
        $updated = $this->whereIn('class_id', $classIds)
            ->where('school_id', $schoolId)
            ->set(['course_id' => $courseId])
            ->update();

        return $updated !== false;
    }

    /**
     * Get classes that are NOT linked to any course
     * Useful for showing available classes to link
     *
     * @param int $schoolId
     * @return array
     */
    public function getUnlinkedClasses(int $schoolId): array
    {
        return $this->select('class_id, class_name, class_code')
            ->where([
                'school_id' => $schoolId,
                'course_id' => 0,
                'status' => 1
            ])
            ->findAll();
    }
}
