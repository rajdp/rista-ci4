<?php

namespace App\Models\V1;

use CodeIgniter\Model;
use CodeIgniter\API\ResponseTrait;

class CourseModel extends BaseModel
{
    use ResponseTrait;

    protected $table = 'tbl_course';
    protected $allowedFields = [
        'course_id',
        'course_name',
        'seo_title',
        'category_id',
        'subject_id',
        'grade_id',
        'description',
        'short_description',
        'path',
        'validity_start_date',
        'validity_end_date',
        'status',
        'lessons',
        'overview_content',
        'course_content',
        'prerequisites',
        'other_details',
        'author',
        'fees',
        'certified_course',
        'multiple_schedule',
        'schedule',
        'redirect_url',
        'button_name',
        'created_by',
        'created_date',
        'is_popular',
        'is_exclusive',
        'event',
        'display_order',
        'contact_info',
        'entity_id'
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function getCourse(string $condition): array
    {
        $builder = $this->getBuilder('c');
        $builder->select('c.course_id, c.course_name, c.seo_title, c.category_id, c.subject_id, c.grade_id, c.description,
                         c.short_description, COALESCE(c.path, "") as path, c.validity_start_date, c.validity_end_date, c.status, 
                         c.lessons, c.overview_content, c.course_content, c.prerequisites, c.other_details, c.author, c.fees,
                         c.certified_course, c.multiple_schedule, c.schedule, c.redirect_url, COALESCE(c.button_name, "") as button_name,
                         c.created_by, c.created_date, c.is_popular, c.is_exclusive, c.event, c.display_order, c.contact_info,
                         (SELECT GROUP_CONCAT(category_name) FROM tbl_course_category WHERE FIND_IN_SET(category_id, c.category_id)) as category_name,
                         (SELECT GROUP_CONCAT(subject_name) FROM subject WHERE FIND_IN_SET(subject_id, c.subject_id)) as subject_name,
                         (SELECT GROUP_CONCAT(grade_name) FROM grade WHERE FIND_IN_SET(grade_id, c.grade_id)) as grade_name');

        if (!empty($condition)) {
            $builder->where($condition);
        }

        return $this->getResult($builder);
    }

    public function courseSchedule(string $condition): array
    {
        $builder = $this->getBuilder('csc');
        $builder->select('c.course_id, c.course_name, c.seo_title,
                         (SELECT GROUP_CONCAT(category_name) FROM tbl_course_category WHERE FIND_IN_SET(category_id, c.category_id)) as category_name,
                         (SELECT GROUP_CONCAT(subject_name) FROM tbl_subject WHERE FIND_IN_SET(subject_id, c.subject_id)) as subject_name,
                         c.description, c.short_description, c.path, c.lessons, c.overview_content, c.course_content,
                         c.prerequisites, c.other_details, c.author, c.fees, c.certified_course, c.multiple_schedule,
                         c.created_by, c.created_date, c.is_popular, c.is_exclusive,
                         csc.schedule_id, csc.schedule_title, csc.course_start_date, csc.course_end_date,
                         csc.registration_start_date, csc.registration_end_date, csc.payment_type, csc.payment_sub_type,
                         csc.course_type, csc.location_id, csc.cost, csc.discount_amount, csc.actual_cost,
                         csc.total_slots, csc.slots_booked, csc.status, csc.entity_id, csc.edquill_class_id,
                         csc.program_code, cs.edquill_teacher_id, cs.session_id, cs.slot_day, cs.slot_start_time,
                         cs.slot_end_time, cs.instructor,
                         (SELECT GROUP_CONCAT(first_name) FROM tbl_teacher WHERE FIND_IN_SET(teacher_id, cs.instructor)) as instructor_name,
                         COALESCE(eb.branch_name, "") as branch_name')
                ->join('tbl_course as c', 'c.course_id = csc.course_id', 'inner')
                ->join('tbl_course_sessions as cs', 'cs.schedule_id = csc.schedule_id', 'inner')
                ->join('tbl_entity_branch as eb', 'csc.location_id = eb.entity_branch_id', 'left');

        if (!empty($condition)) {
            $builder->where($condition);
        }

        $builder->orderBy('cs.slot_day');

        return $this->getResult($builder);
    }

    public function courseWithoutSchedule(string $condition): array
    {
        $builder = $this->getBuilder();
        $builder->select('*')
                ->where('multiple_schedule', 0);

        if (!empty($condition)) {
            $builder->where($condition);
        }

        return $this->getResult($builder);
    }

    public function getCourseSession(string $condition): array
    {
        $builder = $this->getBuilder('tbl_course_sessions');
        $builder->select('*');

        if (!empty($condition)) {
            $builder->where($condition);
        }

        return $this->getResult($builder);
    }

    public function courseSession(string $condition): array
    {
        $builder = $this->getBuilder('tbl_course_sessions');
        $builder->select('*')
                ->orderBy('slot_day');

        if (!empty($condition)) {
            $builder->where($condition);
        }

        return $this->getResult($builder);
    }

    public function getAdminDetails(string $condition): array
    {
        $builder = $this->getBuilder('tbl_admin');
        $builder->select('*');

        if (!empty($condition)) {
            $builder->where($condition);
        }

        return $this->getResult($builder);
    }

    public function categoryList(string $condition): array
    {
        $builder = $this->getBuilder('tbl_course_category');
        $builder->select('*')
                ->orderBy('category_name');

        if (!empty($condition)) {
            $builder->where($condition);
        }

        return $this->getResult($builder);
    }

    public function getCourseFaq(string $condition): array
    {
        $builder = $this->getBuilder('tbl_course_faq');
        $builder->select('*')
                ->orderBy('display_order');

        if (!empty($condition)) {
            $builder->where($condition);
        }

        return $this->getResult($builder);
    }

    public function getCourseDetail(string $condition): array
    {
        $builder = $this->getBuilder('c');
        $builder->select('c.*,
                         (SELECT GROUP_CONCAT(category_name) FROM tbl_course_category WHERE FIND_IN_SET(category_id, c.category_id)) as category_name,
                         (SELECT GROUP_CONCAT(subject_name) FROM subject WHERE FIND_IN_SET(subject_id, c.subject_id)) as subject_name,
                         (SELECT GROUP_CONCAT(grade_name) FROM grade WHERE FIND_IN_SET(grade_id, c.grade_id)) as grade_name');

        if (!empty($condition)) {
            $builder->where($condition);
        }

        return $this->getResult($builder);
    }

    public function getAllCourse(string $condition): array
    {
        $builder = $this->getBuilder('c');
        $builder->select('c.*,
                         (SELECT GROUP_CONCAT(category_name) FROM tbl_course_category WHERE FIND_IN_SET(category_id, c.category_id)) as category_name,
                         (SELECT GROUP_CONCAT(subject_name) FROM subject WHERE FIND_IN_SET(subject_id, c.subject_id)) as subject_name,
                         (SELECT GROUP_CONCAT(grade_name) FROM grade WHERE FIND_IN_SET(grade_id, c.grade_id)) as grade_name,
                         (SELECT COUNT(*) FROM tbl_course_registration WHERE course_id = c.course_id) as total_registrations');

        if (!empty($condition)) {
            $builder->where($condition);
        }

        return $this->getResult($builder);
    }

    public function courseRating(string $condition): array
    {
        $builder = $this->getBuilder('tbl_course_rating');
        $builder->select('*');

        if (!empty($condition)) {
            $builder->where($condition);
        }

        return $this->getResult($builder);
    }

    public function registrationList(string $condition, int $limit = 0): array
    {
        $builder = $this->getBuilder('cr');
        $builder->select('cr.*, c.course_name, u.first_name, u.last_name, u.email_id')
                ->join('tbl_course as c', 'c.course_id = cr.course_id')
                ->join('tbl_user as u', 'u.user_id = cr.user_id');

        if (!empty($condition)) {
            $builder->where($condition);
        }

        if ($limit > 0) {
            $builder->limit($limit);
        }

        return $this->getResult($builder);
    }

    public function registration(string $condition, int $limit = 0): array
    {
        $builder = $this->getBuilder('cr');
        $builder->select('cr.*, c.course_name, u.first_name, u.last_name, u.email_id')
                ->join('tbl_course as c', 'c.course_id = cr.course_id')
                ->join('tbl_user as u', 'u.user_id = cr.user_id');

        if (!empty($condition)) {
            $builder->where($condition);
        }

        if ($limit > 0) {
            $builder->limit($limit);
        }

        return $this->getResult($builder);
    }

    public function registrationDetail(string $condition): array
    {
        $builder = $this->getBuilder('cr');
        $builder->select('cr.*, c.course_name, u.first_name, u.last_name, u.email_id')
                ->join('tbl_course as c', 'c.course_id = cr.course_id')
                ->join('tbl_user as u', 'u.user_id = cr.user_id');

        if (!empty($condition)) {
            $builder->where($condition);
        }

        return $this->getResult($builder);
    }

    public function locationList(string $condition): array
    {
        $builder = $this->getBuilder('tbl_entity_branch');
        $builder->select('*')
                ->orderBy('branch_name');

        if (!empty($condition)) {
            $builder->where($condition);
        }

        return $this->getResult($builder);
    }

    public function getCourseLocation(string $condition): array
    {
        $builder = $this->getBuilder('tbl_course_location');
        $builder->select('*');

        if (!empty($condition)) {
            $builder->where($condition);
        }

        return $this->getResult($builder);
    }

    public function getCourseSeo(string $condition): array
    {
        $builder = $this->getBuilder('tbl_course_seo');
        $builder->select('*');

        if (!empty($condition)) {
            $builder->where($condition);
        }

        return $this->getResult($builder);
    }

    public function userOrders(string $condition): array
    {
        $builder = $this->getBuilder('tbl_course_orders');
        $builder->select('*');

        if (!empty($condition)) {
            $builder->where($condition);
        }

        return $this->getResult($builder);
    }

    public function getCourseGradeList(array $params): array
    {
        $builder = $this->getBuilder('grade');
        $builder->select('grade_id, grade_name')
                ->where('status', 1)
                ->orderBy('grade_name');

        if (!empty($params['grade_id'])) {
            $builder->whereIn('grade_id', explode(',', $params['grade_id']));
        }

        return $this->getResult($builder);
    }

    public function getCourseSubjectList(array $params): array
    {
        $builder = $this->getBuilder('subject');
        $builder->select('subject_id, subject_name')
                ->where('status', 1)
                ->orderBy('subject_name');

        if (!empty($params['subject_id'])) {
            $builder->whereIn('subject_id', explode(',', $params['subject_id']));
        }

        return $this->getResult($builder);
    }
} 