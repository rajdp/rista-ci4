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
            
            // Convert array to object if needed
            if (is_array($data)) {
                $data = (object)$data;
            }
            
            $students = $this->studentModel->getStudents($data ?? (object)[]);
            
            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $students,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Student list error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
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
            $schoolId = $params['school_id'] ?? null;

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
                             up.first_name, up.last_name, up.gender, 
                             COALESCE(ua.country, "") as country, 
                             COALESCE(ua.state, "") as state, 
                             COALESCE(ua.city, "") as city,
                             up.profile_url, up.profile_thumb_url, 
                             COALESCE(ua.address1, "") as address, 
                             COALESCE(ua.postal_code, "") as zipcode,
                             upd.grade_id, upd.doj as joining_date, upd.dropped_date, 
                             COALESCE(DATE_FORMAT(upd.next_billing_date, "%Y-%m-%d"), "") as next_billing_date,
                             COALESCE(u.mobile, "") as phone, 
                             COALESCE(ua_parent.name, "") as parent_name,
                             COALESCE(ua_parent.email_ids, "") as parent_email,
                             "" as parent_phone,
                             upd.school_id, 
                             COALESCE(up.birthday, "") as dob');
            $builder->join('user_profile up', 'u.user_id = up.user_id', 'left');
            // Join user_profile_details with school_id filter if provided to get the correct next_billing_date
            if (!empty($schoolId)) {
                $builder->join('user_profile_details upd', 'u.user_id = upd.user_id AND upd.school_id = ' . (int)$schoolId, 'left');
            } else {
                $builder->join('user_profile_details upd', 'u.user_id = upd.user_id', 'left');
            }
            $builder->join('user_address ua', 'u.user_id = ua.user_id', 'left');
            $builder->join('user_address ua_parent', 'u.user_id = ua_parent.user_id AND ua_parent.address_type = 2', 'left');
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
                if (isset($value['teacher_id']) && !empty($value['teacher_id'])) {
                    $classList[$key]['teacher_id'] = explode(',', $value['teacher_id']);
                } else {
                    $classList[$key]['teacher_id'] = [];
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
            log_message('error', 'StudentFromClassList error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update student details
     */
    public function update($id = null): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            // Use $id parameter if provided, otherwise use selected_user_id from params
            $userId = $id ?? ($params['selected_user_id'] ?? null);
            $schoolId = $params['school_id'] ?? null;

            // Validation
            if (empty($userId)) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'User Id should not be empty'
                ]);
            }

            if (empty($schoolId)) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'School Id should not be empty'
                ]);
            }

            $db = \Config\Database::connect();
            $db->transStart();

            // Check if email already exists for another user
            if (!empty($params['email_id'])) {
                $emailCheck = $db->table('user')
                    ->where('email_id', $params['email_id'])
                    ->where('user_id !=', $userId)
                    ->countAllResults();
                
                if ($emailCheck > 0) {
                    $db->transRollback();
                    return $this->respond([
                        'IsSuccess' => false,
                        'ResponseObject' => null,
                        'ErrorObject' => 'Email ID already exists'
                    ]);
                }
            }

            // Update user table
            $userData = [];
            if (isset($params['mobile']) && is_array($params['mobile'])) {
                $mobileArray = array_filter($params['mobile']);
                if (!empty($mobileArray)) {
                    $userData['mobile'] = implode(',', $mobileArray);
                }
            }
            if (isset($params['email_id'])) {
                $userData['email_id'] = $params['email_id'];
            }
            if (isset($params['status'])) {
                $userData['status'] = $params['status'];
            }
            if (isset($params['user_id'])) {
                $userData['modified_by'] = $params['user_id'];
            }
            $userData['modified_date'] = date('Y-m-d H:i:s');

            if (!empty($userData)) {
                $db->table('user')
                    ->where('user_id', $userId)
                    ->update($userData);
            }

            // Update user_profile table
            $profileData = [];
            if (isset($params['first_name'])) {
                $profileData['first_name'] = $params['first_name'];
            }
            if (isset($params['last_name'])) {
                $profileData['last_name'] = $params['last_name'];
            }
            if (isset($params['gender'])) {
                $profileData['gender'] = $params['gender'];
            }
            if (isset($params['birthday'])) {
                $profileData['birthday'] = $params['birthday'];
            }
            if (isset($params['profile_url'])) {
                $profileData['profile_url'] = $params['profile_url'];
            }
            if (isset($params['profile_thumb_url'])) {
                $profileData['profile_thumb_url'] = $params['profile_thumb_url'];
            }
            if (isset($params['user_id'])) {
                $profileData['modified_by'] = $params['user_id'];
            }
            $profileData['modified_date'] = date('Y-m-d H:i:s');

            if (!empty($profileData)) {
                $db->table('user_profile')
                    ->where('user_id', $userId)
                    ->update($profileData);
            }

            // Update user_profile_details table
            $profileDetailsData = [];
            if (isset($params['registration_date'])) {
                $profileDetailsData['doj'] = $params['registration_date'];
            }
            if (isset($params['status'])) {
                $profileDetailsData['status'] = $params['status'];
            }
            if (isset($params['dropped_date'])) {
                $profileDetailsData['dropped_date'] = $params['dropped_date'];
            }
            if (isset($params['school_idno'])) {
                $profileDetailsData['school_idno'] = $params['school_idno'];
            }
            if (isset($params['grade_id'])) {
                $profileDetailsData['grade_id'] = $params['grade_id'] ?: 0;
            }
            if (isset($params['batch_id'])) {
                $profileDetailsData['batch_id'] = $params['batch_id'];
            }
            if (isset($params['next_billing_date'])) {
                // Check if column exists before trying to update it
                if ($db->fieldExists('next_billing_date', 'user_profile_details')) {
                    // Handle empty string, null, or valid date
                    $nextBillingDate = $params['next_billing_date'];
                    if ($nextBillingDate === null || $nextBillingDate === 'null' || $nextBillingDate === '') {
                        $profileDetailsData['next_billing_date'] = null;
                    } else {
                        // Trim only if it's a string
                        $nextBillingDate = is_string($nextBillingDate) ? trim($nextBillingDate) : $nextBillingDate;
                        if ($nextBillingDate === '') {
                            $profileDetailsData['next_billing_date'] = null;
                        } else {
                            // Validate date format (YYYY-MM-DD)
                            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $nextBillingDate)) {
                                $profileDetailsData['next_billing_date'] = $nextBillingDate;
                            } else {
                                // Invalid format, set to null
                                $profileDetailsData['next_billing_date'] = null;
                            }
                        }
                    }
                } else {
                    // Column doesn't exist - log warning but don't fail the update
                    log_message('warning', 'next_billing_date column does not exist in user_profile_details table. Please run migration.');
                }
            }

            if (!empty($profileDetailsData)) {
                // Get user_details_id for the school
                $userDetails = $db->table('user_profile_details')
                    ->where('user_id', $userId)
                    ->where('school_id', $schoolId)
                    ->get()
                    ->getRowArray();

                if ($userDetails) {
                    $db->table('user_profile_details')
                        ->where('user_details_id', $userDetails['user_details_id'])
                        ->where('school_id', $schoolId)
                        ->update($profileDetailsData);
                } else {
                    // Create if doesn't exist
                    $profileDetailsData['user_id'] = $userId;
                    $profileDetailsData['school_id'] = $schoolId;
                    $profileDetailsData['created_date'] = date('Y-m-d H:i:s');
                    if (isset($params['user_id'])) {
                        $profileDetailsData['created_by'] = $params['user_id'];
                    }
                    $db->table('user_profile_details')->insert($profileDetailsData);
                }
            }

            // Update addresses
            if (isset($params['address']) && is_array($params['address'])) {
                foreach ($params['address'] as $index => $address) {
                    $addressType = ($index === 0) ? 2 : 3; // 2 = parent1, 3 = parent2
                    $parentKey = ($index === 0) ? 'parent1' : 'parent2';
                    
                    $addressData = [];
                    if (isset($address['address1'])) {
                        $addressData['address1'] = $address['address1'];
                    }
                    if (isset($address['address2'])) {
                        $addressData['address2'] = $address['address2'];
                    }
                    if (isset($address['city'])) {
                        $addressData['city'] = $address['city'];
                    }
                    if (isset($address['state'])) {
                        $addressData['state'] = $address['state'];
                    }
                    if (isset($address['country'])) {
                        $addressData['country'] = $address['country'];
                    }
                    if (isset($address['postal_code'])) {
                        $addressData['postal_code'] = $address['postal_code'];
                    }
                    
                    // Parent name
                    $parentFirstName = $params[$parentKey . '_firstname'] ?? '';
                    $parentLastName = $params[$parentKey . '_lastname'] ?? '';
                    if ($parentFirstName || $parentLastName) {
                        $addressData['name'] = trim($parentFirstName . ',' . $parentLastName);
                    }
                    
                    // Parent emails
                    $emailKey = $parentKey . '_email_ids';
                    if (isset($params[$emailKey]) && is_array($params[$emailKey])) {
                        $emailArray = array_filter($params[$emailKey]);
                        if (!empty($emailArray)) {
                            $addressData['email_ids'] = implode(',', $emailArray);
                        }
                    }

                    // Check if address exists
                    $existingAddress = $db->table('user_address')
                        ->where('user_id', $userId)
                        ->where('address_type', $addressType)
                        ->get()
                        ->getRowArray();

                    if ($existingAddress) {
                        $db->table('user_address')
                            ->where('user_id', $userId)
                            ->where('address_type', $addressType)
                            ->update($addressData);
                    } else {
                        $addressData['user_id'] = $userId;
                        $addressData['address_type'] = $addressType;
                        $addressData['created_date'] = date('Y-m-d H:i:s');
                        $db->table('user_address')->insert($addressData);
                    }
                }
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                $error = $db->error();
                log_message('error', 'Student update transaction failed: ' . json_encode($error));
                log_message('error', 'Update params: ' . json_encode($params));
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Failed to update student details: ' . ($error['message'] ?? 'Database error')
                ]);
            }

            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => 'Student updated successfully',
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Student update error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            $db = \Config\Database::connect();
            $dbError = $db->error();
            if (!empty($dbError)) {
                log_message('error', 'Database error: ' . json_encode($dbError));
            }
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => 'Failed to update student details: ' . $e->getMessage()
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
            $builder->select('sc.class_id, sc.student_id, sc.notify_status, sc.joining_date, sc.drafted_date, sc.validity, sc.status,
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
            // Include all classes (active, scheduled to drop, and past dropped)
            // The frontend will categorize them into Active/Past based on status and validity date
            $builder->groupStart();
                $builder->where('sc.status', 1); // Active classes
                $builder->orWhere('sc.status', 0); // Inactive classes (includes both future and past validity dates)
            $builder->groupEnd();
            $builder->orderBy('sc.created_date', 'DESC');
            
            $classes = $builder->get()->getResultArray();

            // Process each class to add schedule and teacher information
            foreach ($classes as &$class) {
                // Get class schedule with teacher information
                $scheduleBuilder = $db->table('class_schedule cs');
                $scheduleBuilder->select('cs.slot_days, cs.start_time, cs.end_time, cs.teacher_id,
                                        GROUP_CONCAT(DISTINCT CONCAT(up.first_name, " ", up.last_name) SEPARATOR ", ") as teacher_names');
                $scheduleBuilder->join('user_profile up', 'FIND_IN_SET(up.user_id, cs.teacher_id)', 'left');
                $scheduleBuilder->where('cs.class_id', $class['class_id']);
                $scheduleBuilder->orderBy('cs.slot_days', 'ASC');
                $scheduleBuilder->groupBy('cs.id');
                $schedules = $scheduleBuilder->get()->getResultArray();
                
                // Format schedule data
                $class['schedules'] = [];
                $allTeacherNames = [];
                foreach ($schedules as $schedule) {
                    $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                    $dayName = isset($dayNames[$schedule['slot_days']]) ? $dayNames[$schedule['slot_days']] : 'Day ' . $schedule['slot_days'];
                    
                    $scheduleInfo = [
                        'day' => $dayName,
                        'day_number' => $schedule['slot_days'],
                        'start_time' => $schedule['start_time'],
                        'end_time' => $schedule['end_time'],
                        'teacher_names' => !empty($schedule['teacher_names']) ? $schedule['teacher_names'] : ''
                    ];
                    $class['schedules'][] = $scheduleInfo;
                    
                    // Collect all teacher names
                    if (!empty($schedule['teacher_names'])) {
                        $teachers = explode(', ', $schedule['teacher_names']);
                        $allTeacherNames = array_merge($allTeacherNames, $teachers);
                    }
                }
                
                // Remove duplicates and set primary teacher name
                $allTeacherNames = array_unique(array_filter($allTeacherNames));
                if (!empty($allTeacherNames)) {
                    $class['teacher_first_name'] = '';
                    $class['teacher_last_name'] = '';
                    $class['teacher_full_name'] = implode(', ', $allTeacherNames);
                    // Set first teacher as primary for backward compatibility
                    $firstTeacher = explode(' ', $allTeacherNames[0]);
                    if (count($firstTeacher) >= 2) {
                        $class['teacher_first_name'] = $firstTeacher[0];
                        $class['teacher_last_name'] = implode(' ', array_slice($firstTeacher, 1));
                    } else {
                        $class['teacher_first_name'] = $allTeacherNames[0];
                        $class['teacher_last_name'] = '';
                    }
                }
            }

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
            // Clear all output buffers immediately
            while (ob_get_level()) {
                ob_end_clean();
            }
            // Disable output buffering
            ini_set('output_buffering', 'off');
            ini_set('zlib.output_compression', '0');
        }
        
        $params = $this->request->getJSON(true) ?? [];
        
        if (empty($params)) {
            $params = $this->request->getPost() ?? [];
        }
        
        // If SSE, also check in params
        if (!$isSSE && isset($params['response_type']) && $params['response_type'] === 'SSE') {
            $isSSE = true;
            error_reporting(0);
            ini_set('display_errors', '0');
            while (ob_get_level()) {
                ob_end_clean();
            }
            ini_set('output_buffering', 'off');
            ini_set('zlib.output_compression', '0');
        }
        
        try {
            // Validation
            if (empty($params['student_id'])) {
                if ($isSSE) {
                    while (ob_get_level()) {
                        ob_end_clean();
                    }
                    
                    // Set CORS headers
                    $origin = $this->request->getHeaderLine('Origin');
                    $allowedOrigins = explode(',', env('cors.allowedOrigins', 'http://localhost:8211,http://localhost:4211,http://schoolnew.localhost:8211'));
                    
                    // Check for exact match first
                    if (in_array($origin, $allowedOrigins)) {
                        header('Access-Control-Allow-Origin: ' . $origin);
                    } else {
                        // Check if it's a localhost subdomain (e.g., schoolnew.localhost)
                        $originHost = parse_url($origin, PHP_URL_HOST);
                        $isLocalhostSubdomain = $originHost && (
                            strpos($originHost, 'localhost') !== false || 
                            strpos($originHost, '127.0.0.1') !== false ||
                            $originHost === 'localhost' ||
                            preg_match('/^[a-zA-Z0-9-]+\.localhost$/', $originHost)
                        );
                        
                        if ($isLocalhostSubdomain) {
                            header('Access-Control-Allow-Origin: ' . $origin);
                        } else {
                            header('Access-Control-Allow-Origin: ' . ($allowedOrigins[0] ?? '*'));
                        }
                    }
                    header('Access-Control-Allow-Credentials: true');
                    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
                    header('Access-Control-Allow-Headers: Content-Type, Authorization, Accesstoken');
                    
                    // Set SSE headers
                    header('Connection: keep-alive');
                    header('Content-Type: text/event-stream');
                    header('Cache-Control: no-cache, no-store, must-revalidate');
                    header('Pragma: no-cache');
                    header('Expires: 0');
                    header('X-Accel-Buffering: no');
                    
                    $errorData = json_encode(['error' => 'Student ID should not be empty']);
                    echo "data: {$errorData}\n\n";
                    
                    if (ob_get_level()) {
                        ob_end_flush();
                    }
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
            $builder->select('sc.*, sc.joining_date, sc.validity, c.class_id, c.class_name, c.subject, c.grade, c.start_date, c.end_date,
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
            // Include all classes (active, scheduled to drop, and past dropped)
            // The frontend will categorize them into Active/Past based on status and validity date
            $builder->groupStart();
                $builder->where('sc.status', '1'); // Active classes
                $builder->orWhere('sc.status', '0'); // Inactive classes (includes both future and past validity dates)
            $builder->groupEnd();
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
                
                // Map classDate_status to status for frontend compatibility
                // status: "1" = Not Started, "2" = In Progress, "3" = Completed
                $class['status'] = $class['classDate_status'] ?? "2";
                
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
                // For SSE, clear buffers again (in case anything was output)
                while (ob_get_level()) {
                    ob_end_clean();
                }
                
                // Set CORS headers first (must be set after ob_end_clean)
                $origin = $this->request->getHeaderLine('Origin');
                $allowedOrigins = explode(',', env('cors.allowedOrigins', 'http://localhost:8211,http://localhost:4211,http://schoolnew.localhost:8211'));
                
                // Check for exact match first
                if (in_array($origin, $allowedOrigins)) {
                    header('Access-Control-Allow-Origin: ' . $origin);
                } else {
                    // Check if it's a localhost subdomain (e.g., schoolnew.localhost)
                    $originHost = parse_url($origin, PHP_URL_HOST);
                    $isLocalhostSubdomain = $originHost && (
                        strpos($originHost, 'localhost') !== false || 
                        strpos($originHost, '127.0.0.1') !== false ||
                        $originHost === 'localhost' ||
                        preg_match('/^[a-zA-Z0-9-]+\.localhost$/', $originHost)
                    );
                    
                    if ($isLocalhostSubdomain) {
                        header('Access-Control-Allow-Origin: ' . $origin);
                    } else {
                        header('Access-Control-Allow-Origin: ' . ($allowedOrigins[0] ?? '*'));
                    }
                }
                header('Access-Control-Allow-Credentials: true');
                header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
                header('Access-Control-Allow-Headers: Content-Type, Authorization, Accesstoken');
                
                // Set SSE headers (Connection must be set before Content-Type for some servers)
                header('Connection: keep-alive');
                header('Content-Type: text/event-stream');
                header('Cache-Control: no-cache, no-store, must-revalidate');
                header('Pragma: no-cache');
                header('Expires: 0');
                header('X-Accel-Buffering: no');
                
                // Send the data with proper SSE format
                $data = json_encode(['classList' => $classes]);
                echo "data: {$data}\n\n";
                
                // Force flush all output
                if (ob_get_level()) {
                    ob_end_flush();
                }
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
                while (ob_get_level()) {
                    ob_end_clean();
                }
                
                // Set CORS headers
                $origin = $this->request->getHeaderLine('Origin');
                $allowedOrigins = explode(',', env('cors.allowedOrigins', 'http://localhost:8211,http://localhost:4211,http://schoolnew.localhost:8211'));
                
                // Check for exact match first
                if (in_array($origin, $allowedOrigins)) {
                    header('Access-Control-Allow-Origin: ' . $origin);
                } else {
                    // Check if it's a localhost subdomain (e.g., schoolnew.localhost)
                    $originHost = parse_url($origin, PHP_URL_HOST);
                    $isLocalhostSubdomain = $originHost && (
                        strpos($originHost, 'localhost') !== false || 
                        strpos($originHost, '127.0.0.1') !== false ||
                        $originHost === 'localhost' ||
                        preg_match('/^[a-zA-Z0-9-]+\.localhost$/', $originHost)
                    );
                    
                    if ($isLocalhostSubdomain) {
                        header('Access-Control-Allow-Origin: ' . $origin);
                    } else {
                        header('Access-Control-Allow-Origin: ' . ($allowedOrigins[0] ?? '*'));
                    }
                }
                header('Access-Control-Allow-Credentials: true');
                header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
                header('Access-Control-Allow-Headers: Content-Type, Authorization, Accesstoken');
                
                // Set SSE headers
                header('Connection: keep-alive');
                header('Content-Type: text/event-stream');
                header('Cache-Control: no-cache, no-store, must-revalidate');
                header('Pragma: no-cache');
                header('Expires: 0');
                header('X-Accel-Buffering: no');
                
                $errorData = json_encode(['error' => $e->getMessage()]);
                echo "data: {$errorData}\n\n";
                
                if (ob_get_level()) {
                    ob_end_flush();
                }
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
     * Get student class list (legacy endpoint for compatibility)
     * This method provides the same functionality as the old CI3 studentClassList endpoint
     */
    public function studentClassList(): ResponseInterface
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
            
            if (empty($params['user_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'User Id should not be empty'
                ]);
            }
            
            if (empty($params['role_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Role Id should not be empty'
                ]);
            }
            
            if (empty($params['student_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Student Id should not be empty'
                ]);
            }
            
            if (empty($params['school_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'School Id should not be empty'
                ]);
            }

            $db = \Config\Database::connect();

            // Handle grade dates if grade_id is provided
            $gradeStartDate = '';
            $gradeEndDate = '';
            if (isset($params['grade_id']) && $params['grade_id'] != '') {
                $gradeBuilder = $db->table('student_grade sg');
                $gradeBuilder->select('sg.grade_id, sg.active_date');
                $gradeBuilder->where('sg.student_id', $params['student_id']);
                $gradeBuilder->orderBy('sg.grade_id', 'ASC');
                $getStudentGrade = $gradeBuilder->get()->getResultArray();
                
                foreach ($getStudentGrade as $key => $value) {
                    if ($value['grade_id'] == $params['grade_id']) {
                        $gradeStartDate = $value['active_date'];
                    }
                    if ($value['grade_id'] > $params['grade_id']) {
                        $gradeEndDate = $value['active_date'];
                        break;
                    }
                }
            }
            $params['grade_start_date'] = $gradeStartDate;
            $params['grade_end_date'] = $gradeEndDate;

            // Get classes for this student
            $builder = $db->table('student_class sc');
            $builder->select('sc.*, sc.from_class, sc.transfer_class, sc.transferred_date, sc.joining_date, sc.validity, sc.status,
                             c.class_id, c.class_name, c.subject, c.grade, c.start_date, c.end_date,
                             c.status as class_status, c.class_type, c.tags, c.class_code, c.teacher_id,
                             s.subject_name, g.grade_name,
                             (CASE WHEN c.start_date > CURDATE() THEN "1"
                                   WHEN c.start_date <= CURDATE() AND c.end_date >= CURDATE() THEN "2"
                                   WHEN c.end_date < CURDATE() AND c.end_date != "0000-00-00" THEN "3"
                                   ELSE "2" END) AS classDate_status');
            $builder->join('class c', 'sc.class_id = c.class_id', 'left');
            $builder->join('subject s', 'c.subject = s.subject_id', 'left');
            $builder->join('grade g', 'c.grade = g.grade_id', 'left');
            $builder->where('sc.student_id', $params['student_id']);
            // Include all classes (active, scheduled to drop, and past dropped)
            // The frontend will categorize them into Active/Past based on status and validity date
            $builder->groupStart();
                $builder->where('sc.status', '1'); // Active classes
                $builder->orWhere('sc.status', '0'); // Inactive classes (includes both future and past validity dates)
            $builder->groupEnd();
            $builder->where('c.status', '1');
            $builder->orderBy('c.class_id', 'DESC');
            
            $classes = $builder->get()->getResultArray();

            // Process the results
            foreach ($classes as $key => $value) {
                // Handle transferred classes
                if (isset($value['from_class']) && $value['from_class'] == 0) {
                    $classes[$key]['transferred_date'] = '';
                } elseif (!isset($value['from_class'])) {
                    $classes[$key]['from_class'] = 0;
                    $classes[$key]['transferred_date'] = '';
                }
                
                // Process teacher names
                if (!empty($value['teacher_id'])) {
                    $teacherId = explode(',', $value['teacher_id']);
                    $teacherId = array_unique($teacherId);
                    $teacherName = [];
                    
                    foreach ($teacherId as $id) {
                        $teacherBuilder = $db->table('user_profile up');
                        $teacherBuilder->select("CONCAT(up.first_name, ' ', up.last_name) as teacher_name");
                        $teacherBuilder->where('up.user_id', $id);
                        $getteacherName = $teacherBuilder->get()->getRowArray();
                        
                        if ($getteacherName && !empty($getteacherName['teacher_name'])) {
                            $teacherName[] = $getteacherName['teacher_name'];
                        }
                    }
                    
                    $classes[$key]['teacher_name'] = implode(',', $teacherName);
                } else {
                    $classes[$key]['teacher_name'] = '';
                }
            }

            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $classes,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Student studentClassList error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
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
            @ini_set('display_errors', '0');
            error_reporting(0);
            
            // Clear all output buffers FIRST
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            // Remove any headers that might have been set by CodeIgniter
            if (function_exists('header_remove')) {
                header_remove();
            }
            
            // Disable output buffering completely
            @ini_set('output_buffering', 'off');
            @ini_set('zlib.output_compression', '0');
        }
        
        $params = $this->request->getJSON(true) ?? [];
        
        if (empty($params)) {
            $params = $this->request->getPost() ?? [];
        }
        
        try {
            // Validation
            if (empty($params['student_id'])) {
                if ($isSSE) {
                    // Ensure buffers are cleared
                    while (ob_get_level()) {
                        ob_end_clean();
                    }
                    
                    // Set CORS headers
                    $origin = $this->request->getHeaderLine('Origin');
                    $allowedOrigins = explode(',', env('cors.allowedOrigins', 'http://localhost:8211,http://localhost:4211,http://schoolnew.localhost:8211'));
                    
                    // Check for exact match first
                    if (in_array($origin, $allowedOrigins)) {
                        header('Access-Control-Allow-Origin: ' . $origin);
                    } else {
                        // Check if it's a localhost subdomain (e.g., schoolnew.localhost)
                        $originHost = parse_url($origin, PHP_URL_HOST);
                        $isLocalhostSubdomain = $originHost && (
                            strpos($originHost, 'localhost') !== false || 
                            strpos($originHost, '127.0.0.1') !== false ||
                            $originHost === 'localhost' ||
                            preg_match('/^[a-zA-Z0-9-]+\.localhost$/', $originHost)
                        );
                        
                        if ($isLocalhostSubdomain) {
                            header('Access-Control-Allow-Origin: ' . $origin);
                        } else {
                            header('Access-Control-Allow-Origin: ' . ($allowedOrigins[0] ?? '*'));
                        }
                    }
                    header('Access-Control-Allow-Credentials: true');
                    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
                    header('Access-Control-Allow-Headers: Content-Type, Authorization, Accesstoken');
                    
                    // Set SSE headers
                    header('Content-Type: text/event-stream');
                    header('Cache-Control: no-cache');
                    header('Connection: keep-alive');
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
            // This query handles both cases:
            // 1. Content assigned to all students (all_student = 1): Show to all students in the class
            // 2. Content assigned to specific students (all_student = 0): Only show if student_content record exists
            $sql = "
                SELECT DISTINCT
                    COALESCE(sc.id, 0) as id,
                    COALESCE(sc.id, 0) as student_content_id,
                    COALESCE(sc.class_content_id, cc.id) as class_content_id,
                    cc.content_id,
                    COALESCE(sc.class_id, cc.class_id) as class_id,
                    c.name as content_name,
                    c.content_type,
                    c.content_format,
                    c.file_path,
                    cc.start_date,
                    cc.end_date,
                    cc.notes,
                    cc.downloadable,
                    cl.class_name,
                    cl.subject,
                    cl.grade,
                    s.subject_name,
                    g.grade_name,
                    DATEDIFF(cc.end_date, CURDATE()) as overdue,
                    (CASE 
                        WHEN cc.start_date > CURDATE() THEN '1'
                        WHEN cc.start_date <= CURDATE() AND cc.end_date >= CURDATE() THEN '2'
                        WHEN cc.end_date < CURDATE() THEN '3'
                        ELSE '2'
                    END) AS content_date_status,
                    COALESCE(sc.draft_status, '1') as draft_status,
                    COALESCE(sc.draft_status, '1') as student_content_status,
                    COALESCE(sc.status, 1) as status,
                    sc.created_by,
                    sc.created_date,
                    sc.modified_by,
                    sc.modified_date
                FROM class_content cc
                INNER JOIN content c ON cc.content_id = c.content_id
                INNER JOIN class cl ON cc.class_id = cl.class_id
                LEFT JOIN subject s ON cl.subject = s.subject_id
                LEFT JOIN grade g ON cl.grade = g.grade_id
                LEFT JOIN student_class scs ON cc.class_id = scs.class_id AND scs.student_id = ?
                LEFT JOIN student_content sc ON sc.class_content_id = cc.id 
                    AND sc.student_id = ?
                    AND sc.draft_status = '1'
                WHERE scs.student_id = ?
                AND cc.status = 1
                AND (
                    -- Content assigned to all students in the class
                    -- New students should only see in-progress and future content (not past/completed)
                    -- In-progress: start_date <= CURDATE() AND end_date >= CURDATE()
                    -- Future: start_date > CURDATE()
                    -- Combined: end_date >= CURDATE() (covers both in-progress and future)
                    (cc.all_student = 1 AND cc.end_date >= CURDATE())
                    OR
                    -- Content assigned to specific students - only show if student_content record exists
                    -- (No date restriction for student-specific content - they see all their assigned content)
                    (cc.all_student = 0 AND sc.id IS NOT NULL)
                )
                ORDER BY cc.end_date ASC
            ";
            
            $curriculum = $db->query($sql, [
                $params['student_id'],
                $params['student_id'],
                $params['student_id']
            ])->getResultArray();

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
                
                // Ensure student_content_status is always set (already set in SQL, but double-check)
                if (!isset($item['student_content_status']) || $item['student_content_status'] === null) {
                    $item['student_content_status'] = $item['draft_status'] ?? '1';
                }
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
                // For SSE, ensure buffers are cleared (already done at start, but double-check)
                while (ob_get_level()) {
                    ob_end_clean();
                }
                
                // Set CORS headers
                $origin = $this->request->getHeaderLine('Origin');
                $allowedOrigins = explode(',', env('cors.allowedOrigins', 'http://localhost:8211,http://localhost:4211,http://schoolnew.localhost:8211'));
                
                // Check for exact match first
                if (in_array($origin, $allowedOrigins)) {
                    header('Access-Control-Allow-Origin: ' . $origin);
                } else {
                    // Check if it's a localhost subdomain (e.g., schoolnew.localhost)
                    $originHost = parse_url($origin, PHP_URL_HOST);
                    $isLocalhostSubdomain = $originHost && (
                        strpos($originHost, 'localhost') !== false || 
                        strpos($originHost, '127.0.0.1') !== false ||
                        $originHost === 'localhost' ||
                        preg_match('/^[a-zA-Z0-9-]+\.localhost$/', $originHost)
                    );
                    
                    if ($isLocalhostSubdomain) {
                        header('Access-Control-Allow-Origin: ' . $origin);
                    } else {
                        header('Access-Control-Allow-Origin: ' . ($allowedOrigins[0] ?? '*'));
                    }
                }
                header('Access-Control-Allow-Credentials: true');
                header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
                header('Access-Control-Allow-Headers: Content-Type, Authorization, Accesstoken');
                
                // Set SSE headers (Connection must be set before Content-Type for some servers)
                header('Connection: keep-alive');
                header('Content-Type: text/event-stream');
                header('Cache-Control: no-cache, no-store, must-revalidate');
                header('Pragma: no-cache');
                header('Expires: 0');
                header('X-Accel-Buffering: no');
                
                // Send the data with proper SSE format
                $data = json_encode($response);
                echo "data: {$data}\n\n";
                
                // Force flush all output
                if (ob_get_level()) {
                    ob_end_flush();
                }
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
                // Ensure buffers are cleared
                while (ob_get_level()) {
                    ob_end_clean();
                }
                
                // Set CORS headers
                $origin = $this->request->getHeaderLine('Origin');
                $allowedOrigins = explode(',', env('cors.allowedOrigins', 'http://localhost:8211,http://localhost:4211,http://schoolnew.localhost:8211'));
                
                // Check for exact match first
                if (in_array($origin, $allowedOrigins)) {
                    header('Access-Control-Allow-Origin: ' . $origin);
                } else {
                    // Check if it's a localhost subdomain (e.g., schoolnew.localhost)
                    $originHost = parse_url($origin, PHP_URL_HOST);
                    $isLocalhostSubdomain = $originHost && (
                        strpos($originHost, 'localhost') !== false || 
                        strpos($originHost, '127.0.0.1') !== false ||
                        $originHost === 'localhost' ||
                        preg_match('/^[a-zA-Z0-9-]+\.localhost$/', $originHost)
                    );
                    
                    if ($isLocalhostSubdomain) {
                        header('Access-Control-Allow-Origin: ' . $origin);
                    } else {
                        header('Access-Control-Allow-Origin: ' . ($allowedOrigins[0] ?? '*'));
                    }
                }
                header('Access-Control-Allow-Credentials: true');
                header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
                header('Access-Control-Allow-Headers: Content-Type, Authorization, Accesstoken');
                
                // Set SSE headers
                header('Connection: keep-alive');
                header('Content-Type: text/event-stream');
                header('Cache-Control: no-cache, no-store, must-revalidate');
                header('Pragma: no-cache');
                header('Expires: 0');
                header('X-Accel-Buffering: no');
                
                $errorData = json_encode(['error' => $e->getMessage()]);
                echo "data: {$errorData}\n\n";
                
                if (ob_get_level()) {
                    ob_end_flush();
                }
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

            // Get filter type (1 = All, 2 = Upcoming, 3 = In progress, 4 = Completed)
            $filterType = isset($params['type']) ? $params['type'] : '1';
            $isAllFilter = ($filterType == '1' || $filterType == 1);
            
            // Build date condition for class-wide content
            // For "All" filter: show all class-wide content (no date restriction)
            // For other filters: only show in-progress and future (end_date >= CURDATE())
            $classWideDateCondition = $isAllFilter ? '1=1' : 'cc.end_date >= CURDATE()';
            
            // Get student's assessments (content_type = 2 for assessments)
            // This query handles both class-wide and student-specific assessments
            $sql = "
                SELECT DISTINCT
                    COALESCE(sc.id, 0) as student_content_id,
                    COALESCE(sc.id, 0) as id,
                    COALESCE(sc.class_content_id, cc.id) as class_content_id,
                    cc.content_id,
                    COALESCE(sc.class_id, cc.class_id) as class_id,
                    ? as student_id,
                    COALESCE(sc.status, 1) as status,
                    COALESCE(sc.draft_status, '1') as draft_status,
                    COALESCE(sc.start_date, cc.start_date) as start_date,
                    COALESCE(sc.end_date, cc.end_date) as end_date,
                    COALESCE(sc.earned_points, 0) as earned_points,
                    COALESCE(sc.points, 0) as points,
                    COALESCE(sc.upload_answer, '') as upload_answer,
                    COALESCE(sc.student_feedback, '') as student_feedback,
                    sc.answer_completed_date,
                    sc.platform,
                    sc.redo_test,
                    c.name as content_name,
                    c.content_type,
                    c.content_format,
                    c.file_path,
                    cc.start_date as content_start_date,
                    cc.end_date as content_end_date,
                    cc.notes,
                    cc.downloadable,
                    cl.class_name,
                    cl.subject,
                    cl.grade,
                    s.subject_name,
                    g.grade_name,
                    DATEDIFF(cc.end_date, CURDATE()) as overdue,
                    (CASE 
                        WHEN cc.start_date > CURDATE() THEN '1'
                        WHEN cc.start_date <= CURDATE() AND cc.end_date >= CURDATE() THEN '2'
                        WHEN cc.end_date < CURDATE() THEN '3'
                        ELSE '2'
                    END) AS content_date_status
                FROM class_content cc
                INNER JOIN content c ON cc.content_id = c.content_id
                INNER JOIN class cl ON cc.class_id = cl.class_id
                LEFT JOIN subject s ON cl.subject = s.subject_id
                LEFT JOIN grade g ON cl.grade = g.grade_id
                LEFT JOIN student_class scs ON cc.class_id = scs.class_id AND scs.student_id = ?
                LEFT JOIN student_content sc ON sc.class_content_id = cc.id 
                    AND sc.student_id = ?
                    AND sc.draft_status = '1'
                WHERE scs.student_id = ?
                AND cc.status = 1
                AND c.content_type = 2
                AND (
                    -- Content assigned to all students in the class
                    -- For All filter: show all class-wide content (past, in-progress, future)
                    -- For other filters: only show in-progress and future
                    (cc.all_student = 1 AND ({$classWideDateCondition}))
                    OR
                    -- Content assigned to specific students - only show if student_content record exists
                    -- (No date restriction - students see all their assigned content)
                    (cc.all_student = 0 AND sc.id IS NOT NULL)
                )
            ";
            
            $query = $db->query($sql, [
                $params['student_id'],
                $params['student_id'],
                $params['student_id'],
                $params['student_id']
            ]);
            
            $assessments = $query->getResultArray();
            
            // Apply type filter if provided (for client-side filtering of already-fetched data)
            if (isset($params['type']) && $params['type'] != '' && $params['type'] != '1') {
                $filteredAssessments = [];
                foreach ($assessments as $item) {
                    $dateStatus = $item['content_date_status'];
                    // Type 2: Upcoming
                    if ($params['type'] == '2' && $dateStatus == '1') {
                        $filteredAssessments[] = $item;
                    }
                    // Type 3: In progress
                    elseif ($params['type'] == '3' && $dateStatus == '2') {
                        $filteredAssessments[] = $item;
                    }
                    // Type 4: Completed
                    elseif ($params['type'] == '4' && $dateStatus == '3') {
                        $filteredAssessments[] = $item;
                    }
                }
                $assessments = $filteredAssessments;
            }
            
            // Sort by end_date
            usort($assessments, function($a, $b) {
                return strtotime($a['content_end_date']) - strtotime($b['content_end_date']);
            });

            // Process file paths
            foreach ($assessments as &$item) {
                // Ensure student_content_id is always set (PHP 8+ requires explicit checks)
                // Use COALESCE to handle null values from database
                if (!array_key_exists('student_content_id', $item) || $item['student_content_id'] === null || $item['student_content_id'] === '') {
                    $item['student_content_id'] = 0;
                } else {
                    $item['student_content_id'] = (int)$item['student_content_id'];
                }
                
                // Ensure id field exists for backward compatibility
                if (!array_key_exists('id', $item)) {
                    $item['id'] = $item['student_content_id'];
                }
                
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

            // Get filter type (1 = All, 2 = Upcoming, 3 = In progress, 4 = Completed)
            $filterType = isset($params['type']) ? $params['type'] : '1';
            $isAllFilter = ($filterType == '1' || $filterType == 1);
            
            // Build date condition for class-wide content
            // For "All" filter: show all class-wide content (no date restriction)
            // For other filters: only show in-progress and future (end_date >= CURDATE())
            $classWideDateCondition = $isAllFilter ? '1=1' : 'cc.end_date >= CURDATE()';

            // Get student's assignments (content_type = 3 for assignments)
            // This query handles both class-wide and student-specific assignments
            $sql = "
                SELECT DISTINCT
                    COALESCE(sc.id, 0) as student_content_id,
                    COALESCE(sc.id, 0) as id,
                    COALESCE(sc.class_content_id, cc.id) as class_content_id,
                    cc.content_id,
                    COALESCE(sc.class_id, cc.class_id) as class_id,
                    ? as student_id,
                    COALESCE(sc.status, 1) as status,
                    COALESCE(sc.draft_status, '1') as draft_status,
                    COALESCE(sc.start_date, cc.start_date) as start_date,
                    COALESCE(sc.end_date, cc.end_date) as end_date,
                    COALESCE(sc.earned_points, 0) as earned_points,
                    COALESCE(sc.points, 0) as points,
                    COALESCE(sc.upload_answer, '') as upload_answer,
                    COALESCE(sc.student_feedback, '') as student_feedback,
                    sc.answer_completed_date,
                    sc.platform,
                    sc.redo_test,
                    c.name as content_name,
                    c.content_type,
                    c.content_format,
                    c.file_path,
                    cc.start_date as content_start_date,
                    cc.end_date as content_end_date,
                    cc.notes,
                    cc.downloadable,
                    cl.class_name,
                    cl.subject,
                    cl.grade,
                    s.subject_name,
                    g.grade_name,
                    DATEDIFF(cc.end_date, CURDATE()) as overdue,
                    (CASE 
                        WHEN cc.start_date > CURDATE() THEN '1'
                        WHEN cc.start_date <= CURDATE() AND cc.end_date >= CURDATE() THEN '2'
                        WHEN cc.end_date < CURDATE() THEN '3'
                        ELSE '2'
                    END) AS content_date_status
                FROM class_content cc
                INNER JOIN content c ON cc.content_id = c.content_id
                INNER JOIN class cl ON cc.class_id = cl.class_id
                LEFT JOIN subject s ON cl.subject = s.subject_id
                LEFT JOIN grade g ON cl.grade = g.grade_id
                LEFT JOIN student_class scs ON cc.class_id = scs.class_id AND scs.student_id = ?
                LEFT JOIN student_content sc ON sc.class_content_id = cc.id 
                    AND sc.student_id = ?
                    AND sc.draft_status = '1'
                WHERE scs.student_id = ?
                AND cc.status = 1
                AND c.content_type = 3
                AND (
                    -- Content assigned to all students in the class
                    -- For All filter: show all class-wide content (past, in-progress, future)
                    -- For other filters: only show in-progress and future
                    (cc.all_student = 1 AND ({$classWideDateCondition}))
                    OR
                    -- Content assigned to specific students - only show if student_content record exists
                    -- (No date restriction - students see all their assigned content)
                    (cc.all_student = 0 AND sc.id IS NOT NULL)
                )
            ";
            
            $query = $db->query($sql, [
                $params['student_id'],
                $params['student_id'],
                $params['student_id'],
                $params['student_id']
            ]);
            
            $assignments = $query->getResultArray();
            
            // Apply type filter if provided (for client-side filtering of already-fetched data)
            // Skip filtering when type = 1 (All) since we already fetched all data
            if (isset($params['type']) && $params['type'] != '' && $params['type'] != '1') {
                $filteredAssignments = [];
                foreach ($assignments as $item) {
                    $dateStatus = $item['content_date_status'];
                    // Type 2: Upcoming
                    if ($params['type'] == '2' && $dateStatus == '1') {
                        $filteredAssignments[] = $item;
                    }
                    // Type 3: In progress
                    elseif ($params['type'] == '3' && $dateStatus == '2') {
                        $filteredAssignments[] = $item;
                    }
                    // Type 4: Completed
                    elseif ($params['type'] == '4' && $dateStatus == '3') {
                        $filteredAssignments[] = $item;
                    }
                }
                $assignments = $filteredAssignments;
            }
            
            // Sort by end_date
            usort($assignments, function($a, $b) {
                return strtotime($a['content_end_date']) - strtotime($b['content_end_date']);
            });

            // Process file paths
            foreach ($assignments as &$item) {
                // Ensure student_content_id is always set (PHP 8+ requires explicit checks)
                // Use COALESCE to handle null values from database
                if (!array_key_exists('student_content_id', $item) || $item['student_content_id'] === null || $item['student_content_id'] === '') {
                    $item['student_content_id'] = 0;
                } else {
                    $item['student_content_id'] = (int)$item['student_content_id'];
                }
                
                // Ensure id field exists for backward compatibility
                if (!array_key_exists('id', $item)) {
                    $item['id'] = $item['student_content_id'];
                }
                
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

            // Get filter type (1 = All, 2 = Upcoming, 3 = In progress, 4 = Completed)
            $filterType = isset($params['type']) ? $params['type'] : '1';
            $isAllFilter = ($filterType == '1' || $filterType == 1);
            
            // Build date condition for class-wide content
            // For "All" filter: show all class-wide content (no date restriction)
            // For other filters: only show in-progress and future (end_date >= CURDATE())
            $classWideDateCondition = $isAllFilter ? '1=1' : 'cc.end_date >= CURDATE()';

            // Log input parameters
            log_message('debug', '📋 [RESOURCES LIST] Input parameters: ' . json_encode($params));
            log_message('debug', '📋 [RESOURCES LIST] Filter type: ' . $filterType . ', isAllFilter: ' . ($isAllFilter ? 'true' : 'false'));
            log_message('debug', '📋 [RESOURCES LIST] Class-wide date condition: ' . $classWideDateCondition);

            // Get student's resources (content_type = 1 for resources)
            // This query handles both class-wide and student-specific resources
            $sql = "
                SELECT DISTINCT
                    COALESCE(sc.id, 0) as student_content_id,
                    COALESCE(sc.class_content_id, cc.id) as class_content_id,
                    cc.content_id,
                    cc.class_id,
                    cc.all_student,
                    c.name as content_name,
                    c.content_type,
                    c.content_format,
                    c.file_path,
                    c.links,
                    COALESCE(sc.annotation, c.annotation, '') as annotation,
                    cc.start_date,
                    cc.end_date,
                    cc.notes,
                    cc.downloadable,
                    cl.class_name,
                    cl.subject,
                    cl.grade,
                    s.subject_name,
                    g.grade_name,
                    sc.student_id as student_content_student_id,
                    scs.student_id as student_class_student_id,
                    DATEDIFF(cc.end_date, CURDATE()) as overdue,
                    (CASE 
                        WHEN cc.start_date > CURDATE() THEN '1'
                        WHEN cc.start_date <= CURDATE() AND cc.end_date >= CURDATE() THEN '2'
                        WHEN cc.end_date < CURDATE() THEN '3'
                        ELSE '2'
                    END) AS content_date_status
                FROM class_content cc
                INNER JOIN content c ON cc.content_id = c.content_id
                INNER JOIN class cl ON cc.class_id = cl.class_id
                LEFT JOIN subject s ON cl.subject = s.subject_id
                LEFT JOIN grade g ON cl.grade = g.grade_id
                LEFT JOIN student_class scs ON cc.class_id = scs.class_id AND scs.student_id = ?
                LEFT JOIN student_content sc ON sc.class_content_id = cc.id 
                    AND sc.student_id = ?
                WHERE scs.student_id IS NOT NULL
                AND cc.status = 1
                AND c.content_type = 1
                AND (
                    -- Content assigned to all students in the class
                    -- Show if date condition is met
                    (cc.all_student = 1 AND ({$classWideDateCondition}))
                    OR
                    -- Content assigned to specific students
                    -- Must match student_content.student_id to the logged-in student
                    (cc.all_student = 0 AND sc.student_id = ? AND sc.id IS NOT NULL)
                )
            ";
            
            // Apply grade filter if provided
            if (isset($params['grade_id']) && !empty($params['grade_id'])) {
                $sql .= " AND cl.grade = " . (int)$params['grade_id'];
            }
            
            $sql .= " ORDER BY cc.end_date ASC";
            
            // Log the SQL query with placeholders
            log_message('debug', '📋 [RESOURCES LIST] SQL Query: ' . $sql);
            
            $queryParams = [
                $params['user_id'],  // For student_class JOIN
                $params['user_id'],  // For student_content JOIN (student_id filter)
                $params['user_id']   // For student-specific content WHERE clause (explicit match)
            ];
            
            log_message('debug', '📋 [RESOURCES LIST] Query Parameters: ' . json_encode($queryParams));
            
            $query = $db->query($sql, $queryParams);
            
            // Log the compiled query
            $compiledQuery = $db->query($sql, $queryParams);
            log_message('debug', '📋 [RESOURCES LIST] Executed query with user_id: ' . $params['user_id']);
            
            $resources = $query->getResultArray();
            
            // Log results
            log_message('debug', '📋 [RESOURCES LIST] Total resources found: ' . count($resources));
            
            // Log details about each resource
            foreach ($resources as $index => $resource) {
                log_message('debug', "📋 [RESOURCES LIST] Resource #{$index}: " . json_encode([
                    'content_id' => $resource['content_id'] ?? 'N/A',
                    'content_name' => $resource['content_name'] ?? 'N/A',
                    'class_id' => $resource['class_id'] ?? 'N/A',
                    'all_student' => $resource['all_student'] ?? 'N/A',
                    'student_content_id' => $resource['student_content_id'] ?? 'N/A',
                    'student_content_student_id' => $resource['student_content_student_id'] ?? 'N/A',
                    'student_class_student_id' => $resource['student_class_student_id'] ?? 'N/A',
                    'class_name' => $resource['class_name'] ?? 'N/A'
                ]));
            }
            
            // Also check what student_content records exist for this student
            $studentContentCheck = $db->table('student_content sc')
                ->select('sc.id, sc.student_id, sc.content_id, sc.class_content_id, sc.class_id, sc.draft_status, cc.all_student, c.content_type, c.name as content_name')
                ->join('class_content cc', 'sc.class_content_id = cc.id', 'left')
                ->join('content c', 'cc.content_id = c.content_id', 'left')
                ->where('sc.student_id', $params['user_id'])
                ->where('c.content_type', 1)
                ->get()
                ->getResultArray();
            
            log_message('debug', '📋 [RESOURCES LIST] Student-specific student_content records found: ' . count($studentContentCheck));
            foreach ($studentContentCheck as $index => $scRecord) {
                log_message('debug', "📋 [RESOURCES LIST] Student Content Record #{$index}: " . json_encode([
                    'id' => $scRecord['id'] ?? 'N/A',
                    'student_id' => $scRecord['student_id'] ?? 'N/A',
                    'content_id' => $scRecord['content_id'] ?? 'N/A',
                    'class_content_id' => $scRecord['class_content_id'] ?? 'N/A',
                    'class_id' => $scRecord['class_id'] ?? 'N/A',
                    'draft_status' => $scRecord['draft_status'] ?? 'N/A',
                    'all_student' => $scRecord['all_student'] ?? 'N/A',
                    'content_name' => $scRecord['content_name'] ?? 'N/A'
                ]));
            }
            
            // Check class_content records for this student's classes
            $studentClasses = $db->table('student_class')
                ->where('student_id', $params['user_id'])
                ->where('status', 1)
                ->get()
                ->getResultArray();
            
            $classIds = array_column($studentClasses, 'class_id');
            log_message('debug', '📋 [RESOURCES LIST] Student is in classes: ' . json_encode($classIds));
            
            if (!empty($classIds)) {
                $classContentCheck = $db->table('class_content cc')
                    ->select('cc.id, cc.content_id, cc.class_id, cc.all_student, cc.status, c.content_type, c.name as content_name')
                    ->join('content c', 'cc.content_id = c.content_id', 'left')
                    ->whereIn('cc.class_id', $classIds)
                    ->where('cc.status', 1)
                    ->where('c.content_type', 1)
                    ->get()
                    ->getResultArray();
                
                log_message('debug', '📋 [RESOURCES LIST] Class content records in student classes: ' . count($classContentCheck));
                foreach ($classContentCheck as $index => $ccRecord) {
                    log_message('debug', "📋 [RESOURCES LIST] Class Content Record #{$index}: " . json_encode([
                        'id' => $ccRecord['id'] ?? 'N/A',
                        'content_id' => $ccRecord['content_id'] ?? 'N/A',
                        'class_id' => $ccRecord['class_id'] ?? 'N/A',
                        'all_student' => $ccRecord['all_student'] ?? 'N/A',
                        'status' => $ccRecord['status'] ?? 'N/A',
                        'content_name' => $ccRecord['content_name'] ?? 'N/A'
                    ]));
                }
            }
            
            // Apply type filter if provided (for client-side filtering of already-fetched data)
            // Skip filtering when type = 1 (All) since we already fetched all data
            if (isset($params['type']) && $params['type'] != '' && $params['type'] != '1') {
                $today = date('Y-m-d');
                $resources = array_filter($resources, function($item) use ($params, $today) {
                    if ($params['type'] == '2') { // Upcoming
                        return $item['start_date'] > $today;
                    } elseif ($params['type'] == '3') { // In progress
                        return $item['start_date'] <= $today && $item['end_date'] >= $today;
                    } elseif ($params['type'] == '4') { // Completed
                        return $item['end_date'] < $today;
                    }
                    return true;
                });
                $resources = array_values($resources); // Re-index array
            }

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

    /**
     * Get class recordings for a student
     * CI4-compatible wrapper for ClassRecording endpoint
     */
    public function classRecording(): ResponseInterface
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
                    'ErrorObject' => 'Role Id should not be empty'
                ]);
            }

            if (empty($params['user_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => [],
                    'ErrorObject' => 'User Id should not be empty'
                ]);
            }

            if (empty($params['class_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => [],
                    'ErrorObject' => 'Class Id should not be empty'
                ]);
            }

            if (empty($params['school_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => [],
                    'ErrorObject' => 'School Id should not be empty'
                ]);
            }

            // For now, return empty array - the full implementation would require
            // porting the CI3 logic with zoom_model and student_model
            // This prevents 404 errors while the full implementation is being developed
            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => [],
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Student classRecording error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => [],
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check content time availability for student
     * Validates if content is accessible based on start/end dates and times
     */
    public function checkContentTime(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            // Validate required fields
            if (empty($params['platform'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Platform Should not be Empty'
                ], 400);
            }
            
            if (empty($params['user_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'User Id Should not be Empty'
                ], 400);
            }
            
            if (empty($params['role_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Role Id Should not be Empty'
                ], 400);
            }
            
            if (empty($params['content_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Content Id Should not be Empty'
                ], 400);
            }
            
            if (empty($params['class_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Class Id Should not be Empty'
                ], 400);
            }
            
            // Get content time information
            $checkTime = $this->studentModel->checkContentTime($params);
            
            if (empty($checkTime)) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Content not found'
                ], 404);
            }
            
            $message = '';
            $status = '';
            $time = 0;
            
            // Process based on content type
            // content_type 3 = Assessment, 2 = Assignment
            if ($checkTime['content_type'] == 3) {
                // Assessment with date/time restrictions
                if ($checkTime['start_date'] != '0000-00-00' && $checkTime['end_date'] != '0000-00-00') {
                    if ($checkTime['start_date'] <= date('Y-m-d') && $checkTime['end_date'] >= date('Y-m-d')) {
                        if ($checkTime['start_time'] != '00:00:00' && $checkTime['end_time'] != '00:00:00') {
                            if ($checkTime['start_time'] <= date('H:i:s') && $checkTime['end_time'] >= date('H:i:s')) {
                                $status = 1;
                                $time = (strtotime($checkTime['end_time']) - strtotime($checkTime['start_time'])) / 60;
                                $message = $checkTime['name'] . " started at " . date('m-d-Y', strtotime($checkTime['start_date']));
                            } elseif ($checkTime['start_time'] > date('H:i:s')) {
                                $message = $checkTime['name'] . " starts only by " . date('m-d-Y', strtotime($checkTime['start_date'])) . " " . date('h:i A', strtotime($checkTime['start_time']));
                                $status = 0;
                            } elseif ($checkTime['end_time'] < date('H:i:s')) {
                                $message = $checkTime['name'] . " ended at " . date('m-d-Y', strtotime($checkTime['start_date'])) . " " . date('h:i A', strtotime($checkTime['end_time']));
                                $status = 0;
                            }
                        } elseif ($checkTime['start_time'] != '00:00:00') {
                            if ($checkTime['start_time'] <= date('H:i:s')) {
                                $status = 1;
                                $message = $checkTime['name'] . " started at " . date('m-d-Y', strtotime($checkTime['start_date']));
                            } else {
                                $message = $checkTime['name'] . " starts only by " . date('m-d-Y', strtotime($checkTime['start_date'])) . " " . date('h:i A', strtotime($checkTime['start_time']));
                                $status = 0;
                            }
                        } elseif ($checkTime['start_time'] == '00:00:00' && $checkTime['end_time'] != '00:00:00') {
                            if ($checkTime['end_time'] <= date('H:i:s')) {
                                $message = $checkTime['name'] . " ended at " . date('m-d-Y', strtotime($checkTime['end_date'])) . " " . date('h:i A', strtotime($checkTime['end_time']));
                                $status = 0;
                            } else {
                                $message = $checkTime['name'] . " Started at " . date('m-d-Y', strtotime($checkTime['start_date']));
                                $status = 1;
                            }
                        } else {
                            $message = $checkTime['name'] . " Started at " . date('m-d-Y', strtotime($checkTime['start_date']));
                            $status = 1;
                        }
                    } else {
                        $message = $checkTime['name'] . " ended at " . date('m-d-Y', strtotime($checkTime['end_date']));
                        $status = 0;
                    }
                } elseif ($checkTime['start_date'] != '0000-00-00' && $checkTime['end_date'] == '0000-00-00') {
                    if ($checkTime['start_date'] == date('Y-m-d')) {
                        if ($checkTime['start_time'] != '00:00:00' && $checkTime['end_time'] != '00:00:00') {
                            if ($checkTime['start_time'] <= date('H:i:s') && $checkTime['end_time'] >= date('H:i:s')) {
                                $status = 1;
                                $time = (strtotime($checkTime['end_time']) - strtotime($checkTime['start_time'])) / 60;
                                $message = $checkTime['name'] . " started at " . date('m-d-Y', strtotime($checkTime['start_date']));
                            } elseif ($checkTime['start_time'] > date('H:i:s')) {
                                $message = $checkTime['name'] . " starts only by " . date('m-d-Y', strtotime($checkTime['start_date'])) . " " . date('h:i A', strtotime($checkTime['start_time']));
                                $status = 0;
                            }
                        } elseif ($checkTime['start_time'] != '00:00:00' && $checkTime['end_time'] == '00:00:00') {
                            if ($checkTime['start_time'] <= date('H:i:s')) {
                                $status = 1;
                                $message = $checkTime['name'] . " started at" . date('m-d-Y', strtotime($checkTime['start_date']));
                            } elseif ($checkTime['start_time'] > date('H:i:s')) {
                                $message = $checkTime['name'] . " starts only by " . date('m-d-Y', strtotime($checkTime['start_date'])) . " " . date('h:i A', strtotime($checkTime['start_time']));
                                $status = 0;
                            } else {
                                $message = $checkTime['name'] . " Started at " . date('m-d-Y', strtotime($checkTime['start_date']));
                                $status = 1;
                            }
                        } else {
                            $message = $checkTime['name'] . " Started at " . date('m-d-Y', strtotime($checkTime['start_date']));
                            $status = 1;
                        }
                    } elseif ($checkTime['start_date'] < date('Y-m-d')) {
                        $message = $checkTime['name'] . " Started at " . date('m-d-Y', strtotime($checkTime['start_date']));
                        $status = 1;
                    } else {
                        $message = $checkTime['name'] . " ended at " . date('h:i A', strtotime($checkTime['end_time']));
                        $status = 0;
                    }
                } else {
                    $message = $checkTime['name'] . " Started at " . date('m-d-Y', strtotime($checkTime['start_date']));
                    $status = 1;
                }
            } elseif ($checkTime['content_type'] == 2) {
                // Assignment
                if ($checkTime['start_date'] != '0000-00-00') {
                    if ($checkTime['start_date'] <= date('Y-m-d')) {
                        $status = 1;
                        $message = $checkTime['name'] . " started at " . date('m-d-Y', strtotime($checkTime['start_date']));
                    } else {
                        $message = $checkTime['name'] . " starts only by " . date('m-d-Y', strtotime($checkTime['start_date'])) . " " . date('h:i A', strtotime($checkTime['start_time']));
                        $status = 1;
                    }
                }
            }
            
            if ($status) {
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => $message,
                    'Time' => $time
                ]);
            } else {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => $message,
                    'Time' => 0
                ]);
            }
            
        } catch (\Exception $e) {
            log_message('error', 'Student checkContentTime error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save student annotation
     * Saves annotation data to a JSON file and updates student_content table
     */
    public function saveAnnotation(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            log_message('debug', '🟢 saveAnnotation called with params: ' . json_encode([
                'platform' => $params['platform'] ?? 'NOT SET',
                'student_id' => $params['student_id'] ?? 'NOT SET',
                'content_id' => $params['content_id'] ?? 'NOT SET',
                'class_id' => $params['class_id'] ?? 'NOT SET',
                'student_content_id' => $params['student_content_id'] ?? 'NOT SET',
                'annotation_count' => is_array($params['annotation'] ?? null) ? count($params['annotation']) : 'NOT ARRAY'
            ]));
            
            // Validation
            if (!isset($params['platform']) || !in_array($params['platform'], ['web', 'ios'])) {
                log_message('error', '❌ saveAnnotation: Platform validation failed');
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Platform should not be empty'
                ], 400);
            }
            
            if (empty($params['annotation'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Annotation should not be empty'
                ], 400);
            }
            
            if (empty($params['student_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Student Id should not be empty'
                ], 400);
            }
            
            if (empty($params['content_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Content Id should not be empty'
                ], 400);
            }
            
            if (empty($params['student_content_id'])) {
                log_message('error', '❌ saveAnnotation: student_content_id is missing');
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Student Content Id should not be empty'
                ], 400);
            }
            
            if (empty($params['class_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Class Id should not be empty'
                ], 400);
            }
            
            // Create annotation folder if it doesn't exist
            // Use FCPATH (public folder) or ROOTPATH depending on your structure
            // For MAMP, uploads should be accessible via web, so use FCPATH
            $folder = FCPATH . 'uploads/studentAnnotation/';
            if (!is_dir($folder)) {
                mkdir($folder, 0755, true);
            }
            
            // Generate filename
            $fileName = "student-annotation" . $params['student_content_id'] . $params['class_id'] . $params['content_id'] . '.json';
            $filePath = $folder . $fileName;
            
            // Save annotation to JSON file
            $annotationJson = json_encode($params['annotation'], JSON_PRETTY_PRINT);
            log_message('debug', '🟢 saveAnnotation: Saving to file: ' . $filePath);
            
            if (file_put_contents($filePath, $annotationJson) === false) {
                log_message('error', '❌ saveAnnotation: Failed to write file: ' . $filePath);
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Failed to save annotation file'
                ], 500);
            }
            
            log_message('debug', '🟢 saveAnnotation: File saved successfully, size: ' . filesize($filePath) . ' bytes');
            
            // Update student_content table
            $db = \Config\Database::connect();
            $path = "uploads/studentAnnotation/" . $fileName;
            
            $builder = $db->table('student_content');
            $update = $builder->where('id', $params['student_content_id'])
                             ->update(['annotation' => $path]);
            
            if ($update) {
                log_message('debug', '🟢 saveAnnotation: Database updated successfully for student_content_id: ' . $params['student_content_id']);
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => 'Annotation updated',
                    'ErrorObject' => ''
                ]);
            } else {
                log_message('error', '❌ saveAnnotation: Database update failed for student_content_id: ' . $params['student_content_id']);
                // Check if record exists
                $exists = $builder->where('id', $params['student_content_id'])->countAllResults();
                log_message('debug', '🔍 saveAnnotation: Record exists check: ' . ($exists > 0 ? 'YES' : 'NO'));
                
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Annotation Not Updated - Record may not exist or no changes detected'
                ], 500);
            }
            
        } catch (\Exception $e) {
            log_message('error', 'Student saveAnnotation error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update content start time for a student
     * Creates student_content record if it doesn't exist (for class-wide content)
     */
    /**
     * Get courses for the logged-in student
     */
    public function myCourses(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            // Get student ID from session/token (logged-in student)
            $userId = $params['user_id'] ?? null;
            
            if (empty($userId)) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'User ID required'
                ]);
            }

            $schoolId = $params['school_id'] ?? null;
            if (empty($schoolId)) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'School ID required'
                ]);
            }

            $db = \Config\Database::connect();
            
            // Get courses for the student
            $builder = $db->table('student_courses sc');
            $builder->select('sc.*, tbl_course.course_name, tbl_course.description as course_description,
                             fee_plans.name as fee_plan_name');
            $builder->join('tbl_course', 'tbl_course.course_id = sc.course_id', 'left');
            $builder->join('student_fee_plans sfp', 'sfp.id = sc.student_fee_plan_id', 'left');
            $builder->join('fee_plans', 'fee_plans.id = sfp.fee_plan_id', 'left');
            $builder->where('sc.student_id', $userId);
            $builder->where('sc.school_id', $schoolId);
            
            if (!empty($params['status'])) {
                $builder->where('sc.status', $params['status']);
            }
            
            $builder->orderBy('sc.enrollment_date', 'DESC');
            
            $courses = $builder->get()->getResultArray();

            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => [
                    'student_id' => (int)$userId,
                    'courses' => $courses ?? [],
                    'total' => count($courses ?? [])
                ],
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Student::myCourses - ' . $e->getMessage());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => 'Unable to load courses: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get completed content folder content for a student, organized by test type
     * Returns content grouped by test type (content, sat, act)
     */
    public function completedCfsContent(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            // Get student ID - use student_id if provided and not empty, otherwise use user_id
            $studentId = (!empty($params['student_id'])) ? $params['student_id'] : ($params['user_id'] ?? null);
            $schoolId = $params['school_id'] ?? null;
            $classId = $params['class_id'] ?? null;

            if (empty($studentId)) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Student ID or User ID is required'
                ], 400);
            }

            $db = \Config\Database::connect();

            // Build query to get completed content with test type information
            // Note: test_type_id comes directly from content table
            // batch relationship is through classroom_content table, not directly on content
            $sql = "
                SELECT DISTINCT
                    c.content_id,
                    c.name as content_name,
                    c.content_type,
                    c.content_format,
                    c.file_path,
                    COALESCE(c.test_type_id, 1) as test_type_id,
                    (CASE 
                        WHEN c.test_type_id = 1 THEN 'content'
                        WHEN c.test_type_id = 2 THEN 'sat'
                        WHEN c.test_type_id = 3 THEN 'act'
                        ELSE 'content'
                    END) as test_type_key,
                    (CASE 
                        WHEN c.test_type_id = 1 THEN 'Content'
                        WHEN c.test_type_id = 2 THEN 'SAT'
                        WHEN c.test_type_id = 3 THEN 'ACT'
                        ELSE 'Content'
                    END) as test_type,
                    sc.id as student_content_id,
                    sc.status as student_content_status,
                    sc.answer_completed_date,
                    sc.earned_points,
                    sc.points,
                    cc.id as class_content_id,
                    cc.class_id,
                    cl.class_name,
                    COALESCE(b.batch_id, 0) as batch_id,
                    COALESCE(b.batch_name, '') as batch_name,
                    sc.student_id,
                    COALESCE(up.first_name, '') as first_name,
                    COALESCE(up.last_name, '') as last_name,
                    CONCAT(COALESCE(up.first_name, ''), ' ', COALESCE(up.last_name, '')) as student_name
                FROM student_content sc
                INNER JOIN class_content cc ON sc.class_content_id = cc.id
                INNER JOIN content c ON sc.content_id = c.content_id
                INNER JOIN class cl ON sc.class_id = cl.class_id
                LEFT JOIN classroom_content clc ON c.content_id = clc.content_id AND clc.status = 1
                LEFT JOIN batch b ON clc.batch_id = b.batch_id
                LEFT JOIN user_profile up ON sc.student_id = up.user_id
                WHERE sc.student_id = ?
                AND sc.status IN (3, 4, 5)
                AND sc.draft_status = '1'
            ";

            $queryParams = [$studentId];

            if (!empty($schoolId)) {
                $sql .= " AND cl.school_id = ?";
                $queryParams[] = $schoolId;
            }

            if (!empty($classId)) {
                $sql .= " AND sc.class_id = ?";
                $queryParams[] = $classId;
            }

            $sql .= " ORDER BY sc.answer_completed_date DESC, c.name ASC";

            $results = $db->query($sql, $queryParams)->getResultArray();

            // Organize results by test type
            $organizedContent = [
                'content' => [],
                'sat' => [],
                'act' => []
            ];

            // Group content by content_id, test_type, and student_id
            // Multiple student_content records can exist for same content_id + student_id
            $contentGroups = [];
            foreach ($results as $row) {
                $testTypeKey = $row['test_type_key'] ?? 'content';
                $contentId = $row['content_id'];
                $studentId = $row['student_id'];
                
                // Initialize content group if not exists
                if (!isset($contentGroups[$testTypeKey][$contentId])) {
                    $contentGroups[$testTypeKey][$contentId] = [
                        'content_id' => $contentId,
                        'content_name' => $row['content_name'],
                        'content_type' => $row['content_type'],
                        'content_format' => $row['content_format'],
                        'file_path' => $row['file_path'],
                        'class_content_id' => $row['class_content_id'],
                        'class_id' => $row['class_id'],
                        'class_name' => $row['class_name'],
                        'batch_id' => $row['batch_id'],
                        'batch_name' => $row['batch_name'],
                        'test_type_id' => $row['test_type_id'],
                        'test_type' => $row['test_type'],
                        'student_details' => []
                    ];
                }

                // Check if student detail already exists for this content
                $studentDetailKey = null;
                foreach ($contentGroups[$testTypeKey][$contentId]['student_details'] as $key => $detail) {
                    if ($detail['student_id'] == $studentId) {
                        $studentDetailKey = $key;
                        break;
                    }
                }

                if ($studentDetailKey !== null) {
                    // Add student_content_id to existing student detail
                    if (!in_array($row['student_content_id'], $contentGroups[$testTypeKey][$contentId]['student_details'][$studentDetailKey]['student_content_id'])) {
                        $contentGroups[$testTypeKey][$contentId]['student_details'][$studentDetailKey]['student_content_id'][] = $row['student_content_id'];
                    }
                } else {
                    // Create new student detail
                    $contentGroups[$testTypeKey][$contentId]['student_details'][] = [
                        'student_id' => $studentId,
                        'student_name' => trim($row['student_name']),
                        'student_content_id' => [$row['student_content_id']],
                        'status' => $row['student_content_status'],
                        'answer_completed_date' => $row['answer_completed_date'],
                        'earned_points' => $row['earned_points'],
                        'points' => $row['points']
                    ];
                }
            }

            // Convert to array format expected by frontend
            // Ensure all test type keys exist (even if empty) to prevent frontend errors
            foreach ($contentGroups as $testTypeKey => $contents) {
                $organizedContent[$testTypeKey] = array_values($contents);
            }
            
            // Ensure all expected keys exist with empty arrays if missing
            // This prevents "Cannot read properties of undefined" errors in frontend
            $expectedKeys = ['content', 'sat', 'act', 'other'];
            foreach ($expectedKeys as $key) {
                if (!isset($organizedContent[$key])) {
                    $organizedContent[$key] = [];
                }
            }

            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $organizedContent,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Student::completedCfsContent - ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => [
                    'content' => [],
                    'sat' => [],
                    'act' => []
                ],
                'ErrorObject' => 'Unable to load completed content: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get CFS (Content Folder) report for student performance
     * Returns detailed report with question topics, subtopics, and student answers
     */
    public function cfsReport(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            // Get parameters
            $studentId = (!empty($params['student_id'])) ? $params['student_id'] : ($params['user_id'] ?? null);
            $schoolId = $params['school_id'] ?? null;
            $classId = $params['class_id'] ?? null;
            $content = $params['content'] ?? []; // Array of content IDs
            $testTypeId = $params['test_type_id'] ?? '1';

            if (empty($studentId)) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => [],
                    'ErrorObject' => 'Student ID or User ID is required'
                ], 400);
            }

            if (empty($content) || !is_array($content) || count($content) === 0) {
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => [],
                    'ErrorObject' => ''
                ]);
            }

            $db = \Config\Database::connect();
            $results = [];

            // Get student name
            $studentNameQuery = $db->table('user_profile up')
                ->select("CONCAT(COALESCE(up.first_name, ''), ' ', COALESCE(up.last_name, '')) as student_name")
                ->where('up.user_id', $studentId)
                ->get()
                ->getRowArray();
            
            $studentName = trim($studentNameQuery['student_name'] ?? '') ?: 'Unknown Student';

            // Process each content item
            foreach ($content as $contentItem) {
                $contentId = is_array($contentItem) ? ($contentItem['content_id'] ?? null) : $contentItem;
                if (empty($contentId)) {
                    continue;
                }

                // Get content details
                $contentQuery = $db->table('content c')
                    ->select('c.content_id, c.name as content_name, c.content_type, c.content_format, c.test_type_id')
                    ->where('c.content_id', $contentId)
                    ->get()
                    ->getRowArray();

                if (empty($contentQuery)) {
                    continue;
                }

                // Get student_content_id(s) for this content
                $studentContentQuery = $db->table('student_content sc')
                    ->select('sc.id as student_content_id')
                    ->where('sc.student_id', $studentId)
                    ->where('sc.content_id', $contentId)
                    ->where('sc.status', '>=', 3) // Completed status
                    ->where('sc.draft_status', '1')
                    ->get()
                    ->getResultArray();

                if (empty($studentContentQuery)) {
                    continue;
                }

                // Use the first student_content_id (or could aggregate multiple attempts)
                $studentContentId = $studentContentQuery[0]['student_content_id'];

                // Get questions with topics and subtopics
                $questionsQuery = "
                    SELECT DISTINCT
                        q.question_id,
                        q.question_no,
                        COALESCE(q.question, '') as question,
                        COALESCE(qt.question_topic_id, 0) as question_topic_id,
                        COALESCE(qt.question_topic, '') as question_topic,
                        COALESCE(st.sub_topic_id, 0) as sub_topic_id,
                        COALESCE(st.sub_topic, '') as sub_topic,
                        COALESCE(sa.student_answer, '') as student_answer,
                        COALESCE(sa.answer_status, 0) as answer_status,
                        COALESCE(sa.earned_points, 0) as earned_points,
                        COALESCE(q.points, 0) as points,
                        COALESCE(q.answer, '') as correct_answer
                    FROM answers q
                    LEFT JOIN question_topic qt ON q.question_topic_id = qt.question_topic_id
                    LEFT JOIN sub_topic st ON q.sub_topic_id = st.sub_topic_id
                    LEFT JOIN student_answers sa ON q.answer_id = sa.answer_id 
                        AND sa.student_content_id = ?
                    WHERE q.content_id = ?
                    AND q.status = 1
                    ORDER BY q.display_order, q.question_no
                ";

                $questions = $db->query($questionsQuery, [$studentContentId, $contentId])->getResultArray();

                // Organize by topics and subtopics
                $topics = [];
                $subTopics = [];

                foreach ($questions as $q) {
                    $topicId = $q['question_topic_id'] ?? 0;
                    $subTopicId = $q['sub_topic_id'] ?? 0;
                    $topicName = $q['question_topic'] ?? 'Uncategorized';
                    $subTopicName = $q['sub_topic'] ?? 'Uncategorized';

                    // Group by topic
                    if (!isset($topics[$topicId])) {
                        $topics[$topicId] = [
                            'question_topic_id' => $topicId,
                            'question_topic' => $topicName,
                            'question_data' => []
                        ];
                    }

                    // Group by subtopic
                    if (!isset($subTopics[$subTopicId])) {
                        $subTopics[$subTopicId] = [
                            'sub_topic_id' => $subTopicId,
                            'sub_topic' => $subTopicName,
                            'question_data' => []
                        ];
                    }

                    // Add question data
                    $questionData = [
                        'question_id' => $q['question_id'],
                        'question_no' => $q['question_no'],
                        'question' => $q['question'],
                        'student_answer' => $q['student_answer'],
                        'correct_answer' => $q['correct_answer'],
                        'answer_status' => $q['answer_status'],
                        'earned_points' => $q['earned_points'],
                        'points' => $q['points']
                    ];

                    $topics[$topicId]['question_data'][] = $questionData;
                    $subTopics[$subTopicId]['question_data'][] = $questionData;
                }

                // Build response structure
                $contentData = [
                    'student_id' => (int)$studentId,
                    'student_name' => $studentName,
                    'student_content_id' => (int)$studentContentId,
                    'content_id' => (int)$contentId,
                    'content_name' => $contentQuery['content_name'],
                    'question_topics' => array_values($topics),
                    'question_sub_topics' => array_values($subTopics)
                ];

                // For SAT/ACT, add subject/module structure (simplified for now)
                if ($testTypeId != '1' && $testTypeId != 1) {
                    $contentData['subject'] = [
                        [
                            'subject_name' => 'Math', // Default - should be determined from content
                            'modules' => [
                                [
                                    'module_id' => 1,
                                    'module_name' => 'Module 1',
                                    'question_topics' => array_values($topics),
                                    'question_sub_topics' => array_values($subTopics)
                                ]
                            ]
                        ]
                    ];
                }

                $results[] = $contentData;
            }

            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $results,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Student::cfsReport - ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => [],
                'ErrorObject' => 'Unable to load report: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateContentStartTime(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            // Validation
            if (empty($params['user_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'User ID should not be empty'
                ], 400);
            }

            $db = \Config\Database::connect();
            $studentId = (int)$params['user_id'];
            
            // Handle case where student_content_id is 0 or missing (class-wide content)
            $studentContentId = isset($params['student_content_id']) ? (int)$params['student_content_id'] : 0;
            
            // If student_content_id is 0 or missing, try to find or create it
            if ($studentContentId == 0 || empty($params['student_content_id'])) {
                // Need additional info to create/find student_content
                if (empty($params['content_id']) || empty($params['class_id']) || empty($params['class_content_id'])) {
                    return $this->respond([
                        'IsSuccess' => false,
                        'ResponseObject' => null,
                        'ErrorObject' => 'Content ID, Class ID, and Class Content ID are required when student_content_id is missing'
                    ], 400);
                }
                
                $contentId = (int)$params['content_id'];
                $classId = (int)$params['class_id'];
                $classContentId = (int)$params['class_content_id'];
                
                // Try to find existing student_content record
                $existingStudentContent = $db->table('student_content')
                    ->where('student_id', $studentId)
                    ->where('content_id', $contentId)
                    ->where('class_content_id', $classContentId)
                    ->where('class_id', $classId)
                    ->get()
                    ->getRowArray();
                
                if ($existingStudentContent) {
                    $studentContentId = (int)$existingStudentContent['id'];
                } else {
                    // Create new student_content record for class-wide content
                    $classContent = $db->table('class_content')
                        ->where('id', $classContentId)
                        ->get()
                        ->getRowArray();
                    
                    if (!$classContent) {
                        return $this->respond([
                            'IsSuccess' => false,
                            'ResponseObject' => null,
                            'ErrorObject' => 'Class content not found'
                        ], 404);
                    }
                    
                    // Get student's grade
                    $studentGrade = $db->table('student_class sc')
                        ->select('c.grade as grade_id')
                        ->join('class c', 'sc.class_id = c.class_id', 'left')
                        ->where('sc.student_id', $studentId)
                        ->where('sc.class_id', $classId)
                        ->get()
                        ->getRowArray();
                    
                    $studentContentData = [
                        'student_id' => $studentId,
                        'content_id' => $contentId,
                        'class_content_id' => $classContentId,
                        'class_id' => $classId,
                        'grade_id' => $studentGrade['grade_id'] ?? null,
                        'start_date' => $classContent['start_date'] ?? null,
                        'end_date' => ($classContent['end_date'] && $classContent['end_date'] != '0000-00-00') ? $classContent['end_date'] : null,
                        'status' => 1, // Yet to start
                        'draft_status' => '1',
                        'created_by' => $studentId,
                        'created_date' => date('Y-m-d H:i:s'),
                        'modified_by' => $studentId,
                        'modified_date' => date('Y-m-d H:i:s')
                    ];
                    
                    $db->table('student_content')->insert($studentContentData);
                    $studentContentId = $db->insertID();
                    
                    log_message('debug', '✅ [STUDENT] Created student_content record: ' . $studentContentId . ' for class-wide content');
                }
            }
            
            // Update content_started_at timestamp
            $updateData = [
                'content_started_at' => date('Y-m-d H:i:s'),
                'modified_date' => date('Y-m-d H:i:s')
            ];
            
            $updateResult = $db->table('student_content')
                ->where('id', $studentContentId)
                ->update($updateData);
            
            if ($updateResult) {
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => [
                        'student_content_id' => $studentContentId,
                        'content_started_at' => $updateData['content_started_at']
                    ],
                    'ErrorObject' => ''
                ]);
            } else {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Failed to update content start time'
                ], 500);
            }

        } catch (\Exception $e) {
            log_message('error', 'Student updateContentStartTime error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Make up class - Transfer student to a makeup class
     */
    public function makeUpClass(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            // Validation
            if (empty($params['platform'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Platform Should not be Empty'
                ]);
            }
            
            if (empty($params['user_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'User Id Should not be Empty'
                ]);
            }
            
            if (empty($params['role_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Role Id Should not be Empty'
                ]);
            }
            
            if (empty($params['to_class'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'MakeUp Class Should not be Empty'
                ]);
            }
            
            if (empty($params['from_class'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Current Class Should not be Empty'
                ]);
            }
            
            if (empty($params['student_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Student Id Should not be Empty'
                ]);
            }
            
            $db = \Config\Database::connect();
            
            // Check if makeup class transfer already exists
            $transferBuilder = $db->table('student_class_transfer');
            $transferBuilder->where('from_class', $params['from_class']);
            $transferBuilder->where('to_class', $params['to_class']);
            $transferBuilder->where('student_id', $params['student_id']);
            $transferBuilder->where('type', 'M');
            $existingTransfer = $transferBuilder->get()->getRowArray();
            
            // Prepare makeup class transfer data
            $makeUpClass = [
                'class_id' => $params['to_class'],
                'from_class' => $params['from_class'],
                'to_class' => $params['to_class'],
                'student_id' => $params['student_id'],
                'validity' => $params['start_date'] ?? $params['absent_date'] ?? date('Y-m-d'),
                'absent_date' => $params['absent_date'] ?? date('Y-m-d'),
                'status' => $params['status'] ?? 1,
                'type' => 'M',
                'joining_date' => $params['start_date'] ?? $params['absent_date'] ?? date('Y-m-d')
            ];
            
            $makeUpClassId = 0;
            
            if (empty($existingTransfer)) {
                // Insert new makeup class transfer
                $makeUpClass['created_by'] = $params['user_id'];
                $makeUpClass['created_date'] = date('Y-m-d H:i:s');
                $db->table('student_class_transfer')->insert($makeUpClass);
                $makeUpClassId = $db->insertID();
            } else {
                // Update existing makeup class transfer
                $makeUpClass['modified_by'] = $params['user_id'];
                $makeUpClass['modified_date'] = date('Y-m-d H:i:s');
                $db->table('student_class_transfer')
                    ->where('id', $existingTransfer['id'])
                    ->update($makeUpClass);
                $makeUpClassId = $existingTransfer['id'];
            }
            
            // Check if student already exists in the target class
            $studentClassBuilder = $db->table('student_class');
            $studentClassBuilder->where('student_id', $params['student_id']);
            $studentClassBuilder->where('class_id', $params['to_class']);
            $existingStudentClass = $studentClassBuilder->get()->getRowArray();
            
            $studentClassId = 0;
            
            if (!empty($existingStudentClass)) {
                // Update existing student class record
                $studentData = [
                    'class_id' => $params['to_class'],
                    'from_class' => $params['from_class'],
                    'student_id' => $params['student_id'],
                    'joining_date' => $params['start_date'] ?? $params['absent_date'] ?? date('Y-m-d'),
                    'validity' => $params['start_date'] ?? $params['absent_date'] ?? date('Y-m-d'),
                    'status' => 1,
                    'modified_by' => $params['user_id'],
                    'modified_date' => date('Y-m-d H:i:s')
                ];
                $db->table('student_class')
                    ->where('student_id', $params['student_id'])
                    ->where('class_id', $params['to_class'])
                    ->update($studentData);
                $studentClassId = $existingStudentClass['id'] ?? 1;
            } else {
                // Insert new student class record
                $studentClassData = [
                    'class_id' => $params['to_class'],
                    'from_class' => $params['from_class'],
                    'student_id' => $params['student_id'],
                    'joining_date' => $params['start_date'] ?? $params['absent_date'] ?? date('Y-m-d'),
                    'validity' => $params['start_date'] ?? $params['absent_date'] ?? date('Y-m-d'),
                    'status' => 1,
                    'class_type' => 2,
                    'created_by' => $params['user_id'],
                    'created_date' => date('Y-m-d H:i:s')
                ];
                $db->table('student_class')->insert($studentClassData);
                $studentClassId = $db->insertID();
            }
            
            if ($studentClassId > 0 && $makeUpClassId > 0) {
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => 'Student Added Successfully.',
                    'ErrorObject' => ''
                ]);
            } else {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Failed to add'
                ]);
            }
            
        } catch (\Exception $e) {
            log_message('error', 'makeUpClass error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get OpenAI Feedback for student essay
     * Migrated from pre-migration code
     */
    public function getOpenAiFeedback(): ResponseInterface
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
            
            if (empty($params['question_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Question Id should not be empty'
                ]);
            }
            
            if (empty($params['question'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Question should not be empty'
                ]);
            }
            
            if (empty($params['student_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Student Id should not be empty'
                ]);
            }
            
            if (empty($params['student_answer'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Student Answer should not be empty'
                ]);
            }

            // Call newAiFeedback method
            $feedback = $this->newAiFeedback($params);
            
            return $this->respond($feedback);

        } catch (\Exception $e) {
            log_message('error', 'getOpenAiFeedback error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * New AI Feedback method
     * Migrated from pre-migration code
     */
    private function newAiFeedback($params)
    {
        $jsonarr = ['IsSuccess' => false, 'ResponseObject' => null, 'ErrorObject' => ''];
        
        // Get API key
        $api_key = env('openai.apiKey', '');
        if (empty($api_key)) {
            // Try to read from properties.ini if it exists
            $propertiesPath = ROOTPATH . '../properties.ini';
            if (file_exists($propertiesPath)) {
                $prop = parse_ini_file($propertiesPath, true, INI_SCANNER_RAW);
                $api_key = $prop['api_key'] ?? '';
            }
        }
        
        if (!$api_key) {
            $jsonarr['IsSuccess'] = false;
            $jsonarr['ErrorObject'] = "OPENAI_API_KEY environment variable not set.";
            return $jsonarr;
        }

        $db = \Config\Database::connect();
        
        // Get previous essay and feedback
        $essay_old = '';
        $essay_old_feedback = '';
        $builder = $db->table('student_essays');
        $builder->where('status', 1);
        $builder->where('student_content_id', $params['student_content_id']);
        $builder->where('question_id', $params['question_id']);
        $builder->orderBy('student_essay_id', 'DESC');
        $getStudentEssay = $builder->get()->getResultArray();
        
        $params['version'] = count($getStudentEssay) + 1;
        if (count($getStudentEssay) > 0) {
            $essay_old = $getStudentEssay[0]["student_answer"];
            $essay_old_feedback = $getStudentEssay[0]["feedback"];
        }

        // Get question details
        $params['content_format'] = 3;
        $questionBuilder = $db->table('question q');
        $questionBuilder->select('q.question, q.scoring_instruction');
        $questionBuilder->where('q.question_id', $params['question_id']);
        $getQuestion = $questionBuilder->get()->getRowArray();
        
        if ($getQuestion && !empty($getQuestion['scoring_instruction'])) {
            $essay_prompt = $getQuestion['question'] . ' ' . $getQuestion['scoring_instruction'];
        } else {
            $essay_prompt = $getQuestion['question'] ?? '';
        }

        $essay_new = $params['student_answer'];

        $trait_instructions = [
            "ideas" => "Clear thesis; relevant, sufficient reasons; specific details/examples from stimulus.",
            "organization" => "Letter format; focused intro; ordered body paragraphs with transitions; purposeful conclusion.",
            "voice" => "Respectful, persuasive, audience-aware (superintendent); confident and engaged, not slangy.",
            "word_choice" => "Precise, varied, school-appropriate vocabulary; avoid vague/exaggerated/cliché words.",
            "sentence_fluency" => "Varied sentence beginnings/lengths; smooth flow; no run-ons/fragments.",
            "conventions" => "Correct grammar, usage, punctuation, capitalization, spelling.",
            "presentation" => "Proper letter parts, paragraphing, headings, neatness/readability."
        ];
        
        // Get class grade
        $classGradeBuilder = $db->table('student_content sc');
        $classGradeBuilder->select('sc.grade, sc.student_id, sc.content_id');
        $classGradeBuilder->where('sc.id', $params['student_content_id']);
        $getClassGrade = $classGradeBuilder->get()->getResultArray();
        
        if (empty($getClassGrade)) {
            $jsonarr['IsSuccess'] = false;
            $jsonarr['ErrorObject'] = "Class Grade and Student Grade are empty.";
            return $jsonarr;
        }
        
        $grade = explode(',', $getClassGrade[0]['grade']);
        
        // If class grade is empty, get student grade from user table
        if (empty($grade[0])) {
            $studentGradeBuilder = $db->table('user_profile_details upd');
            $studentGradeBuilder->select('upd.grade_id');
            $studentGradeBuilder->where('upd.user_id', $params['student_id']);
            $studentGradeBuilder->where('upd.school_id', $params['school_id']);
            $studentGrade = $studentGradeBuilder->get()->getResultArray();
            $grade[0] = count($studentGrade) > 0 ? $studentGrade[0]['grade_id'] : '';
        }
        
        if (!empty($grade[0]) && $grade[0] != 0) {
            $gradeBuilder = $db->table('grade');
            $gradeBuilder->select('grade_id, grade_name');
            $gradeBuilder->where('grade_id', $grade[0]);
            $getGradeList = $gradeBuilder->get()->getResultArray();
            
            if (!empty($getGradeList)) {
                $params['grade_id'] = $getGradeList[0]['grade_name'];
                
                if (($params['grade_id'] >= 1 && $params['grade_id'] <= 12) || $params['grade_id'] == 'College' || $params['grade_id'] == 'PKG' || $params['grade_id'] == 'KG') {
                    $student_grade = $params['grade_id'];
                    
                    // Load libraries
                    $modelConfig = new \App\Libraries\ModelConfig("gpt-5-mini", "gpt-5-mini-2025-08-07", 0.50, 0.25, 2.00);
                    $modelConfigGpt4o = new \App\Libraries\ModelConfig("gpt-4o", "gpt-4o-2024-08-06", 2.50, 1.25, 10.00);
                    $modelConfigGpt4oMini = new \App\Libraries\ModelConfig("gpt-4o-mini", "gpt-4o-mini-2024-07-18", 0.15, 0.075, 0.60);
                    $modelConfigO1 = new \App\Libraries\ModelConfig("o1", "o1-2024-12-17", 15.00, 7.50, 60.00);
                    $modelConfigO3Mini = new \App\Libraries\ModelConfig("o3-mini", "o3-mini-2025-01-31", 1.10, 0.55, 4.40);
                    $modelConfigO1Mini = new \App\Libraries\ModelConfig("o1-mini", "o1-mini-2024-09-12", 1.10, 0.55, 4.40);
                    
                    $MODELS = [
                        "gpt5-mini" => $modelConfig,
                        "gpt4o" => $modelConfigGpt4o,
                        "gpt4o-mini" => $modelConfigGpt4oMini,
                        "o1" => $modelConfigO1,
                        "o3-mini" => $modelConfigO3Mini,
                        "o1-mini" => $modelConfigO1Mini
                    ];
                    
                    $requestData = [
                        'student_id' => $getClassGrade[0]['student_id'],
                        'content_id' => $getClassGrade[0]['content_id']
                    ];
                    
                    $CHOSEN_MODEL = "gpt5-mini";
                    
                    $grader = new \App\Libraries\EssayGrader(
                        $MODELS[$CHOSEN_MODEL],
                        $essay_prompt,
                        $essay_old,
                        $essay_new,
                        $essay_old_feedback,
                        $trait_instructions,
                        $student_grade,
                        $MODELS["gpt4o-mini"],
                        $requestData
                    );
                    
                    $essayFeedback = $grader->run($params);
                    
                    if (!empty($essayFeedback['combined_results'])) {
                        // Insert teacher overall feedback
                        $feedbackData = [
                            'student_content_id' => $params['student_content_id'],
                            'feedback' => isset($essayFeedback['combined_results']['overall']['summary']) ? $essayFeedback['combined_results']['overall']['summary'] : '',
                            'feedback_type' => 'A',
                            'version' => !empty($params['version']) ? $params['version'] : NULL,
                            'status' => 1,
                            'created_by' => $params['user_id'],
                            'created_date' => date('Y-m-d H:i:s')
                        ];
                        $db->table('teacher_overall_feedback')->insert($feedbackData);
                        
                        // Insert student essay
                        $addStudentEssay = [
                            'student_content_id' => $params['student_content_id'],
                            'question_id' => $params['question_id'],
                            'question' => $params['question'],
                            'student_answer' => $params['student_answer'],
                            'student_score' => $essayFeedback['overall_total'],
                            'total_score' => $essayFeedback['overall_possible'],
                            'prompt_token' => $essayFeedback['prompt_token'],
                            'completion_token' => $essayFeedback['completion_token'],
                            'total_token' => $essayFeedback['total_token'],
                            'total_cost' => $essayFeedback['total_cost'],
                            'feedback' => json_encode($essayFeedback['combined_results']),
                            'feedback_received' => date('Y-m-d H:i:s'),
                            'time_taken' => $params['time_taken'] ?? 0,
                            'status' => 1,
                            'essay_embedding' => '',
                            'created_by' => $params['user_id'],
                            'created_date' => date('Y-m-d H:i:s')
                        ];
                        $db->table('student_essays')->insert($addStudentEssay);
                        $studentEssayId = $db->insertID();
                        
                        if ($studentEssayId > 0) {
                            $strengths = $top_opportunities = $next_edit_plan = [];
                            $essayBuilder = $db->table('student_essays');
                            $essayBuilder->where('student_essay_id', $studentEssayId);
                            $getStudentEssay = $essayBuilder->get()->getResultArray();
                            
                            if (!empty($getStudentEssay)) {
                                $feedback = json_decode($getStudentEssay[0]['feedback'], true);
                                
                                if (isset($feedback['overall']['strengths']) && !empty($feedback['overall']['strengths'])) {
                                    $strengths = $feedback['overall']['strengths'];
                                }
                                if (isset($feedback['overall']['top_opportunities']) && !empty($feedback['overall']['top_opportunities'])) {
                                    $top_opportunities = $feedback['overall']['top_opportunities'];
                                }
                                if (isset($feedback['next_edit_plan']) && !empty($feedback['next_edit_plan'])) {
                                    $next_edit_plan = $feedback['next_edit_plan'];
                                }
                                if (isset($feedback['trait_scores']) && !empty($feedback['trait_scores'])) {
                                    $feedback = $feedback['trait_scores'];
                                }
                                if (isset($feedback['traits']) && !empty($feedback['traits'])) {
                                    $feedback = $feedback['traits'];
                                }
                                
                                if (is_null($feedback)) {
                                    $getStudentEssay[0]['version'] = "V1";
                                } else {
                                    $getStudentEssay[0]['version'] = "V2";
                                    $getStudentEssay[0]['feedback'] = $feedback;
                                    $getStudentEssay[0]['strengths'] = $strengths;
                                    $getStudentEssay[0]['top_opportunities'] = $top_opportunities;
                                    $getStudentEssay[0]['next_edit_plan'] = $next_edit_plan;
                                }
                            }
                            
                            $jsonarr["IsSuccess"] = true;
                            $jsonarr["ResponseObject"] = $getStudentEssay;
                        } else {
                            $jsonarr['IsSuccess'] = false;
                            $jsonarr['ErrorObject'] = "Feedback Not Updated";
                        }
                    } else {
                        $jsonarr['IsSuccess'] = false;
                        $jsonarr['ErrorObject'] = "Your essay has been saved successfully. However feedback couldn't be generated at this moment. Please try again after sometime.";
                    }
                } else {
                    $jsonarr['IsSuccess'] = false;
                    $jsonarr['ErrorObject'] = "Invalid Grade";
                }
            } else {
                $jsonarr['IsSuccess'] = false;
                $jsonarr['ErrorObject'] = "Invalid Grade";
            }
        } else {
            $jsonarr['IsSuccess'] = false;
            $jsonarr['ErrorObject'] = "Class Grade and Student Grade are empty.";
        }
        
        return $jsonarr;
    }

    /**
     * Get OpenAI Feedback Count
     * Migrated from pre-migration code
     */
    public function getOpenAiFeedbackCount(): ResponseInterface
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
            
            if (empty($params['student_content_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Student Content Id should not be empty'
                ]);
            }

            if (empty($params['question_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Question Id should not be empty'
                ]);
            }

            $db = \Config\Database::connect();
            
            $builder = $db->table('student_essays');
            $builder->select('student_essay_id, student_content_id, question_id, question, student_answer, feedback, student_score, total_score, time_taken, essay_embedding, created_date');
            $builder->where('status', 1);
            $builder->where('student_content_id', $params['student_content_id']);
            $builder->where('question_id', $params['question_id']);
            $builder->orderBy('student_essay_id', 'DESC');
            $getStudentEssay = $builder->get()->getResultArray();
            
            foreach($getStudentEssay as $key => $value){
                $getStudentEssay[$key]['feedback'] = json_decode($value['feedback'], true);
                if(isset($getStudentEssay[$key]['feedback']['overall']['strengths']) && !empty($getStudentEssay[$key]['feedback']['overall']['strengths'])){
                    $getStudentEssay[$key]['strengths'] = $getStudentEssay[$key]['feedback']['overall']['strengths'];
                }
                if(isset($getStudentEssay[$key]['feedback']['overall']['top_opportunities']) && !empty($getStudentEssay[$key]['feedback']['overall']['top_opportunities'])){
                    $getStudentEssay[$key]['top_opportunities'] = $getStudentEssay[$key]['feedback']['overall']['top_opportunities'];
                }
                if(isset($getStudentEssay[$key]['feedback']['next_edit_plan']) && !empty($getStudentEssay[$key]['feedback']['next_edit_plan'])){
                    $getStudentEssay[$key]['next_edit_plan'] = $getStudentEssay[$key]['feedback']['next_edit_plan'];
                }                 
                if(isset($getStudentEssay[$key]['feedback']['trait_scores']) && !empty($getStudentEssay[$key]['feedback']['trait_scores'])){
                    $getStudentEssay[$key]['feedback'] = $getStudentEssay[$key]['feedback']['trait_scores'];
                }
                if(isset($getStudentEssay[$key]['feedback']['traits']) && !empty($getStudentEssay[$key]['feedback']['traits'])){
                    $getStudentEssay[$key]['feedback'] = $getStudentEssay[$key]['feedback']['traits'];
                }
                $getStudentEssay[$key]['version'] = 'V2';
                if(is_null($getStudentEssay[$key]['feedback'])){
                    $getStudentEssay[$key]['feedback'] = $value['feedback'];
                    $getStudentEssay[$key]['version'] = 'V1';
                }
            }
            
            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $getStudentEssay,
                'count' => count($getStudentEssay),
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            log_message('error', 'getOpenAiFeedbackCount error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ], 500);
        }
    }
}

