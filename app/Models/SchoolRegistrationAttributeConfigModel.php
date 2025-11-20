<?php

namespace App\Models;

use CodeIgniter\Model;

class SchoolRegistrationAttributeConfigModel extends Model
{
    protected $table = 'school_registration_attribute_configs';
    protected $primaryKey = 'id';
    protected $allowedFields = ['school_id', 'definition'];
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}


