<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Traits\RestTrait;
use App\Models\V1\LmsModel;
use CodeIgniter\HTTP\ResponseInterface;

class Lms extends BaseController
{
    use RestTrait;

    protected $lmsModel;

    public function __construct()
    {
        $this->lmsModel = new LmsModel();
    }

    /**
     * Get LMS integrations
     */
    public function getIntegrations(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            $integrations = $this->lmsModel->getIntegrations($data ?? []);
            
            return $this->successResponse($integrations, 'LMS integrations retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve LMS integrations: ' . $e->getMessage());
        }
    }

    /**
     * Add LMS integration
     */
    public function addIntegration(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            if (!$data) {
                return $this->errorResponse('Invalid request data');
            }

            // Validate required fields
            $requiredFields = ['lms_type', 'api_url', 'api_key'];
            if (!$this->validateRequiredFields((array)$data, $requiredFields)) {
                $missing = $this->getMissingFields((array)$data, $requiredFields);
                return $this->errorResponse('Missing required fields: ' . implode(', ', $missing));
            }

            $integrationId = $this->lmsModel->createIntegration($data);
            
            if ($integrationId) {
                return $this->successResponse(['integration_id' => $integrationId], 'LMS integration created successfully');
            } else {
                return $this->errorResponse('Failed to create LMS integration');
            }

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create LMS integration: ' . $e->getMessage());
        }
    }

    /**
     * Update LMS integration
     */
    public function updateIntegration(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            if (!$data) {
                return $this->errorResponse('Invalid request data');
            }

            // Validate required fields
            $requiredFields = ['integration_id'];
            if (!$this->validateRequiredFields((array)$data, $requiredFields)) {
                $missing = $this->getMissingFields((array)$data, $requiredFields);
                return $this->errorResponse('Missing required fields: ' . implode(', ', $missing));
            }

            $result = $this->lmsModel->updateIntegration($data->integration_id, $data);
            
            if ($result) {
                return $this->successResponse($result, 'LMS integration updated successfully');
            } else {
                return $this->errorResponse('Failed to update LMS integration');
            }

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update LMS integration: ' . $e->getMessage());
        }
    }

    /**
     * Delete LMS integration
     */
    public function deleteIntegration(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            if (!$data) {
                return $this->errorResponse('Invalid request data');
            }

            // Validate required fields
            $requiredFields = ['integration_id'];
            if (!$this->validateRequiredFields((array)$data, $requiredFields)) {
                $missing = $this->getMissingFields((array)$data, $requiredFields);
                return $this->errorResponse('Missing required fields: ' . implode(', ', $missing));
            }

            $result = $this->lmsModel->deleteIntegration($data->integration_id);
            
            if ($result) {
                return $this->successResponse(null, 'LMS integration deleted successfully');
            } else {
                return $this->errorResponse('Failed to delete LMS integration');
            }

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete LMS integration: ' . $e->getMessage());
        }
    }

    /**
     * Test LMS connection
     */
    public function testConnection(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            if (!$data) {
                return $this->errorResponse('Invalid request data');
            }

            // Validate required fields
            $requiredFields = ['integration_id'];
            if (!$this->validateRequiredFields((array)$data, $requiredFields)) {
                $missing = $this->getMissingFields((array)$data, $requiredFields);
                return $this->errorResponse('Missing required fields: ' . implode(', ', $missing));
            }

            $result = $this->lmsModel->testConnection($data->integration_id);
            
            if ($result['success']) {
                return $this->successResponse($result, 'LMS connection test successful');
            } else {
                return $this->errorResponse($result['error'] ?? 'LMS connection test failed');
            }

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to test LMS connection: ' . $e->getMessage());
        }
    }

    /**
     * Sync data with LMS
     */
    public function syncData(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            if (!$data) {
                return $this->errorResponse('Invalid request data');
            }

            // Validate required fields
            $requiredFields = ['integration_id', 'sync_type'];
            if (!$this->validateRequiredFields((array)$data, $requiredFields)) {
                $missing = $this->getMissingFields((array)$data, $requiredFields);
                return $this->errorResponse('Missing required fields: ' . implode(', ', $missing));
            }

            $result = $this->lmsModel->syncData($data->integration_id, $data->sync_type, $data->sync_data ?? []);
            
            if ($result['success']) {
                return $this->successResponse($result, 'Data synchronized successfully');
            } else {
                return $this->errorResponse($result['error'] ?? 'Data synchronization failed');
            }

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to sync data: ' . $e->getMessage());
        }
    }

    /**
     * Get LMS courses
     */
    public function getCourses(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            if (!$data) {
                return $this->errorResponse('Invalid request data');
            }

            // Validate required fields
            $requiredFields = ['integration_id'];
            if (!$this->validateRequiredFields((array)$data, $requiredFields)) {
                $missing = $this->getMissingFields((array)$data, $requiredFields);
                return $this->errorResponse('Missing required fields: ' . implode(', ', $missing));
            }

            $courses = $this->lmsModel->getCourses($data->integration_id, $data->filters ?? []);
            
            return $this->successResponse($courses, 'LMS courses retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve LMS courses: ' . $e->getMessage());
        }
    }

    /**
     * Get LMS students
     */
    public function getStudents(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            if (!$data) {
                return $this->errorResponse('Invalid request data');
            }

            // Validate required fields
            $requiredFields = ['integration_id'];
            if (!$this->validateRequiredFields((array)$data, $requiredFields)) {
                $missing = $this->getMissingFields((array)$data, $requiredFields);
                return $this->errorResponse('Missing required fields: ' . implode(', ', $missing));
            }

            $students = $this->lmsModel->getStudents($data->integration_id, $data->filters ?? []);
            
            return $this->successResponse($students, 'LMS students retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve LMS students: ' . $e->getMessage());
        }
    }

    /**
     * Get LMS assignments
     */
    public function getAssignments(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            if (!$data) {
                return $this->errorResponse('Invalid request data');
            }

            // Validate required fields
            $requiredFields = ['integration_id'];
            if (!$this->validateRequiredFields((array)$data, $requiredFields)) {
                $missing = $this->getMissingFields((array)$data, $requiredFields);
                return $this->errorResponse('Missing required fields: ' . implode(', ', $missing));
            }

            $assignments = $this->lmsModel->getAssignments($data->integration_id, $data->filters ?? []);
            
            return $this->successResponse($assignments, 'LMS assignments retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve LMS assignments: ' . $e->getMessage());
        }
    }

    /**
     * Get supported LMS types
     */
    public function getSupportedTypes(): ResponseInterface
    {
        try {
            $types = $this->lmsModel->getSupportedTypes();
            
            return $this->successResponse($types, 'Supported LMS types retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve supported LMS types: ' . $e->getMessage());
        }
    }
}
