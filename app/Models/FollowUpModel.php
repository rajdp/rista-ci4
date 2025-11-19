<?php

namespace App\Models;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Model;

class FollowUpModel extends Model
{
    protected $table = 'crm_followups';
    protected $primaryKey = 'followup_id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'school_id',
        'action',
        'owner_user_id',
        'due_date',
        'status',
        'related_type',
        'related_id',
        'notes',
        'completed_at',
        'completed_by',
        'created_by',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Create a new follow-up
     */
    public function createFollowUp(array $data): int
    {
        $payload = $this->normalizePayload($data);
        $this->db->table($this->table)->insert($payload);
        return (int) $this->db->insertID();
    }

    /**
     * Get follow-ups with filters
     * 
     * @param array<string,mixed> $filters
     * @return array<int,array<string,mixed>>
     */
    public function getFollowUps(array $filters = []): array
    {
        $builder = $this->db->table($this->table . ' f');
        
        // Join with user and user_profile tables to get owner name
        $builder->select('f.*');
        $builder->select('CONCAT_WS(" ", up_owner.first_name, up_owner.last_name) as owner_name', false);
        $builder->select('u_owner.email_id as owner_email', false);
        $builder->select('CONCAT_WS(" ", up_creator.first_name, up_creator.last_name) as created_by_name', false);
        $builder->join('user u_owner', 'u_owner.user_id = f.owner_user_id', 'left');
        $builder->join('user_profile up_owner', 'up_owner.user_id = f.owner_user_id', 'left');
        $builder->join('user u_creator', 'u_creator.user_id = f.created_by', 'left');
        $builder->join('user_profile up_creator', 'up_creator.user_id = f.created_by', 'left');
        
        $this->applyFilters($builder, $filters);

        // Order by due date (nulls last), then by created_at
        // Use IF() to put NULLs last (1) and non-nulls first (0)
        $builder->orderBy('IF(f.due_date IS NULL, 1, 0)', 'ASC', false)
                ->orderBy('f.due_date', 'ASC')
                ->orderBy('f.created_at', 'DESC');

        $limit = isset($filters['limit']) ? (int) $filters['limit'] : 100;
        $limit = $limit > 0 ? min($limit, 500) : 100;
        
        if ($limit > 0) {
            $builder->limit($limit);
        }

        if (isset($filters['offset'])) {
            $builder->offset((int) $filters['offset']);
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Count follow-ups with filters
     * 
     * @param array<string,mixed> $filters
     * @return int
     */
    public function countFollowUps(array $filters = []): int
    {
        $builder = $this->db->table($this->table . ' f');
        $this->applyFilters($builder, $filters);
        return (int) $builder->countAllResults(false);
    }

    /**
     * Update follow-up status
     */
    public function updateStatus(int $followupId, string $status, ?int $completedBy = null): bool
    {
        $data = [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($status === 'completed') {
            $data['completed_at'] = date('Y-m-d H:i:s');
            if ($completedBy) {
                $data['completed_by'] = $completedBy;
            }
        }

        return $this->db->table($this->table)
            ->where('followup_id', $followupId)
            ->update($data);
    }

    /**
     * Update follow-up
     */
    public function updateFollowUp(int $followupId, array $data): bool
    {
        $payload = [];
        $allowed = ['action', 'owner_user_id', 'due_date', 'status', 'notes'];
        
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $payload[$field] = $data[$field];
            }
        }

        if (empty($payload)) {
            return false;
        }

        $payload['updated_at'] = date('Y-m-d H:i:s');

        if (isset($payload['status']) && $payload['status'] === 'completed' && !isset($data['completed_at'])) {
            $payload['completed_at'] = date('Y-m-d H:i:s');
            if (isset($data['completed_by'])) {
                $payload['completed_by'] = $data['completed_by'];
            }
        }

        return $this->db->table($this->table)
            ->where('followup_id', $followupId)
            ->update($payload);
    }

    /**
     * Get follow-ups grouped by owner
     * 
     * @param array<string,mixed> $filters
     * @return array<string,array<int,array<string,mixed>>>
     */
    public function getFollowUpsByOwner(array $filters = []): array
    {
        $followUps = $this->getFollowUps($filters);
        $grouped = [];

        foreach ($followUps as $followUp) {
            $ownerId = $followUp['owner_user_id'] ?? 0;
            $ownerKey = $ownerId > 0 ? (string) $ownerId : 'unassigned';
            
            if (!isset($grouped[$ownerKey])) {
                $grouped[$ownerKey] = [
                    'owner_id' => $ownerId > 0 ? $ownerId : null,
                    'owner_name' => $followUp['owner_name'] ?? 'Unassigned',
                    'owner_email' => $followUp['owner_email'] ?? null,
                    'tasks' => []
                ];
            }
            
            $grouped[$ownerKey]['tasks'][] = $followUp;
        }

        return $grouped;
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    private function normalizePayload(array $data): array
    {
        $payload = [
            'school_id' => isset($data['school_id']) ? (int) $data['school_id'] : null,
            'action' => trim((string) ($data['action'] ?? '')),
            'owner_user_id' => isset($data['owner_user_id']) && $data['owner_user_id'] ? (int) $data['owner_user_id'] : null,
            'due_date' => !empty($data['due_date']) ? $data['due_date'] : null,
            'status' => in_array($data['status'] ?? 'pending', ['pending', 'in_progress', 'completed', 'cancelled']) 
                ? $data['status'] 
                : 'pending',
            'related_type' => !empty($data['related_type']) ? (string) $data['related_type'] : null,
            'related_id' => isset($data['related_id']) && $data['related_id'] ? (int) $data['related_id'] : null,
            'notes' => !empty($data['notes']) ? trim((string) $data['notes']) : null,
            'created_by' => isset($data['created_by']) ? (int) $data['created_by'] : null,
        ];

        return $payload;
    }

    /**
     * @param BaseBuilder $builder
     * @param array<string,mixed> $filters
     */
    private function applyFilters(BaseBuilder $builder, array $filters): void
    {
        if (!empty($filters['followup_id'])) {
            $builder->where('f.followup_id', (int) $filters['followup_id']);
        }

        if (!empty($filters['school_id'])) {
            $builder->where('f.school_id', (int) $filters['school_id']);
        }

        if (!empty($filters['owner_user_id'])) {
            $builder->where('f.owner_user_id', (int) $filters['owner_user_id']);
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            if (is_array($filters['status'])) {
                $builder->whereIn('f.status', $filters['status']);
            } else {
                $builder->where('f.status', $filters['status']);
            }
        }

        if (!empty($filters['related_type'])) {
            $builder->where('f.related_type', (string) $filters['related_type']);
        }

        if (!empty($filters['related_id'])) {
            $builder->where('f.related_id', (int) $filters['related_id']);
        }

        if (isset($filters['due_before'])) {
            $builder->where('f.due_date <=', $filters['due_before']);
        }

        if (isset($filters['due_after'])) {
            $builder->where('f.due_date >=', $filters['due_after']);
        }

        if (isset($filters['overdue']) && $filters['overdue']) {
            $builder->where('f.due_date <', date('Y-m-d'))
                    ->where('f.status !=', 'completed');
        }
    }
}

