<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Traits\RestTrait;
use App\Libraries\EssayGrader as EssayGraderLibrary;
use CodeIgniter\HTTP\ResponseInterface;

class EssayGrader extends BaseController
{
    use RestTrait;

    /**
     * Grade essay endpoint
     */
    public function grade(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            if (!$data) {
                return $this->errorResponse('Invalid request data');
            }

            // Validate required fields
            $requiredFields = ['essay_prompt', 'essay_content', 'student_grade'];
            if (!$this->validateRequiredFields((array)$data, $requiredFields)) {
                $missing = $this->getMissingFields((array)$data, $requiredFields);
                return $this->errorResponse('Missing required fields: ' . implode(', ', $missing));
            }

            // Get model configuration
            $modelConfig = $this->getModelConfig($data->model ?? 'gpt-4o-mini');
            
            // Initialize essay grader
            $grader = new EssayGraderLibrary(
                $modelConfig,
                $data->essay_prompt,
                $data->essay_old ?? '',
                $data->essay_content,
                $data->essay_old_feedback ?? '',
                $data->trait_instructions ?? '',
                $data->student_grade,
                $data->precheck_model ?? null,
                [
                    'content_id' => $data->content_id ?? null,
                    'student_id' => $data->student_id ?? null
                ]
            );

            // Grade the essay
            $result = $grader->gradeEssay();
            
            if ($result['success']) {
                return $this->successResponse($result, 'Essay graded successfully');
            } else {
                return $this->errorResponse($result['error'] ?? 'Failed to grade essay');
            }

        } catch (\Exception $e) {
            return $this->errorResponse('Essay grading failed: ' . $e->getMessage());
        }
    }

    /**
     * Get available models
     */
    public function getModels(): ResponseInterface
    {
        try {
            $models = EssayGraderLibrary::getAvailableModels();
            
            return $this->successResponse($models, 'Available models retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve models: ' . $e->getMessage());
        }
    }

    /**
     * Validate model configuration
     */
    public function validateModel(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            if (!$data) {
                return $this->errorResponse('Invalid request data');
            }

            $isValid = EssayGraderLibrary::validateModelConfig($data);
            
            return $this->successResponse([
                'valid' => $isValid
            ], $isValid ? 'Model configuration is valid' : 'Model configuration is invalid');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to validate model: ' . $e->getMessage());
        }
    }

    /**
     * Get grading history
     */
    public function getHistory(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            $filters = [
                'student_id' => $data->student_id ?? null,
                'content_id' => $data->content_id ?? null,
                'limit' => $data->limit ?? 10,
                'offset' => $data->offset ?? 0
            ];

            $history = $this->getGradingHistory($filters);
            
            return $this->successResponse($history, 'Grading history retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve grading history: ' . $e->getMessage());
        }
    }

    /**
     * Get grading statistics
     */
    public function getStats(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            $filters = [
                'student_id' => $data->student_id ?? null,
                'content_id' => $data->content_id ?? null,
                'date_from' => $data->date_from ?? null,
                'date_to' => $data->date_to ?? null
            ];

            $stats = $this->getGradingStats($filters);
            
            return $this->successResponse($stats, 'Grading statistics retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve grading statistics: ' . $e->getMessage());
        }
    }

    /**
     * Get model configuration
     *
     * @param string $modelName
     * @return array
     */
    private function getModelConfig($modelName)
    {
        $models = [
            'gpt-4' => ['model' => 'gpt-4', 'max_tokens' => 2000, 'temperature' => 0.7],
            'gpt-4-turbo' => ['model' => 'gpt-4-turbo', 'max_tokens' => 2000, 'temperature' => 0.7],
            'gpt-3.5-turbo' => ['model' => 'gpt-3.5-turbo', 'max_tokens' => 2000, 'temperature' => 0.7],
            'gpt-4o' => ['model' => 'gpt-4o', 'max_tokens' => 2000, 'temperature' => 0.7],
            'gpt-4o-mini' => ['model' => 'gpt-4o-mini', 'max_tokens' => 2000, 'temperature' => 0.7]
        ];

        return $models[$modelName] ?? $models['gpt-4o-mini'];
    }

    /**
     * Get grading history
     *
     * @param array $filters
     * @return array
     */
    private function getGradingHistory($filters)
    {
        // This would typically query a database table for grading history
        // For now, return empty array as placeholder
        return [];
    }

    /**
     * Get grading statistics
     *
     * @param array $filters
     * @return array
     */
    private function getGradingStats($filters)
    {
        // This would typically calculate statistics from grading history
        // For now, return empty stats as placeholder
        return [
            'total_gradings' => 0,
            'average_score' => 0,
            'grade_distribution' => [],
            'model_usage' => []
        ];
    }
}
