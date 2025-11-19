<?php

namespace App\Models;

use CodeIgniter\Model;

class SchoolProviderConfigModel extends Model
{
    protected $table = 'school_provider_config';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'school_id',
        'provider_id',
        'is_enabled',
        'credentials',
        'settings',
        'priority',
        'webhook_url',
        'webhook_secret',
        'last_test_at',
        'last_test_status',
        'last_test_message',
        'last_test_by',
        'monthly_limit',
        'monthly_usage',
        'last_reset_at',
        'created_by',
        'updated_by'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'school_id' => 'required|integer',
        'provider_id' => 'required|integer',
        'credentials' => 'required'
    ];

    /**
     * Get all provider configs for a school
     */
    public function getSchoolConfigs(int $schoolId, ?string $providerType = null): array
    {
        $builder = $this->select('school_provider_config.*, providers.code as provider_code, providers.name as provider_name, provider_types.code as type_code')
            ->join('providers', 'providers.id = school_provider_config.provider_id')
            ->join('provider_types', 'provider_types.id = providers.provider_type_id')
            ->where('school_provider_config.school_id', $schoolId);

        if ($providerType) {
            $builder->where('provider_types.code', $providerType);
        }

        return $builder->orderBy('school_provider_config.priority', 'ASC')
            ->findAll();
    }

    /**
     * Get enabled provider for a school by type (returns primary provider)
     */
    public function getEnabledProvider(int $schoolId, string $providerType, int $priority = 1): ?array
    {
        return $this->select('school_provider_config.*, providers.code as provider_code, providers.name as provider_name')
            ->join('providers', 'providers.id = school_provider_config.provider_id')
            ->join('provider_types', 'provider_types.id = providers.provider_type_id')
            ->where('school_provider_config.school_id', $schoolId)
            ->where('school_provider_config.is_enabled', 1)
            ->where('provider_types.code', $providerType)
            ->where('school_provider_config.priority', $priority)
            ->first();
    }

    /**
     * Get all enabled providers for a school by type (for fallback support)
     */
    public function getAllEnabledProviders(int $schoolId, string $providerType): array
    {
        return $this->select('school_provider_config.*, providers.code as provider_code, providers.name as provider_name')
            ->join('providers', 'providers.id = school_provider_config.provider_id')
            ->join('provider_types', 'provider_types.id = providers.provider_type_id')
            ->where('school_provider_config.school_id', $schoolId)
            ->where('school_provider_config.is_enabled', 1)
            ->where('provider_types.code', $providerType)
            ->orderBy('school_provider_config.priority', 'ASC')
            ->findAll();
    }

    /**
     * Get specific config for school and provider
     */
    public function getConfig(int $schoolId, int $providerId): ?array
    {
        return $this->select('school_provider_config.*, providers.code as provider_code, providers.name as provider_name')
            ->join('providers', 'providers.id = school_provider_config.provider_id')
            ->where('school_provider_config.school_id', $schoolId)
            ->where('school_provider_config.provider_id', $providerId)
            ->first();
    }

    /**
     * Update test status
     */
    public function updateTestStatus(int $schoolId, int $providerId, array $status): bool
    {
        return $this->where('school_id', $schoolId)
            ->where('provider_id', $providerId)
            ->set($status)
            ->update();
    }

    /**
     * Check if school has provider configured
     */
    public function hasProvider(int $schoolId, int $providerId): bool
    {
        return $this->where('school_id', $schoolId)
            ->where('provider_id', $providerId)
            ->countAllResults() > 0;
    }

    /**
     * Increment monthly usage
     */
    public function incrementUsage(int $configId, int $units = 1): bool
    {
        return $this->set('monthly_usage', "monthly_usage + {$units}", false)
            ->where('id', $configId)
            ->update();
    }

    /**
     * Reset monthly usage for all configs
     */
    public function resetMonthlyUsage(): int
    {
        return $this->set([
            'monthly_usage' => 0,
            'last_reset_at' => date('Y-m-d H:i:s')
        ])->update();
    }

    /**
     * Save or update provider config
     */
    public function saveConfig(int $schoolId, int $providerId, array $data): int
    {
        $existing = $this->getConfig($schoolId, $providerId);

        $configData = [
            'school_id' => $schoolId,
            'provider_id' => $providerId,
            'is_enabled' => $data['is_enabled'] ?? false,
            'credentials' => $data['credentials'],
            'settings' => isset($data['settings']) ? json_encode($data['settings']) : null,
            'priority' => $data['priority'] ?? 1,
            'webhook_url' => $data['webhook_url'] ?? null,
            'webhook_secret' => $data['webhook_secret'] ?? null,
            'updated_by' => $data['updated_by'] ?? null
        ];

        if ($existing) {
            $this->update($existing['id'], $configData);
            return $existing['id'];
        } else {
            $configData['created_by'] = $data['created_by'] ?? $data['updated_by'] ?? null;
            return $this->insert($configData);
        }
    }
}
