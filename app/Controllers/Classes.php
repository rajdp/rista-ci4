<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Traits\RestTrait;
use App\Models\V1\ClassesModel;
use CodeIgniter\HTTP\ResponseInterface;

class Classes extends BaseController
{
    use RestTrait;

    protected $classesModel;

    public function __construct()
    {
        $this->classesModel = new ClassesModel();
    }

    /**
     * Get teacher list for classes
     */
    public function teacherList(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            $teachers = $this->classesModel->getTeacherList($data->school_id ?? 0);
            
            // Remove entries with empty teacher names
            $teachers = array_filter($teachers, function($teacher) {
                return !empty(trim($teacher['teacher_name'] ?? ''));
            });
            
            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => array_values($teachers),
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get class list
     */
    public function list(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            $db = \Config\Database::connect();

            // Get classes with student counts
            $builder = $db->table('class c');
            $builder->select('c.class_id, c.class_name, c.subject, c.grade, c.start_date, c.end_date,
                             c.status, c.class_status, c.class_type, c.tags, c.class_code, c.batch_id,
                             c.meeting_link, c.meeting_id, c.passcode, c.announcement_type,
                             s.subject_name, g.grade_name,
                             (SELECT COUNT(*) FROM student_class sc 
                              WHERE sc.class_id = c.class_id AND sc.status = 1) as no_of_students,
                             (SELECT GROUP_CONCAT(DISTINCT teacher_id) FROM class_schedule 
                              WHERE class_id = c.class_id) as teacher_ids,
                             (SELECT CONCAT_WS(", ", GROUP_CONCAT(DISTINCT CONCAT_WS(" ", up.first_name, up.last_name)))
                              FROM class_schedule cs2
                              LEFT JOIN user_profile up ON FIND_IN_SET(up.user_id, cs2.teacher_id) > 0
                              WHERE cs2.class_id = c.class_id) as teacher_name');
            $builder->join('subject s', 'c.subject = s.subject_id', 'left');
            $builder->join('grade g', 'c.grade = g.grade_id', 'left');
            
            // Apply filters
            if (isset($params['school_id']) && !empty($params['school_id'])) {
                $builder->where('c.school_id', $params['school_id']);
            }
            
            // Unified search: search for class name OR student name
            $searchTerm = '';
            $hasSearch = false;
            
            if (isset($params['search']) && !empty(trim($params['search']))) {
                $searchTerm = trim($params['search']);
                $hasSearch = true;
            } elseif (isset($params['student_search']) && !empty(trim($params['student_search']))) {
                $searchTerm = trim($params['student_search']);
                $hasSearch = true;
            }
            
            if ($hasSearch) {
                // Join student tables for unified search
                $builder->join('student_class sc_search', 'sc_search.class_id = c.class_id', 'left');
                $builder->join('user_profile up_student', 'sc_search.student_id = up_student.user_id', 'left');
                
                // Use OR logic to search both class name and student name
                $builder->groupStart();
                    $builder->like('c.class_name', $searchTerm);
                    $builder->orLike('up_student.first_name', $searchTerm);
                    $builder->orLike('up_student.last_name', $searchTerm);
                $builder->groupEnd();
                
                // Only include active students or classes without students
                $builder->where('(sc_search.student_id IS NULL OR sc_search.status NOT IN (0, 3))', null, false);
            }
            
            // Add grade filter
            if (isset($params['grade']) && is_array($params['grade']) && count($params['grade']) > 0) {
                $builder->groupStart();
                foreach ($params['grade'] as $gradeId) {
                    $builder->orWhere("FIND_IN_SET('$gradeId', c.grade) >", 0);
                }
                $builder->groupEnd();
            }
            
            // Add subject filter
            if (isset($params['subject']) && is_array($params['subject']) && count($params['subject']) > 0) {
                $builder->groupStart();
                foreach ($params['subject'] as $subjectId) {
                    $builder->orWhere("FIND_IN_SET('$subjectId', c.subject) >", 0);
                }
                $builder->groupEnd();
            }
            
            // Add classroom/batch filter
            if (isset($params['classroom']) && !empty($params['classroom']) && $params['classroom'] != '0') {
                $builder->where("FIND_IN_SET('{$params['classroom']}', c.batch_id) >", 0);
            }
            
            // Add teacher filter
            if (isset($params['teacher_id']) && !empty($params['teacher_id']) && $params['teacher_id'] != '0') {
                $builder->join('class_schedule cs_teacher', 'cs_teacher.class_id = c.class_id', 'left');
                $builder->where("FIND_IN_SET('{$params['teacher_id']}', cs_teacher.teacher_id) >", 0);
            }
            
            // Add course filter
            if (isset($params['course_id']) && is_array($params['course_id']) && count($params['course_id']) > 0) {
                $builder->whereIn('c.course_id', $params['course_id']);
            }
            
            if (isset($params['type']) && !empty($params['type'])) {
                // Type filtering logic from CI3
                $builder->where('c.status', '1');
                
                // Type 2: Upcoming classes
                if ($params['type'] == 2) {
                    $builder->where('c.start_date >', date('Y-m-d'));
                }
                // Type 3: In progress classes
                elseif ($params['type'] == 3) {
                    $builder->where('c.start_date <=', date('Y-m-d'));
                    $builder->where('c.end_date >=', date('Y-m-d'));
                }
                // Type 4: Completed classes
                elseif ($params['type'] == 4) {
                    $builder->where('c.end_date <', date('Y-m-d'));
                }
            }

            // Add distinct to avoid duplicates from joins
            $builder->distinct();
            $builder->orderBy('c.class_id', 'DESC');
            
            // Pagination
            if (isset($params['page_no']) && isset($params['records_per_page'])) {
                $offset = ($params['page_no'] - 1) * $params['records_per_page'];
                $builder->limit($params['records_per_page'], $offset);
            }

            $classes = $builder->get()->getResultArray();

            // Process each class
            foreach ($classes as $key => $class) {
                // Ensure no_of_students is a number
                $classes[$key]['no_of_students'] = (int)($class['no_of_students'] ?? 0);
                
                // Process teacher_ids
                $classes[$key]['teacher_ids'] = !empty($class['teacher_ids']) ? explode(',', $class['teacher_ids']) : [];
            }
            
            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $classes,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Class list error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            log_message('error', 'SQL Query: ' . $db->getLastQuery());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => [],
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Create class
     */
    public function create(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            $classId = $this->classesModel->createClass($data);
            
            if ($classId) {
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => ['class_id' => $classId],
                    'ErrorObject' => ''
                ]);
                    } else {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Failed to create class'
                ]);
            }

        } catch (\Exception $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get class details for editing
     */
    public function edit($id = null): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            // Accept class_id from either URL parameter or POST data
            $classId = $id ?? $data->class_id ?? null;
            
            if (empty($classId)) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Class ID is required'
                ]);
            }

            $db = \Config\Database::connect();
            
            // Get class details for editing
            $builder = $db->table('class c');
            $builder->select('c.*, g.grade_name, g.grade_id, s.subject_name, s.subject_id');
            $builder->join('grade g', 'c.grade = g.grade_id', 'left');
            $builder->join('subject s', 'c.subject = s.subject_id', 'left');
            $builder->where('c.class_id', $classId);
            $classData = $builder->get()->getRowArray();

            if (empty($classData)) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Class not found'
                ]);
            }

            // Process the data for editing
            $classData['tags'] = !empty($classData['tags']) ? explode(',', $classData['tags']) : [];
            $classData['batch_id'] = !empty($classData['batch_id']) ? explode(',', $classData['batch_id']) : [];
            $classData['video_link'] = !empty($classData['video_link']) ? json_decode($classData['video_link'], true) : [];
            $classData['teacher_ids'] = !empty($classData['teacher_ids']) ? explode(',', $classData['teacher_ids']) : [];
            
            if (!is_array($classData['video_link'])) {
                $classData['video_link'] = [];
            }

            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $classData,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update class
     */
    public function updateClass(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            $result = $this->classesModel->updateClass($data->class_id ?? 0, $data);
            
            if ($result) {
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => ['message' => 'Class updated successfully'],
                    'ErrorObject' => ''
                ]);
                } else {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Failed to update class'
                ]);
            }

        } catch (\Exception $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get comment count for classes
     */
    public function getCommentCount(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            $commentCounts = $this->classesModel->getCommentCount($data ?? (object)[]);
            
            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $commentCounts,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get class detail with students, schedules, and notes
     */
    public function classDetail(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            // Validation
            if (empty($params['platform'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => [],
                    'ErrorObject' => 'Platform should not be empty'
                ]);
            }

            if (empty($params['role_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => [],
                    'ErrorObject' => 'Role_Id should not be empty'
                ]);
            }

            if (empty($params['user_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => [],
                    'ErrorObject' => 'User Id should not be empty'
                ]);
            }

            if (empty($params['school_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => [],
                    'ErrorObject' => 'School Id should not be empty'
                ]);
            }

            if (empty($params['class_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => [],
                    'ErrorObject' => 'Class Id should not be empty'
                ]);
            }

            $db = \Config\Database::connect();
            
            // Get class details
            $builder = $db->table('class c');
            $builder->select('c.*, g.grade_name, g.grade_id, s.subject_name, s.subject_id,
                             (CASE WHEN c.start_date > CURDATE() THEN "1"
                                   WHEN c.start_date <= CURDATE() AND c.end_date >= CURDATE() THEN "2"
                                   WHEN c.end_date < CURDATE() AND c.end_date != "0000-00-00" THEN "3"
                                   WHEN c.end_date = "0000-00-00" THEN "2"
                                   ELSE "2" END) AS classDate_status');
            $builder->join('grade g', 'c.grade = g.grade_id', 'left');
            $builder->join('subject s', 'c.subject = s.subject_id', 'left');
            $builder->where('c.class_id', $params['class_id']);
            $builder->where('c.school_id', $params['school_id']);
            $classList = $builder->get()->getResultArray();

            if (empty($classList)) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => [],
                    'ErrorObject' => 'Class not found'
                ]);
            }

            // Process the class data
            $classData = $classList[0];
            
            // Process tags
            $classData['tags'] = !empty($classData['tags']) ? explode(',', $classData['tags']) : [];
            
            // Process batch_id
            $classData['batch_id'] = !empty($classData['batch_id']) ? explode(',', $classData['batch_id']) : [];
            
            // Process video_link
            $classData['video_link'] = !empty($classData['video_link']) ? json_decode($classData['video_link'], true) : [];
            if (!is_array($classData['video_link'])) {
                $classData['video_link'] = [];
            }
            
            // Process teacher_ids
            $classData['teacher_ids'] = !empty($classData['teacher_ids']) ? explode(',', $classData['teacher_ids']) : [];
            
            // Handle date status
            if ($classData['start_date'] == '0000-00-00' || $classData['end_date'] == '0000-00-00') {
                $classData['classDate_status'] = "5";
            }
            
            // Get students in the class
            $studentBuilder = $db->table('student_class sc');
            $studentBuilder->select('sc.student_id, sc.class_id, sc.status, sc.joining_date, sc.drafted_date, sc.validity,
                                    up.first_name, up.last_name, u.email_id, up.profile_url, 
                                    upd.grade_id, g.grade_name');
            $studentBuilder->join('user u', 'sc.student_id = u.user_id', 'left');
            $studentBuilder->join('user_profile up', 'u.user_id = up.user_id', 'left');
            $studentBuilder->join('user_profile_details upd', 'u.user_id = upd.user_id AND upd.school_id = ' . $params['school_id'], 'left');
            $studentBuilder->join('grade g', 'upd.grade_id = g.grade_id', 'left');
            $studentBuilder->where('sc.class_id', $params['class_id']);
            $studentBuilder->where('sc.status', '1');
            $studentBuilder->orderBy('up.first_name', 'ASC');
            $classData['students'] = $studentBuilder->get()->getResultArray();
            
            // Add computed fields to students
            foreach ($classData['students'] as $key => $student) {
                $classData['students'][$key]['student_name'] = trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''));
                $classData['students'][$key]['user_id'] = $student['student_id'];
            }
            
            // Get class schedule/availability
            $scheduleBuilder = $db->table('class_schedule cs');
            $scheduleBuilder->select('cs.*, cs.id as schedule_id, cs.id as shechdule_id, 
                                     cs.slot_days as slotday, cs.start_time as slotstarttime, cs.end_time as slotendtime');
            $scheduleBuilder->where('cs.class_id', $params['class_id']);
            $scheduleBuilder->orderBy('cs.slot_days', 'ASC');
            $classSchedule = $scheduleBuilder->get()->getResultArray();
            
            // Process schedule data
            foreach ($classSchedule as $key => $schedule) {
                // Get teacher names for this schedule
                if (!empty($schedule['teacher_id'])) {
                    $teacherIds = explode(',', $schedule['teacher_id']);
                    $teacherNames = [];
                    
                    foreach ($teacherIds as $teacherId) {
                        $teacherResult = $db->table('user_profile')
                            ->select('CONCAT_WS(" ", first_name, last_name) as name')
                            ->where('user_id', trim($teacherId))
                            ->get()
                            ->getRowArray();
                        
                        if ($teacherResult) {
                            $teacherNames[] = $teacherResult['name'];
                        }
                    }
                    
                    $classSchedule[$key]['teacher_name'] = $teacherNames;
                    $classSchedule[$key]['teacher_id'] = $teacherIds;
                } else {
                    $classSchedule[$key]['teacher_name'] = [];
                    $classSchedule[$key]['teacher_id'] = [];
                }
                
                // Fill in missing meeting details from main class
                if (empty($classSchedule[$key]['meeting_link'])) {
                    $classSchedule[$key]['meeting_link'] = $classData['meeting_link'] ?? '';
                }
                if (empty($classSchedule[$key]['meeting_id'])) {
                    $classSchedule[$key]['meeting_id'] = $classData['meeting_id'] ?? '';
                }
                if (empty($classSchedule[$key]['passcode'])) {
                    $classSchedule[$key]['passcode'] = $classData['passcode'] ?? '';
                }
            }
            
            $classData['availabilityDate'] = $classSchedule;
            
            // Get class notes
            $notesBuilder = $db->table('class_notes');
            $notesBuilder->select('*');
            $notesBuilder->where('class_id', $params['class_id']);
            $notesBuilder->where('status', '1');
            $notesBuilder->orderBy('id', 'DESC');
            $notes = $notesBuilder->get()->getResultArray();
            $classData['notes'] = $notes ?? [];

            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => [$classData],
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Class detail error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => [],
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get overall class attendance for a specific date
     */
    public function overallClassAttendance(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            // Validation
            if (empty($params['platform'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => [],
                    'ErrorObject' => 'Platform should not be empty'
                ]);
            }

            if (empty($params['role_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => [],
                    'ErrorObject' => 'Role id should not be Empty'
                ]);
            }

            if (empty($params['user_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => [],
                    'ErrorObject' => 'user id should not be empty'
                ]);
            }

            if (empty($params['school_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => [],
                    'ErrorObject' => 'School id should not be empty'
                ]);
            }

            $db = \Config\Database::connect();
            $classList = [];

            // Convert date and get slot_id (day of week)
            if (isset($params['date'])) {
                $time = strtotime($params['date']);
                $slotDay = date('l', $time);
                $dayMapping = [
                    'Monday' => 1,
                    'Tuesday' => 2,
                    'Wednesday' => 3,
                    'Thursday' => 4,
                    'Friday' => 5,
                    'Saturday' => 6,
                    'Sunday' => 7
                ];
                $params['slot_id'] = $dayMapping[$slotDay] ?? 1;
                $params['date'] = date('Y-m-d', $time);
            }

            // Get allow_zoom_api setting for the school
            $settingBuilder = $db->table('admin_settings_school');
            $settingBuilder->select('value');
            $settingBuilder->where('school_id', $params['school_id']);
            $settingBuilder->where('name', 'allow_zoom_api');
            $zoomApiSetting = $settingBuilder->get()->getRowArray();
            $allow_zoom_api = $zoomApiSetting['value'] ?? '0';

            // Get classes for the specified date and school
            $builder = $db->table('class c');
            $builder->select('c.class_id, c.class_name, c.start_date, c.end_date, c.subject, c.grade,
                             c.class_type, c.announcement_type, c.class_code, c.meeting_link, c.meeting_id,
                             c.passcode, c.status,
                             s.subject_name, g.grade_name,
                             (CASE WHEN c.start_date > CURDATE() THEN 1
                                   WHEN c.start_date <= CURDATE() AND c.end_date >= CURDATE() THEN 2
                                   WHEN c.end_date < CURDATE() AND c.end_date != "0000-00-00" THEN 3
                                   ELSE 2 END) AS class_date_status');
            $builder->join('subject s', 'c.subject = s.subject_id', 'left');
            $builder->join('grade g', 'c.grade = g.grade_id', 'left');
            $builder->where('c.school_id', $params['school_id']);
            $builder->where('c.status', '1');
            
            // Filter by date range if provided
            if (isset($params['date'])) {
                $builder->where('c.start_date <=', $params['date']);
                $builder->where('c.end_date >=', $params['date']);
            }

            $classes = $builder->get()->getResultArray();

            // For each class, get schedule and students
            foreach ($classes as $key => $class) {
                // Get class schedule for the specific slot day
                $scheduleBuilder = $db->table('class_schedule cs');
                $scheduleBuilder->select('cs.*, cs.id as schedule_id, cs.id as shechdule_id,
                                         cs.slot_days as slotday, cs.start_time, cs.end_time');
                $scheduleBuilder->where('cs.class_id', $class['class_id']);
                if (isset($params['slot_id'])) {
                    $scheduleBuilder->where('cs.slot_days', $params['slot_id']);
                }
                $classSchedule = $scheduleBuilder->get()->getResultArray();

                if (empty($classSchedule)) {
                    continue; // Skip this class if no schedule for this day
                }

                // Get students for this class
                $studentBuilder = $db->table('student_class sc');
                $studentBuilder->select('sc.student_id, sc.status as student_class_status,
                                        up.first_name, up.last_name,
                                        upd.grade_id, g.grade_name as student_grade_name,
                                        sc.class_type as student_class_type');
                $studentBuilder->join('user u', 'sc.student_id = u.user_id', 'left');
                $studentBuilder->join('user_profile up', 'u.user_id = up.user_id', 'left');
                $studentBuilder->join('user_profile_details upd', 'u.user_id = upd.user_id AND upd.school_id = ' . $params['school_id'], 'left');
                $studentBuilder->join('grade g', 'upd.grade_id = g.grade_id', 'left');
                $studentBuilder->where('sc.class_id', $class['class_id']);
                $studentBuilder->where('sc.status', '1');
                $students = $studentBuilder->get()->getResultArray();

                // Get attendance for each student
                $studentDetails = [];
                foreach ($students as $student) {
                    if (!empty($student['student_id'])) {
                        // Check attendance
                        $attendanceBuilder = $db->table('class_attendance');
                        $attendanceBuilder->select('attendance');
                        $attendanceBuilder->where('student_id', $student['student_id']);
                        $attendanceBuilder->where('class_id', $class['class_id']);
                        if (isset($params['date'])) {
                            $attendanceBuilder->where('date', $params['date']);
                        }
                        $attendance = $attendanceBuilder->get()->getRowArray();

                        // Check makeup class
                        $makeupBuilder = $db->table('student_class_transfer sct');
                        $makeupBuilder->select('sct.class_id as makeup_class_id, c.class_name as makeup_class_name, sct.joining_date as makeup_class_date');
                        $makeupBuilder->join('class c', 'sct.class_id = c.class_id', 'left');
                        $makeupBuilder->where('sct.from_class', $class['class_id']);
                        $makeupBuilder->where('sct.student_id', $student['student_id']);
                        if (isset($params['date'])) {
                            $makeupBuilder->where('sct.absent_date', $params['date']);
                        }
                        $makeupBuilder->where('sct.type', 'M');
                        $makeupBuilder->where('sct.status', '1');
                        $makeup = $makeupBuilder->get()->getRowArray();

                        $studentDetails[] = [
                            'student_id' => $student['student_id'],
                            'student_name' => trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? '')),
                            'student_grade_name' => $student['student_grade_name'] ?? '',
                            'attendance' => $attendance['attendance'] ?? '',
                            'makeup_class_id' => $makeup['makeup_class_id'] ?? '',
                            'makeup_class_name' => $makeup['makeup_class_name'] ?? '',
                            'makeup_class_date' => $makeup['makeup_class_date'] ?? '',
                            'student_class_type' => $student['student_class_type'] ?? ''
                        ];
                    }
                }

                // Build the response for this class
                $schedule = $classSchedule[0];
                
                // Get teacher names
                $teacherIds = !empty($schedule['teacher_id']) ? explode(',', $schedule['teacher_id']) : [];
                $teacherNames = [];
                foreach ($teacherIds as $teacherId) {
                    $teacherResult = $db->table('user_profile')
                        ->select('CONCAT_WS(" ", first_name, last_name) as name')
                        ->where('user_id', trim($teacherId))
                        ->get()
                        ->getRowArray();
                    if ($teacherResult) {
                        $teacherNames[] = $teacherResult['name'];
                    }
                }

                // Prepare availabilityDate with all required fields
                $availabilityDate = $schedule;
                $availabilityDate['teacher_id'] = $teacherIds;
                $availabilityDate['teacher_name'] = $teacherNames;
                $availabilityDate['class_type'] = $class['class_type'] ?? '';

                $classList[] = [
                    'class_id' => $class['class_id'],
                    'class_name' => $class['class_name'],
                    'class_start_date' => $class['start_date'],
                    'class_end_date' => $class['end_date'],
                    'class_start_time' => $schedule['start_time'] ?? '',
                    'class_end_time' => $schedule['end_time'] ?? '',
                    'subject_id' => $class['subject'],
                    'subject_name' => $class['subject_name'] ?? '',
                    'grade_id' => $class['grade'],
                    'grade_name' => $class['grade_name'] ?? '',
                    'schedule_id' => $schedule['schedule_id'] ?? '',
                    'teacher_id' => $teacherIds,
                    'teacher_name' => $teacherNames,
                    'slot_days' => $this->getDayName($schedule['slotday'] ?? 1),
                    'start_time' => $schedule['start_time'] ?? '',
                    'end_time' => $schedule['end_time'] ?? '',
                    'class_type' => $class['class_type'] ?? '',
                    'announcement_type' => $class['announcement_type'] ?? '',
                    'allow_zoom_api' => $allow_zoom_api,
                    'class_code' => $class['class_code'] ?? '',
                    'passcode' => $class['passcode'] ?? '',
                    'meeting_id' => $class['meeting_id'] ?? '',
                    'meeting_link' => $class['meeting_link'] ?? '',
                    'status' => $class['status'],
                    'class_date_status' => $class['class_date_status'],
                    'student_details' => $studentDetails,
                    'availabilityDate' => [$availabilityDate]
                ];
            }

            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $classList,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Overall class attendance error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => [],
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Helper function to get day name from day number
     */
    private function getDayName($dayNumber)
    {
        $days = [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday'
        ];
        return $days[$dayNumber] ?? '';
    }

    /**
     * Create or get Zoom instant meeting
     */
    public function zoomInstantCreation(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            // Validation
            if (empty($params['platform'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Platform should not be empty'
                ]);
            }

            if (empty($params['role_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Role_Id should not be empty'
                ]);
            }

            if (empty($params['user_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'User Id should not be empty'
                ]);
            }

            if (empty($params['class_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Class Id should not be empty'
                ]);
            }

            $db = \Config\Database::connect();

            // Check if class exists and get meeting details
            $classBuilder = $db->table('class');
            $classBuilder->select('meeting_link, meeting_id, passcode, class_type');
            $classBuilder->where('class_id', $params['class_id']);
            $classData = $classBuilder->get()->getRowArray();

            if (empty($classData)) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Class not found'
                ]);
            }

            // Check if schedule exists with meeting links
            if (isset($params['schedule_id']) && !empty($params['schedule_id'])) {
                $scheduleBuilder = $db->table('class_schedule');
                $scheduleBuilder->select('teacher_link, student_link, meeting_id, meeting_link, passcode');
                $scheduleBuilder->where('id', $params['schedule_id']);
                $scheduleBuilder->where('class_id', $params['class_id']);
                $scheduleData = $scheduleBuilder->get()->getRowArray();

                if (!empty($scheduleData) && !empty($scheduleData['teacher_link'])) {
                    // Schedule already has meeting links
                    return $this->respond([
                        'IsSuccess' => true,
                        'ResponseObject' => [
                            'teacher_link' => $scheduleData['teacher_link'],
                            'student_link' => $scheduleData['student_link'],
                            'meeting_id' => $scheduleData['meeting_id'] ?? $classData['meeting_id'],
                            'meeting_link' => $scheduleData['meeting_link'] ?? $classData['meeting_link'],
                            'passcode' => $scheduleData['passcode'] ?? $classData['passcode'],
                            'message' => 'Zoom Already Created successfully'
                        ],
                        'ErrorObject' => ''
                    ]);
                }
            }

            // If no existing meeting in schedule, check if we have schedule-specific links
            if (isset($params['schedule_id']) && !empty($params['schedule_id'])) {
                $scheduleBuilder = $db->table('class_schedule');
                $scheduleBuilder->select('teacher_link, student_link, meeting_id, meeting_link, passcode');
                $scheduleBuilder->where('id', $params['schedule_id']);
                $scheduleBuilder->where('class_id', $params['class_id']);
                $scheduleData = $scheduleBuilder->get()->getRowArray();

                if (!empty($scheduleData)) {
                    return $this->respond([
                        'IsSuccess' => true,
                        'ResponseObject' => [
                            'teacher_link' => $scheduleData['teacher_link'] ?? $classData['meeting_link'],
                            'student_link' => $scheduleData['student_link'] ?? $classData['meeting_link'],
                            'meeting_id' => $scheduleData['meeting_id'] ?? $classData['meeting_id'],
                            'meeting_link' => $scheduleData['meeting_link'] ?? $classData['meeting_link'],
                            'passcode' => $scheduleData['passcode'] ?? $classData['passcode'],
                            'message' => 'Meeting details retrieved successfully'
                        ],
                        'ErrorObject' => ''
                    ]);
                }
            }

            // Return class-level meeting info
            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => [
                    'teacher_link' => $classData['meeting_link'] ?? '',
                    'student_link' => $classData['meeting_link'] ?? '',
                    'meeting_id' => $classData['meeting_id'] ?? '',
                    'meeting_link' => $classData['meeting_link'] ?? '',
                    'passcode' => $classData['passcode'] ?? '',
                    'message' => 'Meeting details retrieved successfully'
                ],
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Zoom instant creation error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check Zoom API permission for school
     */
    public function zoomPermission(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            $db = \Config\Database::connect();
            
            // Get Zoom API settings for the school
            $builder = $db->table('admin_settings_school');
            $builder->select('name, value');
            $builder->where('school_id', $params['school_id'] ?? 0);
            $builder->whereIn('name', ['allow_zoom_api', 'allow_google_meet']);
            $settings = $builder->get()->getResultArray();
            
            // Return in the format the frontend expects (array of objects)
            if (!empty($settings)) {
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => $settings,
                    'ErrorObject' => ''
                ]);
            } else {
                // Return default values if no settings found
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => [
                        ['name' => 'allow_zoom_api', 'value' => '0'],
                        ['name' => 'allow_google_meet', 'value' => '0']
                    ],
                    'ErrorObject' => ''
                ]);
            }

        } catch (\Exception $e) {
            log_message('error', 'Zoom permission error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => [],
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Add or update student attendance
     */
    public function attendance(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            // Validation
            if (empty($params['platform'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Platform should not be empty'
                ]);
            }

            if (empty($params['role_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Role Id should not be empty'
                ]);
            }

            if (empty($params['user_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'User Id should not be empty'
                ]);
            }

            if (empty($params['date'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Date should not be empty'
                ]);
            }

            $db = \Config\Database::connect();

            // Convert date from dd-MM-yyyy to yyyy-MM-dd
            $date = date('Y-m-d', strtotime($params['date']));
            
            $data = [];
            $updatedata = [];

            // Process each student's attendance
            foreach ($params['attendence'] as $attendance) {
                // Check if attendance record already exists
                $builder = $db->table('class_attendance');
                $builder->select('id as attendance_id');
                $builder->where('start_time', $params['start_time']);
                $builder->where('end_time', $params['end_time']);
                $builder->where('slot_day', $params['slot_day']);
                $builder->where('class_id', $params['class_id']);
                $builder->where('date', $date);
                $builder->where('student_id', $attendance['student_id']);
                $check = $builder->get()->getResultArray();

                if (empty($check)) {
                    // Insert new attendance record
                    $data[] = [
                        'start_time' => $params['start_time'],
                        'end_time' => $params['end_time'],
                        'slot_day' => $params['slot_day'],
                        'schedule_id' => $params['schedule_id'] ?? 0,
                        'class_id' => $params['class_id'],
                        'student_id' => $attendance['student_id'],
                        'attendance' => isset($attendance['attendance']) && $attendance['attendance'] != '' ? $attendance['attendance'] : null,
                        'date' => $date,
                        'request_json' => json_encode($params),
                        'created_date' => date('Y-m-d H:i:s'),
                        'created_by' => $params['user_id'],
                        'modified_date' => date('Y-m-d H:i:s'),
                        'modified_by' => $params['user_id']
                    ];
                } else {
                    // Update existing attendance record
                    $updatedata[] = [
                        'id' => $check[0]['attendance_id'],
                        'start_time' => $params['start_time'],
                        'end_time' => $params['end_time'],
                        'slot_day' => $params['slot_day'],
                        'student_id' => $attendance['student_id'],
                        'attendance' => isset($attendance['attendance']) && $attendance['attendance'] != '' ? $attendance['attendance'] : null,
                        'date' => $date,
                        'request_json' => json_encode($params),
                        'modified_date' => date('Y-m-d H:i:s'),
                        'modified_by' => $params['user_id']
                    ];
                }
            }

            // Perform bulk insert or update
            if (count($data) > 0) {
                $db->table('class_attendance')->insertBatch($data);
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => 'Attendance Added Successfully',
                    'ErrorObject' => ''
                ]);
            } elseif (count($updatedata) > 0) {
                foreach ($updatedata as $update) {
                    $id = $update['id'];
                    unset($update['id']);
                    $db->table('class_attendance')->where('id', $id)->update($update);
                }
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => 'Updated Successfully',
                    'ErrorObject' => ''
                ]);
            } else {
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => 'No changes made',
                    'ErrorObject' => ''
                ]);
            }

        } catch (\Exception $e) {
            log_message('error', 'Attendance error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Add students to a class
     */
    public function addStudent(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            // Validation
            if (empty($params['class_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Class Id should not be empty'
                ]);
            }

            if (empty($params['students']) || !is_array($params['students'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Students should not be empty'
                ]);
            }

            $db = \Config\Database::connect();
            $students = [];
            $isAssigned = $params['is_makeup'] ?? '0';

            foreach ($params['students'] as $student) {
                $studentId = $student['user_id'] ?? $student['student_id'] ?? null;
                
                if (!$studentId) {
                    continue;
                }

                // Check if student already exists in class
                $builder = $db->table('student_class');
                $builder->where('class_id', $params['class_id']);
                $builder->where('student_id', $studentId);
                $existing = $builder->get()->getRowArray();

                if (!$existing) {
                    // Insert new student
                    $students[] = [
                        'class_id' => $params['class_id'],
                        'student_id' => $studentId,
                        'validity' => !empty($params['end_date']) ? $params['end_date'] : '2099-12-31',
                        'status' => 1,
                        'joining_date' => $params['start_date'] ?? date('Y-m-d'),
                        'class_type' => $isAssigned == '1' ? 2 : 1,
                        'created_by' => $params['user_id'] ?? 0,
                        'created_date' => date('Y-m-d H:i:s')
                    ];
                } else {
                    // Update existing student
                    $db->table('student_class')
                        ->where('class_id', $params['class_id'])
                        ->where('student_id', $studentId)
                        ->update([
                            'validity' => $params['end_date'] ?? null,
                            'status' => 1,
                            'modified_by' => $params['user_id'] ?? 0,
                            'modified_date' => date('Y-m-d H:i:s')
                        ]);
                }
            }

            // Bulk insert new students
            if (count($students) > 0) {
                $db->table('student_class')->insertBatch($students);
            }

            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => 'Student Added Successfully',
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Add student error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get curriculum list for a class
     */
    public function curriculumList(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            // Validation
            if (empty($params['class_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Class Id should not be empty'
                ]);
            }

            $db = \Config\Database::connect();

            log_message('debug', '📥 Classes::curriculumList params: ' . json_encode($params));
            
            // Get class contents/curriculum
            $builder = $db->table('class_content cc');
            $builder->select('cc.*, cc.id as class_content_id, c.name as content_name, c.content_type, c.content_format, c.file_path,
                             CASE 
                                WHEN cc.start_date > CURDATE() THEN "1"
                                WHEN cc.start_date <= CURDATE() AND cc.end_date >= CURDATE() THEN "2"
                                WHEN cc.end_date < CURDATE() THEN "3"
                                ELSE "2"
                             END as content_date_status,
                             CASE WHEN cc.all_student = 0 THEN
                                GROUP_CONCAT(DISTINCT CONCAT_WS(" ", up.first_name, up.last_name) ORDER BY up.first_name SEPARATOR ", ")
                             END AS individual_students,
                             CASE WHEN cc.all_student = 0 THEN COUNT(DISTINCT sc.student_id) ELSE 0 END AS individual_count');
            $builder->join('content c', 'cc.content_id = c.content_id', 'left');
            $builder->join('student_content sc', 'sc.class_content_id = cc.id', 'left');
            $builder->join('user_profile up', 'sc.student_id = up.user_id', 'left');
            $builder->where('cc.class_id', $params['class_id']);
            $builder->where('cc.status', 1);
            $builder->orderBy('cc.start_date', 'ASC');
            $builder->groupBy('cc.id');

            // Log the compiled query for debugging
            log_message('debug', '📝 Classes::curriculumList SQL: ' . $builder->getCompiledSelect(false));
            
            $list = $builder->get()->getResultArray();

            foreach ($list as &$item) {
                $item['individual_students'] = $item['individual_students'] ?? '';
                $item['individual_count'] = isset($item['individual_count']) ? (int) $item['individual_count'] : 0;
            }

            log_message('debug', '📦 Classes::curriculumList fetched ' . count($list) . ' row(s)');
            if (!empty($list)) {
                log_message('debug', '📄 Classes::curriculumList sample row: ' . json_encode($list[0]));
            }

            // Process file paths
            foreach ($list as &$item) {
                if (!empty($item['file_path'])) {
                    $filePath = json_decode($item['file_path'], true);
                    $item['is_pdf_content'] = isset($filePath[0]['original_image_url']) && !empty($filePath[0]['original_image_url']) ? 1 : 0;
                    $item['file_path'] = $filePath;
                } else {
                    $item['is_pdf_content'] = 0;
                    $item['file_path'] = [];
                }
                
                // teacher_version_path may not exist in all schemas
                $item['teacher_version_path'] = [];
            }

            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $list,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Curriculum list error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => [],
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get topic list for a class
     */
    public function topicList(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            // Validation
            if (empty($params['class_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Class Id should not be empty'
                ]);
            }

            $db = \Config\Database::connect();

            log_message('debug', '📥 Classes::topicList params: ' . json_encode($params));
            
            // Get class topics from legacy `topic` table
            $builder = $db->table('topic');
            $builder->select('topic_id, class_id, topic, start_date, end_date, display_order, status, created_by, created_date, modified_by, modified_date');
            $builder->where('class_id', $params['class_id']);
            $builder->where('status', 1);
            $builder->orderBy('display_order', 'ASC');
            $builder->orderBy('topic_id', 'ASC');

            log_message('debug', '📝 Classes::topicList SQL: ' . $builder->getCompiledSelect(false));
            
            $topics = $builder->get()->getResultArray();

            log_message('debug', '📦 Classes::topicList fetched ' . count($topics) . ' row(s)');
            if (!empty($topics)) {
                log_message('debug', '📄 Classes::topicList sample row: ' . json_encode($topics[0]));
            }

            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $topics,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Topic list error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => [],
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Add a new topic to a class
     */
    public function addTopic(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            log_message('debug', '📥 Classes::addTopic params: ' . json_encode($params));

            // Validation
            if (empty($params['class_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Class Id should not be empty'
                ]);
            }

            if (empty($params['topic'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Topic should not be empty'
                ]);
            }

            $db = \Config\Database::connect();

            // Check if display_order is provided, otherwise get the next order
            $displayOrder = $params['display_order'] ?? null;
            if ($displayOrder === null) {
                $builder = $db->table('topic');
                $builder->selectMax('display_order');
                $builder->where('class_id', $params['class_id']);
                $result = $builder->get()->getRowArray();
                $displayOrder = ($result['display_order'] ?? 0) + 1;
            }

            // Prepare data for insertion
            $topicData = [
                'class_id' => $params['class_id'],
                'topic' => $params['topic'],
                'start_date' => $params['start_date'] ?? null,
                'end_date' => $params['end_date'] ?? null,
                'display_order' => $displayOrder,
                'status' => $params['status'] ?? 1,
                'created_by' => $params['user_id'] ?? null,
                'created_date' => date('Y-m-d H:i:s')
            ];

            // Insert the topic
            $builder = $db->table('topic');
            $insertSuccess = $builder->insert($topicData);

            if ($insertSuccess) {
                log_message('debug', '✅ Classes::addTopic successfully inserted topic');
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => 'Topic Added Successfully',
                    'ErrorObject' => ''
                ]);
            } else {
                log_message('error', '❌ Classes::addTopic failed to insert topic');
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Failed to add topic'
                ]);
            }

        } catch (\Exception $e) {
            log_message('error', 'Add topic error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update an existing topic
     */
    public function updateTopic(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            log_message('debug', '📥 Classes::updateTopic params: ' . json_encode($params));

            // Validation
            if (empty($params['topic_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Topic Id should not be empty'
                ]);
            }

            if (empty($params['class_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Class Id should not be empty'
                ]);
            }

            if (empty($params['topic'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Topic should not be empty'
                ]);
            }

            $db = \Config\Database::connect();

            // Prepare data for update
            $topicData = [
                'topic' => $params['topic'],
                'start_date' => $params['start_date'] ?? null,
                'end_date' => $params['end_date'] ?? null,
                'display_order' => $params['display_order'] ?? null,
                'status' => $params['status'] ?? 1,
                'modified_by' => $params['user_id'] ?? null,
                'modified_date' => date('Y-m-d H:i:s')
            ];

            // Remove null values to avoid overwriting with null
            $topicData = array_filter($topicData, function($value) {
                return $value !== null;
            });

            // Update the topic
            $builder = $db->table('topic');
            $builder->where('topic_id', $params['topic_id']);
            $builder->where('class_id', $params['class_id']);
            $updateSuccess = $builder->update($topicData);

            if ($updateSuccess) {
                log_message('debug', '✅ Classes::updateTopic successfully updated topic');
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => 'Topic Updated Successfully',
                    'ErrorObject' => ''
                ]);
            } else {
                log_message('error', '❌ Classes::updateTopic failed to update topic');
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Failed to update topic'
                ]);
            }

        } catch (\Exception $e) {
            log_message('error', 'Update topic error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Remove student from class
     */
    public function removeStudent(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            // Validation
            if (empty($params['class_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Class Id should not be empty'
                ]);
            }

            if (empty($params['student_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Student Id should not be empty'
                ]);
            }

            $db = \Config\Database::connect();
            
            // Update student_class status to 0 (removed)
            $builder = $db->table('student_class');
            $builder->where('class_id', $params['class_id']);
            $builder->where('student_id', $params['student_id']);
            $removeStudent = $builder->update([
                'status' => 0,
                'modified_by' => $params['user_id'] ?? null,
                'modified_date' => date('Y-m-d H:i:s')
            ]);
            
            // Update student_class_transfer status to 0 if exists
            $builder = $db->table('student_class_transfer');
            $builder->where('class_id', $params['class_id']);
            $builder->where('student_id', $params['student_id']);
            $builder->where('type', 'M');
            $builder->update(['status' => 0]);
            
            // Get future content for this class
            $params['joining_date'] = date('Y-m-d');
            $builder = $db->table('class_content');
            $builder->select('content_id');
            $builder->where('class_id', $params['class_id']);
            $builder->where('status', 1);
            $builder->where('start_date >', $params['joining_date']);
            $classFutureContent = $builder->get()->getResultArray();
            
            // Mark future content as draft (draft_status = 2) for this student
            if (count($classFutureContent) > 0) {
                foreach ($classFutureContent as $content) {
                    // Update student_content
                    $builder = $db->table('student_content');
                    $builder->where('class_id', $params['class_id']);
                    $builder->where('content_id', $content['content_id']);
                    $builder->where('student_id', $params['student_id']);
                    $builder->update([
                        'draft_status' => 2,
                        'modified_by' => $params['user_id'] ?? null,
                        'modified_date' => date('Y-m-d H:i:s')
                    ]);
                    
                    // Update student_work
                    $builder = $db->table('student_work');
                    $builder->where('class_id', $params['class_id']);
                    $builder->where('content_id', $content['content_id']);
                    $builder->where('student_id', $params['student_id']);
                    $builder->update([
                        'status' => 0,
                        'draft_status' => 2
                    ]);
                }
            }

            if ($removeStudent) {
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => 'Student Removed Successfully',
                    'ErrorObject' => ''
                ]);
            } else {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Failed To Remove Student'
                ]);
            }

        } catch (\Exception $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Delete class (soft delete by setting status to 3)
     */
    public function deleteClass(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            // Validation
            if (empty($params['class_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Class Id should not be empty'
                ]);
            }

            $db = \Config\Database::connect();
            
            // Update class status to 3 (deleted)
            $builder = $db->table('class');
            $builder->where('class_id', $params['class_id']);
            $classDelete = $builder->update([
                'status' => 3,
                'modified_by' => $params['user_id'] ?? null,
                'modified_date' => date('Y-m-d H:i:s')
            ]);
            
            // Update class_content status to 2 (deleted)
            $builder = $db->table('class_content');
            $builder->where('class_id', $params['class_id']);
            $builder->update([
                'status' => 2,
                'modified_by' => $params['user_id'] ?? null,
                'modified_date' => date('Y-m-d H:i:s')
            ]);

            if ($classDelete) {
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => 'Class Deleted Successfully',
                    'ErrorObject' => ''
                ]);
            } else {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Failed To Delete Class'
                ]);
            }

        } catch (\Exception $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get slot list for schedule calendar view
     */
    public function slotList(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            $db = \Config\Database::connect();
            
            // Get date range
            $fromDate = $params['from_date'] ?? date('Y-m-d');
            $toDate = $params['to_date'] ?? date('Y-m-d', strtotime('+1 month'));
            
            // Build query for class schedules
            $builder = $db->table('class_schedule cs');
            $builder->select('cs.*, c.class_id, c.class_name, c.start_date as class_start_date, 
                             c.end_date as class_end_date, c.status as class_status');
            $builder->join('class c', 'cs.class_id = c.class_id', 'left');
            $builder->where('cs.school_id', $params['school_id'] ?? 0);
            $builder->where('c.status', 1);
            
            // Filter by teacher if provided
            if (isset($params['teacher_id']) && $params['teacher_id'] != '0' && $params['teacher_id'] != '') {
                $builder->where("FIND_IN_SET('{$params['teacher_id']}', cs.teacher_id) >", 0);
            }
            
            // Filter by class if provided
            if (isset($params['class_id']) && $params['class_id'] != '') {
                $builder->where('cs.class_id', $params['class_id']);
            }
            
            // For teacher role, filter by their user_id
            if (isset($params['role_id']) && $params['role_id'] > 2 && $params['role_id'] != 6) {
                $builder->where("FIND_IN_SET('{$params['user_id']}', cs.teacher_id) >", 0);
            }
            
            $schedules = $builder->get()->getResultArray();
            
            // Build date-based events
            $dateEvents = [];
            $daysMap = [
                1 => 'Monday',
                2 => 'Tuesday',
                3 => 'Wednesday',
                4 => 'Thursday',
                5 => 'Friday',
                6 => 'Saturday',
                7 => 'Sunday'
            ];
            
            // Generate dates between from_date and to_date
            $currentDate = strtotime($fromDate);
            $endDate = strtotime($toDate);
            
            while ($currentDate <= $endDate) {
                $dateStr = date('Y-m-d', $currentDate);
                $dayOfWeek = date('N', $currentDate); // 1=Monday, 7=Sunday
                
                $dateEvents[$dateStr] = [
                    'date' => $dateStr,
                    'slotday' => $dayOfWeek,
                    'slotselected' => '',
                    'event_details' => []
                ];
                
                // Find schedules that match this day of week
                foreach ($schedules as $schedule) {
                    if ($schedule['slot_days'] == $dayOfWeek) {
                        // Check if this date falls within the class date range
                        $classStartDate = $schedule['class_start_date'];
                        $classEndDate = $schedule['class_end_date'];
                        
                        if ($dateStr >= $classStartDate && ($classEndDate == '0000-00-00' || $dateStr <= $classEndDate)) {
                            // Get teacher names
                            $teacherIds = explode(',', $schedule['teacher_id'] ?? '');
                            $teacherNames = [];
                            
                            foreach ($teacherIds as $teacherId) {
                                $teacherId = trim($teacherId);
                                if (!empty($teacherId)) {
                                    $teacherResult = $db->table('user_profile')
                                        ->select('CONCAT_WS(" ", first_name, last_name) as name')
                                        ->where('user_id', $teacherId)
                                        ->get()
                                        ->getRowArray();
                                    
                                    if ($teacherResult) {
                                        $teacherNames[] = $teacherResult['name'];
                                    }
                                }
                            }
                            
                            $dateEvents[$dateStr]['event_details'][] = [
                                'class_id' => $schedule['class_id'],
                                'message' => $schedule['class_name'],
                                'slotstarttime' => $schedule['start_time'] ?? '',
                                'slotendtime' => $schedule['end_time'] ?? '',
                                'teacher_id' => $schedule['teacher_id'] ?? '',
                                'teacher_name' => implode(', ', $teacherNames),
                                'meeting_link' => $schedule['meeting_link'] ?? '',
                                'meeting_id' => $schedule['meeting_id'] ?? '',
                                'passcode' => $schedule['passcode'] ?? '',
                                'telephone_number' => $schedule['telephone_number'] ?? '',
                                'status' => 1
                            ];
                        }
                    }
                }
                
                $currentDate = strtotime('+1 day', $currentDate);
            }
            
            // Convert to array and filter out dates with no events (optional)
            $responseData = array_values($dateEvents);
            
            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $responseData,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Slot list error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => [],
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * View assignments for a class
     */
    public function viewAssignments(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }
            
            log_message('debug', '📥 Classes::viewAssignments called with params: ' . json_encode($params));
            
            $assignments = $this->classesModel->viewAssignments($params);
            
            log_message('debug', '✅ Classes::viewAssignments returning ' . count($assignments) . ' assignments');
            
            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $assignments,
                'ErrorObject' => ''
            ]);
        } catch (\Exception $e) {
            log_message('error', '❌ Classes::viewAssignments error: ' . $e->getMessage());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add or update curriculum topic assignment
     */
    public function addCurriculumTopic(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            log_message('debug', '📥 Classes::addCurriculumTopic params: ' . json_encode($params));

            // Validation
            if (empty($params['class_content_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Class content ID should not be empty'
                ]);
            }

            $db = \Config\Database::connect();

            // Prepare update data
            $updateData = [
                'topic_id' => $params['topic_id'] ?? '0',
                'modified_by' => $params['user_id'] ?? null,
                'modified_date' => date('Y-m-d H:i:s')
            ];

            // Add position if provided
            if (isset($params['position']) && $params['position'] !== '') {
                $updateData['position'] = $params['position'];
            }

            // Remove null values
            $updateData = array_filter($updateData, function($value) {
                return $value !== null;
            });

            // Update the class_content record
            $builder = $db->table('class_content');
            $builder->where('id', $params['class_content_id']);
            $updateSuccess = $builder->update($updateData);

            if ($updateSuccess !== false) {
                log_message('debug', '✅ Classes::addCurriculumTopic successfully updated');
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => 'Curriculum topic updated successfully',
                    'ErrorObject' => ''
                ]);
            } else {
                log_message('error', '❌ Classes::addCurriculumTopic failed to update');
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Failed to update curriculum topic'
                ]);
            }

        } catch (\Exception $e) {
            log_message('error', 'Add curriculum topic error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get class notes/announcements for one or more classes
     */
    public function getClassNotes(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            log_message('debug', '📥 Classes::getClassNotes params: ' . json_encode($params));

            $db = \Config\Database::connect();

            // Build query for class notes
            $builder = $db->table('class_notes');
            $builder->select('*');
            
            // Handle both single class_id and array of class_ids
            if (isset($params['class_id'])) {
                if (is_array($params['class_id'])) {
                    $builder->whereIn('class_id', $params['class_id']);
                } else {
                    $builder->where('class_id', $params['class_id']);
                }
            }

            // Only get active notes
            $builder->where('status', '1');
            $builder->orderBy('class_id', 'ASC');
            $builder->orderBy('id', 'DESC');

            $notes = $builder->get()->getResultArray();

            log_message('debug', '📦 Classes::getClassNotes fetched ' . count($notes) . ' notes');

            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $notes,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Get class notes error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => [],
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Enroll a student in a class using class code
     */
    public function enrollStudent(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            log_message('debug', '📥 Classes::enrollStudent params: ' . json_encode($params));

            // Validation
            if (empty($params['student_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Student ID should not be empty'
                ]);
            }

            if (empty($params['class_code'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Class code should not be empty'
                ]);
            }

            $db = \Config\Database::connect();

            // Find the class by class code
            $classBuilder = $db->table('class');
            $classBuilder->select('class_id, class_name, start_date, end_date, status');
            $classBuilder->where('class_code', $params['class_code']);
            $classBuilder->where('status', '1');
            $class = $classBuilder->get()->getRowArray();

            if (empty($class)) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Invalid class code or class is not active'
                ]);
            }

            // Check if student is already enrolled
            $enrollmentBuilder = $db->table('student_class');
            $enrollmentBuilder->where('class_id', $class['class_id']);
            $enrollmentBuilder->where('student_id', $params['student_id']);
            $existing = $enrollmentBuilder->get()->getRowArray();

            if ($existing) {
                if ($existing['status'] == '1') {
                    return $this->respond([
                        'IsSuccess' => false,
                        'ResponseObject' => null,
                        'ErrorObject' => 'You are already enrolled in this class'
                    ]);
                } else {
                    // Reactivate enrollment
                    $db->table('student_class')
                        ->where('class_id', $class['class_id'])
                        ->where('student_id', $params['student_id'])
                        ->update([
                            'status' => '1',
                            'joining_date' => date('Y-m-d'),
                            'modified_by' => $params['user_id'] ?? null,
                            'modified_date' => date('Y-m-d H:i:s')
                        ]);
                }
            } else {
                // Create new enrollment
                $enrollmentData = [
                    'class_id' => $class['class_id'],
                    'student_id' => $params['student_id'],
                    'status' => '1',
                    'joining_date' => date('Y-m-d'),
                    'validity' => $class['end_date'] != '0000-00-00' ? $class['end_date'] : '2099-12-31',
                    'class_type' => 1,
                    'created_by' => $params['user_id'] ?? null,
                    'created_date' => date('Y-m-d H:i:s')
                ];

                $db->table('student_class')->insert($enrollmentData);
            }

            // Get schedule_id if available
            $scheduleBuilder = $db->table('class_schedule');
            $scheduleBuilder->select('id');
            $scheduleBuilder->where('class_id', $class['class_id']);
            $scheduleBuilder->orderBy('id', 'ASC');
            $scheduleBuilder->limit(1);
            $schedule = $scheduleBuilder->get()->getRowArray();

            log_message('debug', '✅ Classes::enrollStudent successfully enrolled student in class: ' . $class['class_id']);

            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => [
                    'Message' => 'Successfully enrolled in ' . $class['class_name'],
                    'class_id' => $class['class_id'],
                    'schedule_id' => $schedule['id'] ?? null
                ],
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Enroll student error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Add a new class note/announcement
     */
    public function classAddNotes(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            log_message('debug', '📥 Classes::classAddNotes params: ' . json_encode($params));

            // Validation
            if (empty($params['class_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Class ID should not be empty'
                ]);
            }

            if (empty($params['notes'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Notes content should not be empty'
                ]);
            }

            $db = \Config\Database::connect();

            // Prepare data for insertion
            $noteData = [
                'class_id' => $params['class_id'],
                'notes' => $params['notes'],
                'status' => $params['status'] ?? '1',
                'created_by' => $params['user_id'] ?? null,
                'created_date' => date('Y-m-d H:i:s')
            ];

            // Remove null values
            $noteData = array_filter($noteData, function($value) {
                return $value !== null;
            });

            // Insert the note
            $builder = $db->table('class_notes');
            $insertSuccess = $builder->insert($noteData);

            if ($insertSuccess) {
                $noteId = $db->insertID();
                log_message('debug', '✅ Classes::classAddNotes successfully inserted note with ID: ' . $noteId);
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => [
                        'id' => $noteId,
                        'message' => 'Note added successfully'
                    ],
                    'ErrorObject' => ''
                ]);
            } else {
                log_message('error', '❌ Classes::classAddNotes failed to insert note');
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Failed to add note'
                ]);
            }

        } catch (\Exception $e) {
            log_message('error', 'Add class notes error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }
}
