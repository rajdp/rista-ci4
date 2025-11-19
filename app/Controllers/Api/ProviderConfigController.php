<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\ProviderTypeModel;
use App\Models\ProviderModel;
use App\Models\SchoolProviderConfigModel;
use App\Models\ProviderUsageLogModel;
use App\Libraries\Encryption\ProviderCredentialEncryption;
use App\Services\Payment\StripeProvider;
use App\Services\Payment\ForteProvider;

class ProviderConfigController extends ResourceController
{
    protected $format = 'json';
    protected ProviderTypeModel $providerTypeModel;
    protected ProviderModel $providerModel;
    protected SchoolProviderConfigModel $configModel;
    protected ProviderUsageLogModel $usageLogModel;
    protected ProviderCredentialEncryption $encryption;

    public function __construct()
    {
        $this->providerTypeModel = new ProviderTypeModel();
        $this->providerModel = new ProviderModel();
        $this->configModel = new SchoolProviderConfigModel();
        $this->usageLogModel = new ProviderUsageLogModel();
        $this->encryption = new ProviderCredentialEncryption();
    }

    /**
     * Get all provider types
     * GET /api/provider-types
     */
    public function getTypes()
    {
        $types = $this->providerTypeModel->getActiveTypes();
        return $this->respond($types);
    }

    /**
     * Get all providers or filter by type
     * GET /api/providers?type=payment
     */
    public function getProviders()
    {
        $type = $this->request->getGet('type');

        if ($type) {
            $providers = $this->providerModel->getByTypeCode($type);
        } else {
            $providers = $this->providerModel->getActiveProviders();
        }

        // Parse JSON schemas
        foreach ($providers as &$provider) {
            $provider['config_schema'] = json_decode($provider['config_schema'] ?? '{}', true);
            $provider['settings_schema'] = json_decode($provider['settings_schema'] ?? '{}', true);
        }

        return $this->respond($providers);
    }

    /**
     * Get school's provider configurations
     * GET /api/schools/{schoolId}/providers?type=payment
     */
    public function getSchoolProviders($schoolId = null)
    {
        if (!$schoolId) {
            return $this->respond(['error' => 'School ID required'], 400);
        }

        $type = $this->request->getGet('type');
        $configs = $this->configModel->getSchoolConfigs((int)$schoolId, $type);

        // Format response (don't include actual credentials)
        foreach ($configs as &$config) {
            $config['has_credentials'] = !empty($config['credentials']);
            $config['settings'] = json_decode($config['settings'] ?? '{}', true);
            unset($config['credentials']); // Never return credentials
            unset($config['webhook_secret']);
        }

        return $this->respond($configs);
    }

    /**
     * Get specific provider configuration
     * GET /api/schools/{schoolId}/providers/{providerId}
     */
    public function getConfig($schoolId = null, $providerId = null)
    {
        if (!$schoolId || !$providerId) {
            return $this->respond(['error' => 'School ID and Provider ID required'], 400);
        }

        $config = $this->configModel->getConfig((int)$schoolId, (int)$providerId);

        if (!$config) {
            return $this->respond(['error' => 'Configuration not found'], 404);
        }

        // Format response
        $config['has_credentials'] = !empty($config['credentials']);
        $config['settings'] = json_decode($config['settings'] ?? '{}', true);
        unset($config['credentials']);
        unset($config['webhook_secret']);

        return $this->respond($config);
    }

    /**
     * Save provider configuration
     * POST /api/schools/{schoolId}/providers
     */
    public function saveConfig($schoolId = null)
    {
        if (!$schoolId) {
            return $this->respond(['error' => 'School ID required'], 400);
        }

        $data = $this->request->getJSON(true);

        if (empty($data['provider_id'])) {
            return $this->respond(['error' => 'Provider ID required'], 400);
        }

        // Get provider schema
        $provider = $this->providerModel->getWithType((int)$data['provider_id']);
        if (!$provider) {
            return $this->respond(['error' => 'Provider not found'], 404);
        }

        // Validate credentials if provided
        if (!empty($data['credentials'])) {
            $schema = json_decode($provider['config_schema'] ?? '{}', true);
            $validation = $this->encryption->validateCredentials($data['credentials'], $schema);

            if (!$validation['valid']) {
                return $this->respond([
                    'error' => 'Invalid credentials',
                    'missing' => $validation['missing'],
                    'validation_errors' => $validation['errors']
                ], 400);
            }

            // Encrypt credentials
            $data['credentials'] = $this->encryption->encryptCredentials($data['credentials']);
        } else {
            // If no credentials provided, keep existing ones
            $existing = $this->configModel->getConfig((int)$schoolId, (int)$data['provider_id']);
            if ($existing) {
                $data['credentials'] = $existing['credentials'];
            } else {
                return $this->respond(['error' => 'Credentials required for new configuration'], 400);
            }
        }

        // Add audit fields
        $data['updated_by'] = session()->get('userId') ?? null;

        try {
            $configId = $this->configModel->saveConfig((int)$schoolId, (int)$data['provider_id'], $data);

            return $this->respond([
                'success' => true,
                'message' => 'Provider configuration saved',
                'config_id' => $configId
            ], 201);
        } catch (\Exception $e) {
            log_message('error', 'Failed to save provider config: ' . $e->getMessage());
            return $this->respond([
                'success' => false,
                'error' => 'Failed to save configuration'
            ], 500);
        }
    }

