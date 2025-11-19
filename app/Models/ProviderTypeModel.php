<?php

namespace App\Models;

use CodeIgniter\Model;

class ProviderTypeModel extends Model
{
    protected $table = 'provider_types';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'code',
        'name',
        'description',
        'is_active'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'code' => 'required|max_length[50]|is_unique[provider_types.code,id,{id}]',
        'name' => 'required|max_length[100]'
    ];

    protected $validationMessages = [
        'code' => [
            'is_unique' => 'This provider type code already exists'
        ]
    ];

    /**
     * Get all active provider types
     */
    public function getActiveTypes(): array
    {
        return $this->where('is_active', 1)
            ->orderBy('name', 'ASC')
            ->findAll();
    }

    /**
     * Get provider type by code
     */
    public function getByCode(string $code): ?array
    {
        return $this->where('code', $code)->first();
    }

    /**
     * Get providers for a specific type
     */
    public function getProviders(int $typeId): array
    {
        return $this->db->table('providers')
            ->where('provider_type_id', $typeId)
            ->where('is_active', 1)
            ->orderBy('name', 'ASC')
            ->get()
            ->getResultArray();
    }
}
