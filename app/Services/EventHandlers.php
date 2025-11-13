<?php

namespace App\Services;

use CodeIgniter\Config\BaseService;
use Config\Database;

/**
 * Event Handlers Service
 * Processes events from the outbox queue
 */
class EventHandlers extends BaseService
{
    protected $db;
    protected $messageService;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->messageService = service('messaging', false) ?? new MessagingService();
    }

    /**
     * Handle self-registration status update
     */
    public function selfregStatus(array $row, array $payload): bool
    {
        try {
            $selfregId = $payload['selfreg_id'] ?? null;
            $oldStatus = $payload['old'] ?? null;
            $newStatus = $payload['new'] ?? null;

            if (!$selfregId) {
                log_message('error', 'selfregStatus: missing selfreg_id');
                return false;
            }

            // Log audit trail
            $this->auditLog($row['school_id'], null, 'student_self_registrations', $selfregId, 
                'status_updated', ['old' => $oldStatus], ['new' => $newStatus]);

            // Send notifications based on status change
            if ($newStatus === 'in_review') {
                // Notify registrar
                $this->notifyRegistrar($row['school_id'], $selfregId);
            } elseif ($newStatus === 'needs_info') {
                // Send info request to applicant
                $this->sendInfoRequest($row['school_id'], $selfregId);
            } elseif ($newStatus === 'approved') {
                // Send approval notification
                $this->sendApprovalNotification($row['school_id'], $selfregId);
            }

            return true;
        } catch (\Throwable $e) {
            log_message('error', 'selfregStatus handler error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Handle self-registration conversion
     */
    public function selfregConverted(array $row, array $payload): bool
    {
        try {
            $selfregId = $payload['selfreg_id'] ?? null;
            $studentUserId = $payload['student_user_id'] ?? null;
            $primaryGuardianId = $payload['primary_guardian_id'] ?? null;

            if (!$selfregId) {
                log_message('error', 'selfregConverted: missing selfreg_id');
                return false;
            }

            // Log audit trail
            $this->auditLog($row['school_id'], null, 'student_self_registrations', $selfregId,
                'converted', null, [
                    'student_user_id' => $studentUserId,
                    'primary_guardian_id' => $primaryGuardianId
                ]);

            // Send welcome/onboarding messages
            if ($primaryGuardianId) {
                $this->sendOnboardingMessage($row['school_id'], $primaryGuardianId, $studentUserId);
            }

            // Update KPI sinks
            $this->updateKPI($row['school_id'], 'enrollment');

            return true;
        } catch (\Throwable $e) {
            log_message('error', 'selfregConverted handler error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Handle session reminder
     */
    public function sessionReminder(array $row, array $payload): bool
    {
        // TODO: Implement session reminder logic
        // Check consent, quiet hours, send reminder
        return true;
    }

    /**
     * Handle no-show attendance
     */
    public function noShow(array $row, array $payload): bool
    {
        // TODO: Implement no-show logic
        // Send make-up credit link
        return true;
    }

    /**
     * Handle invoice opened
     */
    public function invoiceOpen(array $row, array $payload): bool
    {
        // TODO: Implement invoice open logic
        // Track engagement, send payment reminders
        return true;
    }

    /**
     * Handle invoice paid
     */
    public function invoicePaid(array $row, array $payload): bool
    {
        try {
            // Update revenue KPI
            $invoiceId = $payload['invoice_id'] ?? null;
            $amountCents = $payload['amount_cents'] ?? 0;

            if ($invoiceId) {
                $this->updateRevenueKPI($row['school_id'], $amountCents);
            }

            return true;
        } catch (\Throwable $e) {
            log_message('error', 'invoicePaid handler error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Log audit entry
     */
    private function auditLog(int $schoolId, ?int $actorUserId, string $entityType, 
                             ?int $entityId, string $action, ?array $before = null, ?array $after = null): void
    {
        $this->db->table('t_audit_log')->insert([
            'school_id' => $schoolId,
            'actor_user_id' => $actorUserId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'before_json' => $before ? json_encode($before) : null,
            'after_json' => $after ? json_encode($after) : null,
        ]);
    }

    /**
     * Notify registrar of new review item
     */
    private function notifyRegistrar(int $schoolId, int $selfregId): void
    {
        // TODO: Implement registrar notification
        // Could send email or in-app notification
    }

    /**
     * Send info request to applicant
     */
    private function sendInfoRequest(int $schoolId, int $selfregId): void
    {
        // Get registration details
        $reg = $this->db->table('student_self_registrations')
            ->where('id', $selfregId)
            ->where('school_id', $schoolId)
            ->get()
            ->getRowArray();

        if (!$reg) {
            return;
        }

        // Send templated message
        $this->messageService->sendTemplate(
            $schoolId,
            'email',
            'needs_info',
            $reg['email'],
            [
                'student_name' => $reg['student_first_name'] . ' ' . $reg['student_last_name'],
                'registration_code' => $reg['registration_code'],
            ]
        );
    }

    /**
     * Send approval notification
     */
    private function sendApprovalNotification(int $schoolId, int $selfregId): void
    {
        // Get registration details
        $reg = $this->db->table('student_self_registrations')
            ->where('id', $selfregId)
            ->where('school_id', $schoolId)
            ->get()
            ->getRowArray();

        if (!$reg) {
            return;
        }

        // Send templated message
        $this->messageService->sendTemplate(
            $schoolId,
            'email',
            'approved',
            $reg['email'],
            [
                'student_name' => $reg['student_first_name'] . ' ' . $reg['student_last_name'],
                'registration_code' => $reg['registration_code'],
            ]
        );
    }

    /**
     * Send onboarding message
     */
    private function sendOnboardingMessage(int $schoolId, int $guardianId, ?int $studentUserId): void
    {
        // TODO: Get guardian email and send onboarding message
        // Could include portal invite, welcome info, etc.
    }

    /**
     * Update marketing KPI
     */
    private function updateKPI(int $schoolId, string $type): void
    {
        $today = date('Y-m-d');
        
        // Use INSERT ... ON DUPLICATE KEY UPDATE for atomic upsert
        if ($type === 'enrollment') {
            $this->db->query("
                INSERT INTO t_marketing_kpi_daily (school_id, day, source, enrollments)
                VALUES (?, ?, '', 1)
                ON DUPLICATE KEY UPDATE enrollments = enrollments + 1
            ", [$schoolId, $today]);
        } elseif ($type === 'lead') {
            $this->db->query("
                INSERT INTO t_marketing_kpi_daily (school_id, day, source, leads)
                VALUES (?, ?, '', 1)
                ON DUPLICATE KEY UPDATE leads = leads + 1
            ", [$schoolId, $today]);
        }
    }

    /**
     * Update revenue KPI
     */
    private function updateRevenueKPI(int $schoolId, int $amountCents): void
    {
        $today = date('Y-m-d');
        
        $this->db->query("
            INSERT INTO t_revenue_daily (school_id, day, mrr_cents, arr_cents)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                mrr_cents = mrr_cents + VALUES(mrr_cents),
                arr_cents = arr_cents + VALUES(arr_cents)
        ", [$schoolId, $today, $amountCents, $amountCents * 12]);
    }
}

