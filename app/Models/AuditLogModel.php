<?php

namespace App\Models;

use CodeIgniter\Model;

class AuditLogModel extends Model
{
    protected $table = 't_audit_log';
    protected $primaryKey = 'audit_id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'school_id',
        'actor_user_id', // Fixed: actual column name is actor_user_id, not actor_id
        'entity_type', // Fixed: actual column name is entity_type, not entity
        'entity_id',
        'action',
        'before_json', // Fixed: table has before_json instead of meta_json
        'after_json',
    ];

    // Dates - Table has created_at with CURRENT_TIMESTAMP default
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = '';

    // Validation
    protected $validationRules = [
        'school_id' => 'required|integer',
        'entity_type' => 'required|max_length[40]', // Fixed: column name is entity_type
        'entity_id' => 'permit_empty|integer', // Changed to permit_empty
        'action' => 'required|max_length[40]', // Fixed: max_length matches column
    ];

    protected $skipValidation = false;

    /**
     * Log an audit event
     */
    public function logEvent(int $schoolId, string $entity, int $entityId, string $action, ?int $actorId = null, ?string $actorRole = null, ?array $meta = null): bool
    {
        $data = [
            'school_id' => $schoolId,
            'actor_user_id' => $actorId, // Fixed: use correct column name
            'entity_type' => $entity, // Fixed: use correct column name
            'entity_id' => $entityId,
            'action' => $action,
            'after_json' => $meta ? json_encode($meta) : null, // Fixed: use after_json for metadata
        ];

        // Don't set created_at manually - table has CURRENT_TIMESTAMP default and useTimestamps is true

        return $this->insert($data) !== false;
    }

    /**
     * Get logs by entity
     */
    public function getByEntity(string $entity, int $entityId, int $limit = 50): array
    {
        return $this->where('entity_type', $entity) // Fixed: use correct column name
            ->where('entity_id', $entityId)
            ->orderBy('created_at', 'DESC')
            ->findAll($limit);
    }

    /**
     * Get logs by school and date range
     */
    public function getBySchool(int $schoolId, string $fromDate, string $toDate, ?string $entity = null, int $limit = 100): array
    {
        $builder = $this->where('school_id', $schoolId)
            ->where('created_at >=', $fromDate)
            ->where('created_at <=', $toDate);

        if ($entity) {
            $builder->where('entity_type', $entity); // Fixed: use correct column name
        }

        return $builder->orderBy('created_at', 'DESC')
            ->findAll($limit);
    }

    /**
     * Get logs by actor
     */
    public function getByActor(int $actorId, int $limit = 50): array
    {
        return $this->where('actor_user_id', $actorId) // Fixed: use correct column name
            ->orderBy('created_at', 'DESC')
            ->findAll($limit);
    }

    /**
     * Cleanup old logs
     */
    public function cleanupOldLogs(int $daysOld = 365): int
    {
        $date = date('Y-m-d', strtotime("-{$daysOld} days"));

        return $this->where('created_at <', $date)->delete();
    }
}
