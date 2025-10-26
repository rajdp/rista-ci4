<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Traits\RestTrait;
use CodeIgniter\HTTP\ResponseInterface;

class ModelConfig extends BaseController
{
    use RestTrait;

    /**
     * Get model configurations
     */
    public function getConfigs(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            $configs = $this->getModelConfigurations($data ?? []);
            
            return $this->successResponse($configs, 'Model configurations retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve model configurations: ' . $e->getMessage());
        }
    }

    /**
     * Update model configuration
     */
    public function updateConfig(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            if (!$data) {
                return $this->errorResponse('Invalid request data');
            }

            // Validate required fields
            $requiredFields = ['model_name', 'config'];
            if (!$this->validateRequiredFields((array)$data, $requiredFields)) {
                $missing = $this->getMissingFields((array)$data, $requiredFields);
                return $this->errorResponse('Missing required fields: ' . implode(', ', $missing));
            }

            $result = $this->updateModelConfiguration($data->model_name, $data->config);
            
            if ($result) {
                return $this->successResponse($result, 'Model configuration updated successfully');
            } else {
                return $this->errorResponse('Failed to update model configuration');
            }

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update model configuration: ' . $e->getMessage());
        }
    }

    /**
     * Get available models
     */
    public function getAvailableModels(): ResponseInterface
    {
        try {
            $models = $this->getSupportedModels();
            
            return $this->successResponse($models, 'Available models retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve available models: ' . $e->getMessage());
        }
    }

    /**
     * Test model configuration
     */
    public function testConfig(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            if (!$data) {
                return $this->errorResponse('Invalid request data');
            }

            // Validate required fields
            $requiredFields = ['model_name', 'config'];
            if (!$this->validateRequiredFields((array)$data, $requiredFields)) {
                $missing = $this->getMissingFields((array)$data, $requiredFields);
                return $this->errorResponse('Missing required fields: ' . implode(', ', $missing));
            }

            $result = $this->testModelConfiguration($data->model_name, $data->config);
            
            if ($result['success']) {
                return $this->successResponse($result, 'Model configuration test successful');
            } else {
                return $this->errorResponse($result['error'] ?? 'Model configuration test failed');
            }

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to test model configuration: ' . $e->getMessage());
        }
    }

    /**
     * Get model statistics
     */
    public function getStats(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            $stats = $this->getModelStatistics($data ?? []);
            
            return $this->successResponse($stats, 'Model statistics retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve model statistics: ' . $e->getMessage());
        }
    }

    /**
     * Reset model configuration
     */
    public function resetConfig(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            if (!$data) {
                return $this->errorResponse('Invalid request data');
            }

            // Validate required fields
            $requiredFields = ['model_name'];
            if (!$this->validateRequiredFields((array)$data, $requiredFields)) {
                $missing = $this->getMissingFields((array)$data, $requiredFields);
                return $this->errorResponse('Missing required fields: ' . implode(', ', $missing));
            }

            $result = $this->resetModelConfiguration($data->model_name);
            
            if ($result) {
                return $this->successResponse(null, 'Model configuration reset successfully');
            } else {
                return $this->errorResponse('Failed to reset model configuration');
            }

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to reset model configuration: ' . $e->getMessage());
        }
    }

    /**
     * Get model configurations
     *
     * @param array $filters
     * @return array
     */
    private function getModelConfigurations($filters = [])
    {
        // This would typically query a database table for model configurations
        // For now, return default configurations
        return [
            'gpt-4' => [
                'model' => 'gpt-4',
                'max_tokens' => 2000,
                'temperature' => 0.7,
                'enabled' => true
            ],
            'gpt-4-turbo' => [
                'model' => 'gpt-4-turbo',
                'max_tokens' => 2000,
                'temperature' => 0.7,
                'enabled' => true
            ],
            'gpt-3.5-turbo' => [
                'model' => 'gpt-3.5-turbo',
                'max_tokens' => 2000,
                'temperature' => 0.7,
                'enabled' => true
            ],
            'gpt-4o' => [
                'model' => 'gpt-4o',
                'max_tokens' => 2000,
                'temperature' => 0.7,
                'enabled' => true
            ],
            'gpt-4o-mini' => [
                'model' => 'gpt-4o-mini',
                'max_tokens' => 2000,
                'temperature' => 0.7,
                'enabled' => true
            ]
        ];
    }

    /**
     * Update model configuration
     *
     * @param string $modelName
     * @param array $config
     * @return bool
     */
    private function updateModelConfiguration($modelName, $config)
    {
        // This would typically update a database table
        // For now, return true as placeholder
        return true;
    }

    /**
     * Get supported models
     *
     * @return array
     */
    private function getSupportedModels()
    {
        return [
            'gpt-4' => 'GPT-4',
            'gpt-4-turbo' => 'GPT-4 Turbo',
            'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
            'gpt-4o' => 'GPT-4o',
            'gpt-4o-mini' => 'GPT-4o Mini',
            'claude-3-opus' => 'Claude 3 Opus',
            'claude-3-sonnet' => 'Claude 3 Sonnet',
            'claude-3-haiku' => 'Claude 3 Haiku'
        ];
    }

    /**
     * Test model configuration
     *
     * @param string $modelName
     * @param array $config
     * @return array
     */
    private function testModelConfiguration($modelName, $config)
    {
        try {
            // This would typically test the model configuration by making a test API call
            // For now, return success as placeholder
            return [
                'success' => true,
                'message' => 'Model configuration test successful',
                'response_time' => '0.5s'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Model configuration test failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get model statistics
     *
     * @param array $filters
     * @return array
     */
    private function getModelStatistics($filters = [])
    {
        // This would typically calculate statistics from model usage logs
        // For now, return empty stats as placeholder
        return [
            'total_requests' => 0,
            'successful_requests' => 0,
            'failed_requests' => 0,
            'average_response_time' => 0,
            'model_usage' => [],
            'error_rate' => 0
        ];
    }

    /**
     * Reset model configuration
     *
     * @param string $modelName
     * @return bool
     */
    private function resetModelConfiguration($modelName)
    {
        // This would typically reset the model configuration to defaults
        // For now, return true as placeholder
        return true;
    }
}
