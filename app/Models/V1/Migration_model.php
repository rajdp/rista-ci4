<?php

class Migration_model extends CI_Model
{

    function __construct()
    {
        parent::__construct();
        $this->load->database();
//        $this->db->query("SET GLOBAL time_zone = '{$this->config->item('time_zone')}'");

    }

    public function checkBatchExist($params,$batchName){
        $data = $this->db->query("SELECT batch_id FROM batch WHERE school_id = '{$params['school_id']}' AND batch_name = '{$batchName}' ")->result_array();
        return $data;
    }


    public function getTeacherName($edquillTeacherId){
        $data = $this->db->query("SELECT u.user_id,CONCAT_WS('', first_name,last_name) as name FROM user AS u 
                            left join user_profile as up on up.user_id = u.user_id WHERE edquill_teacher_id = $edquillTeacherId")->result_array();
        return $data;
    }
    public function checkTeacherExists($teacherEdquillId){
        $data = $this->db->query("SELECT user_id FROM user where edquill_teacher_id = $teacherEdquillId ")->result_array();
        return $data;
    }

    public function checkBatchExists($batchId,$schoolId){
        $data = $this->db->query("SELECT batch_name,batch_id from batch where edquill_batch_id = $batchId and school_id = $schoolId")->result_array();
        return $data;
    }

    public function getUserDetail($emailId){
        $userDetail = $this->db->query("SELECT u.user_id, up.first_name, up.last_name FROM user AS u 
                                     LEFT JOIN  user_profile AS up ON up.user_id = u.user_id
                                     WHERE u.email_id = '$emailId' AND u.role_id = 5")->result_array();
        return $userDetail;
    }

  public function getUser($data){
        $query = $this->db->query("SELECT user_id FROM user WHERE student_id = '{$data}'")->result_array();

        return $query;
}
  public function getClass($data){
        $query = $this->db->query("SELECT class_id FROM class WHERE edquill_schedule_id = '{$data}'")->result_array();

        return $query;
  }
  public function checkBook($bookId,$schoolId){
        $query = $this->db->query("SELECT content_id FROM content WHERE name = '{$bookId}' AND school_id = '{$schoolId}' AND status = 1")->result_array();

        return $query;
  }
    public function getClassId($data){
        $query = $this->db->query("SELECT class_id FROM class WHERE edquill_classroom_id = '{$data}'")->result_array();

        return $query;
    }

    public function studentExists($studentId,$classId){
        $data = $this->db->query("select class_id from student_class where student_id = '$studentId' AND class_id = '$classId'")->result_array();
        return $data;
    }

    public function checkClassContent($classId, $contentId,$startDate,$endDate) {
        $result = $this->db->query("SELECT id,status FROM class_content WHERE content_id = {$contentId} AND class_id = {$classId}
                                    AND start_date = '{$startDate}' AND end_date = '{$endDate}'")->result_array();
        return $result;
    }

    public function getClassStudents($classId) {
        $result = $this->db->query("SELECT student_id FROM student_class WHERE class_id = {$classId} AND status = 1")->result_array();
        return $result;
    }

    public function checkStudentContent($classId, $studentId,$contentId, $startDate, $endDate) {
        $result = $this->db->query("SELECT student_id FROM student_content WHERE class_id = {$classId} AND 
                                    student_id = '{$studentId}' AND content_id = {$contentId} AND start_date = '{$startDate}' AND end_date = '{$endDate}'")->result_array();
        return $result;
    }

    public function checkStudentEmail($emailId) {
        $data = $this->db->query("SELECT user_id FROM user WHERE email_id = '$emailId' ")->result_array();
        return $data;
    }

    public function checkSubjectExists($subjectName,$schoolId) {
        $result = $this->db->query("SELECT subject_id FROM subject 
                                    WHERE school_id = {$schoolId} AND subject_name = '{$subjectName}'")->result_array();
        return $result;
    }

    public function getClassIdBatch($data){
        $query = $this->db->query("SELECT class_id FROM class WHERE batch_id = '{$data}' AND end_date > CURRENT_DATE()")->result_array();

        return $query;
    }

    public function getQuestions($class_id, $content_id) {
        $question = $this->db->query("(SELECT DISTINCT
    (sc.student_id),
    (SELECT 
            CONCAT_WS(' ', first_name, last_name)
        FROM
            user_profile
        WHERE
            user_id = sc.student_id) AS student_name,
    q.question,
    q.question_no,
    q.question_id AS question_id,
    (CASE
        WHEN sa.answer_status = 1 THEN 'Correct'
        ELSE 'InCorrect'
    END) AS answer_status, sc.id
FROM
    student_content AS sc
        LEFT JOIN
    student_answers AS sa ON sc.content_id = sa.content_id
        AND sc.class_id = sa.class_id
        LEFT JOIN
    text_questions AS q ON sc.content_id = q.content_id
WHERE
    sc.content_id = $content_id
        AND sc.class_id = $class_id
        AND sa.student_id IN (select  GROUP_CONCAT(student_id) from student_class where class_id = $class_id and status = 1)
        AND sa.answer_id IN (q.question_id)
ORDER BY q.question ASC) UNION (SELECT DISTINCT
    (sc.student_id),
    (SELECT 
            CONCAT_WS(' ', first_name, last_name)
        FROM
            user_profile
        WHERE
            user_id = sc.student_id) AS student_name,
    q.question,
    q.question_no,
    q.question_id,
    '0' AS answer_status,sc.id
FROM
    student_content AS sc
        LEFT JOIN
    text_questions AS q ON sc.content_id = q.content_id
WHERE
    sc.content_id = $content_id
        AND sc.class_id = $class_id
        AND q.question_id NOT IN (SELECT 
            answer_id
        FROM
            student_answers
        WHERE
            student_id IN (select  GROUP_CONCAT(student_id) from student_class where class_id = $class_id and status = 1)
                AND content_id = $content_id
                AND class_id = $class_id)
ORDER BY q.question ASC) ORDER BY student_id,question_no LIMIT 100000;")->result_array();
return $question;
    }

    public function getContentQuestions($contentId) {
        $getContent = $this->db->query("SELECT question_no from text_questions WHERE content_id = $contentId")->result_array();
        return $getContent;
    }

    public function getStudentNames($class_id) {
        $names = $this->db->query("SELECT (SELECT 
            CONCAT_WS(' ', first_name, last_name)
        FROM
            user_profile
        WHERE
            user_id = sc.student_id) AS student_name,sc.student_id FROM student_class sc
            WHERE class_id = $class_id and status = 1")->result_array();
            return $names;
    }

    public function getQuestionAnswerStatus($question_no,$student_id,$content_id,$class_id) {
        $data=$this->db->query("SELECT question_no,(CASE
        WHEN answer_status = 2 THEN 'Correct'
        ELSE 'InCorrect'
    END) AS answer_status FROM student_answers 
                                WHERE student_id = $student_id AND content_id = $content_id
                                AND class_id = $class_id AND question_no = $question_no")->row_array();
        return $data;
    }
}
