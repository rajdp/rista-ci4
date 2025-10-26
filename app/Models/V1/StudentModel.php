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
        $db = \Config\Database::connect();
        
        $condition = '';
        $studentId = '';
        
        if (isset($data->school_id) && $data->school_id > 0) {
            $condition = " AND FIND_IN_SET({$data->school_id}, u.school_id)";
        }
        
        if (isset($data->student_id) && $data->student_id > 0) {
            $studentId = " AND u.user_id = {$data->student_id}";
        }
        
        $query = "SELECT u.user_id, u.role_id, u.email_id, COALESCE(u.mobile,'') AS mobile,
                         upd.status, '{$data->school_id}' AS school_id,
                         COALESCE(s.name, '') AS school_name, u.login_type, u.created_by, u.created_date, u.modified_by, 
                         u.modified_date, up.profile_id, 
                         COALESCE(up.first_name, '') AS first_name, 
                         COALESCE(up.last_name, '') AS last_name, upd.school_idno, 
                         COALESCE(up.profile_url, '') AS profile_url,
                         COALESCE(up.profile_thumb_url, '') AS profile_thumb_url, 
                         COALESCE(up.gender, '') AS gender, 
                         COALESCE(up.birthday,'') AS birthday, up.currency, upd.subject, upd.doj as registration_date,
                         upd.dropped_date, upd.grade_id, DATE_FORMAT(upd.upgrade_date,'%m-%d-%Y') as upgrade_date,
                         DATE_FORMAT(upd.created_date,'%m-%d-%Y') as graded_date, 
                         COALESCE(g.grade_name,'') AS grade_name, upd.batch_id,
                         COALESCE(b.batch_name,'') AS batch_name, ua.address_type, ua.name as address_name,
                         ua.address1, 
                         COALESCE(ua.address2,'') AS address2, ua.city, ua.state as state_id, ua.email_ids,
                         COALESCE(st.name, '') AS state_name,
                         COALESCE(c.name, '') AS country_name, ua.country as country_id, 
                         ua.postal_code 
                         FROM user u 
                         LEFT JOIN user_profile up ON u.user_id = up.user_id
                         LEFT JOIN user_address ua ON u.user_id = ua.user_id 
                         LEFT JOIN user_profile_details upd ON u.user_id = upd.user_id 
                         LEFT JOIN school s ON upd.school_id = s.school_id
                         LEFT JOIN grade g ON upd.grade_id = g.grade_id
                         LEFT JOIN batch b ON upd.batch_id = b.batch_id
                         LEFT JOIN state st ON ua.state = st.id
                         LEFT JOIN country c ON ua.country = c.id
                         WHERE u.role_id = 5 $condition $studentId AND upd.school_id = '{$data->school_id}' 
                         AND s.school_id = '{$data->school_id}'
                         ORDER BY upd.user_details_id DESC, ua.address_id";
        
        $results = $db->query($query)->getResultArray();
        
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
        $builder = $this->db->table('class c');
        $builder->select('c.class_id, c.class_name, c.teacher_id, u.user_id as student_id, 
                         CONCAT(up.first_name, " ", up.last_name) as student_name');
        $builder->join('student_class sc', 'sc.class_id = c.class_id', 'inner');
        $builder->join('user u', 'u.user_id = sc.student_id', 'inner');
        $builder->join('user_profile up', 'up.user_id = u.user_id', 'left');
        
        if (isset($data->school_id) && $data->school_id != 0) {
            $builder->where('c.school_id', $data->school_id);
        }
        
        $builder->where('c.status', 1);
        $builder->where('sc.status', 1);
        $builder->where('u.status', 1);
        
        return $builder->get()->getResultArray();
    }
} 