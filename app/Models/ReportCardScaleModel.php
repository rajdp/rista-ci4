<?php

namespace App\Models;

use App\Models\V1\BaseModel;

class ReportCardScaleModel extends BaseModel
{
    protected $table = 't_rc_scale';
    protected $primaryKey = 'scale_id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'school_id',
        'name',
        'scale_json',
        'is_active',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'school_id' => 'required|integer',
        'name' => 'required|max_length[120]',
        'scale_json' => 'required',
    ];

    protected $validationMessages = [
        'school_id' => [
            'required' => 'School ID is required',
        ],
        'name' => [
            'required' => 'Scale name is required',
            'max_length' => 'Scale name cannot exceed 120 characters',
        ],
    ];

    /**
     * Get active scales for a school
     */
    public function getActiveScales($schoolId)
    {
        return $this->where('school_id', $schoolId)
                    ->where('is_active', 1)
                    ->orderBy('name', 'ASC')
                    ->findAll();
    }

    /**
     * Get scale by ID with school validation
     */
    public function getScaleForSchool($scaleId, $schoolId)
    {
        return $this->where('scale_id', $scaleId)
                    ->where('school_id', $schoolId)
                    ->first();
    }
}
