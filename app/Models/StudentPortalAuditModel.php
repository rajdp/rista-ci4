<?php

namespace App\Models;

use App\Models\V1\BaseModel;

class StudentPortalAuditModel extends BaseModel
{
    protected $table = 't_student_portal_audit';
    protected $primaryKey = 'audit_id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'school_id',
        'entity_type',
        'entity_id',
        'action',
        'actor_id',
        'actor_role_id',
        'changes_json',
        'ip_address',
        'user_agent',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = null;

    protected $validationRules = [
        'school_id' => 'required|integer',
        'entity_type' => 'required|max_length[50]',
        'entity_id' => 'required|integer',
        'action' => 'required|max_length[50]',
        'actor_id' => 'required|integer',
        'actor_role_id' => 'required|integer',
    ];

    protected $validationMessages = [
        'school_id' => [
            'required' => 'School ID is required',
        ],
        'entity_type' => [
            'required' => 'Entity type is required',
        ],
        'action' => [
            'required' => 'Action is required',
        ],
    ];

    /**
     * Log an action
     */
    public function logAction($schoolId, $entityType, $entityId, $action, $actorId, $actorRoleId, $changes = null, $ipAddress = null, $userAgent = null)
    {
        $data = [
            'school_id' => $schoolId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'actor_id' => $actorId,
            'actor_role_id' => $actorRoleId,
            'changes_json' => $changes ? json_encode($changes) : null,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ];

        return $this->insert($data);
    }

    /**
     * Get audit log for an entity
     */
    public function getEntityAuditLog($schoolId, $entityType, $entityId, $limit = 100, $offset = 0)
    {
        return $this->where('school_id', $schoolId)
                    ->where('entity_type', $entityType)
                    ->where('entity_id', $entityId)
                    ->orderBy('created_at', 'DESC')
                    ->limit($limit, $offset)
                    ->findAll();
    }

    /**
     * Get audit log by actor
     */
    public function getActorAuditLog($schoolId, $actorId, $limit = 100, $offset = 0)
    {
        return $this->where('school_id', $schoolId)
                    ->where('actor_id', $actorId)
                    ->orderBy('created_at', 'DESC')
                    ->limit($limit, $offset)
                    ->findAll();
    }

    /**
     * Search audit log
     */
    public function searchAuditLog($schoolId, $filters = [], $limit = 100, $offset = 0)
    {
        $builder = $this->where('school_id', $schoolId);

        if (isset($filters['entity_type'])) {
            $builder->where('entity_type', $filters['entity_type']);
        }

        if (isset($filters['action'])) {
            $builder->where('action', $filters['action']);
        }

        if (isset($filters['actor_id'])) {
            $builder->where('actor_id', $filters['actor_id']);
        }

        if (isset($filters['date_from'])) {
            $builder->where('created_at >=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $builder->where('created_at <=', $filters['date_to']);
        }

        return $builder->orderBy('created_at', 'DESC')
                      ->limit($limit, $offset)
                      ->findAll();
    }
}
