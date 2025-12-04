<?php

namespace App\Services\StudentPortal;

use App\Models\StudentDocumentModel;
use App\Models\StudentPortalAuditModel;

class DocumentService
{
    protected $documentModel;
    protected $auditModel;
    protected $uploadPath;
    protected $allowedMimeTypes;
    protected $maxFileSize;

    public function __construct()
    {
        $this->documentModel = new StudentDocumentModel();
        $this->auditModel = new StudentPortalAuditModel();
        $this->uploadPath = WRITEPATH . '../public/uploads/student-portal/';
        $this->allowedMimeTypes = [
            'application/pdf',
            'image/jpeg',
            'image/jpg',
            'image/png',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];
        $this->maxFileSize = 10 * 1024 * 1024; // 10MB
    }

    /**
     * Upload document
     *
     * @param int $studentId
     * @param int $schoolId
     * @param array $fileData ['name', 'type', 'tmp_name', 'size']
     * @param array $metadata ['document_type', 'description'?, 'related_request_type'?, 'related_request_id'?]
     * @return array
     */
    public function uploadDocument(int $studentId, int $schoolId, array $fileData, array $metadata): array
    {
        try {
            // Validate file
            $validation = $this->validateFile($fileData);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => $validation['error'],
                ];
            }

            // Ensure upload directory exists
            $schoolDir = $this->uploadPath . $schoolId . '/' . $studentId . '/';
            if (!is_dir($schoolDir)) {
                mkdir($schoolDir, 0755, true);
            }

            // Generate unique filename
            $extension = pathinfo($fileData['name'], PATHINFO_EXTENSION);
            $storedFilename = uniqid('doc_', true) . '.' . $extension;
            $fullPath = $schoolDir . $storedFilename;

