<?php

namespace App\Models\Admin;

use CodeIgniter\Model;

class SettingsModel extends Model
{
    protected $table = 'admin_settings';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'name',
        'value',
        'status',
        'created_date',
        'modified_date'
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_date';
    protected $updatedField = 'modified_date';

    protected $validationRules = [
        'name' => 'required|max_length[255]',
        'value' => 'required',
        'status' => 'required|in_list[0,1]'
    ];

    protected $validationMessages = [
        'name' => [
            'required' => 'Setting name is required',
            'max_length' => 'Setting name cannot exceed 255 characters'
        ],
        'value' => [
            'required' => 'Setting value is required'
        ],
        'status' => [
            'required' => 'Status is required',
            'in_list' => 'Status must be either 0 or 1'
        ]
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    protected $beforeInsert = [];
    protected $afterInsert = [];
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    /**
     * Get all active settings
     */
    public function getSettings($filters = [])
    {
        $builder = $this->builder();
        
        $builder->where('status', 1);
        
        if (isset($filters['search']) && !empty($filters['search'])) {
            $builder->like('name', $filters['search']);
        }
        
        if (isset($filters['limit']) && $filters['limit'] > 0) {
            $builder->limit($filters['limit']);
        }
        
        if (isset($filters['offset']) && $filters['offset'] > 0) {
            $builder->offset($filters['offset']);
        }
        
        return $builder->get()->getResultArray();
    }

    /**
     * Get setting by name
     */
    public function getSetting($name)
    {
        return $this->where('name', $name)
                    ->where('status', 1)
                    ->first();
    }

    /**
     * Update setting by name
     */
    public function setSetting($name, $value)
    {
        $setting = $this->where('name', $name)->first();
        
        if ($setting) {
            return $this->update($setting['id'], ['value' => $value]);
        } else {
            return $this->insert([
                'name' => $name,
                'value' => $value,
                'status' => 1
            ]);
        }
    }

    /**
     * Update multiple settings
     */
    public function updateSettings($settings)
    {
        $updated = 0;
        
        foreach ($settings as $name => $value) {
            if ($this->setSetting($name, $value)) {
                $updated++;
            }
        }
        
        return $updated;
    }

    /**
     * Delete setting by name
     */
    public function deleteSetting($name)
    {
        return $this->where('name', $name)->delete();
    }

    /**
     * Get setting list for admin (legacy method)
     */
    public function settingList()
    {
        return $this->select('id as setting_id, value as setting_value, name as setting_name')
                    ->where('status', 1)
                    ->get()
                    ->getResultArray();
    }

    /**
     * Update setting by ID (legacy method)
     */
    public function updateById($id, $value)
    {
        return $this->update($id, ['value' => $value]);
    }
}