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
            
            $sortList = $this->contentModel->getSortMaster($data ?? (object)[]);
            
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
                                    AND user_id IN ((SELECT GROUP_CONCAT(user_id) FROM user WHERE role_id = 5 AND user_id = upd.user_id))");
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
                    
                    // Insert assignment data
                    $assignmentData = [
                        'class_id' => $details['class_id'] ?? null,
                        'content_id' => $details['content_id'] ?? null,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'start_time' => $details['start_time'] ?? '00:00:00',
                        'end_time' => $details['end_time'] ?? '23:59:00',
                        'status' => 1,
                        'created_by' => $params['user_id'] ?? null,
                        'created_date' => date('Y-m-d H:i:s')
                    ];
                    
                    $assignmentBuilder->insert($assignmentData);
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
}
