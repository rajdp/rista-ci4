<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Services\StudentPortal\ProfileChangeService;
use App\Services\StudentPortal\AbsenceRequestService;
use App\Services\StudentPortal\SpecialRequestService;
use App\Services\StudentPortal\DocumentService;
use App\Services\StudentPortal\ApprovalCenterService;
use App\Services\StudentPortal\RequestConversationService;

class StudentPortalAPI extends ResourceController
{
    use ResponseTrait;

    protected $profileService;
    protected $absenceService;
    protected $specialRequestService;
    protected $documentService;
    protected $approvalService;
    protected $conversationService;

    public function __construct()
    {
        helper(['url', 'form']);
        $this->profileService = new ProfileChangeService();
        $this->absenceService = new AbsenceRequestService();
        $this->specialRequestService = new SpecialRequestService();
        $this->documentService = new DocumentService();
        $this->approvalService = new ApprovalCenterService();
        $this->conversationService = new RequestConversationService();
    }

    /**
     * Get user info from JWT or session
     */
    protected function getUserInfo()
    {
        // This would typically extract from JWT token
        // For now, return from session or request
        $session = session();
        return [
            'user_id' => $session->get('user_id') ?? $this->request->getHeaderLine('X-User-Id'),
            'school_id' => $session->get('school_id') ?? $this->request->getHeaderLine('X-School-Id'),
            'role_id' => $session->get('role_id') ?? $this->request->getHeaderLine('X-Role-Id'),
        ];
    }

