<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Unified Content Controller
 * Handles the new unified content creator workflow
 */
class UnifiedContent extends ResourceController
{
    protected $contentModel;
    protected $format = 'json';

    public function __construct()
    {
        $this->contentModel = new \App\Models\V1\ContentModel();
    }

    /**
     * Create new content
     * POST /unified-content/create
     */
    public function create(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON(true) ?? [];
            if (empty($data)) {
                $data = $this->request->getPost() ?? [];
            }

            log_message('debug', '📝 UnifiedContent::create called with data: ' . json_encode($data));

            // Validate required fields
            // Updated: subject_ids and grade_ids are now arrays
            $requiredFields = ['content_name', 'content_type', 'content_format', 'subject_ids', 'grade_ids', 'folder_id', 'created_by', 'school_id'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || (is_array($data[$field]) && empty($data[$field])) || (!is_array($data[$field]) && $data[$field] === '')) {
                    return $this->respond([
                        'IsSuccess' => false,
                        'success' => false,
                        'message' => "Missing required field: $field",
                        'ErrorObject' => "Missing required field: $field"
                    ], 400);
                }
            }

            // Prepare content data
            // Map API field names to database column names
            $contentData = [
                'name' => $data['content_name'], // Database uses 'name', not 'content_name'
                'content_type' => $data['content_type'],
                'content_format' => $data['content_format'],
                'description' => $data['description'] ?? '',
                // NEW: Convert subject_ids to subject (database stores as string, comma-separated)
                'subject' => is_array($data['subject_ids']) ? implode(',', $data['subject_ids']) : (string)$data['subject_ids'],
                // NEW: Convert grade_ids to grade (database stores as string, comma-separated)
                'grade' => is_array($data['grade_ids']) ? implode(',', $data['grade_ids']) : (string)$data['grade_ids'],
                'tags' => !empty($data['tags']) ? implode(',', $data['tags']) : '',
                'school_id' => $data['school_id'], // Required field

                // Access control - database uses 'access' column
                'access' => $data['access_level'] ?? $data['access'] ?? '1', // 1=private(within school), 2=private(within user), 3=public, 4=private(within corporate)

                // Assignment/Assessment specific
                'content_duration' => $data['content_duration'] ?? 0,
                // Note: shuffle_questions, allow_late_submission, feedback_timing are not in content table
                // These may be stored in class_content or classroom_content tables if needed

                // Delivery - use 'links' column for link_url
                'links' => $data['link_url'] ?? null,
                'file_path' => $data['pdf_url'] ?? null, // PDF URL stored in file_path

                // Cover image - database uses 'profile_url' and 'profile_thumb_url'
                'profile_url' => $data['cover_image_url'] ?? $data['profile_url'] ?? null,
                'profile_thumb_url' => $data['cover_image_thumb'] ?? $data['profile_thumb_url'] ?? null,

                // Download permissions - database uses 'download' column
                'download' => isset($data['allow_download']) ? ($data['allow_download'] ? 1 : 0) : (isset($data['download']) ? $data['download'] : 1),

                // Publishing
                'status' => $data['status'] ?? '1', // 1=published, 5=draft

                // Meta
                'created_by' => $data['created_by'],
                'created_date' => date('Y-m-d H:i:s'),
                'modified_by' => $data['created_by'], // Set to same as created_by initially
                'modified_date' => date('Y-m-d H:i:s')
            ];

            // Insert content
            $contentId = $this->contentModel->addContentUnified($contentData);

            if (!$contentId) {
                throw new \Exception('Failed to create content');
            }

            // Handle folder_id (batch_id) - insert into classroom_content table
            if (!empty($data['folder_id'])) {
                $db = \Config\Database::connect();
                $batchId = $data['folder_id'];
                
                // Check if batch content already exists
                $existing = $this->contentModel->checkBatchContent($batchId, $contentId);
                if (empty($existing)) {
                    $classroomContent = [
                        'batch_id' => $batchId,
                        'school_id' => $data['school_id'] ?? null,
                        'content_id' => $contentId,
                        'status' => 1,
                        'start_time' => '00:00:00',
                        'end_time' => '23:59:00',
                        'created_by' => $data['created_by'],
                        'created_date' => date('Y-m-d H:i:s')
                    ];
                    
                    // Handle available_from and available_until if provided
                    if (!empty($data['available_from'])) {
                        $classroomContent['start_date'] = date('Y-m-d', strtotime($data['available_from']));
                    }
                    if (!empty($data['available_until'])) {
                        $classroomContent['end_date'] = date('Y-m-d', strtotime($data['available_until']));
                    }
                    
                    $db->table('classroom_content')->insert($classroomContent);
                }
            }

