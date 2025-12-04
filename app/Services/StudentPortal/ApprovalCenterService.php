<?php

namespace App\Services\StudentPortal;

use App\Models\StudentProfileChangeRequestModel;
use App\Models\StudentAbsenceRequestModel;
use App\Models\StudentSpecialRequestModel;
use App\Models\StudentDocumentModel;
use App\Models\StudentPortalAuditModel;
use CodeIgniter\Database\BaseConnection;

class ApprovalCenterService
{
    protected $profileChangeModel;
    protected $absenceModel;
    protected $specialRequestModel;
    protected $documentModel;
    protected $auditModel;
    protected $db;

    public function __construct()
    {
        $this->profileChangeModel = new StudentProfileChangeRequestModel();
        $this->absenceModel = new StudentAbsenceRequestModel();
        $this->specialRequestModel = new StudentSpecialRequestModel();
        $this->documentModel = new StudentDocumentModel();
        $this->auditModel = new StudentPortalAuditModel();
        $this->db = \Config\Database::connect();
    }

    /**
     * Get dashboard statistics
     *
     * @param int $schoolId
     * @return array
     */
    public function getDashboardStats(int $schoolId): array
    {
        try {
            $stats = [
                'profile_change_count' => [
                    'pending' => $this->profileChangeModel->countByStatus($schoolId, 'pending'),
                    'in_review' => $this->profileChangeModel->countByStatus($schoolId, 'in_review'),
                    'total' => $this->profileChangeModel->countByStatus($schoolId),
                ],
                'absence_count' => [
                    'pending' => $this->absenceModel->countByStatus($schoolId, 'pending'),
                    'total' => $this->absenceModel->countByStatus($schoolId),
                ],
                'special_request_count' => [
                    'pending' => $this->specialRequestModel->countByStatus($schoolId, 'pending'),
                    'in_review' => $this->specialRequestModel->countByStatus($schoolId, 'in_review'),
                    'total' => $this->specialRequestModel->countByStatus($schoolId),
                ],
                'document_count' => [
                    'pending' => $this->documentModel->countByStatus($schoolId, 'pending'),
                    'total' => $this->documentModel->countByStatus($schoolId),
                ],
                'total_pending' => 0,
            ];

            // Calculate total pending
            $stats['total_pending'] =
                $stats['profile_change_count']['pending'] +
                $stats['absence_count']['pending'] +
                $stats['special_request_count']['pending'] +
                $stats['document_count']['pending'];

            return [
                'success' => true,
                'data' => $stats,
                'error' => null,
            ];
        } catch (\Exception $e) {
            log_message('error', 'ApprovalCenterService::getDashboardStats - ' . $e->getMessage());
            return [
                'success' => false,
                'data' => null,
                'error' => 'An error occurred while retrieving dashboard statistics',
            ];
        }
    }