    /**
     * Standard API response format
     */
    protected function apiResponse($result, $successCode = 200, $errorCode = 400)
    {
        if ($result['success']) {
            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $result['data'],
                'ErrorObject' => '',
            ], $successCode);
        } else {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $result['error'],
            ], $errorCode);
        }
    }

    // ==================== PROFILE CHANGE REQUESTS ====================

    public function createProfileChangeRequest()
    {
        $userInfo = $this->getUserInfo();
        $data = $this->request->getJSON(true);

        $result = $this->profileService->createProfileChangeRequest(
            (int) $userInfo['user_id'],
            (int) $userInfo['school_id'],
            $data['changes'] ?? [],
            $data['reason'] ?? null
        );

        return $this->apiResponse($result, 201);
    }

    public function listProfileChangeRequests()
    {
        $userInfo = $this->getUserInfo();

        $filters = [
            'school_id' => $userInfo['school_id'],
            'student_id' => $this->request->getGet('student_id') ?? $userInfo['user_id'],
            'status' => $this->request->getGet('status'),
            'limit' => $this->request->getGet('limit') ?? 50,
            'offset' => $this->request->getGet('offset') ?? 0,
        ];

        $result = $this->profileService->listProfileChangeRequests($filters);
        return $this->apiResponse($result);
    }

    public function getProfileChangeRequest($requestId)
    {
        $result = $this->profileService->getProfileChangeRequest($requestId);
        return $this->apiResponse($result);
    }

    public function approveProfileChange($requestId)
    {
        $userInfo = $this->getUserInfo();
        $data = $this->request->getJSON(true);

        $result = $this->profileService->approveProfileChange(
            $requestId,
            (int) $userInfo['user_id'],
            $data['notes'] ?? null
        );

        return $this->apiResponse($result);
    }

    public function rejectProfileChange($requestId)
    {
        $userInfo = $this->getUserInfo();
        $data = $this->request->getJSON(true);

        if (empty($data['reason'])) {
            return $this->apiResponse(['success' => false, 'data' => null, 'error' => 'Rejection reason is required'], 200, 400);
        }

        $result = $this->profileService->rejectProfileChange(
            $requestId,
            (int) $userInfo['user_id'],
            $data['reason']
        );

        return $this->apiResponse($result);
    }

    // ==================== ABSENCE REQUESTS ====================

    public function createAbsenceRequest()
    {
        $userInfo = $this->getUserInfo();
        $data = $this->request->getJSON(true);

        $result = $this->absenceService->createAbsenceRequest(
            (int) $userInfo['user_id'],
            (int) $userInfo['school_id'],
            $data
        );

        return $this->apiResponse($result, 201);
    }

    public function listAbsenceRequests()
    {
        $userInfo = $this->getUserInfo();

        $filters = [
            'school_id' => $userInfo['school_id'],
            'student_id' => $this->request->getGet('student_id') ?? $userInfo['user_id'],
            'status' => $this->request->getGet('status'),
            'limit' => $this->request->getGet('limit') ?? 50,
            'offset' => $this->request->getGet('offset') ?? 0,
        ];

        $result = $this->absenceService->listAbsenceRequests($filters);
        return $this->apiResponse($result);
    }

    public function getAbsenceRequest($absenceId)
    {
        $result = $this->absenceService->getAbsenceRequest($absenceId);
        return $this->apiResponse($result);
    }

    public function approveAbsence($absenceId)
    {
        $userInfo = $this->getUserInfo();
        $data = $this->request->getJSON(true);

        $result = $this->absenceService->approveAbsence(
            $absenceId,
            (int) $userInfo['user_id'],
            $data['notes'] ?? null
        );

        return $this->apiResponse($result);
    }

    public function rejectAbsence($absenceId)
    {
        $userInfo = $this->getUserInfo();
        $data = $this->request->getJSON(true);

        if (empty($data['reason'])) {
            return $this->apiResponse(['success' => false, 'data' => null, 'error' => 'Rejection reason is required'], 200, 400);
        }

        $result = $this->absenceService->rejectAbsence(
            $absenceId,
            (int) $userInfo['user_id'],
            $data['reason']
        );

        return $this->apiResponse($result);
    }

    public function getAbsencesForClass($classId)
    {
        $startDate = $this->request->getGet('from_date') ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $this->request->getGet('to_date') ?? date('Y-m-d');

        $result = $this->absenceService->getAbsencesForClass($classId, $startDate, $endDate);
        return $this->apiResponse($result);
    }

    // ==================== SPECIAL REQUESTS ====================

    public function createSpecialRequest()
    {
        $userInfo = $this->getUserInfo();
        $data = $this->request->getJSON(true);

        $result = $this->specialRequestService->createSpecialRequest(
            (int) $userInfo['user_id'],
            (int) $userInfo['school_id'],
            $data
        );

        return $this->apiResponse($result, 201);
    }

    public function listSpecialRequests()
    {
        $userInfo = $this->getUserInfo();

        $filters = [
            'school_id' => $userInfo['school_id'],
            'student_id' => $this->request->getGet('student_id'),
            'status' => $this->request->getGet('status'),
            'request_type' => $this->request->getGet('request_type'),
            'priority' => $this->request->getGet('priority'),
            'assigned_to' => $this->request->getGet('assigned_to'),
            'limit' => $this->request->getGet('limit') ?? 50,
            'offset' => $this->request->getGet('offset') ?? 0,
        ];

        // If student, only show their own requests
        if ($userInfo['role_id'] == 5) {
            $filters['student_id'] = $userInfo['user_id'];
        }

        $result = $this->specialRequestService->listSpecialRequests($filters);
        return $this->apiResponse($result);
    }

    public function getSpecialRequest($requestId)
    {
        $result = $this->specialRequestService->getSpecialRequest($requestId);
        return $this->apiResponse($result);
    }

    public function updateSpecialRequest($requestId)
    {
        $userInfo = $this->getUserInfo();
        $data = $this->request->getJSON(true);

        $result = $this->specialRequestService->updateRequestStatus(
            $requestId,
            $data['status'],
            (int) $userInfo['user_id'],
            $data['notes'] ?? null
        );

        return $this->apiResponse($result);
    }

    public function assignSpecialRequest($requestId)
    {
        $userInfo = $this->getUserInfo();
        $data = $this->request->getJSON(true);

        if (empty($data['assigned_to'])) {
            return $this->apiResponse(['success' => false, 'data' => null, 'error' => 'assigned_to is required'], 200, 400);
        }

        $result = $this->specialRequestService->assignRequest(
            $requestId,
            $data['assigned_to'],
            (int) $userInfo['user_id']
        );

        return $this->apiResponse($result);
    }

    public function getRequestTypes()
    {
        $userInfo = $this->getUserInfo();
        $result = $this->specialRequestService->getRequestTypeConfig((int) $userInfo['school_id']);
        return $this->apiResponse($result);
    }

    public function getAllRequestTypes()
    {
        $userInfo = $this->getUserInfo();
        $result = $this->specialRequestService->getAllRequestTypes((int) $userInfo['school_id']);
        return $this->apiResponse($result);
    }

    public function createRequestType()
    {
        $userInfo = $this->getUserInfo();
        $data = $this->request->getJSON(true);

        $result = $this->specialRequestService->createRequestType((int) $userInfo['school_id'], $data);
        return $this->apiResponse($result, 201);
    }

    public function updateRequestType($typeId)
    {
        $data = $this->request->getJSON(true);
        $result = $this->specialRequestService->updateRequestType($typeId, $data);
        return $this->apiResponse($result);
    }

    // ==================== DOCUMENTS ====================

    public function uploadDocument()
    {
        $userInfo = $this->getUserInfo();

        $file = $this->request->getFile('file');
        if (!$file) {
            return $this->apiResponse(['success' => false, 'data' => null, 'error' => 'No file uploaded'], 200, 400);
        }

        $fileData = [
            'name' => $file->getName(),
            'type' => $file->getMimeType(),
            'tmp_name' => $file->getTempName(),
            'size' => $file->getSize(),
        ];

        $metadata = [
            'document_type' => $this->request->getPost('document_type'),
            'description' => $this->request->getPost('description'),
            'related_request_type' => $this->request->getPost('related_request_type'),
            'related_request_id' => $this->request->getPost('related_request_id'),
        ];

        $result = $this->documentService->uploadDocument(
            (int) $userInfo['user_id'],
            (int) $userInfo['school_id'],
            $fileData,
            $metadata
        );

        return $this->apiResponse($result, 201);
    }

    public function listDocuments()
    {
        $userInfo = $this->getUserInfo();

        $filters = [
            'document_type' => $this->request->getGet('document_type'),
            'status' => $this->request->getGet('status'),
            'limit' => $this->request->getGet('limit') ?? 50,
            'offset' => $this->request->getGet('offset') ?? 0,
        ];

        $studentId = $userInfo['role_id'] == 5 ? $userInfo['user_id'] : $this->request->getGet('student_id');

        $result = $this->documentService->listDocuments((int) $studentId, (int) $userInfo['school_id'], $filters);
        return $this->apiResponse($result);
    }

    public function getDocument($documentId)
    {
        $result = $this->documentService->getDocument((int) $documentId);
        return $this->apiResponse($result);
    }

    public function downloadDocument($documentId)
    {
        $userInfo = $this->getUserInfo();

        $result = $this->documentService->downloadDocument(
            (int) $documentId,
            (int) $userInfo['user_id'],
            (int) $userInfo['role_id']
        );

        if (!$result['success']) {
            return $this->apiResponse($result, 200, 403);
        }

        // Stream the file
        return $this->response->download($result['file_path'], null)
                             ->setFileName($result['original_filename']);
    }

    public function deleteDocument($documentId)
    {
        $userInfo = $this->getUserInfo();
        $result = $this->documentService->deleteDocument((int) $documentId, (int) $userInfo['user_id']);
        return $this->apiResponse($result);
    }

    public function approveDocument($documentId)
    {
        $userInfo = $this->getUserInfo();
        $data = $this->request->getJSON(true);

        $result = $this->documentService->approveDocument(
            (int) $documentId,
            (int) $userInfo['user_id'],
            $data['notes'] ?? null
        );

        return $this->apiResponse($result);
    }

    public function rejectDocument($documentId)
    {
        $userInfo = $this->getUserInfo();
        $data = $this->request->getJSON(true);

        if (empty($data['notes'])) {
            return $this->apiResponse(['success' => false, 'data' => null, 'error' => 'Rejection notes are required'], 200, 400);
        }

        $result = $this->documentService->rejectDocument(
            (int) $documentId,
            (int) $userInfo['user_id'],
            $data['notes']
        );

        return $this->apiResponse($result);
    }

    // ==================== APPROVAL CENTER ====================

    public function getApprovalDashboard()
    {
        $userInfo = $this->getUserInfo();
        $result = $this->approvalService->getDashboardStats((int) $userInfo['school_id']);
        return $this->apiResponse($result);
    }

    public function getAllPendingRequests()
    {
        $userInfo = $this->getUserInfo();

        $filters = [
            'type' => $this->request->getGet('type') ?? 'all',
            'limit' => $this->request->getGet('limit') ?? 50,
            'offset' => $this->request->getGet('offset') ?? 0,
        ];

        $result = $this->approvalService->getAllPendingRequests((int) $userInfo['school_id'], $filters);
        return $this->apiResponse($result);
    }

    public function bulkApprove()
    {
        $userInfo = $this->getUserInfo();
        $data = $this->request->getJSON(true);

        if (empty($data['request_type']) || empty($data['request_ids'])) {
            return $this->apiResponse(['success' => false, 'data' => null, 'error' => 'request_type and request_ids are required'], 200, 400);
        }

        $result = $this->approvalService->bulkApprove(
            $data['request_type'],
            $data['request_ids'],
            (int) $userInfo['user_id']
        );

        return $this->apiResponse($result);
    }

    public function bulkReject()
    {
        $userInfo = $this->getUserInfo();
        $data = $this->request->getJSON(true);

        if (empty($data['request_type']) || empty($data['request_ids']) || empty($data['reason'])) {
            return $this->apiResponse(['success' => false, 'data' => null, 'error' => 'request_type, request_ids, and reason are required'], 200, 400);
        }

        $result = $this->approvalService->bulkReject(
            $data['request_type'],
            $data['request_ids'],
            (int) $userInfo['user_id'],
            $data['reason']
        );

        return $this->apiResponse($result);
    }

    public function getAdminWorkload()
    {
        $userInfo = $this->getUserInfo();
        $adminId = $this->request->getGet('admin_id') ?? $userInfo['user_id'];

        $result = $this->approvalService->getAdminWorkload((int) $adminId, (int) $userInfo['school_id']);
        return $this->apiResponse($result);
    }

    // ==================== STUDENT CLASSES ====================

    /**
     * Get student's enrolled classes for absence request
     */
    public function getStudentClasses()
    {
        $userInfo = $this->getUserInfo();
        $db = \Config\Database::connect();

        try {
            $classes = $db->table('student_class sc')
                ->select('
                    sc.class_id,
                    c.class_name,
                    c.class_code,
                    CONCAT(COALESCE(up.first_name, ""), " ", COALESCE(up.last_name, "")) as teacher_name
                ')
                ->join('class c', 'c.class_id = sc.class_id', 'left')
                ->join('user_profile up', 'up.user_id = c.teacher_id', 'left')
                ->where('sc.student_id', (int) $userInfo['user_id'])
                ->where('sc.status', '1')
                ->where('c.status', '1')
                ->orderBy('c.class_name', 'ASC')
                ->get()
                ->getResultArray();

            return $this->apiResponse([
                'success' => true,
                'data' => $classes
            ]);

        } catch (\Throwable $e) {
            log_message('error', 'StudentPortalAPI::getStudentClasses - ' . $e->getMessage());
            return $this->apiResponse([
                'success' => false,
                'data' => null,
                'error' => 'Unable to load student classes'
            ], 200, 500);
        }
    }

    // ==================== CONVERSATIONS ====================

    public function addMessage($requestType, $requestId)
    {
        $userInfo = $this->getUserInfo();
        $data = $this->request->getJSON(true);

        if (empty($data['message'])) {
            return $this->apiResponse(['success' => false, 'data' => null, 'error' => 'Message is required'], 200, 400);
        }

        $result = $this->conversationService->addMessage(
            (int) $userInfo['school_id'],
            $requestType,
            $requestId,
            (int) $userInfo['user_id'],
            (int) $userInfo['role_id'],
            $data['message'],
            $data['is_internal'] ?? false
        );

        return $this->apiResponse($result, 201);
    }

    public function getConversation($requestType, $requestId)
    {
        $userInfo = $this->getUserInfo();

        $result = $this->conversationService->getConversation(
            $requestType,
            $requestId,
            (int) $userInfo['user_id'],
            (int) $userInfo['role_id']
        );

        return $this->apiResponse($result);
    }
}
