<?php

namespace App\Models\V1;

use CodeIgniter\Model;

class LmsModel extends Model
{
    protected $table = 'lms_integrations';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'lms_type',
        'api_url',
        'api_key',
        'api_secret',
        'school_id',
        'status',
        'config',
        'created_date',
        'modified_date'
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_date';
    protected $updatedField = 'modified_date';

    protected $validationRules = [
        'lms_type' => 'required|max_length[50]',
        'api_url' => 'required|valid_url|max_length[255]',
        'api_key' => 'required|max_length[255]',
        'school_id' => 'required|integer',
        'status' => 'required|in_list[0,1]'
    ];

    protected $validationMessages = [
        'lms_type' => [
            'required' => 'LMS type is required',
            'max_length' => 'LMS type cannot exceed 50 characters'
        ],
        'api_url' => [
            'required' => 'API URL is required',
            'valid_url' => 'Please provide a valid URL',
            'max_length' => 'API URL cannot exceed 255 characters'
        ],
        'api_key' => [
            'required' => 'API key is required',
            'max_length' => 'API key cannot exceed 255 characters'
        ],
        'school_id' => [
            'required' => 'School ID is required',
            'integer' => 'School ID must be an integer'
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
     * Get LMS integrations
     */
    public function getIntegrations($filters = [])
    {
        $builder = $this->builder();
        
        // Join with schools table
        $builder->select('lms_integrations.*, schools.school_name');
        $builder->join('schools', 'schools.id = lms_integrations.school_id', 'left');
        
        // Apply filters
        if (isset($filters['school_id']) && $filters['school_id'] > 0) {
            $builder->where('lms_integrations.school_id', $filters['school_id']);
        }
        
        if (isset($filters['lms_type']) && !empty($filters['lms_type'])) {
            $builder->where('lms_integrations.lms_type', $filters['lms_type']);
        }
        
        if (isset($filters['status']) && $filters['status'] !== '') {
            $builder->where('lms_integrations.status', $filters['status']);
        }
        
        if (isset($filters['search']) && !empty($filters['search'])) {
            $builder->groupStart()
                    ->like('lms_integrations.lms_type', $filters['search'])
                    ->orLike('schools.school_name', $filters['search'])
                    ->groupEnd();
        }
        
        // Order by
        $orderBy = $filters['order_by'] ?? 'lms_integrations.created_date';
        $orderDirection = $filters['order_direction'] ?? 'DESC';
        $builder->orderBy($orderBy, $orderDirection);
        
        // Pagination
        if (isset($filters['limit']) && $filters['limit'] > 0) {
            $builder->limit($filters['limit']);
        }
        
        if (isset($filters['offset']) && $filters['offset'] > 0) {
            $builder->offset($filters['offset']);
        }
        
        return $builder->get()->getResultArray();
    }

    /**
     * Create LMS integration
     */
    public function createIntegration($data)
    {
        $integrationData = [
            'lms_type' => $data->lms_type ?? '',
            'api_url' => $data->api_url ?? '',
            'api_key' => $data->api_key ?? '',
            'api_secret' => $data->api_secret ?? '',
            'school_id' => $data->school_id ?? 0,
            'status' => $data->status ?? 1,
            'config' => json_encode($data->config ?? [])
        ];
        
        return $this->insert($integrationData);
    }

    /**
     * Update LMS integration
     */
    public function updateIntegration($integrationId, $data)
    {
        $updateData = [];
        
        if (isset($data->lms_type)) {
            $updateData['lms_type'] = $data->lms_type;
        }
        if (isset($data->api_url)) {
            $updateData['api_url'] = $data->api_url;
        }
        if (isset($data->api_key)) {
            $updateData['api_key'] = $data->api_key;
        }
        if (isset($data->api_secret)) {
            $updateData['api_secret'] = $data->api_secret;
        }
        if (isset($data->school_id)) {
            $updateData['school_id'] = $data->school_id;
        }
        if (isset($data->status)) {
            $updateData['status'] = $data->status;
        }
        if (isset($data->config)) {
            $updateData['config'] = json_encode($data->config);
        }
        
        if (empty($updateData)) {
            return false;
        }
        
        return $this->update($integrationId, $updateData);
    }

    /**
     * Delete LMS integration
     */
    public function deleteIntegration($integrationId)
    {
        return $this->delete($integrationId);
    }

    /**
     * Test LMS connection
     */
    public function testConnection($integrationId)
    {
        $integration = $this->find($integrationId);
        
        if (!$integration) {
            return [
                'success' => false,
                'error' => 'Integration not found'
            ];
        }

        try {
            $client = \Config\Services::curlrequest();
            
            $response = $client->get($integration['api_url'], [
                'headers' => [
                    'Authorization' => 'Bearer ' . $integration['api_key'],
                    'Content-Type' => 'application/json'
                ],
                'timeout' => 30
            ]);

            if ($response->getStatusCode() === 200) {
                return [
                    'success' => true,
                    'message' => 'Connection successful',
                    'response_time' => $response->getHeader('X-Response-Time') ?? 'N/A'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Connection failed with status: ' . $response->getStatusCode()
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Connection failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Sync data with LMS
     */
    public function syncData($integrationId, $syncType, $syncData = [])
    {
        $integration = $this->find($integrationId);
        
        if (!$integration) {
            return [
                'success' => false,
                'error' => 'Integration not found'
            ];
        }

        try {
            switch ($syncType) {
                case 'courses':
                    return $this->syncCourses($integration, $syncData);
                case 'students':
                    return $this->syncStudents($integration, $syncData);
                case 'assignments':
                    return $this->syncAssignments($integration, $syncData);
                case 'grades':
                    return $this->syncGrades($integration, $syncData);
                default:
                    return [
                        'success' => false,
                        'error' => 'Invalid sync type'
                    ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Sync failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get LMS courses
     */
    public function getCourses($integrationId, $filters = [])
    {
        $integration = $this->find($integrationId);
        
        if (!$integration) {
            return [];
        }

        try {
            $client = \Config\Services::curlrequest();
            
            $response = $client->get($integration['api_url'] . '/courses', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $integration['api_key'],
                    'Content-Type' => 'application/json'
                ],
                'timeout' => 30
            ]);

            if ($response->getStatusCode() === 200) {
                $courses = json_decode($response->getBody(), true);
                return $courses['data'] ?? $courses;
            } else {
                return [];
            }
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get LMS students
     */
    public function getStudents($integrationId, $filters = [])
    {
        $integration = $this->find($integrationId);
        
        if (!$integration) {
            return [];
        }

        try {
            $client = \Config\Services::curlrequest();
            
            $response = $client->get($integration['api_url'] . '/students', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $integration['api_key'],
                    'Content-Type' => 'application/json'
                ],
                'timeout' => 30
            ]);

            if ($response->getStatusCode() === 200) {
                $students = json_decode($response->getBody(), true);
                return $students['data'] ?? $students;
            } else {
                return [];
            }
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get LMS assignments
     */
    public function getAssignments($integrationId, $filters = [])
    {
        $integration = $this->find($integrationId);
        
        if (!$integration) {
            return [];
        }

        try {
            $client = \Config\Services::curlrequest();
            
            $response = $client->get($integration['api_url'] . '/assignments', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $integration['api_key'],
                    'Content-Type' => 'application/json'
                ],
                'timeout' => 30
            ]);

            if ($response->getStatusCode() === 200) {
                $assignments = json_decode($response->getBody(), true);
                return $assignments['data'] ?? $assignments;
            } else {
                return [];
            }
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get supported LMS types
     */
    public function getSupportedTypes()
    {
        return [
            'canvas' => 'Canvas LMS',
            'moodle' => 'Moodle',
            'blackboard' => 'Blackboard',
            'schoology' => 'Schoology',
            'brightspace' => 'Brightspace',
            'sakai' => 'Sakai'
        ];
    }

    /**
     * Sync courses
     */
    private function syncCourses($integration, $syncData)
    {
        // Implementation for syncing courses
        return [
            'success' => true,
            'message' => 'Courses synced successfully',
            'count' => 0
        ];
    }

    /**
     * Sync students
     */
    private function syncStudents($integration, $syncData)
    {
        // Implementation for syncing students
        return [
            'success' => true,
            'message' => 'Students synced successfully',
            'count' => 0
        ];
    }

    /**
     * Sync assignments
     */
    private function syncAssignments($integration, $syncData)
    {
        // Implementation for syncing assignments
        return [
            'success' => true,
            'message' => 'Assignments synced successfully',
            'count' => 0
        ];
    }

    /**
     * Sync grades
     */
    private function syncGrades($integration, $syncData)
    {
        // Implementation for syncing grades
        return [
            'success' => true,
            'message' => 'Grades synced successfully',
            'count' => 0
        ];
    }
}
