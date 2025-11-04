<?php

namespace App\Models\V1;

class ClassesModel extends BaseModel
{
    protected $table = 'class';
    protected $primaryKey = 'class_id';
    protected $allowedFields = [
        'class_name', 'school_id', 'subject', 'grade', 'batch_id', 
        'start_date', 'end_date', 'status', 'class_status', 'class_type',
        'announcement_type', 'tags', 'created_by', 'created_date', 'modified_date'
    ];

    /**
     * Get teacher list for a school
     */
    public function getTeacherList($schoolId)
    {
        $db = \Config\Database::connect();
        
        $query = "SELECT DISTINCT u.user_id AS teacher_id, 
                    CONCAT_WS(' ', up.first_name, up.last_name) AS teacher_name,
                    up.profile_url
                    FROM user u
                    INNER JOIN user_profile up ON u.user_id = up.user_id
                    WHERE u.role_id = 2 
                    AND u.school_id = ? 
                    AND u.status = 1
                    ORDER BY up.first_name";
        
        return $db->query($query, [$schoolId])->getResultArray();
    }

    /**
     * Get class list
     */
    public function getClassList($data)
    {
        $schoolId = $data->school_id ?? 0;
        
        $builder = $this->getBuilder('class c');
        $builder->select('c.class_id, c.class_name, c.subject, c.grade, c.start_date, c.end_date, 
                         c.status, c.class_status, c.class_type, c.tags');
        $builder->where('c.school_id', $schoolId);
        $builder->where('c.status', 1);
        $builder->orderBy('c.class_id', 'DESC');
        
        return $this->getResult($builder);
    }

    /**
     * Create new class
     */
    public function createClass($data)
    {
        $classData = [
            'class_name' => $data->class_name ?? '',
            'school_id' => $data->school_id ?? 0,
            'subject' => $data->subject ?? '',
            'grade' => $data->grade ?? '',
            'batch_id' => $data->batch_id ?? 0,
            'start_date' => $data->start_date ?? null,
            'end_date' => $data->end_date ?? null,
            'status' => $data->status ?? 1,
            'class_status' => $data->class_status ?? 0,
            'created_by' => $data->user_id ?? 0,
            'created_date' => date('Y-m-d H:i:s')
        ];
        
        return $this->db->table('class')->insert($classData) ? $this->db->insertID() : false;
    }

    /**
     * Update class
     */
    public function updateClass($classId, $data)
    {
        $updateData = [
            'class_name' => $data->class_name ?? null,
            'subject' => $data->subject ?? null,
            'grade' => $data->grade ?? null,
            'start_date' => $data->start_date ?? null,
            'end_date' => $data->end_date ?? null,
            'modified_date' => date('Y-m-d H:i:s')
        ];
        
        // Remove null values
        $updateData = array_filter($updateData, function($value) {
            return $value !== null;
        });
        
        return $this->db->table('class')->where('class_id', $classId)->update($updateData);
    }

    /**
     * Get comment count for classes
     */
    public function getCommentCount($data)
    {
        // Return empty array for now - this is typically used for class notes/comments
        // which may not be critical for initial testing
        return [];
    }

    /**
     * Get class list based on type
     * Type 8 = Grade report classes (active classes that have started)
     */
    public function classList($params)
    {
        $db = \Config\Database::connect();
        
        $type = $params['type'] ?? 1;
        $schoolId = $params['school_id'] ?? 0;
        
        // Build query - ALWAYS use user's school(s) from their profile first
        $whereClause = "";
        if (isset($params['user_id']) && $params['user_id'] > 0) {
            // Get user's school_id - user.school_id can be comma-separated
            $userSchools = $db->query("SELECT school_id FROM user WHERE user_id = {$params['user_id']}")->getRowArray();
            
            if ($userSchools && !empty($userSchools['school_id'])) {
                // User table has school_id
                $userSchoolId = $userSchools['school_id'];
                
                // Check if comma-separated list
                if (strpos($userSchoolId, ',') !== false) {
                    $whereClause = "FIND_IN_SET(c.school_id, '$userSchoolId')";
                } else {
                    $whereClause = "c.school_id = $userSchoolId";
                }
            } else {
                // No user school found - use any school with classes (for development/testing)
                $whereClause = "1=1";
            }
        } else {
            // No user_id - use provided school_id or get any classes
            $whereClause = ($schoolId > 0) ? "c.school_id = $schoolId" : "1=1";
        }
        
        // Query for active classes
        $query = "SELECT c.class_id, c.school_id, c.class_name, c.batch_id, c.class_type,
                    c.subject, c.start_date, c.end_date, c.tags, c.class_status, c.grade,
                    COALESCE((SELECT GROUP_CONCAT(batch_name) FROM batch WHERE FIND_IN_SET(batch_id, c.batch_id)),'-') as classroom_name,
                    COALESCE((SELECT GROUP_CONCAT(subject_name) FROM subject WHERE FIND_IN_SET(subject_id, c.subject)),'') AS subject_name,
                    COALESCE((SELECT GROUP_CONCAT(grade_name) FROM grade WHERE FIND_IN_SET(grade_id, c.grade)),'') AS grade_name,
                    (CASE WHEN c.start_date > CURRENT_DATE() THEN 2 
                    WHEN c.end_date >= CURRENT_DATE() THEN 3
                    WHEN c.end_date < CURRENT_DATE() THEN 4
                    ELSE 1 END) AS classDate_status
                    FROM class c
                    WHERE $whereClause AND c.status = 1
                    ORDER BY c.start_date DESC, c.class_name";
        
        $result = $db->query($query)->getResultArray();
        
        return $result;
    }

    /**
     * View assignments for a class
     * Based on CI3 Classes_model->viewAssignments()
     */
    public function viewAssignments($params)
    {
        $db = \Config\Database::connect();
        
        $schoolAutoReview = '';
        $condition = '';
        $condition1 = "cc.start_date ASC";
        
        // Filter by assignment type
        if (isset($params['type'])) {
            if ($params['type'] == 0) {
                $condition = '';
            } elseif ($params['type'] == 1) {
                // Upcoming
                $condition = "AND cc.start_date > CURRENT_DATE()";
            } elseif ($params['type'] == 2) {
                // In progress
                $condition = "AND cc.start_date <= CURRENT_DATE() AND (cc.end_date >= CURRENT_DATE() OR cc.end_date = '0000-00-00')";
            } elseif ($params['type'] == 3) {
                // Completed
                $condition = "AND cc.end_date < CURRENT_DATE() AND cc.end_date != '0000-00-00'";
                $condition1 = "cc.start_date DESC";
            }
        }
        
        // Add school auto-review setting if school_id provided
        if (isset($params['school_id']) && $params['school_id'] > 0) {
            $schoolAutoReview = ", COALESCE((SELECT value FROM admin_settings_school WHERE name = 'allow_autograde_settings' AND school_id = {$params['school_id']}),'') as allow_autograde";
        }
        
        $query = "SELECT cnt.content_id, cnt.name AS content_name, c.class_name, c.class_id,
                    COALESCE(cnt.description, '') AS description,
                    COALESCE(cnt.profile_url, '') AS profile_url, 
                    COALESCE(cnt.profile_thumb_url, '') AS profile_thumb_url,
                    COALESCE(cnt.file_path, '') AS file_path, 
                    COALESCE(cnt.teacher_version, '') AS teacher_version_path,
                    COALESCE(cnt.links, '') AS links,
                    COALESCE(cnt.file_text, '') AS file_text,
                    COALESCE(cnt.tags,'') AS tags, cnt.content_type, cnt.content_format, 
                    cc.start_date, cc.end_date,
                    TIME_FORMAT(cc.start_time, '%h:%i %p') as start_time,
                    TIME_FORMAT(cc.end_time, '%h:%i %p') as end_time, cc.id as class_content_id,
                    (CASE WHEN cc.start_date > CURRENT_DATE() THEN 2
                    WHEN cc.end_date >= CURRENT_DATE() THEN 3
                    WHEN cc.end_date < CURRENT_DATE() THEN 4 ELSE 1 END) as content_date_status,
                    cc.topic_id, COALESCE(t.topic, '') as topic, cc.auto_review $schoolAutoReview
                    FROM class AS c 
                    LEFT JOIN class_content AS cc ON c.class_id = cc.class_id
                    LEFT JOIN content AS cnt ON cc.content_id = cnt.content_id
                    LEFT JOIN topic AS t ON t.topic_id = cc.topic_id
                    WHERE c.class_id = {$params['class_id']} AND cnt.content_type = 2
                    AND cnt.status = 1 AND c.status = 1 AND cc.status = 1 $condition 
                    ORDER BY CASE WHEN cc.start_date <= CURRENT_DATE()
                    AND cc.end_date != '0000-00-00' AND cc.end_date >= CURRENT_DATE() THEN 1
                    WHEN cc.start_date <= CURRENT_DATE() AND cc.end_date = '0000-00-00' THEN 2
                    WHEN cc.start_date >= CURRENT_DATE() THEN 3 ELSE 4 END, $condition1";
        
        log_message('debug', '🔍 ClassesModel::viewAssignments query: ' . $query);
        
        $result = $db->query($query)->getResultArray();
        
        log_message('debug', '✅ ClassesModel::viewAssignments found ' . count($result) . ' assignments');
        
        return $result;
    }
}
