<?php

namespace App\Models\V1;

use CodeIgniter\Model;

class TeacherModel extends Model
{
    protected $table = 'user';
    protected $primaryKey = 'user_id';
    protected $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect();
    }

    /**
     * Decode base64-encoded annotation (4x base64 encoding)
     * Matches CI3 common_model->annotation()
     */
    private function decodeAnnotation(string $data): string
    {
        if (empty($data) || $data === '[]') {
            return '[]';
        }
        
        try {
            // Decode 4 times as per CI3 logic
            $decoded = base64_decode($data);
            $decoded = base64_decode($decoded);
            $decoded = base64_decode($decoded);
            $decoded = base64_decode($decoded);
            return $decoded;
        } catch (\Exception $e) {
            log_message('error', '❌ Annotation decode error: ' . $e->getMessage());
            return '[]';
        }
    }

    /**
     * Get list of teachers (role_id = 4)
     */
    public function getTeachers($data)
    {
        $builder = $this->db->table('user u');
        $builder->select('u.user_id, u.email_id, up.first_name, up.last_name, u.status, 
                         u.school_id, u.role_id, u.login_type, u.created_by, u.created_date');
        $builder->join('user_profile up', 'up.user_id = u.user_id', 'left');
        $builder->where('u.role_id', 4); // Teacher role
        
        if (isset($data->school_id) && $data->school_id != 0) {
            $builder->where('u.school_id', $data->school_id);
        }
        
        $builder->where('u.status', 1);
        $builder->orderBy('up.first_name', 'ASC');
        
        $results = $builder->get()->getResultArray();
        
        // Add mobile as empty array - frontend expects this field
        foreach ($results as $key => $teacher) {
            $results[$key]['mobile'] = [];
        }
        
        return $results;
    }

    /**
     * Get assignment list for teacher dashboard
     */
    public function assignmentList(array $params): array
    {
        log_message('debug', '📋 TeacherModel::assignmentList called with params: ' . json_encode($params));
        
        $condition = '';
        if (isset($params['type'])) {
            if ($params['type'] == 1) {
                $condition = "AND cc.start_date > CURRENT_DATE()";
            } elseif ($params['type'] == 2) {
                $condition = "AND (cc.start_date <= CURRENT_DATE() AND cc.end_date >= CURRENT_DATE() OR cc.end_date = '0000-00-00')";
            } elseif ($params['type'] == 3) {
                $condition = "AND cc.end_date < CURRENT_DATE() AND cc.end_date != '0000-00-00'";
            }
        }
        
        $orderCondition = 'ORDER BY cc.start_date DESC';
        if(isset($params['sort'])) {
            if ($params['sort'] == 1) {
                $orderCondition = "ORDER BY cc.start_date DESC";
            } elseif ($params['sort'] == 4) {
                $orderCondition = "ORDER BY cc.start_date";
            } elseif ($params['sort'] == 2) {
                $orderCondition = "ORDER BY co.name";
            } elseif ($params['sort'] == 3) {
                $orderCondition = "ORDER BY co.name DESC";
            }
        }
        
        $teacherCondition = "";
        if (isset($params['teacher_id']) && $params['teacher_id'] > 0) {
            $teacherCondition = "AND FIND_IN_SET (cc.class_id, (SELECT GROUP_CONCAT(DISTINCT (class_id)) FROM class_schedule 
                                 WHERE teacher_id = {$params['teacher_id']}))";
        }
        
        $classCondition = '';
        if(isset($params['class_id']) && $params['class_id'] > 0 ){
            $classCondition = "AND cc.class_id = {$params['class_id']}";
        }
        
        $batchCondition = '';
        if(isset($params['batch_id']) && $params['batch_id'] > 0 ) {
            $batchCondition = "AND cc.class_id IN ((SELECT class_id from class where batch_id  = {$params['batch_id']}))";
        }
        
        // Simplified query without complex subqueries
        $query = "SELECT co.content_id, co.name AS content_name, co.school_id, co.subject, co.grade, 
                    cc.class_id,
                    (SELECT class_name FROM class WHERE class_id = cc.class_id) AS class_name,
                    COALESCE(co.profile_url, '') AS profile_url,
                    COALESCE(co.profile_thumb_url, '') AS profile_thumb_url, 
                    COALESCE(co.links,'') AS links,
                    co.status AS content_status, 
                    cc.start_date,
                    cc.end_date, 
                    cc.start_time, 
                    TIME_FORMAT(cc.end_time, '%h:%i:%p') as end_time,
                    cc.created_by, 
                    cc.created_date, 
                    co.content_format,
                    co.content_type,
                    COALESCE(co.tags,'') AS tags,
                    co.access,
                    (CASE 
                        WHEN cc.start_date > CURRENT_DATE() THEN 1 
                        WHEN cc.start_date <= CURRENT_DATE() AND cc.end_date = '0000-00-00' THEN 2
                        WHEN cc.start_date <= CURRENT_DATE() AND cc.end_date >= CURRENT_DATE() THEN 2 
                        WHEN cc.end_date < CURRENT_DATE() THEN 3 
                    END) AS status,
                    '' as student_ids
                    FROM class_content AS cc 
                    LEFT JOIN content AS co ON cc.content_id = co.content_id 
                    LEFT JOIN class AS c ON cc.class_id = c.class_id
                    WHERE cc.school_id={$params['school_id']} 
                    AND c.status = 1
                    AND co.content_type = 2 
                    AND cc.status = 1 
                    $classCondition 
                    $batchCondition
                    $teacherCondition 
                    $condition 
                    $orderCondition";
        
        log_message('debug', '🔍 Assignment List Query: ' . $query);
        
        try {
            $result = $this->db->query($query)->getResultArray();
            log_message('debug', '✅ Assignment List returned ' . count($result) . ' records');
            return $result;
        } catch (\Exception $e) {
            log_message('error', '❌ Assignment List Query Error: ' . $e->getMessage());
            log_message('error', 'Query: ' . $query);
            return [];
        }
    }

    /**
     * Get assessment list for teacher dashboard
     */
    public function assessmentList(array $params): array
    {
        log_message('debug', '📋 TeacherModel::assessmentList called with params: ' . json_encode($params));
        
        $condition = '';
        if (isset($params['type'])) {
            if ($params['type'] == 1) {
                $condition = "AND cc.start_date > CURRENT_DATE()";
            } elseif ($params['type'] == 2) {
                $condition = "AND (cc.start_date <= CURRENT_DATE() AND cc.end_date >= CURRENT_DATE() OR cc.end_date = '0000-00-00')";
            } elseif ($params['type'] == 3) {
                $condition = "AND cc.end_date < CURRENT_DATE() AND cc.end_date != '0000-00-00'";
            }
        }
        
        $orderCondition = 'ORDER BY cc.start_date DESC';
        if(isset($params['sort'])) {
            if ($params['sort'] == 1) {
                $orderCondition = "ORDER BY cc.start_date DESC";
            } elseif ($params['sort'] == 4) {
                $orderCondition = "ORDER BY cc.start_date";
            } elseif ($params['sort'] == 2) {
                $orderCondition = "ORDER BY co.name";
            } elseif ($params['sort'] == 3) {
                $orderCondition = "ORDER BY co.name DESC";
            }
        }
        
        $teacherCondition = "";
        if (isset($params['teacher_id']) && $params['teacher_id'] > 0) {
            $teacherCondition = "AND FIND_IN_SET (cc.class_id, (SELECT GROUP_CONCAT(DISTINCT (class_id)) FROM class_schedule 
                                 WHERE teacher_id = {$params['teacher_id']}))";
        }
        
        $classCondition = '';
        if(isset($params['class_id']) && $params['class_id'] > 0 ){
            $classCondition = "AND cc.class_id = {$params['class_id']}";
        }
        
        $batchCondition = '';
        if(isset($params['batch_id']) && $params['batch_id'] > 0 ) {
            $batchCondition = "AND cc.class_id IN ((SELECT class_id from class where batch_id  = {$params['batch_id']}))";
        }
        
        // Simplified query without complex subqueries
        $query = "SELECT co.content_id, co.name AS content_name, co.school_id, co.subject, co.grade, 
                    cc.class_id,
                    (SELECT class_name FROM class WHERE class_id = cc.class_id) AS class_name,
                    COALESCE(co.profile_url, '') AS profile_url,
                    COALESCE(co.profile_thumb_url, '') AS profile_thumb_url, 
                    COALESCE(co.links,'') AS links,
                    co.status AS content_status, 
                    cc.start_date,
                    cc.end_date, 
                    cc.start_time, 
                    TIME_FORMAT(cc.end_time, '%h:%i:%p') as end_time,
                    cc.created_by, 
                    cc.created_date, 
                    co.content_format,
                    co.content_type,
                    COALESCE(co.tags,'') AS tags,
                    co.access,
                    (CASE 
                        WHEN cc.start_date > CURRENT_DATE() THEN 1 
                        WHEN cc.start_date <= CURRENT_DATE() AND cc.end_date = '0000-00-00' THEN 2
                        WHEN cc.start_date <= CURRENT_DATE() AND cc.end_date >= CURRENT_DATE() THEN 2 
                        WHEN cc.end_date < CURRENT_DATE() THEN 3 
                    END) AS status,
                    '' as student_ids
                    FROM class_content AS cc 
                    LEFT JOIN content AS co ON cc.content_id = co.content_id 
                    LEFT JOIN class AS c ON cc.class_id = c.class_id
                    WHERE cc.school_id={$params['school_id']} 
                    AND c.status = 1
                    AND co.content_type = 3 
                    AND cc.status = 1 
                    $classCondition 
                    $batchCondition
                    $teacherCondition 
                    $condition 
                    $orderCondition";
        
        log_message('debug', '🔍 Assessment List Query: ' . $query);
        
        try {
            $result = $this->db->query($query)->getResultArray();
            log_message('debug', '✅ Assessment List returned ' . count($result) . ' records');
            return $result;
        } catch (\Exception $e) {
            log_message('error', '❌ Assessment List Query Error: ' . $e->getMessage());
            log_message('error', 'Query: ' . $query);
            return [];
        }
    }

    /**
     * Get student names from comma-separated IDs
     */
    public function getStudentsName(string $studentIds): array
    {
        if (empty($studentIds)) {
            return [];
        }
        
        $query = "SELECT GROUP_CONCAT(CONCAT_WS(' ', first_name, last_name) SEPARATOR ',') as student_names 
                  FROM user_profile 
                  WHERE user_id IN ($studentIds)";
        
        $result = $this->db->query($query)->getResultArray();
        
        return $result;
    }

    /**
     * Get list of students assigned to a specific content with their progress
     */
    public function teacherAssign(array $params): array
    {
        log_message('debug', '📋 TeacherModel::teacherAssign called with params: ' . json_encode($params));
        
        $condition = "";
        $condition1 = '';
        $testPoints = '';
        $class_content_id = '';
        
        if (isset($params['class_content_id']) && $params['class_content_id'] > 0) {
            $class_content_id = "AND class_content_id = '{$params['class_content_id']}'";
        }
        
        if ($params['content_format'] == 1) {
            $condition = "(CASE WHEN (SELECT count(question_no) FROM answers WHERE content_id = s.content_id and status = 1) = '' THEN '-'
                          ELSE (SELECT count(question_no) FROM answers WHERE content_id = s.content_id and status = 1) END) as total_question,
                          (CASE WHEN (SELECT SUM(points) FROM answers WHERE content_id = s.content_id AND status =1) != '' THEN
                          (SELECT SUM(points) FROM answers WHERE content_id = s.content_id AND status = 1) ELSE
                          (SELECT points FROM student_content WHERE id = s.id $class_content_id) END) as total_points,";
        } else if ($params['content_format'] == 3) {
            $condition = "(select count(question_no) from text_questions where content_id = s.content_id) as total_question,
                          (CASE WHEN (SELECT SUM(points) FROM text_questions WHERE content_id = s.content_id) != '' THEN
                          (SELECT SUM(points) FROM text_questions WHERE content_id = s.content_id) ELSE
                          (SELECT points FROM student_content WHERE 
                          id = s.id $class_content_id) END) as total_points,";
        }
        
        if (isset($params['type']) && $params['type'] == 0) {
            $condition1 = "";
        } elseif (isset($params['type']) && $params['type'] == 1) {
            $condition1 = "AND s.status = 1";
        } elseif (isset($params['type']) && $params['type'] == 2) {
            $condition1 = "AND s.status = 2";
        } elseif (isset($params['type']) && $params['type'] == 3) {
            $condition1 = "AND s.status IN (3,4,5)";
        }
        
        $query = "SELECT s.id as student_content_id, s.content_id, s.student_id, $condition
                    (CASE WHEN (SELECT SUM(earned_points) FROM student_answers 
                    WHERE student_content_id = s.id) != ''
                    THEN (SELECT SUM(earned_points) FROM student_answers 
                    WHERE student_content_id = s.id) ELSE
                    (SELECT earned_points FROM student_content 
                    WHERE id = s.id $class_content_id) END) AS earned_points,
                    COALESCE(up.profile_url,'') as student_profile,
                    CONCAT_WS(' ', up.first_name, up.last_name) as student_name, s.status,
                    (CASE WHEN (SELECT count(question_no) FROM student_answers 
                    WHERE student_content_id = s.id AND answer_status != 4) = '' THEN '-'
                    ELSE (SELECT count(question_no) FROM student_answers 
                    WHERE student_content_id = s.id AND answer_status != 4) END) AS attend_questions,
                    s.points as total_marks, s.earned_points as marks, 
                    DATE_FORMAT(s.answer_completed_date,'%m-%d-%Y') as answer_completed_date,
                    DATE_FORMAT(s.score_release_date, '%m-%d-%Y') as score_release_date, 
                    COALESCE(s.feedback,'') as teacher_feedback,
                    (SELECT class_id FROM class WHERE class_id = s.class_id) AS class_id,
                    (SELECT class_name FROM class WHERE class_id = s.class_id) AS class_name,
                    (SELECT test_type_id FROM content WHERE content_id = s.content_id) AS test_type_id,
                    (SELECT is_test FROM content WHERE content_id = s.content_id) AS is_test $testPoints
                    FROM student_content as s
                    LEFT JOIN user_profile as up ON up.user_id = s.student_id
                    WHERE s.content_id='{$params['content_id']}' 
                    AND s.class_id = {$params['class_id']} 
                    AND s.student_id IN ((SELECT student_id FROM student_class 
                                         WHERE status IN (1,3) AND class_id = '{$params['class_id']}' 
                                         AND student_id = s.student_id))
                    $condition1 $class_content_id 
                    GROUP BY s.student_id";
        
        log_message('debug', '🔍 Teacher Assign Query: ' . $query);
        
        try {
            $result = $this->db->query($query)->getResultArray();
            log_message('debug', '✅ Teacher Assign returned ' . count($result) . ' students');
            return $result;
        } catch (\Exception $e) {
            log_message('error', '❌ Teacher Assign Query Error: ' . $e->getMessage());
            log_message('error', 'Query: ' . $query);
            return [];
        }
    }

    /**
     * Get student answer list for grading a specific submission
     * Returns student details, answers, annotations, etc.
     */
    public function studentAnswerList(array $params): array
    {
        log_message('debug', '📋 TeacherModel::studentAnswerList called with params: ' . json_encode($params));
        
        $studentList = [];
        
        // Get student name
        if (isset($params['student_id'])) {
            $studentName = $this->db->query("SELECT CONCAT_WS(' ', first_name, last_name) as student_name 
                                            FROM user_profile 
                                            WHERE user_id = '{$params['student_id']}'")->getResultArray();
            
            $studentList['student_id'] = $params['student_id'];
            $studentList['student_name'] = $studentName[0]['student_name'] ?? '';
            $studentList['content_id'] = $params['content_id'];
            $studentList['class_id'] = $params['class_id'];
            
            // Get student content annotation and metadata
            $points = "";
            if (isset($params['is_test']) && $params['is_test'] == 1) {
                $contentId = $params['module_id'];
                $points = ",(SELECT earned_points FROM student_content_module 
                           WHERE student_content_id = {$params['student_content_id']} 
                           AND module_id = {$params['module_id']}) as earned_points,
                          (SELECT points FROM student_content_module 
                           WHERE student_content_id = {$params['student_content_id']} 
                           AND module_id = {$params['module_id']}) as points";
            } else {
                $contentId = $params['content_id'];
            }
            
            $annotationQuery = "SELECT sc.annotation AS student_annotation, sc.status, sc.teacher_annotation,
                                COALESCE(sc.feedback, '') AS feedback, sc.points, sc.earned_points, c.annotation,
                                COALESCE(sc.student_feedback, '') AS overall_student_feedback, c.file_path, c.is_test,
                                COALESCE(c.answerkey_path, '') AS answerkey_path, c.base64_data, c.questionAnnotation,
                                c.allow_answer_key, COALESCE(sc.upload_answer, '') AS upload_answer,
                                COALESCE(sc.answer_sheet_annotation, '') AS answer_sheet_annotation, sc.content_time_taken,
                                sc.class_content_id, cls.class_name, cc.allow_feedback, cc.allow_workspace, cc.show_timer
                                $points
                                FROM student_content sc
                                LEFT JOIN content c ON c.content_id = {$contentId}
                                LEFT JOIN class cls ON cls.class_id = {$params['class_id']}
                                LEFT JOIN class_content cc ON cc.id = sc.class_content_id
                                WHERE sc.id = {$params['student_content_id']}";
            
            $studentContentAnnotation = $this->db->query($annotationQuery)->getResultArray();
            
            if (empty($studentContentAnnotation)) {
                log_message('warning', '⚠️  No student content found for ID: ' . $params['student_content_id']);
                return [
                    'student_id' => $params['student_id'],
                    'content_id' => $params['content_id'],
                    'class_id' => $params['class_id'],
                    'answers' => [],
                    'status' => 0
                ];
            }
            
            $studentList['status'] = $studentContentAnnotation[0]['status'];
            $studentList['feedback'] = $studentContentAnnotation[0]['feedback'];
            $studentList['points'] = $studentContentAnnotation[0]['points'];
            $studentList['earned_points'] = $studentContentAnnotation[0]['earned_points'];
            $studentList['student_content_id'] = $params['student_content_id'];
            
            // Add all annotation and metadata fields
            // File paths stay as strings (they may be base64 encoded - frontend handles decoding)
            $studentList['file_path'] = $studentContentAnnotation[0]['file_path'] ?? '';
            $studentList['answerkey_path'] = $studentContentAnnotation[0]['answerkey_path'] ?? '';
            $studentList['upload_answer'] = $studentContentAnnotation[0]['upload_answer'] ?? '';
            $studentList['base64_data'] = $studentContentAnnotation[0]['base64_data'] ?? '';
            $studentList['is_test'] = $studentContentAnnotation[0]['is_test'] ?? 0;
            $studentList['class_name'] = $studentContentAnnotation[0]['class_name'] ?? '';
            $studentList['content_time_taken'] = $studentContentAnnotation[0]['content_time_taken'] ?? 0;
            
            // Annotation fields - decode base64 and parse JSON to return as arrays
            // These are stored as base64(base64(base64(base64(JSON))))
            $annotation = $studentContentAnnotation[0]['annotation'] ?? '';
            if (!empty($annotation) && $annotation !== '[]') {
                $studentList['annotation'] = json_decode($this->decodeAnnotation($annotation)) ?? [];
            } else {
                $studentList['annotation'] = [];
            }
            
            $student_annotation = $studentContentAnnotation[0]['student_annotation'] ?? '';
            if (!empty($student_annotation) && $student_annotation !== '[]') {
                $studentList['student_annotation'] = json_decode($this->decodeAnnotation($student_annotation)) ?? [];
            } else {
                $studentList['student_annotation'] = [];
            }
            
            $teacher_annotation = $studentContentAnnotation[0]['teacher_annotation'] ?? '';
            if (!empty($teacher_annotation) && $teacher_annotation !== '[]') {
                $studentList['teacher_annotation'] = json_decode($this->decodeAnnotation($teacher_annotation)) ?? [];
            } else {
                $studentList['teacher_annotation'] = [];
            }
            
            $question_annotation = $studentContentAnnotation[0]['questionAnnotation'] ?? '';
            if (!empty($question_annotation) && $question_annotation !== '[]') {
                $studentList['question_annotation'] = json_decode($this->decodeAnnotation($question_annotation)) ?? [];
            } else {
                $studentList['question_annotation'] = [];
            }
            
            $answer_sheet_annotation = $studentContentAnnotation[0]['answer_sheet_annotation'] ?? '';
            if (!empty($answer_sheet_annotation) && $answer_sheet_annotation !== '[]') {
                $studentList['answer_sheet_annotation'] = json_decode($this->decodeAnnotation($answer_sheet_annotation)) ?? [];
            } else {
                $studentList['answer_sheet_annotation'] = [];
            }
            
            $studentList['overall_student_feedback'] = $studentContentAnnotation[0]['overall_student_feedback'] ?? '';
            $studentList['student_feedback'] = $studentContentAnnotation[0]['overall_student_feedback'] ?? '';
            $studentList['allow_answer_key'] = $studentContentAnnotation[0]['allow_answer_key'] ?? 0;
            $studentList['allow_feedback'] = $studentContentAnnotation[0]['allow_feedback'] ?? 0;
            $studentList['allow_workspace'] = $studentContentAnnotation[0]['allow_workspace'] ?? 0;
            $studentList['show_timer'] = $studentContentAnnotation[0]['show_timer'] ?? 0;
            
            // Get student answers based on content format
            if (isset($params['content_format']) && $params['content_format'] == 1) {
                // Standard format - get all answers with nested structure
                $answersQuery = "SELECT a.answer_id, a.question_no, COALESCE(a.question,'') AS question,
                                COALESCE(a.answer_instructions,'') AS answer_instructions,
                                COALESCE(a.section_heading, '') AS heading,
                                a.display_order, a.content_id, a.question_type_id, a.has_sub_question,
                                COALESCE(a.sub_question_no, '') AS sub_question_no, a.options,
                                a.answer, COALESCE(a.editor_answer,'') as question_editor_answer, a.auto_grade, a.points,
                                COALESCE(a.match_case, '') AS match_case,
                                COALESCE(sa.workarea, '') AS workarea,
                                COALESCE(sa.student_answer, '') AS student_answer,
                                COALESCE(sa.editor_answer, '') AS editor_answer,
                                COALESCE(sa.earned_points, '0') AS earned_points,
                                COALESCE(sa.feedback, '') AS feedback,
                                COALESCE(sa.correction_status, '0') AS correction_status
                                FROM answers a 
                                LEFT JOIN student_answers sa ON a.answer_id = sa.answer_id 
                                    AND sa.student_content_id = {$params['student_content_id']}
                                WHERE a.content_id = {$params['content_id']}
                                AND a.status = 1
                                ORDER BY a.display_order";
                
                $allAnswers = $this->db->query($answersQuery)->getResultArray();
                
                // Group by section heading
                $groupedAnswers = [];
                foreach ($allAnswers as $answer) {
                    $heading = $answer['heading'] ?? '';
                    if (!isset($groupedAnswers[$heading])) {
                        $groupedAnswers[$heading] = [
                            'heading' => $heading,
                            'section' => [[
                                'sub_questions' => []
                            ]]
                        ];
                    }
                    $groupedAnswers[$heading]['section'][0]['sub_questions'][] = $answer;
                }
                
                $studentList['answers'] = array_values($groupedAnswers);
            } else {
                // Other formats - return empty array
                $studentList['answers'] = [];
            }
            
            log_message('debug', '✅ Student Answer List returning ' . count($studentList['answers']) . ' answer groups');
            
            return $studentList;
        }
        
        // Return empty structure if no student_id
        return [
            'student_id' => 0,
            'content_id' => 0,
            'class_id' => 0,
            'answers' => [],
            'status' => 0,
            'annotation' => [],
            'student_annotation' => [],
            'teacher_annotation' => [],
            'question_annotation' => [],
            'answer_sheet_annotation' => []
        ];
    }

    /**
     * Get student assessment/assignment submissions for grading
     * @param array $params Request parameters
     * @param int $contentType 2 = Assignment, 3 = Assessment
     * @return array
     */
    public function studentAssessment(array $params, int $contentType): array
    {
        log_message('debug', '📋 TeacherModel::studentAssessment called with contentType: ' . $contentType . ', params: ' . json_encode($params));
        
        $condition = '';
        $studentId = '';
        $teacherId = '';
        $leftJoin = '';
        $dateSearch = '';
        $scoreReleaseDate = '';
        
        // Teacher filtering
        if(isset($params['teacher_id']) && $params['teacher_id'] > 0){
            $teacherId = "AND cs.teacher_id='{$params['teacher_id']}'";
            $leftJoin = "LEFT JOIN class_schedule as cs ON cs.class_id = s.class_id";
        }
        
        // Search by student name
        if(isset($params['search']) && $params['search'] != "") {
            $queryStudentConcat = "SELECT user_id FROM user_profile WHERE CONCAT_WS(' ', first_name,last_name) LIKE '%{$params['search']}%' OR first_name LIKE '%{$params['search']}%' OR last_name LIKE '%{$params['search']}%'";
            $studentId = "AND s.student_id IN ({$queryStudentConcat})";
        }
        
        // Date-based filtering (type: 0=all, 1=upcoming, 2=in-progress, 3=completed)
        if(isset($params['type']) && $params['type'] == 1){
            $condition = "AND s.content_start_date > CURRENT_DATE()";
        } elseif(isset($params['type']) && $params['type'] == 2){
            $condition = "AND s.content_start_date <= CURRENT_DATE() AND s.content_end_date >= CURRENT_DATE()";
        } elseif(isset($params['type']) && $params['type'] == 3){
            $condition = "AND s.content_end_date < CURRENT_DATE()";
        }
        
        // Status-based filtering
        // Always sort by most recent activity first (sys_time tracks all updates)
        $orderCondition = 'ORDER BY s.sys_time';
        $condition1 = '';
        if(isset($params['status'])) {
            if($params['status'] == 0){
                // All submissions
                $scoreReleaseDate = ",DATE_FORMAT(s.score_release_date, '%m-%d-%Y') as score_release_date";
                $condition1 = "AND s.student_content_status IN (1,2,3,4,5,6)";
            } elseif($params['status'] == 1){
                // Submitted (needs grading)
                $condition1 = "AND s.student_content_status IN (4,6)";
                if(isset($params['date_search']) && $params['date_search'] != "") {
                    $dateSearch = "AND DATE_FORMAT(s.answer_completed_date, '%m-%d-%Y') = '{$params['date_search']}'";
                }
            } elseif($params['status'] == 2){
                // Graded
                $condition1 = "AND s.student_content_status = 5";
                if(isset($params['date_search']) && $params['date_search'] != "") {
                    $dateSearch = "AND DATE_FORMAT(s.answer_completed_date, '%m-%d-%Y') = '{$params['date_search']}'";
                }
            } elseif($params['status'] == 3){
                // Score released
                $scoreReleaseDate = ",DATE_FORMAT(s.score_release_date, '%m-%d-%Y') as score_release_date";
                $condition1 = "AND s.student_content_status = 3";
                if(isset($params['date_search']) && $params['date_search'] != "") {
                    $dateSearch = "AND DATE_FORMAT(s.score_release_date, '%m-%d-%Y') = '{$params['date_search']}'";
                }
            } elseif($params['status'] == 4) {
                // Overdue - sort by how many days overdue (most overdue first), then by recent activity
                $condition1 = "AND s.student_content_status IN (1,2)";
                $orderCondition = "ORDER BY overDue DESC, s.sys_time";
            }
        }
        
        // Class filtering
        $condition2 = '';
        if(isset($params['class_id']) && $params['class_id'] != 0){
            $condition2 = "AND s.class_id = {$params['class_id']}";
        }
        
        // Batch filtering
        $batchCondition = '';
        if(isset($params['batch_id']) && $params['batch_id'] > 0 ) {
            $batchCondition = "AND FIND_IN_SET({$params['batch_id']}, c.batch_id)";
        }
        
        // Content name search
        $searchContent = "";
        if(isset($params['search_content']) && $params['search_content'] != "") {
            $searchContent = "AND s.content_name LIKE '%{$params['search_content']}%'";
        }
        
        $dateCondition = "AND s.content_start_date <= CURRENT_DATE()";
        
        $query = "SELECT s.student_content_id, s.student_id, COALESCE(up.profile_url,'') as student_profile,
                    CONCAT_WS(' ', up.first_name, up.last_name) as student_name,
                    s.content_id, s.content_name, s.class_id, s.class_name, s.content_start_date, s.content_end_date, s.content_type,
                    s.student_content_status, s.content_format, s.total_score, s.obtained_score, 
                    DATE_FORMAT(s.answer_completed_date,'%m-%d-%Y') as answer_completed_date,
                    (CASE WHEN CURRENT_DATE > s.content_end_date AND s.content_end_date != '0000-00-00' 
                     AND s.student_content_status < 3 THEN DATEDIFF(CURRENT_DATE(), s.content_end_date) ELSE 0 END) as overDue,
                    DATE_FORMAT(s.correction_completed_date,'%m-%d-%Y') as correction_completed_date,
                    s.student_content_status as status, s.score_released, s.sys_time $scoreReleaseDate
                    FROM student_work as s 
                    LEFT JOIN class as c ON s.class_id = c.class_id 
                    LEFT JOIN user_profile as up ON up.user_id = s.student_id
                    $leftJoin 
                    WHERE c.school_id = {$params['school_id']} 
                    AND s.content_type = $contentType
                    $teacherId $condition2 $condition $condition1 $studentId $batchCondition $searchContent $dateSearch
                    $dateCondition 
                    $orderCondition DESC";
        
        log_message('debug', '🔍 Student Assessment Query: ' . $query);
        
        try {
            $result = $this->db->query($query)->getResultArray();
            log_message('debug', '✅ Student Assessment returned ' . count($result) . ' records');
            return $result;
        } catch (\Exception $e) {
            log_message('error', '❌ Student Assessment Query Error: ' . $e->getMessage());
            log_message('error', 'Query: ' . $query);
            return [];
        }
    }
}
