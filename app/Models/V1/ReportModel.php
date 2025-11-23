<?php

namespace App\Models\V1;

use CodeIgniter\Model;
use CodeIgniter\API\ResponseTrait;

class ReportModel extends BaseModel
{
    use ResponseTrait;

    protected $table = 'reports';
    protected $allowedFields = [
        'report_id',
        'class_id',
        'content_id',
        'student_id',
        'school_id',
        'report_type',
        'report_data',
        'status',
        'created_date',
        'modified_date'
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function classList($params)
    {
        $builder = $this->getBuilder('class c');
        $builder->select('DISTINCT(c.class_id) AS class_id, c.class_name, c.start_date');
        $builder->join('class_content cc', 'c.class_id = cc.class_id', 'left');
        $builder->join('content co', 'cc.content_id = co.content_id', 'left');
        $builder->where('c.school_id', $params['school_id']);
        $builder->where('c.status', 1);
        $builder->where('c.class_status', 0);
        $builder->where('c.start_date <= CURRENT_DATE()');
        $builder->where('cc.status', 1);

        if ($params['role_id'] == 4 && isset($params['user_id']) && $params['user_id'] > 0) {
            $builder->join('class_schedule cs', 'cs.class_id = c.class_id', 'left');
            $builder->where('cs.teacher_id', $params['user_id']);
            $builder->orderBy('c.start_date', 'DESC');
        }

        return $this->getResult($builder);
    }

    public function classContent($params, $type)
    {
        $builder = $this->getBuilder('class_content c');
        $builder->select('c.content_id, co.name AS content_name, co.content_format, c.start_date, c.end_date');
        $builder->join('content co', 'c.content_id = co.content_id', 'left');
        $builder->where('c.class_id', $params['class_id']);
        $builder->where('c.status', 1);

        if ($type == 1) {
            $builder->where('co.content_type', 2);
        } elseif ($type == 2) {
            $builder->where('co.content_type', 3);
        }

        if (isset($params['from_date'])) {
            if ($params['from_date'] != '' && $params['to_date'] != '') {
                $builder->groupStart()
                    ->where('c.end_date >=', $params['from_date'])
                    ->where('c.end_date <=', $params['to_date'])
                    ->orWhere('c.end_date', '0000-00-00')
                    ->groupEnd();
            }
        }

        return $this->getResult($builder);
    }

    public function studentList($contentId, $classId)
    {
        $builder = $this->getBuilder('student_content c');
        $builder->select('c.content_id, c.student_id, co.name as content_name');
        $builder->select("DATE_FORMAT(c.start_date,'%m-%d-%Y') AS content_date");
        $builder->select("(CASE WHEN (SELECT SUM(points) FROM text_questions WHERE content_id IN (c.content_id)) != '' 
            THEN (SELECT SUM(points) FROM text_questions WHERE content_id IN (c.content_id)) 
            ELSE (SELECT SUM(points) FROM answers WHERE content_id IN (c.content_id) AND status = 1) END) AS total_mark");
        $builder->select("(SELECT SUM(earned_points) FROM student_answers 
            WHERE content_id IN (c.content_id) AND student_id = c.student_id AND class_id = {$classId}) AS student_mark");
        $builder->select('c.class_id, c.status, c.points, c.earned_points');
        $builder->join('class_content cc', 'c.class_id = cc.class_id AND c.content_id = cc.content_id', 'left');
        $builder->join('content co', 'c.content_id = co.content_id', 'left');
        $builder->whereIn('c.content_id', explode(',', $contentId));
        $builder->whereIn('co.content_id', explode(',', $contentId));
        $builder->whereIn('cc.content_id', explode(',', $contentId));
        $builder->where('cc.class_id', $classId);
        $builder->where('c.class_id', $classId);
        $builder->where('cc.status', 1);

        return $this->getResult($builder);
    }

    public function classStudent($params)
    {
        $builder = $this->getBuilder('student_class sc');
        $builder->select('sc.student_id, CONCAT_WS(" ", u.first_name, u.last_name) AS student_name');
        $builder->join('user_profile u', 'sc.student_id = u.user_id', 'left');
        $builder->where('sc.class_id', $params['class_id']);

        return $this->getResult($builder);
    }

    public function studentMarkList($studentId, $contentId, $classId)
    {
        $builder = $this->getBuilder('student_content c');
        $builder->select('c.id as student_content_id, c.content_id, c.student_id');
        $builder->select('CONCAT_WS(" ", u.first_name, u.last_name) AS student_name');
        $builder->select('co.content_format');
        $builder->select("(CASE WHEN (SELECT SUM(points) FROM text_questions WHERE content_id = {$contentId}) != '' 
            THEN (SELECT SUM(points) FROM text_questions WHERE content_id = {$contentId}) 
            ELSE (SELECT SUM(points) FROM answers WHERE content_id = {$contentId} AND status = 1) END) AS total_mark");
        $builder->select("(SELECT SUM(earned_points) FROM student_answers 
            WHERE content_id = c.content_id AND student_id = c.student_id) AS student_mark");
        $builder->select('c.class_id, c.status, c.points, c.earned_points');
        $builder->join('class_content cc', 'c.class_id = cc.class_id AND c.content_id = cc.content_id', 'left');
        $builder->join('content co', 'c.content_id = co.content_id', 'left');
        $builder->join('user_profile u', 'c.student_id = u.user_id', 'left');
        $builder->where('c.student_id', $studentId);
        $builder->where('c.class_id', $classId);
        $builder->where('c.content_id', $contentId);
        $builder->where('cc.status', 1);

        return $this->getResult($builder);
    }

    public function studentContentData($params)
    {
        $builder = $this->getBuilder('student_content c');
        $builder->select('c.content_id, c.student_id, co.name as content_name');
        $builder->select("DATE_FORMAT(c.start_date,'%m-%d-%Y') AS content_date");
        $builder->select("(CASE WHEN (SELECT SUM(points) FROM text_questions WHERE content_id = c.content_id) != '' 
            THEN (SELECT SUM(points) FROM text_questions WHERE content_id = c.content_id) 
            ELSE (SELECT SUM(points) FROM answers WHERE content_id = c.content_id AND status = 1) END) AS total_mark");
        $builder->select("(SELECT SUM(earned_points) FROM student_answers 
            WHERE content_id = c.content_id AND student_id = c.student_id) AS student_mark");
        $builder->select('c.class_id, c.status, c.points, c.earned_points');
        $builder->join('class_content cc', 'c.class_id = cc.class_id AND c.content_id = cc.content_id', 'left');
        $builder->join('content co', 'c.content_id = co.content_id', 'left');
        $builder->where('c.student_id', $params['student_id']);
        $builder->where('c.class_id', $params['class_id']);
        $builder->where('cc.status', 1);

        return $this->getResult($builder);
    }

    public function contentData($contentId, $params)
    {
        $builder = $this->getBuilder('content c');
        $builder->select('c.content_id, c.name as content_name, c.content_format');
        $builder->select("(CASE WHEN (SELECT SUM(points) FROM text_questions WHERE content_id = {$contentId}) != '' 
            THEN (SELECT SUM(points) FROM text_questions WHERE content_id = {$contentId}) 
            ELSE (SELECT SUM(points) FROM answers WHERE content_id = {$contentId} AND status = 1) END) AS total_mark");
        $builder->where('c.content_id', $contentId);

        return $this->getResult($builder);
    }

    public function getStudentGrades($params)
    {
        $builder = $this->getBuilder('user_profile_details upd');
        $builder->select('upd.grade_id, g.grade_name');
        $builder->join('grade g', 'upd.grade_id = g.grade_id', 'left');
        $builder->where('upd.user_id', $params['student_id']);
        $builder->where('upd.school_id', $params['school_id']);
        $builder->where('upd.status', 1);
        $builder->where('upd.grade_id >', 0); // Only get grades that are set (not 0)

        return $this->getResult($builder);
    }

    public function getGradeSubjects($params)
    {
        // Get distinct subjects from classes that match the grade
        // The class table has 'subject' and 'grade' fields
        $builder = $this->getBuilder('class c');
        $builder->select('c.subject as subject_id, s.subject_name');
        $builder->distinct();
        $builder->join('subject s', 'c.subject = s.subject_id', 'left');
        // Check if grade_id is in the grade field (which may be comma-separated or single value)
        $builder->where("(FIND_IN_SET('{$params['grade_id']}', c.grade) > 0 OR c.grade = '{$params['grade_id']}')", null, false);
        $builder->where('c.school_id', $params['school_id']);
        $builder->where('c.status', 1);
        $builder->where('c.subject >', 0); // Only get classes with valid subject
        $builder->orderBy('s.subject_name', 'ASC');

        return $this->getResult($builder);
    }

    public function getSubjectClasses($params, $subjectId)
    {
        $builder = $this->getBuilder('class c');
        $builder->select('c.class_id, c.class_name');
        // The class table uses 'subject' field (not 'subject_id'), and it may be comma-separated
        $builder->where("(FIND_IN_SET('{$subjectId}', c.subject) > 0 OR c.subject = '{$subjectId}')", null, false);
        $builder->where('c.school_id', $params['school_id']);
        $builder->where('c.status', 1);

        return $this->getResult($builder);
    }

    public function getClassContent($params, $classId)
    {
        $studentId = isset($params['student_id']) ? (int)$params['student_id'] : null;
        
        $builder = $this->getBuilder('class_content cc');
        $builder->select('cc.content_id, c.name as content_name, c.content_format, c.content_type, cc.start_date');
        
        // Calculate total_score (total points available)
        $builder->select("(CASE 
            WHEN (SELECT SUM(points) FROM text_questions WHERE content_id = cc.content_id) IS NOT NULL 
                AND (SELECT SUM(points) FROM text_questions WHERE content_id = cc.content_id) != '' 
            THEN (SELECT SUM(points) FROM text_questions WHERE content_id = cc.content_id) 
            ELSE COALESCE((SELECT SUM(points) FROM answers WHERE content_id = cc.content_id AND status = 1), 0) 
        END) AS total_score", false);
        
        // Join with student_content to get student-specific data if student_id is provided
        if ($studentId) {
            $builder->select('COALESCE(sc.status, 1) as status');
            $builder->select('COALESCE(sc.points, 0) as points');
            $builder->select('COALESCE(sc.earned_points, 0) as your_score');
            $builder->join('student_content sc', 'sc.class_content_id = cc.id AND sc.student_id = ' . (int)$studentId, 'left');
        } else {
            $builder->select('1 as status');
            $builder->select('0 as points');
            $builder->select('0 as your_score');
        }
        
        $builder->join('content c', 'cc.content_id = c.content_id', 'left');
        $builder->where('cc.class_id', $classId);
        $builder->where('cc.status', 1);

        return $this->getResult($builder);
    }

    public function getStudents($params)
    {
        $builder = $this->getBuilder('student_class sc');
        $builder->select('sc.student_id, CONCAT_WS(" ", u.first_name, u.last_name) AS student_name');
        $builder->join('user_profile u', 'sc.student_id = u.user_id', 'left');
        $builder->where('sc.class_id', $params['class_id']);

        return $this->getResult($builder);
    }

    public function getStudentContents($params, $date)
    {
        $builder = $this->getBuilder('student_content sc');
        $builder->select('sc.content_id, c.name as content_name, c.content_format');
        $builder->select("DATE_FORMAT(sc.start_date,'%m-%d-%Y') AS content_date");
        $builder->join('content c', 'sc.content_id = c.content_id', 'left');
        $builder->where('sc.student_id', $params['student_id']);
        $builder->where('sc.class_id', $params['class_id']);
        if ($date) {
            $builder->where('sc.start_date', $date);
        }

        return $this->getResult($builder);
    }

    public function contentReport($classId, $contentId, $studentId, $type, $id)
    {
        $builder = $this->getBuilder('student_content sc');
        $builder->select('sc.*, c.name as content_name, c.content_format');
        $builder->select('CONCAT_WS(" ", u.first_name, u.last_name) AS student_name');
        $builder->join('content c', 'sc.content_id = c.content_id', 'left');
        $builder->join('user_profile u', 'sc.student_id = u.user_id', 'left');
        $builder->where('sc.class_id', $classId);
        $builder->where('sc.content_id', $contentId);
        $builder->where('sc.student_id', $studentId);
        if ($type == 'student') {
            $builder->where('sc.id', $id);
        }

        return $this->getResult($builder);
    }

    public function studentReport($classId, $studentId, $contentId)
    {
        $builder = $this->getBuilder('student_content sc');
        $builder->select('sc.*, c.name as content_name, c.content_format');
        $builder->select('CONCAT_WS(" ", u.first_name, u.last_name) AS student_name');
        $builder->join('content c', 'sc.content_id = c.content_id', 'left');
        $builder->join('user_profile u', 'sc.student_id = u.user_id', 'left');
        $builder->where('sc.class_id', $classId);
        $builder->where('sc.student_id', $studentId);
        $builder->where('sc.content_id', $contentId);

        return $this->getResult($builder);
    }

    public function contentLists($params, $contentId)
    {
        $builder = $this->getBuilder('class_content cc');
        $builder->select('cc.content_id, c.name as content_name, c.content_format');
        $builder->select("DATE_FORMAT(cc.start_date,'%m-%d-%Y') AS start_date");
        $builder->select("DATE_FORMAT(cc.end_date,'%m-%d-%Y') AS end_date");
        $builder->join('content c', 'cc.content_id = c.content_id', 'left');
        $builder->where('cc.class_id', $params['class_id']);
        $builder->where('cc.content_id', $contentId);
        $builder->where('cc.status', 1);

        return $this->getResult($builder);
    }

    public function getContentName($params, $contentId)
    {
        $builder = $this->getBuilder('content c');
        $builder->select('c.name as content_name, c.content_format');
        $builder->where('c.content_id', $contentId);

        return $this->getResult($builder);
    }

    public function getUsers($params)
    {
        $builder = $this->getBuilder('user u');
        $builder->select('u.user_id, u.email_id, u.role_id');
        $builder->select('CONCAT_WS(" ", up.first_name, up.last_name) AS user_name');
        $builder->join('user_profile up', 'u.user_id = up.user_id', 'left');
        $builder->where('u.school_id', $params['school_id']);
        if (isset($params['role_id'])) {
            $builder->where('u.role_id', $params['role_id']);
        }

        return $this->getResult($builder);
    }

    public function getTotalAttendedQuestions($classId, $contentId, $studentId)
    {
        $builder = $this->getBuilder('student_answers sa');
        $builder->select('COUNT(*) as total_attended');
        $builder->where('sa.class_id', $classId);
        $builder->where('sa.content_id', $contentId);
        $builder->where('sa.student_id', $studentId);

        return $this->getResult($builder);
    }

    public function getContentNames($contentIds, $classId)
    {
        $builder = $this->getBuilder('content c');
        $builder->select('c.content_id, c.name as content_name, c.content_format');
        $builder->join('class_content cc', 'c.content_id = cc.content_id', 'left');
        $builder->whereIn('c.content_id', explode(',', $contentIds));
        $builder->where('cc.class_id', $classId);
        $builder->where('cc.status', 1);

        return $this->getResult($builder);
    }

    public function studentLists($params)
    {
        $builder = $this->getBuilder('user u');
        $builder->select('u.user_id as student_id, CONCAT_WS(" ", up.first_name, up.last_name) AS student_name');
        $builder->join('user_profile up', 'u.user_id = up.user_id', 'left');
        $builder->join('user_profile_details upd', 'u.user_id = upd.user_id', 'left');
        $builder->where('u.role_id', 5); // Students role
        $builder->where('u.school_id', $params['school_id']);
        $builder->where('upd.status', 1);
        
        // Filter by teacher if role_id is 4 (Teacher) and user_id is provided
        if (isset($params['role_id']) && $params['role_id'] == 4 && isset($params['user_id']) && $params['user_id'] > 0) {
            $builder->join('student_class sc', 'sc.student_id = u.user_id', 'left');
            $builder->join('class_schedule cs', 'cs.class_id = sc.class_id', 'left');
            $builder->where('cs.teacher_id', $params['user_id']);
            $builder->where('sc.status', 1);
        }
        
        $builder->orderBy('up.last_name', 'ASC');
        $builder->orderBy('up.first_name', 'ASC');
        $builder->distinct();

        return $this->getResult($builder);
    }
} 