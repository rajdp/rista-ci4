<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

class Content extends ResourceController
{
    protected $contentModel;
    protected $format = 'json';

    public function __construct()
    {
        $this->contentModel = new \App\Models\V1\ContentModel();
    }

    /**
     * Get content sort master list
     */
    public function sortMaster(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            // Convert object to array if needed
            if (is_object($data)) {
                $data = (array) $data;
            }
            
            log_message('debug', '🔍 PAGINATION - Content::sortMaster received params: page_no=' . ($data['page_no'] ?? 'NOT SET') . ', records_per_page=' . ($data['records_per_page'] ?? 'NOT SET'));
            log_message('debug', '🔍 PAGINATION - All params: ' . json_encode($data));
            
            $sortList = $this->contentModel->sortMaster($data ?? []);
            
            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $sortList,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get test types
     */
    public function testType(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }
            
            log_message('debug', '📋 Content::testType called with params: ' . json_encode($params));
            
            $testTypes = $this->contentModel->getTestTypes($params);
            
            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $testTypes,
                'ErrorObject' => ''
            ]);
        } catch (\Exception $e) {
            log_message('error', '❌ Content::testType error: ' . $e->getMessage());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get content list
     */
    public function list(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }
            
            log_message('debug', '📋 Content::list called with params: ' . json_encode($params));
            
            $contentList = $this->contentModel->contentList($params);
            
            // Process links and tags
            $processedList = [];
            foreach ($contentList as $content) {
                $links = !empty($content['links']) ? array_filter(explode(',', $content['links'])) : [];
                $tags = !empty($content['tags']) ? array_filter(explode(',', $content['tags'])) : [];
                
                $content['links'] = $links;
                $content['tags'] = $tags;
                $processedList[] = $content;
            }
            
            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $processedList,
                'ErrorObject' => ''
            ]);
        } catch (\Exception $e) {
            log_message('error', '❌ Content::list error: ' . $e->getMessage());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get content detail by content_id
     */
    public function contentDetail(): ResponseInterface
    {
        try {
            $params = $this->request->getPost();
            
            if (empty($params)) {
                $params = $this->request->getJSON(true) ?? [];
            }

            // Validation
            if (empty($params['content_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Content Id should not be empty'
                ]);
            }

            $contentList = $this->contentModel->contentIdList($params);
            
            if ($contentList) {
                // Process file paths
                if (!empty($contentList['file_path']) && $contentList['file_path'] != '[]') {
                    $filePaths = json_decode($contentList['file_path'], true);
                    $contentList['file_path'] = is_array($filePaths) ? $filePaths : [];
                } else {
                    $contentList['file_path'] = [];
                }
                
                // Process answer key paths
                if (!empty($contentList['answerkey_path']) && $contentList['answerkey_path'] != '[]') {
                    $answerKeyPaths = json_decode($contentList['answerkey_path'], true);
                    $contentList['answerkey_path'] = is_array($answerKeyPaths) ? $answerKeyPaths : [];
                } else {
                    $contentList['answerkey_path'] = [];
                }
                
                // Get answers for the content
                $answers = $this->contentModel->answerList($params);
                $contentList['answers'] = is_array($answers) ? $answers : [];
                
                // Check if all questions are auto-gradable (for students)
                if (isset($params['role_id']) && $params['role_id'] == 5) {
                    $questionDetails = $this->contentModel->getQuestionDetails($params['content_id']);
                    if ($questionDetails && count($questionDetails) > 0) {
                        $autoGradeCount = 0;
                        foreach ($questionDetails as $question) {
                            if (isset($question['audo_grade']) && $question['audo_grade'] == 1) {
                                $autoGradeCount++;
                            }
                        }
                        $contentList['all_autograde'] = (count($questionDetails) == $autoGradeCount) ? 1 : 0;
                    } else {
                        $contentList['all_autograde'] = 0;
                    }
                }
                
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => $contentList,
                    'ErrorObject' => ''
                ]);
            } else {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Content not found'
                ]);
            }

        } catch (\Exception $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get passage list by content_id or all passages
     */
    public function listPassage(): ResponseInterface
    {
        try {
            $params = $this->request->getPost();
            
            if (empty($params)) {
                $params = $this->request->getJSON(true) ?? [];
            }

            $db = \Config\Database::connect();
            $builder = $db->table('passage p');
            $builder->select('p.*');
            
            // If passage_id is provided, get specific passage
            if (!empty($params['passage_id'])) {
                $builder->where('p.passage_id', $params['passage_id']);
            }
            
            // If content_id is provided, filter by content
            if (!empty($params['content_id'])) {
                $builder->join('question q', 'q.content_id = ' . $params['content_id'], 'left');
                $builder->where('q.passage_id = p.passage_id');
            }
            
            $builder->where('p.status', 1);
            $builder->groupBy('p.passage_id');
            $builder->orderBy('p.passage_id', 'DESC');
            
            $passages = $builder->get()->getResultArray();

            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $passages,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => [],
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get content details for editing
     */
    public function getEditContent(): ResponseInterface
    {
        try {
            $params = $this->request->getPost();
            
            if (empty($params)) {
                $params = $this->request->getJSON(true) ?? [];
            }

            // Validation
            if (empty($params['content_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Content Id should not be empty'
                ]);
            }

            $contentDetails = $this->contentModel->contentIdList($params);
            
            if ($contentDetails) {
                // Process file paths
                if (!empty($contentDetails['file_path']) && $contentDetails['file_path'] != '[]') {
                    $filePaths = json_decode($contentDetails['file_path'], true);
                    $contentDetails['file_path'] = is_array($filePaths) ? $filePaths : [];
                } else {
                    $contentDetails['file_path'] = [];
                }
                
                // Process answer key paths
                if (!empty($contentDetails['answerkey_path']) && $contentDetails['answerkey_path'] != '[]') {
                    $answerKeyPaths = json_decode($contentDetails['answerkey_path'], true);
                    $contentDetails['answerkey_path'] = is_array($answerKeyPaths) ? $answerKeyPaths : [];
                } else {
                    $contentDetails['answerkey_path'] = [];
                }

                // Process teacher version
                if (!empty($contentDetails['teacher_version']) && $contentDetails['teacher_version'] != '[]') {
                    $teacherVersion = json_decode($contentDetails['teacher_version'], true);
                    $contentDetails['teacher_version'] = is_array($teacherVersion) ? $teacherVersion : [];
                } else {
                    $contentDetails['teacher_version'] = [];
                }

                // Process links
                if (!empty($contentDetails['links']) && $contentDetails['links'] != '[]') {
                    $links = json_decode($contentDetails['links'], true);
                    $contentDetails['links'] = is_array($links) ? $links : [];
                } else {
                    $contentDetails['links'] = [];
                }

                // Process tags
                if (!empty($contentDetails['tags'])) {
                    $tags = explode(',', $contentDetails['tags']);
                    $contentDetails['tags'] = $tags;
                } else {
                    $contentDetails['tags'] = [];
                }

                // Process grade and subject
                if (!empty($contentDetails['grade'])) {
                    $grades = explode(',', $contentDetails['grade']);
                    $contentDetails['grade'] = array_map('intval', $grades);
                } else {
                    $contentDetails['grade'] = [];
                }

                if (!empty($contentDetails['subject'])) {
                    $subjects = explode(',', $contentDetails['subject']);
                    $contentDetails['subject'] = array_map('intval', $subjects);
                } else {
                    $contentDetails['subject'] = [];
                }
                
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => $contentDetails,
                    'ErrorObject' => ''
                ]);
            } else {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Content not found'
                ]);
            }

        } catch (\Exception $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get specified class list
     */
    public function specifiedClassList(): ResponseInterface
    {
        try {
            $params = $this->request->getPost();
            if (empty($params)) {
                $params = $this->request->getJSON(true) ?? [];
            }

            // Validation
            if (empty($params['class_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Class Id should not be empty'
                ]);
            }

            // Get class list
            $classIds = is_array($params['class_id']) ? implode(',', $params['class_id']) : $params['class_id'];
            
            $db = \Config\Database::connect();
            $query = $db->query("SELECT class_id, student_id,
                                (SELECT concat_ws(' ', up.first_name, up.last_name) FROM user_profile up 
                                 WHERE up.user_id = student_id) AS name 
                                FROM student_class
                                WHERE class_id IN ({$classIds}) AND status = 1");
            $classList = $query->getResultArray();

            // If no students found and school_id is provided, get all school students
            if (empty($classList) && isset($params['school_id']) && !empty($params['school_id'])) {
                $query = $db->query("SELECT upd.user_id as student_id,
                                    (SELECT concat_ws(' ', up.first_name, up.last_name) FROM user_profile up 
                                     WHERE up.user_id = upd.user_id) AS name 
                                    FROM user_profile_details upd
                                    WHERE upd.school_id = '{$params['school_id']}' AND upd.status = 1 
                                    AND upd.user_id IN (SELECT user_id FROM user WHERE role_id = 5 AND status = 1)");
                $classList = $query->getResultArray();
            }

            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $classList,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Add new content
     * Note: This is a complex method that handles content creation, answers, tags, and assignments
     */
    public function add(): ResponseInterface
    {
        try {
            // Try to get data from JSON body first (most common for Angular apps)
            $params = json_decode(file_get_contents('php://input'), true);
            
            // Fallback to POST data if JSON is empty
            if (empty($params)) {
                $params = $this->request->getPost();
            }
            
            // Fallback to getJSON if both are empty
            if (empty($params)) {
                $params = $this->request->getJSON(true) ?? [];
            }

            // Basic validation - only for new content creation (assign = 0)
            if (isset($params['assign']) && $params['assign'] == 0 && empty($params['name'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Content name should not be empty'
                ]);
            }

            $db = \Config\Database::connect();
            $commonModel = new \App\Models\V1\CommonModel();

            // If assign = 0, create new content
            if (isset($params['assign']) && $params['assign'] == 0) {
                // Prepare content data
                $data = [
                    'name' => $params['name'],
                    'description' => $params['description'] ?? '',
                    'school_id' => $params['school_id'] ?? null,
                    'grade' => isset($params['grade']) && is_array($params['grade']) ? implode(',', $params['grade']) : ($params['grade'] ?? ''),
                    'subject' => isset($params['subject']) && is_array($params['subject']) ? implode(',', $params['subject']) : ($params['subject'] ?? ''),
                    'tags' => isset($params['tags']) && is_array($params['tags']) && count($params['tags']) > 0 ? implode(',', $params['tags']) : '',
                    'content_type' => $params['content_type'] ?? '',
                    'content_format' => $params['content_format'] ?? '',
                    'editor_type' => $params['editor_type'] ?? '',
                    'file_path' => isset($params['file_path']) && $params['file_path'] != '' ? json_encode($params['file_path']) : '',
                    'answerkey_path' => isset($params['answerkey_path']) && is_array($params['answerkey_path']) && count($params['answerkey_path']) > 0 ? json_encode($params['answerkey_path']) : '',
                    'teacher_version' => isset($params['teacher_version']) && $params['teacher_version'] != '' ? json_encode($params['teacher_version']) : '',
                    'allow_answer_key' => isset($params['allow_answer_key']) ? $params['allow_answer_key'] : 0,
                    'links' => isset($params['links']) && count($params['links']) > 0 ? json_encode($params['links']) : '',
                    'file_text' => $params['file_text'] ?? '',
                    'download' => isset($params['download']) ? $params['download'] : 0,
                    'access' => $params['access'] ?? 0,
                    'status' => $params['status'] ?? 1,
                    'content_duration' => isset($params['content_duration']) && $params['content_duration'] != '' ? $params['content_duration'] : 0,
                    'created_by' => $params['user_id'] ?? null,
                    'created_date' => date('Y-m-d H:i:s')
                ];

                if (isset($params['role_id']) && $params['role_id'] == 6 && isset($params['corporate_id'])) {
                    $data['corporate_id'] = $params['corporate_id'];
                }

                if (isset($params['profile_url']) && isset($params['profile_thumb_url'])) {
                    $data['profile_url'] = $params['profile_url'];
                    $data['profile_thumb_url'] = $params['profile_thumb_url'];
                }

                // Insert content
                $builder = $db->table('content');
                $builder->insert($data);
                $contentId = $db->insertID();

                if (!$contentId) {
                    throw new \Exception('Failed to insert content');
                }

                // Insert classroom_content if batch_id is provided
                if (isset($params['batch_id']) && !empty($params['batch_id'])) {
                    $classroomContent = [];
                    $batchIds = is_array($params['batch_id']) ? $params['batch_id'] : [$params['batch_id']];
                    
                    foreach ($batchIds as $batchId) {
                        // Check if batch content already exists
                        $existing = $this->contentModel->checkBatchContent($batchId, $contentId);
                        if (empty($existing)) {
                            $classroomContent[] = [
                                'batch_id' => $batchId,
                                'school_id' => $params['school_id'] ?? null,
                                'content_id' => $contentId,
                                'status' => 1,
                                'start_time' => '00:00:00',
                                'end_time' => '23:59:00',
                                'created_by' => $params['user_id'] ?? null,
                                'created_date' => date('Y-m-d H:i:s')
                            ];
                        }
                    }

                    if (count($classroomContent) > 0) {
                        $builder = $db->table('classroom_content');
                        $builder->insertBatch($classroomContent);
                    }
                }

                // Insert answers if provided
                if (isset($params['answers']) && is_array($params['answers']) && count($params['answers']) > 0) {
                    $answersBuilder = $db->table('answers');
                    
                    foreach ($params['answers'] as $answerSection) {
                        if (isset($answerSection['section']) && is_array($answerSection['section'])) {
                            foreach ($answerSection['section'] as $section) {
                                if (isset($section['sub_questions']) && is_array($section['sub_questions'])) {
                                    foreach ($section['sub_questions'] as $subQuestion) {
                                        $answerData = [
                                            'content_id' => $contentId,
                                            'has_sub_question' => $section['has_sub_question'] ?? 0,
                                            'question_no' => $subQuestion['question_no'] ?? '',
                                            'section_heading' => $answerSection['heading'] ?? '',
                                            'question' => $subQuestion['question'] ?? '',
                                            'sub_question_no' => $subQuestion['sub_question_no'] ?? '',
                                            'question_type_id' => $subQuestion['question_type_id'] ?? '',
                                            'page_no' => $subQuestion['page_no'] ?? 0,
                                            'options' => $subQuestion['options'] ?? '',
                                            'array' => isset($subQuestion['array']) ? json_encode($subQuestion['array']) : '',
                                            'answer' => isset($subQuestion['answer']) ? (is_array($subQuestion['answer']) ? json_encode($subQuestion['answer']) : $subQuestion['answer']) : '',
                                            'editor_answer' => $subQuestion['editor_answer'] ?? '',
                                            'answer_explanation' => $subQuestion['answer_explanation'] ?? '',
                                            'auto_grade' => $subQuestion['auto_grade'] ?? 0,
                                            'points' => $subQuestion['points'] ?? 0,
                                            'difficulty' => $subQuestion['difficulty'] ?? 0,
                                            'allow_exact_match' => $subQuestion['allow_exact_match'] ?? 0,
                                            'allow_any_text' => $subQuestion['allow_any_text'] ?? 0,
                                            'match_case' => $subQuestion['match_case'] ?? 0,
                                            'minimum_line' => $subQuestion['minimum_line'] ?? 0,
                                            'created_by' => $params['user_id'] ?? null,
                                            'created_date' => date('Y-m-d H:i:s')
                                        ];
                                        
                                        $answersBuilder->insert($answerData);
                                    }
                                }
                            }
                        }
                    }
                }

                // Insert tags if provided
                if (isset($params['tags']) && is_array($params['tags']) && count($params['tags']) > 0) {
                    $tagsBuilder = $db->table('tags');
                    foreach ($params['tags'] as $tagName) {
                        // Check if tag already exists
                        $existingTag = $db->table('tags')
                            ->where('tag_name', $tagName)
                            ->where('user_id', $params['user_id'] ?? null)
                            ->where('status', 1)
                            ->get()
                            ->getResultArray();
                        
                        if (empty($existingTag)) {
                            $tagsBuilder->insert([
                                'tag_name' => $tagName,
                                'school_id' => $params['school_id'] ?? null,
                                'user_id' => $params['user_id'] ?? null,
                                'content_id' => $contentId
                            ]);
                        }
                    }
                }

                // Get the created content details
                $contentDetails = $this->contentModel->contentIdList(['content_id' => $contentId]);

                return $this->respond([
                    'IsSuccess' => true,
                    'Contentdetails' => $contentDetails,
                    'ResponseObject' => 'Content added successfully',
                    'ErrorObject' => ''
                ]);

            } elseif (isset($params['assign']) && $params['assign'] == 1) {
                // Handle assignment of existing content to classes
                if (!isset($params['classdetails']) || !is_array($params['classdetails'])) {
                    return $this->respond([
                        'IsSuccess' => false,
                        'ResponseObject' => null,
                        'ErrorObject' => 'Class details should not be empty'
                    ]);
                }

                $assignmentBuilder = $db->table('student_assign_content');
                
                foreach ($params['classdetails'] as $details) {
                    $startDate = $details['start_date'] ?? date('Y-m-d');
                    $endDate = !empty($details['end_date']) ? $details['end_date'] : '0000-00-00';
                    
                    $startTime = $this->normalizeTimeValue($details['start_time'] ?? null, '00:00:00');
                    $endTime = $this->normalizeTimeValue($details['end_time'] ?? null, '23:59:00');
                    
                    // Insert assignment data
                    $assignmentData = [
                        'class_id' => $details['class_id'] ?? null,
                        'content_id' => $details['content_id'] ?? null,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'status' => 1,
                        'created_by' => $params['user_id'] ?? null,
                        'created_date' => date('Y-m-d H:i:s')
                    ];
                    
                    $classId = (int) ($details['class_id'] ?? 0);
                    $contentId = (int) ($details['content_id'] ?? 0);

                    if ($classId === 0 || $contentId === 0) {
                        log_message('error', '❌ Content::add missing class_id or content_id in assignment details: ' . json_encode($details));
                        continue;
                    }

                    // Ensure single student_assign_content per class/content
                    $existingAssignment = $db->table('student_assign_content')
                        ->where('class_id', $classId)
                        ->where('content_id', $contentId)
                        ->get()
                        ->getRowArray();

                    if ($existingAssignment) {
                        $db->table('student_assign_content')
                            ->where('id', $existingAssignment['id'])
                            ->update([
                                'start_date' => $startDate,
                                'end_date' => $endDate,
                                'start_time' => $startTime,
                                'end_time' => $endTime,
                                'status' => 1,
                            ]);
                    } else {
                        $assignmentBuilder->insert($assignmentData);
                    }

                    // Mirror into class_content so curriculum views stay in sync
                    $classContentData = [
                        'class_id' => $classId,
                        'content_id' => $contentId,
                        'school_id' => $params['school_id'] ?? null,
                        'status' => 1,
                        'all_student' => isset($details['all_student']) ? (int) $details['all_student'] : 1,
                        'release_score' => isset($details['release_score']) ? (int) $details['release_score'] : 0,
                        'auto_review' => isset($details['auto_review']) ? (int) $details['auto_review'] : 0,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'notes' => $details['notes'] ?? null,
                        'downloadable' => isset($details['download']) ? (int) $details['download'] : 0,
                        'topic_id' => isset($details['topic_id']) && $details['topic_id'] !== '' ? (int) $details['topic_id'] : 0,
                        'is_accessible' => isset($details['allow_workspace']) ? (int) $details['allow_workspace'] : 0,
                        'created_by' => $params['user_id'] ?? null,
                        'created_date' => date('Y-m-d H:i:s'),
                        'modified_by' => $params['user_id'] ?? null,
                        'modified_date' => date('Y-m-d H:i:s')
                    ];
                    
                    // If updating an existing class_content record, respect the provided id
                    $classContentTable = $db->table('class_content');
                    $existingClassContent = null;

                    if (!empty($details['class_content_id'])) {
                        $existingClassContent = $classContentTable
                            ->where('id', $details['class_content_id'])
                            ->get()
                            ->getRowArray();
                    }

                    if (!$existingClassContent) {
                        $existingClassContent = $classContentTable
                            ->where('class_id', $classId)
                            ->where('content_id', $contentId)
                            ->where('status !=', 2)
                            ->orderBy('id', 'ASC')
                            ->get()
                            ->getRowArray();
                    }

                    if ($existingClassContent) {
                        $updateData = $classContentData;
                        unset($updateData['created_by'], $updateData['created_date']);

                        // Preserve existing topic if none provided
                        if (empty($details['topic_id']) && isset($existingClassContent['topic_id'])) {
                            $updateData['topic_id'] = $existingClassContent['topic_id'];
                        }

                        // Maintain all_student flag unless explicitly provided
                        if (!isset($details['all_student'])) {
                            unset($updateData['all_student']);
                        }

                        $classContentTable
                            ->where('id', $existingClassContent['id'])
                            ->update($updateData);
                    } else {
                        $classContentTable->insert($classContentData);
                    }
                }

                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => 'Content assigned successfully',
                    'ErrorObject' => ''
                ]);
            }

            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => 'Invalid assign parameter'
            ]);

        } catch (\Exception $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get question types list
     */
    public function questionTypes(): ResponseInterface
    {
        try {
            $questionTypes = $this->contentModel->questionTypeList();
            
            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $questionTypes
            ]);
            
        } catch (\Exception $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Student views content (lazy creation trigger)
     */
    public function viewContent(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            if (is_object($data)) {
                $data = (array) $data;
            }
            
            $studentId = $data['student_id'] ?? null;
            $contentId = $data['content_id'] ?? null;
            $classContentId = $data['class_content_id'] ?? null;
            $classId = $data['class_id'] ?? null;
            $userId = session()->get('user_id') ?? 1; // Fallback for testing
            
            if (!$studentId || !$contentId || !$classContentId || !$classId) {
                throw new \Exception('Missing required parameters');
            }
            
            // Get or create student_content record
            $studentContent = $this->contentModel->getOrCreateStudentContent(
                $studentId,
                $contentId,
                $classContentId,
                $classId,
                $userId
            );
            
            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $studentContent,
                'ErrorObject' => ''
            ]);
            
        } catch (\Exception $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get class curriculum (consolidated view)
     */
    public function getClassCurriculum(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            if (is_object($data)) {
                $data = (array) $data;
            }
            
            $classId = $data['class_id'] ?? null;
            
            if (!$classId) {
                throw new \Exception('Class ID is required');
            }
            
            $curriculum = $this->contentModel->getClassCurriculumConsolidated($classId);
            
            // Format assignment scope for display
            foreach ($curriculum as &$item) {
                if ($item['has_class_assignment'] == 1 && $item['individual_count'] > 0) {
                    $item['assignment_scope'] = "Class + Individual ({$item['individual_count']})";
                    $item['assignment_detail'] = "Class-wide + " . $item['individual_students'];
                } elseif ($item['has_class_assignment'] == 1) {
                    $item['assignment_scope'] = "Class-wide";
                    $item['assignment_detail'] = "All students";
                } else {
                    $item['assignment_scope'] = "Individual ({$item['individual_count']})";
                    $item['assignment_detail'] = $item['individual_students'];
                }
            }
            
            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $curriculum,
                'ErrorObject' => ''
            ]);
            
        } catch (\Exception $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get student's available content
     */
    public function getStudentAvailableContent(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            if (is_object($data)) {
                $data = (array) $data;
            }
            
            $studentId = $data['student_id'] ?? null;
            
            if (!$studentId) {
                throw new \Exception('Student ID is required');
            }
            
            $content = $this->contentModel->getStudentAvailableContent($studentId);
            
            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $content,
                'ErrorObject' => ''
            ]);
            
        } catch (\Exception $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get teacher's gradable content
     */
    public function getTeacherGradableContent(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            if (is_object($data)) {
                $data = (array) $data;
            }
            
            $teacherId = $data['teacher_id'] ?? session()->get('user_id');
            $classId = $data['class_id'] ?? null;
            
            if (!$classId) {
                throw new \Exception('Class ID is required');
            }
            
            $content = $this->contentModel->getTeacherGradableContent($teacherId, $classId);
            
            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $content,
                'ErrorObject' => ''
            ]);
            
        } catch (\Exception $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update content
     */
    public function updateContent(): ResponseInterface
    {
        try {
            $params = json_decode(file_get_contents('php://input'), true);
            
            if (empty($params)) {
                $params = $this->request->getPost();
            }
            
            if (empty($params)) {
                $params = $this->request->getJSON(true) ?? [];
            }

            // Validation
            if (empty($params['content_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Content Id should not be empty'
                ]);
            }

            $db = \Config\Database::connect();
            
            // Prepare update data
            $updateData = [];
            if (isset($params['name'])) $updateData['name'] = $params['name'];
            if (isset($params['description'])) $updateData['description'] = $params['description'];
            if (isset($params['grade'])) {
                $updateData['grade'] = is_array($params['grade']) ? implode(',', $params['grade']) : $params['grade'];
            }
            if (isset($params['subject'])) {
                $updateData['subject'] = is_array($params['subject']) ? implode(',', $params['subject']) : $params['subject'];
            }
            if (isset($params['tags'])) {
                $updateData['tags'] = is_array($params['tags']) ? implode(',', $params['tags']) : $params['tags'];
            }
            if (isset($params['content_type'])) $updateData['content_type'] = $params['content_type'];
            if (isset($params['content_format'])) $updateData['content_format'] = $params['content_format'];
            if (isset($params['file_path'])) $updateData['file_path'] = json_encode($params['file_path']);
            if (isset($params['file_text'])) $updateData['file_text'] = $params['file_text'];
            if (isset($params['links'])) $updateData['links'] = json_encode($params['links']);
            if (isset($params['access'])) $updateData['access'] = $params['access'];
            if (isset($params['status'])) $updateData['status'] = $params['status'];
            if (isset($params['download'])) $updateData['download'] = $params['download'];
            
            $updateData['modified_date'] = date('Y-m-d H:i:s');

            // Update content
            $builder = $db->table('content');
            $builder->where('content_id', $params['content_id']);
            $result = $builder->update($updateData);

            if ($result) {
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => 'Content updated successfully',
                    'ErrorObject' => ''
                ]);
            } else {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Failed to update content'
                ]);
            }

        } catch (\Exception $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Remove/Delete content
     */
    public function remove(): ResponseInterface
    {
        try {
            $params = json_decode(file_get_contents('php://input'), true);
            
            if (empty($params)) {
                $params = $this->request->getPost();
            }
            
            if (empty($params)) {
                $params = $this->request->getJSON(true) ?? [];
            }

            // Validation
            if (empty($params['content_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Content Id should not be empty'
                ]);
            }

            $db = \Config\Database::connect();
            
            // Soft delete - set status to 0 instead of actually deleting
            $builder = $db->table('content');
            $builder->where('content_id', $params['content_id']);
            $result = $builder->update([
                'status' => 0,
                'modified_date' => date('Y-m-d H:i:s')
            ]);

            if ($result) {
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => 'Content removed successfully',
                    'ErrorObject' => ''
                ]);
            } else {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Failed to remove content'
                ]);
            }

        } catch (\Exception $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Remove content assignment from a class (soft delete class_content links)
     */
    public function deleteClassContent(): ResponseInterface
    {
        try {
            $params = json_decode(file_get_contents('php://input'), true);

            log_message('debug', '📥 deleteClassContent raw payload: ' . json_encode($params));

            if (empty($params)) {
                $params = $this->request->getPost();
            }

            if (empty($params)) {
                $params = $this->request->getJSON(true) ?? [];
            }

            log_message('debug', '📥 deleteClassContent normalized params: ' . json_encode($params));

            $classContentIds = $params['class_content_id'] ?? [];
            if (empty($classContentIds) || !is_array($classContentIds)) {
                log_message('error', '❌ deleteClassContent missing class_content_id: ' . json_encode($params));
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'class_content_id should not be empty'
                ]);
            }

            if (empty($params['user_id'])) {
                log_message('error', '❌ deleteClassContent missing user_id');
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'User Id should not be empty'
                ]);
            }

            $db = \Config\Database::connect();
            $classContentTable = $db->table('class_content');
            $classContentLogTable = $db->table('class_content_log');
            $studentContentTable = $db->table('student_content');
            $studentWorkTable = $db->table('student_work');
            $logFields = [];
            if ($db->tableExists('class_content_log')) {
                try {
                    $logFields = array_flip($db->getFieldNames('class_content_log'));
                } catch (\Throwable $e) {
                    log_message('error', 'Failed to fetch class_content_log fields: ' . $e->getMessage());
                }
            }

            $success = false;
            foreach ($classContentIds as $id) {
                $id = (int) $id;
                if ($id <= 0) {
                    log_message('warning', '⚠️ deleteClassContent skipping invalid id: ' . $id);
                    continue;
                }

                log_message('debug', '🔍 deleteClassContent processing class_content_id: ' . $id);

                $classContent = $classContentTable
                    ->where('id', $id)
                    ->get()
                    ->getRowArray();

                if (!$classContent) {
                    log_message('warning', '⚠️ deleteClassContent: class_content not found for id ' . $id);
                    continue;
                }

                // Log existing record if log table exists
                if (!empty($logFields)) {
                    $logInsert = array_intersect_key($classContent, $logFields);
                    if (!empty($logInsert)) {
                        try {
                            $classContentLogTable->insert($logInsert);
                        } catch (\Throwable $e) {
                            log_message('error', 'Failed to insert class_content_log: ' . $e->getMessage());
                        }
                    }
                }

                // Soft delete class_content entry
                $updateData = [
                    'status' => 2,
                    'modified_by' => $params['user_id'],
                    'modified_date' => date('Y-m-d H:i:s')
                ];

                $updated = $classContentTable
                    ->where('id', $id)
                    ->update($updateData);

                if ($updated) {
                    $success = true;

                    log_message('debug', '✅ deleteClassContent soft-deleted class_content ' . $id);

                    // Remove student_content links for this class_content
                    $deletedStudentContent = $studentContentTable
                        ->where('class_content_id', $id)
                        ->delete();

                    log_message('debug', '🗑 deleteClassContent removed student_content rows: ' . $deletedStudentContent);

                    // Deactivate related student_work entries
                    $updatedStudentWork = $studentWorkTable
                        ->where('class_id', $classContent['class_id'])
                        ->where('content_id', $classContent['content_id'])
                        ->update([
                            'status' => 0,
                            'modified_by' => $params['user_id'],
                            'modified_date' => date('Y-m-d H:i:s')
                        ]);

                    log_message('debug', '🛠 deleteClassContent updated student_work rows: ' . ($updatedStudentWork ? '1+' : '0'));
                } else {
                    log_message('error', '❌ deleteClassContent failed to update class_content ' . $id . ' - DB error: ' . $db->error()['message'] ?? 'unknown');
                }
            }

            if ($success) {
                log_message('debug', '✅ deleteClassContent completed successfully for ids: ' . implode(',', $classContentIds));
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => 'Content deleted successfully',
                    'ErrorObject' => ''
                ]);
            }

            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => 'Failed to delete content from class'
            ]);

        } catch (\Exception $e) {
            log_message('error', 'deleteClassContent error: ' . $e->getMessage());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Normalize mixed time payloads from the UI into HH:MM:SS strings for SQL inserts.
     */
    private function normalizeTimeValue($value, string $default): string
    {
        if (empty($value) && $value !== '0' && $value !== 0) {
            return $default;
        }

        if (is_object($value)) {
            $value = (array) $value;
        }

        if (is_array($value)) {
            $hour = isset($value['hour']) ? (int) $value['hour'] : 0;
            $minute = isset($value['minute']) ? (int) $value['minute'] : 0;
            $second = isset($value['second']) ? (int) $value['second'] : 0;
            return sprintf('%02d:%02d:%02d', $hour, $minute, $second);
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '') {
                return $default;
            }

            // Handle HH:MM payloads by adding seconds
            if (preg_match('/^\d{1,2}:\d{2}$/', $trimmed)) {
                return $trimmed . ':00';
            }

            // Assume already HH:MM:SS or SQL-friendly
            return $trimmed;
        }

        if (is_int($value)) {
            // Treat integer as hour value (legacy payloads)
            $hour = max(0, min(23, $value));
            return sprintf('%02d:00:00', $hour);
        }

        return $default;
    }
}
