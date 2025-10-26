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
}
