<?php

class classes_model extends CI_Model
{

    function __construct()
    {
        parent::__construct();
        $this->load->database();
//        $this->db->query("SET GLOBAL time_zone = '{$this->config->item('time_zone')}'");

    }

    public function checkClass($data,$type)
    {
        $condition1 = '';
        $classCode = '';
        if (isset($data['class_code'])) {
            $classCode = "class_code = '{$data['class_code']}' AND";
        }
        //$className = $this->db->escape($data['name']);
        if($type == 'edit') {
            $condition1 = "AND class_id != {$data['class_id']}";
        }
        $check = $this->db->query("SELECT class_id FROM class WHERE $classCode
                                   school_id = '{$data['school_id']}' $condition1")->result_array();
        return $check;
    }

    public function classList($params){
        $condition = '';
        $leftJoin = '';
        $classId = '';
        $condition1 = '';
        $classCode = '';
        $dateRange = '';
        $limit = '';
        $search = '';
        $announcement = '';
        $end_date_condition = '';
        $course_condition = '';
        $prop = parse_ini_file('../properties.ini', true, INI_SCANNER_RAW);
        if(isset($prop['class_date_validation_days']) && $prop['class_date_validation_days'] != ''){
            $days = '-' . $prop['class_date_validation_days'] . ' days';
            $date = date('Y-m-d', strtotime($days));
            $end_date_condition = "AND (c.end_date > '{$date}' OR c.end_date = '0000-00-00')";
        }
        if (isset($params['page_no']) && $params['page_no'] != "") {
            $recordsPerPage = ($params['page_no'] - 1) * $params['records_per_page'];
            $page = $params['records_per_page'];
            $limit = "limit $recordsPerPage,$page";
        }

        if (isset($params['course_id']) && count($params['course_id']) > 0) {
            $course_id = implode(',', $params['course_id']);
            $course_condition = " AND (c.course_id IN ({$course_id})) ";
        }

        if (isset($params['search']) && $params['search'] != '') {
            $search = "AND (c.class_name like '%{$params['search']}%')";
        }
        //1=>all,2=>upcoming,3=>inprogress,4=>completed,5=>active classes while content assign,
        //6=>saved classes, 7=> schedule classes 8=> gradereport classes,
        $getIndividualTeacher = $this->db->query("SELECT subject, individual_teacher FROM user_profile_details
                                                    WHERE user_id = {$params['user_id']} AND school_id = {$params['school_id']}")->row_array();
        if(isset($params['type'])) {
            if ($params['type'] == 1){
                if (isset($params['list_type']) && $params['list_type'] == 'announcement') {
                    $announcement = "AND c.announcement_type IN(2,3)";
                    if($params['role_id'] == 4){
                        $params['teacher_id'] = $params['user_id'];
                    }
                }
                if($params['role_id'] > 2 && $params['role_id']!=6) {
                    $classId = "DISTINCT (cs.class_id) as class_id, {$params['user_id']} as teacher_id,
                                (SELECT first_name FROM user_profile WHERE user_id = {$params['teacher_id']}) as teacher_name";
                    $leftJoin = "LEFT JOIN class_schedule as cs ON c.class_id = cs.class_id";
                    if ($getIndividualTeacher['individual_teacher'] == 1) {
                        $condition = "AND c.status IN (1,2) AND c.class_status != 1 $announcement ORDER BY c.created_date DESC, c.class_name ASC";
                    } else {
                        $condition = "AND c.status = 1 $announcement ORDER BY c.created_date DESC, c.class_name ASC";
                    }
                } else {
                    $classId = "c.class_id, COALESCE((SELECT GROUP_CONCAT(DISTINCT(teacher_id)) FROM class_schedule WHERE class_id = c.class_id),0) as teacher_id,
                                COALESCE((SELECT GROUP_CONCAT(DISTINCT(SELECT first_name FROM user_profile WHERE user_id IN (teacher_id))) FROM class_schedule WHERE class_id = c.class_id),'') as teacher_name";
                    $condition = "AND c.status IN (1,2) $announcement ORDER BY c.created_date DESC, c.class_name ASC";
                }
            } elseif ($params['type'] == 2) {
                if ($params['role_id'] > 2 && $params['role_id']!=6) {
                    $classId = "DISTINCT (cs.class_id) as class_id, {$params['user_id']} as teacher_id,
                                (SELECT first_name FROM user_profile WHERE user_id = {$params['teacher_id']}) as teacher_name";
                    $leftJoin = "LEFT JOIN class_schedule as cs ON c.class_id = cs.class_id";
                    $condition = "AND c.status = 1 AND c.start_date > CURRENT_DATE() ORDER BY c.start_date ASC, c.class_name ASC";
                } else {
                    $classId = "c.class_id, COALESCE((SELECT GROUP_CONCAT(DISTINCT(teacher_id)) FROM class_schedule WHERE class_id = c.class_id),0) as teacher_id,
                                COALESCE((SELECT GROUP_CONCAT(DISTINCT(SELECT first_name FROM user_profile WHERE user_id IN (teacher_id))) FROM class_schedule WHERE class_id = c.class_id),'') as teacher_name";
                    $condition = "AND c.status = 1 AND c.class_status != 1 AND c.start_date > CURRENT_DATE() ORDER BY c.created_date DESC, c.class_name ASC";
                }
            } elseif ($params['type'] == 3) {
                if($params['role_id'] > 2 && $params['role_id']!=6) {
                    $classId = "DISTINCT (cs.class_id) as class_id, {$params['user_id']} as teacher_id,
                                (SELECT first_name FROM user_profile WHERE user_id = {$params['teacher_id']}) as teacher_name";
                    $leftJoin = "LEFT JOIN class_schedule as cs ON c.class_id = cs.class_id";
                    $condition = "AND c.status = 1 AND c.start_date <= CURRENT_DATE() AND c.end_date >= CURRENT_DATE() ORDER BY c.start_date ASC, c.class_name ASC";
                } else {
                    $classId = "c.class_id, COALESCE((SELECT GROUP_CONCAT(DISTINCT(teacher_id)) FROM class_schedule WHERE class_id = c.class_id),0) as teacher_id,
                                COALESCE((SELECT GROUP_CONCAT(DISTINCT(SELECT first_name FROM user_profile WHERE user_id IN (teacher_id))) FROM class_schedule WHERE class_id = c.class_id),'') as teacher_name";
                    $condition = "AND c.status = 1 AND c.class_status != 1 AND c.start_date <= CURRENT_DATE() AND c.end_date >= CURRENT_DATE() ORDER BY c.created_date DESC, c.class_name ASC";
                }
            } elseif ($params['type'] == 4) {
                if($params['role_id'] > 2 && $params['role_id']!=6) {
                    $classId = "DISTINCT (cs.class_id) as class_id, {$params['user_id']} as teacher_id,
                                (SELECT first_name FROM user_profile WHERE user_id = {$params['teacher_id']}) as teacher_name";
                    $leftJoin = "LEFT JOIN class_schedule as cs ON c.class_id = cs.class_id";
                    $condition = "AND c.status = 1 AND c.end_date < CURRENT_DATE() ORDER BY c.start_date DESC, c.class_name";
                } else {
                    $classId = "c.class_id, COALESCE((SELECT GROUP_CONCAT(DISTINCT(teacher_id)) FROM class_schedule WHERE class_id = c.class_id),0) as teacher_id,
                                COALESCE((SELECT GROUP_CONCAT(DISTINCT(SELECT first_name FROM user_profile WHERE user_id IN (teacher_id))) FROM class_schedule WHERE class_id = c.class_id),'') as teacher_name";
                    $condition = "AND c.status = 1 AND c.class_status != 1 AND c.end_date < CURRENT_DATE() ORDER BY c.created_date DESC, c.class_name";
                }
            } elseif ($params['type'] == 5) {
                if($params['role_id'] > 2 && $params['role_id'] !=6) {
                    $classId = "DISTINCT (cs.class_id) as class_id, {$params['user_id']} as teacher_id";
                    $leftJoin = "LEFT JOIN class_schedule as cs ON c.class_id = cs.class_id";
                    $condition = "AND c.status = 1 AND c.end_date >= CURRENT_DATE() ORDER BY c.start_date DESC, c.class_name";
                } elseif (isset($params['teacher_id']) && $params['teacher_id'] > 0 && ($params['role_id'] == 2 || $params['role_id'] == 6)) {
                    $classId = "DISTINCT (cs.class_id) as class_id, {$params['teacher_id']} as teacher_id";
                    $leftJoin = "LEFT JOIN class_schedule as cs ON c.class_id = cs.class_id";
                    $condition = "AND c.status = 1 ORDER BY c.start_date DESC, c.class_name";
                    $condition1 = "AND FIND_IN_SET('{$params['teacher_id']}',cs.teacher_id)";
                } else {
                    $classId = "c.class_id, COALESCE((SELECT GROUP_CONCAT(DISTINCT(teacher_id)) FROM class_schedule WHERE class_id = c.class_id),0) as teacher_id";
                    $condition = "AND c.status = 1 AND c.class_status != 1 AND c.end_date >= CURRENT_DATE() ORDER BY c.start_date DESC, c.class_name";
                }
            } elseif ($params['type'] == 6) {
                $classId = "c.class_id, COALESCE((SELECT GROUP_CONCAT(DISTINCT(teacher_id)) FROM class_schedule WHERE class_id = c.class_id),0) as teacher_id,
                            COALESCE((SELECT GROUP_CONCAT(DISTINCT(SELECT first_name FROM user_profile WHERE user_id IN (teacher_id))) FROM class_schedule WHERE class_id = c.class_id),'') as teacher_name";
                $condition = "AND c.class_status = 1 AND c.status = 1 ORDER BY c.created_date DESC,c.class_name";
            } elseif ($params['type'] == 7) {
                if(isset($params['teacher_id']) && $params['teacher_id'] > 0) {
                    $classId = "DISTINCT (cs.class_id) as class_id, {$params['teacher_id']} as teacher_id";
                    $leftJoin = "LEFT JOIN class_schedule as cs ON c.class_id = cs.class_id";
                    $condition = "AND c.status = 1 ORDER BY c.start_date DESC, c.class_name";
                    $condition1 = "AND FIND_IN_SET('{$params['teacher_id']}',cs.teacher_id)";
                } else {
                    $classId = "c.class_id, COALESCE((SELECT GROUP_CONCAT(DISTINCT(teacher_id)) FROM class_schedule WHERE class_id = c.class_id),0) as teacher_id";
                    $condition = "AND c.status = 1 AND c.class_status != 1 ORDER BY c.start_date DESC, c.class_name";
                }
            } elseif ($params['type'] == 8) {
                if (isset($params['class_code']) && $params['class_code'] != "") {
                    $condition = "AND c.class_code = '{$params['class_code']}' AND c.status = 1 AND c.class_status != 1 AND c.start_date <= CURRENT_DATE() ORDER BY c.start_date DESC,c.class_name ";
                } else {
                    $condition = "AND c.status = 1 AND c.class_status != 1 AND c.start_date <= CURRENT_DATE() ORDER BY c.start_date DESC, c.class_name ";
                }
                if (isset($params['from_date']) && isset($params['to_date'])) {
                    if ($params['from_date'] != '' && $params['to_date'] != '') {
                        $dateRange = "AND c.end_date >= '{$params['from_date']}' AND c.end_date <= '{$params['to_date']}'";
                    } elseif ($params['from_date'] != '') {
                        $dateRange = "AND c.end_date >= '{$params['from_date']}'";
                    } elseif ($params['to_date'] != '') {
                        $dateRange = "AND c.end_date <= '{$params['to_date']}'";
                    }
                }
                $classCode = "CONCAT(c.class_name,'(',c.class_code,')') as classcode,";
                $classId = "c.class_id, COALESCE((SELECT GROUP_CONCAT(DISTINCT(teacher_id)) FROM class_schedule WHERE class_id = c.class_id),0) as teacher_id";
            } elseif ($params['type'] == 9) {
                if($params['role_id'] > 2 && $params['role_id'] !=6) {
                    $classId = "DISTINCT (cs.class_id) as class_id, {$params['user_id']} as teacher_id";
                    $leftJoin = "LEFT JOIN class_schedule as cs ON c.class_id = cs.class_id";
                    $condition = "AND c.status = 1 ORDER BY c.start_date DESC, c.class_name";
                } elseif (isset($params['teacher_id']) && $params['teacher_id'] > 0 && ($params['role_id'] == 2 || $params['role_id'] == 6)) {
                    $classId = "DISTINCT (cs.class_id) as class_id, {$params['teacher_id']} as teacher_id";
                    $leftJoin = "LEFT JOIN class_schedule as cs ON c.class_id = cs.class_id";
                    $condition = "AND c.status = 1 ORDER BY c.start_date DESC, c.class_name";
                    $condition1 = "AND FIND_IN_SET('{$params['teacher_id']}',cs.teacher_id)";
                } else {
                    $classId = "c.class_id, COALESCE((SELECT GROUP_CONCAT(DISTINCT(teacher_id)) FROM class_schedule WHERE class_id = c.class_id),0) as teacher_id";
                    $condition = "AND c.status = 1 AND c.class_status != 1 ORDER BY c.start_date DESC, c.class_name";
                }
            }
        }
        $gradeCondition = '';
        if (isset($params['grade']) && count($params['grade']) > 0) {
            $queryGrade = '';
            $g = count($params['grade']);
            for($i = 0; $i < count($params['grade']); $i++) {
                $queryGrade = $queryGrade. "SELECT grade FROM class WHERE FIND_IN_SET({$params['grade'][$i]}, grade)";
                if(count($params['grade']) != 1) {
                    if ($g > $i) {
                        if ($i != count($params['grade'])-1) {
                            $queryGrade = $queryGrade . " UNION ";
                        }
                    }
                }
            }
            $gradeCondition = "AND c.grade IN ({$queryGrade})";
        }
        $subjectCondition = '';
        if (isset($params['subject']) && count($params['subject']) > 0) {
            $querySubject = '';
            $g = count($params['subject']);
            for($i = 0; $i < count($params['subject']); $i++) {
                $querySubject = $querySubject. "SELECT subject FROM class
                                                WHERE FIND_IN_SET({$params['subject'][$i]}, subject)";
                if(count($params['subject']) != 1) {
                    if ($g > $i) {
                        if ($i != count($params['subject'])-1) {
                            $querySubject = $querySubject . " UNION ";
                        }
                    }
                }
            }
            $subjectCondition = "AND c.subject IN ({$querySubject})";
        }
        $batchCondition = '';
        $teacherId = '';
        if (isset($params['classroom']) && $params['classroom'] != 0) {
            $batchCondition = "AND FIND_IN_SET({$params['classroom']}, c.batch_id)";
        }
        if(isset($params['teacher_id']) && $params['teacher_id'] != 0 && $params['type'] == 6) {
            $condition1 = "AND c.teacher_id = {$params['user_id']}";
        } elseif(isset($params['teacher_id']) && $params['teacher_id'] != 0 && $params['role_id'] > 2 && $params['role_id'] != 6) {
            $condition1 = "AND FIND_IN_SET('{$params['user_id']}',cs.teacher_id)";
        } elseif(isset($params['teacher_id']) && $params['teacher_id'] !=0 && ($params['role_id'] == 2 || $params['role_id']) == 6) {
            $classId = "DISTINCT (cs.class_id) as class_id, COALESCE((SELECT GROUP_CONCAT(DISTINCT(teacher_id)) FROM class_schedule WHERE class_id = c.class_id),0) as teacher_id,
                                COALESCE((SELECT GROUP_CONCAT(DISTINCT(SELECT first_name FROM user_profile WHERE user_id IN (teacher_id))) FROM class_schedule WHERE class_id = c.class_id),'') as teacher_name";
            $teacherId = "AND FIND_IN_SET('{$params['teacher_id']}',cs.teacher_id)";
            $leftJoin = "LEFT JOIN class_schedule as cs ON c.class_id = cs.class_id";
        }
        $studentSearch = "";
        $studentClassLeftJoin = "";
        if(isset($params['student_search']) && $params['student_search'] != '') {
            $studentClassLeftJoin = "LEFT JOIN student_class sc ON sc.class_id = c.class_id";
            $studentSearch = "AND sc.student_id IN (SELECT up.user_id FROM user_profile up
                              LEFT JOIN user u ON up.user_id = u.user_id WHERE (CONCAT_WS(' ',up.first_name,up.last_name) LIKE '%{$params['student_search']}%' OR up.first_name LIKE '%{$params['student_search']}%' 
                              OR up.last_name LIKE '%{$params['student_search']}%') AND u.role_id = 5 AND 
                              FIND_IN_SET('{$params['school_id']}',u.school_id) AND sc.status NOT IN (0,3))";
        }

        $batchName ="COALESCE ((SELECT GROUP_CONCAT(batch_name) FROM batch WHERE FIND_IN_SET(batch_id ,c.batch_id)),'-') as classroom_name";

        $classList = $this->db->query("SELECT $classId, $classCode c.school_id, c.class_name, c.batch_id,c.class_type, $batchName,
                                       COALESCE ((SELECT GROUP_CONCAT(subject_name)
                                        FROM subject WHERE FIND_IN_SET(subject_id, c.subject)),'') AS subject_name,
                                       c.subject, c.start_date, c.end_date, c.tags,
                                       (CASE WHEN c.start_date > CURRENT_DATE() THEN 2 
                                       WHEN c.end_date >= CURRENT_DATE() THEN 3
                                       WHEN c.end_date < CURRENT_DATE() THEN 4
                                       ELSE 1 END) AS classDate_status, c.class_status,
                                       COALESCE ((SELECT GROUP_CONCAT(grade_name) FROM grade 
                                       WHERE FIND_IN_SET(grade_id, c.grade)),'') 
                                       AS grade_name,c.grade AS grade_id, c.meeting_link, c.meeting_id, c.passcode, 
                                       COALESCE (c.telephone_number,'') AS telephone_number,c.class_code, c.status,
                                       COALESCE(c.profile_url,'') AS profile_url, 
                                       COALESCE(c.profile_thumb_url,'') AS profile_thumb_url,c.announcement_type,
                                       c.course_id,COALESCE (c.registration_start_date, '') AS registration_start_date,COALESCE (c.registration_end_date, '') AS registration_end_date,COALESCE (c.cost, '') AS cost,COALESCE (c.discount_amount, '') AS discount_amount,COALESCE (c.actual_cost, '') AS actual_cost,COALESCE (c.total_slots, '') AS total_slots,COALESCE (c.slots_booked, '') AS slots_booked,COALESCE (c.payment_type, '') AS payment_type,COALESCE (c.payment_sub_type, '') AS payment_sub_type,
                                       (CASE WHEN LPAD((SELECT count(student_id) FROM student_class 
                                       WHERE class_id = c.class_id AND status = 1),2,0) = 00 THEN 0 ELSE LPAD 
                                       ((SELECT count(student_id) FROM student_class 
                                       WHERE class_id = c.class_id AND status = 1 AND class_type IN (0,1) ),2,0) END) AS no_of_students,
                                       (CASE WHEN LPAD((SELECT count(ca.content_id) FROM content cnt 
                                       LEFT JOIN class_content ca ON cnt.content_id = ca.content_id 
                                       WHERE cnt.content_type = 1 and cnt.status = 1 AND ca.status=1
                                       AND ca.class_id = c.class_id),2,0) = 00 THEN 0 ELSE LPAD
                                       ((SELECT count(ca.content_id) FROM content cnt 
                                       LEFT JOIN class_content ca ON cnt.content_id = ca.content_id 
                                       WHERE cnt.content_type = 1 AND cnt.status = 1 AND ca.status=1
                                       AND ca.class_id = c.class_id),2,0) END) AS no_of_resources,
                                       (CASE WHEN LPAD((SELECT count(ca.content_id) FROM content cnt 
                                       LEFT JOIN class_content ca ON cnt.content_id = ca.content_id 
                                       WHERE cnt.content_type = 2 AND cnt.status = 1 AND ca.status=1
                                       AND ca.class_id = c.class_id),2,0) = 00 THEN 0
                                       ELSE LPAD((SELECT count(ca.content_id) FROM content cnt 
                                       LEFT JOIN class_content ca ON cnt.content_id = ca.content_id 
                                       WHERE cnt.content_type = 2 AND cnt.status = 1 AND ca.status=1
                                       AND ca.class_id = c.class_id),2,0) END) AS no_of_assignments,
                                       (CASE WHEN LPAD((SELECT count(ca.content_id) FROM content cnt 
                                       LEFT JOIN class_content ca ON cnt.content_id = ca.content_id 
                                       WHERE cnt.content_type = 3 AND cnt.status = 1 AND ca.status=1
                                       AND ca.class_id = c.class_id),2,0) = 00 THEN 0
                                       ELSE LPAD((SELECT count(ca.content_id) FROM content cnt 
                                       LEFT JOIN class_content ca ON cnt.content_id = ca.content_id 
                                       WHERE cnt.content_type = 3 AND cnt.status = 1 AND ca.status=1
                                       AND ca.class_id = c.class_id),2,0) END) AS no_of_assessments,
                                       COALESCE ((SELECT value from admin_settings_school where school_id = '{$params['school_id']}' AND name = 'allow_zoom_api'),'') as allow_zoom_api
                                       FROM class AS c $leftJoin $studentClassLeftJoin
                                       WHERE c.school_id = '{$params['school_id']}' $end_date_condition $course_condition $search $dateRange $gradeCondition $subjectCondition
                                       $batchCondition $condition1 $teacherId $studentSearch $condition  $limit ")->result_array();
        return $classList;
    }

    public function getDraftsClasses($params) {
        $gradeCondition = '';
        if (isset($params['grade']) && count($params['grade']) > 0) {
            $queryGrade = '';
            $g = count($params['grade']);
            for($i = 0; $i < count($params['grade']); $i++) {
                $queryGrade = $queryGrade. "SELECT grade FROM class WHERE FIND_IN_SET({$params['grade'][$i]}, grade)";
                if(count($params['grade']) != 1) {
                    if ($g > $i) {
                        if ($i != count($params['grade'])-1) {
                            $queryGrade = $queryGrade . " UNION ";
                        }
                    }
                }
            }
            $gradeCondition = "AND c.grade IN ({$queryGrade})";
        }
        $subjectCondition = '';
        if (isset($params['subject']) && count($params['subject']) > 0) {
            $querySubject = '';
            $g = count($params['subject']);
            for($i = 0; $i < count($params['subject']); $i++) {
                $querySubject = $querySubject. "SELECT subject FROM class 
                                                WHERE FIND_IN_SET({$params['subject'][$i]}, subject)";
                if(count($params['subject']) != 1) {
                    if ($g > $i) {
                        if ($i != count($params['subject'])-1) {
                            $querySubject = $querySubject . " UNION ";
                        }
                    }
                }
            }
            $subjectCondition = "AND c.subject IN ({$querySubject})";
        }
        $batchCondition = '';
        if (isset($params['classroom']) && $params['classroom'] != 0) {
            $batchCondition = "AND c.batch_id = {$params['classroom']}";
        }
        $condition = "";
        $teacherDetail = "c.class_id, COALESCE((SELECT GROUP_CONCAT(DISTINCT(teacher_id)) FROM class_schedule WHERE class_id = c.class_id),0) as teacher_id,
                            COALESCE((SELECT GROUP_CONCAT(DISTINCT(SELECT first_name FROM user_profile WHERE user_id IN (teacher_id))) FROM class_schedule WHERE class_id = c.class_id),'') as teacher_name";
        if ($params['role_id'] > 2) {

            $condition = "AND c.created_by = {$params['user_id']}";
        }
        $result = $this->db->query("SELECT c.class_id, $teacherDetail, c.school_id, c.class_name, c.batch_id,c.class_type,
                                    COALESCE ((SELECT batch_name FROM batch WHERE batch_id = c.batch_id),'-') as classroom_name,
                                   COALESCE ((SELECT GROUP_CONCAT(subject_name) FROM subject 
                                   WHERE FIND_IN_SET(subject_id, c.subject)),'') AS subject_name,
                                   c.subject, c.start_date, c.end_date, c.tags,
                                   (CASE WHEN c.start_date > CURRENT_DATE() THEN 2 
                                   WHEN c.end_date >= CURRENT_DATE() THEN 3
                                   WHEN c.end_date < CURRENT_DATE() THEN 4 ELSE 1 END)
                                   AS classDate_status, c.class_status,
                                   COALESCE ((SELECT GROUP_CONCAT(grade_name) FROM grade 
                                   WHERE FIND_IN_SET(grade_id, c.grade)),'') AS grade_name,
                                   c.grade AS grade_id, c.meeting_link, c.meeting_id, c.passcode,
                                   COALESCE (c.telephone_number,'') AS telephone_number,
                                   c.class_code, c.status,COALESCE(c.profile_url,'') AS profile_url,
                                   COALESCE(c.profile_thumb_url,'') AS profile_thumb_url,
                                   (CASE WHEN LPAD((SELECT count(student_id) FROM student_class 
                                   WHERE class_id = c.class_id AND status = 1),2,0) = 00 THEN 0
                                   ELSE LPAD ((SELECT count(student_id) FROM student_class 
                                   WHERE class_id = c.class_id AND status = 1 AND class_type IN (0,1)),2,0) END) AS no_of_students,
                                   (CASE WHEN LPAD((SELECT count(ca.content_id) FROM content cnt
                                   LEFT JOIN class_content ca ON cnt.content_id = ca.content_id 
                                   WHERE cnt.content_type = 1 AND cnt.status = 1 AND ca.status=1
                                   AND ca.class_id = c.class_id),2,0) = 00 THEN 0
                                   ELSE LPAD((SELECT count(ca.content_id) FROM content cnt 
                                   LEFT JOIN class_content ca ON cnt.content_id = ca.content_id 
                                   WHERE cnt.content_type = 1 AND cnt.status = 1 AND ca.status=1
                                   AND ca.class_id = c.class_id),2,0) END) AS no_of_resources,
                                   (CASE WHEN LPAD((SELECT count(ca.content_id) FROM content cnt
                                   LEFT JOIN class_content ca ON cnt.content_id = ca.content_id 
                                   WHERE cnt.content_type = 2 AND cnt.status = 1 AND ca.status=1
                                   AND ca.class_id = c.class_id),2,0) = 00 THEN 0
                                   ELSE LPAD((SELECT count(ca.content_id) FROM content cnt 
                                   LEFT JOIN class_content ca ON cnt.content_id = ca.content_id 
                                   WHERE cnt.content_type = 2 AND cnt.status = 1 AND ca.status=1
                                   AND ca.class_id = c.class_id),2,0) END) AS no_of_assignments,
                                   (CASE WHEN LPAD((SELECT count(ca.content_id) FROM content cnt
                                   LEFT JOIN class_content ca ON cnt.content_id = ca.content_id 
                                   WHERE cnt.content_type = 3 AND cnt.status = 1 AND ca.status=1
                                   AND ca.class_id = c.class_id),2,0) = 00 THEN 0
                                   ELSE LPAD((SELECT count(ca.content_id) FROM content cnt 
                                   LEFT JOIN class_content ca
                                   ON cnt.content_id = ca.content_id 
                                   WHERE cnt.content_type = 3 AND cnt.status = 1 AND ca.status=1
                                   AND ca.class_id = c.class_id),2,0) END) AS no_of_assessments
                                   FROM class AS c WHERE c.school_id = '{$params['school_id']}' 
                                   AND c.class_status = 1 AND status IN (1,2) 
                                   $gradeCondition $subjectCondition $batchCondition
                                   $condition")->result_array();
        return $result;
    }

    public function checkcontent($id){
        $check=$this->db->query("SELECT content_id FROM class_content WHERE class_id='{$id}'")->result_array();
        return $check;
    }

    public function checkstudent($classId,$studentId){
        $check=$this->db->query("SELECT student_id,status FROM student_class 
                                 WHERE class_id='{$classId}' AND student_id = '{$studentId}'")->result_array();
        return $check;
    }

    public function classIdList($data) {
        $typeCondition = '';

        $result = $this->db->query("SELECT $typeCondition c.class_id, c.teacher_id, c.video_link, 
                                    c.school_id, c.class_name, c.subject, c.batch_id, COALESCE(c.notes,'') AS notes,
                                    COALESCE ((SELECT GROUP_CONCAT(subject_name) FROM subject 
                                    WHERE FIND_IN_SET(subject_id, c.subject)),'') AS subject_name, c.grade, 
                                    COALESCE ((SELECT GROUP_CONCAT(grade_name) FROM grade 
                                    WHERE FIND_IN_SET(grade_id, c.grade)),'') AS grade_name,
                                    COALESCE (c.tags, '') AS tags, 
                                    (CASE WHEN c.start_date > CURRENT_DATE() THEN 2 
                                    WHEN c.end_date >= CURRENT_DATE() THEN 3
                                    WHEN c.end_date < CURRENT_DATE() THEN 4 
                                    ELSE 1 END) AS classDate_status, c.class_status,c.class_type,
                                    c.start_date, c.end_date, c.meeting_link, c.meeting_id, c.passcode,
                                    COALESCE (c.telephone_number,'') AS telephone_number,
                                    c.class_code, c.status,COALESCE(c.profile_url,'') AS profile_url,
                                    COALESCE(c.profile_thumb_url,'') AS profile_thumb_url,c.announcement_type,
                                    c.course_id,COALESCE (c.registration_start_date, '') AS registration_start_date,COALESCE (c.registration_end_date, '') AS registration_end_date,COALESCE (c.cost, '') AS cost,COALESCE (c.discount_amount, '') AS discount_amount,COALESCE (c.actual_cost, '') AS actual_cost,COALESCE (c.total_slots, '') AS total_slots,COALESCE (c.slots_booked, '') AS slots_booked,COALESCE (c.payment_type, '') AS payment_type,COALESCE (c.payment_sub_type, '') AS payment_sub_type,
                                    (CASE WHEN LPAD((SELECT count(student_id) FROM student_class 
                                    WHERE class_id = c.class_id AND status = 1),2,0) = 00 THEN 0
                                    ELSE LPAD((SELECT count(student_id) FROM student_class
                                    WHERE class_id = c.class_id AND status = 1 AND class_type IN (0,1)),2,0) END) AS no_of_students,
                                    (CASE WHEN LPAD((SELECT count(ca.content_id) FROM content cnt 
                                    LEFT JOIN class_content ca ON cnt.content_id = ca.content_id 
                                    WHERE cnt.content_type = 1 AND cnt.status = 1 AND ca.status=1
                                    AND ca.class_id = c.class_id),2,0) = 00 THEN 0
                                    ELSE LPAD((SELECT count(ca.content_id) FROM content cnt 
                                    LEFT JOIN class_content ca ON cnt.content_id = ca.content_id 
                                    WHERE cnt.content_type = 1 AND cnt.status = 1 AND ca.status=1
                                    AND ca.class_id = c.class_id),2,0) END) AS no_of_resources,
                                    (CASE WHEN LPAD((SELECT count(ca.content_id) FROM content cnt 
                                    LEFT JOIN class_content ca ON cnt.content_id = ca.content_id 
                                    WHERE cnt.content_type = 2 AND cnt.status = 1 AND ca.status=1
                                    AND ca.class_id = c.class_id),2,0) = 00 THEN 0
                                    ELSE LPAD((SELECT count(ca.content_id) FROM content cnt 
                                    LEFT JOIN class_content ca ON cnt.content_id = ca.content_id 
                                    WHERE cnt.content_type = 2 AND cnt.status = 1 AND ca.status=1
                                    AND ca.class_id = c.class_id),2,0) END) AS no_of_assignments,
                                    (CASE WHEN LPAD((SELECT count(ca.content_id) FROM content cnt 
                                    LEFT JOIN class_content ca ON cnt.content_id = ca.content_id 
                                    WHERE cnt.content_type = 3 AND cnt.status = 1 AND ca.status=1
                                    AND ca.class_id = c.class_id),2,0) = 00 THEN 0
                                    ELSE LPAD((SELECT count(ca.content_id) FROM content cnt 
                                    LEFT JOIN class_content ca ON cnt.content_id = ca.content_id 
                                    WHERE cnt.content_type = 3 AND cnt.status = 1 AND ca.status=1
                                    AND ca.class_id = c.class_id),2,0) END) AS no_of_assessments,c.time_zone_id,
                                    (SELECT GROUP_CONCAT(DISTINCT(teacher_id)) FROM class_schedule WHERE class_id = c.class_id) as teacher_ids                                
                                    FROM class AS c WHERE c.class_id = '{$data}'")->result_array();
        return $result;
    }

    public function checkClassStudent($data) {
        $result = $this->db->query("SELECT GROUP_CONCAT(status) as status FROM student_class 
                                    WHERE class_id = {$data['class_id']}")->row_array();
        return $result;
    }

    public function classDetailList($data,$schoolId) {

        $result = $this->db->query("SELECT c.class_id, c.teacher_id, c.video_link, c.school_id,
                                    c.class_name, c.subject, c.batch_id, c.class_type, COALESCE(c.notes,'') AS notes,
                                    COALESCE ((SELECT GROUP_CONCAT(subject_name) FROM subject 
                                    WHERE FIND_IN_SET(subject_id, c.subject)),'') AS subject_name, c.grade,
                                    COALESCE ((SELECT GROUP_CONCAT(grade_name) FROM grade 
                                    WHERE FIND_IN_SET(grade_id, c.grade)),'') AS grade_name,
                                    COALESCE (c.tags, '') AS tags, 
                                    (CASE WHEN c.start_date > CURRENT_DATE() THEN 2 
                                    WHEN c.end_date >= CURRENT_DATE() THEN 3
                                    WHEN c.end_date < CURRENT_DATE() THEN 4 ELSE 1 END) 
                                    AS classDate_status, c.class_status,
                                    c.start_date, c.end_date, c.meeting_link, c.meeting_id, c.passcode, 
                                    COALESCE (c.telephone_number,'') AS telephone_number,c.class_code, c.status,
                                    COALESCE(c.profile_url,'') AS profile_url, 
                                    COALESCE(c.profile_thumb_url,'') AS profile_thumb_url,c.announcement_type,
                                    c.course_id,COALESCE (c.registration_start_date, '') AS registration_start_date,COALESCE (c.registration_end_date, '') AS registration_end_date,COALESCE (c.cost, '') AS cost,COALESCE (c.discount_amount, '') AS discount_amount,COALESCE (c.actual_cost, '') AS actual_cost,COALESCE (c.total_slots, '') AS total_slots,COALESCE (c.slots_booked, '') AS slots_booked,COALESCE (c.payment_type, '') AS payment_type,COALESCE (c.payment_sub_type, '') AS payment_sub_type,
                                    (CASE WHEN LPAD((SELECT count(student_id) FROM student_class 
                                    WHERE class_id = c.class_id AND status = 1),2,0) = 00 THEN 0
                                    ELSE LPAD((SELECT count(student_id) FROM student_class 
                                    WHERE class_id = c.class_id AND status = 1 AND class_type IN (0,1) ),2,0) END) AS no_of_students,
                                    (CASE WHEN LPAD((SELECT count(ca.content_id) FROM content cnt 
                                    LEFT JOIN class_content ca ON cnt.content_id = ca.content_id 
                                    WHERE cnt.content_type = 1 AND cnt.status = 1 AND ca.status=1
                                    AND ca.class_id = c.class_id),2,0) = 00 THEN 0
                                    ELSE LPAD((SELECT count(ca.content_id) FROM content cnt 
                                    LEFT JOIN class_content ca ON cnt.content_id = ca.content_id 
                                    WHERE cnt.content_type = 1 AND cnt.status = 1 AND ca.status=1
                                    AND ca.class_id = c.class_id),2,0) END) as no_of_resources,
                                    (CASE WHEN LPAD((SELECT count(ca.content_id) FROM content cnt 
                                    LEFT JOIN class_content ca ON cnt.content_id = ca.content_id 
                                    WHERE cnt.content_type = 2 AND cnt.status = 1 AND ca.status=1
                                    AND ca.class_id = c.class_id),2,0) = 00 THEN 0
                                    ELSE LPAD((SELECT count(ca.content_id) FROM content cnt 
                                    LEFT JOIN class_content ca ON cnt.content_id = ca.content_id 
                                    WHERE cnt.content_type = 2 AND cnt.status = 1 AND ca.status=1
                                    AND ca.class_id = c.class_id),2,0) END) AS no_of_assignments,
                                    (CASE WHEN LPAD((SELECT count(ca.content_id) FROM content cnt 
                                    LEFT JOIN class_content ca ON cnt.content_id = ca.content_id 
                                    WHERE cnt.content_type = 3 AND cnt.status = 1 AND ca.status=1
                                    AND ca.class_id = c.class_id),2,0) = 00 THEN 0
                                    ELSE LPAD((SELECT count(ca.content_id) FROM content cnt 
                                    LEFT JOIN class_content ca ON cnt.content_id = ca.content_id 
                                    WHERE cnt.content_type = 3 AND cnt.status = 1 AND ca.status=1
                                    AND ca.class_id = c.class_id),2,0) END) AS no_of_assessments,
                                    sc.student_id AS user_id,sc.validity,sc.class_type as student_class_type,
                                    (SELECT email_id FROM user WHERE user_id = sc.student_id) AS student_id,
                                    (SELECT first_name FROM user_profile WHERE user_id = sc.student_id) AS first_name,
                                    (SELECT last_name FROM user_profile WHERE user_id = sc.student_id) AS last_name,
                                    (SELECT grade_id FROM user_profile_details
                                    WHERE user_id = sc.student_id AND school_id = {$schoolId}) AS grade_id,
                                    (SELECT grade_name FROM grade 
                                    WHERE grade_id = (SELECT grade_id FROM user_profile_details 
                                    WHERE user_id = sc.student_id AND school_id = {$schoolId})) AS grade_name,c.time_zone_id,
                                    (SELECT GROUP_CONCAT(DISTINCT(teacher_id)) FROM class_schedule WHERE class_id = c.class_id) as teacher_ids 
                                    FROM class AS c 
                                    LEFT JOIN student_class AS sc ON sc.class_id = c.class_id
                                    WHERE sc.status = 1 AND c.class_id = '{$data}'")->result_array();
//        print_r($this->db->last_query());exit;
        return $result;
    }

    public function removeclass(){
        $result=$this->db->query("SELECT class_id from class where status = 3")->result_array();
    }

    public function no_of_students($grade,$schoolId) {
        $result = $this->db->query("SELECT count(school_idno) as no_of_students FROM user_profile_details 
                                    WHERE school_id = '{$schoolId}' AND status = 1 AND class_type IN (0,1)
                                    AND grade_id IN ({$grade})")->row_array();
        return $result;
    }

    public function no_of_resources($data) {
        $result = $this->db->query("SELECT (CHAR_LENGTH(c.file_path) - CHAR_LENGTH(REPLACE(c.file_path, ',', '')) + 1) 
                                    AS no_of_resources FROM class_content AS cc 
                                    LEFT JOIN content AS c ON cc.content_id = c.content_id
                                    WHERE cc.class_id = {$data}")->row_array();
        return $result;
    }

    public function subjectName($data) {
        $result = $this->db->query("SELECT subject_name FROM subject WHERE subject_id = '{$data}'")->row_array();
        return $result;
    }

    public function classStudents($data) {
        $result = $this->db->query("SELECT student_id AS school_idno, validity AS validity_date FROM student_class
                                    WHERE class_id = '{$data}' ")->result_array();
        return $result;
    }

    public function checkStudentContent($classId, $studentId, $contentId, $startDate, $endDate) {
        $result = $this->db->query("SELECT student_id FROM student_content WHERE class_id = {$classId} AND 
                                    student_id = '{$studentId}' AND content_id = {$contentId} AND start_date ='$startDate' AND end_date = '$endDate'")->result_array();
        return $result;
    }

    public function checkSpecificStudents($classId, $studentId) {
        $result = $this->db->query("SELECT all_student FROM student_class WHERE class_id = {$classId} AND 
                                    student_id = '{$studentId}'")->result_array();
        return $result;
    }

    public function classAddList($data) {
        $list= $this->db->query("SELECT c.class_id, c.teacher_id, c.video_link, c.school_id,
                                 c.class_name, c.subject,c.tags,c.batch_id, COALESCE(c.notes,'') AS notes,
                                 (CASE WHEN c.start_date > CURRENT_DATE() THEN 2
                                 WHEN c.end_date >= CURRENT_DATE() THEN 3
                                 WHEN c.end_date < CURRENT_DATE() THEN 4 ELSE 1 END) AS classDate_status,c.class_status,
                                 COALESCE ((SELECT GROUP_CONCAT(subject_name) FROM subject 
                                 WHERE FIND_IN_SET(subject_id, c.subject)),'') AS subject_name, c.grade, 
                                 COALESCE ((SELECT GROUP_CONCAT(grade_name) FROM grade 
                                 WHERE FIND_IN_SET(grade_id, c.grade)),'') AS grade_name,
                                 c.start_date, c.end_date, c.meeting_link, c.meeting_id, c.passcode,c.class_type,
                                 COALESCE (c.telephone_number,'') AS telephone_number, c.class_code,c.status,
                                 COALESCE(c.profile_url,'') AS profile_url, 
                                 COALESCE(c.profile_thumb_url,'') AS profile_thumb_url,
                                 (SELECT cnt.file_path FROM class AS ca 
                                 LEFT JOIN class_content AS cc ON ca.class_id = cc.class_id
                                 LEFT JOIN content AS cnt ON cc.content_id = cnt.content_id 
                                 WHERE ca.class_id = c.class_id AND cnt.content_type = 1 limit 0,1) AS file_path,
                                 (SELECT cnt.links FROM class AS ca 
                                 LEFT JOIN class_content AS cc ON ca.class_id = cc.class_id
                                 LEFT JOIN content AS cnt ON cc.content_id = cnt.content_id 
                                 WHERE ca.class_id = c.class_id AND cnt.content_type = 1 limit 0,1) AS links
                                 FROM class AS c WHERE c.class_id = {$data}")->result_array();
        return $list;
    }

    public function checkClassContent($classId, $contentId) {
        $result = $this->db->query("SELECT id FROM class_content 
                                    WHERE class_id = {$classId} 
                                    AND content_id = {$contentId}")->result_array();
        return $result;
    }

    public function editClassContent($params)
    {
        $result = $this->db->query("SELECT id FROM class_content WHERE class_id = {$params['class_id']} AND content_id = {$params['content_id']} 
                                    AND start_date = '{$params['start_date']}' AND end_date = '{$params['end_date']}' 
                                    AND id != '{$params['class_content_id']}'")->result_array();
        return $result;
    }

    public function viewResources($params) {
        $schoolAutoReview = '';
        $condition = '';
        $condition1 = "cc.start_date ASC";
        if (isset($params['type'])) {
            if ($params['type'] == 0) {
                $condition = '';
            } elseif ($params['type'] == 1) {
                //upcoming
                $condition = "AND cc.start_date > CURRENT_DATE()";
            } elseif ($params['type'] == 2) {
                //inprogress
                $condition = "AND cc.start_date <= CURRENT_DATE() AND (cc.end_date >= CURRENT_DATE() OR cc.end_date = '0000-00-00')";
            } elseif ($params['type'] == 3) {
                //completed
                $condition = "AND cc.end_date < CURRENT_DATE() AND cc.end_date != '0000-00-00'";
                $condition1 = "cc.start_date DESC";
            }
        }
        if (isset($params['school_id']) && $params['school_id'] > 0) {
            $schoolAutoReview = ", COALESCE((SELECT value FROM admin_settings_school WHERE name = 'allow_autograde_settings' AND school_id = {$params['school_id']}),'') as allow_autograde";
        }
        $result = $this->db->query("SELECT cnt.content_id, cnt.name AS content_name, c.class_name, 
                                    COALESCE (cnt.description, '') AS description,
                                    COALESCE (cnt.profile_url, '') AS profile_url, 
                                    COALESCE (cnt.profile_thumb_url, '') AS profile_thumb_url,
                                    COALESCE (cnt.file_path, '') AS file_path, 
                                    COALESCE (cnt.links, '') AS links, 
                                    COALESCE (cnt.file_text, '') AS file_text, 
                                    COALESCE (cnt.tags,'') AS tags,cnt.content_type, cnt.content_format, cc.start_date,
                                    cc.end_date, TIME_FORMAT(cc.start_time, '%h:%i %p') as start_time,
                                    TIME_FORMAT(cc.end_time, '%h:%i %p') as end_time,cc.id as class_content_id,
                                    (CASE WHEN cc.start_date > CURRENT_DATE() THEN 2
                                    WHEN cc.end_date >= CURRENT_DATE() THEN 3
                                    WHEN cc.end_date < CURRENT_DATE() THEN 4 ELSE 1 END) as content_date_status,
                                    cc.topic_id,COALESCE (t.topic, '') as topic,cc.auto_review $schoolAutoReview
                                    FROM class AS c 
                                    LEFT JOIN class_content AS cc ON c.class_id = cc.class_id
                                    LEFT JOIN content AS cnt ON cc.content_id = cnt.content_id
                                    LEFT JOIN topic AS t ON t.topic_id = cc.topic_id
                                    WHERE c.class_id = {$params['class_id']} AND cnt.content_type = 1 
                                    AND cnt.status = 1 AND c.status = 1 AND cc.status = 1 $condition 
                                    ORDER BY CASE WHEN cc.start_date <= CURRENT_DATE()
                                    AND cc.end_date != '0000-00-00' AND cc.end_date >= CURRENT_DATE() THEN 1
                                    WHEN cc.start_date <= CURRENT_DATE() AND cc.end_date = '0000-00-00' THEN 2
                                    WHEN cc.start_date >= CURRENT_DATE() THEN 3 ELSE 4 END, $condition1")->result_array();
        return $result;
    }

    public function viewAssignments($params) {
        $schoolAutoReview = '';
        $condition = '';
        $condition1 = "cc.start_date ASC";
        if (isset($params['type'])) {
            if ($params['type'] == 0) {
                $condition = '';
            } elseif ($params['type'] == 1) {
                //upcoming
                $condition = "AND cc.start_date > CURRENT_DATE()";
            } elseif ($params['type'] == 2) {
                //inprogress
                $condition = "AND cc.start_date <= CURRENT_DATE() AND (cc.end_date >= CURRENT_DATE() OR cc.end_date = '0000-00-00')";
            } elseif ($params['type'] == 3) {
                //completed
                $condition = "AND cc.end_date < CURRENT_DATE() AND cc.end_date != '0000-00-00'";
                $condition1 = "cc.start_date DESC";
            }
        }
        if (isset($params['school_id']) && $params['school_id'] > 0) {
            $schoolAutoReview = ", COALESCE((SELECT value FROM admin_settings_school WHERE name = 'allow_autograde_settings' AND school_id = {$params['school_id']}),'') as allow_autograde";
        }
        $result = $this->db->query("SELECT cnt.content_id, cnt.name AS content_name, c.class_name, c.class_id,
                                    COALESCE (cnt.description, '') AS description,
                                    COALESCE (cnt.profile_url, '') AS profile_url, 
                                    COALESCE (cnt.profile_thumb_url, '') AS profile_thumb_url,
                                    COALESCE (cnt.file_path, '') AS file_path, 
                                    COALESCE (cnt.teacher_version, '') AS teacher_version_path,
                                    COALESCE (cnt.links, '') AS links,
                                    COALESCE (cnt.file_text, '') AS file_text,
                                    COALESCE (cnt.tags,'') AS tags,cnt.content_type, cnt.content_format, cc.start_date , cc.end_date,
                                    TIME_FORMAT(cc.start_time, '%h:%i %p') as start_time,
                                    TIME_FORMAT(cc.end_time, '%h:%i %p') as end_time,cc.id as class_content_id,
                                    (CASE WHEN cc.start_date > CURRENT_DATE() THEN 2
                                    WHEN cc.end_date >= CURRENT_DATE() THEN 3
                                    WHEN cc.end_date < CURRENT_DATE() THEN 4 ELSE 1 END) as content_date_status,
                                    cc.topic_id,COALESCE (t.topic, '') as topic,cc.auto_review $schoolAutoReview
                                    FROM class AS c 
                                    LEFT JOIN class_content AS cc ON c.class_id = cc.class_id
                                    LEFT JOIN content AS cnt ON cc.content_id = cnt.content_id
                                    LEFT JOIN topic AS t ON t.topic_id = cc.topic_id
                                    WHERE c.class_id = {$params['class_id']} AND cnt.content_type = 2
                                    AND cnt.status = 1 AND c.status = 1 AND cc.status = 1 $condition 
                                    ORDER BY CASE WHEN cc.start_date <= CURRENT_DATE()
                                    AND cc.end_date != '0000-00-00' AND cc.end_date >= CURRENT_DATE() THEN 1
                                    WHEN cc.start_date <= CURRENT_DATE() AND cc.end_date = '0000-00-00' THEN 2
                                    WHEN cc.start_date >= CURRENT_DATE() THEN 3 ELSE 4 END, $condition1")->result_array();
        return $result;
    }

    public function viewAssessments($params) {
        $schoolAutoReview = '';
        $condition = '';
        $condition1 = "cc.start_date ASC";
        if (isset($params['type'])) {
            if ($params['type'] == 0) {
                $condition = '';
            } elseif ($params['type'] == 1) {
                //upcoming
                $condition = "AND cc.start_date > CURRENT_DATE()";
            } elseif ($params['type'] == 2) {
                //inprogress
                $condition = "AND cc.start_date <= CURRENT_DATE() AND (cc.end_date >= CURRENT_DATE() OR cc.end_date = '0000-00-00')";
                //$condition = "AND cc.start_date <= CURRENT_DATE() AND cc.end_date >= CURRENT_DATE()";
            } elseif ($params['type'] == 3) {
                //completed
                $condition = "AND cc.end_date < CURRENT_DATE() AND cc.end_date != '0000-00-00'";
                $condition1 = "cc.end_date DESC";

            }
        }
        if (isset($params['school_id']) && $params['school_id'] > 0) {
            $schoolAutoReview = ", COALESCE((SELECT value FROM admin_settings_school WHERE name = 'allow_autograde_settings' AND school_id = {$params['school_id']}),'') as allow_autograde";
        }
        $result = $this->db->query("SELECT cnt.content_id, cnt.name AS content_name, c.class_name, c.class_id,
                                    COALESCE (cnt.description, '') AS description,
                                    COALESCE (cnt.profile_url, '') AS profile_url, 
                                    COALESCE (cnt.profile_thumb_url, '') AS profile_thumb_url,
                                    COALESCE (cnt.file_path, '') AS file_path, 
                                    COALESCE (cnt.teacher_version, '') AS teacher_version_path,
                                    COALESCE (cnt.links, '') AS links,
                                    COALESCE (cnt.file_text, '') AS file_text, 
                                    COALESCE (cnt.tags,'') AS tags,cnt.content_type, cnt.content_format, cc.start_date , cc.end_date,
                                    TIME_FORMAT(cc.start_time, '%h:%i %p') as start_time,
                                    TIME_FORMAT(cc.end_time, '%h:%i %p') as end_time,cc.id as class_content_id,
                                    (CASE WHEN cc.start_date > CURRENT_DATE() THEN 2
                                    WHEN cc.end_date >= CURRENT_DATE() THEN 3
                                    WHEN cc.end_date < CURRENT_DATE() THEN 4 ELSE 1 END) as content_date_status,
                                    cc.topic_id,COALESCE (t.topic, '') as topic,cc.auto_review $schoolAutoReview
                                    FROM class AS c 
                                    LEFT JOIN class_content AS cc ON c.class_id = cc.class_id
                                    LEFT JOIN content AS cnt ON cc.content_id = cnt.content_id
                                    LEFT JOIN topic AS t ON t.topic_id = cc.topic_id
                                    WHERE c.class_id = {$params['class_id']} AND cnt.content_type = 3 
                                    AND cnt.status = 1 AND c.status = 1 AND cc.status = 1 $condition 
                                    ORDER BY CASE WHEN cc.start_date <= CURRENT_DATE()
                                    AND cc.end_date != '0000-00-00' AND cc.end_date >= CURRENT_DATE() THEN 1
                                    WHEN cc.start_date <= CURRENT_DATE() AND cc.end_date = '0000-00-00' THEN 2
                                    WHEN cc.start_date >= CURRENT_DATE() THEN 3 ELSE 4 END, $condition1")->result_array();
        return $result;
    }

    public function curriculumList($params)
    {
        $schoolAutoReview = '';
        $condition = '';
        $condition1 = "cc.start_date ASC";
        if (isset($params['type'])) {
            if ($params['type'] == 0) {
                $condition = '';
            } elseif ($params['type'] == 1) {
                //upcoming
                $condition = "AND cc.start_date > CURRENT_DATE()";
            } elseif ($params['type'] == 2) {
                //inprogress
                $condition = "AND cc.start_date <= CURRENT_DATE() AND (cc.end_date >= CURRENT_DATE() OR cc.end_date = '0000-00-00')";
            } elseif ($params['type'] == 3) {
                //completed
                $condition = "AND cc.end_date < CURRENT_DATE() AND cc.end_date != '0000-00-00'";
                $condition1 = "cc.start_date DESC";
            }
        }
        if (isset($params['school_id']) && $params['school_id'] > 0) {
            $schoolAutoReview = ", COALESCE((SELECT value FROM admin_settings_school WHERE name = 'allow_autograde_settings' AND school_id = {$params['school_id']}),'') as allow_autograde";
        }
        $end_date_condition = '';
        $prop = parse_ini_file('../properties.ini', true, INI_SCANNER_RAW);
        if (isset($prop['class_date_validation_days']) && $prop['class_date_validation_days'] != '') {
            $days = '-' . $prop['class_date_validation_days'] . ' days';
            $date = date('Y-m-d', strtotime($days));
            $end_date_condition = "AND ((cc.end_date = '0000-00-00' AND c.end_date > '{$date}')
                                   OR (cc.end_date != '0000-00-00' AND cc.end_date > '{$date}'))";
        }
        $result = $this->db->query("SELECT cnt.content_id, cnt.name AS content_name, c.class_name, c.class_id,
                                    COALESCE (cnt.description, '') AS description,
                                    COALESCE (cnt.profile_url, '') AS profile_url, 
                                    COALESCE (cnt.profile_thumb_url, '') AS profile_thumb_url,
                                    COALESCE (cnt.file_path, '') AS file_path, 
                                    COALESCE (cnt.teacher_version, '') AS teacher_version_path,
                                    COALESCE (cnt.links, '') AS links,
                                    COALESCE (cnt.file_text, '') AS file_text,
                                    COALESCE (cnt.tags,'') AS tags,cnt.content_type, cnt.content_format, cc.start_date , cc.end_date,
                                    TIME_FORMAT(cc.start_time, '%h:%i %p') as start_time,
                                    TIME_FORMAT(cc.end_time, '%h:%i %p') as end_time,cc.id as class_content_id,
                                    (CASE WHEN cc.start_date > CURRENT_DATE() THEN 2
                                    WHEN cc.end_date >= CURRENT_DATE() THEN 3
                                    WHEN cc.end_date < CURRENT_DATE() THEN 4 ELSE 1 END) as content_date_status,
                                    (CASE WHEN EXISTS ( SELECT 1 FROM text_questions tq WHERE tq.question_type_id = 55 
                                    AND tq.content_id = cnt.content_id AND cnt.content_format = 3) THEN 1 ELSE 0 END) AS is_essay,
                                    cc.topic_id,COALESCE (t.topic, '') as topic,cc.auto_review $schoolAutoReview
                                    FROM class AS c 
                                    LEFT JOIN class_content AS cc ON c.class_id = cc.class_id
                                    LEFT JOIN content AS cnt ON cc.content_id = cnt.content_id
                                    LEFT JOIN topic AS t ON t.topic_id = cc.topic_id
                                    WHERE c.class_id = {$params['class_id']}
                                    AND cnt.status = 1 AND c.status = 1 AND cc.status = 1 $condition  $end_date_condition
                                    ORDER BY CASE WHEN cc.start_date <= CURRENT_DATE()
                                    AND cc.end_date != '0000-00-00' AND cc.end_date >= CURRENT_DATE() THEN 1
                                    WHEN cc.start_date <= CURRENT_DATE() AND cc.end_date = '0000-00-00' THEN 2
                                    WHEN cc.start_date >= CURRENT_DATE() THEN 3 ELSE 4 END, $condition1")->result_array();
        return $result;
    }

    public function checkRecord($slot, $classId) {
        $result = $this->db->query("SELECT id FROM class_schedule 
                                    WHERE class_id = {$classId} 
                                    AND slot_days = {$slot}")->result_array();
        return $result;
    }

    public function checkSchedule($classId,$scheduleIds,$type) {
        if($type == 1){
            $condition = "AND id NOT IN ($scheduleIds)";
        } else {
            $condition = "";
        }
        $data = $this->db->query("SELECT GROUP_CONCAT(DISTINCT(id)) as id from class_schedule where class_id = '$classId' $condition")->result_array();
        return $data;
    }

    public function checkZoom($params,$schedule,$scheduleId,$type) {

        if ($type == 1) {
            $condition = '';
        } elseif ($type == 2) {
            $condition = "AND class_id = '{$params['class_id']}' AND schedule_id != $scheduleId";
        } elseif ($type == 3) {
            $condition = "AND schedule_id != '{$scheduleId}'";
        }
        $data = $this->db->query("SELECT DISTINCT(user_email) as user_email,meeting_id,schedule_id,class_id from zoom_creation_email 
                        where slot_days = '{$schedule['slotday']}' 
                        AND (((start_time <= '{$schedule['slotstarttime']}' 
                        AND end_time >= '{$schedule['slotendtime']}')
                        OR (start_time < '{$schedule['slotendtime']}' 
                        AND end_time >= '{$schedule['slotendtime']}')
                        OR (start_time >= '{$schedule['slotstarttime']}' 
                        AND end_time <= '{$schedule['slotendtime']}')
                        OR (start_time <= '{$schedule['slotstarttime']}'
                        AND end_time <= '{$schedule['slotendtime']}' 
                        AND end_time >= '{$schedule['slotstarttime']}')
                        OR (start_time >= '{$schedule['slotstarttime']}'
                        AND end_time >= '{$schedule['slotstarttime']}' 
                        AND start_time <= '{$schedule['slotendtime']}'))
                        OR (start_time = '{$schedule['slotstarttime']}'
                        AND end_time = '{$schedule['slotendtime']}'))
                        AND start_date = '{$params['start_date']}'
                        $condition")->result_array();
        return $data;
    }

    public function checkScheduleExists($params,$type) {
        $params['slotstarttime'] = date("H:i",strtotime($params['slotstarttime']));
        $params['slotendtime'] = date("H:i",strtotime($params['slotendtime']));
        if($type == 1) {
            $condition = "schedule_id = '{$params['schedule_id']}' AND start_date = '{$params['start_date']}'";
        } elseif($type == 2) {
            $condition = "schedule_id = '{$params['schedule_id']}' AND start_time = '{$params['slotstarttime']}' AND end_time = '{$params['slotendtime']}' AND start_date = '{$params['start_date']}'";
        } else {
            $condition = "schedule_id != '{$params['schedule_id']}' AND start_time = '{$params['slotstarttime']}' AND end_time = '{$params['slotendtime']}";
        }
        $result = $this->db->query("SELECT * FROM zoom_creation_email WHERE 
                                    $condition ORDER BY id DESC")->result_array();
        return $result;
    }

    public function getScheduleEmailId($scheduleId) {
        $result = $this->db->query("SELECT * FROM zoom_creation_email WHERE schedule_id = '{$scheduleId}' ORDER BY id desc limit 0,1")->row_array();
        return $result;
    }

    public function getEmailId($params) {
        $data = $this->db->query("SELECT value from admin_settings_school where school_id = '{$params['school_id']}'
                                 AND name = 'zoom_user_email'")->result_array();
        return $data;
    }

    public function getZoomApi($params) {
        $data = $this->db->query("SELECT value from admin_settings_school where school_id = '{$params['school_id']}'
                                 AND name = 'allow_zoom_api'")->row_array();
        return $data;
    }

    public function checkUpdatedRecord($date, $value, $userId, $classId) {
        $condition = '';
        if($userId != '') {
            $condition = "AND teacher_id = '{$userId}'";
        }
        $result = $this->db->query("SELECT id,created_date,class_id FROM updated_class_schedule 
                                    WHERE class_id = {$classId} AND start_time = '{$value['slotstarttime']}'
                                    AND end_time = '{$value['slotendtime']}' 
                                    AND date = '{$date}' AND meeting_id = '{$value['meeting_id']}'
                                    AND meeting_link = '{$value['meeting_link']}' AND passcode = '{$value['passcode']}'
                                    AND telephone_number = '{$value['telephone_number']}'$condition")->result_array();
        return $result;
    }

    public function getSchedule($params) {
        $data = $this->db->query("SELECT start_time,end_time,slot_days from class_schedule where class_id = '{$params['class_id']}' AND slot_days = '{$params['slotdays']}'")->result_array();
        return $data;
    }

    public function checkPreviousClass($slot, $value, $userId, $classId) {
        $condition = '';
        if($userId != '') {
            $condition = "AND teacher_id = '{$userId}'";
        }
        $result = $this->db->query("SELECT id FROM class_schedule 
                                    WHERE class_id = {$classId} AND start_time = '{$value['slotstarttime']}'
                                    AND end_time = '{$value['slotendtime']}' 
                                    AND slot_days = '{$slot}' AND meeting_id = '{$value['meeting_id']}'
                                    AND meeting_link = '{$value['meeting_link']}' AND passcode = '{$value['passcode']}'
                                    AND telephone_number = '{$value['telephone_number']}' $condition")->result_array();
        return $result;
    }

    public function teacherList($params) {
        $list = $this->db->query("SELECT u.user_id AS teacher_id, 
                                  CONCAT_WS(' ',first_name,last_name) AS teacher_name 
                                  FROM user u 
                                  LEFT JOIN user_profile up ON up.user_id = u.user_id
                                  LEFT JOIN user_profile_details upd ON u.user_id = upd.user_id 
                                  WHERE upd.school_id = {$params['school_id']} 
                                  AND u.role_id = 4 AND u.status = 1 ORDER BY up.first_name ASC")->result_array();
        return $list;
    }

    public function getClassScheduleOld($params) {
        $teacherId = "";
        if (isset($params['teacher_id']) && $params['teacher_id'] > 0) {
            $teacherId = "AND FIND_IN_SET('{$params['teacher_id']}',cs.teacher_id)";
        }
        if ($params['role_id'] > 2 && $params['role_id'] != 6) {
            $teacherIds = "{$params['user_id']} as teacher_id";
            $teacherNames = "(SELECT CONCAT_WS(' ',first_name,last_name) FROM user_profile WHERE user_id = {$params['user_id']}) as teacher_name";
        } else {
            $teacherIds = "cs.teacher_id";
            $teacherNames = "(SELECT GROUP_CONCAT(CONCAT_WS(' ',first_name,last_name)) FROM user_profile 
                              WHERE user_id IN (cs.teacher_id)) AS teacher_name";
        }
        $list = $this->db->query("SELECT $teacherIds,$teacherNames,cs.id as shechdule_id,
                                  (CASE WHEN cs.slotselected = 1 THEN 'true' ELSE 'false' END) AS slotselected,
                                  cs.start_time AS slotstarttime, cs.end_time AS slotendtime, 
                                  (SELECT class_name FROM class WHERE class_id = cs.class_id) AS class_name,
                                  slot_days AS slotday,cs.meeting_link,cs.meeting_id,cs.passcode,
                                  COALESCE (cs.telephone_number,'') AS telephone_number FROM class_schedule cs
                                  WHERE cs.class_id = {$params['class_id']} AND cs.school_id = {$params['school_id']}
                                  $teacherId")->result_array();
//        print_r($this->db->last_query());exit;
        return $list;
    }

    public function getClassSchedule($params) {
        $teacherId = "";
        $condition ="";
        if (isset($params['teacher_id']) && $params['teacher_id'] > 0) {
            $teacherId = "AND FIND_IN_SET('{$params['teacher_id']}',cs.teacher_id)";
        }
        if ($params['role_id'] > 2 && $params['role_id'] != 6 && $params['role_id'] != 5) {
            $teacherIds = "{$params['user_id']} as teacher_id";
            $teacherNames = "(SELECT CONCAT_WS(' ',first_name,last_name) FROM user_profile 
                              WHERE user_id = {$params['user_id']}) AS teacher_name";
        } else {
            $teacherIds = "cs.teacher_id";
            $teacherNames = "(SELECT GROUP_CONCAT(CONCAT_WS(' ',first_name,last_name)) FROM user_profile m
                              WHERE FIND_IN_SET(user_id,cs.teacher_id)) AS teacher_name";
        }
        $list = $this->db->query("SELECT $teacherIds, $teacherNames, cs.class_id,cs.id as shechdule_id, cl.class_name,cl.class_type,
                                  (SELECT value FROM admin_settings_school WHERE school_id = '{$params['school_id']}' AND name = 'teacher_zoom_view') as teacher_zoom_view,
                                  (SELECT value FROM admin_settings_school WHERE school_id = '{$params['school_id']}' AND name = 'student_zoom_view') as student_zoom_view,
                                  (CASE WHEN cs.slotselected = 1 THEN 'true' ELSE 'false' END) AS slotselected,
                                  cs.start_time AS slotstarttime, cs.end_time AS slotendtime,
                                  slot_days AS slotday,cs.teacher_link,cs.student_link,cs.meeting_link,cs.meeting_id,cs.passcode, COALESCE (cs.telephone_number,'') AS telephone_number 
                                  FROM class_schedule cs
                                  LEFT JOIN class cl on cs.class_id = cl.class_id
                                  WHERE cs.class_id = {$params['class_id']} AND cs.school_id = {$params['school_id']}
                                  $teacherId")->result_array();
        //print_r($this->db->last_query()); exit();
        return $list;
    }

    public function getClassList($params) {
        $condition = '';
        $leftJoin = '';
        $classCondition = '';
        if ($params['batch_id'] == '' && $params['role_id'] == 4) {
            $condition = "AND FIND_IN_SET('{$params['user_id']}',cs.teacher_id)";
        } elseif($params['batch_id'] != '' && $params['role_id'] == 4) {
            $condition = "AND c.batch_id = {$params['batch_id']} AND FIND_IN_SET('{$params['user_id']}',cs.teacher_id)";
        } elseif($params['batch_id'] != '' && $params['role_id'] == 2) {
            $condition = "AND c.batch_id = {$params['batch_id']}";
        }
        if ($params['role_id'] == 2 || $params['role_id'] == 6) {
            $classId = "c.class_id";
        } else {
            $classId = "DISTINCT(cs.class_id) as class_id";
            $leftJoin = "LEFT JOIN class_schedule as cs ON c.class_id = cs.class_id";
        }
        if (isset($params['teacher_id']) && $params['teacher_id'] > 0 && $params['role_id'] == 2) {
            $classId = "DISTINCT(cs.class_id) as class_id";
            $leftJoin = "LEFT JOIN class_schedule as cs ON c.class_id = cs.class_id";
            $condition = "AND FIND_IN_SET('{$params['teacher_id']}',cs.teacher_id)";
        }
        if (isset($params['class_id']) && $params['class_id'] > 0) {
            $classCondition = "AND c.class_id = {$params['class_id']}";
        }
        $result = $this->db->query("SELECT $classId, c.teacher_id, c.start_date, c.end_date, c.school_id
                                    FROM class c $leftJoin 
                                    WHERE c.school_id = {$params['school_id']} 
                                    AND c.status = 1 AND c.class_status != 1 $condition $classCondition")->result_array();
        return $result;
    }

    public function getUpdatedClassScheduleOld($params) {
        $teacherId = "";
        if (isset($params['teacher_id']) && $params['teacher_id'] > 0) {
            $teacherId = "AND FIND_IN_SET('{$params['user_id']}',cs.teacher_id)";
        }
        $list = $this->db->query("SELECT cs.class_id, cs.teacher_id,
                                 (SELECT GROUP_CONCAT(CONCAT_WS(' ',first_name,last_name)) FROM user_profile 
                                  WHERE user_id IN(cs.teacher_id)) AS teacher_name,cs.start_time AS slotstarttime, 
                                  cs.end_time AS slotendtime,cs.meeting_link,cs.meeting_id,cs.passcode,
                                  COALESCE (cs.telephone_number,'') AS telephone_number, cs.date, 
                                  (SELECT class_name FROM class 
                                  WHERE class_id = cs.class_id) AS class_name,cs.slot_days AS slotday,cs.status
                                  FROM updated_class_schedule cs
                                  WHERE cs.class_id = {$params['class_id']} $teacherId
                                  AND cs.school_id = {$params['school_id']}")->result_array();
        return $list;
    }

    public function getUpdatedClassSchedule($params) {
        $teacherId = "";
        if (isset($params['teacher_id']) && $params['teacher_id'] > 0) {
            $teacherId = "AND FIND_IN_SET('{$params['user_id']}',cs.teacher_id)";
        }
        $list = $this->db->query("SELECT cs.class_id, cs.teacher_id, cs.start_time AS slotstarttime, cs.end_time AS slotendtime, cs.meeting_link, cs.meeting_id, 
                                  CONCAT_WS(' ',u.first_name, u.last_name) AS teacher_name, COALESCE (cs.telephone_number,'') AS telephone_number, 
                                  cl.class_name, cs.date, cs.slot_days AS slotday, cs.passcode, cs.status
                                  FROM updated_class_schedule cs
                                  LEFT JOIN class cl on cs.class_id = cl.class_id
                                  LEFT JOIN user_profile u on cs.teacher_id = u.user_id
                                  WHERE cs.class_id = '{$params['class_id']}' $teacherId
                                  AND cs.school_id = '{$params['school_id']}' ")->result_array();
        return $list;
    }

    public function getCalendar($params) {
        $result = $this->db->query("SELECT id, school_id, from_date, to_date, festival_name FROM holiday_calendar 
                                    WHERE from_date >= '{$params['from_date']}'
                                    AND to_date <= '{$params['to_date']}' 
                                    AND  school_id = {$params['school_id']}")->result_array();
        return $result;
    }

    public function getClassroomContent($batchId) {
        $result = $this->db->query("SELECT content_id,start_time,end_time,auto_review FROM classroom_content 
                                    WHERE batch_id IN ({$batchId}) AND status = 1")->result_array();
        return $result;
    }

    public function getClassContent($params) {
        $result = $this->db->query("SELECT id,content_id,start_date,end_date FROM class_content 
                                    WHERE class_id = {$params['class_id']} AND all_student = 1 
                                    AND (end_date >= CURRENT_DATE() OR end_date = '0000-00-00')")->result_array();
        return $result;
    }

    public function getTeacherName($id) {
        return $this->db->query("SELECT GROUP_CONCAT(user_id) as teacher_id,GROUP_CONCAT(first_name) AS teacher_name FROM user_profile
                                 WHERE user_id IN ({$id})")->row_array();
    }

    public function checkNotes($classId,$notes) {
        $result = $this->db->query("SELECT id FROM class_notes WHERE class_id = $classId 
                                    AND note = '$notes' AND status != 2")->result_array();
        return $result;
    }

    public function getClassNotes($classId) {
        $result = $this->db->query("SELECT n.id,n.class_id,c.class_name,n.note,n.status,n.add_date,
                                    CONCAT_WS(' ',u.first_name,u.last_name) as created_by,n.created_by as user_id,n.created_date,
                                    (SELECT COUNT(*) FROM note_comments WHERE note_id = n.id AND status = 1) as comment_count,
                                    (SELECT COUNT(*) FROM note_comments WHERE note_id = n.id AND status = 1 AND is_read = 0) as new_comments
         FROM class_notes as n
         INNER JOIN class as c ON n.class_id = c.class_id
         INNER JOIN user_profile as u ON u.user_id = n.created_by
         WHERE n.class_id IN ($classId) AND n.status = 1 ORDER BY n.id DESC")->result_array();
        return $result;
    }
    public function getContentType($contentId){
        $List = $this->db->query("select content_type,content_format from content where content_id = '{$contentId}' ")->row_array();
        return $List;
    }
    public function name($contentId,$studentId,$classId){
        $List = $this->db->query("SELECT name as content_name , (SELECT CONCAT_WS(' ',first_name,last_name) from user_profile where user_id = '{$studentId}') as student_name ,
                                    (SELECT class_name FROM class WHERE class_id = '{$classId}') as class_name FROM content WHERE content_id = '{$contentId}' ")->result_array();
        return $List;
    }
    public function check($starttime,$endtime,$slotday,$classId,$date){
        $getList = $this->db->query("SELECT *  FROM class_attendance WHERE start_time = '{$starttime}' AND end_time = '{$endtime}' AND slot_day = '{$slotday}' AND class_id = '{$classId}'AND date = '{$date}' ")->result_array();
        return $getList;
    }
    public function checkStudentAttendence($starttime,$endtime,$slotday,$classId,$date,$studentId){
        $List = $this->db->query("SELECT id as attendance_id FROM class_attendance WHERE start_time = '{$starttime}' AND end_time = '{$endtime}' AND slot_day = '{$slotday}' AND class_id = '{$classId}' AND date = '{$date}'
                                  AND student_id = '{$studentId}'")->result_array();
        return $List;
    }
    public function scheduleList($params){
        $GetList = $this->db->query("SELECT id,start_time,end_time,slot_day,schedule_id,class_id,student_id,attendance,DATE_FORMAT(date,'%m-%d-%Y') as date,date as class_date,created_date,created_by,modified_date,modified_by
                                     FROM class_attendance WHERE start_time = '{$params['start_time']}' AND end_time = '{$params['end_time']}' AND slot_day = '{$params['slot_day']}' AND class_id = '{$params['class_id']}'")->result_array();
        return $GetList;
    }
//    public function getClassDate($shechduleId){
//        $getDate = $this->db->query("SELECT cs.created_date,cs.class_id,cs.id as schedule_id,c.start_date as dates,c.end_date FROM class_schedule as cs LEFT JOIN class as c ON c.class_id = cs.class_id WHERE id = '{$shechduleId}'")->result_array();
//        return $getDate;
//    }
    public function getClassDate($classId){
        $List = $this->db->query("SELECT DATE_FORMAT(start_date,'%d-%m-%Y') AS dates,class_code,class_name FROM class  WHERE class_id = '{$classId}'")->result_array();
        return $List;
    }
    public function getClassRecords($classId,$schoolId){
        $list = $this->db->query("SELECT c.school_id,c.class_name,c.grade,c.subject,c.tags,c.profile_url,c.profile_thumb_url,
                                  c.status,c.class_status,c.start_date,c.end_date,c.batch_id,c.video_link,c.meeting_link,
                                  c.meeting_id,c.class_type,c.passcode,c.telephone_number 
                                  FROM class as c
                                  WHERE c.class_id = {$classId} AND c.school_id = {$schoolId}
                                  ")->result_array();
        return $list;
    }
    public function classSchedule($classId){
        $list = $this->db->query("SELECT cs.teacher_id,u.first_name AS teacher_name,cs.id,
                                   (SELECT class_name FROM class 
                                  WHERE class_id = cs.class_id) AS class_name,cs.slotselected,cs.start_time,cs.end_time,cs.slot_days,
                                  cs.meeting_link,cs.meeting_id,cs.passcode,cs.telephone_number,cs.class_id  
                                  FROM class_schedule cs 
                                  LEFT JOIN user_profile u on cs.teacher_id = u.user_id
                                  WHERE cs.class_id = {$classId} 
                                  ")->result_array();
        return $list;
    }

    public function zoomApiPer($params) {
        return $this->db->query("SELECT name,value from admin_settings_school where school_id = '{$params['school_id']}' AND name = 'allow_zoom_api'")->result_array();
    }
    public function adminClassList($params) {
        $roleCondition = '';
        $studentCondition = '';
        $teacherName = "(select GROUP_CONCAT((CONCAT_WS(' ',first_name,last_name))) from user_profile where FIND_IN_SET(user_id,cs.teacher_id)) as teacher_name,";
        if($params['role_id']== 4){
            $teacherName = "(select (CONCAT_WS(' ',first_name,last_name)) from user_profile where user_id = '{$params['user_id']}') as teacher_name,";
            $roleCondition = "AND FIND_IN_SET('{$params['user_id']}',cs.teacher_id)";
        } elseif($params['role_id'] == 5){
            $studentCondition = "AND student_id = '{$params['user_id']}' ";
        }
        // date filter
        $date_filter = '';
        if (isset($params['date']) && $params['date'] != '') {
            $date_filter = "AND ((c.start_date <= '{$params['date']}' AND c.end_date >= '{$params['date']}')
                                  OR (c.start_date > '{$params['date']}')
                                  OR (c.end_date > '{$params['date']}'))";
        }
        if($params['role_id'] != 5){
            $data = $this->db->query("(SELECT DISTINCT(cs.class_id) as class_id,c.class_name,c.subject,c.grade,c.start_date,c.end_date,c.start_time as class_start_time,
                                 c.end_time as class_end_time,c.class_type,c.announcement_type,c.class_code,c.passcode,c.meeting_id,c.meeting_link,c.status,cs.start_time,cs.end_time,cs.teacher_id,cs.id as schedule_id,cs.slot_days,
                                  COALESCE ((SELECT GROUP_CONCAT(subject_name)
                                  FROM subject WHERE FIND_IN_SET(subject_id, c.subject)),'') AS subject_name,sc.status as student_class_status,
                                  $teacherName
                                  (select (CONCAT_WS(' ',first_name,last_name)) from user_profile where user_id = sc.student_id) as student_name,COALESCE (g.grade_name,'') AS student_grade_name,
                                  COALESCE ((SELECT GROUP_CONCAT(grade_name) FROM grade WHERE FIND_IN_SET(grade_id, c.grade)),'') AS grade_name,c.grade AS grade_id,
                                  (SELECT value from admin_settings_school where school_id = '{$params['school_id']}' AND name = 'allow_zoom_api')as allow_zoom_api,
                                  sc.student_id,sc.class_type as student_class_type,(CASE WHEN c.start_date > CURRENT_DATE() THEN 1 
                                    WHEN c.start_date <= CURRENT_DATE() AND c.end_date >= CURRENT_DATE() THEN 2 
                                    WHEN c.end_date < CURRENT_DATE() AND c.end_date != '0000-00-00' THEN 3 ELSE 2 END) AS class_date_status
                                 from class as c
                                 left join student_class as sc ON sc.class_id = c.class_id
                                 left join class_schedule as cs ON cs.class_id = c.class_id
                                 left join user_profile as up ON up.user_id = cs.teacher_id
                                 left join user_profile_details as upd ON upd.user_id = sc.student_id AND upd.school_id = c.school_id
                                 left join grade g ON upd.grade_id = g.grade_id
                                 where c.school_id = '{$params['school_id']}' AND c.status = 1 AND c.class_status = 0 AND cs.slot_days = '{$params['slot_id']}' AND sc.joining_date <= '{$params['date']}' 
                                 AND (sc.validity >= '{$params['date']}' OR sc.validity = '0000-00-00')  $roleCondition $studentCondition $date_filter ORDER BY cs.id)
                                 UNION (SELECT DISTINCT(cs.class_id) as class_id,c.class_name,c.subject,c.grade,c.start_date,c.end_date,c.start_time as class_start_time,
                                 c.end_time as class_end_time,c.class_type,c.announcement_type,c.class_code,c.passcode,c.meeting_id,c.meeting_link,c.status,cs.start_time,cs.end_time,cs.teacher_id,cs.id as schedule_id,cs.slot_days,
                                  COALESCE ((SELECT GROUP_CONCAT(subject_name)
                                  FROM subject WHERE FIND_IN_SET(subject_id, c.subject)),'') AS subject_name,0 as student_class_status,$teacherName
                                  COALESCE ((SELECT GROUP_CONCAT(grade_name) FROM grade WHERE FIND_IN_SET(grade_id, c.grade)),'') AS grade_name,c.grade AS grade_id,'' as allow_zoom_api,
                                  '' as student_id,0 as student_class_type,
                                  '' as student_name,'' AS student_grade_name,(CASE WHEN c.start_date > CURRENT_DATE() THEN 1 
                                    WHEN c.start_date <= CURRENT_DATE() AND c.end_date >= CURRENT_DATE() THEN 2 
                                    WHEN c.end_date < CURRENT_DATE() AND c.end_date != '0000-00-00' THEN 3 ELSE 2 END) AS class_date_status
                                 from class as c
                                 left join class_schedule as cs ON cs.class_id = c.class_id 
                                 left join user_profile as up ON up.user_id = cs.teacher_id
                                 where c.school_id = '{$params['school_id']}' AND c.status = 1 AND c.class_status = 0 AND cs.slot_days = '{$params['slot_id']}' $roleCondition
                                  AND c.class_id NOT IN (select class_id from student_class where class_id = c.class_id)
                                  $date_filter ORDER BY cs.id)")->result_array();
        } else {
            $data = $this->db->query("SELECT DISTINCT(cs.class_id) as class_id,c.class_name,c.subject,c.grade,c.start_date,c.end_date,c.start_time as class_start_time,
                                 c.end_time as class_end_time,c.class_type,c.announcement_type,c.class_code,c.passcode,c.meeting_id,c.meeting_link,c.status,cs.start_time,cs.end_time,cs.teacher_id,cs.id as schedule_id,cs.slot_days,
                                  COALESCE ((SELECT GROUP_CONCAT(subject_name)
                                  FROM subject WHERE FIND_IN_SET(subject_id, c.subject)),'') AS subject_name, sc.status as student_class_status,
                                  $teacherName
                                  (select (CONCAT_WS(' ',first_name,last_name)) from user_profile where user_id = sc.student_id) as student_name,
                                  COALESCE ((SELECT GROUP_CONCAT(grade_name) FROM grade WHERE FIND_IN_SET(grade_id, c.grade)),'') AS grade_name,c.grade AS grade_id,
                                  (SELECT value from admin_settings_school where school_id = '{$params['school_id']}' AND name = 'allow_zoom_api')as allow_zoom_api,
                                  sc.student_id,(CASE WHEN c.start_date > CURRENT_DATE() THEN 1 
                                    WHEN c.start_date <= CURRENT_DATE() AND c.end_date >= CURRENT_DATE() THEN 2 
                                    WHEN c.end_date < CURRENT_DATE() AND c.end_date != '0000-00-00' THEN 3 ELSE 2 END) AS class_date_status
                                 FROM class as c
                                 LEFT JOIN student_class as sc ON sc.class_id = c.class_id
                                 LEFT JOIN class_schedule as cs ON cs.class_id = c.class_id
                                 LEFT JOIN user_profile as up ON up.user_id = cs.teacher_id
                                 WHERE c.school_id = '{$params['school_id']}' AND sc.status = 1 
                                 AND c.status = 1 AND c.class_status = 0 AND cs.slot_days = '{$params['slot_id']}' 
                                 AND sc.joining_date <= '{$params['date']}' 
                                 AND (sc.validity >= '{$params['date']}' OR sc.validity = '0000-00-00')
                                 $roleCondition $studentCondition $date_filter ORDER BY cs.id")->result_array();
        }
       // print_r($this->db->last_query()); exit();
        return $data;
    }

    public function checkAttendance($params) {
        $data = $this->db->query("SELECT start_time,end_time,class_id,student_id,COALESCE(attendance,'') as attendance,date from class_attendance
                                  WHERE class_id ='{$params['class_id']}' AND start_time = '{$params['start_time']}' 
                                  AND end_time = '{$params['end_time']}' AND slot_day = '{$params['slot_days']}' 
                                  AND student_id = '{$params['student_id']}' AND date = '{$params['date']}'")->result_array();
        return $data;
    }

    public function getClassMeeting($classId, $day,$params,$zoom) {
        $link = '';
        $passcode = '';
        if ($params['role_id'] == 2 || $params['role_id'] == 4) {
            $link = "z.teacher_link as meeting_link,";
        } elseif ($params['role_id'] == 5) {
            $link = "z.student_link as meeting_link,";
        }
        if ($zoom == 0) {
            $passcode = "(SELECT passcode FROM class_schedule WHERE class_id = '$classId' AND slot_days = '$day') as passcode,";
        } else {
            $passcode = " '' as passcode,";
        }
        $result = $this->db->query("SELECT $link z.meeting_id, $passcode (SELECT  CONCAT_WS(' ',first_name,last_name ) AS student_name 
                                    FROM user_profile
                                    WHERE user_id IN ({$params['user_id']})) as user_name
                                    FROM zoom_creation_email as z
                                    WHERE class_id = {$classId} AND slot_days = {$day}")->result_array();
        return $result;
    }
    public function studentGrade($schoolId,$studentId){
        $query = $this->db->query("SELECT grade_id FROM user_profile_details WHERE school_id = {$schoolId} AND user_id = {$studentId}")->result_array();
        return $query;
    }

    public function getScheduleEmail($userEmail,$startDate)
    {
        $query = $this->db->query("SELECT user_email as user_email,meeting_id,id 
                                   FROM zoom_creation_email 
                                   WHERE user_email = '{$userEmail}' AND start_date = '{$startDate}'
                                   ORDER BY id DESC LIMIT 0,1")->result_array();
        return $query;
    }

    public function getScheduleEmailAll($userEmail,$startDate)
    {
        $query = $this->db->query("SELECT user_email as user_email,meeting_id,schedule_id,class_id
                                   FROM zoom_creation_email 
                                   WHERE user_email = '{$userEmail}' AND start_date = '{$startDate}'
                                   ORDER BY id DESC")->result_array();
        return $query;
    }
        public function zoomCreationEmail($scheduleId,$startDate){
        $query = $this->db->query("SELECT user_email,meeting_id from zoom_creation_email where schedule_id = '$scheduleId'
                                   AND DATE_FORMAT(start_date,'%d-%m-%Y') = '{$startDate}' ORDER BY id DESC")->result_array();
        return $query;
    }
    public function getClassStudents($classId,$schoolId){
        $query = $this->db->query("SELECT sc.student_id as user_id,u.email_id as student_id,up.first_name,
                                   up.last_name,sc.validity,upd.grade_id,
                                   (SELECT grade_name FROM grade WHERE grade_id = upd.grade_id) as grade_name
                                   FROM student_class as sc 
                                   LEFT JOIN user as u ON sc.student_id = u.user_id
                                   LEFT JOIN user_profile as up ON u.user_id = up.user_id
                                   LEFT JOIN user_profile_details as upd ON u.user_id = upd.user_id
                                   WHERE sc.class_id = $classId and sc.status = 1 AND upd.school_id = $schoolId")->result_array();
        return $query;
    }
    public function getClassId($data){
        $query = $this->db->query("SELECT c.class_id,c.school_id,c.class_name,c.class_code,c.end_date,c.grade,(select id from class_schedule where class_id = c.class_id limit 0,1) as schedule_id,
                                  (select user_id from user WHERE role_id = 2 AND FIND_IN_SET(c.school_id,school_id) limit 0,1) as admin
                                  FROM class as c
                                  WHERE c.class_code = '{$data['class_code']}'")->result_array();
        return $query;
    }
    public function checkStudentExist($studentId,$classId){
        $query = $this->db->query("SELECT id,class_id,status
                                  FROM student_class
                                  WHERE student_id = $studentId AND class_id = $classId")->result_array();
        return $query;
    }
    public function loginEnroll($data){

        $query = $this->db->query("SELECT u.user_id, u.role_id AS role, upd.individual_teacher,upd.school_id AS school, 
                    upd.status, u.school_id, u.email_id, u.mobile,  u.default_password,  u.mobile
                    FROM user u
                    LEFT JOIN user_profile up ON up.user_id = u.user_id
                    LEFT JOIN user_profile_details upd ON upd.user_id = u.user_id
                    WHERE email_id = '{$data['email_id']}' AND password = md5('{$data['password']}') AND upd.school_id = {$data['school_id']}
                    ")->result_array();
        return $query;
    }
    public function checkClassCode($classCode){
        $query = $this->db->query("SELECT class_id,class_name,class_code,start_date,end_date,school_id FROM class WHERE class_code = '{$classCode}'")->result_array();
        return $query;
    }

    public function studentDetails($params){
        $query = $this->db->query("SELECT u.user_id,upd.school_id AS school,
                                   CONCAT_WS(' ',up.first_name,up.last_name) as student_name,u.email_id
                                   FROM user u
                                   LEFT JOIN user_profile up ON up.user_id = u.user_id
                                   LEFT JOIN user_profile_details upd ON upd.user_id = u.user_id
                                   WHERE upd.school_id = {$params['school_id']} AND u.user_id IN ({$params['student_id']})")->result_array();
        return $query;
    }

    public function classTopic($params)
    {
        $condition ="";
        if(isset($params['class_id'])){
            if (is_array($params['class_id'])) {
                $params['class_id'] = implode(',', $params['class_id']);
            }
            $condition = "WHERE class_id IN ({$params['class_id']}) AND status = 1";
        }
        $topic_condition = "";
        if (isset($params['topic_id']) && $params['topic_id'] != '') {
            $topic_condition = "WHERE topic_id = {$params['topic_id']}";
        }
        $query = $this->db->query("SELECT topic_id,topic,class_id,start_date,end_date,display_order,status,created_by,created_date 
                                   FROM topic $condition $topic_condition ORDER BY display_order ASC")->result_array();
        return $query;
    }
    public function checkTopicExists($params,$type)
    {
        $topicName = $this->db->escape($params['topic']);
        $condition = "WHERE class_id = {$params['class_id']} AND topic = {$topicName}";
        if($type == 'edit'){
            $condition = "WHERE class_id = {$params['class_id']} AND topic_id != {$params['topic_id']} AND topic = {$topicName}";
        }
        $query = $this->db->query("SELECT topic_id,topic,class_id,status,start_date,end_date,created_by,created_date 
                                   FROM topic $condition")->result_array();
        return $query;
    }

    public function getTopicClasses($condition)
    {
        $result = $this->db->query("SELECT id,class_id,content_id,school_id,status,all_student,release_score,
                                    auto_review,start_date,end_date,start_time,end_time,notes,downloadable,topic_id,is_accessible
                                    created_by,created_date,modified_by,modified_date 
                                    FROM class_content 
                                    $condition")->result_array();
        return $result;
    }

    public function getAcademyClass($condition)
    {
        $query = $this->db->query("SELECT class_id FROM class $condition")->result_array();
        return $query;
    }
    public function classContentId($classId,$contentId,$startDate,$endDate)
    {
        $result = $this->db->query("SELECT id,content_id,start_date,end_date FROM class_content 
                                    WHERE class_id = {$classId} AND content_id = {$contentId}
                                    AND start_date = '{$startDate}' AND end_date = '{$endDate}'
                                    ")->result_array();
        return $result;
    }
    public function classMailNotification($condition){
        $query = $this->db->query("SELECT class_id,email_id,mail_sent,is_makeup,start_date,end_date FROM class_mail_notification $condition")->result_array();
        return $query;
    }

    public function getAnnouncementComments($noteId)
    {
        $result = $this->db->query("SELECT n.id,n.note_id,n.comment,n.status,comment_date,
                                   CONCAT_WS(' ',u.first_name,u.last_name) as created_by,n.created_by as user_id
                                   FROM note_comments as n
                                   INNER JOIN user_profile as u ON u.user_id = n.created_by
                                   WHERE n.note_id = $noteId AND n.status = 1 
                                   ORDER BY n.id DESC")->result_array();
        return $result;
    }
    public function getCommentCount($condition)
    {
        $result = $this->db->query("SELECT c.class_id,c.class_name,c.announcement_type,cn.id,cn.note,cn.created_by,cn.created_date
                                   FROM class as c
                                   INNER JOIN class_notes as cn ON cn.class_id = c.class_id
                                   INNER JOIN note_comments as n ON cn.id = n.note_id
                                   $condition ")->result_array();
        return $result;
    }

    public function getClassCode($condition){
        $result = $this->db->query("SELECT c.class_id,c.class_name,c.class_code,s.name 
                                    FROM class as c 
                                    INNER JOIN school as s ON c.school_id = s.school_id 
                                   $condition ")->result_array();
        return $result;
    }

    public function checkSubTopicExists($subTopicName){
        $result = $this->db->query("SELECT sub_topic_id FROM sub_topic WHERE sub_topic = '{$subTopicName}'")->result_array();
        return $result;
    }

}