            // Add questions if format is Questions
            if ($data['content_format'] === '3' && !empty($data['questions'])) {
                foreach ($data['questions'] as $index => $question) {
                    // Flatten type_specific_data into the main question object
                    $typeSpecificData = $question['type_specific_data'] ?? [];
                    unset($question['type_specific_data']);

                    $questionData = array_merge($question, $typeSpecificData, [
                        'content_id' => $contentId,
                        'created_by' => $data['created_by']
                    ]);

                    $this->contentModel->addQuestionUnified($questionData);
                }
            }

            log_message('info', '✅ UnifiedContent::create - Content created successfully: ' . $contentId);

            return $this->respond([
                'IsSuccess' => true,
                'success' => true,
                'message' => 'Content created successfully',
                'contentId' => $contentId,
                'content_id' => $contentId,
                'ResponseObject' => [
                    'content_id' => $contentId,
                    'contentId' => $contentId
                ],
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            log_message('error', '❌ UnifiedContent::create error: ' . $e->getMessage());
            return $this->respond([
                'IsSuccess' => false,
                'success' => false,
                'message' => 'Failed to create content: ' . $e->getMessage(),
                'ErrorObject' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update existing content
     * POST /unified-content/update/{id}
     */
    public function update($contentId = null): ResponseInterface
    {
        try {
            if (!$contentId) {
                return $this->respond([
                    'IsSuccess' => false,
                    'success' => false,
                    'message' => 'Content ID is required',
                    'ErrorObject' => 'Content ID is required'
                ], 400);
            }

            $data = $this->request->getJSON(true) ?? [];
            if (empty($data)) {
                $data = $this->request->getPost() ?? [];
            }

            log_message('debug', '📝 UnifiedContent::update called for content: ' . $contentId);

            // Prepare update data
            // Map API field names to database column names
            $contentData = [
                'name' => $data['content_name'], // Database uses 'name', not 'content_name'
                'content_type' => $data['content_type'],
                'content_format' => $data['content_format'],
                'description' => $data['description'] ?? '',
                // NEW: Convert subject_ids to subject (database stores as string, comma-separated)
                'subject' => is_array($data['subject_ids']) ? implode(',', $data['subject_ids']) : (string)$data['subject_ids'],
                // NEW: Convert grade_ids to grade (database stores as string, comma-separated)
                'grade' => is_array($data['grade_ids']) ? implode(',', $data['grade_ids']) : (string)$data['grade_ids'],
                'tags' => !empty($data['tags']) ? implode(',', $data['tags']) : '',

                // Access control - database uses 'access' column
                'access' => $data['access_level'] ?? $data['access'] ?? '1', // 1=private(within school), 2=private(within user), 3=public, 4=private(within corporate)

                'content_duration' => $data['content_duration'] ?? 0,
                'links' => $data['link_url'] ?? null,
                'file_path' => $data['pdf_url'] ?? null, // PDF URL stored in file_path

                // Cover image - database uses 'profile_url' and 'profile_thumb_url'
                'profile_url' => $data['cover_image_url'] ?? $data['profile_url'] ?? null,
                'profile_thumb_url' => $data['cover_image_thumb'] ?? $data['profile_thumb_url'] ?? null,

                // Download permissions - database uses 'download' column
                'download' => isset($data['allow_download']) ? ($data['allow_download'] ? 1 : 0) : (isset($data['download']) ? $data['download'] : 1),

                'status' => $data['status'] ?? '1',
                'modified_by' => $data['created_by'] ?? $data['modified_by'] ?? null,
                'modified_date' => date('Y-m-d H:i:s')
            ];

            // Update content
            $updated = $this->contentModel->updateContentUnified($contentId, $contentData);

            if (!$updated) {
                throw new \Exception('Failed to update content');
            }

            // Update questions if provided
            if ($data['content_format'] === '3' && !empty($data['questions'])) {
                // Delete existing questions
                $this->contentModel->deleteQuestionsForContent($contentId);

                // Add new questions
                foreach ($data['questions'] as $index => $question) {
                    // Flatten type_specific_data into the main question object (same as create method)
                    $typeSpecificData = $question['type_specific_data'] ?? [];
                    unset($question['type_specific_data']);

                    $questionData = array_merge($question, $typeSpecificData, [
                        'content_id' => $contentId,
                        'created_by' => $data['created_by'] ?? $data['modified_by'] ?? null
                    ]);

                    $this->contentModel->addQuestionUnified($questionData);
                }
            }

            log_message('info', '✅ UnifiedContent::update - Content updated successfully: ' . $contentId);

            return $this->respond([
                'IsSuccess' => true,
                'success' => true,
                'message' => 'Content updated successfully',
                'contentId' => $contentId,
                'ResponseObject' => ['content_id' => $contentId],
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            log_message('error', '❌ UnifiedContent::update error: ' . $e->getMessage());
            return $this->respond([
                'IsSuccess' => false,
                'success' => false,
                'message' => 'Failed to update content: ' . $e->getMessage(),
                'ErrorObject' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save draft
     * POST /unified-content/save-draft
     */
    public function saveDraft(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON(true) ?? [];
            if (empty($data)) {
                $data = $this->request->getPost() ?? [];
            }

            log_message('debug', '💾 UnifiedContent::saveDraft called');

            // Save to drafts table or use status=1
            $draftData = [
                'user_id' => $data['userId'] ?? $data['user_id'] ?? null,
                'state_data' => json_encode($data['stateData'] ?? $data),
                'timestamp' => $data['timestamp'] ?? date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $draftId = $this->contentModel->saveDraft($draftData);

            return $this->respond([
                'IsSuccess' => true,
                'success' => true,
                'message' => 'Draft saved successfully',
                'draftId' => $draftId,
                'draft_id' => $draftId,
                'ResponseObject' => ['draft_id' => $draftId],
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            log_message('error', '❌ UnifiedContent::saveDraft error: ' . $e->getMessage());
            return $this->respond([
                'IsSuccess' => false,
                'success' => false,
                'message' => 'Failed to save draft: ' . $e->getMessage(),
                'ErrorObject' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Load draft
     * GET /unified-content/load-draft/{id}
     */
    public function loadDraft($draftId = null): ResponseInterface
    {
        try {
            if (!$draftId) {
                return $this->respond([
                    'IsSuccess' => false,
                    'success' => false,
                    'message' => 'Draft ID is required',
                    'ErrorObject' => 'Draft ID is required'
                ], 400);
            }

            $draft = $this->contentModel->getDraft($draftId);

            if (!$draft) {
                return $this->respond([
                    'IsSuccess' => false,
                    'success' => false,
                    'message' => 'Draft not found',
                    'ErrorObject' => 'Draft not found'
                ], 404);
            }

            return $this->respond([
                'IsSuccess' => true,
                'success' => true,
                'message' => 'Draft loaded successfully',
                'ResponseObject' => $draft,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            log_message('error', '❌ UnifiedContent::loadDraft error: ' . $e->getMessage());
            return $this->respond([
                'IsSuccess' => false,
                'success' => false,
                'message' => 'Failed to load draft: ' . $e->getMessage(),
                'ErrorObject' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Load existing content for editing
     * GET /unified-content/load-content/{id}
     */
    public function loadContent($contentId = null): ResponseInterface
    {
        try {
            if (!$contentId) {
                return $this->respond([
                    'IsSuccess' => false,
                    'success' => false,
                    'message' => 'Content ID is required',
                    'ErrorObject' => 'Content ID is required'
                ], 400);
            }

            $content = $this->contentModel->getContentById($contentId);

            if (!$content) {
                return $this->respond([
                    'IsSuccess' => false,
                    'success' => false,
                    'message' => 'Content not found',
                    'ErrorObject' => 'Content not found'
                ], 404);
            }

            // Load folder/batch information from classroom_content table
            $db = \Config\Database::connect();
            $classroomContent = $db->table('classroom_content')
                ->where('content_id', $contentId)
                ->orderBy('created_date', 'DESC')
                ->get()
                ->getRowArray();

            if ($classroomContent) {
                $content['folder_id'] = $classroomContent['batch_id'];
                $content['batch_id'] = $classroomContent['batch_id'];

                // Get folder name from batch table
                $batch = $db->table('batch')
                    ->where('batch_id', $classroomContent['batch_id'])
                    ->get()
                    ->getRowArray();

                if ($batch) {
                    $content['folder_name'] = $batch['batch_name'];
                    $content['batch_name'] = $batch['batch_name'];
                }
            }

            // Load questions if format is Questions
            if ($content['content_format'] === '3') {
                try {
                    $content['questions'] = $this->contentModel->getQuestionsByContentId($contentId);
                } catch (\Exception $e) {
                    // Table might not exist yet - return empty questions array
                    log_message('warning', '⚠️ UnifiedContent::loadContent - Could not load questions: ' . $e->getMessage());
                    $content['questions'] = [];
                }
            } else {
                $content['questions'] = [];
            }

            // Parse tags
            if (!empty($content['tags'])) {
                $content['tags'] = explode(',', $content['tags']);
            }

            return $this->respond([
                'IsSuccess' => true,
                'success' => true,
                'message' => 'Content loaded successfully',
                'ResponseObject' => $content,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            log_message('error', '❌ UnifiedContent::loadContent error: ' . $e->getMessage());
            return $this->respond([
                'IsSuccess' => false,
                'success' => false,
                'message' => 'Failed to load content: ' . $e->getMessage(),
                'ErrorObject' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload PDF file
     * POST /unified-content/upload-pdf
     */
    public function uploadPdf(): ResponseInterface
    {
        try {
            $file = $this->request->getFile('pdf_file');

            if (!$file || !$file->isValid()) {
                return $this->respond([
                    'IsSuccess' => false,
                    'success' => false,
                    'message' => 'No file uploaded or file is invalid',
                    'ErrorObject' => 'No file uploaded'
                ], 400);
            }

            // Validate file type
            if ($file->getMimeType() !== 'application/pdf') {
                return $this->respond([
                    'IsSuccess' => false,
                    'success' => false,
                    'message' => 'Only PDF files are allowed',
                    'ErrorObject' => 'Invalid file type'
                ], 400);
            }

            // Validate file size (50MB max)
            if ($file->getSize() > 50 * 1024 * 1024) {
                return $this->respond([
                    'IsSuccess' => false,
                    'success' => false,
                    'message' => 'File size must be less than 50MB',
                    'ErrorObject' => 'File too large'
                ], 400);
            }

            // Move file to upload directory
            $uploadPath = WRITEPATH . 'uploads/content/pdf/';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }

            $newName = $file->getRandomName();
            $file->move($uploadPath, $newName);

            $fileUrl = base_url('writable/uploads/content/pdf/' . $newName);

            log_message('info', '✅ UnifiedContent::uploadPdf - File uploaded: ' . $newName);

            return $this->respond([
                'IsSuccess' => true,
                'success' => true,
                'message' => 'File uploaded successfully',
                'ResponseObject' => [
                    'url' => $fileUrl,
                    'filename' => $newName,
                    'originalName' => $file->getClientName()
                ],
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            log_message('error', '❌ UnifiedContent::uploadPdf error: ' . $e->getMessage());
            return $this->respond([
                'IsSuccess' => false,
                'success' => false,
                'message' => 'Failed to upload file: ' . $e->getMessage(),
                'ErrorObject' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get batches/folders
     * GET /unified-content/batches?school_id={id}
     */
    public function getBatches(): ResponseInterface
    {
        try {
            $schoolId = $this->request->getGet('school_id');

            if (!$schoolId) {
                return $this->respond([
                    'IsSuccess' => false,
                    'success' => false,
                    'message' => 'School ID is required',
                    'ErrorObject' => 'School ID is required'
                ], 400);
            }

            $batches = $this->contentModel->getBatches($schoolId);

            return $this->respond([
                'IsSuccess' => true,
                'success' => true,
                'message' => 'Batches retrieved successfully',
                'ResponseObject' => $batches,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            log_message('error', '❌ UnifiedContent::getBatches error: ' . $e->getMessage());
            return $this->respond([
                'IsSuccess' => false,
                'success' => false,
                'message' => 'Failed to get batches: ' . $e->getMessage(),
                'ErrorObject' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get subjects
     * GET /unified-content/subjects
     */
    public function getSubjects(): ResponseInterface
    {
        try {
            $schoolId = $this->request->getGet('school_id');
            $subjects = $this->contentModel->getSubjects($schoolId);

            return $this->respond([
                'IsSuccess' => true,
                'success' => true,
                'message' => 'Subjects retrieved successfully',
                'ResponseObject' => $subjects,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            log_message('error', '❌ UnifiedContent::getSubjects error: ' . $e->getMessage());
            return $this->respond([
                'IsSuccess' => false,
                'success' => false,
                'message' => 'Failed to get subjects: ' . $e->getMessage(),
                'ErrorObject' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get grades
     * GET /unified-content/grades
     */
    public function getGrades(): ResponseInterface
    {
        try {
            $grades = $this->contentModel->getGrades();

            return $this->respond([
                'IsSuccess' => true,
                'success' => true,
                'message' => 'Grades retrieved successfully',
                'ResponseObject' => $grades,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            log_message('error', '❌ UnifiedContent::getGrades error: ' . $e->getMessage());
            return $this->respond([
                'IsSuccess' => false,
                'success' => false,
                'message' => 'Failed to get grades: ' . $e->getMessage(),
                'ErrorObject' => $e->getMessage()
            ], 500);
        }
    }
}
