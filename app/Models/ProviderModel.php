<?php

namespace App\Models;

use CodeIgniter\Model;

class ProviderModel extends Model
{
    protected $table = 'providers';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'provider_type_id',
        'code',
        'name',
        'description',
        'is_active',
        'config_schema',
        'settings_schema',
        'documentation_url',
        'logo_url'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'provider_type_id' => 'required|integer',
        'code' => 'required|max_length[50]',
        'name' => 'required|max_length[100]'
    ];

    /**
     * Get all active providers
     */
    public function getActiveProviders(): array
    {
        return $this->where('is_active', 1)
            ->orderBy('name', 'ASC')
            ->findAll();
    }

    /**
     * Get providers by type code (sms, email, payment)
     */
    public function getByTypeCode(string $typeCode): array
    {
        return $this->select('providers.*')
            ->join('provider_types', 'provider_types.id = providers.provider_type_id')
            ->where('provider_types.code', $typeCode)
            ->where('providers.is_active', 1)
            ->orderBy('providers.name', 'ASC')
            ->findAll();
    }

    /**
     * Get provider by code
     */
    public function getByCode(string $code): ?array
    {
        return $this->where('code', $code)->first();
    }

    /**
     * Get provider with type information
     */
    public function getWithType(int $providerId): ?array
    {
        return $this->select('providers.*, provider_types.code as type_code, provider_types.name as type_name')
            ->join('provider_types', 'provider_types.id = providers.provider_type_id')
            ->where('providers.id', $providerId)
            ->first();
    }

    /**
     * Get config schema for a provider
     */
    public function getConfigSchema(int $providerId): array
    {
        $provider = $this->find($providerId);
        if (!$provider || empty($provider['config_schema'])) {
            return ['required' => [], 'optional' => []];
        }
        return json_decode($provider['config_schema'], true) ?? ['required' => [], 'optional' => []];
    }

    /**
     * Get settings schema for a provider
     */
    public function getSettingsSchema(int $providerId): array
    {
        $provider = $this->find($providerId);
        if (!$provider || empty($provider['settings_schema'])) {
            return ['optional' => []];
        }
        return json_decode($provider['settings_schema'], true) ?? ['optional' => []];
    }
}
