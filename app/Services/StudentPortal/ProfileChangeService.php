<?php

namespace App\Services\StudentPortal;

use App\Models\StudentProfileChangeRequestModel;
use App\Models\StudentPortalAuditModel;
use CodeIgniter\Database\BaseConnection;

class ProfileChangeService
{
    protected $profileChangeModel;
    protected $auditModel;
    protected $db;

    public function __construct()
    {
        $this->profileChangeModel = new StudentProfileChangeRequestModel();
        $this->auditModel = new StudentPortalAuditModel();
        $this->db = \Config\Database::connect();
    }

    /**
     * Create a new profile change request
     *
     * @param int $studentId
     * @param int $schoolId
     * @param array $changes Format: ['field_name' => ['old' => 'value', 'new' => 'value']]
     * @param string|null $reason
     * @return array ['success' => bool, 'data' => mixed, 'error' => string|null]
     */
    public function createProfileChangeRequest(int $studentId, int $schoolId, array $changes, ?string $reason = null): array
    {
        try {
            // Validate changes format
            if (empty($changes)) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'No changes provided',
                ];
            }

            // Determine request type based on fields
            $requestType = $this->determineRequestType($changes);

            $data = [
                'school_id' => $schoolId,
                'student_id' => $studentId,
                'status' => 'pending',
                'request_type' => $requestType,
                'changes_json' => json_encode($changes),
                'reason' => $reason,
            ];

            $requestId = $this->profileChangeModel->insert($data);

