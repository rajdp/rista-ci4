<?php

namespace App\Services\Billing;

use App\Models\AuditLogModel;

class AuditService
{
    protected $auditLogModel;

    public function __construct()
    {
        $this->auditLogModel = new AuditLogModel();
    }

    /**
     * Log an audit event
     */
    public function log(
        int $schoolId,
        string $entity,
        int $entityId,
        string $action,
        ?int $actorId = null,
        ?string $actorRole = null,
        ?array $meta = null
    ): bool {
        try {
            return $this->auditLogModel->logEvent(
                $schoolId,
                $entity,
                $entityId,
                $action,
                $actorId,
                $actorRole,
                $meta
            );
        } catch (\Exception $e) {
            log_message('error', 'AuditService::log error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get audit trail for entity
     */
    public function getEntityLogs(string $entity, int $entityId, int $limit = 50): array
    {
        try {
            return $this->auditLogModel->getByEntity($entity, $entityId, $limit);
        } catch (\Exception $e) {
            log_message('error', 'AuditService::getEntityLogs error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get logs by school
     */
    public function getSchoolLogs(int $schoolId, string $fromDate, string $toDate, ?string $entity = null, int $limit = 100): array
    {
        try {
            return $this->auditLogModel->getBySchool($schoolId, $fromDate, $toDate, $entity, $limit);
        } catch (\Exception $e) {
            log_message('error', 'AuditService::getSchoolLogs error: ' . $e->getMessage());
            return [];
        }
    }
}
