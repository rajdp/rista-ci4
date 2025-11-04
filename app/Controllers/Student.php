<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

class Student extends ResourceController
{
    protected $studentModel;
    protected $format = 'json';

    public function __construct()
    {
        $this->studentModel = new \App\Models\V1\StudentModel();
    }

    /**
     * Get list of students
     */
    public function list(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            $students = $this->studentModel->getStudents($data ?? (object)[]);
            
            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $students,
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
     * Get student details for editing
     * Override parent edit method to handle POST with JSON body
     */
    public function edit($id = null): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            // If $id is provided via URL parameter, use it; otherwise use selected_user_id from params
            $userId = $id ?? ($params['selected_user_id'] ?? null);

            // Validation
            if (empty($userId)) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'User Id should not be empty'
                ]);
            }

            $db = \Config\Database::connect();
            
            // Get student details
            $builder = $db->table('user u');
            $builder->select('u.user_id, u.email_id, u.role_id, u.status as user_status,
                             up.first_name, up.last_name, up.gender, up.country, up.state, up.city,
                             up.profile_url, up.profile_thumb_url, up.address, up.zipcode,
                             upd.grade_id, upd.joining_date, upd.dropped_date, upd.phone, upd.parent_name,
                             upd.parent_email, upd.parent_phone, upd.school_id, 
                             COALESCE(upd.dob, "") as dob');
            $builder->join('user_profile up', 'u.user_id = up.user_id', 'left');
            $builder->join('user_profile_details upd', 'u.user_id = upd.user_id', 'left');
            $builder->where('u.user_id', $userId);
            
            $student = $builder->get()->getRowArray();

            if (!$student) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Student not found'
                ]);
            }

            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $student,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Student edit error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get students from class list
     */
    public function StudentFromClassList(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            $classList = $this->studentModel->studentFromClass($data ?? (object)[]);
            
            // Process teacher IDs
            foreach ($classList as $key => $value) {
                if (isset($value['teacher_id'])) {
                    $classList[$key]['teacher_id'] = explode(',', $value['teacher_id']);
                }
            }
            
            // Remove duplicates
            $uniqueList = array_values(array_map("unserialize", array_unique(array_map("serialize", $classList))));
            
            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $uniqueList,
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
     * Get all classes for a student
     */
    public function studentAllClassList(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            // Validation
            if (empty($params['student_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Student Id should not be empty'
                ]);
            }

            $db = \Config\Database::connect();
            
            // Get all classes for the student
            $builder = $db->table('student_class sc');
            $builder->select('sc.class_id, sc.student_id, sc.notify_status, sc.joining_date, sc.drafted_date,
                             c.class_name, c.start_date, c.end_date, c.status as class_status, 
                             c.class_code, 
                             COALESCE(s.subject_name, "") as subject, 
                             COALESCE(gr.grade_name, "") as grade,
                             b.batch_name');
            $builder->join('class c', 'sc.class_id = c.class_id', 'left');
            $builder->join('batch b', 'c.batch_id = b.batch_id', 'left');
            $builder->join('grade gr', 'c.grade = gr.grade_id', 'left');
            $builder->join('subject s', 'c.subject = s.subject_id', 'left');
            $builder->where('sc.student_id', $params['student_id']);
            $builder->where('sc.status', 1);
            $builder->orderBy('sc.created_date', 'DESC');
            
            $classes = $builder->get()->getResultArray();

            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $classes ?? [],
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

    public function attendanceDetail()
    {
        try {
            // Handle JSON POST data
            $params = $this->request->getJSON(true);
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            // Validation
            if (empty($params['student_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Student Id should not be empty'
                ]);
            }

            $db = \Config\Database::connect();
            
            // Get student attendance
            $builder = $db->table('class_attendance ca');
            $builder->select('ca.date, ca.attendance, ca.slot_day, ca.start_time, ca.end_time, 
                             ca.student_id, ca.class_id, c.class_name');
            $builder->join('class c', 'c.class_id = ca.class_id', 'left');
            $builder->where('ca.student_id', $params['student_id']);
            
            // Optional class filter
            if (isset($params['class_id']) && !empty($params['class_id'])) {
                $builder->where('ca.class_id', $params['class_id']);
            }
            
            $builder->orderBy('ca.date', 'ASC');
            
            $attendance = $builder->get()->getResultArray();

            // Check for makeup classes for absent students
            foreach ($attendance as $key => &$value) {
                if (isset($value['attendance']) && $value['attendance'] == 0) {
                    // Check if makeup class exists
                    $makeupBuilder = $db->table('student_class_transfer sc');
                    $makeupBuilder->select('sc.class_id as makeup_class_id, c.class_name as makeup_class_name, 
                                           sc.joining_date as makeup_class_date');
                    $makeupBuilder->join('class c', 'sc.class_id = c.class_id', 'left');
                    $makeupBuilder->where('sc.from_class', $value['class_id']);
                    $makeupBuilder->where('sc.student_id', $params['student_id']);
                    $makeupBuilder->where('sc.absent_date', $value['date']);
                    $makeupBuilder->where('sc.type', 'M');
                    $makeupBuilder->where('sc.status', 1);
                    
                    $makeupClass = $makeupBuilder->get()->getRowArray();
                    
                    if ($makeupClass) {
                        $value['makeup_class_id'] = $makeupClass['makeup_class_id'];
                        $value['makeup_class_name'] = $makeupClass['makeup_class_name'];
                        $value['makeup_class_date'] = $makeupClass['makeup_class_date'];
                    } else {
                        $value['makeup_class_id'] = '';
                        $value['makeup_class_name'] = '';
                        $value['makeup_class_date'] = '';
                    }
                } else {
                    $value['makeup_class_id'] = '';
                    $value['makeup_class_name'] = '';
                    $value['makeup_class_date'] = '';
                }
            }

            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $attendance ?? [],
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
     * Move student to new class
     */
    public function moveToClass(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            if (is_object($data)) {
                $data = (array) $data;
            }
            
            $studentId = $data['student_id'] ?? null;
            $oldClassId = $data['old_class_id'] ?? null;
            $newClassId = $data['new_class_id'] ?? null;
            $userId = session()->get('user_id') ?? 1;
            
            if (!$studentId || !$oldClassId || !$newClassId) {
                throw new \Exception('Missing required parameters: student_id, old_class_id, new_class_id');
            }
            
            $result = $this->studentModel->moveStudentToClass($studentId, $oldClassId, $newClassId, $userId);
            
            return $this->respond([
                'IsSuccess' => $result,
                'ResponseObject' => ['message' => 'Student moved successfully'],
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
     * Add student to additional class
     */
    public function addToClass(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            if (is_object($data)) {
                $data = (array) $data;
            }
            
            $studentId = $data['student_id'] ?? null;
            $classId = $data['class_id'] ?? null;
            $userId = session()->get('user_id') ?? 1;
            
            if (!$studentId || !$classId) {
                throw new \Exception('Missing required parameters: student_id, class_id');
            }
            
            $result = $this->studentModel->addStudentToClass($studentId, $classId, $userId);
            
            return $this->respond([
                'IsSuccess' => $result,
                'ResponseObject' => ['message' => 'Student added to class successfully'],
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
     * Remove student from class
     */
    public function removeFromClass(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            if (is_object($data)) {
                $data = (array) $data;
            }
            
            $studentId = $data['student_id'] ?? null;
            $classId = $data['class_id'] ?? null;
            $userId = session()->get('user_id') ?? 1;
            
            if (!$studentId || !$classId) {
                throw new \Exception('Missing required parameters: student_id, class_id');
            }
            
            $result = $this->studentModel->removeStudentFromClass($studentId, $classId, $userId);
            
            return $this->respond([
                'IsSuccess' => $result,
                'ResponseObject' => ['message' => 'Student removed from class successfully'],
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
     * Get class list for a student
     */
    public function classList()
    {
        // Check for SSE early and disable error display for SSE
        $rawInput = file_get_contents('php://input');
        $tempParams = json_decode($rawInput, true);
        $isSSE = isset($tempParams['response_type']) && $tempParams['response_type'] === 'SSE';
        
        if ($isSSE) {
            // For SSE, suppress all errors and warnings to prevent contaminating the stream
            error_reporting(0);
            ini_set('display_errors', '0');
        }
        
        $params = $this->request->getJSON(true) ?? [];
        
        if (empty($params)) {
            $params = $this->request->getPost() ?? [];
        }
        
        try {
            // Validation
            if (empty($params['student_id'])) {
                if ($isSSE) {
                    while (ob_get_level()) ob_end_clean();
                    
                    // Set CORS headers
                    $origin = $this->request->getHeaderLine('Origin');
                    $allowedOrigins = explode(',', env('cors.allowedOrigins', 'http://localhost:8211,http://localhost:4211'));
                    if (in_array($origin, $allowedOrigins)) {
                        header('Access-Control-Allow-Origin: ' . $origin);
                    } else {
                        header('Access-Control-Allow-Origin: ' . $allowedOrigins[0]);
                    }
                    header('Access-Control-Allow-Credentials: true');
                    
                    header('Content-Type: text/event-stream');
                    header('Cache-Control: no-cache');
                    header('X-Accel-Buffering: no');
                    echo 'data: ' . json_encode(['error' => 'Student ID should not be empty']) . "\n\n";
                    flush();
                    exit;
                }
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => [],
                    'ErrorObject' => 'Student ID should not be empty'
                ]);
            }

            $db = \Config\Database::connect();

            // Get classes for this student
            $builder = $db->table('student_class sc');
            $builder->select('sc.*, c.class_id, c.class_name, c.subject, c.grade, c.start_date, c.end_date,
                             c.status as class_status, c.class_type, c.tags, c.class_code,
                             s.subject_name, g.grade_name,
                             (CASE WHEN c.start_date > CURDATE() THEN "1"
                                   WHEN c.start_date <= CURDATE() AND c.end_date >= CURDATE() THEN "2"
                                   WHEN c.end_date < CURDATE() AND c.end_date != "0000-00-00" THEN "3"
                                   ELSE "2" END) AS classDate_status');
            $builder->join('class c', 'sc.class_id = c.class_id', 'left');
            $builder->join('subject s', 'c.subject = s.subject_id', 'left');
            $builder->join('grade g', 'c.grade = g.grade_id', 'left');
            $builder->where('sc.student_id', $params['student_id']);
            $builder->where('sc.status', '1');
            $builder->where('c.status', '1');

            // Apply type filter if provided
            if (isset($params['type']) && $params['type'] != '') {
                // Type 2: Upcoming classes
                if ($params['type'] == '2') {
                    $builder->where('c.start_date >', date('Y-m-d'));
                }
                // Type 3: In progress classes
                elseif ($params['type'] == '3') {
                    $builder->where('c.start_date <=', date('Y-m-d'));
                    $builder->where('c.end_date >=', date('Y-m-d'));
                }
                // Type 4: Completed classes
                elseif ($params['type'] == '4') {
                    $builder->where('c.end_date <', date('Y-m-d'));
                }
                // Type 1 or default: All classes (no additional filter)
            }

            $builder->orderBy('c.class_id', 'DESC');
            $classes = $builder->get()->getResultArray();

            // Process the results and add nested data
            foreach ($classes as &$class) {
                // Process tags
                $class['tags'] = !empty($class['tags']) ? explode(',', $class['tags']) : [];
                
                // Handle date status
                if ($class['start_date'] == '0000-00-00' || $class['end_date'] == '0000-00-00') {
                    $class['classDate_status'] = "5";
                }
                
                // Get class notes/announcements
                $notesBuilder = $db->table('class_notes');
                $notesBuilder->where('class_id', $class['class_id']);
                $notesBuilder->where('status', '1');
                $notesBuilder->orderBy('id', 'DESC');
                $class['announcement_message'] = $notesBuilder->get()->getResultArray();
                $class['announcement_count'] = count($class['announcement_message']);
                
                // Get unread inbox messages for this student
                $class['inbox_message'] = [];
                $class['inbox_count'] = 0;
                
                // Get availability/schedule data
                $scheduleBuilder = $db->table('class_schedule');
                $scheduleBuilder->select('*, id as schedule_id, slot_days as slotday, start_time as slotstarttime, end_time as slotendtime');
                $scheduleBuilder->where('class_id', $class['class_id']);
                $scheduleBuilder->orderBy('slot_days', 'ASC');
                $class['availabilityDate'] = $scheduleBuilder->get()->getResultArray();
            }

            // Return response in appropriate format
            if ($isSSE) {
                // For SSE, clear buffers and set headers
                while (ob_get_level()) ob_end_clean();
                
                // Set CORS headers (must be set after ob_end_clean)
                $origin = $this->request->getHeaderLine('Origin');
                $allowedOrigins = explode(',', env('cors.allowedOrigins', 'http://localhost:8211,http://localhost:4211'));
                if (in_array($origin, $allowedOrigins)) {
                    header('Access-Control-Allow-Origin: ' . $origin);
                } else {
                    header('Access-Control-Allow-Origin: ' . $allowedOrigins[0]);
                }
                header('Access-Control-Allow-Credentials: true');
                
                // Set SSE headers
                header('Content-Type: text/event-stream');
                header('Cache-Control: no-cache');
                header('Connection: keep-alive');
                header('X-Accel-Buffering: no');
                
                // Send the data
                echo 'data: ' . json_encode(['classList' => $classes]) . "\n\n";
                flush();
                
                // Close the database connection
                $db->close();
                exit;
            }

            // For standard JSON response
            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $classes,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Student classList error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            
            // Return error in appropriate format
            if ($isSSE) {
                while (ob_get_level()) ob_end_clean();
                
                // Set CORS headers
                $origin = $this->request->getHeaderLine('Origin');
                $allowedOrigins = explode(',', env('cors.allowedOrigins', 'http://localhost:8211,http://localhost:4211'));
                if (in_array($origin, $allowedOrigins)) {
                    header('Access-Control-Allow-Origin: ' . $origin);
                } else {
                    header('Access-Control-Allow-Origin: ' . $allowedOrigins[0]);
                }
                header('Access-Control-Allow-Credentials: true');
                
                header('Content-Type: text/event-stream');
                header('Cache-Control: no-cache');
                header('X-Accel-Buffering: no');
                echo 'data: ' . json_encode(['error' => $e->getMessage()]) . "\n\n";
                flush();
                exit;
            }
            
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => [],
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get curriculum list for a student
     */
    public function curriculumList()
    {
        // Check for SSE early and disable error display for SSE
        $rawInput = file_get_contents('php://input');
        $tempParams = json_decode($rawInput, true);
        $isSSE = isset($tempParams['response_type']) && $tempParams['response_type'] === 'SSE';
        
        if ($isSSE) {
            // For SSE, suppress all errors and warnings to prevent contaminating the stream
            error_reporting(0);
            ini_set('display_errors', '0');
        }
        
        $params = $this->request->getJSON(true) ?? [];
        
        if (empty($params)) {
            $params = $this->request->getPost() ?? [];
        }
        
        try {
            // Validation
            if (empty($params['student_id'])) {
                if ($isSSE) {
                    while (ob_get_level()) ob_end_clean();
                    
                    // Set CORS headers
                    $origin = $this->request->getHeaderLine('Origin');
                    $allowedOrigins = explode(',', env('cors.allowedOrigins', 'http://localhost:8211,http://localhost:4211'));
                    if (in_array($origin, $allowedOrigins)) {
                        header('Access-Control-Allow-Origin: ' . $origin);
                    } else {
                        header('Access-Control-Allow-Origin: ' . $allowedOrigins[0]);
                    }
                    header('Access-Control-Allow-Credentials: true');
                    
                    header('Content-Type: text/event-stream');
                    header('Cache-Control: no-cache');
                    header('X-Accel-Buffering: no');
                    echo 'data: ' . json_encode(['error' => 'Student ID should not be empty']) . "\n\n";
                    flush();
                    exit;
                }
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => [],
                    'ErrorObject' => 'Student ID should not be empty'
                ]);
            }

            $db = \Config\Database::connect();

            // Get student's curriculum/content
            $builder = $db->table('student_content sc');
            $builder->select('sc.*, sc.class_content_id, sc.content_id, sc.class_id,
                             c.name as content_name, c.content_type, c.content_format, c.file_path,
                             cc.start_date, cc.end_date, cc.notes, cc.downloadable,
                             cl.class_name, cl.subject, cl.grade,
                             s.subject_name, g.grade_name,
                             DATEDIFF(cc.end_date, CURDATE()) as overdue,
                             (CASE 
                                WHEN cc.start_date > CURDATE() THEN "1"
                                WHEN cc.start_date <= CURDATE() AND cc.end_date >= CURDATE() THEN "2"
                                WHEN cc.end_date < CURDATE() THEN "3"
                                ELSE "2"
                             END) AS content_date_status');
            $builder->join('content c', 'sc.content_id = c.content_id', 'left');
            $builder->join('class_content cc', 'sc.class_content_id = cc.id', 'left');
            $builder->join('class cl', 'sc.class_id = cl.class_id', 'left');
            $builder->join('subject s', 'cl.subject = s.subject_id', 'left');
            $builder->join('grade g', 'cl.grade = g.grade_id', 'left');
            $builder->where('sc.student_id', $params['student_id']);
            $builder->where('sc.draft_status', '1');
            $builder->where('cc.status', '1');
            $builder->orderBy('cc.end_date', 'ASC');
            $curriculum = $builder->get()->getResultArray();

            // Process file paths
            foreach ($curriculum as &$item) {
                if (!empty($item['file_path'])) {
                    $filePath = json_decode($item['file_path'], true);
                    $item['file_path'] = $filePath;
                    $item['answerkey_path'] = isset($filePath[0]['original_image_url']) ? $filePath[0]['original_image_url'] : '';
                } else {
                    $item['file_path'] = [];
                    $item['answerkey_path'] = '';
                }
                
                // Add student content status
                $item['student_content_status'] = $item['draft_status'] ?? '1';
            }

            // Get latest curriculum (upcoming or current)
            $latestCurriculum = [];
            foreach ($curriculum as $item) {
                if ($item['content_date_status'] == '1' || $item['content_date_status'] == '2') {
                    $latestCurriculum[] = $item;
                    if (count($latestCurriculum) >= 5) {
                        break;
                    }
                }
            }

            $response = [
                'curriculumList' => $curriculum,
                'LatestCurriculum' => $latestCurriculum
            ];

            // Return response in appropriate format
            if ($isSSE) {
                // For SSE, clear buffers and set headers
                while (ob_get_level()) ob_end_clean();
                
                // Set CORS headers (must be set after ob_end_clean)
                $origin = $this->request->getHeaderLine('Origin');
                $allowedOrigins = explode(',', env('cors.allowedOrigins', 'http://localhost:8211,http://localhost:4211'));
                if (in_array($origin, $allowedOrigins)) {
                    header('Access-Control-Allow-Origin: ' . $origin);
                } else {
                    header('Access-Control-Allow-Origin: ' . $allowedOrigins[0]);
                }
                header('Access-Control-Allow-Credentials: true');
                
                // Set SSE headers
                header('Content-Type: text/event-stream');
                header('Cache-Control: no-cache');
                header('Connection: keep-alive');
                header('X-Accel-Buffering: no');
                
                // Send the data
                echo 'data: ' . json_encode($response) . "\n\n";
                flush();
                
                // Close the database connection
                $db->close();
                exit;
            }

            // For standard JSON response
            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $response,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Student curriculumList error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            
            // Return error in appropriate format
            if ($isSSE) {
                while (ob_get_level()) ob_end_clean();
                
                // Set CORS headers
                $origin = $this->request->getHeaderLine('Origin');
                $allowedOrigins = explode(',', env('cors.allowedOrigins', 'http://localhost:8211,http://localhost:4211'));
                if (in_array($origin, $allowedOrigins)) {
                    header('Access-Control-Allow-Origin: ' . $origin);
                } else {
                    header('Access-Control-Allow-Origin: ' . $allowedOrigins[0]);
                }
                header('Access-Control-Allow-Credentials: true');
                
                header('Content-Type: text/event-stream');
                header('Cache-Control: no-cache');
                header('X-Accel-Buffering: no');
                echo 'data: ' . json_encode(['error' => $e->getMessage()]) . "\n\n";
                flush();
                exit;
            }
            
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => [],
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get assessment list for a student
     */
    public function assessmentList(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            // Validation
            if (empty($params['student_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => [],
                    'ErrorObject' => 'Student ID should not be empty'
                ]);
            }

            $db = \Config\Database::connect();

            // Get student's assessments (content_type = 2 for assessments)
            $builder = $db->table('student_content sc');
            $builder->select('sc.*, sc.class_content_id, sc.content_id, sc.class_id,
                             c.name as content_name, c.content_type, c.content_format, c.file_path,
                             cc.start_date, cc.end_date, cc.notes, cc.downloadable,
                             cl.class_name, cl.subject, cl.grade,
                             s.subject_name, g.grade_name,
                             DATEDIFF(cc.end_date, CURDATE()) as overdue,
                             (CASE 
                                WHEN cc.start_date > CURDATE() THEN "1"
                                WHEN cc.start_date <= CURDATE() AND cc.end_date >= CURDATE() THEN "2"
                                WHEN cc.end_date < CURDATE() THEN "3"
                                ELSE "2"
                             END) AS content_date_status');
            $builder->join('content c', 'sc.content_id = c.content_id', 'left');
            $builder->join('class_content cc', 'sc.class_content_id = cc.id', 'left');
            $builder->join('class cl', 'sc.class_id = cl.class_id', 'left');
            $builder->join('subject s', 'cl.subject = s.subject_id', 'left');
            $builder->join('grade g', 'cl.grade = g.grade_id', 'left');
            $builder->where('sc.student_id', $params['student_id']);
            $builder->where('c.content_type', '2'); // 2 = assessment
            $builder->where('sc.draft_status', '1');
            $builder->where('cc.status', '1');

            // Apply type filter if provided
            if (isset($params['type']) && $params['type'] != '') {
                // Type 2: Upcoming
                if ($params['type'] == '2') {
                    $builder->where('cc.start_date >', date('Y-m-d'));
                }
                // Type 3: In progress
                elseif ($params['type'] == '3') {
                    $builder->where('cc.start_date <=', date('Y-m-d'));
                    $builder->where('cc.end_date >=', date('Y-m-d'));
                }
                // Type 4: Completed
                elseif ($params['type'] == '4') {
                    $builder->where('cc.end_date <', date('Y-m-d'));
                }
            }

            $builder->orderBy('cc.end_date', 'ASC');
            $assessments = $builder->get()->getResultArray();

            // Process file paths
            foreach ($assessments as &$item) {
                if (!empty($item['file_path'])) {
                    $filePath = json_decode($item['file_path'], true);
                    $item['file_path'] = $filePath;
                    $item['answerkey_path'] = isset($filePath[0]['original_image_url']) ? $filePath[0]['original_image_url'] : '';
                } else {
                    $item['file_path'] = [];
                    $item['answerkey_path'] = '';
                }
            }

            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $assessments,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Student assessmentList error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => [],
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get assignment list for a student
     */
    public function assignmentList(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            // Validation
            if (empty($params['student_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => [],
                    'ErrorObject' => 'Student ID should not be empty'
                ]);
            }

            $db = \Config\Database::connect();

            // Get student's assignments (content_type = 3 for assignments)
            $builder = $db->table('student_content sc');
            $builder->select('sc.*, sc.class_content_id, sc.content_id, sc.class_id,
                             c.name as content_name, c.content_type, c.content_format, c.file_path,
                             cc.start_date, cc.end_date, cc.notes, cc.downloadable,
                             cl.class_name, cl.subject, cl.grade,
                             s.subject_name, g.grade_name,
                             DATEDIFF(cc.end_date, CURDATE()) as overdue,
                             (CASE 
                                WHEN cc.start_date > CURDATE() THEN "1"
                                WHEN cc.start_date <= CURDATE() AND cc.end_date >= CURDATE() THEN "2"
                                WHEN cc.end_date < CURDATE() THEN "3"
                                ELSE "2"
                             END) AS content_date_status');
            $builder->join('content c', 'sc.content_id = c.content_id', 'left');
            $builder->join('class_content cc', 'sc.class_content_id = cc.id', 'left');
            $builder->join('class cl', 'sc.class_id = cl.class_id', 'left');
            $builder->join('subject s', 'cl.subject = s.subject_id', 'left');
            $builder->join('grade g', 'cl.grade = g.grade_id', 'left');
            $builder->where('sc.student_id', $params['student_id']);
            $builder->where('c.content_type', '3'); // 3 = assignment
            $builder->where('sc.draft_status', '1');
            $builder->where('cc.status', '1');

            // Apply type filter if provided
            if (isset($params['type']) && $params['type'] != '') {
                // Type 2: Upcoming
                if ($params['type'] == '2') {
                    $builder->where('cc.start_date >', date('Y-m-d'));
                }
                // Type 3: In progress
                elseif ($params['type'] == '3') {
                    $builder->where('cc.start_date <=', date('Y-m-d'));
                    $builder->where('cc.end_date >=', date('Y-m-d'));
                }
                // Type 4: Completed
                elseif ($params['type'] == '4') {
                    $builder->where('cc.end_date <', date('Y-m-d'));
                }
            }

            $builder->orderBy('cc.end_date', 'ASC');
            $assignments = $builder->get()->getResultArray();

            // Process file paths
            foreach ($assignments as &$item) {
                if (!empty($item['file_path'])) {
                    $filePath = json_decode($item['file_path'], true);
                    $item['file_path'] = $filePath;
                    $item['answerkey_path'] = isset($filePath[0]['original_image_url']) ? $filePath[0]['original_image_url'] : '';
                } else {
                    $item['file_path'] = [];
                    $item['answerkey_path'] = '';
                }
            }

            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $assignments,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Student assignmentList error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => [],
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get resources list for a student
     */
    public function resourcesList(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            // Validation
            if (empty($params['platform']) || ($params['platform'] != "web" && $params['platform'] != "ios")) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => [],
                    'ErrorObject' => 'Platform should not be empty'
                ]);
            }

            if (empty($params['user_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => [],
                    'ErrorObject' => 'User ID should not be empty'
                ]);
            }

            if (empty($params['school_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => [],
                    'ErrorObject' => 'School ID should not be empty'
                ]);
            }

            $db = \Config\Database::connect();

            // Get student's resources (content_type = 1 for resources)
            $builder = $db->table('student_content sc');
            $builder->select('sc.*, sc.class_content_id, sc.content_id, sc.class_id,
                             c.name as content_name, c.content_type, c.content_format, c.file_path,
                             c.links, c.annotation,
                             cc.start_date, cc.end_date, cc.notes, cc.downloadable,
                             cl.class_name, cl.subject, cl.grade,
                             s.subject_name, g.grade_name,
                             DATEDIFF(cc.end_date, CURDATE()) as overdue,
                             (CASE 
                                WHEN cc.start_date > CURDATE() THEN "1"
                                WHEN cc.start_date <= CURDATE() AND cc.end_date >= CURDATE() THEN "2"
                                WHEN cc.end_date < CURDATE() THEN "3"
                                ELSE "2"
                             END) AS content_date_status');
            $builder->join('content c', 'sc.content_id = c.content_id', 'left');
            $builder->join('class_content cc', 'sc.class_content_id = cc.id', 'left');
            $builder->join('class cl', 'sc.class_id = cl.class_id', 'left');
            $builder->join('subject s', 'cl.subject = s.subject_id', 'left');
            $builder->join('grade g', 'cl.grade = g.grade_id', 'left');
            $builder->where('sc.student_id', $params['user_id']);
            $builder->where('c.content_type', '1'); // 1 = resource
            $builder->where('sc.draft_status', '1');
            $builder->where('cc.status', '1');

            // Apply grade filter if provided
            if (isset($params['grade_id']) && !empty($params['grade_id'])) {
                $builder->where('cl.grade', $params['grade_id']);
            }

            // Apply type filter if provided
            if (isset($params['type']) && $params['type'] != '') {
                // Type 2: Upcoming
                if ($params['type'] == '2') {
                    $builder->where('cc.start_date >', date('Y-m-d'));
                }
                // Type 3: In progress
                elseif ($params['type'] == '3') {
                    $builder->where('cc.start_date <=', date('Y-m-d'));
                    $builder->where('cc.end_date >=', date('Y-m-d'));
                }
                // Type 4: Completed
                elseif ($params['type'] == '4') {
                    $builder->where('cc.end_date <', date('Y-m-d'));
                }
            }

            $builder->orderBy('cc.end_date', 'ASC');
            $resources = $builder->get()->getResultArray();

            // Process file paths, links, and annotations
            foreach ($resources as &$item) {
                // Process file paths
                if (!empty($item['file_path'])) {
                    $filePath = json_decode($item['file_path'], true);
                    $item['file_path'] = $filePath ?? [];
                    $item['is_pdf_content'] = (!empty($filePath) && isset($filePath[0]['original_image_url']) && !empty($filePath[0]['original_image_url'])) ? 1 : 0;
                } else {
                    $item['file_path'] = [];
                    $item['is_pdf_content'] = 0;
                }

                // Process links
                if (!empty($item['links'])) {
                    $links = json_decode($item['links'], true);
                    if ($links === null) {
                        // If not valid JSON, try exploding by comma
                        $item['links'] = explode(',', $item['links']);
                    } else {
                        $item['links'] = $links;
                    }
                } else {
                    $item['links'] = [];
                }

                // Process annotations
                if (!empty($item['annotation'])) {
                    $annotation = json_decode($item['annotation'], true);
                    if ($params['platform'] == 'ios') {
                        $item['annotation'] = $item['annotation'];
                    } else {
                        $item['annotation'] = $annotation ?? [];
                    }
                } else {
                    $item['annotation'] = ($params['platform'] == 'ios') ? '' : [];
                }
            }

            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $resources,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Student resourcesList error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => [],
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }
}

