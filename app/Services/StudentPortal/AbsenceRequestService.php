<?php

namespace App\Services\StudentPortal;

use App\Models\StudentAbsenceRequestModel;
use App\Models\StudentPortalAuditModel;

class AbsenceRequestService
{
    protected $absenceModel;
    protected $auditModel;

    public function __construct()
    {
        $this->absenceModel = new StudentAbsenceRequestModel();
        $this->auditModel = new StudentPortalAuditModel();
    }

    /**
     * Create absence request
     *
     * @param int $studentId
     * @param int $schoolId
     * @param array $absenceData ['start_date', 'end_date', 'absence_type', 'reason', 'class_ids'?, 'is_advance_notice'?]
     * @return array
     */
    public function createAbsenceRequest(int $studentId, int $schoolId, array $absenceData): array
    {
        try {
            // Validate dates
            if (strtotime($absenceData['end_date']) < strtotime($absenceData['start_date'])) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'End date must be after start date',
                ];
            }

            $data = [
                'school_id' => $schoolId,
                'student_id' => $studentId,
                'status' => 'pending',
                'start_date' => $absenceData['start_date'],
                'end_date' => $absenceData['end_date'],
                'absence_type' => $absenceData['absence_type'],
                'reason' => $absenceData['reason'],
                'is_advance_notice' => $absenceData['is_advance_notice'] ?? 1,
                'class_ids' => $absenceData['class_ids'] ?? null,
                'has_documentation' => $absenceData['has_documentation'] ?? 0,
            ];

            $absenceId = $this->absenceModel->insert($data);