            // Move uploaded file
            if (!move_uploaded_file($fileData['tmp_name'], $fullPath)) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'Failed to save uploaded file',
                ];
            }

            // Save document record
            $data = [
                'school_id' => $schoolId,
                'student_id' => $studentId,
                'document_type' => $metadata['document_type'],
                'original_filename' => $fileData['name'],
                'stored_filename' => $storedFilename,
                'file_path' => $schoolId . '/' . $studentId . '/' . $storedFilename,
                'file_size_bytes' => $fileData['size'],
                'mime_type' => $fileData['type'],
                'status' => 'pending',
                'description' => $metadata['description'] ?? null,
                'related_request_type' => $metadata['related_request_type'] ?? null,
                'related_request_id' => $metadata['related_request_id'] ?? null,
            ];

            $documentId = $this->documentModel->insert($data);

            if (!$documentId) {
                // Clean up uploaded file
                unlink($fullPath);
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'Failed to save document record: ' . json_encode($this->documentModel->errors()),
                ];
            }

            // Log the action
            $this->auditModel->logAction(
                $schoolId,
                'document',
                $documentId,
                'upload',
                $studentId,
                5, // Student role
                ['filename' => $fileData['name'], 'type' => $metadata['document_type']],
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            );

            log_message('info', "DocumentService: Uploaded document {$documentId} for student {$studentId}");

            return [
                'success' => true,
                'data' => [
                    'document_id' => $documentId,
                    'status' => 'pending',
                    'original_filename' => $fileData['name'],
                ],
                'error' => null,
            ];
        } catch (\Exception $e) {
            log_message('error', 'DocumentService::uploadDocument - ' . $e->getMessage());
            return [
                'success' => false,
                'data' => null,
                'error' => 'An error occurred while uploading the document',
            ];
        }
    }

    /**
     * List documents
     *
     * @param int $studentId
     * @param int $schoolId
     * @param array $filters ['document_type', 'status', 'limit', 'offset']
     * @return array
     */
    public function listDocuments(int $studentId, int $schoolId, array $filters = []): array
    {
        try {
            $limit = (int) ($filters['limit'] ?? 50);
            $offset = (int) ($filters['offset'] ?? 0);

            $documents = $this->documentModel->getStudentDocuments($schoolId, $studentId, $filters, $limit, $offset);

            return [
                'success' => true,
                'data' => $documents,
                'error' => null,
            ];
        } catch (\Exception $e) {
            log_message('error', 'DocumentService::listDocuments - ' . $e->getMessage());
            return [
                'success' => false,
                'data' => null,
                'error' => 'An error occurred while retrieving documents',
            ];
        }
    }

    /**
     * Get document metadata
     *
     * @param int $documentId
     * @return array
     */
    public function getDocument(int $documentId): array
    {
        try {
            $document = $this->documentModel->find($documentId);

            if (!$document) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'Document not found',
                ];
            }

            return [
                'success' => true,
                'data' => $document,
                'error' => null,
            ];
        } catch (\Exception $e) {
            log_message('error', 'DocumentService::getDocument - ' . $e->getMessage());
            return [
                'success' => false,
                'data' => null,
                'error' => 'An error occurred while retrieving the document',
            ];
        }
    }

    /**
     * Download document (with permission check)
     *
     * @param int $documentId
     * @param int $userId
     * @param int $roleId
     * @return array ['success', 'file_path', 'original_filename', 'mime_type', 'error']
     */
    public function downloadDocument(int $documentId, int $userId, int $roleId): array
    {
        try {
            $document = $this->documentModel->find($documentId);

            if (!$document) {
                return [
                    'success' => false,
                    'file_path' => null,
                    'original_filename' => null,
                    'mime_type' => null,
                    'error' => 'Document not found',
                ];
            }

            // Permission check: students can only download their own documents, admins can download any
            if ($roleId == 5 && $document['student_id'] != $userId) {
                return [
                    'success' => false,
                    'file_path' => null,
                    'original_filename' => null,
                    'mime_type' => null,
                    'error' => 'Permission denied',
                ];
            }

            $fullPath = $this->uploadPath . $document['file_path'];

            if (!file_exists($fullPath)) {
                return [
                    'success' => false,
                    'file_path' => null,
                    'original_filename' => null,
                    'mime_type' => null,
                    'error' => 'File not found on disk',
                ];
            }

            // Log the download
            $this->auditModel->logAction(
                $document['school_id'],
                'document',
                $documentId,
                'download',
                $userId,
                $roleId,
                null,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            );

            return [
                'success' => true,
                'file_path' => $fullPath,
                'original_filename' => $document['original_filename'],
                'mime_type' => $document['mime_type'],
                'error' => null,
            ];
        } catch (\Exception $e) {
            log_message('error', 'DocumentService::downloadDocument - ' . $e->getMessage());
            return [
                'success' => false,
                'file_path' => null,
                'original_filename' => null,
                'mime_type' => null,
                'error' => 'An error occurred while downloading the document',
            ];
        }
    }

    /**
     * Delete document (soft delete)
     *
     * @param int $documentId
     * @param int $userId
     * @return array
     */
    public function deleteDocument(int $documentId, int $userId): array
    {
        try {
            $document = $this->documentModel->find($documentId);

            if (!$document) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'Document not found',
                ];
            }

            // Soft delete
            $this->documentModel->softDelete($documentId);

            // Log the action
            $this->auditModel->logAction(
                $document['school_id'],
                'document',
                $documentId,
                'delete',
                $userId,
                5, // Assume student role for now
                null,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            );

            log_message('info', "DocumentService: Soft deleted document {$documentId} by user {$userId}");

            return [
                'success' => true,
                'data' => ['document_id' => $documentId],
                'error' => null,
            ];
        } catch (\Exception $e) {
            log_message('error', 'DocumentService::deleteDocument - ' . $e->getMessage());
            return [
                'success' => false,
                'data' => null,
                'error' => 'An error occurred while deleting the document',
            ];
        }
    }

    /**
     * Approve document (admin)
     *
     * @param int $documentId
     * @param int $adminId
     * @param string|null $notes
     * @return array
     */
    public function approveDocument(int $documentId, int $adminId, ?string $notes = null): array
    {
        try {
            $document = $this->documentModel->find($documentId);

            if (!$document) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'Document not found',
                ];
            }

            $this->documentModel->updateStatus($documentId, 'approved', $adminId, $notes);

            // Log the action
            $this->auditModel->logAction(
                $document['school_id'],
                'document',
                $documentId,
                'approve',
                $adminId,
                2, // Admin role
                null,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            );

            log_message('info', "DocumentService: Approved document {$documentId} by admin {$adminId}");

            return [
                'success' => true,
                'data' => ['document_id' => $documentId, 'status' => 'approved'],
                'error' => null,
            ];
        } catch (\Exception $e) {
            log_message('error', 'DocumentService::approveDocument - ' . $e->getMessage());
            return [
                'success' => false,
                'data' => null,
                'error' => 'An error occurred while approving the document',
            ];
        }
    }

    /**
     * Reject document (admin)
     *
     * @param int $documentId
     * @param int $adminId
     * @param string $notes
     * @return array
     */
    public function rejectDocument(int $documentId, int $adminId, string $notes): array
    {
        try {
            $document = $this->documentModel->find($documentId);

            if (!$document) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'Document not found',
                ];
            }

            $this->documentModel->updateStatus($documentId, 'rejected', $adminId, $notes);

            // Log the action
            $this->auditModel->logAction(
                $document['school_id'],
                'document',
                $documentId,
                'reject',
                $adminId,
                2, // Admin role
                ['reason' => $notes],
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            );

            log_message('info', "DocumentService: Rejected document {$documentId} by admin {$adminId}");

            return [
                'success' => true,
                'data' => ['document_id' => $documentId, 'status' => 'rejected'],
                'error' => null,
            ];
        } catch (\Exception $e) {
            log_message('error', 'DocumentService::rejectDocument - ' . $e->getMessage());
            return [
                'success' => false,
                'data' => null,
                'error' => 'An error occurred while rejecting the document',
            ];
        }
    }

    /**
     * Get pending documents for admin review
     *
     * @param int $schoolId
     * @param array $filters
     * @return array
     */
    public function getPendingDocuments(int $schoolId, array $filters = []): array
    {
        try {
            $limit = (int) ($filters['limit'] ?? 50);
            $offset = (int) ($filters['offset'] ?? 0);

            $documents = $this->documentModel->getPendingDocuments($schoolId, $limit, $offset);

            return [
                'success' => true,
                'data' => $documents,
                'error' => null,
            ];
        } catch (\Exception $e) {
            log_message('error', 'DocumentService::getPendingDocuments - ' . $e->getMessage());
            return [
                'success' => false,
                'data' => null,
                'error' => 'An error occurred while retrieving pending documents',
            ];
        }
    }

    /**
     * Validate uploaded file
     *
     * @param array $fileData
     * @return array
     */
    protected function validateFile(array $fileData): array
    {
        // Check if file was uploaded
        if (!isset($fileData['tmp_name']) || !is_uploaded_file($fileData['tmp_name'])) {
            return ['valid' => false, 'error' => 'No file uploaded'];
        }

        // Check file size
        if ($fileData['size'] > $this->maxFileSize) {
            return ['valid' => false, 'error' => 'File size exceeds maximum allowed (10MB)'];
        }

        // Check MIME type
        if (!in_array($fileData['type'], $this->allowedMimeTypes)) {
            return ['valid' => false, 'error' => 'File type not allowed. Allowed types: PDF, JPG, PNG, DOC, DOCX'];
        }

        // Additional security: check file extension
        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
        $extension = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions)) {
            return ['valid' => false, 'error' => 'File extension not allowed'];
        }

        return ['valid' => true, 'error' => null];
    }
}
