<?php

namespace App\Services\StudentPortal;

use App\Models\StudentSpecialRequestModel;
use App\Models\SpecialRequestTypeModel;
use App\Models\StudentPortalAuditModel;

class SpecialRequestService
{
    protected $specialRequestModel;
    protected $requestTypeModel;
    protected $auditModel;

    public function __construct()
    {
        $this->specialRequestModel = new StudentSpecialRequestModel();
        $this->requestTypeModel = new SpecialRequestTypeModel();
        $this->auditModel = new StudentPortalAuditModel();
    }

    /**
     * Create special request
     *
     * @param int $studentId
     * @param int $schoolId
     * @param array $requestData ['request_type', 'title', 'description', 'priority'?, 'related_class_id'?, 'related_course_id'?]
     * @return array
     */
    public function createSpecialRequest(int $studentId, int $schoolId, array $requestData): array
    {
        try {
            // Validate request type exists and is active
            $requestType = $this->requestTypeModel->getByKey($schoolId, $requestData['request_type']);

            if (!$requestType) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'Invalid request type',
                ];
            }

            if (!$requestType['is_active']) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'This request type is currently not available',
                ];
            }

            $data = [
                'school_id' => $schoolId,
                'student_id' => $studentId,
                'status' => 'pending',
                'request_type' => $requestData['request_type'],
                'title' => $requestData['title'],
                'description' => $requestData['description'],
                'priority' => $requestData['priority'] ?? 'normal',
                'related_class_id' => $requestData['related_class_id'] ?? null,
                'related_course_id' => $requestData['related_course_id'] ?? null,
            ];

            // Auto-assign if configured
            if ($requestType['auto_assign_to_role'] && $requestType['requires_admin_approval']) {
                // Would need logic to find admin with that role
                // For now, we'll leave it unassigned
            }

            $requestId = $this->specialRequestModel->insert($data);

            if (!$requestId) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'Failed to create request: ' . json_encode($this->specialRequestModel->errors()),
                ];
            }

            // Log the action
            $this->auditModel->logAction(
                $schoolId,
                'special_request',
                $requestId,
                'create',
                $studentId,
                5, // Student role
                ['request_type' => $requestData['request_type'], 'title' => $requestData['title']],
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            );

            log_message('info', "SpecialRequestService: Created request {$requestId} for student {$studentId}");

            return [
                'success' => true,
                'data' => [
                    'request_id' => $requestId,
                    'status' => 'pending',
                ],
                'error' => null,
            ];
        } catch (\Exception $e) {
            log_message('error', 'SpecialRequestService::createSpecialRequest - ' . $e->getMessage());
            return [
                'success' => false,
                'data' => null,
                'error' => 'An error occurred while creating the request',
            ];
        }
    }

    /**
     * List special requests
     *
     * @param array $filters ['school_id', 'student_id', 'status', 'request_type', 'priority', 'assigned_to', 'limit', 'offset']
     * @return array
     */
    public function listSpecialRequests(array $filters): array
    {
        try {
            $schoolId = $filters['school_id'] ?? null;
            $studentId = $filters['student_id'] ?? null;
            $limit = $filters['limit'] ?? 50;
            $offset = $filters['offset'] ?? 0;

            if (!$schoolId) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'School ID is required',
                ];
            }

            if ($studentId) {
                $requests = $this->specialRequestModel->getStudentRequests($schoolId, $studentId, $filters, $limit, $offset);
            } else {
                $builder = $this->specialRequestModel->where('school_id', $schoolId);

                if (isset($filters['status'])) {
                    $builder->where('status', $filters['status']);
                }
                if (isset($filters['request_type'])) {
                    $builder->where('request_type', $filters['request_type']);
                }
                if (isset($filters['priority'])) {
                    $builder->where('priority', $filters['priority']);
                }
                if (isset($filters['assigned_to'])) {
                    $builder->where('assigned_to', $filters['assigned_to']);
                }

                $requests = $builder->orderBy('priority', 'DESC')
                                   ->orderBy('created_at', 'DESC')
                                   ->limit($limit, $offset)
                                   ->findAll();
            }

            return [
                'success' => true,
                'data' => $requests,
                'error' => null,
            ];
        } catch (\Exception $e) {
            log_message('error', 'SpecialRequestService::listSpecialRequests - ' . $e->getMessage());
            return [
                'success' => false,
                'data' => null,
                'error' => 'An error occurred while retrieving requests',
            ];
        }
    }

    /**
     * Get single special request
     *
     * @param int $requestId
     * @return array
     */
    public function getSpecialRequest(int $requestId): array
    {
        try {
            $request = $this->specialRequestModel->find($requestId);

            if (!$request) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'Request not found',
                ];
            }

            return [
                'success' => true,
                'data' => $request,
                'error' => null,
            ];
        } catch (\Exception $e) {
            log_message('error', 'SpecialRequestService::getSpecialRequest - ' . $e->getMessage());
            return [
                'success' => false,
                'data' => null,
                'error' => 'An error occurred while retrieving the request',
            ];
        }
    }

    /**
     * Update request status
     *
     * @param int $requestId
     * @param string $status
     * @param int $adminId
     * @param string|null $notes
     * @return array
     */
    public function updateRequestStatus(int $requestId, string $status, int $adminId, ?string $notes = null): array
    {
        try {
            $request = $this->specialRequestModel->find($requestId);

            if (!$request) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'Request not found',
                ];
            }

            // Validate status transition
            $validStatuses = ['pending', 'in_review', 'approved', 'rejected', 'needs_info', 'completed', 'cancelled'];
            if (!in_array($status, $validStatuses)) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'Invalid status',
                ];
            }

            // Update status
            $this->specialRequestModel->updateStatus($requestId, $status, $adminId, $notes);

            // Log the action
            $this->auditModel->logAction(
                $request['school_id'],
                'special_request',
                $requestId,
                'status_update',
                $adminId,
                2, // Admin role
                ['old_status' => $request['status'], 'new_status' => $status],
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            );

            log_message('info', "SpecialRequestService: Updated request {$requestId} status to {$status} by admin {$adminId}");

            return [
                'success' => true,
                'data' => ['request_id' => $requestId, 'status' => $status],
                'error' => null,
            ];
        } catch (\Exception $e) {
            log_message('error', 'SpecialRequestService::updateRequestStatus - ' . $e->getMessage());
            return [
                'success' => false,
                'data' => null,
                'error' => 'An error occurred while updating the request',
            ];
        }
    }

    /**
     * Assign request to admin
     *
     * @param int $requestId
     * @param int $adminId
     * @param int $assignedBy
     * @return array
     */
    public function assignRequest(int $requestId, int $adminId, int $assignedBy): array
    {
        try {
            $request = $this->specialRequestModel->find($requestId);

            if (!$request) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'Request not found',
                ];
            }

            $this->specialRequestModel->assignRequest($requestId, $adminId);

            // Log the action
            $this->auditModel->logAction(
                $request['school_id'],
                'special_request',
                $requestId,
                'assign',
                $assignedBy,
                2, // Admin role
                ['assigned_to' => $adminId],
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            );

            log_message('info', "SpecialRequestService: Assigned request {$requestId} to admin {$adminId}");

            return [
                'success' => true,
                'data' => ['request_id' => $requestId, 'assigned_to' => $adminId],
                'error' => null,
            ];
        } catch (\Exception $e) {
            log_message('error', 'SpecialRequestService::assignRequest - ' . $e->getMessage());
            return [
                'success' => false,
                'data' => null,
                'error' => 'An error occurred while assigning the request',
            ];
        }
    }

    /**
     * Get request type configuration
     *
     * @param int $schoolId
     * @return array
     */
    public function getRequestTypeConfig(int $schoolId): array
    {
        try {
            $types = $this->requestTypeModel->getActiveTypes($schoolId);

            return [
                'success' => true,
                'data' => $types,
                'error' => null,
            ];
        } catch (\Exception $e) {
            log_message('error', 'SpecialRequestService::getRequestTypeConfig - ' . $e->getMessage());
            return [
                'success' => false,
                'data' => null,
                'error' => 'An error occurred while retrieving request types',
            ];
        }
    }

    /**
     * Update request type configuration (admin)
     *
     * @param int $typeId
     * @param array $data
     * @return array
     */
    public function updateRequestType(int $typeId, array $data): array
    {
        try {
            $result = $this->requestTypeModel->update($typeId, $data);

            if (!$result) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'Failed to update request type',
                ];
            }

            log_message('info', "SpecialRequestService: Updated request type {$typeId}");

            return [
                'success' => true,
                'data' => ['type_id' => $typeId],
                'error' => null,
            ];
        } catch (\Exception $e) {
            log_message('error', 'SpecialRequestService::updateRequestType - ' . $e->getMessage());
            return [
                'success' => false,
                'data' => null,
                'error' => 'An error occurred while updating request type',
            ];
        }
    }

    /**
     * Create new request type (admin)
     *
     * @param int $schoolId
     * @param array $data
     * @return array
     */
    public function createRequestType(int $schoolId, array $data): array
    {
        try {
            $data['school_id'] = $schoolId;
            $typeId = $this->requestTypeModel->insert($data);

            if (!$typeId) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'Failed to create request type: ' . json_encode($this->requestTypeModel->errors()),
                ];
            }

            log_message('info', "SpecialRequestService: Created request type {$typeId} for school {$schoolId}");

            return [
                'success' => true,
                'data' => ['type_id' => $typeId],
                'error' => null,
            ];
        } catch (\Exception $e) {
            log_message('error', 'SpecialRequestService::createRequestType - ' . $e->getMessage());
            return [
                'success' => false,
                'data' => null,
                'error' => 'An error occurred while creating request type',
            ];
        }
    }

    /**
     * Get all request types for school (including inactive)
     *
     * @param int $schoolId
     * @return array
     */
    public function getAllRequestTypes(int $schoolId): array
    {
        try {
            $types = $this->requestTypeModel->getSchoolTypes($schoolId);

            return [
                'success' => true,
                'data' => $types,
                'error' => null,
            ];
        } catch (\Exception $e) {
            log_message('error', 'SpecialRequestService::getAllRequestTypes - ' . $e->getMessage());
            return [
                'success' => false,
                'data' => null,
                'error' => 'An error occurred while retrieving request types',
            ];
        }
    }
}
