<?php

namespace App\Models\V1;

use CodeIgniter\Model;

class TeacherModel extends Model
{
    protected $table = 'user';
    protected $primaryKey = 'user_id';
    protected $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect();
    }

    /**
     * Get list of teachers (role_id = 4)
     */
    public function getTeachers($data)
    {
        $builder = $this->db->table('user u');
        $builder->select('u.user_id, u.email_id, up.first_name, up.last_name, u.status, 
                         u.school_id, u.role_id, u.login_type, u.created_by, u.created_date');
        $builder->join('user_profile up', 'up.user_id = u.user_id', 'left');
        $builder->where('u.role_id', 4); // Teacher role
        
        if (isset($data->school_id) && $data->school_id != 0) {
            $builder->where('u.school_id', $data->school_id);
        }
        
        $builder->where('u.status', 1);
        $builder->orderBy('up.first_name', 'ASC');
        
        $results = $builder->get()->getResultArray();
        
        // Add mobile as empty array - frontend expects this field
        foreach ($results as $key => $teacher) {
            $results[$key]['mobile'] = [];
        }
        
        return $results;
    }
}