            if (!$absenceId) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'Failed to create absence request: ' . json_encode($this->absenceModel->errors()),
                ];
            }

            // Log the action
            $this->auditModel->logAction(
                $schoolId,
                'absence_request',
                $absenceId,
                'create',
                $studentId,
                5, // Student role
                $data,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            );

            log_message('info', "AbsenceRequestService: Created absence request {$absenceId} for student {$studentId}");

            return [
                'success' => true,
                'data' => [
                    'absence_id' => $absenceId,
                    'status' => 'pending',
                ],
                'error' => null,
            ];
        } catch (\Exception $e) {
            log_message('error', 'AbsenceRequestService::createAbsenceRequest - ' . $e->getMessage());
            return [
                'success' => false,
                'data' => null,
                'error' => 'An error occurred while creating the absence request',
            ];
        }
    }

    /**
     * List absence requests
     *
     * @param array $filters ['school_id', 'student_id', 'status', 'limit', 'offset']
     * @return array
     */
    public function listAbsenceRequests(array $filters): array
    {
        try {
            $schoolId = $filters['school_id'] ?? null;
            $studentId = $filters['student_id'] ?? null;
            $status = $filters['status'] ?? null;
            $limit = (int) ($filters['limit'] ?? 50);
            $offset = (int) ($filters['offset'] ?? 0);

            if (!$schoolId) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'School ID is required',
                ];
            }

            if ($studentId) {
                $absences = $this->absenceModel->getStudentAbsences($schoolId, $studentId, $status, $limit, $offset);
            } elseif ($status) {
                $absences = $this->absenceModel->getAbsencesByStatus($schoolId, $status, $limit, $offset);
            } else {
                $absences = $this->absenceModel->where('school_id', $schoolId)
                    ->orderBy('start_date', 'DESC')
                    ->limit($limit, $offset)
                    ->findAll();
            }

            return [
                'success' => true,
                'data' => $absences,
                'error' => null,
            ];
        } catch (\Exception $e) {
            log_message('error', 'AbsenceRequestService::listAbsenceRequests - ' . $e->getMessage());
            return [
                'success' => false,
                'data' => null,
                'error' => 'An error occurred while retrieving absence requests',
            ];
        }
    }

    /**
     * Get single absence request
     *
     * @param int $absenceId
     * @return array
     */
    public function getAbsenceRequest(int $absenceId): array
    {
        try {
            $absence = $this->absenceModel->find($absenceId);

            if (!$absence) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'Absence request not found',
                ];
            }

            return [
                'success' => true,
                'data' => $absence,
                'error' => null,
            ];
        } catch (\Exception $e) {
            log_message('error', 'AbsenceRequestService::getAbsenceRequest - ' . $e->getMessage());
            return [
                'success' => false,
                'data' => null,
                'error' => 'An error occurred while retrieving the absence request',
            ];
        }
    }

    /**
     * Approve absence request
     *
     * @param int $absenceId
     * @param int $adminId
     * @param string|null $notes
     * @return array
     */
    public function approveAbsence(int $absenceId, int $adminId, ?string $notes = null): array
    {
        try {
            $absence = $this->absenceModel->find($absenceId);

            if (!$absence) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'Absence request not found',
                ];
            }

            if ($absence['status'] !== 'pending') {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'Absence request cannot be approved in current status: ' . $absence['status'],
                ];
            }

            // Update status
            $this->absenceModel->updateStatus($absenceId, 'approved', $adminId, $notes);

            // Log the action
            $this->auditModel->logAction(
                $absence['school_id'],
                'absence_request',
                $absenceId,
                'approve',
                $adminId,
                2, // Admin role
                null,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            );

            log_message('info', "AbsenceRequestService: Approved absence {$absenceId} by admin {$adminId}");

            return [
                'success' => true,
                'data' => ['absence_id' => $absenceId, 'status' => 'approved'],
                'error' => null,
            ];
        } catch (\Exception $e) {
            log_message('error', 'AbsenceRequestService::approveAbsence - ' . $e->getMessage());
            return [
                'success' => false,
                'data' => null,
                'error' => 'An error occurred while approving the absence request',
            ];
        }
    }

    /**
     * Reject absence request
     *
     * @param int $absenceId
     * @param int $adminId
     * @param string $reason
     * @return array
     */
    public function rejectAbsence(int $absenceId, int $adminId, string $reason): array
    {
        try {
            $absence = $this->absenceModel->find($absenceId);

            if (!$absence) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'Absence request not found',
                ];
            }

            if ($absence['status'] !== 'pending') {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'Absence request cannot be rejected in current status: ' . $absence['status'],
                ];
            }

            // Update status
            $this->absenceModel->updateStatus($absenceId, 'rejected', $adminId, null, $reason);

            // Log the action
            $this->auditModel->logAction(
                $absence['school_id'],
                'absence_request',
                $absenceId,
                'reject',
                $adminId,
                2, // Admin role
                ['rejection_reason' => $reason],
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            );

            log_message('info', "AbsenceRequestService: Rejected absence {$absenceId} by admin {$adminId}");

            return [
                'success' => true,
                'data' => ['absence_id' => $absenceId, 'status' => 'rejected'],
                'error' => null,
            ];
        } catch (\Exception $e) {
            log_message('error', 'AbsenceRequestService::rejectAbsence - ' . $e->getMessage());
            return [
                'success' => false,
                'data' => null,
                'error' => 'An error occurred while rejecting the absence request',
            ];
        }
    }

    /**
     * Get absences for a class (teacher view)
     *
     * @param int $classId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getAbsencesForClass(int $classId, string $startDate, string $endDate): array
    {
        try {
            $absences = $this->absenceModel->getAbsencesForClass($classId, $startDate, $endDate);

            return [
                'success' => true,
                'data' => $absences,
                'error' => null,
            ];
        } catch (\Exception $e) {
            log_message('error', 'AbsenceRequestService::getAbsencesForClass - ' . $e->getMessage());
            return [
                'success' => false,
                'data' => null,
                'error' => 'An error occurred while retrieving absences for class',
            ];
        }
    }

    /**
     * Get student absence summary
     *
     * @param int $studentId
     * @param int $schoolId
     * @return array
     */
    public function getStudentAbsenceSummary(int $studentId, int $schoolId): array
    {
        try {
            $absences = $this->absenceModel->getStudentAbsences($schoolId, $studentId);

            $summary = [
                'total' => count($absences),
                'approved' => 0,
                'pending' => 0,
                'rejected' => 0,
                'by_type' => [],
            ];

            foreach ($absences as $absence) {
                // Count by status
                if ($absence['status'] === 'approved') {
                    $summary['approved']++;
                } elseif ($absence['status'] === 'pending') {
                    $summary['pending']++;
                } elseif ($absence['status'] === 'rejected') {
                    $summary['rejected']++;
                }

                // Count by type
                $type = $absence['absence_type'];
                if (!isset($summary['by_type'][$type])) {
                    $summary['by_type'][$type] = 0;
                }
                $summary['by_type'][$type]++;
            }

            return [
                'success' => true,
                'data' => $summary,
                'error' => null,
            ];
        } catch (\Exception $e) {
            log_message('error', 'AbsenceRequestService::getStudentAbsenceSummary - ' . $e->getMessage());
            return [
                'success' => false,
                'data' => null,
                'error' => 'An error occurred while generating absence summary',
            ];
        }
    }
}
