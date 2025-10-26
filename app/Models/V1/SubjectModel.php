<?php

namespace App\Models\V1;

use CodeIgniter\Model;

class SubjectModel extends Model
{
    protected $table = 'subject';
    protected $primaryKey = 'subject_id';
    protected $allowedFields = ['subject_name', 'description', 'status'];
    protected $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect();
    }

    /**
     * Get list of subjects
     */
    public function getSubjects($data)
    {
        $builder = $this->db->table('subject');
        $builder->where('status', 1);
        $builder->orderBy('subject_id', 'ASC');
        
        $query = $builder->get();
        return $query->getResultArray();
    }
}