            if (!$requestId) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'Failed to create request: ' . json_encode($this->profileChangeModel->errors()),
                ];
            }

            // Log the action
            $this->auditModel->logAction(
                $schoolId,
                'profile_change_request',
                $requestId,
                'create',
                $studentId,
                5, // Student role
                $changes,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            );

            log_message('info', "ProfileChangeService: Created request {$requestId} for student {$studentId}");

            return [
                'success' => true,
                'data' => [
                    'request_id' => $requestId,
                    'status' => 'pending',
                ],
                'error' => null,
            ];
        } catch (\Exception $e) {
            log_message('error', 'ProfileChangeService::createProfileChangeRequest - ' . $e->getMessage());
            return [
                'success' => false,
                'data' => null,
                'error' => 'An error occurred while creating the request',
            ];
        }
    }

    /**
     * List profile change requests
     *
     * @param array $filters ['school_id', 'student_id', 'status', 'limit', 'offset']
     * @return array
     */
    public function listProfileChangeRequests(array $filters): array
    {
        try {
            $schoolId = $filters['school_id'] ?? null;
            $studentId = $filters['student_id'] ?? null;
            $status = $filters['status'] ?? null;
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
                $requests = $this->profileChangeModel->getStudentRequests($schoolId, $studentId, $status, $limit, $offset);
            } elseif ($status) {
                $requests = $this->profileChangeModel->getRequestsByStatus($schoolId, $status, $limit, $offset);
            } else {
                $requests = $this->profileChangeModel->where('school_id', $schoolId)
                    ->orderBy('created_at', 'DESC')
                    ->limit($limit, $offset)
                    ->findAll();
            }

            // Decode JSON fields
            foreach ($requests as &$request) {
                $request['changes'] = json_decode($request['changes_json'], true);
                unset($request['changes_json']);
            }

            return [
                'success' => true,
                'data' => $requests,
                'error' => null,
            ];
        } catch (\Exception $e) {
            log_message('error', 'ProfileChangeService::listProfileChangeRequests - ' . $e->getMessage());
            return [
                'success' => false,
                'data' => null,
                'error' => 'An error occurred while retrieving requests',
            ];
        }
    }

    /**
     * Get a single profile change request
     *
     * @param int $requestId
     * @return array
     */
    public function getProfileChangeRequest(int $requestId): array
    {
        try {
            $request = $this->profileChangeModel->find($requestId);

            if (!$request) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'Request not found',
                ];
            }

            $request['changes'] = json_decode($request['changes_json'], true);
            unset($request['changes_json']);

            return [
                'success' => true,
                'data' => $request,
                'error' => null,
            ];
        } catch (\Exception $e) {
            log_message('error', 'ProfileChangeService::getProfileChangeRequest - ' . $e->getMessage());
            return [
                'success' => false,
                'data' => null,
                'error' => 'An error occurred while retrieving the request',
            ];
        }
    }

    /**
     * Approve profile change request
     *
     * @param int $requestId
     * @param int $adminId
     * @param string|null $notes
     * @return array
     */
    public function approveProfileChange(int $requestId, int $adminId, ?string $notes = null): array
    {
        $this->db->transStart();

        try {
            $request = $this->profileChangeModel->find($requestId);

            if (!$request) {
                $this->db->transRollback();
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'Request not found',
                ];
            }

            if ($request['status'] !== 'pending' && $request['status'] !== 'in_review') {
                $this->db->transRollback();
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'Request cannot be approved in current status: ' . $request['status'],
                ];
            }

            // Apply the profile changes
            $changes = json_decode($request['changes_json'], true);
            $applyResult = $this->applyProfileChanges($request['student_id'], $changes);

            if (!$applyResult['success']) {
                $this->db->transRollback();
                return $applyResult;
            }

            // Update request status
            $this->profileChangeModel->updateStatus($requestId, 'approved', $adminId, $notes);

            // Log the action
            $this->auditModel->logAction(
                $request['school_id'],
                'profile_change_request',
                $requestId,
                'approve',
                $adminId,
                2, // Admin role
                ['approved_changes' => $changes],
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            );

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                log_message('error', "ProfileChangeService: Transaction failed for request {$requestId}");
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'Transaction failed',
                ];
            }

            log_message('info', "ProfileChangeService: Approved request {$requestId} by admin {$adminId}");

            return [
                'success' => true,
                'data' => ['request_id' => $requestId, 'status' => 'approved'],
                'error' => null,
            ];
        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'ProfileChangeService::approveProfileChange - ' . $e->getMessage());
            return [
                'success' => false,
                'data' => null,
                'error' => 'An error occurred while approving the request',
            ];
        }
    }

    /**
     * Reject profile change request
     *
     * @param int $requestId
     * @param int $adminId
     * @param string $reason
     * @return array
     */
    public function rejectProfileChange(int $requestId, int $adminId, string $reason): array
    {
        try {
            $request = $this->profileChangeModel->find($requestId);

            if (!$request) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'Request not found',
                ];
            }

            if ($request['status'] !== 'pending' && $request['status'] !== 'in_review') {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'Request cannot be rejected in current status: ' . $request['status'],
                ];
            }

            // Update request status
            $this->profileChangeModel->updateStatus($requestId, 'rejected', $adminId, null, $reason);

            // Log the action
            $this->auditModel->logAction(
                $request['school_id'],
                'profile_change_request',
                $requestId,
                'reject',
                $adminId,
                2, // Admin role
                ['rejection_reason' => $reason],
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            );

            log_message('info', "ProfileChangeService: Rejected request {$requestId} by admin {$adminId}");

            return [
                'success' => true,
                'data' => ['request_id' => $requestId, 'status' => 'rejected'],
                'error' => null,
            ];
        } catch (\Exception $e) {
            log_message('error', 'ProfileChangeService::rejectProfileChange - ' . $e->getMessage());
            return [
                'success' => false,
                'data' => null,
                'error' => 'An error occurred while rejecting the request',
            ];
        }
    }

    /**
     * Apply profile changes to user table
     *
     * @param int $studentId
     * @param array $changes
     * @return array
     */
    protected function applyProfileChanges(int $studentId, array $changes): array
    {
        try {
            $updateData = [];

            foreach ($changes as $field => $change) {
                // Map request field names to database column names
                $dbField = $this->mapFieldToColumn($field);
                if ($dbField && isset($change['new'])) {
                    $updateData[$dbField] = $change['new'];
                }
            }

            if (empty($updateData)) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'No valid fields to update',
                ];
            }

            // Update user table
            $userModel = new \App\Models\V1\UserModel();
            $result = $userModel->update($studentId, $updateData);

            if (!$result) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'Failed to update user profile',
                ];
            }

            return [
                'success' => true,
                'data' => $updateData,
                'error' => null,
            ];
        } catch (\Exception $e) {
            log_message('error', 'ProfileChangeService::applyProfileChanges - ' . $e->getMessage());
            return [
                'success' => false,
                'data' => null,
                'error' => 'An error occurred while applying changes',
            ];
        }
    }

    /**
     * Get change history for a student
     *
     * @param int $studentId
     * @param int $schoolId
     * @return array
     */
    public function getChangeHistory(int $studentId, int $schoolId): array
    {
        try {
            $requests = $this->profileChangeModel->getStudentRequests($schoolId, $studentId);

            foreach ($requests as &$request) {
                $request['changes'] = json_decode($request['changes_json'], true);
                unset($request['changes_json']);
            }

            return [
                'success' => true,
                'data' => $requests,
                'error' => null,
            ];
        } catch (\Exception $e) {
            log_message('error', 'ProfileChangeService::getChangeHistory - ' . $e->getMessage());
            return [
                'success' => false,
                'data' => null,
                'error' => 'An error occurred while retrieving change history',
            ];
        }
    }

    /**
     * Determine request type based on changed fields
     *
     * @param array $changes
     * @return string
     */
    protected function determineRequestType(array $changes): string
    {
        $fields = array_keys($changes);

        $addressFields = ['address', 'city', 'state', 'zip', 'country'];
        $contactFields = ['email', 'phone', 'mobile'];
        $emergencyFields = ['emergency_contact', 'emergency_phone'];

        if (array_intersect($fields, $addressFields)) {
            return 'address';
        } elseif (array_intersect($fields, $contactFields)) {
            return 'contact_info';
        } elseif (array_intersect($fields, $emergencyFields)) {
            return 'emergency_contact';
        } else {
            return 'personal_info';
        }
    }

    /**
     * Map request field name to database column name
     *
     * @param string $field
     * @return string|null
     */
    protected function mapFieldToColumn(string $field): ?string
    {
        $mapping = [
            'address' => 'address',
            'city' => 'city',
            'state' => 'state',
            'zip' => 'zip',
            'country' => 'country',
            'email' => 'email',
            'phone' => 'phone',
            'mobile' => 'mobile',
            'emergency_contact' => 'emergency_contact_name',
            'emergency_phone' => 'emergency_contact_phone',
        ];

        return $mapping[$field] ?? null;
    }
}
