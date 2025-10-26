<?php

namespace App\Models\V1;

use CodeIgniter\Model;

class BaseModel extends Model
{
    protected $table;
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [];
    protected $useTimestamps = true;
    protected $createdField = 'created_date';
    protected $updatedField = 'updated_date';
    protected $deletedField = 'deleted_date';

    public function __construct()
    {
        parent::__construct();
    }

    protected function getBuilder($table = null)
    {
        return $this->db->table($table ?? $this->table);
    }

    protected function getResult($builder)
    {
        return $builder->get()->getResultArray();
    }

    protected function getRow($builder)
    {
        return $builder->get()->getRowArray();
    }
} 