    /**
     * Get all pending requests (unified view)
     *
     * @param int $schoolId
     * @param array $filters ['type', 'limit', 'offset']
     * @return array
     */
    public function getAllPendingRequests(int $schoolId, array $filters = []): array
    {
        try {
            $type = $filters['type'] ?? 'all';
            $limit = $filters['limit'] ?? 50;
            $offset = $filters['offset'] ?? 0;

            $allRequests = [];

            // Profile change requests
            if ($type === 'all' || $type === 'profile_change') {
                $profileChanges = $this->profileChangeModel->getPendingRequests($schoolId, $limit, $offset);
                foreach ($profileChanges as $request) {
                    $allRequests[] = [
                        'type' => 'profile_change',
                        'id' => $request['request_id'],
                        'student_id' => $request['student_id'],
                        'created_at' => $request['created_at'],
                        'status' => $request['status'],
                        'data' => $request,
                    ];
                }
            }

            // Absence requests
            if ($type === 'all' || $type === 'absence') {
                $absences = $this->absenceModel->getPendingAbsences($schoolId, $limit, $offset);
                foreach ($absences as $request) {
                    $allRequests[] = [
                        'type' => 'absence',
                        'id' => $request['absence_id'],
                        'student_id' => $request['student_id'],
                        'created_at' => $request['created_at'],
                        'status' => $request['status'],
                        'data' => $request,
                    ];
                }
            }

            // Special requests
            if ($type === 'all' || $type === 'special_request') {
                $specialRequests = $this->specialRequestModel->getPendingRequests($schoolId, $limit, $offset);
                foreach ($specialRequests as $request) {
                    $allRequests[] = [
                        'type' => 'special_request',
                        'id' => $request['request_id'],
                        'student_id' => $request['student_id'],
                        'created_at' => $request['created_at'],
                        'status' => $request['status'],
                        'priority' => $request['priority'],
                        'data' => $request,
                    ];
                }
            }

            // Documents
            if ($type === 'all' || $type === 'document') {
                $documents = $this->documentModel->getPendingDocuments($schoolId, $limit, $offset);
                foreach ($documents as $document) {
                    $allRequests[] = [
                        'type' => 'document',
                        'id' => $document['document_id'],
                        'student_id' => $document['student_id'],
                        'created_at' => $document['uploaded_at'],
                        'status' => $document['status'],
                        'data' => $document,
                    ];
                }
            }

            // Sort by created_at descending
            usort($allRequests, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });

            // Apply limit if needed
            if ($type === 'all') {
                $allRequests = array_slice($allRequests, 0, $limit);
            }

            return [
                'success' => true,
                'data' => $allRequests,
                'error' => null,
            ];
        } catch (\Exception $e) {
            log_message('error', 'ApprovalCenterService::getAllPendingRequests - ' . $e->getMessage());
            return [
                'success' => false,
                'data' => null,
                'error' => 'An error occurred while retrieving pending requests',
            ];
        }
    }

    /**
     * Bulk approve requests
     *
     * @param string $requestType ['profile_change', 'absence', 'special_request', 'document']
     * @param array $requestIds
     * @param int $adminId
     * @return array
     */
    public function bulkApprove(string $requestType, array $requestIds, int $adminId): array
    {
        $this->db->transStart();

        try {
            $results = [];
            $successCount = 0;
            $failCount = 0;

            foreach ($requestIds as $requestId) {
                $result = null;

                switch ($requestType) {
                    case 'profile_change':
                        $service = new ProfileChangeService();
                        $result = $service->approveProfileChange($requestId, $adminId);
                        break;

                    case 'absence':
                        $service = new AbsenceRequestService();
                        $result = $service->approveAbsence($requestId, $adminId);
                        break;

                    case 'special_request':
                        $service = new SpecialRequestService();
                        $result = $service->updateRequestStatus($requestId, 'approved', $adminId);
                        break;

                    case 'document':
                        $service = new DocumentService();
                        $result = $service->approveDocument($requestId, $adminId);
                        break;

                    default:
                        $failCount++;
                        $results[] = ['id' => $requestId, 'success' => false, 'error' => 'Invalid request type'];
                        continue 2;
                }

                if ($result['success']) {
                    $successCount++;
                    $results[] = ['id' => $requestId, 'success' => true, 'error' => null];
                } else {
                    $failCount++;
                    $results[] = ['id' => $requestId, 'success' => false, 'error' => $result['error']];
                }
            }

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                log_message('error', "ApprovalCenterService: Bulk approve transaction failed");
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'Transaction failed',
                ];
            }

            log_message('info', "ApprovalCenterService: Bulk approved {$successCount} {$requestType} requests by admin {$adminId}");

            return [
                'success' => true,
                'data' => [
                    'success_count' => $successCount,
                    'fail_count' => $failCount,
                    'results' => $results,
                ],
                'error' => null,
            ];
        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'ApprovalCenterService::bulkApprove - ' . $e->getMessage());
            return [
                'success' => false,
                'data' => null,
                'error' => 'An error occurred during bulk approval',
            ];
        }
    }

    /**
     * Bulk reject requests
     *
     * @param string $requestType
     * @param array $requestIds
     * @param int $adminId
     * @param string $reason
     * @return array
     */
    public function bulkReject(string $requestType, array $requestIds, int $adminId, string $reason): array
    {
        $this->db->transStart();

        try {
            $results = [];
            $successCount = 0;
            $failCount = 0;

            foreach ($requestIds as $requestId) {
                $result = null;

                switch ($requestType) {
                    case 'profile_change':
                        $service = new ProfileChangeService();
                        $result = $service->rejectProfileChange($requestId, $adminId, $reason);
                        break;

                    case 'absence':
                        $service = new AbsenceRequestService();
                        $result = $service->rejectAbsence($requestId, $adminId, $reason);
                        break;

                    case 'special_request':
                        $service = new SpecialRequestService();
                        $result = $service->updateRequestStatus($requestId, 'rejected', $adminId, $reason);
                        break;

                    case 'document':
                        $service = new DocumentService();
                        $result = $service->rejectDocument($requestId, $adminId, $reason);
                        break;

                    default:
                        $failCount++;
                        $results[] = ['id' => $requestId, 'success' => false, 'error' => 'Invalid request type'];
                        continue 2;
                }

                if ($result['success']) {
                    $successCount++;
                    $results[] = ['id' => $requestId, 'success' => true, 'error' => null];
                } else {
                    $failCount++;
                    $results[] = ['id' => $requestId, 'success' => false, 'error' => $result['error']];
                }
            }

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                log_message('error', "ApprovalCenterService: Bulk reject transaction failed");
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'Transaction failed',
                ];
            }

            log_message('info', "ApprovalCenterService: Bulk rejected {$successCount} {$requestType} requests by admin {$adminId}");

            return [
                'success' => true,
                'data' => [
                    'success_count' => $successCount,
                    'fail_count' => $failCount,
                    'results' => $results,
                ],
                'error' => null,
            ];
        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'ApprovalCenterService::bulkReject - ' . $e->getMessage());
            return [
                'success' => false,
                'data' => null,
                'error' => 'An error occurred during bulk rejection',
            ];
        }
    }

    /**
     * Get admin workload statistics
     *
     * @param int $adminId
     * @param int $schoolId
     * @return array
     */
    public function getAdminWorkload(int $adminId, int $schoolId): array
    {
        try {
            $workload = [
                'assigned_special_requests' => count($this->specialRequestModel->getAssignedRequests($adminId, $schoolId, null)),
                'assigned_pending' => count($this->specialRequestModel->getAssignedRequests($adminId, $schoolId, 'pending')),
                'assigned_in_review' => count($this->specialRequestModel->getAssignedRequests($adminId, $schoolId, 'in_review')),
                'total_reviewed' => 0,
            ];

            // Count total reviewed by this admin (across all request types)
            $reviewedCount = $this->db->table('t_student_profile_change_request')
                ->where('school_id', $schoolId)
                ->where('reviewed_by', $adminId)
                ->countAllResults();

            $reviewedCount += $this->db->table('t_student_absence_request')
                ->where('school_id', $schoolId)
                ->where('reviewed_by', $adminId)
                ->countAllResults();

            $reviewedCount += $this->db->table('t_student_special_request')
                ->where('school_id', $schoolId)
                ->where('reviewed_by', $adminId)
                ->countAllResults();

            $reviewedCount += $this->db->table('t_student_document')
                ->where('school_id', $schoolId)
                ->where('reviewed_by', $adminId)
                ->countAllResults();

            $workload['total_reviewed'] = $reviewedCount;

            return [
                'success' => true,
                'data' => $workload,
                'error' => null,
            ];
        } catch (\Exception $e) {
            log_message('error', 'ApprovalCenterService::getAdminWorkload - ' . $e->getMessage());
            return [
                'success' => false,
                'data' => null,
                'error' => 'An error occurred while retrieving admin workload',
            ];
        }
    }
}
