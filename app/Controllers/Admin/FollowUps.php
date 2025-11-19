<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\FollowUpModel;
use App\Traits\RestTrait;
use CodeIgniter\HTTP\ResponseInterface;

class FollowUps extends BaseController
{
    use RestTrait;

    protected FollowUpModel $followUpModel;

    public function __construct()
    {
        $this->followUpModel = new FollowUpModel();
    }

    /**
     * List follow-ups with optional filters
     */
    public function list(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);

            $token = $this->validateToken();
            if (!$token) {
                return $this->unauthorizedResponse('Access token required');
            }

            $schoolId = $this->getSchoolId($token);
            if ($schoolId) {
                $payload['school_id'] = $payload['school_id'] ?? (int) $schoolId;
            }

            $limit = (int) ($payload['limit'] ?? 100);
            $limit = max(1, min(500, $limit));
            $page = max(1, (int) ($payload['page'] ?? 1));
            $offset = ($page - 1) * $limit;

            $filters = [
                'school_id' => $payload['school_id'] ?? null,
                'owner_user_id' => $payload['owner_user_id'] ?? null,
                'status' => $payload['status'] ?? null,
                'related_type' => $payload['related_type'] ?? null,
                'related_id' => $payload['related_id'] ?? null,
                'overdue' => $payload['overdue'] ?? false,
                'limit' => $limit,
                'offset' => $offset,
            ];

            // Remove null filters
            $filters = array_filter($filters, function($value) {
                return $value !== null && $value !== '';
            });

            $followUps = $this->followUpModel->getFollowUps($filters);
            $total = $this->followUpModel->countFollowUps($filters);

            return $this->successResponse([
                'items' => $followUps,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => (int) ceil($total / $limit)
                ]
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'FollowUps::list - ' . $e->getMessage());
            return $this->errorResponse('Unable to list follow-ups');
        }
    }

    /**
     * Get follow-ups grouped by owner
     */
    public function listByOwner(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);

            $token = $this->validateToken();
            if (!$token) {
                return $this->unauthorizedResponse('Access token required');
            }

            $schoolId = $this->getSchoolId($token);
            if ($schoolId) {
                $payload['school_id'] = $payload['school_id'] ?? (int) $schoolId;
            }

            $filters = [
                'school_id' => $payload['school_id'] ?? null,
                'status' => $payload['status'] ?? ['pending', 'in_progress'],
                'limit' => $payload['limit'] ?? 500,
            ];

            // Remove null filters
            $filters = array_filter($filters, function($value) {
                return $value !== null && $value !== '';
            });

            $grouped = $this->followUpModel->getFollowUpsByOwner($filters);

            // Convert to array format for response
            $result = [];
            foreach ($grouped as $ownerKey => $group) {
                $result[] = $group;
            }

            return $this->successResponse($result);
        } catch (\Throwable $e) {
            log_message('error', 'FollowUps::listByOwner - ' . $e->getMessage());
            log_message('error', 'FollowUps::listByOwner - Trace: ' . $e->getTraceAsString());
            return $this->errorResponse('Unable to list follow-ups by owner: ' . $e->getMessage());
        }
    }

    /**
     * Create a new follow-up
     */
    public function create(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);

            $token = $this->validateToken();
            if (!$token) {
                return $this->unauthorizedResponse('Access token required');
            }

            $userId = $this->getUserId($token);
            $schoolId = $this->getSchoolId($token);
            if ($schoolId) {
                $payload['school_id'] = $payload['school_id'] ?? (int) $schoolId;
            }

            $required = ['action', 'school_id'];
            if (!$this->validateRequiredFields($payload, $required)) {
                return $this->errorResponse('Missing required fields: ' . implode(', ', $this->getMissingFields($payload, $required)));
            }

            $data = [
                'school_id' => (int) $payload['school_id'],
                'action' => trim($payload['action']),
                'owner_user_id' => !empty($payload['owner_user_id']) ? (int) $payload['owner_user_id'] : null,
                'due_date' => !empty($payload['due_date']) ? $payload['due_date'] : null,
                'status' => $payload['status'] ?? 'pending',
                'related_type' => !empty($payload['related_type']) ? $payload['related_type'] : null,
                'related_id' => !empty($payload['related_id']) ? (int) $payload['related_id'] : null,
                'notes' => !empty($payload['notes']) ? trim($payload['notes']) : null,
                'created_by' => $userId,
            ];

            $followupId = $this->followUpModel->createFollowUp($data);

            // Get the created follow-up with owner details
            $followUp = $this->followUpModel->getFollowUps(['followup_id' => $followupId]);
            $followUp = !empty($followUp) ? $followUp[0] : null;

            return $this->successResponse($followUp, 'Follow-up created successfully');
        } catch (\Throwable $e) {
            log_message('error', 'FollowUps::create - ' . $e->getMessage());
            return $this->errorResponse('Unable to create follow-up');
        }
    }

    /**
     * Update a follow-up
     */
    public function updateFollowUp(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);

            $token = $this->validateToken();
            if (!$token) {
                return $this->unauthorizedResponse('Access token required');
            }

            if (empty($payload['followup_id'])) {
                return $this->errorResponse('followup_id is required');
            }

            $followupId = (int) $payload['followup_id'];
            $userId = $this->getUserId($token);

            $data = [];
            $allowedFields = ['action', 'owner_user_id', 'due_date', 'status', 'notes'];
            
            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $payload)) {
                    $data[$field] = $payload[$field];
                }
            }

            if (isset($data['status']) && $data['status'] === 'completed') {
                $data['completed_by'] = $userId;
            }

            if (empty($data)) {
                return $this->errorResponse('No fields to update');
            }

            $updated = $this->followUpModel->updateFollowUp($followupId, $data);

            if (!$updated) {
                return $this->errorResponse('Unable to update follow-up');
            }

            // Get the updated follow-up
            $followUps = $this->followUpModel->getFollowUps(['followup_id' => $followupId]);
            $followUp = !empty($followUps) ? $followUps[0] : null;

            return $this->successResponse($followUp, 'Follow-up updated successfully');
        } catch (\Throwable $e) {
            log_message('error', 'FollowUps::update - ' . $e->getMessage());
            return $this->errorResponse('Unable to update follow-up');
        }
    }

    /**
     * Update follow-up status
     */
    public function updateStatus(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);

            $token = $this->validateToken();
            if (!$token) {
                return $this->unauthorizedResponse('Access token required');
            }

            if (empty($payload['followup_id']) || empty($payload['status'])) {
                return $this->errorResponse('followup_id and status are required');
            }

            $followupId = (int) $payload['followup_id'];
            $status = $payload['status'];
            $userId = $this->getUserId($token);

            if (!in_array($status, ['pending', 'in_progress', 'completed', 'cancelled'])) {
                return $this->errorResponse('Invalid status');
            }

            $updated = $this->followUpModel->updateStatus($followupId, $status, $userId);

            if (!$updated) {
                return $this->errorResponse('Unable to update follow-up status');
            }

            // Get the updated follow-up
            $followUps = $this->followUpModel->getFollowUps(['followup_id' => $followupId]);
            $followUp = !empty($followUps) ? $followUps[0] : null;

            return $this->successResponse($followUp, 'Follow-up status updated successfully');
        } catch (\Throwable $e) {
            log_message('error', 'FollowUps::updateStatus - ' . $e->getMessage());
            return $this->errorResponse('Unable to update follow-up status');
        }
    }
}