    /**
     * Update provider configuration
     * PUT /api/schools/{schoolId}/providers/{providerId}
     */
    public function updateConfig($schoolId = null, $providerId = null)
    {
        if (!$schoolId || !$providerId) {
            return $this->respond(['error' => 'School ID and Provider ID required'], 400);
        }

        $existing = $this->configModel->getConfig((int)$schoolId, (int)$providerId);
        if (!$existing) {
            return $this->respond(['error' => 'Configuration not found'], 404);
        }

        $data = $this->request->getJSON(true);
        $data['provider_id'] = (int)$providerId;

        // Same logic as saveConfig
        return $this->saveConfig($schoolId);
    }

    /**
     * Test provider connection
     * POST /api/schools/{schoolId}/providers/{providerId}/test
     */
    public function testConnection($schoolId = null, $providerId = null)
    {
        if (!$schoolId || !$providerId) {
            return $this->respond(['error' => 'School ID and Provider ID required'], 400);
        }

        $data = $this->request->getJSON(true);

        // Get provider info
        $provider = $this->providerModel->getWithType((int)$providerId);
        if (!$provider) {
            return $this->respond(['error' => 'Provider not found'], 404);
        }

        // Get credentials (either from request or from saved config)
        if (!empty($data['credentials'])) {
            $credentials = $data['credentials'];
        } else {
            $config = $this->configModel->getConfig((int)$schoolId, (int)$providerId);
            if (!$config || empty($config['credentials'])) {
                return $this->respond(['error' => 'No credentials to test'], 400);
            }
            $credentials = $this->encryption->decryptCredentials($config['credentials']);
        }

        try {
            // Instantiate provider and test connection
            $providerInstance = $this->instantiateProvider($provider['code'], $credentials);
            $result = $providerInstance->testConnection();

            // Update test status in database
            $this->configModel->updateTestStatus((int)$schoolId, (int)$providerId, [
                'last_test_at' => date('Y-m-d H:i:s'),
                'last_test_status' => $result['success'] ? 'success' : 'failed',
                'last_test_message' => $result['message'] ?? ($result['success'] ? 'Connection successful' : 'Connection failed'),
                'last_test_by' => session()->get('userId') ?? null
            ]);

            return $this->respond([
                'success' => $result['success'],
                'message' => $result['message'] ?? 'Test completed',
                'details' => $result['data'] ?? null
            ]);

        } catch (\Exception $e) {
            // Update failed test status
            $this->configModel->updateTestStatus((int)$schoolId, (int)$providerId, [
                'last_test_at' => date('Y-m-d H:i:s'),
                'last_test_status' => 'failed',
                'last_test_message' => $e->getMessage(),
                'last_test_by' => session()->get('userId') ?? null
            ]);

            return $this->respond([
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Delete provider configuration
     * DELETE /api/schools/{schoolId}/providers/{providerId}
     */
    public function deleteConfig($schoolId = null, $providerId = null)
    {
        if (!$schoolId || !$providerId) {
            return $this->respond(['error' => 'School ID and Provider ID required'], 400);
        }

        $config = $this->configModel->getConfig((int)$schoolId, (int)$providerId);
        if (!$config) {
            return $this->respond(['error' => 'Configuration not found'], 404);
        }

        $this->configModel->delete($config['id']);

        return $this->respond([
            'success' => true,
            'message' => 'Provider configuration deleted'
        ]);
    }

    /**
     * Check if feature is enabled for school
     * GET /api/schools/{schoolId}/features/{type}/enabled
     */
    public function isFeatureEnabled($schoolId = null, $type = null)
    {
        if (!$schoolId || !$type) {
            return $this->respond(['error' => 'School ID and feature type required'], 400);
        }

        $config = $this->configModel->getEnabledProvider((int)$schoolId, $type, 1);
        $enabled = $config !== null && $config['is_enabled'];

        return $this->respond(['enabled' => $enabled]);
    }

    /**
     * Get provider usage statistics
     * GET /api/schools/{schoolId}/providers/usage
     */
    public function getUsageStats($schoolId = null)
    {
        if (!$schoolId) {
            return $this->respond(['error' => 'School ID required'], 400);
        }

        $providerId = $this->request->getGet('provider_id');
        $from = $this->request->getGet('from');
        $to = $this->request->getGet('to');

        $stats = $this->usageLogModel->getUsageStats(
            (int)$schoolId,
            $providerId ? (int)$providerId : null,
            $from,
            $to
        );

        return $this->respond($stats);
    }

    /**
     * Get provider usage logs
     * GET /api/schools/{schoolId}/providers/logs
     */
    public function getUsageLogs($schoolId = null)
    {
        if (!$schoolId) {
            return $this->respond(['error' => 'School ID required'], 400);
        }

        $filters = [
            'provider_id' => $this->request->getGet('provider_id'),
            'action_type' => $this->request->getGet('action_type'),
            'status' => $this->request->getGet('status'),
            'from_date' => $this->request->getGet('from'),
            'to_date' => $this->request->getGet('to'),
            'limit' => (int)($this->request->getGet('limit') ?? 100),
            'offset' => (int)($this->request->getGet('offset') ?? 0)
        ];

        $filters = array_filter($filters);

        $logs = $this->usageLogModel->getSchoolLogs((int)$schoolId, $filters);

        return $this->respond($logs);
    }

    /**
     * Instantiate provider for testing
     */
    protected function instantiateProvider(string $code, array $credentials): object
    {
        switch ($code) {
            case 'stripe':
                return new StripeProvider($credentials);
            case 'forte':
                return new ForteProvider($credentials);
            default:
                throw new \RuntimeException('Unsupported provider: ' . $code);
        }
    }
}
