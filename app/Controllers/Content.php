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
                
                // Process teacher version
                if (!empty($contentList['teacher_version']) && $contentList['teacher_version'] != '[]') {
                    $teacherVersion = json_decode($contentList['teacher_version'], true);
                    $contentList['teacher_version'] = is_array($teacherVersion) ? $teacherVersion : [];
                } else {
                    $contentList['teacher_version'] = [];
                }
                
                // Process links
                if (!empty($contentList['links']) && $contentList['links'] != '[]') {
                    $links = json_decode($contentList['links'], true);
                    $contentList['links'] = is_array($links) ? $links : [];
                } else {
                    $contentList['links'] = [];
                }
                
                // Process tags
                if (!empty($contentList['tags'])) {
                    $tags = explode(',', $contentList['tags']);
                    $contentList['tags'] = array_filter($tags);
                } else {
                    $contentList['tags'] = [];
                }
                
                // Process grade and subject
                if (!empty($contentList['grade'])) {
                    $grades = explode(',', $contentList['grade']);
                    $contentList['grade'] = array_map('intval', array_filter($grades));
                } else {
                    $contentList['grade'] = [];
                }
                
                if (!empty($contentList['subject'])) {
                    $subjects = explode(',', $contentList['subject']);
                    $contentList['subject'] = array_map('intval', array_filter($subjects));
                } else {
                    $contentList['subject'] = [];
                }
                
                // Get answers for the content
                log_message('debug', '🔍 [CONTENT DETAIL] Loading answers for content_id: ' . ($params['content_id'] ?? 'NOT SET'));
                $answers = $this->contentModel->answerList($params);
                log_message('debug', '🔍 [CONTENT DETAIL] Answers returned: ' . count($answers));
                log_message('debug', '🔍 [CONTENT DETAIL] Answers data: ' . json_encode($answers));
                $contentList['answers'] = is_array($answers) ? $answers : [];
                
                // Get questions from text_questions table (for content_format = 3)
                // Check content_format from params or from contentList
                $contentFormat = $params['content_format'] ?? $contentList['content_format'] ?? null;
                if ($contentFormat == '3' || $contentFormat == 3) {
                    try {
                        $questions = $this->contentModel->getQuestionsForContent($params['content_id']);
                        // Process questions - decode JSON fields
                        foreach ($questions as &$question) {
                            // Decode options - handle empty strings and null
                            if (isset($question['options']) && $question['options'] !== null && $question['options'] !== '') {
                                $decoded = json_decode($question['options'], true);
                                $question['options'] = ($decoded !== null) ? $decoded : [];
                            } else {
                                $question['options'] = [];
                            }
                            
                            // Decode answer - handle empty strings and null
                            if (isset($question['answer']) && $question['answer'] !== null && $question['answer'] !== '') {
                                $decoded = json_decode($question['answer'], true);
                                $question['answer'] = ($decoded !== null) ? $decoded : [];
                            } else {
                                $question['answer'] = [];
                            }
                            
                            // Decode hint - handle empty strings and null
                            if (isset($question['hint']) && $question['hint'] !== null && $question['hint'] !== '') {
                                $decoded = json_decode($question['hint'], true);
                                $question['hint'] = ($decoded !== null) ? $decoded : [];
                            } else {
                                $question['hint'] = [];
                            }
                            
                            // Decode heading_option - handle empty strings and null
                            if (isset($question['heading_option']) && $question['heading_option'] !== null && $question['heading_option'] !== '') {
                                $decoded = json_decode($question['heading_option'], true);
                                $question['heading_option'] = ($decoded !== null) ? $decoded : [];
                            } else {
                                $question['heading_option'] = [];
                            }
                            
                            // Decode skill - handle empty strings and null
                            if (isset($question['skill']) && $question['skill'] !== null && $question['skill'] !== '') {
                                $decoded = json_decode($question['skill'], true);
                                $question['skill'] = ($decoded !== null) ? $decoded : [];
                            } else {
                                $question['skill'] = [];
                            }
                        }
                        $contentList['questions'] = $questions;
                    } catch (\Exception $e) {
                        log_message('error', 'Error loading questions in contentDetail: ' . $e->getMessage());
                        log_message('error', 'Stack trace: ' . $e->getTraceAsString());
                        // If there's an error loading questions, set empty array instead of failing
                        $contentList['questions'] = [];
                    }
                } else {
                    // Initialize empty questions array if not content_format 3
                    $contentList['questions'] = [];
                }
                
                // Process annotation fields - ensure they are arrays
                if (isset($contentList['annotation'])) {
                    if (is_string($contentList['annotation']) && !empty($contentList['annotation'])) {
                        $decoded = json_decode($contentList['annotation'], true);
                        $contentList['annotation'] = is_array($decoded) ? $decoded : [];
                    } elseif (!is_array($contentList['annotation'])) {
                        $contentList['annotation'] = [];
                    }
                } else {
                    $contentList['annotation'] = [];
                }
                
                if (isset($contentList['questionAnnotation'])) {
                    if (is_string($contentList['questionAnnotation']) && !empty($contentList['questionAnnotation'])) {
                        $decoded = json_decode($contentList['questionAnnotation'], true);
                        $contentList['questionAnnotation'] = is_array($decoded) ? $decoded : [];
                    } elseif (!is_array($contentList['questionAnnotation'])) {
                        $contentList['questionAnnotation'] = [];
                    }
                } else {
                    $contentList['questionAnnotation'] = [];
                }
                
                // Ensure batch_id is set if it exists (for assignments)
                if (!isset($contentList['batch_id'])) {
                    $contentList['batch_id'] = null;
                }
                
                // For students, ensure student_content_id is set - find or create student_content record if needed
                if (isset($params['role_id']) && $params['role_id'] == 5 && isset($params['student_id']) && $params['student_id'] > 0) {
                    $studentId = (int)$params['student_id'];
                    $contentId = (int)$params['content_id'];
                    $classContentId = isset($params['class_content_id']) && $params['class_content_id'] > 0 
                        ? (int)$params['class_content_id'] 
                        : null;
                    $classId = isset($params['class_id']) && $params['class_id'] > 0 
                        ? (int)$params['class_id'] 
                        : null;
                    
                    // If student_content_id is missing or 0, try to find or create it
                    if (empty($params['student_content_id']) || $params['student_content_id'] == '0' || $params['student_content_id'] == 0) {
                        $db = \Config\Database::connect();
                        
                        // First, try to find existing student_content record
                        $studentContentBuilder = $db->table('student_content');
                        $studentContentBuilder->where('student_id', $studentId);
                        $studentContentBuilder->where('content_id', $contentId);
                        if ($classContentId) {
                            $studentContentBuilder->where('class_content_id', $classContentId);
                        }
                        if ($classId) {
                            $studentContentBuilder->where('class_id', $classId);
                        }
                        $studentContent = $studentContentBuilder->get()->getRowArray();
                        
                        if ($studentContent) {
                            $contentList['student_content_id'] = $studentContent['id'];
                            log_message('debug', '✅ [CONTENT DETAIL] Found existing student_content_id: ' . $studentContent['id']);
                        } elseif ($classContentId && $classId) {
                            // Try to create student_content record if we have the required info
                            try {
                                // Get class_content to get dates
                                $classContent = $db->table('class_content')
                                    ->where('id', $classContentId)
                                    ->get()
                                    ->getRowArray();
                                
                                if ($classContent) {
                                    // Get student's grade from class
                                    $studentGrade = $db->table('student_class sc')
                                        ->select('c.grade as grade_id')
                                        ->join('class c', 'sc.class_id = c.class_id', 'left')
                                        ->where('sc.student_id', $studentId)
                                        ->where('sc.class_id', $classId)
                                        ->get()
                                        ->getRowArray();
                                    
                                    $studentContentData = [
                                        'student_id' => $studentId,
                                        'content_id' => $contentId,
                                        'class_content_id' => $classContentId,
                                        'class_id' => $classId,
                                        'grade_id' => $studentGrade['grade_id'] ?? null,
                                        'start_date' => $classContent['start_date'] ?? null,
                                        'end_date' => $classContent['end_date'] ?? null,
                                        'status' => 1,
                                        'draft_status' => '1',
                                        'created_by' => $params['user_id'] ?? null,
                                        'created_date' => date('Y-m-d H:i:s'),
                                        'modified_by' => $params['user_id'] ?? null,
                                        'modified_date' => date('Y-m-d H:i:s')
                                    ];
                                    
                                    $db->table('student_content')->insert($studentContentData);
                                    $newStudentContentId = $db->insertID();
                                    $contentList['student_content_id'] = $newStudentContentId;
                                    log_message('debug', '✅ [CONTENT DETAIL] Created new student_content_id: ' . $newStudentContentId);
                                }
                            } catch (\Exception $e) {
                                log_message('error', '❌ [CONTENT DETAIL] Failed to create student_content: ' . $e->getMessage());
                                $contentList['student_content_id'] = 0;
                            }
                        } else {
                            $contentList['student_content_id'] = 0;
                            log_message('debug', '⚠️ [CONTENT DETAIL] Could not find or create student_content - missing class_content_id or class_id');
                        }
                    } else {
                        $contentList['student_content_id'] = $params['student_content_id'];
                    }
                }
                
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
                $builder->join('text_questions tq', 'tq.content_id = ' . $params['content_id'], 'left');
                $builder->where('tq.passage_id = p.passage_id');
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

            // Check if this is an update request (has update fields like name, description, etc.)
            $isUpdateRequest = isset($params['name']) || isset($params['description']) || isset($params['teacher_version']) || 
                               isset($params['answerkey_path']) || isset($params['file_path']) || isset($params['answers']);

            if ($isUpdateRequest) {
                // Handle update operation
                $db = \Config\Database::connect();
                
                // Prepare update data
                $updateData = [];
                if (isset($params['name'])) $updateData['name'] = $params['name'];
                if (isset($params['description'])) $updateData['description'] = $params['description'];
                if (isset($params['grade'])) {
                    $updateData['grade'] = is_array($params['grade']) ? implode(',', array_map('strval', $this->flattenArray($params['grade']))) : $params['grade'];
                }
                if (isset($params['subject'])) {
                    $updateData['subject'] = is_array($params['subject']) ? implode(',', array_map('strval', $this->flattenArray($params['subject']))) : $params['subject'];
                }
                if (isset($params['tags'])) {
                    $updateData['tags'] = is_array($params['tags']) ? implode(',', $params['tags']) : $params['tags'];
                }
                if (isset($params['content_type'])) $updateData['content_type'] = $params['content_type'];
                if (isset($params['content_format'])) $updateData['content_format'] = $params['content_format'];
                if (isset($params['file_path'])) {
                    $updateData['file_path'] = is_array($params['file_path']) ? json_encode($params['file_path']) : $params['file_path'];
                }
                if (isset($params['file_text'])) $updateData['file_text'] = $params['file_text'];
                if (isset($params['links'])) {
                    $updateData['links'] = is_array($params['links']) ? json_encode($params['links']) : $params['links'];
                }
                if (isset($params['answerkey_path'])) {
                    $updateData['answerkey_path'] = is_array($params['answerkey_path']) && count($params['answerkey_path']) > 0 ? json_encode($params['answerkey_path']) : '';
                }
                if (isset($params['teacher_version'])) {
                    // Handle teacher_version - ensure it's properly encoded even if empty array
                    if (is_array($params['teacher_version'])) {
                        $updateData['teacher_version'] = count($params['teacher_version']) > 0 ? json_encode($params['teacher_version']) : '';
                    } else {
                        $updateData['teacher_version'] = $params['teacher_version'];
                    }
                }
                if (isset($params['allow_answer_key'])) $updateData['allow_answer_key'] = $params['allow_answer_key'];
                if (isset($params['access'])) $updateData['access'] = $params['access'];
                if (isset($params['status'])) $updateData['status'] = $params['status'];
                if (isset($params['download'])) $updateData['download'] = $params['download'];
                if (isset($params['profile_url'])) $updateData['profile_url'] = $params['profile_url'];
                if (isset($params['profile_thumb_url'])) $updateData['profile_thumb_url'] = $params['profile_thumb_url'];
                
                $updateData['modified_date'] = date('Y-m-d H:i:s');

                // Update content
                $builder = $db->table('content');
                $builder->where('content_id', $params['content_id']);
                $result = $builder->update($updateData);

                // Handle answers update if provided
                if (isset($params['answers']) && is_array($params['answers']) && count($params['answers']) > 0) {
                    log_message('debug', '📝 [CONTENT EDIT] Processing answers - count: ' . count($params['answers']));
                    log_message('debug', '📝 [CONTENT EDIT] Answers structure: ' . json_encode($params['answers']));
                    
                    // Delete existing answers
                    $deletedCount = $db->table('answers')->where('content_id', $params['content_id'])->delete();
                    log_message('debug', '📝 [CONTENT EDIT] Deleted ' . $deletedCount . ' existing answers');
                    
                    // Insert new answers
                    $answersBuilder = $db->table('answers');
                    $insertedCount = 0;
                    foreach ($params['answers'] as $answerSection) {
                        log_message('debug', '📝 [CONTENT EDIT] Processing section - heading: ' . ($answerSection['heading'] ?? 'N/A'));
                        if (isset($answerSection['section']) && is_array($answerSection['section'])) {
                            log_message('debug', '📝 [CONTENT EDIT] Section has ' . count($answerSection['section']) . ' sub-sections');
                            foreach ($answerSection['section'] as $section) {
                                if (isset($section['sub_questions']) && is_array($section['sub_questions'])) {
                                    log_message('debug', '📝 [CONTENT EDIT] Sub-section has ' . count($section['sub_questions']) . ' questions');
                                    foreach ($section['sub_questions'] as $subQuestion) {
                                        $answerData = [
                                            'content_id' => $params['content_id'],
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
                                            'status' => 1, // Set status to 1 so questions are retrievable
                                            'created_by' => $params['user_id'] ?? null,
                                            'created_date' => date('Y-m-d H:i:s')
                                        ];
                                        
                                        try {
                                            $answersBuilder->insert($answerData);
                                            $insertedCount++;
                                            log_message('debug', '📝 [CONTENT EDIT] Inserted question: ' . ($subQuestion['question_no'] ?? 'N/A') . ' - ' . substr($subQuestion['question'] ?? '', 0, 50));
                                        } catch (\Exception $e) {
                                            log_message('error', '❌ [CONTENT EDIT] Failed to insert question: ' . $e->getMessage());
                                            log_message('error', '❌ [CONTENT EDIT] Answer data: ' . json_encode($answerData));
                                        }
                                    }
                                } else {
                                    log_message('warning', '⚠️ [CONTENT EDIT] Sub-section missing sub_questions array');
                                }
                            }
                        } else {
                            log_message('warning', '⚠️ [CONTENT EDIT] Answer section missing section array - heading: ' . ($answerSection['heading'] ?? 'N/A'));
                        }
                    }
                    log_message('debug', '📝 [CONTENT EDIT] Total questions inserted: ' . $insertedCount);
                } else {
                    log_message('warning', '⚠️ [CONTENT EDIT] No answers provided or answers is empty');
                }

                if ($result) {
                    // Get updated content details
                    $contentDetails = $this->contentModel->contentIdList(['content_id' => $params['content_id']]);
                    
                    // Process the response similar to retrieval
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
                    }
                    
                    return $this->respond([
                        'IsSuccess' => true,
                        'Contentdetails' => $contentDetails,
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
            } else {
                // Handle retrieval operation (original behavior)
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
            
            // Log incoming data for debugging
            log_message('debug', '🔍 [CONTENT ADD] Received params: ' . json_encode($params));

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
                    'grade' => isset($params['grade']) ? (is_array($params['grade']) ? implode(',', array_map('strval', $this->flattenArray($params['grade']))) : (is_string($params['grade']) || is_numeric($params['grade']) ? (string)$params['grade'] : '')) : '',
                    'subject' => isset($params['subject']) ? (is_array($params['subject']) ? implode(',', array_map('strval', $this->flattenArray($params['subject']))) : (is_string($params['subject']) || is_numeric($params['subject']) ? (string)$params['subject'] : '')) : '',
                    'tags' => isset($params['tags']) && is_array($params['tags']) && count($params['tags']) > 0 ? implode(',', $params['tags']) : '',
                    'content_type' => $params['content_type'] ?? '',
                    'content_format' => $params['content_format'] ?? '',
                    'editor_type' => $params['editor_type'] ?? '',
                    'file_path' => isset($params['file_path']) && is_array($params['file_path']) && count($params['file_path']) > 0 ? json_encode($params['file_path']) : '',
                    'answerkey_path' => isset($params['answerkey_path']) && is_array($params['answerkey_path']) && count($params['answerkey_path']) > 0 ? json_encode($params['answerkey_path']) : '',
                    'teacher_version' => isset($params['teacher_version']) && is_array($params['teacher_version']) && count($params['teacher_version']) > 0 ? json_encode($params['teacher_version']) : '',
                    'allow_answer_key' => isset($params['allow_answer_key']) ? $params['allow_answer_key'] : 0,
                    'links' => isset($params['links']) && is_array($params['links']) && count($params['links']) > 0 ? json_encode($params['links']) : '',
                    'file_text' => $params['file_text'] ?? '',
                    'download' => isset($params['download']) ? $params['download'] : 0,
                    'access' => $params['access'] ?? 0,
                    'status' => $params['status'] ?? 1,
                    'content_duration' => isset($params['content_duration']) && $params['content_duration'] != '' ? $params['content_duration'] : 0,
                    'annotation' => isset($params['annotation']) && is_array($params['annotation']) && count($params['annotation']) > 0 ? json_encode($params['annotation']) : '',
                    'questionAnnotation' => isset($params['questionAnnotation']) && is_array($params['questionAnnotation']) && count($params['questionAnnotation']) > 0 ? json_encode($params['questionAnnotation']) : '',
                    'created_by' => $params['user_id'] ?? null,
                    'created_date' => date('Y-m-d H:i:s')
                ];
                
                // Ensure no arrays remain in data (safety check)
                foreach ($data as $key => $value) {
                    if (is_array($value)) {
                        log_message('error', '❌ [CONTENT ADD] Found array in data field: ' . $key . ' = ' . json_encode($value));
                        $data[$key] = count($value) > 0 ? json_encode($value) : '';
                    } elseif (is_object($value)) {
                        log_message('error', '❌ [CONTENT ADD] Found object in data field: ' . $key . ' = ' . json_encode($value));
                        $data[$key] = json_encode($value);
                    } elseif (!is_scalar($value) && !is_null($value)) {
                        log_message('error', '❌ [CONTENT ADD] Found non-scalar value in data field: ' . $key . ' (type: ' . gettype($value) . ')');
                        $data[$key] = is_string($value) ? $value : (string)$value;
                    }
                }
                
                log_message('debug', '🔍 [CONTENT ADD] Final data after cleanup: ' . json_encode($data));

                if (isset($params['role_id']) && $params['role_id'] == 6 && isset($params['corporate_id'])) {
                    $data['corporate_id'] = $params['corporate_id'];
                }

                if (isset($params['profile_url']) && isset($params['profile_thumb_url'])) {
                    $data['profile_url'] = $params['profile_url'];
                    $data['profile_thumb_url'] = $params['profile_thumb_url'];
                }

                // Insert content
                try {
                    log_message('debug', '🔍 [CONTENT ADD] Prepared data for insert: ' . json_encode($data));
                    $builder = $db->table('content');
                    $builder->insert($data);
                    $contentId = $db->insertID();
                    log_message('debug', '🔍 [CONTENT ADD] Insert successful, content_id: ' . $contentId);
                } catch (\Exception $e) {
                    log_message('error', '❌ [CONTENT ADD] Database insert error: ' . $e->getMessage());
                    log_message('error', '❌ [CONTENT ADD] Data that caused error: ' . json_encode($data));
                    throw $e;
                }

                if (!$contentId) {
                    throw new \Exception('Failed to insert content');
                }

                // Insert classroom_content if batch_id is provided
                if (isset($params['batch_id']) && !empty($params['batch_id'])) {
                    $classroomContent = [];
                    // Handle batch_id - can be null, array, or single value
                    if (is_array($params['batch_id'])) {
                        $batchIds = array_filter($params['batch_id']); // Remove null/empty values
                    } else {
                        $batchIds = $params['batch_id'] !== null ? [$params['batch_id']] : [];
                    }
                    
                    foreach ($batchIds as $batchId) {
                        // Skip if batchId is null or empty
                        if (empty($batchId)) {
                            continue;
                        }
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
                                            'status' => 1, // Set status to 1 so questions are retrievable
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
                        $classContentId = $existingClassContent['id'];
                    } else {
                        $classContentTable->insert($classContentData);
                        $classContentId = $db->insertID();
                    }

                    // If assigned to individual students (all_student = 0), create student_content records
                    if (isset($details['all_student']) && $details['all_student'] == '0' && !empty($details['student_id'])) {
                        $studentIds = is_array($details['student_id']) 
                            ? $details['student_id'] 
                            : explode(',', $details['student_id']);
                        
                        log_message('debug', '🔍 [CONTENT ADD] Creating student_content records for class_content_id: ' . $classContentId . ', student_ids: ' . json_encode($studentIds));
                        
                        $studentContentTable = $db->table('student_content');
                        $createdCount = 0;
                        $skippedCount = 0;
                        
                        foreach ($studentIds as $studentId) {
                            $studentId = trim($studentId);
                            if (empty($studentId)) {
                                log_message('debug', '⚠️ [CONTENT ADD] Skipping empty student_id');
                                continue;
                            }
                            
                            // Check if student_content already exists
                            $existingStudentContent = $studentContentTable
                                ->where('class_content_id', $classContentId)
                                ->where('student_id', $studentId)
                                ->where('content_id', $contentId)
                                ->get()
                                ->getRowArray();
                            
                            if (!$existingStudentContent) {
                                // Get student's grade
                                $studentGrade = $db->table('student_class sc')
                                    ->select('c.grade as grade_id')
                                    ->join('class c', 'sc.class_id = c.class_id', 'left')
                                    ->where('sc.student_id', $studentId)
                                    ->where('sc.class_id', $classId)
                                    ->get()
                                    ->getRowArray();
                                
                                $studentContentData = [
                                    'student_id' => $studentId,
                                    'content_id' => $contentId,
                                    'class_content_id' => $classContentId,
                                    'class_id' => $classId,
                                    'grade_id' => $studentGrade['grade_id'] ?? null,
                                    'start_date' => $startDate,
                                    'end_date' => $endDate != '0000-00-00' ? $endDate : null,
                                    'status' => 1, // Yet to start
                                    'created_by' => $params['user_id'] ?? null,
                                    'created_date' => date('Y-m-d H:i:s'),
                                    'modified_by' => $params['user_id'] ?? null,
                                    'modified_date' => date('Y-m-d H:i:s')
                                ];
                                
                                log_message('debug', '✅ [CONTENT ADD] Inserting student_content: ' . json_encode($studentContentData));
                                $studentContentTable->insert($studentContentData);
                                $createdCount++;
                            } else {
                                log_message('debug', '⏭️ [CONTENT ADD] student_content already exists for student_id: ' . $studentId);
                                $skippedCount++;
                            }
                        }
                        
                        log_message('debug', '📊 [CONTENT ADD] Created ' . $createdCount . ' student_content records, skipped ' . $skippedCount);
                        
                        // Remove student_content records for students not in the list (if updating)
                        if ($existingAssignment && !empty($details['student_id'])) {
                            $studentIdsArray = is_array($details['student_id']) 
                                ? array_map('trim', $details['student_id']) 
                                : array_map('trim', explode(',', $details['student_id']));
                            
                            $studentContentTable
                                ->where('class_content_id', $classContentId)
                                ->whereNotIn('student_id', $studentIdsArray)
                                ->delete();
                        }
                    } elseif (isset($details['all_student']) && $details['all_student'] == '1') {
                        // If switching to class-wide, remove individual student_content records
                        $db->table('student_content')
                            ->where('class_content_id', $classContentId)
                            ->delete();
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
            log_message('error', '❌ [CONTENT ADD] Exception: ' . $e->getMessage());
            log_message('error', '❌ [CONTENT ADD] File: ' . $e->getFile() . ' Line: ' . $e->getLine());
            log_message('error', '❌ [CONTENT ADD] Trace: ' . $e->getTraceAsString());
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
            $data = $this->request->getJSON(true);
            $questionTypes = $this->contentModel->questionTypeList();
            
            // Filter out question_type_id 54 if content_format is 3
            if (isset($data['content_format']) && $data['content_format'] == 3) {
                $questionTypes = array_filter($questionTypes, function($item) {
                    return $item['question_type_id'] != 54;
                });
                $questionTypes = array_values($questionTypes);
            }
            
            // Transform flat list into grouped structure expected by frontend
            $grouped = [];
            foreach ($questionTypes as $item) {
                if (empty($item['resource_type']) || empty($item['question_type_id'])) {
                    continue; // Skip invalid entries
                }
                
                $resourceType = $item['resource_type'];
                
                // Find if this resource_type already exists in grouped array
                $foundIndex = null;
                foreach ($grouped as $key => $group) {
                    if ($group['resource_type'] === $resourceType) {
                        $foundIndex = $key;
                        break;
                    }
                }
                
                // Prepare question type data
                $questionTypeData = [
                    'resource_type' => $item['resource_type'],
                    'question_type_id' => $item['question_type_id'],
                    'resource_type_id' => $item['resource_type_id'],
                    'question_type' => $item['question_type'],
                    'image_path' => $item['image_path'],
                    'icon_path' => $item['icon_path'] ?? null
                ];
                
                if ($foundIndex !== null) {
                    // Add this question type to existing group
                    $grouped[$foundIndex]['types'][] = $questionTypeData;
                } else {
                    // Create new group
                    $grouped[] = [
                        'resource_type' => $resourceType,
                        'types' => [$questionTypeData]
                    ];
                }
            }
            
            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $grouped
            ]);
            
        } catch (\Exception $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get question standard list
     */
    public function questionStandard(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON(true);
            
            // Validate required parameters
            if (!isset($data['platform']) || ($data['platform'] != "web" && $data['platform'] != "ios")) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ErrorObject' => "Platform should not be empty"
                ], 400);
            }
            
            if (empty($data['role_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ErrorObject' => "Role Id should not be empty"
                ], 400);
            }
            
            if (empty($data['user_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ErrorObject' => "User Id should not be empty"
                ], 400);
            }
            
            $condition = "";
            // Note: question_standard table may not have question_id column
            // If filtering is needed, it should be done differently
            // For now, return all standards
            
            $standards = $this->contentModel->questionStandard($condition);
            
            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $standards
            ]);
            
        } catch (\Exception $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get question topic list
     */
    public function questionTopic(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON(true);
            
            // Validate required parameters
            if (!isset($data['platform']) || ($data['platform'] != "web" && $data['platform'] != "ios")) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ErrorObject' => "Platform should not be empty"
                ], 400);
            }
            
            if (empty($data['role_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ErrorObject' => "Role Id should not be empty"
                ], 400);
            }
            
            if (empty($data['user_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ErrorObject' => "User Id should not be empty"
                ], 400);
            }
            
            $condition = "";
            if (isset($data['question_id']) && $data['question_id'] > 0) {
                $condition = "WHERE question_id = " . (int)$data['question_id'];
            } elseif (isset($data['subject_id']) && $data['subject_id'] > 0) {
                // Check if question_topic table has subject_id column, otherwise use empty condition
                $condition = "WHERE subject_id = " . (int)$data['subject_id'];
            }
            
            $topics = $this->contentModel->questionTopic($condition);
            
            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $topics
            ]);
            
        } catch (\Exception $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get question subtopic list
     */
    public function questionSubTopic(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON(true);
            
            // Validate required parameters
            if (!isset($data['platform']) || ($data['platform'] != "web" && $data['platform'] != "ios")) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ErrorObject' => "Platform should not be empty"
                ], 400);
            }
            
            if (empty($data['role_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ErrorObject' => "Role Id should not be empty"
                ], 400);
            }
            
            if (empty($data['user_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ErrorObject' => "User Id should not be empty"
                ], 400);
            }
            
            $condition = "";
            if (isset($data['question_topic_id']) && $data['question_topic_id'] > 0) {
                $condition = "WHERE question_topic_id = " . (int)$data['question_topic_id'];
            } elseif (isset($data['topic_id']) && $data['topic_id'] > 0) {
                $condition = "WHERE question_topic_id = " . (int)$data['topic_id'];
            }
            
            $subTopics = $this->contentModel->questionSubTopic($condition);
            
            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $subTopics
            ]);
            
        } catch (\Exception $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add question(s) to content
     */
    public function addQuestion(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON(true);
            
            // Validate required parameters
            if (!isset($data['platform']) || ($data['platform'] != "web" && $data['platform'] != "ios")) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ErrorObject' => "Platform should not be empty"
                ], 400);
            }
            
            if (empty($data['role_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ErrorObject' => "Role Id should not be empty"
                ], 400);
            }
            
            if (empty($data['user_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ErrorObject' => "User Id should not be empty"
                ], 400);
            }
            
            if (empty($data['content_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ErrorObject' => "Content Id should not be empty"
                ], 400);
            }
            
            if (empty($data['questions']) || !is_array($data['questions']) || count($data['questions']) == 0) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ErrorObject' => "Questions should not be empty"
                ], 400);
            }
            
            $db = \Config\Database::connect();
            $questionsToInsert = [];
            $count = 1;
            
            // Get current question count for this content
            $existingQuestions = $db->table('text_questions')
                ->where('content_id', $data['content_id'])
                ->orderBy('question_no', 'DESC')
                ->limit(1)
                ->get()
                ->getRowArray();
            
            if ($existingQuestions) {
                $count = (int)$existingQuestions['question_no'] + 1;
            }
            
            // Process questions
            foreach ($data['questions'] as $question) {
                if ($question['question_type_id'] == 24) {
                    // Passage type question with sub-questions
                    $quesNum = 1;
                    foreach ($question['subQuestions'] as $subQuestion) {
                        $questionsToInsert[] = $this->prepareQuestionData($subQuestion, $data, $data['content_id'], $count, $quesNum, true, $question);
                        $quesNum++;
                    }
                    $count++;
                } else {
                    // Regular question
                    $questionsToInsert[] = $this->prepareQuestionData($question, $data, $data['content_id'], $count, $count, false);
                    $count++;
                }
            }
            
            // Bulk insert questions
            if (!empty($questionsToInsert)) {
                $db->table('text_questions')->insertBatch($questionsToInsert);
                
                // Update content total_questions count
                $db->table('content')
                    ->where('content_id', $data['content_id'])
                    ->update(['total_questions' => $count - 1]);
                
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => "Question added Successfully"
                ]);
            } else {
                return $this->respond([
                    'IsSuccess' => false,
                    'ErrorObject' => "Question not added"
                ], 400);
            }
            
        } catch (\Exception $e) {
            log_message('error', 'addQuestion error: ' . $e->getMessage());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Edit question(s) in content
     */
    public function editQuestion(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON(true);
            
            // Validate required parameters
            if (!isset($data['platform']) || ($data['platform'] != "web" && $data['platform'] != "ios")) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ErrorObject' => "Platform should not be empty"
                ], 400);
            }
            
            if (empty($data['role_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ErrorObject' => "Role Id should not be empty"
                ], 400);
            }
            
            if (empty($data['user_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ErrorObject' => "User Id should not be empty"
                ], 400);
            }
            
            if (empty($data['questions']) || !is_array($data['questions']) || count($data['questions']) == 0) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ErrorObject' => "Questions should not be empty"
                ], 400);
            }
            
            $db = \Config\Database::connect();
            
            // Process questions
            foreach ($data['questions'] as $question) {
                if ($question['question_type_id'] == 24) {
                    // Passage type question with sub-questions
                    foreach ($question['subQuestions'] as $subQuestion) {
                        if (!empty($subQuestion['question_id'])) {
                            // Update existing sub-question
                            $updateData = $this->prepareQuestionUpdateData($subQuestion, $data, $question);
                            $db->table('text_questions')
                                ->where('question_id', $subQuestion['question_id'])
                                ->update($updateData);
                        }
                    }
                } else {
                    // Regular question
                    if (!empty($question['question_id'])) {
                        $updateData = $this->prepareQuestionUpdateData($question, $data);
                        $db->table('text_questions')
                            ->where('question_id', $question['question_id'])
                            ->update($updateData);
                    }
                }
            }
            
            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => "Questions updated successfully"
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'editQuestion error: ' . $e->getMessage());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Prepare question data for insertion
     */
    private function prepareQuestionData($question, $params, $contentId, $questionNo, $subQuestionNo, $hasSubQuestion = false, $parentQuestion = null)
    {
        $data = [
            'content_id' => $contentId,
            'question_type_id' => $hasSubQuestion ? $parentQuestion['question_type_id'] : $question['question_type_id'],
            'question_no' => $questionNo,
            'sub_question_no' => $subQuestionNo,
            'has_sub_question' => $hasSubQuestion ? 1 : 0,
            'question' => $question['question'] ?? '',
            'editor_answer' => $question['editor_answer'] ?? '',
            'options' => json_encode($question['options'] ?? []),
            'answer' => json_encode($question['answer'] ?? []),
            'level' => $question['level'] ?? 0,
            'multiple_response' => $question['multiple_response'] ?? 0,
            'audo_grade' => $question['autograde'] ?? 0,
            'points' => $question['points'] ?? 0,
            'exact_match' => $question['exact_match'] ?? 0,
            'hint' => json_encode($question['hint'] ?? []),
            'explanation' => $question['explanation'] ?? '',
            'resource' => $question['resource'] ?? '',
            'word_limit' => $question['word_limit'] ?? '',
            'scoring_instruction' => $question['scoring_instruction'] ?? '',
            'source' => $question['source'] ?? '',
            'target' => $question['target'] ?? '',
            'subject_id' => !empty($question['subject_id']) ? (int)$question['subject_id'] : null,
            'question_topic_id' => !empty($question['question_topic_id']) ? (int)$question['question_topic_id'] : null,
            'question_standard' => !empty($question['question_standard']) ? (int)$question['question_standard'] : null,
            'question_sub_topic_id' => !empty($question['question_sub_topic_id']) ? (int)$question['question_sub_topic_id'] : null,
            'skill' => !empty($question['skill']) ? json_encode($question['skill']) : null,
            'created_by' => $params['user_id'],
            'created_date' => date('Y-m-d H:i:s')
        ];
        
        if ($hasSubQuestion) {
            $data['sub_question_type_id'] = $question['question_type_id'];
            $data['passage_id'] = $parentQuestion['passage_id'] ?? 0;
            if (isset($parentQuestion['editor_context'])) {
                $data['editor_context'] = $parentQuestion['editor_context'];
            }
        }
        
        if (isset($question['editor_type'])) {
            $data['editor_type'] = $question['editor_type'];
        }
        
        if (isset($question['editor_context'])) {
            $data['editor_context'] = $question['editor_context'];
        }
        
        // Handle heading_option for specific question types (3 = True/False, 5 = Match Table-Standard, 7 = Match Table-Labels)
        $questionTypeId = (int)($question['question_type_id'] ?? 0);
        if (in_array($questionTypeId, [3, 5, 7])) {
            $headingOption = $question['heading_option'] ?? [];
            if (!empty($headingOption)) {
                $data['heading_option'] = json_encode($headingOption);
                log_message('debug', 'Saving heading_option for question_type_id ' . $questionTypeId . ': ' . json_encode($headingOption));
            } else {
                $data['heading_option'] = '';
                log_message('debug', 'heading_option is empty for question_type_id ' . $questionTypeId);
            }
        } else {
            $data['heading_option'] = '';
        }
        
        return $data;
    }

    /**
     * Prepare question data for update
     */
    private function prepareQuestionUpdateData($question, $params, $parentQuestion = null)
    {
        $data = [
            'question' => $question['question'] ?? '',
            'editor_answer' => $question['editor_answer'] ?? '',
            'options' => json_encode($question['options'] ?? []),
            'answer' => json_encode($question['answer'] ?? []),
            'level' => $question['level'] ?? 0,
            'multiple_response' => $question['multiple_response'] ?? 0,
            'audo_grade' => $question['autograde'] ?? 0,
            'points' => $question['points'] ?? 0,
            'exact_match' => $question['exact_match'] ?? 0,
            'hint' => json_encode($question['hint'] ?? []),
            'explanation' => $question['explanation'] ?? '',
            'resource' => $question['resource'] ?? '',
            'word_limit' => $question['word_limit'] ?? '',
            'scoring_instruction' => $question['scoring_instruction'] ?? '',
            'source' => $question['source'] ?? '',
            'target' => $question['target'] ?? '',
            'subject_id' => !empty($question['subject_id']) ? (int)$question['subject_id'] : null,
            'question_topic_id' => !empty($question['question_topic_id']) ? (int)$question['question_topic_id'] : null,
            'question_standard' => !empty($question['question_standard']) ? (int)$question['question_standard'] : null,
            'question_sub_topic_id' => !empty($question['question_sub_topic_id']) ? (int)$question['question_sub_topic_id'] : null,
            'skill' => !empty($question['skill']) ? json_encode($question['skill']) : null,
            'modified_by' => $params['user_id'],
            'modified_date' => date('Y-m-d H:i:s')
        ];
        
        if ($parentQuestion) {
            if (isset($parentQuestion['editor_context'])) {
                $data['editor_context'] = $parentQuestion['editor_context'];
            }
            $data['passage_id'] = $parentQuestion['passage_id'] ?? 0;
        }
        
        if (isset($question['editor_type'])) {
            $data['editor_type'] = $question['editor_type'];
        }
        
        if (isset($question['editor_context'])) {
            $data['editor_context'] = $question['editor_context'];
        }
        
        // Handle heading_option for specific question types (3 = True/False, 5 = Match Table-Standard, 7 = Match Table-Labels)
        $questionTypeId = (int)($question['question_type_id'] ?? 0);
        if (in_array($questionTypeId, [3, 5, 7])) {
            $headingOption = $question['heading_option'] ?? [];
            if (!empty($headingOption)) {
                $data['heading_option'] = json_encode($headingOption);
                log_message('debug', 'Updating heading_option for question_type_id ' . $questionTypeId . ': ' . json_encode($headingOption));
            } else {
                $data['heading_option'] = '';
                log_message('debug', 'heading_option is empty for question_type_id ' . $questionTypeId . ' in update');
            }
        } else {
            $data['heading_option'] = '';
        }
        
        return $data;
    }

    /**
     * Delete question(s) from content
     */
    public function deleteQuestion(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON(true);
            
            // Validate required parameters
            if (!isset($data['platform']) || ($data['platform'] != "web" && $data['platform'] != "ios")) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ErrorObject' => "Platform should not be empty"
                ], 400);
            }
            
            if (empty($data['role_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ErrorObject' => "Role Id should not be empty"
                ], 400);
            }
            
            if (empty($data['user_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ErrorObject' => "User Id should not be empty"
                ], 400);
            }
            
            if (empty($data['question_id']) || !is_array($data['question_id']) || count($data['question_id']) == 0) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ErrorObject' => "Question Id should not be empty"
                ], 400);
            }
            
            $db = \Config\Database::connect();
            
            // Convert question_id array to integers
            $questionIds = array_map('intval', $data['question_id']);
            
            // Get content_id before deleting (needed to update total_questions)
            $contentIdResult = $db->table('text_questions')
                ->select('content_id')
                ->whereIn('question_id', $questionIds)
                ->limit(1)
                ->get()
                ->getRowArray();
            
            if (!$contentIdResult) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ErrorObject' => "Question not found"
                ], 404);
            }
            
            $contentId = $contentIdResult['content_id'];
            
            // Delete questions
            $deleted = $db->table('text_questions')
                ->whereIn('question_id', $questionIds)
                ->delete();
            
            if ($deleted) {
                // Recalculate total_questions for this content
                $totalQuestions = $db->table('text_questions')
                    ->where('content_id', $contentId)
                    ->countAllResults();
                
                $db->table('content')
                    ->where('content_id', $contentId)
                    ->update(['total_questions' => $totalQuestions]);
                
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => "Question Deleted Successfully"
                ]);
            } else {
                return $this->respond([
                    'IsSuccess' => false,
                    'ErrorObject' => "Unable to delete question"
                ], 400);
            }
            
        } catch (\Exception $e) {
            log_message('error', 'deleteQuestion error: ' . $e->getMessage());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ], 500);
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
                $updateData['grade'] = is_array($params['grade']) ? implode(',', array_map('strval', $this->flattenArray($params['grade']))) : $params['grade'];
            }
            if (isset($params['subject'])) {
                $updateData['subject'] = is_array($params['subject']) ? implode(',', array_map('strval', $this->flattenArray($params['subject']))) : $params['subject'];
            }
            if (isset($params['tags'])) {
                $updateData['tags'] = is_array($params['tags']) ? implode(',', $params['tags']) : $params['tags'];
            }
            if (isset($params['content_type'])) $updateData['content_type'] = $params['content_type'];
            if (isset($params['content_format'])) $updateData['content_format'] = $params['content_format'];
            if (isset($params['file_path'])) $updateData['file_path'] = json_encode($params['file_path']);
            if (isset($params['file_text'])) $updateData['file_text'] = $params['file_text'];
            if (isset($params['links'])) $updateData['links'] = json_encode($params['links']);
            if (isset($params['answerkey_path'])) {
                $updateData['answerkey_path'] = is_array($params['answerkey_path']) && count($params['answerkey_path']) > 0 ? json_encode($params['answerkey_path']) : '';
            }
            if (isset($params['teacher_version'])) {
                $updateData['teacher_version'] = is_array($params['teacher_version']) && count($params['teacher_version']) > 0 ? json_encode($params['teacher_version']) : '';
            }
            if (isset($params['allow_answer_key'])) $updateData['allow_answer_key'] = $params['allow_answer_key'];
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
    /**
     * Flatten nested arrays to a single level
     * Handles cases like [[10]] -> [10] or [[10], [20]] -> [10, 20]
     */
    private function flattenArray($array): array
    {
        if (!is_array($array)) {
            return [];
        }
        
        $result = [];
        foreach ($array as $item) {
            if (is_array($item)) {
                $result = array_merge($result, $this->flattenArray($item));
            } else {
                $result[] = $item;
            }
        }
        
        return $result;
    }

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

    /**
     * Add/update student answer for a content
     * This saves student answers and updates the student_content status
     */
    public function addStudentAnswer(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            log_message('debug', '📝 Content::addStudentAnswer called with params: ' . json_encode($params));

            // Validation
            if (empty($params['platform']) || !in_array($params['platform'], ['web', 'ios'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Platform should not be empty'
                ]);
            }

            if (empty($params['role_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Role Id should not be empty'
                ]);
            }

            if (empty($params['user_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'User Id should not be empty'
                ]);
            }

            if (empty($params['content_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Content Id should not be empty'
                ]);
            }

            if (empty($params['student_content_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Student Content Id should not be empty'
                ]);
            }

            $db = \Config\Database::connect();
            $commonModel = new \App\Models\V1\CommonModel();

            // Get student platform info
            $platformQuery = $db->table('student_content')
                ->select('platform')
                ->where('id', $params['student_content_id'])
                ->get()
                ->getRowArray();
            
            $platform = $params['platform'] == 'web' ? 1 : 2;
            if ($platformQuery && $platformQuery['platform'] != 0 && $platformQuery['platform'] != $platform) {
                $platform = 3; // Both platforms
            }

            // Determine status
            $statusQuery = $db->table('student_content')
                ->select('status, redo_test')
                ->where('id', $params['student_content_id'])
                ->get()
                ->getRowArray();

            $status = $params['status'] ?? 1;
            if ($statusQuery) {
                $redoTest = $statusQuery['redo_test'] ?? 0;
                $currentStatus = $statusQuery['status'] ?? 1;
                
                // Only allow status change if redo is allowed or status is not submitted (4)
                if (!(($redoTest == 0 && $currentStatus != 4) || ($redoTest == 1 || $redoTest == 2))) {
                    $status = $currentStatus;
                }
            }

            // Update student_content
            $updateData = [
                'status' => $status,
                'student_feedback' => $params['overall_student_feedback'] ?? '',
                'upload_answer' => isset($params['upload_answer']) && !empty($params['upload_answer']) ? json_encode($params['upload_answer']) : '',
                'answer_completed_date' => date('Y-m-d H:i:s'),
                'platform' => $platform,
                'laq_id' => $params['laq_id'] ?? 0
            ];

            $updateCondition = ['id' => $params['student_content_id']];
            $updateResult = $db->table('student_content')->update($updateData, $updateCondition);

            // Update student_work if exists
            $workData = [
                'student_content_status' => $status,
                'answer_completed_date' => date('Y-m-d H:i:s')
            ];
            $workCondition = ['student_content_id' => $params['student_content_id']];
            $db->table('student_work')->update($workData, $workCondition);

            // Save answers if provided (non-PDF content)
            if (isset($params['answers']) && is_array($params['answers']) && !empty($params['answers'])) {
                $contentFormat = $params['content_format'] ?? 1;
                
                if ($contentFormat != 3) { // Not PDF format
                    // Process and save answers
                    // This is a simplified version - full implementation would handle all question types
                    foreach ($params['answers'] as $answerGroup) {
                        if (isset($answerGroup['section']) && is_array($answerGroup['section'])) {
                            foreach ($answerGroup['section'] as $section) {
                                if (isset($section['sub_questions']) && is_array($section['sub_questions'])) {
                                    foreach ($section['sub_questions'] as $subQ) {
                                        $answerData = [
                                            'answer_id' => $subQ['answer_id'] ?? 0,
                                            'content_id' => $params['content_id'],
                                            'student_id' => $params['student_id'] ?? $params['user_id'],
                                            'student_content_id' => $params['student_content_id'],
                                            'question_no' => $subQ['question_no'] ?? $subQ['sub_question_no'] ?? '',
                                            'correct_answer' => $subQ['answer'] ?? '',
                                            'student_answer' => $subQ['student_answer'] ?? '',
                                            'actual_points' => $subQ['points'] ?? 0,
                                            'earned_points' => $subQ['earned_points'] ?? 0,
                                            'answer_status' => $status == 4 ? 4 : 0,
                                            'created_by' => $params['user_id'],
                                            'created_date' => date('Y-m-d H:i:s')
                                        ];

                                        if (isset($subQ['class_id'])) {
                                            $answerData['class_id'] = $subQ['class_id'];
                                        }

                                        // Check if answer already exists
                                        $existing = $db->table('student_answers')
                                            ->where('answer_id', $answerData['answer_id'])
                                            ->where('student_content_id', $params['student_content_id'])
                                            ->get()
                                            ->getRowArray();

                                        if ($existing) {
                                            // Update existing answer
                                            unset($answerData['created_by'], $answerData['created_date']);
                                            $db->table('student_answers')
                                                ->where('answer_id', $answerData['answer_id'])
                                                ->where('student_content_id', $params['student_content_id'])
                                                ->update($answerData);
                                        } else {
                                            // Insert new answer
                                            $db->table('student_answers')->insert($answerData);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if ($updateResult) {
                // Calculate total score if status is submitted
                if ($status == 4) {
                    // Calculate earned points and total points
                    $totalScore = $db->query("
                        SELECT COALESCE(SUM(earned_points), 0) as earned_points,
                               COALESCE(SUM(actual_points), 0) as points
                        FROM student_answers
                        WHERE student_content_id = {$params['student_content_id']}
                    ")->getRowArray();

                    if ($totalScore) {
                        $db->table('student_content')
                            ->where('id', $params['student_content_id'])
                            ->update([
                                'earned_points' => $totalScore['earned_points'],
                                'points' => $totalScore['points']
                            ]);
                    }
                }

                $message = $status == 4 ? 'Answers Submitted Successfully' : 'Answers Saved Successfully';
                
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => $message,
                    'ErrorObject' => ''
                ]);
            } else {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Unable to Submit Answers'
                ]);
            }

        } catch (\Exception $e) {
            log_message('error', 'Content::addStudentAnswer error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ], 500);
        }
    }
}
