<?php

namespace App\Services\ReportCard;

use App\Models\ReportCardModel;
use App\Services\ReportCard\AuditService;

class IssuerService
{
    protected $reportCardModel;
    protected $auditService;

    public function __construct()
    {
        $this->reportCardModel = new ReportCardModel();
        $this->auditService = new AuditService();
    }

    /**
     * Issue a report card (make visible to student)
     *
     * @param int $rcId
     * @param int $schoolId
     * @param int $userId
     * @return array
     */
    public function issue(int $rcId, int $schoolId, int $userId): array
    {
        $reportCard = $this->reportCardModel->where('rc_id', $rcId)
                                           ->where('school_id', $schoolId)
                                           ->first();

        if (!$reportCard) {
            return [
                'IsSuccess' => false,
                'Message' => 'Report card not found',
            ];
        }

        if ($reportCard['status'] === 'issued') {
            return [
                'IsSuccess' => false,
                'Message' => 'Report card is already issued',
            ];
        }

        // Update status
        $updated = $this->reportCardModel->updateStatus($rcId, $schoolId, 'issued', $userId);

        if ($updated) {
            // Log event
            $this->auditService->logEvent($rcId, 'reissued', $userId);

            return [
                'IsSuccess' => true,
                'Message' => 'Report card issued successfully',
            ];
        }

        return [
            'IsSuccess' => false,
            'Message' => 'Failed to issue report card',
        ];
    }

    /**
     * Mark report card as ready (locked for editing)
     *
     * @param int $rcId
     * @param int $schoolId
     * @param int $userId
     * @return array
     */
    public function markReady(int $rcId, int $schoolId, int $userId): array
    {
        $reportCard = $this->reportCardModel->where('rc_id', $rcId)
                                           ->where('school_id', $schoolId)
                                           ->first();

        if (!$reportCard) {
            return [
                'IsSuccess' => false,
                'Message' => 'Report card not found',
            ];
        }

        if ($reportCard['status'] !== 'draft') {
            return [
                'IsSuccess' => false,
                'Message' => 'Only draft report cards can be marked as ready',
            ];
        }

        $updated = $this->reportCardModel->updateStatus($rcId, $schoolId, 'ready', $userId);

        if ($updated) {
            return [
                'IsSuccess' => true,
                'Message' => 'Report card marked as ready',
            ];
        }

        return [
            'IsSuccess' => false,
            'Message' => 'Failed to update report card status',
        ];
    }

    /**
     * Revoke a report card (hide from student, maintain audit trail)
     *
     * @param int $rcId
     * @param int $schoolId
     * @param int $userId
     * @param string $reason
     * @return array
     */
    public function revoke(int $rcId, int $schoolId, int $userId, string $reason = ''): array
    {
        $reportCard = $this->reportCardModel->where('rc_id', $rcId)
                                           ->where('school_id', $schoolId)
                                           ->first();

        if (!$reportCard) {
            return [
                'IsSuccess' => false,
                'Message' => 'Report card not found',
            ];
        }

        if ($reportCard['status'] === 'revoked') {
            return [
                'IsSuccess' => false,
                'Message' => 'Report card is already revoked',
            ];
        }

        $updated = $this->reportCardModel->updateStatus($rcId, $schoolId, 'revoked', $userId);

        if ($updated) {
            // Log revocation event
            $this->auditService->logEvent($rcId, 'revoked', $userId, [
                'reason' => $reason,
                'previous_status' => $reportCard['status'],
            ]);

            return [
                'IsSuccess' => true,
                'Message' => 'Report card revoked successfully',
            ];
        }

        return [
            'IsSuccess' => false,
            'Message' => 'Failed to revoke report card',
        ];
    }

    /**
     * Bulk issue report cards
     *
     * @param array $rcIds Array of report card IDs
     * @param int $schoolId
     * @param int $userId
     * @return array
     */
    public function bulkIssue(array $rcIds, int $schoolId, int $userId): array
    {
        $results = [
            'total' => count($rcIds),
            'issued' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($rcIds as $rcId) {
            $result = $this->issue($rcId, $schoolId, $userId);
            if ($result['IsSuccess']) {
                $results['issued']++;
            } else {
                $results['failed']++;
                $results['errors'][] = "RC $rcId: " . $result['Message'];
            }
        }

        return [
            'IsSuccess' => true,
            'Data' => $results,
        ];
    }
}
