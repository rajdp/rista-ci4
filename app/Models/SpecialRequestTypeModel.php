<?php

namespace App\Models;

use App\Models\V1\BaseModel;

class SpecialRequestTypeModel extends BaseModel
{
    protected $table = 't_special_request_type';
    protected $primaryKey = 'type_id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'school_id',
        'type_key',
        'display_name',
        'description',
        'is_active',
        'requires_admin_approval',
        'auto_assign_to_role',
        'display_order',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'school_id' => 'required|integer',
        'type_key' => 'required|max_length[100]',
        'display_name' => 'required|max_length[150]',
    ];

    protected $validationMessages = [
        'school_id' => [
            'required' => 'School ID is required',
        ],
        'type_key' => [
            'required' => 'Type key is required',
        ],
        'display_name' => [
            'required' => 'Display name is required',
        ],
    ];

    /**
     * Get active request types for a school
     */
    public function getActiveTypes($schoolId)
    {
        return $this->where('school_id', $schoolId)
                    ->where('is_active', 1)
                    ->orderBy('display_order', 'ASC')
                    ->findAll();
    }

    /**
     * Get all request types for a school
     */
    public function getSchoolTypes($schoolId)
    {
        return $this->where('school_id', $schoolId)
                    ->orderBy('display_order', 'ASC')
                    ->findAll();
    }

    /**
     * Get type by key
     */
    public function getByKey($schoolId, $typeKey)
    {
        return $this->where('school_id', $schoolId)
                    ->where('type_key', $typeKey)
                    ->first();
    }

    /**
     * Toggle active status
     */
    public function toggleActive($typeId, $isActive)
    {
        return $this->update($typeId, ['is_active' => $isActive ? 1 : 0]);
    }

    /**
     * Update display order
     */
    public function updateOrder($typeId, $displayOrder)
    {
        return $this->update($typeId, ['display_order' => $displayOrder]);
    }
}
