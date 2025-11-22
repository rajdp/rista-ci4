<?php

namespace App\Models\V1;

use CodeIgniter\Model;

class StudentModel extends Model
{
    protected $table = 'students';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'user_id',
        'school_id',
        'grade_id',
        'status',
        'created_by',
        'created_date',
        'updated_by',
        'updated_date'
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function user($params, $type, $id)
    {
        $builder = $this->db->table('user u');
        $builder->select('u.*, up.first_name, up.last_name');
        $builder->join('user_profile up', 'up.user_id = u.id', 'left');
        
        if ($type == 'add') {
            $builder->where('u.email_id', $params['email_id']);
            $builder->where('u.school_id', $params['school_id']);
        } else {
            $builder->where('u.id', $id);
        }
        
        return $builder->get()->getResultArray();
    }

    public function checkSchool($params)
    {
        $builder = $this->db->table('school');
        $builder->where('id', $params['school_id']);
        return $builder->get()->getResultArray();
    }

    public function getStudentClasses($studentId, $schoolId)
    {
        $builder = $this->db->table('class_students cs');
        $builder->select('c.*, g.grade_name, s.subject_name, t.first_name as teacher_first_name, t.last_name as teacher_last_name');
        $builder->join('classes c', 'c.id = cs.class_id');
        $builder->join('grades g', 'g.id = c.grade_id');
        $builder->join('subjects s', 's.id = c.subject_id');
        $builder->join('user_profile t', 't.user_id = c.teacher_id', 'left');
        $builder->where('cs.student_id', $studentId);
        $builder->where('cs.school_id', $schoolId);
        $builder->where('cs.status', 1);
        return $builder->get()->getResultArray();
    }

    public function getStudentAssignments($studentId, $classId)
    {
        $builder = $this->db->table('assignments a');
        $builder->select('a.*, c.class_name, s.subject_name');
        $builder->join('classes c', 'c.id = a.class_id');
        $builder->join('subjects s', 's.id = c.subject_id');
        $builder->where('a.class_id', $classId);
        $builder->where('a.status', 1);
        $builder->orderBy('a.due_date', 'DESC');
        return $builder->get()->getResultArray();
    }

