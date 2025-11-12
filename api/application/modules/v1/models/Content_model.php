<?php

class Content_model extends CI_Model
{

    function __construct()
    {
        parent::__construct();
        $this->load->database();
//        $this->db->query("SET GLOBAL time_zone = '{$this->config->item('time_zone')}'");
        ini_set('memory_limit' ,'-1');
    }

    public function checkContent($params,$type) {
        $contentName = $this->db->escape($params['name']);
        if ($params['role_id'] == 2 || $params['role_id'] == 6) {
            if ($type == 'edit') {
                $result = $this->db->query("SELECT content_id FROM content 
                                            WHERE status != 4 AND 
                                            content_id = {$params['content_id']}")->result_array();
                return $result;
            }
        } else {
            if ($type == 'edit') {
                $result = $this->db->query("SELECT content_id FROM content 
                                            WHERE status != 4 AND 
                                            content_id = {$params['content_id']}")->result_array();
                return $result;
            }
        }
        $result = $this->db->query("SELECT content_id FROM content 
                                    WHERE name = {$contentName} AND created_by = {$params['user_id']}
                                    AND school_id = '{$params['school_id']}' AND status != 4")->result_array();
        return $result;
    }
    public function answer($params){
        $ans=$this->db->query("SELECT a.answer_id, a.question_no, a.question,
                                a.content_id, a.question_type_id, a.has_sub_question, 
                                COALESCE (a.sub_question_no, '') AS sub_question_no, COALESCE (a.options, '') AS options, 
                                a.answer, a.auto_grade,a.points, 
                                COALESCE (a.difficulty, '') AS difficulty, 
                                COALESCE (a.allow_exact_match, '') AS allow_exact_match,
                                COALESCE (a.allow_any_text, '') AS allow_any_text, 
                                COALESCE (a.match_case, '') AS match_case, 
                                COALESCE (a.minimum_line, '') AS minimum_line 
                                FROM answers a WHERE a.content_id='{$params['content_id']}' AND status = 1")->result_array();
        return $ans;
    }
    public function checkContentId($id){
        $check = $this->db->query("SELECT content_id,content_format,download FROM content WHERE content_id = '{$id}'")->result_array();
        return $check;
    }

    public function checkAnswers($params) {
        $result = $this->db->query("SELECT content_id FROM answers WHERE content_id = '{$params}' AND status = 1")->result_array();
        return $result;
    }

    //    public function contentList($params){
    //
    //        $contentList=$this->db->query("SELECT c.content_id, c.name, c.grade, c.subject, c.school_id, c.content_path,
    //                                       c.content_type, c.tags, c.is_file, c.access, c.status, a.answer_id,
    //                                       a.question_no, a.question_type_id,a.has_sub_question,a.sub_question_no, a.options, a.answer, a.points,
    //                                       a.difficulty, a.allow_exact_match, a.allow_any_text, a.match_case,
    //                                       a.minimum_line FROM content c LEFT JOIN answers a ON
    //                                       c.content_id = a.content_id WHERE c.content_id = a.content_id AND c.school_id = '{$params['school_id']}'")->result_array();
    //        return $contentList;
    //    }
    public function contentList($params)
    {
        $condition = '';
        if (isset($params['grade']) && count($params['grade']) > 0) {
            $params['grade'] = implode(',', $params['grade']);
            $condition = "AND FIND_IN_SET({$params['grade']}, c.grade)";
        }
        $condition1 = '';
        if (isset($params['subject']) && count($params['subject']) > 0) {
            $params['subject'] = implode(',', $params['subject']);
            $condition1 = "AND FIND_IN_SET({$params['subject']},c.subject)";
        }
        $condition2 = '';
        if (isset($params['tags']) && count($params['tags']) > 0) {
            $params['tags'] = implode(',', $params['tags']);
            $condition2 = "AND FIND_IN_SET('{$params['tags']}',c.tags)";
        }
        $condition3 = '';
        if (isset($params['filter']) && $params['filter'] == "authored_by_me") {
            $condition3 = "AND created_by = {$params['user_id']}";
        }
        $condition4 = '';
        if (isset($params['library'])) {
            if ($params['library'] == "entire") {
                $condition4 = '';
            } elseif ($params['library'] == "resources") {
                $condition4 = "AND c.content_type = 1";
            } elseif ($params['library'] == "assessments") {
                $condition4 = "AND c.content_type = 2";
            } elseif ($params['library'] == "assignments") {
                $condition4 = "AND c.content_type = 3";
            }
        }
        $limit = '';
        if (isset($params['page']) && $params['page'] != "") {
            $recordsPerPage = ($params['page'] - 1) * $params['record_per_page'];
            $page = $params['page'] * $params['record_per_page'];
            $limit = "limit $recordsPerPage,$page";
        }
        $type = '';
        if ($params['type'] == "list") {
            $type = "AND status IN (1,5)";
        } elseif ($params['type'] == "mydraft") {
            $type = "AND c.status = 5 AND c.created_by = {$params['user_id']}";
        }
        $contentList = $this->db->query("SELECT c.content_id, c.name,COALESCE (c.description,'') AS description, c.grade, 
                                      (SELECT GROUP_CONCAT(grade_name) FROM grade 
                                       WHERE FIND_IN_SET(grade_id, c.grade)) AS grade_name, c.subject, 
                                       (SELECT GROUP_CONCAT(subject_name) FROM subject 
                                       WHERE FIND_IN_SET(subject_id, c.subject)) AS subject_name, c.school_id, 
                                       COALESCE (c.file_path,'') AS file_path,
                                       COALESCE (c.links,'') AS links, 
                                       COALESCE (c.file_text,'') AS file_text, c.annotation,c.questionAnnotation,c.content_type, 
                                       COALESCE (c.tags, '')AS tags,c.content_format, c.access, c.status, 
                                       COALESCE (c.profile_url,'') AS profile_url, 
                                       COALESCE (c.profile_thumb_url, '') AS profile_thumb_url,
                                       COALESCE(
                                       NULLIF(
                                           (SELECT TRIM(CONCAT_WS(' ',first_name,last_name)) FROM user_profile
                                            WHERE user_id = c.created_by),
                                           ''
                                       ),
                                       (SELECT user_name FROM user WHERE user_id = c.created_by),
                                       CAST(c.created_by AS CHAR)
                                   ) AS created_by, c.created_date FROM content c 
                                       WHERE c.school_id='{$params['school_id']}' $type $condition4 $condition3 $condition
                                       $condition1 $condition2 
                                       ORDER BY c.content_id DESC $limit")->result_array();
        return $contentList;
    }

    public function contentListNew($params){
        $condition = '';
        if(isset($params['grade']) && count($params['grade']) > 0) {
            $params['grade'] = implode(',',$params['grade']);
            $condition = "AND FIND_IN_SET({$params['grade']}, c.grade)";
        }
        $condition1 = '';
        if (isset($params['subject']) && count($params['subject']) > 0) {
            $params['subject'] = implode(',',$params['subject']);
            $condition1 = "AND FIND_IN_SET({$params['subject']},c.subject)";
        }
        $condition2 = '';
        if(isset($params['tags']) && count($params['tags']) > 0) {
            $params['tags'] = implode(',',$params['tags']);
            $condition2 = "AND FIND_IN_SET('{$params['tags']}',c.tags)";
        }
        $condition3 = '';
        if(isset($params['filter']) && $params['filter'] == "authored_by_me") {
            $condition3 = "AND created_by = {$params['user_id']}";
        }
        $condition4 = '';
        if(isset($params['library'])) {
            if($params['library'] == "entire") {
                $condition4 = '';
            } elseif($params['library'] == "resources") {
                $condition4 = "AND c.content_type = 1";
            } elseif($params['library'] == "assessments") {
                $condition4 = "AND c.content_type = 2";
            } elseif($params['library'] == "assignments") {
                $condition4 = "AND c.content_type = 3";
            }
        }
        $limit = '';
        if (isset($params['page']) && $params['page'] != "") {
            $recordsPerPage = ($params['page'] - 1) * $params['record_per_page'];
            $page = $params['page'] * $params['record_per_page'];
            $limit = "limit $recordsPerPage,$page";
        }
        $type = '';
        if($params['type'] == "list") {
            $type = "AND status IN (1,5)";
        } elseif($params['type'] == "mydraft") {
            $type = "AND c.status = 5 AND c.created_by = {$params['user_id']}";
        }
        $contentList=$this->db->query("SELECT c.content_id, c.name,COALESCE (c.description,'') AS description, c.grade, 
                                       (SELECT GROUP_CONCAT(grade_name) FROM grade 
                                       WHERE FIND_IN_SET(grade_id, c.grade)) AS grade_name, c.subject, 
                                       (SELECT GROUP_CONCAT(subject_name) FROM subject 
                                       WHERE FIND_IN_SET(subject_id, c.subject)) AS subject_name, c.school_id, 
                                       COALESCE (c.file_path,'') AS file_path,
                                       COALESCE (c.links,'') AS links, 
                                       COALESCE (c.file_text,'') AS file_text, c.annotation,c.questionAnnotation,c.content_type, 
                                       COALESCE (c.tags, '')AS tags,c.content_format, c.access, c.status, 
                                       COALESCE (c.profile_url,'') AS profile_url, 
                                       COALESCE (c.profile_thumb_url, '') AS profile_thumb_url,
                                       CONCAT_WS(' ',u.first_name,u.last_name) AS created_by, c.created_date 
                                       FROM content c 
                                       LEFT JOIN user_profile u on c.created_by = u.user_id
                                       WHERE c.school_id='{$params['school_id']}' $type $condition4 $condition3 $condition
                                       $condition1 $condition2 
                                       ORDER BY c.content_id DESC $limit")->result_array();
        return $contentList;
    }

    public function questionTypeList() {
        $list = $this->db->query("SELECT question_type_id, resource_type_id, question_type, image_path, icon_path
                                  FROM question_types 
                                  WHERE question_uploads =1")->result_array();
        return $list;
    }

    public function subjectName($data) {
        $result = $this->db->query("SELECT subject_name FROM subject 
                                    WHERE FIND_IN_SET('{$data['subject']}',subject_id) 
                                    AND school_id = {$data['school_id']}")->result_array();
        return $result;
    }

    public function tagSearch($tagName, $userId, $contentId)
    {
        $condition = '';
        $tagName = $this->db->escape($tagName);
        if ($contentId != '') {
            $condition = "AND content_id = '{$contentId}'";
        }
        $result = $this->db->query("SELECT id FROM tags 
                                    WHERE tag_name = {$tagName} AND user_id = '{$userId}' $condition")->result_array();
        return $result;
    }

    public function contentIdList($params)
    {
        $condition = '';
        $autoReview = $schoolAutoReview = $LastAnsweredQuestion = $className = $allowFeedback = $allowWorkspace = '';
        $notes = '';
        $answerKey = '';
        $teacherVersion = '';
        $class_content_id = '';
        $class_table = '';
        if ($params['role_id'] == 2) {
            $statuCondition = "";
        } else {
            $statuCondition = "AND status IN (1,5)";
        }
        if (isset($params['class_content_id']) && $params['class_content_id'] > 0) {
            $class_content_id = "AND class_content_id = {$params['class_content_id']}";
            $class_table = "AND id = '{$params['class_content_id']}'";
            $allowFeedback = ",(SELECT allow_feedback FROM class_content WHERE id = '{$params['class_content_id']}') as allow_feedback";
            $allowWorkspace = ",(SELECT allow_workspace FROM class_content WHERE id = '{$params['class_content_id']}') as allow_workspace";
        }
        if (isset($params['class_id'])) {
            $notes = ",(SELECT  COALESCE (notes, '') FROM class_content WHERE class_id = '{$params['class_id']}' AND content_id = '{$params['content_id']}'  AND status = 1 $class_table limit 0,1) as notes";
        }
        if (isset($params['student_id'])) {
            $download = ",(SELECT downloadable FROM class_content WHERE content_id = '{$params['content_id']}' AND class_id = '{$params['class_id']}' AND status = 1 $class_table limit 0,1)  as download";
        } else {
            $download = ",(SELECT download FROM content WHERE content_id = '{$params['content_id']}') as download";
        }
        if (isset($params['student_id']) && $params['student_id'] > 0) {
            $condition = "COALESCE((SELECT student_feedback FROM student_content 
                          WHERE id = {$params['student_content_id']}),'') as overall_student_feedback,
                          COALESCE((SELECT feedback FROM student_content 
                          WHERE id = {$params['student_content_id']}),'') as teacher_feedback,
                          COALESCE((SELECT upload_answer FROM student_content 
                          WHERE id = {$params['student_content_id']}),'') as upload_answer,";
            $autoReview = ",COALESCE((SELECT cc.auto_review FROM class_content as cc
                                   LEFT JOIN student_content as sc ON cc.id = sc.class_content_id
                                   WHERE sc.id = {$params['student_content_id']}),'') AS auto_review";
            $LastAnsweredQuestion = ",(SELECT laq_id FROM student_content WHERE id = {$params['student_content_id']}) AS laq_id";
        }
        if (isset($params['school_id']) && $params['school_id'] > 0) {
            $schoolAutoReview = "COALESCE((SELECT value FROM admin_settings_school WHERE name = 'allow_autograde_settings' AND school_id = {$params['school_id']}),'') as allow_autograde,";
        }
        if (isset($params['class_id']) && $params['class_id'] > 0) {
            $className = "COALESCE((SELECT class_name FROM class WHERE class_id = {$params['class_id']}),'') as class_name,";
        }
        if ($params['platform'] != 'ios') {
            $answerKey = ",COALESCE((SELECT answerkey_path from content where content_id = '{$params['content_id']}'),'') AS answerkey_path,c.allow_answer_key";
            $teacherVersion = ",COALESCE (c.teacher_version,'') AS teacher_version";
        }
        $result = $this->db->query("SELECT c.content_id, $condition c.name, 
                                    COALESCE (c.description,'') AS description, c.grade, 
                                    COALESCE ((SELECT GROUP_CONCAT(grade_name) FROM grade 
                                    WHERE FIND_IN_SET(grade_id, c.grade)),'') AS grade_name, c.subject, 
                                    COALESCE ((SELECT GROUP_CONCAT(subject_name) FROM subject 
                                    WHERE FIND_IN_SET(subject_id, c.subject)),'') AS subject_name, c.school_id, 
                                    COALESCE (c.file_path,'') AS file_path,
                                    COALESCE (c.links,'') AS links, c.content_type,
                                    COALESCE (c.file_text, '') AS file_text, c.annotation,c.questionAnnotation,
                                    COALESCE (c.tags,'') AS tags,c.content_format, c.access, c.status, 
                                    COALESCE (c.profile_url,'') AS profile_url, $schoolAutoReview $className
                                    COALESCE (c.profile_thumb_url, '') AS profile_thumb_url,
                                    (CASE WHEN c.created_by = {$params['user_id']} THEN 1 ELSE 0 END) 
                                    AS edit_status, c.editor_type,
                                    (CASE WHEN c.content_format = 1 THEN 
                                    (SELECT COUNT(question_no) FROM answers WHERE content_id = c.content_id AND status = 1)
                                    WHEN c.content_format = 3 THEN 
                                    (SELECT COUNT(question_id) FROM text_questions 
                                    WHERE content_id = c.content_id) ELSE 0 END) AS no_of_questions,
                                   (CASE WHEN c.content_format = 1 THEN 
                                   (COALESCE ((SELECT SUM(points) FROM answers WHERE content_id = c.content_id AND status = 1),''))
                                    WHEN c.content_format = 3 THEN 
                                    (COALESCE ((SELECT SUM(points) FROM text_questions 
                                    WHERE content_id = c.content_id),'')) ELSE 0 END) as total_points,
                                   (SELECT CONCAT_WS(' ',first_name,last_name) FROM user_profile
                                    WHERE user_id = c.created_by) AS created_by, c.created_date $download $notes $answerKey $teacherVersion $autoReview $LastAnsweredQuestion $allowFeedback $allowWorkspace
                                   FROM content c 
                                    WHERE c.content_id = {$params['content_id']} $statuCondition
                                    ")->row_array();

        //         print_r($this->db->last_query());
        return $result;
    }

    public function contentIdListNew($params)
    {
        $condition = '';
        $autoReview = '';
        $notes = '';
        $leftjoin = '';
        $leftjoin1 = '';
        $condition1 = '';
        $condition2 = '';
        $answerKey = '';
        $teacherVersion = '';

        if (isset($params['class_id']) || isset($params['student_id'])) {
            $leftjoin1 = "LEFT JOIN class_content cc on c.content_id = cc.content_id";
            $condition2 = "AND cc.class_id = '{$params['class_id']}' AND cc.content_id = '{$params['content_id']}'";
        }
        if (isset($params['class_id'])) {
            $notes = "COALESCE (cc.notes, '') as notes,";
        }
        if (isset($params['student_id'])) {
            $download = "cc.downloadable as download";
        } else {
            $download = "c.download as download";
        }

        if (isset($params['student_id']) && $params['student_id'] > 0) {
            $leftjoin = "LEFT JOIN student_content sc on c.content_id = sc.content_id";
            $condition1 = "AND sc.student_id = '{$params['student_id']}' AND sc.class_id = '{$params['class_id']}' AND sc.content_id = '{$params['content_id']}'";
            $condition = "COALESCE(sc.student_feedback,'') as overall_student_feedback,
                          COALESCE(sc.feedback,'') as teacher_feedback,
                          COALESCE(sc.upload_answer,'') as upload_answer,";
        }
        if (isset($params['school_id']) && $params['school_id'] > 0) {
            $autoReview = "COALESCE((SELECT value FROM admin_settings_school WHERE name = 'allow_autograde_settings' AND school_id = '{$params['school_id']}'),'') as allow_autograde,";
        }
        if ($params['platform'] != 'ios') {
            $answerKey = "COALESCE(c.answerkey_path,'') AS answerkey_path,";
            $teacherVersion = "COALESCE (c.teacher_version,'') AS teacher_version,";
        }
        $result = $this->db->query("SELECT c.content_id, c.name, 
                                    COALESCE (c.description,'') AS description, c.grade,
                                    COALESCE ((SELECT GROUP_CONCAT(grade_name) FROM grade 
                                    WHERE FIND_IN_SET(grade_id, c.grade)),'') AS grade_name, c.subject, 
                                    COALESCE ((SELECT GROUP_CONCAT(subject_name) FROM subject 
                                    WHERE FIND_IN_SET(subject_id, c.subject)),'') AS subject_name, c.school_id, 
                                    COALESCE (c.file_path,'') AS file_path,
                                    COALESCE (c.links,'') AS links, c.content_type,
                                    COALESCE (c.file_text, '') AS file_text, c.annotation,c.questionAnnotation,
                                    COALESCE (c.tags,'') AS tags,c.content_format, c.access, c.status, 
                                    COALESCE (c.profile_url,'') AS profile_url, 
                                    COALESCE (c.profile_thumb_url, '') AS profile_thumb_url,
                                    (CASE WHEN c.created_by = '{$params['user_id']}' THEN 1 ELSE 0 END) 
                                    AS edit_status, c.editor_type,
                                    (CASE WHEN c.content_format = 1 THEN 
                                    COUNT(a.question_no)
                                    WHEN c.content_format = 3 THEN 
                                    COUNT(tq.question_id) ELSE 0 END) AS no_of_questions,
                                    (CASE WHEN c.content_format = 1 THEN 
                                    COALESCE (SUM(a.points),'')
                                    WHEN c.content_format = 3 THEN 
                                    COALESCE (SUM(tq.points),'') ELSE 0 END) as total_points,
                                    CONCAT_WS(' ',u.first_name,u.last_name) AS created_by, 
                                    c.created_date, $condition $autoReview $notes $answerKey $teacherVersion $download
                                    FROM content c 
                                    LEFT JOIN answers a on c.content_id = a.content_id
                                    LEFT JOIN text_questions tq on c.content_id = tq.content_id
                                    LEFT JOIN user_profile u on c.created_by = u.user_id
                                    $leftjoin $leftjoin1
                                    WHERE c.content_id = '{$params['content_id']}' $condition1 $condition2
                                    AND c.status IN (1,5)")->row_array();

        // print_r($this->db->last_query());
        return $result;
    }

    public function answerList($params)
    {
        $condition = "";
        if (isset($params['student_id']) && $params['student_id'] > 0) {
            $condition = ",COALESCE((SELECT COALESCE(student_answer,'') FROM student_answers WHERE student_content_id = {$params['student_content_id']} AND answer_id = a.answer_id),'') as student_answer,
                            COALESCE((SELECT COALESCE(jiixdata,'') FROM student_answers WHERE student_content_id = {$params['student_content_id']} AND answer_id = a.answer_id),'') as jiixdata,
                            COALESCE((SELECT COALESCE(roughdata,'') FROM student_answers WHERE student_content_id = {$params['student_content_id']} AND answer_id = a.answer_id),'') as roughdata,
                            COALESCE((SELECT COALESCE(rough_image_url,'') FROM student_answers WHERE student_content_id = {$params['student_content_id']} AND answer_id = a.answer_id),'') as rough_image_url,
                            COALESCE((SELECT COALESCE(rough_image_thumb_url,'') FROM student_answers WHERE student_content_id = {$params['student_content_id']} AND answer_id = a.answer_id),'') as rough_image_thumb_url,
                            COALESCE((SELECT COALESCE(student_answer_image,'') FROM student_answers WHERE student_content_id = {$params['student_content_id']} AND answer_id = a.answer_id),'') as student_answer_image,
                            COALESCE((SELECT COALESCE(student_roughdata,'') FROM student_answers WHERE student_content_id = {$params['student_content_id']} AND answer_id = a.answer_id),'') as student_roughdata,
                           (SELECT status FROM student_content WHERE id = {$params['student_content_id']}) as student_content_status,
                           COALESCE((SELECT COALESCE(annotation,'') FROM student_content WHERE id = {$params['student_content_id']}),'') as student_annotation,
                           COALESCE((select suggestion_query from student_suggestions where content_id={$params['content_id']} AND class_id = {$params['class_id']} AND student_id = {$params['student_id']} AND answer_id = a.answer_id),'') as student_feedback,
                           COALESCE((SELECT COALESCE(editor_answer,'') FROM student_answers WHERE student_content_id = {$params['student_content_id']} AND answer_id = a.answer_id),'') as student_editor_answer";
        }
        $result = $this->db->query("SELECT a.answer_id, a.question_no, 
                                    COALESCE (a.question,'') AS question, a.mob_options, 
                                    COALESCE (a.section_heading,'') AS heading,a.display_order,a.content_id, a.question_type_id,
                                     a.has_sub_question,a.page_no,
                                    COALESCE (a.sub_question_no, '') AS sub_question_no, COALESCE (a.options, '') AS options, a.array, a.answer,
                                    COALESCE (a.editor_answer) as editor_answer, a.auto_grade,  a.points, 
                                    COALESCE (a.difficulty, '') AS difficulty, 
                                    COALESCE (a.allow_exact_match, '') AS allow_exact_match,
                                    COALESCE (a.allow_any_text, '') AS allow_any_text, 
                                    COALESCE (a.match_case, '') AS match_case, 
                                    COALESCE (a.answer_explanation, '') as answer_explanation,
                                    COALESCE (a.minimum_line, '') AS minimum_line $condition
                                    FROM answers a
                                    WHERE a.content_id = '{$params['content_id']}' AND a.status = 1
                                    ORDER BY a.answer_id ASC")->result_array();
        return $result;
    }

    public function studentAnswerList($params)
    {
        $limit = '';
        if ($params['platform'] == 'ios' && isset($params['page_no']) && $params['page_no'] != "" && isset($params['records_per_page']) && $params['records_per_page'] != "") {
            $recordsPerPage = ($params['page_no'] - 1) * $params['records_per_page'];
            $page = $params['records_per_page'];
            $limit = "limit $recordsPerPage,$page";
        }
        $result = $this->db->query("(SELECT a.answer_id, a.question_no, 
                                    COALESCE (a.question,'') AS question, a.mob_options, 
                                    COALESCE (a.section_heading,'') AS heading,a.display_order,a.content_id, a.question_type_id,a.page_no,
                                     a.has_sub_question,
                                    COALESCE (a.sub_question_no, '') AS sub_question_no,COALESCE (a.options,'') AS options, a.array, a.answer,
                                    COALESCE (a.editor_answer) as editor_answer, a.auto_grade,  a.points, 
                                    COALESCE (a.difficulty, '') AS difficulty, 
                                    COALESCE (a.allow_exact_match, '') AS allow_exact_match,
                                    COALESCE (a.allow_any_text, '') AS allow_any_text, 
                                    COALESCE (a.match_case, '') AS match_case, 
                                    COALESCE (a.answer_explanation, '') as answer_explanation,
                                    COALESCE (a.minimum_line, '') AS minimum_line,COALESCE(sa.student_answer,'') as student_answer,
                                    COALESCE(sa.jiixdata,'') as jiixdata, COALESCE(sa.roughdata,'') as roughdata,
                                    COALESCE(sa.workarea,'') as workarea,
                                    COALESCE(sa.rough_image_url,'') as rough_image_url, COALESCE(sa.rough_image_thumb_url,'') as rough_image_thumb_url,
                                    COALESCE(sa.student_answer_image,'') as student_answer_image, COALESCE(sa.student_roughdata,'') as student_roughdata,
                                    COALESCE(a.editor_answer,'') as editor_answer,
                                    COALESCE(sa.editor_answer,'') as student_editor_answer,
                                    sa.answer_attended,sa.answer_status
                                    FROM answers a LEFT JOIN student_answers sa ON a.answer_id = sa.answer_id
                                    WHERE a.content_id = '{$params['content_id']}' AND a.status = 1
                                    AND sa.student_content_id = {$params['student_content_id']}
                                    ORDER BY a.answer_id ASC $limit) UNION
                                    (SELECT a.answer_id, a.question_no, 
                                    COALESCE (a.question,'') AS question, a.mob_options, 
                                    COALESCE (a.section_heading,'') AS heading,a.display_order,a.content_id, a.question_type_id,a.page_no,
                                     a.has_sub_question,
                                    COALESCE (a.sub_question_no, '') AS sub_question_no, COALESCE (a.options,'') AS options, a.array, a.answer,
                                    COALESCE (a.editor_answer) as editor_answer, a.auto_grade,  a.points, 
                                    COALESCE (a.difficulty, '') AS difficulty, 
                                    COALESCE (a.allow_exact_match, '') AS allow_exact_match,
                                    COALESCE (a.allow_any_text, '') AS allow_any_text, 
                                    COALESCE (a.match_case, '') AS match_case, 
                                    COALESCE (a.answer_explanation, '') as answer_explanation,
                                    COALESCE (a.minimum_line, '') AS minimum_line,'' as student_answer,
                                    '' as jiixdata, '' as roughdata, '' as workarea,
                                    '' as rough_image_url, '' as rough_image_thumb_url,
                                    '' as student_answer_image, '' as student_roughdata,
                                    '' as editor_answer,                                   
                                    '' as student_editor_answer,
                                    '' as answer_attended,
                                    '' as answer_status
                                    FROM answers a WHERE a.content_id = {$params['content_id']} and a.status = 1 
                                    and a.answer_id not in (select answer_id from student_answers where student_content_id = {$params['student_content_id']} ORDER BY a.answer_id ASC) $limit)")
            ->result_array();
        return $result;
    }

    public function studentCompletedAnswerList($params)
    {
        $limit = '';
        if ($params['platform'] == 'ios' && isset($params['page_no']) && $params['page_no'] != "" && isset($params['records_per_page']) && $params['records_per_page'] != "") {
            $recordsPerPage = ($params['page_no'] - 1) * $params['records_per_page'];
            $page = $params['records_per_page'];
            $limit = "limit $recordsPerPage,$page";
        }
        $result = $this->db->query("(SELECT a.answer_id, a.question_no, 
                                    COALESCE (a.question,'') AS question, a.mob_options, 
                                    COALESCE (a.section_heading,'') AS heading,a.display_order,a.content_id, a.question_type_id,a.page_no,
                                     a.has_sub_question,
                                    COALESCE (a.sub_question_no, '') AS sub_question_no,COALESCE (a.options,'') AS options, a.array, a.answer,
                                    COALESCE (a.editor_answer) as editor_answer, a.auto_grade,  a.points, 
                                    COALESCE (a.difficulty, '') AS difficulty, 
                                    COALESCE (a.allow_exact_match, '') AS allow_exact_match,
                                    COALESCE (a.allow_any_text, '') AS allow_any_text, 
                                    COALESCE (a.match_case, '') AS match_case, 
                                    COALESCE (a.answer_explanation, '') as answer_explanation,
                                    COALESCE (a.minimum_line, '') AS minimum_line,COALESCE(sa.student_answer,'') as student_answer,
                                    COALESCE(sa.jiixdata,'') as jiixdata, COALESCE(sa.roughdata,'') as roughdata,
                                    COALESCE(sa.workarea,'') as workarea,
                                    COALESCE(sa.rough_image_url,'') as rough_image_url, COALESCE(sa.rough_image_thumb_url,'') as rough_image_thumb_url,
                                    COALESCE(sa.student_answer_image,'') as student_answer_image, COALESCE(sa.student_roughdata,'') as student_roughdata,
                                    COALESCE(a.editor_answer,'') as editor_answer,
                                    COALESCE(sa.editor_answer,'') as student_editor_answer,
                                    sa.answer_attended,sa.answer_status
                                    FROM answers a LEFT JOIN student_answers sa ON a.answer_id = sa.answer_id
                                    WHERE a.content_id = '{$params['content_id']}' AND a.status = 1
                                    AND sa.student_content_id = {$params['student_content_id']}
                                    ORDER BY a.answer_id ASC $limit) UNION (SELECT a.answer_id, a.question_no, 
                                    COALESCE (a.question,'') AS question, a.mob_options, 
                                    COALESCE (a.section_heading,'') AS heading,a.display_order,a.content_id, a.question_type_id,a.page_no,
                                     a.has_sub_question,
                                    COALESCE (a.sub_question_no, '') AS sub_question_no, COALESCE (a.options,'') AS options, a.array, a.answer,
                                    COALESCE (a.editor_answer) as editor_answer, a.auto_grade,  a.points, 
                                    COALESCE (a.difficulty, '') AS difficulty, 
                                    COALESCE (a.allow_exact_match, '') AS allow_exact_match,
                                    COALESCE (a.allow_any_text, '') AS allow_any_text, 
                                    COALESCE (a.match_case, '') AS match_case, 
                                    COALESCE (a.answer_explanation, '') as answer_explanation,
                                    COALESCE (a.minimum_line, '') AS minimum_line,'' as student_answer,
                                    '' as jiixdata, '' as roughdata, '' as workarea,
                                    '' as rough_image_url, '' as rough_image_thumb_url,
                                    '' as student_answer_image, '' as student_roughdata,
                                    '' as editor_answer,                                   
                                    '' as student_editor_answer,
                                    '' as answer_attended,
                                    '' as answer_status
                                    FROM answers a WHERE a.content_id = {$params['content_id']} and a.status = 1 
                                    and a.answer_id not in (select answer_id from student_answers where student_content_id = {$params['student_content_id']} ORDER BY a.answer_id ASC)$limit)")->result_array();
        return $result;
    }

    public function noAnswerList($params)
    {
        $limit = '';
        if ($params['platform'] == 'ios' && isset($params['page_no']) && $params['page_no'] != "" && isset($params['records_per_page']) && $params['records_per_page'] != "") {
            $recordsPerPage = ($params['page_no'] - 1) * $params['records_per_page'];
            $page = $params['records_per_page'];
            $limit = "limit $recordsPerPage,$page";
        }
        $result = $this->db->query("SELECT a.answer_id, a.question_no, 
                                    COALESCE (a.question,'') AS question, a.mob_options, 
                                    COALESCE (a.section_heading,'') AS heading,a.display_order,a.content_id, a.question_type_id,a.page_no,
                                     a.has_sub_question,
                                    COALESCE (a.sub_question_no, '') AS sub_question_no, COALESCE (a.options,'') AS options, a.array, a.answer,
                                    COALESCE (a.editor_answer) as editor_answer, a.auto_grade,  a.points, 
                                    COALESCE (a.difficulty, '') AS difficulty, 
                                    COALESCE (a.allow_exact_match, '') AS allow_exact_match,
                                    COALESCE (a.allow_any_text, '') AS allow_any_text, 
                                    COALESCE (a.match_case, '') AS match_case, 
                                    COALESCE (a.answer_explanation, '') as answer_explanation,
                                    COALESCE (a.minimum_line, '') AS minimum_line,
                                    '' as student_answer, '' as workarea,
                                    '' as jiixdata, '' as roughdata,
                                    '' as rough_image_url, '' as rough_image_thumb_url,
                                    '' as student_answer_image, '' as student_roughdata,
                                    '' as editor_answer,
                                    '' as student_editor_answer
                                    FROM answers a
                                    WHERE a.content_id = '{$params['content_id']}' AND a.status = 1
                                    ORDER BY a.answer_id ASC $limit")->result_array();
        return $result;
    }

    public function studentSuggestions($params)
    {
        $result = $this->db->query("SELECT answer_id, suggestion_query as student_feedback
                                    FROM student_suggestions WHERE content_id={$params['content_id']} AND 
                                    class_id = {$params['class_id']} AND student_id = {$params['student_id']}")->result_array();
        return $result;
    }

    public function studentStatus($params)
    {
        $result = $this->db->query("SELECT status FROM student_content WHERE id = '{$params['student_content_id']}'")->row_array();
        return $result;
    }

    public function questionList($params)
    {
        $list = $this->db->query("SELECT question_type_id,
                                COALESCE (editor_context, '') AS editor_context, question_no,sub_question_no,
                                has_sub_question,question,options,answer,heading_option,multiple_response,points,
                                exact_match,hint,explanation,word_limit,scoring_instruction,source,target,
                                created_by,created_date FROM text_qestions 
                                WHERE content_id='{$params['content_id']}'")->result_array();
        return $list;
    }
    public function classList($params)
    {
        $list = $this->db->query("SELECT class_id,student_id,
                                 (SELECT concat_ws(' ',up.first_name,up.last_name) FROM user_profile up 
                                  WHERE up.user_id = student_id) AS name FROM student_class
                                  WHERE class_id IN ({$params['class_id']}) AND status = 1")->result_array();
        return $list;
    }

    public function classListNew($params)
    {
        $list = $this->db->query("SELECT s.student_id, concat_ws(' ',u.first_name,u.last_name) AS name
                                  FROM student_class s
                                  LEFT JOIN user_profile u on s.student_id = u.user_id
                                  WHERE s.class_id = '{$params['class_id']}' AND s.status = 1")->result_array();
        return $list;
    }

    public function allSchoolStudents($schoolId)
    {
        $list = $this->db->query("SELECT upd.user_id as student_id,
                                 (SELECT concat_ws(' ',up.first_name,up.last_name) FROM user_profile up 
                                  WHERE up.user_id = upd.user_id) AS name FROM user_profile_details upd
                                  WHERE upd.school_id = '{$schoolId}' AND upd.status = 1 
                                  AND user_id IN ((SELECT GROUP_CONCAT(user_id) FROM user WHERE role_id = 5 AND user_id = upd.user_id))")->result_array();
        return $list;
    }

    public function allSchoolStudentsNew($schoolId)
    {
        $list = $this->db->query("SELECT upd.user_id as student_id, concat_ws(' ',u.first_name,u.last_name)  AS name
                                  FROM user_profile_details upd
                                  LEFT JOIN user_profile u on upd.user_id = u.user_id
                                  WHERE upd.school_id = $schoolId AND upd.status = 1 
                                  AND upd.user_id IN ((SELECT GROUP_CONCAT(user_id) FROM user WHERE role_id = 5 AND user_id = upd.user_id))")->result_array();
        return $list;
    }

    public function checkquestion($params)
    {
        $check = $this->db->query("SELECT question_id FROM text_questions WHERE question_id={$params}")->result_array();
        return $check;
    }
    public function answersNumber($params)
    {
        $result = $this->db->query("SELECT count(question_no) AS no_of_questions FROM answers 
                                    WHERE content_id = '{$params['content_id']}' AND status = 1")->result_array();
        return $result;
    }

    public function contentCreatorList($data)
    {
        $result = $this->db->query("SELECT u.user_id AS content_user_id, CONCAT_WS(' ',up.first_name,up.last_name) 
                                    AS name FROM user AS u
                                    LEFT JOIN user_profile as up ON u.user_id = up.user_id 
                                    WHERE u.role_id = 3 AND FIND_IN_SET({$data['school_id']},u.school_id)")->result_array();
        return $result;
    }

    public function getStudentClass($classId)
    {
        $list = $this->db->query("SELECT student_id FROM student_class 
                                  WHERE class_id = {$classId} AND status = 1")->result_array();
        return $list;
    }

    public function getClassId($classCode)
    {
        $list = $this->db->query("SELECT class_id,school_id,created_by FROM class 
                                  WHERE class_code = '{$classCode}'")->row_array();
        return $list;
    }

    public function getContentName($contentName)
    {
        $list = $this->db->query("SELECT content_id FROM content 
                                  WHERE name = '{$contentName}'")->row_array();
        return $list;
    }

    public function getCheckExists($classId, $contentId)
    {
        $list = $this->db->query("SELECT id FROM class_content 
                                  WHERE class_id = {$classId} AND content_id = {$contentId}")->result_array();
        return $list;
    }


    public function textList($params)
    {
        $condition = "";
        if (isset($params['student_id']) && $params['student_id'] > 0 && isset($params['content_format']) && $params['content_format'] == 3) {
            $condition = ",COALESCE((SELECT COALESCE(options,'') FROM student_answers WHERE student_content_id = {$params['student_content_id']} AND answer_id = tq.question_id),'') as student_options,
                          COALESCE((SELECT COALESCE(student_answer,'') FROM student_answers WHERE student_content_id = {$params['student_content_id']} AND answer_id = tq.question_id),'') as student_answer,
                          COALESCE((SELECT COALESCE(editor_answer,'') FROM student_answers WHERE student_content_id = {$params['student_content_id']} AND answer_id = tq.question_id),'') as student_editor_answer,
                          (SELECT status FROM student_content WHERE id = {$params['student_content_id']}) as student_content_status,
                          COALESCE((select suggestion_query from student_suggestions where content_id={$params['content_id']} AND class_id = {$params['class_id']} AND student_id = {$params['student_id']} AND answer_id = tq.question_id),'') as student_feedback,
                          COALESCE((SELECT COALESCE(rough_image_url,'') FROM student_answers WHERE student_content_id = {$params['student_content_id']} AND answer_id = tq.question_id),'') as rough_image_url,
                          COALESCE((SELECT COALESCE(rough_image_thumb_url,'') FROM student_answers WHERE student_content_id = {$params['student_content_id']} AND answer_id = tq.question_id),'') as rough_image_thumb_url,
                          COALESCE((SELECT COALESCE(student_answer_image,'') FROM student_answers WHERE student_content_id = {$params['student_content_id']} AND answer_id = tq.question_id),'') as student_answer_image,
                          COALESCE((SELECT COALESCE(jiixdata,'') FROM student_answers WHERE student_content_id = {$params['student_content_id']} AND answer_id = tq.question_id ),'') as jiixdata,
                          COALESCE((SELECT COALESCE(roughdata,'') FROM student_answers WHERE student_content_id = {$params['student_content_id']} AND answer_id = tq.question_id),'') as roughdata,
                          COALESCE((SELECT COALESCE(student_roughdata,'') FROM student_answers WHERE student_content_id = {$params['student_content_id']} AND answer_id = tq.question_id),'') as student_roughdata,
                          COALESCE((SELECT COALESCE(is_correct,'') FROM student_answers WHERE student_content_id = {$params['student_content_id']} AND answer_id = tq.question_id),'') as is_correct,
                          COALESCE((SELECT COALESCE(no_of_attempt,'') FROM student_answers WHERE student_content_id = {$params['student_content_id']} AND answer_id = tq.question_id),0) as no_of_attempt,
                          COALESCE((SELECT COALESCE(time_taken,'') FROM student_answers WHERE student_content_id = {$params['student_content_id']} AND answer_id = tq.question_id),0) as time_taken,
                          COALESCE((SELECT  COALESCE(CASE WHEN marked_review = 1 THEN 'true' WHEN marked_review = 0 THEN 'false' ELSE NULL END,'') AS markedAsReview FROM student_answers WHERE student_content_id = {$params['student_content_id']} AND answer_id = tq.question_id),0) as markedAsReview,
                          COALESCE((SELECT COALESCE(optionsCopy,'') FROM student_answers WHERE student_content_id = {$params['student_content_id']} AND answer_id = tq.question_id),'') as optionsCopy";
        }
        $list = $this->db->query("SELECT tq.question_id,tq.content_id, tq.question_type_id, tq.sub_question_type_id, 
                                COALESCE (tq.editor_context, '') AS editor_context, tq.editor_type, tq.question_no,
                                tq.sub_question_no,tq.has_sub_question,tq.question,tq.options,answer,tq.level,tq.heading_option,
                                tq.multiple_response,tq.audo_grade,tq.points,tq.exact_match,tq.hint,tq.explanation,tq.word_limit,
                                tq.scoring_instruction, COALESCE (tq.editor_answer,'') as editor_answer,
                                COALESCE (tq.source, '') AS source,
                                COALESCE ( tq.target, '') AS target,tq.passage_id,tq.created_by,tq.created_date,
                                COALESCE (p.passage, '') AS passage,COALESCE (p.title, '') AS passage_title $condition
                                FROM text_questions tq
                                LEFT JOIN passage as p ON tq.passage_id = p.passage_id 
                                WHERE tq.content_id='{$params['content_id']}'")->result_array();
        return $list;
    }
    public function sortMaster($params)
    {
        if (isset($params['search_name'])) {
            $params['search_name'] = $this->db->escape_str($params['search_name']);
        }
        if (isset($params['exact_search']) && $params['exact_search'] == 1) {
            $search = "='{$params['search_name']}'";
        } else {
            $search = "LIKE '%{$params['search_name']}%'";
        }
        $gradeCondition = '';
        $teacherSubjectCondition = '';
        $searchCondition = '';
        $getTeacherAllSubject = $this->db->query("SELECT subject, individual_teacher FROM user_profile_details
                                                    WHERE user_id = {$params['user_id']} AND school_id = {$params['school_id']}")->row_array();
        if ($params['role_id'] == 4 && $getTeacherAllSubject['individual_teacher'] != 1 && $getTeacherAllSubject['subject'] != '') {
            if (count($params['subject']) == 0) {
                $getTeacherSubject = explode(',', $getTeacherAllSubject['subject']);
                $queryTeacherSubject = '';
                $g = count($getTeacherSubject);
                for ($i = 0; $i < count($getTeacherSubject); $i++) {
                    $queryTeacherSubject = $queryTeacherSubject . "SELECT subject FROM content WHERE FIND_IN_SET({$getTeacherSubject[$i]}, subject)";
                    if (count($getTeacherSubject) != 1) {
                        if ($g > $i) {
                            if ($i != count($getTeacherSubject) - 1) {
                                $queryTeacherSubject = $queryTeacherSubject . " UNION ";
                            }
                        }
                    }
                }
                $teacherSubjectCondition = "AND c.subject IN ({$queryTeacherSubject})";
            }
            //search for content name,subject,grade
            if (isset($params['search_name']) && $params['search_name'] != '') {
                //for grade
                $queryGradeConcat = '';
                $gradeQuery = $this->db->query("SELECT DISTINCT (grade_id) FROM grade WHERE grade_name $search")->result_array();
                $g = count($gradeQuery);
                for ($i = 0; $i < $g; $i++) {
                    $queryGradeConcat = $queryGradeConcat . "SELECT grade FROM content WHERE FIND_IN_SET({$gradeQuery[$i]['grade_id']},grade)";
                    if ($g != 1) {
                        if ($g > $i) {
                            if ($i != $g - 1) {
                                $queryGradeConcat = $queryGradeConcat . " UNION ";
                            }
                        }
                    }
                }
                if (count($gradeQuery) != 0) {
                    $gradeIn = "OR c.grade IN ({$queryGradeConcat})";
                } else {
                    $gradeIn = '';
                }
                //for subject
                $querySubjectConcat = '';
                $getTeacherSubject = explode(',', $getTeacherAllSubject['subject']);
                $queryTeacherSubject = '';
                $g = count($getTeacherSubject);
                for ($i = 0; $i < count($getTeacherSubject); $i++) {
                    $queryTeacherSubject = $queryTeacherSubject . "SELECT subject FROM content WHERE FIND_IN_SET({$getTeacherSubject[$i]}, subject)";
                    if (count($getTeacherSubject) != 1) {
                        if ($g > $i) {
                            if ($i != count($getTeacherSubject) - 1) {
                                $queryTeacherSubject = $queryTeacherSubject . " UNION ";
                            }
                        }
                    }
                }
                $subjectQuery = $this->db->query("SELECT DISTINCT (subject_id) FROM subject WHERE subject_name $search AND subject_id IN ({$queryTeacherSubject})")->result_array();
                $g = count($subjectQuery);
                for ($i = 0; $i < $g; $i++) {
                    $querySubjectConcat = $querySubjectConcat . "SELECT subject FROM content WHERE FIND_IN_SET({$subjectQuery[$i]['subject_id']},subject)";
                    if ($g != 1) {
                        if ($g > $i) {
                            if ($i != $g - 1) {
                                $querySubjectConcat = $querySubjectConcat . " UNION ";
                            }
                        }
                    }
                }
                if (count($subjectQuery) != 0) {
                    $subjectIn = "OR c.subject IN ({$querySubjectConcat})";
                } else {
                    $subjectIn = '';
                }
                $searchCondition = "AND (c.name $search $gradeIn $subjectIn)";
            }
        } else {
            //search for content name,subject,grade
            if (isset($params['search_name']) && $params['search_name'] != '') {
                //for grade
                $queryGradeConcat = '';
                $gradeQuery = $this->db->query("SELECT DISTINCT (grade_id) FROM grade WHERE grade_name $search")->result_array();
                $g = count($gradeQuery);
                for ($i = 0; $i < $g; $i++) {
                    $queryGradeConcat = $queryGradeConcat . "SELECT grade FROM content WHERE FIND_IN_SET({$gradeQuery[$i]['grade_id']},grade)";
                    if ($g != 1) {
                        if ($g > $i) {
                            if ($i != $g - 1) {
                                $queryGradeConcat = $queryGradeConcat . " UNION ";
                            }
                        }
                    }
                }
                if (count($gradeQuery) != 0) {
                    $gradeIn = "OR c.grade IN ({$queryGradeConcat})";
                } else {
                    $gradeIn = '';
                }
                //for subject
                $querySubjectConcat = '';
                $subjectQuery = $this->db->query("SELECT DISTINCT (subject_id) FROM subject WHERE subject_name $search")->result_array();
                if (count($subjectQuery) == 0) {
                    $querySubjectConcat = 0;
                }
                $g = count($subjectQuery);
                for ($i = 0; $i < $g; $i++) {
                    $querySubjectConcat = $querySubjectConcat . "SELECT subject FROM content WHERE FIND_IN_SET({$subjectQuery[$i]['subject_id']},subject)";
                    if ($g != 1) {
                        if ($g > $i) {
                            if ($i != $g - 1) {
                                $querySubjectConcat = $querySubjectConcat . " UNION ";
                            }
                        }
                    }
                }
                if (count($subjectQuery) != 0) {
                    $subjectIn = "OR c.subject IN ({$querySubjectConcat})";
                } else {
                    $subjectIn = '';
                }
                $searchCondition = "AND (c.name $search $gradeIn $subjectIn)";
            }
        }
        //for-grade
        if (isset($params['grade']) && count($params['grade']) > 0) {
            $queryGrade = '';
            $g = count($params['grade']);
            for ($i = 0; $i < count($params['grade']); $i++) {
                $queryGrade = $queryGrade . "SELECT grade FROM content WHERE FIND_IN_SET({$params['grade'][$i]}, grade)";
                if (count($params['grade']) != 1) {
                    if ($g > $i) {
                        if ($i != count($params['grade']) - 1) {
                            $queryGrade = $queryGrade . " UNION ";
                        }
                    }
                }
            }
            $gradeCondition = "AND c.grade IN ({$queryGrade})";
        }
        //for-subject
        $subjectCondition = '';
        if (isset($params['subject']) && count($params['subject']) > 0) {
            $querySubject = '';
            $g = count($params['subject']);
            for ($i = 0; $i < count($params['subject']); $i++) {
                $querySubject = $querySubject . "SELECT subject FROM content 
                                                WHERE FIND_IN_SET({$params['subject'][$i]}, subject)";
                if (count($params['subject']) != 1) {
                    if ($g > $i) {
                        if ($i != count($params['subject']) - 1) {
                            $querySubject = $querySubject . " UNION ";
                        }
                    }
                }
            }
            $subjectCondition = "AND c.subject IN ({$querySubject})";
        }
        //for-tags
        $tagsCondition = '';
        if (isset($params['tags']) && count($params['tags']) > 0) {
            $queryTags = '';
            $g = count($params['tags']);
            for ($i = 0; $i < count($params['tags']); $i++) {
                $queryTags = $queryTags . "SELECT tags FROM content WHERE FIND_IN_SET('{$params['tags'][$i]}', tags)";
                if (count($params['tags']) != 1) {
                    if ($g > $i) {
                        if ($i != count($params['tags']) - 1) {
                            $queryTags = $queryTags . " UNION ";
                        }
                    }
                }
            }
            $tagsCondition = "AND c.tags IN ({$queryTags})";
        }
        $contentTypeCondition = '';
        if (isset($params['library'])) {
            if ($params['library'] == 0) {
                $contentTypeCondition = '';
            } elseif ($params['library'] == 1) {
                $contentTypeCondition = "AND c.content_type = 1";
            } elseif ($params['library'] == 2) {
                $contentTypeCondition = "AND c.content_type = 2";
            } elseif ($params['library'] == 3) {
                $contentTypeCondition = "AND c.content_type = 3";
            }
        }
        $myDraft = '';
        if ($params['type'] == "list") {
            if ($params['role_id'] == 2) {
                $myDraft = "AND c.status != 4 AND c.status IN (1,(CASE WHEN c.school_id = '{$params['school_id']}' THEN 5 ELSE 0 END))";
            } else {
                $myDraft = "AND c.status != 4 AND c.status IN (1,(CASE WHEN c.created_by = '{$params['user_id']}' THEN 5 ELSE 0 END))";
            }
        } elseif ($params['type'] == "mydraft") {
            if ($params['role_id'] == 2) {
                $myDraft = "AND c.status != 4 AND c.status IN ((CASE WHEN c.school_id = '{$params['school_id']}' THEN 5 ELSE 0 END))";
            } else {
                $myDraft = "AND c.status != 4 AND c.status = 5 AND c.created_by = {$params['user_id']}";
            }
        }
        $condition = '';
        $authorCondition = '';
        $limit = '';
        //        if ($params['search_name'] == "" ) {
        if (isset($params['page']) && $params['page'] != "") {
            $recordsPerPage = ($params['page'] - 1) * $params['record_per_page'];
            $page = $params['record_per_page'];
            $limit = "limit $recordsPerPage,$page";
        }
        //        }
        if ($params['sort_by'] == '0' && $params['content_user_id'] == 0) {
            $condition = "ORDER BY c.created_date DESC";
        } else if ($params['sort_by'] == -1 && $params['content_user_id'] == 0) {
            $condition = "ORDER BY popularity DESC";
        } else if ($params['content_user_id'] > 0) {
            $authorCondition = "AND c.created_by = {$params['content_user_id']}";
            $condition = "ORDER BY c.created_date DESC";
        } elseif ($params['sort_by'] == 'AZ' && $params['content_user_id'] == 0) {
            $condition = "ORDER BY c.name ASC";
        } elseif ($params['sort_by'] == 'ZA' && $params['content_user_id'] == 0) {
            $condition = "ORDER BY c.name DESC";
        }
        if ($params['role_id'] == 2) {
            $accessCondition = "c.school_id='{$params['school_id']}'";
        } elseif ($params['role_id'] > 2 && $params['role_id'] < 5) {
            $accessCondition = "c.school_id='{$params['school_id']}' AND c.access IN (1,(CASE WHEN c.created_by = '{$params['user_id']}' THEN 2 ELSE 0 END))";
        } elseif ($params['role_id'] == 6) {
            //                $getSchoolIds = $this->db->query("SELECT GROUP_CONCAT(school_id) as school_id FROM school WHERE branch_name = '{$params['corporate_id']}'")->result_array();
            //            } else {
            //                $getSchoolIds[0]['school_id'] = $params['school_id'];
            //            }
            $accessCondition = "c.school_id IN ('{$params['school_id']}')";
        }
        if ($params['user_id'] == $params['content_user_id']) {
            $accessCondition = "c.school_id='{$params['school_id']}'";
        }
        $corporateSchoolCondition = '';
        $corporateContentAccessCondition = '';
        $corporateId = '';
        if ($params['role_id'] != 6) {
            $result1 = $this->db->query("SELECT branch_name FROM school WHERE school_id = {$params['school_id']}")->result_array();
            if ($result1[0]['branch_name'] > 0) {
                $corporateSchoolCondition = "AND (c.corporate_id IN ((SELECT branch_name FROM school WHERE school_id = {$params['school_id']}))";
            }
            if ($result1[0]['branch_name'] > 0) {
                $corporateContentAccessCondition = "OR c.corporate_id IN ((SELECT corporate_id FROM corporate_request WHERE school_id = {$params['school_id']} AND status = 1 AND validity >= CURRENT_DATE())))";
            } else {
                $corporateContentAccessCondition = "AND c.corporate_id IN ((SELECT corporate_id FROM corporate_request WHERE school_id = {$params['school_id']} AND status = 1 AND validity >= CURRENT_DATE()))";
            }
        } else {
            $corporateId = "AND c.corporate_id = {$params['corporate_id']} AND c.status != 4";
        }
        $teacherPermission = "";
        if ($params['role_id'] == 4) {
            $getPermission = $this->db->query("SELECT user_permission_id FROM user_role_permission WHERE user_id = {$params['user_id']} AND school_id = {$params['school_id']}")->result_array();
            if (count($getPermission) > 0) {
                $permissions = array_column($getPermission, 'user_permission_id');
                if (in_array(5, $permissions) && !in_array(6, $permissions)) {
                    $teacherPermission = " AND c.created_by = {$params['user_id']} ";
                } elseif(in_array(6, $permissions)){
                    $getProfileDetails = $this->db->query("SELECT subject,grade_id FROM user_profile_details WHERE user_id = {$params['user_id']} AND school_id = {$params['school_id']}")->result_array();
                    if(count($getProfileDetails) > 0){
                        if($getProfileDetails[0]['subject'] != 0){
                            $getProfileDetails[0]['subject'] = explode(',', $getProfileDetails[0]['subject']);
                            $teacherSubject = '';
                            foreach ($getProfileDetails[0]['subject'] as $subject) {
                                if ($teacherSubject) {
                                    $teacherSubject .= ' OR ';
                                }
                                $teacherSubject .= "FIND_IN_SET($subject, c.subject) > 0";
                            }
                            $teacherPermission = "AND (" . $teacherSubject . ")";
                        }

                        if ($getProfileDetails[0]['grade_id'] != 0) {
                            $getProfileDetails[0]['grade_id'] = explode(',', $getProfileDetails[0]['grade_id']);
                            $teacherGradeCondition = '';
                            foreach ($getProfileDetails[0]['grade_id'] as $subject) {
                                if ($teacherGradeCondition) {
                                    $teacherGradeCondition .= ' OR ';
                                }
                                $teacherGradeCondition .= "FIND_IN_SET($subject, c.grade) > 0";
                            }
                            $teacherPermission .= "AND (" . $teacherGradeCondition . ")";
                        }
                    }
                }
            }
        }
        $result = $this->db->query("SELECT c.content_id, COUNT(cc.content_id) AS popularity, c.name, 
                                    COALESCE (c.description,'') AS description, c.grade, 
                                    COALESCE ((SELECT GROUP_CONCAT(grade_name) FROM grade 
                                    WHERE FIND_IN_SET(grade_id, c.grade)),'') AS grade_name, c.subject, 
                                    COALESCE ((SELECT GROUP_CONCAT(subject_name) FROM subject 
                                    WHERE FIND_IN_SET(subject_id, c.subject)),'') AS subject_name, c.school_id,
                                    COALESCE (c.links,'') AS links,c.file_path,
                                    COALESCE (c.file_text,'') AS file_text, c.annotation,c.questionAnnotation, 
                                     c.content_type,c.download, 
                                    COALESCE (c.tags, '') AS tags,c.content_format, c.access, c.status, c.editor_type, 
                                    COALESCE (c.profile_url,'') AS profile_url, 
                                    COALESCE (c.profile_thumb_url, '') AS profile_thumb_url,
                                    (SELECT CONCAT_WS(' ',first_name,last_name) FROM user_profile
                                    WHERE user_id = c.created_by) AS created_by, c.created_date,
                                    (CASE WHEN c.created_by = {$params['user_id']} THEN 1 ELSE 0 END) AS edit_status,
                                    (CASE WHEN EXISTS ( SELECT 1 FROM text_questions tq WHERE tq.question_type_id = 55 
                                    AND tq.content_id = c.content_id AND c.content_format = 3) THEN 1 ELSE 0 END) AS is_essay 
                                    FROM content c
                                    LEFT JOIN class_content cc ON c.content_id = cc.content_id
                                    WHERE (($accessCondition $myDraft $contentTypeCondition $subjectCondition 
                                    $tagsCondition $authorCondition $gradeCondition $teacherSubjectCondition $searchCondition)
                                    OR (c.access = 3 $contentTypeCondition $subjectCondition $tagsCondition 
                                    $gradeCondition $myDraft $authorCondition $teacherSubjectCondition $searchCondition)
                                    OR (c.access = 4 $corporateSchoolCondition $contentTypeCondition $corporateContentAccessCondition 
                                    $subjectCondition $tagsCondition $myDraft $gradeCondition $corporateId $searchCondition)) $teacherPermission
                                    GROUP BY c.content_id $condition $limit")->result_array();
        //   print_r($this->db->last_query());exit;
        return $result;
    }

    public function questiontypes()
    {
        $result = $this->db->query("SELECT rtm.resource_type, qt.question_type_id, qt.resource_type_id, qt.question_type, qt.image_path
                                    FROM resource_type_master rtm
                                    LEFT JOIN question_types qt ON rtm.resource_type_id = qt.resource_type_id
                                    WHERE rtm.status = 1 AND qt.status = 1")->result_array();
        return $result;
    }

    public function checkStudentAnswer($answerId, $studentContentId)
    {
        $result = $this->db->query("SELECT id FROM student_answers 
                                    WHERE answer_id = {$answerId} AND student_content_id = {$studentContentId}")->result_array();
        return $result;
    }

    public function checkClassAssign($classId, $contentId, $startDate, $endDate)
    {
        $result = $this->db->query("SELECT id,status FROM class_content 
                                    WHERE class_id = {$classId} AND content_id = {$contentId} AND start_date = '{$startDate}' AND end_date = '{$endDate}' ")->result_array();
        return $result;
    }

    public function checkClassDate($params)
    {
        $result = $this->db->query("SELECT class_id,start_date,end_date FROM class 
                                    WHERE class_id = {$params['classdetails'][0]['class_id']}")->result_array();
        return $result;
    }

    public function checkClassroomContent($batchId, $contentId)
    {
        $result = $this->db->query("SELECT batch_id,status FROM classroom_content 
                                    WHERE batch_id = {$batchId} 
                                    AND content_id = {$contentId} AND status = 1")->result_array();
        return $result;
    }

    public function checkTextQuestions($contentId)
    {
        $result = $this->db->query("SELECT question_no FROM text_questions 
                                    WHERE content_id = {$contentId} 
                                    ORDER BY question_id DESC LIMIT 0,1")->row_array();
        return $result;
    }

    public function getSubQuestionNumber($contentId, $questionNumber)
    {
        $result = $this->db->query("SELECT sub_question_no FROM text_questions 
                                    WHERE content_id = {$contentId} AND question_no = {$questionNumber} 
                                    ORDER BY question_id DESC LIMIT 0,1")->row_array();
        return $result;
    }

    public function getQuestionDetails($id)
    {
        $result = $this->db->query("SELECT * FROM text_questions WHERE content_id = {$id}")->result_array();
        return $result;
    }

    public function getGraphQuestions($studentId, $questionId, $classId)
    {
        $result = $this->db->query("SELECT answer_id,content_id,class_id,question_no,student_id,correct_answer,
                                    student_answer, options,actual_points,earned_points,student_answer,created_by,
                                    created_date FROM graph_answers 
                                    WHERE answer_id = {$questionId} 
                                    AND student_id = {$studentId} AND class_id = {$classId}")->row_array();
        return $result;
    }

    public function checkContentAssign($params)
    {
        $result = $this->db->query("SELECT class_id FROM class_content
                                    WHERE content_id = {$params['content_id']} 
                                    AND status = 1")->result_array();
        return $result;
    }

    public function getContentForCopy($params)
    {
        $result = $this->db->query("SELECT name, description, {$params['grade']} as grade, {$params['subject']} as subject, file_path,
                                    base64_data, links, file_text, annotation, questionAnnotation,
                                    content_type, editor_type, tags, content_format, profile_url,
                                    profile_thumb_url, publication_code, '1' as status,
                                    '1' as access, {$params['user_id']} as created_by, {$params['corporate_id']} as corporate_id, 
                                    {$params['user_id']} as modified_by, CURRENT_DATE() as created_date, {$params['school_id']} as school_id
                                    FROM content 
                                    WHERE content_id = {$params['content_id']}")->row_array();
        return $result;
    }

    public function getContentAnswers($params, $contentId)
    {
        $result = $this->db->query("SELECT a.question_no, 
                                    COALESCE (a.question,'') AS question, a.mob_options, 
                                    COALESCE (a.section_heading,'') AS section_heading, a.question_type_id,
                                     a.has_sub_question, {$contentId} as content_id,
                                    COALESCE (a.sub_question_no, '') AS sub_question_no, COALESCE (a.options,'') AS options, a.array, a.answer,
                                     a.auto_grade,  a.points, 
                                    COALESCE (a.difficulty, '') AS difficulty, 
                                    COALESCE (a.allow_exact_match, '') AS allow_exact_match,
                                    COALESCE (a.allow_any_text, '') AS allow_any_text, 
                                    COALESCE (a.match_case, '') AS match_case, 
                                    COALESCE (a.minimum_line, '') AS minimum_line,
                                    {$params['user_id']} as created_by,
                                    {$params['user_id']} as modified_by, CURRENT_DATE() as created_date
                                    FROM answers a
                                    WHERE a.content_id = '{$params['content_id']}' 
                                    ORDER BY a.answer_id ASC")->result_array();
        return $result;
    }

    public function checkQuestionExists($answerId)
    {
        return $this->db->query("SELECT answer_id FROM answers WHERE answer_id = {$answerId} AND status = 1")->result_array();
    }

    public function getContentType($contentId)
    {
        $List = $this->db->query("select content_type,content_format from content where content_id = '{$contentId}' ")->row_array();
        return $List;
    }
    public function name($contentId, $studentId, $classId)
    {
        $List = $this->db->query("SELECT name as content_name , (SELECT CONCAT_WS(' ',first_name,last_name) from user_profile where user_id = '{$studentId}') as student_name,
                                    (SELECT class_name FROM class WHERE class_id = '{$classId}') as class_name FROM content WHERE content_id = '{$contentId}' ")->result_array();
        return $List;
    }

    public function nameNew($contentId, $studentId, $classId)
    {
        $List = $this->db->query("SELECT name as content_name , CONCAT_WS(' ',u.first_name,u.last_name) as student_name, cl.class_name
                                FROM content c
                                LEFT JOIN class cl on c.school_id = cl.school_id
                                LEFT JOIN user_profile u on c.created_by = u.user_id
                                WHERE c.content_id = '{$contentId}' and u.user_id = '{$studentId}' and cl.class_id = '{$classId}' ")->result_array();
        return $List;
    }

    public function checkStatus($data)
    {
        $query = $this->db->query("SELECT status,(select content_type from content WHERE content_id = '{$data['content_id']}' ) as content_type
                                   FROM student_class WHERE class_id = '{$data['class_id']}' AND student_id = '{$data['student_id']}' AND status = 3")->result_array();
        return $query;
    }
    public function getRedoStatus($data)
    {
        return $this->db->query("SELECT redo_test,status FROM student_content 
                                 WHERE id = '{$data['student_content_id']}'")->row_array();
    }

    public function totalScoreUpdate($params)
    {
        $result = $this->db->query("SELECT SUM(earned_points) as earned_points FROM student_answers WHERE 
                                    student_content_id = {$params['student_content_id']} ")->result_array();
        return $result;
    }

    public function totalContentScore($params)
    {
        $result = $this->db->query("SELECT SUM(points) as points FROM answers WHERE content_id = {$params['content_id']} AND status = 1")->result_array();
        return $result;
    }

    public function totalContentPoint($params)
    {
        $result = $this->db->query("SELECT SUM(points) as points FROM text_questions WHERE content_id = {$params['content_id']}")->result_array();
        return $result;
    }

    public function answerkeyRequestList($params)
    {
        $condition = '';
        if (isset($params['type']) && $params['type'] != "") {
            if ($params['type'] == 1) {
                $condition = "s.status = 1";
            } elseif ($params['type'] == 2) {
                $condition = "s.status = 2";
            } elseif ($params['type'] == 3) {
                $condition = "s.status = 3";
            }
        }

        $result = $this->db->query(" SELECT s.id, s.student_id, s.content_id, s.class_id, s.status,
                                    CONCAT_WS(' ', u.first_name, u.last_name) as student_name,
                                    c.name as content_name, cl.class_name, c.content_type,
                                    s.created_by, DATE_FORMAT(s.created_date,'%m-%d-%Y') as created_date
                                    from student_answerkey_request as s
                                    left join content c on s.content_id = c.content_id
                                    left join class cl on s.class_id = cl.class_id
                                    left join user_profile u on s.student_id = u.user_id
                                    WHERE cl.school_id = '{$params['school_id']}' and $condition ")->result_array();
        return $result;
    }

    public function answerkeyStatus($params)
    {

        $result = $this->db->query(" SELECT s.status
                                    from student_answerkey_request as s
                                    left join class cl on s.class_id = cl.class_id
                                    WHERE cl.school_id = '{$params['school_id']}' ")->result_array();
        return $result;
    }

    public function checkAutoGrade($params)
    {
        $result = $this->db->query(" select * from answers where content_id = '{$params['content_id']}' 
                                    and auto_grade = 0 and status = 1 ")->result_array();
        return $result;
    }
    public function getStudentPlatform($params)
    {
        $query = $this->db->query("SELECT platform FROM student_content WHERE id = '{$params['student_content_id']}'
                                    ")->result_array();
        return $query;
    }
    public function getAutoReview($data)
    {
        $query = $this->db->query("SELECT cc.id,cc.auto_review FROM class_content as cc
                                   LEFT JOIN student_content as sc ON cc.id = sc.class_content_id
                                   WHERE sc.id = {$data['student_content_id']}
                                    ")->result_array();
        return $query;
    }
    public function getContentDetails($params)
    {
        $List = $this->db->query("SELECT start_date,end_date FROM class_content WHERE class_id = '{$params['class_id']}' AND content_id = '{$params['content_id']}'")->result_array();
        return $List;
    }
    public function checkClassContent($params)
    {
        $List = $this->db->query("SELECT id,class_id,content_id,school_id,status,all_student,release_score,
                                    auto_review,start_date,end_date,start_time,end_time,notes,downloadable,topic_id,is_accessible,
                                    created_by,created_date,modified_by,modified_date 
                                    FROM class_content WHERE id = '{$params['class_content_id']}'")->result_array();
        return $List;
    }
    public function check($contentId, $classContentId, $studentId)
    {
        $check = $this->db->query("SELECT s.id FROM student_content as s WHERE content_id = {$contentId} AND class_content_id = '{$classContentId}' AND student_id = '{$studentId}'")->result_array();
        return $check;
    }
    public function checkStudentWork($classId, $studentId, $contentId)
    {
        $result = $this->db->query("SELECT * FROM student_work WHERE class_id = '{$classId}'
                                    AND content_id = '{$contentId}' AND student_id = '{$studentId}'")->result_array();
        return $result;
    }
    public function getQuestionExists($params)
    {
        $query = $this->db->query("SELECT answer_id,auto_grade FROM answers 
                                    WHERE content_id = {$params['content_id']} AND status = 1
                                    ")->result_array();
        return $query;
    }
    public function getQuestionExists1($params)
    {
        $query = $this->db->query("SELECT question_id FROM text_questions 
                                    WHERE content_id = {$params['content_id']} ")->result_array();
        return $query;
    }
    public function getOldAnswers($answerId, $params)
    {
        $result = $this->db->query("SELECT answer_status FROM student_answers WHERE answer_id = {$answerId} and student_content_id = {$params['student_content_id']} and student_id = {$params['student_id']}
                                    AND content_id = {$params['content_id']} AND class_id = {$params['class_id']}")->result_array();
        return $result;
    }
    public function getStudentAnswer1($params)
    {
        $result = $this->db->query("SELECT id,answer_id,student_answer,answer_status FROM student_answers WHERE  student_content_id = {$params['student_content_id']}
                                    ")->result_array();
        return $result;
    }
    public function getContentAnswer($answerId)
    {
        $query = $this->db->query("SELECT question_type_id,answer,points FROM answers 
                                    WHERE answer_id = {$answerId} AND auto_grade = 1
                                    ")->result_array();
        return $query;
    }
    public function getClassContentId($params)
    {
        $result = $this->db->query("SELECT class_content_id as id FROM student_content WHERE id = {$params['student_content_id']}")->result_array();
        return $result;
    }

    public function getdb()
    {
        $data = $this->db->query("SELECT DATABASE() as db FROM DUAL")->row_array();
        return $data;
    }
    public function checkBatchContent($batchId, $contentId)
    {
        $result = $this->db->query("SELECT batch_id,content_id,status FROM classroom_content 
                                    WHERE batch_id = '{$batchId}' 
                                    AND content_id = {$contentId}")->result_array();
        return $result;
    }
    public function batchDetail($contentId, $type)
    {
        $condition = '';
        if ($type == 'Active') {
            $condition = "AND status = 1";
        }
        $query = $this->db->query("SELECT GROUP_CONCAT(batch_id) as batch_id FROM classroom_content WHERE content_id = {$contentId} $condition")->result_array();
        return $query;
    }

    public function classContentDetails($id)
    {
        $result = $this->db->query("SELECT id,class_id,content_id,school_id,status,all_student,release_score,
                                    auto_review,start_date,end_date,start_time,end_time,notes,downloadable,topic_id,is_accessible,allow_feedback,allow_workspace,
                                    created_by,created_date,modified_by,modified_date 
                                    FROM class_content WHERE id = {$id}")->result_array();
        return $result;
    }

    public function listPassage($params)
    {
        $teacherPermission = "";
        if ($params['role_id'] == 4) {
            $getPermission = $this->db->query("SELECT user_permission_id FROM user_role_permission WHERE user_id = {$params['user_id']} AND school_id = {$params['school_id']}")->result_array();
            if (count($getPermission) > 0) {
                $permissions = array_column($getPermission, 'user_permission_id');
                if (in_array(5, $permissions) && !in_array(6, $permissions)) {
                    $teacherPermission = " AND p.created_by = {$params['user_id']} ";
                }
            }
        }
        $query = $this->db->query("SELECT p.passage_id,p.title,p.passage,p.status,CONCAT_WS(' ', u.first_name, u.last_name) as created_by_name,p.created_date 
        FROM passage as p 
        LEFT JOIN user_profile u on p.created_by = u.user_id
        WHERE p.status = 1 $teacherPermission ORDER BY p.passage_id DESC")->result_array();
        return $query;
    }

    public function questionTopic($condition){
      $result = $this->db->query("SELECT question_topic_id,question_topic,status FROM question_topic $condition")->result_array();
      return $result;
    }

    public function questionSubTopic($condition)
    {
        $result = $this->db->query("SELECT sub_topic_id,question_topic_id,sub_topic,status FROM sub_topic $condition")->result_array();
        return $result;
    }
    public function checkSkillExists($condition)
    {
        $result = $this->db->query("SELECT id,skill,status FROM question_skill $condition")->result_array();
        return $result;
    }
    
    public function questionSkill($condition){
        $result = $this->db->query("SELECT id,skill,status FROM question_skill $condition")->result_array();
        return $result;
    }
}
