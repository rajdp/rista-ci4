<?php

namespace App\Models;

use App\Models\V1\BaseModel;

class StudentDocumentModel extends BaseModel
{
    protected $table = 't_student_document';
    protected $primaryKey = 'document_id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'school_id',
        'student_id',
        'document_type',
        'original_filename',
        'stored_filename',
        'file_path',
        'file_size_bytes',
        'mime_type',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'description',
        'expiration_date',
        'related_request_type',
        'related_request_id',
        'is_deleted',
        'deleted_at',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'uploaded_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'school_id' => 'required|integer',
        'student_id' => 'required|integer',
        'document_type' => 'required|in_list[transcript,medical,permission_form,id,insurance,immunization,other]',
        'original_filename' => 'required|max_length[255]',
        'stored_filename' => 'required|max_length[255]',
        'file_path' => 'required|max_length[500]',
        'file_size_bytes' => 'required|integer',
    ];

    protected $validationMessages = [
        'school_id' => [
            'required' => 'School ID is required',
        ],
        'student_id' => [
            'required' => 'Student ID is required',
        ],
    ];

    /**
     * Get documents by student
     */
    public function getStudentDocuments($schoolId, $studentId, $filters = [], $limit = 50, $offset = 0)
    {
        $builder = $this->where('school_id', $schoolId)
                       ->where('student_id', $studentId)
                       ->where('is_deleted', 0);

        if (isset($filters['document_type'])) {
            $builder->where('document_type', $filters['document_type']);
        }

        if (isset($filters['status'])) {
            $builder->where('status', $filters['status']);
        }

        return $builder->orderBy('uploaded_at', 'DESC')
                      ->limit($limit, $offset)
                      ->findAll();
    }

    /**
     * Get pending documents for admin review
     */
    public function getPendingDocuments($schoolId, $limit = 50, $offset = 0)
    {
        return $this->where('school_id', $schoolId)
                    ->where('status', 'pending')
                    ->where('is_deleted', 0)
                    ->orderBy('uploaded_at', 'ASC')
                    ->limit($limit, $offset)
                    ->findAll();
    }

    /**
     * Get documents by status
     */
    public function getDocumentsByStatus($schoolId, $status, $limit = 50, $offset = 0)
    {
        return $this->where('school_id', $schoolId)
                    ->where('status', $status)
                    ->where('is_deleted', 0)
                    ->orderBy('uploaded_at', 'DESC')
                    ->limit($limit, $offset)
                    ->findAll();
    }

    /**
     * Get documents related to a request
     */
    public function getRelatedDocuments($requestType, $requestId)
    {
        return $this->where('related_request_type', $requestType)
                    ->where('related_request_id', $requestId)
                    ->where('is_deleted', 0)
                    ->findAll();
    }

    /**
     * Count documents by status
     */
    public function countByStatus($schoolId, $status = null)
    {
        $builder = $this->where('school_id', $schoolId)
                       ->where('is_deleted', 0);

        if ($status !== null) {
            $builder->where('status', $status);
        }

        return $builder->countAllResults();
    }

    /**
     * Update document status
     */
    public function updateStatus($documentId, $status, $reviewedBy, $notes = null)
    {
        $data = [
            'status' => $status,
            'reviewed_by' => $reviewedBy,
            'reviewed_at' => date('Y-m-d H:i:s'),
        ];

        if ($notes !== null) {
            $data['review_notes'] = $notes;
        }

        return $this->update($documentId, $data);
    }

    /**
     * Soft delete document
     */
    public function softDelete($documentId)
    {
        return $this->update($documentId, [
            'is_deleted' => 1,
            'deleted_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Check for expired documents
     */
    public function getExpiredDocuments($schoolId)
    {
        return $this->where('school_id', $schoolId)
                    ->where('expiration_date <', date('Y-m-d'))
                    ->where('status', 'approved')
                    ->where('is_deleted', 0)
                    ->findAll();
    }
}