    /**
     * Get list of students ONLY (role_id = 5)
     * Note: role_id 5 = Student, 4 = Teacher, 3 = Content Creator
     */
    public function getStudents($data)
    {
        try {
            $db = \Config\Database::connect();
            
            // Handle both array and object input
            if (is_array($data)) {
                $data = (object)$data;
            }
            
            $schoolId = isset($data->school_id) && $data->school_id > 0 ? (int)$data->school_id : 0;
            $studentId = isset($data->student_id) && $data->student_id > 0 ? (int)$data->student_id : 0;
            
            // Check if next_billing_date column exists
            $hasNextBillingDate = $db->fieldExists('next_billing_date', 'user_profile_details');
            $nextBillingDateSelect = $hasNextBillingDate 
                ? 'COALESCE(DATE_FORMAT(upd.next_billing_date, "%Y-%m-%d"), "") as next_billing_date'
                : '"" as next_billing_date';
            
            $builder = $db->table('user u');
            $builder->select('u.user_id, u.role_id, u.email_id, COALESCE(u.mobile, "") AS mobile,
                             COALESCE(upd.status, "") as status, ' . $schoolId . ' AS school_id,
                             COALESCE(s.name, "") AS school_name, u.login_type, u.created_by, u.created_date, u.modified_by, 
                             u.modified_date, COALESCE(up.profile_id, "") as profile_id, 
                             COALESCE(up.first_name, "") AS first_name, 
                             COALESCE(up.last_name, "") AS last_name, COALESCE(upd.school_idno, "") as school_idno, 
                             COALESCE(up.profile_url, "") AS profile_url,
                             COALESCE(up.profile_thumb_url, "") AS profile_thumb_url, 
                             COALESCE(up.gender, "") AS gender, 
                             COALESCE(up.birthday, "") AS birthday, COALESCE(up.currency, "") as currency, 
                             COALESCE(upd.subject, "") as subject, 
                             COALESCE(DATE_FORMAT(upd.doj, "%Y-%m-%d"), "") as registration_date,
                             COALESCE(upd.dropped_date, "") as dropped_date, 
                             ' . $nextBillingDateSelect . ',
                             COALESCE(upd.grade_id, "") as grade_id, 
                             COALESCE(DATE_FORMAT(upd.upgrade_date, "%m-%d-%Y"), "") as upgrade_date,
                             COALESCE(DATE_FORMAT(upd.created_date, "%m-%d-%Y"), "") as graded_date, 
                             COALESCE(g.grade_name, "") AS grade_name, COALESCE(upd.batch_id, "") as batch_id,
                             COALESCE(b.batch_name, "") AS batch_name, 
                             COALESCE(ua.address_type, "") as address_type, 
                             COALESCE(ua.name, "") as address_name,
                             COALESCE(ua.address1, "") as address1, 
                             COALESCE(ua.address2, "") AS address2, 
                             COALESCE(ua.city, "") as city, 
                             COALESCE(ua.state, "") as state_id, 
                             COALESCE(ua.email_ids, "") as email_ids,
                             COALESCE(st.name, "") AS state_name,
                             COALESCE(c.name, "") AS country_name, 
                             COALESCE(ua.country, "") as country_id, 
                             COALESCE(ua.postal_code, "") as postal_code');
            $builder->join('user_profile up', 'u.user_id = up.user_id', 'left');
            $builder->join('user_address ua', 'u.user_id = ua.user_id', 'left');
            $builder->join('user_profile_details upd', 'u.user_id = upd.user_id', 'left');
            $builder->join('school s', 'upd.school_id = s.school_id', 'left');
            $builder->join('grade g', 'upd.grade_id = g.grade_id', 'left');
            $builder->join('batch b', 'upd.batch_id = b.batch_id', 'left');
            $builder->join('state st', 'ua.state = st.id', 'left');
            $builder->join('country c', 'ua.country = c.id', 'left');
            
            // Filter by role_id = 5 (Students)
            $builder->where('u.role_id', 5);
            
            // Filter by school_id if provided
            if ($schoolId > 0) {
                // Primary filter: user's school_id (handles both integer and comma-separated string)
                $builder->groupStart()
                    ->where('u.school_id', $schoolId)
                    ->orWhere("FIND_IN_SET('{$schoolId}', u.school_id)", null, false)
                    ->groupEnd();
                // Note: We don't filter upd.school_id here to avoid excluding students
                // The next_billing_date will use the first matching record or be empty
            }
            
            // Filter by specific student_id if provided
            if ($studentId > 0) {
                $builder->where('u.user_id', $studentId);
            }
            
            // Order by user_id as fallback
            $builder->orderBy('u.user_id', 'DESC');
            
            $results = $builder->get()->getResultArray();
        } catch (\Exception $e) {
            log_message('error', 'getStudents error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            return [];
        }
        
        // Group by student and organize data
        $students = [];
        foreach ($results as $row) {
            $userId = $row['user_id'];
            
            if (!isset($students[$userId])) {
                $students[$userId] = $row;
                $students[$userId]['mobile'] = !empty($row['mobile']) ? explode(',', $row['mobile']) : [];
                $students[$userId]['address'] = [];
                $students[$userId]['parent1_email_ids'] = [];
                $students[$userId]['parent2_email_ids'] = [];
                $students[$userId]['parent1_firstname'] = '';
                $students[$userId]['parent1_lastname'] = '';
                $students[$userId]['parent2_firstname'] = '';
                $students[$userId]['parent2_lastname'] = '';
            }
            
            // Process addresses and parent info
            if (!empty($row['address1']) || !empty($row['city'])) {
                $addressIndex = $row['address_type'] == '1' ? 0 : 1;
                $students[$userId]['address'][$addressIndex] = [
                    'address1' => $row['address1'] ?? '',
                    'address2' => $row['address2'] ?? '',
                    'city' => $row['city'] ?? '',
                    'state_id' => $row['state_id'] ?? '0',
                    'state_name' => $row['state_name'] ?? '',
                    'country_id' => $row['country_id'] ?? '0',
                    'country_name' => $row['country_name'] ?? '',
                    'postal_code' => $row['postal_code'] ?? ''
                ];
                
                // Parse parent email IDs
                if (!empty($row['email_ids'])) {
                    $emailIds = explode(',', $row['email_ids']);
                    if ($row['address_type'] == '1') {
                        $students[$userId]['parent1_email_ids'] = $emailIds;
                    } else {
                        $students[$userId]['parent2_email_ids'] = $emailIds;
                    }
                }
                
                // Parse parent name
                if (!empty($row['address_name'])) {
                    $names = explode(' ', $row['address_name'], 2);
                    if ($row['address_type'] == '1') {
                        $students[$userId]['parent1_firstname'] = $names[0] ?? '';
                        $students[$userId]['parent1_lastname'] = $names[1] ?? '';
                    } else {
                        $students[$userId]['parent2_firstname'] = $names[0] ?? '';
                        $students[$userId]['parent2_lastname'] = $names[1] ?? '';
                    }
                }
            }
        }
        
        return array_values($students);
    }

    /**
     * Get students from class
     */
    public function studentFromClass($data)
    {
        try {
            $builder = $this->db->table('class c');
            $builder->select('c.class_id, c.class_name, COALESCE(c.teacher_id, "") as teacher_id, u.user_id as student_id, 
                             COALESCE(CONCAT(up.first_name, " ", up.last_name), "") as student_name');
            $builder->join('student_class sc', 'sc.class_id = c.class_id', 'inner');
            $builder->join('user u', 'u.user_id = sc.student_id', 'inner');
            $builder->join('user_profile up', 'up.user_id = u.user_id', 'left');
            
            // Filter by school_id if provided and column exists
            if (isset($data->school_id) && $data->school_id != 0) {
                // Check if school_id column exists in class table
                if ($this->db->fieldExists('school_id', 'class')) {
                    $builder->where('c.school_id', $data->school_id);
                } else {
                    // If school_id doesn't exist in class, filter by student's school_id
                    $builder->groupStart()
                        ->where('u.school_id', $data->school_id)
                        ->orWhere("FIND_IN_SET('{$data->school_id}', u.school_id)", null, false)
                        ->groupEnd();
                }
            }
            
            $builder->where('c.status', 1);
            $builder->where('sc.status', 1);
            $builder->where('u.status', 1);
            
            return $builder->get()->getResultArray();
        } catch (\Exception $e) {
            log_message('error', 'studentFromClass error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Move student to new class
     */
    public function moveStudentToClass($studentId, $oldClassId, $newClassId, $userId)
    {
        $db = \Config\Database::connect();
        
        // Update student's class membership
        $db->table('student_class')
            ->where('student_id', $studentId)
            ->where('class_id', $oldClassId)
            ->update(['class_id' => $newClassId, 'modified_date' => date('Y-m-d H:i:s')]);
        
        // Update student_content class access
        $accessModel = new \App\Models\V1\StudentContentClassAccessModel();
        
        // Get all student_content records with old class access
        $studentContentRecords = $db->table('student_content_class_access')
            ->where('class_id', $oldClassId)
            ->join('student_content', 'student_content.id = student_content_class_access.student_content_id')
            ->where('student_content.student_id', $studentId)
            ->get()
            ->getResultArray();
        
        foreach ($studentContentRecords as $record) {
            // Check if new class has same content
            $newClassContent = $db->table('class_content')
                ->where('class_id', $newClassId)
                ->where('content_id', $record['content_id'])
                ->get()
                ->getRowArray();
            
            if ($newClassContent) {
                // Update access to new class
                $accessModel->updateClassAccess(
                    $record['student_content_id'],
                    $oldClassId,
                    $newClassId,
                    $newClassContent['id'],
                    $userId
                );
            } else {
                // Remove access (content not in new class)
                $accessModel->removeClassAccess($record['student_content_id'], $oldClassId);
            }
        }
        
        return true;
    }
    
    /**
     * Add student to additional class (for students in multiple classes)
     */
    public function addStudentToClass($studentId, $classId, $userId)
    {
        $db = \Config\Database::connect();
        
        // Add class membership
        $db->table('student_class')->insert([
            'student_id' => $studentId,
            'class_id' => $classId,
            'created_date' => date('Y-m-d H:i:s')
        ]);
        
        // Add class access for existing student_content records with matching content
        $accessModel = new \App\Models\V1\StudentContentClassAccessModel();
        
        $sql = "
            SELECT sc.id as student_content_id, cc.id as class_content_id, cc.content_id
            FROM student_content sc
            INNER JOIN class_content cc ON sc.content_id = cc.content_id
            WHERE sc.student_id = ?
            AND cc.class_id = ?
            AND cc.status = 1
        ";
        
        $matchingContent = $db->query($sql, [$studentId, $classId])->getResultArray();
        
        foreach ($matchingContent as $content) {
            $accessModel->addClassAccess(
                $content['student_content_id'],
                $classId,
                $content['class_content_id'],
                $userId
            );
        }
        
        return true;
    }
    
    /**
     * Remove student from class
     */
    public function removeStudentFromClass($studentId, $classId, $userId)
    {
        $db = \Config\Database::connect();
        
        // Remove class membership
        $db->table('student_class')
            ->where('student_id', $studentId)
            ->where('class_id', $classId)
            ->delete();
        
        // Remove class access for student_content records
        $accessModel = new \App\Models\V1\StudentContentClassAccessModel();
        
        $studentContentRecords = $db->table('student_content_class_access')
            ->where('class_id', $classId)
            ->join('student_content', 'student_content.id = student_content_class_access.student_content_id')
            ->where('student_content.student_id', $studentId)
            ->get()
            ->getResultArray();
        
        foreach ($studentContentRecords as $record) {
            $accessModel->removeClassAccess($record['student_content_id'], $classId);
        }
        
        return true;
    }

    /**
     * Check content time information for student
     * Returns content details with date/time restrictions
     */
    public function checkContentTime($params)
    {
        $db = \Config\Database::connect();
        
        $classContentId = isset($params['class_content_id']) ? (int)$params['class_content_id'] : 0;
        $contentId = isset($params['content_id']) ? (int)$params['content_id'] : 0;
        $classId = isset($params['class_id']) ? (int)$params['class_id'] : 0;
        
        $builder = $db->table('class_content cc');
        $builder->select('cc.start_date, cc.end_date, cc.start_time, cc.end_time, c.content_type, c.name');
        $builder->join('content c', 'c.content_id = cc.content_id');
        
        if ($classContentId > 0) {
            $builder->where('cc.id', $classContentId);
        } else {
            $builder->where('cc.content_id', $contentId);
            $builder->where('cc.class_id', $classId);
        }
        
        $result = $builder->get()->getRowArray();
        
        return $result ?: [];
    }
} 