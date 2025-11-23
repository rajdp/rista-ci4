<?php

namespace App\Models\V1;

use CodeIgniter\Model;
use CodeIgniter\API\ResponseTrait;

class ContentModel extends BaseModel
{
    use ResponseTrait;

    protected $table = 'content';
    protected $allowedFields = [
        'content_id',
        'name',
        'school_id',
        'created_by',
        'status'
    ];

    public function sortMaster($params)
    {
        $db = \Config\Database::connect();
        
        // Build the base query
        // Performance Note: For optimal query performance, ensure the following indexes exist:
        // - content: (school_id, status, created_date)
        // - content: (created_by, status)
        // - content: (name) for search queries
        $builder = $db->table('content c');
        
        // Optimized SELECT - only fetch essential fields for listing
        // Details can be loaded on-demand when viewing individual items
        $builder->select('c.content_id, c.name, c.subject, c.grade, c.content_format, c.content_type, c.status, 
                         c.created_by, c.created_date,
                         c.file_path, c.tags,
                         COALESCE((SELECT GROUP_CONCAT(grade_name) FROM grade 
                                  WHERE FIND_IN_SET(grade_id, c.grade)), \'\') AS grade_name,
                         COALESCE((SELECT GROUP_CONCAT(subject_name) FROM subject 
                                  WHERE FIND_IN_SET(subject_id, c.subject)), \'\') AS subject_name');
        
        // Apply school filter
        if (isset($params['school_id']) && !empty($params['school_id'])) {
            $builder->where('c.school_id', $params['school_id']);
        }
        
        // Apply search filter
        if (isset($params['search_name']) && !empty($params['search_name'])) {
            $searchTerm = $params['search_name'];
            if (isset($params['exact_search']) && $params['exact_search'] == 1) {
                $builder->where('c.name', $searchTerm);
            } else {
                $builder->like('c.name', $searchTerm);
            }
        }
        
        // Apply grade filter
        if (isset($params['grade']) && !empty($params['grade']) && is_array($params['grade'])) {
            $gradeConditions = [];
            foreach ($params['grade'] as $gradeId) {
                $gradeConditions[] = "FIND_IN_SET('{$gradeId}', c.grade)";
            }
            if (!empty($gradeConditions)) {
                $builder->where('(' . implode(' OR ', $gradeConditions) . ')');
            }
        }
        
        // Apply subject filter
        if (isset($params['subject']) && !empty($params['subject']) && is_array($params['subject'])) {
            $subjectConditions = [];
            foreach ($params['subject'] as $subjectId) {
                $subjectConditions[] = "FIND_IN_SET('{$subjectId}', c.subject)";
            }
            if (!empty($subjectConditions)) {
                $builder->where('(' . implode(' OR ', $subjectConditions) . ')');
            }
        }
        
        // Apply library filter (content type)
        if (isset($params['library']) && !empty($params['library'])) {
            switch ($params['library']) {
                case 'Resource':
                    $builder->where('c.content_format', 'resource');
                    break;
                case 'Assignment':
                    $builder->where('c.content_format', 'assignment');
                    break;
                case 'Assessment':
                    $builder->where('c.content_format', 'assessment');
                    break;
            }
        }
        
        // Apply filter (authored by me, my draft, etc.)
        if (isset($params['filter']) && !empty($params['filter'])) {
            switch ($params['filter']) {
                case 'authored_by_me':
                    $builder->where('c.created_by', $params['user_id']);
                    break;
                case 'my_draft':
                    $builder->where('c.status', '0'); // Draft status
                    $builder->where('c.created_by', $params['user_id']);
                    break;
            }
        }
        
        // Apply sorting
        if (isset($params['sort']) && !empty($params['sort'])) {
            switch ($params['sort']) {
                case 'A - Z':
                    $builder->orderBy('c.name', 'ASC');
                    break;
                case 'Z - A':
                    $builder->orderBy('c.name', 'DESC');
                    break;
                case 'Popularity':
                    $builder->orderBy('c.content_id', 'DESC'); // Simplified popularity
                    break;
                case 'Recent':
                default:
                    $builder->orderBy('c.created_date', 'DESC');
                    break;
            }
        } else {
            $builder->orderBy('c.created_date', 'DESC');
        }
        
        // Apply pagination
        if (isset($params['page_no']) && isset($params['records_per_page'])) {
            $offset = ($params['page_no'] - 1) * $params['records_per_page'];
            $builder->limit($params['records_per_page'], $offset);
            log_message('debug', '🔍 PAGINATION - Backend applying LIMIT: records=' . $params['records_per_page'] . ', offset=' . $offset . ', page=' . $params['page_no']);
        } else {
            log_message('warning', '⚠️ PAGINATION - Missing pagination params! page_no=' . ($params['page_no'] ?? 'NOT SET') . ', records_per_page=' . ($params['records_per_page'] ?? 'NOT SET'));
        }
        
        $results = $builder->get()->getResultArray();
        
        log_message('debug', '📦 PAGINATION - Backend returning ' . count($results) . ' records');
        
        // Process results
        foreach ($results as $key => $result) {
            // Process annotation
            if (!empty($result['annotation']) && $result['annotation'] != '[]') {
                $results[$key]['annotation'] = json_decode($result['annotation'], true);
            } else {
                $results[$key]['annotation'] = [];
            }
            
            // Process question annotation
            if (!empty($result['questionAnnotation']) && $result['questionAnnotation'] != '[]') {
                $results[$key]['questionAnnotation'] = json_decode($result['questionAnnotation'], true);
            } else {
                $results[$key]['questionAnnotation'] = [];
            }
            
            // Process tags
            if (!empty($result['tags'])) {
                $results[$key]['tags'] = array_filter(explode(',', $result['tags']));
            } else {
                $results[$key]['tags'] = [];
            }
            
            // Process links
            if (!empty($result['links'])) {
                $results[$key]['links'] = json_decode($result['links'], true);
            } else {
                $results[$key]['links'] = [];
            }
        }
        
        return $results;
    }

    /**
     * Get test types (SAT, ACT, etc.)
     */
    public function getTestTypes($params)
    {
        $db = \Config\Database::connect();
        
        // Query test_type table if it exists, otherwise return default types
        $query = "SELECT test_type_id, test_type, status 
                  FROM test_type 
                  WHERE status = 1 
                  ORDER BY test_type_id";
        
        try {
            $result = $db->query($query)->getResultArray();
            
            if (empty($result)) {
                // Return default test types if table is empty
                $result = [
                    ['test_type_id' => '1', 'test_type' => 'SAT', 'status' => '1'],
                    ['test_type_id' => '2', 'test_type' => 'ACT', 'status' => '1'],
                    ['test_type_id' => '3', 'test_type' => 'Other', 'status' => '1']
                ];
            }
            
            return $result;
        } catch (\Exception $e) {
            log_message('error', 'ContentModel::getTestTypes error: ' . $e->getMessage());
            // Return default types on error
            return [
                ['test_type_id' => '1', 'test_type' => 'SAT', 'status' => '1'],
                ['test_type_id' => '2', 'test_type' => 'ACT', 'status' => '1'],
                ['test_type_id' => '3', 'test_type' => 'Other', 'status' => '1']
            ];
        }
    }

    /**
     * Get content list for a user/school
     */
    public function contentList($params)
    {
        $db = \Config\Database::connect();
        
        $builder = $db->table('content c');
        
        $builder->select('c.content_id, c.name, c.subject, c.grade, c.content_format, c.content_type, 
                         c.status, c.created_by, c.created_date, c.file_path, c.tags, c.links,
                         COALESCE(c.description, \'\') AS description,
                         COALESCE((SELECT GROUP_CONCAT(grade_name) FROM grade 
                                  WHERE FIND_IN_SET(grade_id, c.grade)), \'\') AS grade_name,
                         COALESCE((SELECT GROUP_CONCAT(subject_name) FROM subject 
                                  WHERE FIND_IN_SET(subject_id, c.subject)), \'\') AS subject_name,
                         COALESCE((SELECT CONCAT_WS(" ", first_name, last_name) FROM user_profile 
                                  WHERE user_id = c.created_by LIMIT 1), \'\') AS created_by_name');
        
        // Apply school filter
        if (isset($params['school_id']) && !empty($params['school_id'])) {
            $builder->where('c.school_id', $params['school_id']);
        }
        
        // Apply user filter (for "my content")
        if (isset($params['user_id']) && isset($params['filter']) && $params['filter'] == 'my_content') {
            $builder->where('c.created_by', $params['user_id']);
        }
        
        // Only published content by default
        if (!isset($params['include_draft']) || $params['include_draft'] != 1) {
            $builder->where('c.status', 1);
        }
        
        // Order by recent
        $builder->orderBy('c.created_date', 'DESC');
        
        // Apply limit if specified
        if (isset($params['limit'])) {
            $builder->limit($params['limit']);
        }
        
        return $builder->get()->getResultArray();
    }

    public function getSortMaster($data)
    {
        $db = \Config\Database::connect();
        
        // Build conditions based on parameters
        $params = (array)$data;
        
        // Initialize search conditions
        $search = "LIKE '%%'";
        if (isset($params['search_name']) && $params['search_name'] != '') {
            $searchName = $db->escapeString($params['search_name']);
            if (isset($params['exact_search']) && $params['exact_search'] == 1) {
                $search = "= '{$searchName}'";
            } else {
                $search = "LIKE '%{$searchName}%'";
            }
        }
        
        // Content type condition
        $contentTypeCondition = '';
        if (isset($params['library'])) {
            if ($params['library'] == 1) {
                $contentTypeCondition = "AND c.content_type = 1";
            } elseif ($params['library'] == 2) {
                $contentTypeCondition = "AND c.content_type = 2";
            } elseif ($params['library'] == 3) {
                $contentTypeCondition = "AND c.content_type = 3";
            }
        }
        
        // Draft/published status condition
        $myDraft = '';
        $type = $params['type'] ?? 'list';
        if ($type == "list") {
            if ($params['role_id'] == 2) {
                $myDraft = "AND c.status != 4 AND c.status IN (1,(CASE WHEN c.school_id = '{$params['school_id']}' THEN 5 ELSE 0 END))";
            } else {
                $myDraft = "AND c.status != 4 AND c.status IN (1,(CASE WHEN c.created_by = '{$params['user_id']}' THEN 5 ELSE 0 END))";
            }
        } elseif ($type == "mydraft") {
            if ($params['role_id'] == 2) {
                $myDraft = "AND c.status != 4 AND c.status IN ((CASE WHEN c.school_id = '{$params['school_id']}' THEN 5 ELSE 0 END))";
            } else {
                $myDraft = "AND c.status != 4 AND c.status = 5 AND c.created_by = {$params['user_id']}";
            }
        }
        
        // Sorting and author filter
        $condition = "ORDER BY c.created_date DESC";
        $authorCondition = '';
        if (isset($params['content_user_id']) && $params['content_user_id'] > 0) {
            $authorCondition = "AND c.created_by = {$params['content_user_id']}";
            $condition = "ORDER BY c.created_date DESC";
        } elseif (isset($params['sort_by'])) {
            if ($params['sort_by'] == '0') {
                $condition = "ORDER BY c.created_date DESC";
            } elseif ($params['sort_by'] == -1) {
                $condition = "ORDER BY popularity DESC";
            } elseif ($params['sort_by'] == 'AZ') {
                $condition = "ORDER BY c.name ASC";
            } elseif ($params['sort_by'] == 'ZA') {
                $condition = "ORDER BY c.name DESC";
            }
        }
        
        // Pagination
        $limit = '';
        if (isset($params['page']) && $params['page'] != "" && isset($params['record_per_page'])) {
            $recordsPerPage = ($params['page'] - 1) * $params['record_per_page'];
            $page = $params['record_per_page'];
            $limit = "LIMIT $recordsPerPage, $page";
        }
        
        // Access condition based on role
        $accessCondition = '';
        if ($params['role_id'] == 2) {
            $accessCondition = "c.school_id='{$params['school_id']}'";
        } elseif ($params['role_id'] > 2 && $params['role_id'] < 5) {
            $accessCondition = "c.school_id='{$params['school_id']}' AND c.access IN (1,(CASE WHEN c.created_by = '{$params['user_id']}' THEN 2 ELSE 0 END))";
        } elseif ($params['role_id'] == 6) {
            $accessCondition = "c.school_id IN ('{$params['school_id']}')";
        }
        
        if (isset($params['user_id']) && isset($params['content_user_id']) && $params['user_id'] == $params['content_user_id']) {
            $accessCondition = "c.school_id='{$params['school_id']}'";
        }
        
        // Build the main query
        $query = "SELECT c.content_id, COUNT(cc.content_id) AS popularity, c.name, 
                  COALESCE(c.description,'') AS description, c.grade, 
                  COALESCE((SELECT GROUP_CONCAT(grade_name) FROM grade 
                  WHERE FIND_IN_SET(grade_id, c.grade)),'') AS grade_name, c.subject, 
                  COALESCE((SELECT GROUP_CONCAT(subject_name) FROM subject 
                  WHERE FIND_IN_SET(subject_id, c.subject)),'') AS subject_name, c.school_id,
                  COALESCE(c.links,'') AS links, c.file_path,
                  COALESCE(c.file_text,'') AS file_text, c.annotation, c.questionAnnotation, 
                  c.content_type, c.download, 
                  COALESCE(c.tags, '') AS tags, c.content_format, c.access, c.status, c.editor_type, 
                  COALESCE(c.profile_url,'') AS profile_url, 
                  COALESCE(c.profile_thumb_url, '') AS profile_thumb_url,
                  (SELECT CONCAT_WS(' ',first_name,last_name) FROM user_profile
                  WHERE user_id = c.created_by) AS created_by, c.created_date, c.is_test,
                  (CASE WHEN c.created_by = {$params['user_id']} THEN 1 ELSE 0 END) AS edit_status,
                  (CASE WHEN EXISTS (SELECT 1 FROM text_questions tq WHERE tq.question_type_id = 55 
                  AND tq.content_id = c.content_id AND c.content_format = 3) THEN 1 ELSE 0 END) AS is_essay 
                  FROM content c
                  LEFT JOIN class_content cc ON c.content_id = cc.content_id
                  WHERE ($accessCondition $myDraft $contentTypeCondition $authorCondition)
                  OR (c.access = 3 $contentTypeCondition $myDraft $authorCondition)
                  GROUP BY c.content_id $condition $limit";
        
        $result = $db->query($query)->getResultArray();
        
        // Process the results
        $processedData = [];
        foreach ($result as $index => $value) {
            $processedData[$index] = $value;
            
            // Process annotation
            if (!empty($value['annotation']) && $value['annotation'] != '[]') {
                $processedData[$index]['annotation'] = json_decode($value['annotation']);
            }
            
            // Process questionAnnotation
            if (!empty($value['questionAnnotation']) && $value['questionAnnotation'] != '[]') {
                $processedData[$index]['questionAnnotation'] = json_decode($value['questionAnnotation']);
            } else {
                $processedData[$index]['questionAnnotation'] = [];
            }
            
            // Check if PDF content
            if (!empty($value['file_path'])) {
                $filePath = json_decode($value['file_path'], true);
                $processedData[$index]['is_pdf_content'] = (!empty($filePath[0]['original_image_url'])) ? 1 : 0;
            } else {
                $processedData[$index]['is_pdf_content'] = 0;
            }
            
            // Process links and tags
            $links = !empty($value['links']) ? explode(',', $value['links']) : [];
            $tags = !empty($value['tags']) ? explode(',', $value['tags']) : [];
            $processedData[$index]['links'] = array_filter($links);
            $processedData[$index]['tags'] = array_filter($tags);
        }
        
        return $processedData;
    }

    /**
     * Get detailed content information by content_id
     */
    public function contentIdList($params)
    {
        $db = \Config\Database::connect();
        
        $condition = '';
        $notes = '';
        $download = '';
        $allowFeedback = '';
        $allowWorkspace = '';
        $showTimer = '';
        
        // If fetching for a specific class
        if (isset($params['class_id'])) {
            $classContentTable = isset($params['class_content_id']) && $params['class_content_id'] > 0
                ? "AND id = '{$params['class_content_id']}'"
                : "";
            
            $notes = ",(SELECT COALESCE(notes, '') FROM class_content 
                      WHERE class_id = '{$params['class_id']}' AND content_id = '{$params['content_id']}' 
                      AND status = 1 $classContentTable LIMIT 1) as notes";
            
            $allowFeedback = ",(SELECT allow_feedback FROM class_content 
                             WHERE class_id = '{$params['class_id']}' AND content_id = '{$params['content_id']}' 
                             AND status = 1 $classContentTable LIMIT 1) as allow_feedback";
            
            $allowWorkspace = ",(SELECT allow_workspace FROM class_content 
                              WHERE class_id = '{$params['class_id']}' AND content_id = '{$params['content_id']}' 
                              AND status = 1 $classContentTable LIMIT 1) as allow_workspace";
            
            $showTimer = ",(SELECT show_timer FROM class_content 
                         WHERE class_id = '{$params['class_id']}' AND content_id = '{$params['content_id']}' 
                         AND status = 1 $classContentTable LIMIT 1) as show_timer";
        }
        
        // Download field
        if (isset($params['student_id'])) {
            $download = ",(SELECT downloadable FROM class_content 
                        WHERE content_id = '{$params['content_id']}' AND class_id = '{$params['class_id']}' 
                        AND status = 1 LIMIT 1) as download";
        } else {
            $download = ",(SELECT download FROM content WHERE content_id = '{$params['content_id']}') as download";
        }
        
        // Student-specific fields
        if (isset($params['student_id']) && $params['student_id'] > 0 && isset($params['student_content_id'])) {
            $condition = "COALESCE((SELECT student_feedback FROM student_content 
                         WHERE id = {$params['student_content_id']}),'') as overall_student_feedback,
                         COALESCE((SELECT feedback FROM student_content 
                         WHERE id = {$params['student_content_id']}),'') as teacher_feedback,
                         COALESCE((SELECT upload_answer FROM student_content 
                         WHERE id = {$params['student_content_id']}),'') as upload_answer,";
        }
        
        $query = "SELECT c.content_id, c.name, COALESCE(c.description, '') as description,
                  c.grade, c.subject, c.school_id, COALESCE(c.links, '') as links,
                  c.file_path, COALESCE(c.file_text, '') as file_text, c.annotation,
                  c.questionAnnotation, c.content_type, c.content_format, c.access, c.status,
                  c.editor_type, COALESCE(c.profile_url, '') as profile_url,
                  COALESCE(c.profile_thumb_url, '') as profile_thumb_url,
                  COALESCE(c.tags, '') as tags, c.is_test, c.test_type_id,
                  COALESCE(c.answerkey_path, '') as answerkey_path,
                  COALESCE(c.teacher_version, '') as teacher_version,
                  COALESCE(c.allow_answer_key, '0') as allow_answer_key,
                  COALESCE(c.content_duration, '0') as content_duration,
                  COALESCE(c.total_questions, 0) as total_questions,
                  COALESCE((SELECT GROUP_CONCAT(grade_name) FROM grade 
                           WHERE FIND_IN_SET(grade_id, c.grade)), '') AS grade_name,
                  COALESCE((SELECT GROUP_CONCAT(subject_name) FROM subject 
                           WHERE FIND_IN_SET(subject_id, c.subject)), '') AS subject_name,
                  $condition
                  (SELECT CONCAT_WS(' ', first_name, last_name) FROM user_profile 
                  WHERE user_id = c.created_by) as created_by,
                  c.created_by, c.created_date
                  $notes $download $allowFeedback $allowWorkspace $showTimer
                  FROM content c
                  WHERE c.content_id = '{$params['content_id']}'
                  LIMIT 1";
        
        $result = $db->query($query)->getRowArray();
        
        return $result;
    }

    /**
     * Get question details for content
     */
    public function getQuestionDetails($contentId)
    {
        $db = \Config\Database::connect();
        
        // Remove status filter since text_questions table doesn't have status column
        $query = "SELECT question_id, audo_grade 
                  FROM text_questions 
                  WHERE content_id = '$contentId'";
        
        return $db->query($query)->getResultArray();
    }
    
    /**
     * Get all questions for content (for repository detail view)
     */
    public function getQuestionsForContent($contentId)
    {
        $db = \Config\Database::connect();
        
        $query = "SELECT tq.question_id, tq.content_id, tq.question_type_id, tq.sub_question_type_id, 
                  COALESCE(tq.editor_context, '') AS editor_context, tq.editor_type, tq.question_no,
                  tq.sub_question_no, tq.has_sub_question, 
                  CASE 
                    WHEN tq.question IS NULL OR tq.question = '' OR LOWER(TRIM(tq.question)) = 'undefined' OR LOWER(TRIM(tq.question)) = 'null' 
                    THEN '' 
                    ELSE tq.question 
                  END AS question, 
                  tq.options, tq.answer, tq.level, tq.heading_option,
                  tq.multiple_response, tq.audo_grade, tq.points, tq.exact_match, tq.hint, tq.explanation, 
                  COALESCE(tq.resource, '') AS resource, tq.word_limit,
                  tq.scoring_instruction, COALESCE(tq.editor_answer, '') as editor_answer,
                  COALESCE(tq.source, '') AS source,
                  COALESCE(tq.target, '') AS target, tq.passage_id, tq.created_by, tq.created_date,
                  COALESCE(p.passage, '') AS passage, COALESCE(p.title, '') AS passage_title,
                  tq.subject_id, tq.question_topic_id, tq.question_standard, tq.question_sub_topic_id, tq.skill
                  FROM text_questions tq
                  LEFT JOIN passage as p ON tq.passage_id = p.passage_id 
                  WHERE tq.content_id = '$contentId'
                  ORDER BY tq.question_no ASC, tq.sub_question_no ASC";
        
        log_message('debug', '🔍 [TEXT_QUESTIONS] Query: ' . $query);
        log_message('debug', '🔍 [TEXT_QUESTIONS] Content ID: ' . $contentId);
        
        $results = $db->query($query)->getResultArray();
        
        // Log what's being fetched from the database
        log_message('debug', '🔍 [TEXT_QUESTIONS] Total questions found: ' . count($results));
        
        foreach ($results as $index => $question) {
            log_message('debug', "🔍 [TEXT_QUESTIONS] Question #{$index}:");
            log_message('debug', "   - question_id: " . ($question['question_id'] ?? 'NOT SET'));
            log_message('debug', "   - question_no: " . ($question['question_no'] ?? 'NOT SET'));
            log_message('debug', "   - question_type_id: " . ($question['question_type_id'] ?? 'NOT SET'));
            log_message('debug', "   - question (RAW from DB): " . var_export($question['question'] ?? 'NOT SET', true));
            log_message('debug', "   - question (type): " . gettype($question['question'] ?? null));
            log_message('debug', "   - question (is_null): " . (is_null($question['question'] ?? null) ? 'YES' : 'NO'));
            log_message('debug', "   - question (is_empty): " . (empty($question['question'] ?? null) ? 'YES' : 'NO'));
            log_message('debug', "   - question (length): " . (isset($question['question']) ? strlen($question['question']) : 'N/A'));
            log_message('debug', "   - question (first 100 chars): " . (isset($question['question']) ? substr($question['question'], 0, 100) : 'N/A'));
        }
        
        // Also log the raw SQL result for the question column
        $rawQuery = "SELECT question_id, question_no, question, question_type_id 
                     FROM text_questions 
                     WHERE content_id = '$contentId'
                     ORDER BY question_no ASC, sub_question_no ASC";
        $rawResults = $db->query($rawQuery)->getResultArray();
        log_message('debug', '🔍 [TEXT_QUESTIONS] Raw SQL results for question column: ' . json_encode($rawResults, JSON_PRETTY_PRINT));
        
        return $results;
    }

    /**
     * Check if content already assigned to batch
     */
    public function checkBatchContent($batchId, $contentId)
    {
        $db = \Config\Database::connect();
        
        return $db->table('classroom_content')
                  ->where('batch_id', $batchId)
                  ->where('content_id', $contentId)
                  ->where('status', 1)
                  ->get()
                  ->getResultArray();
    }

    /**
     * Get question types list
     */
    public function questionTypeList()
    {
        $db = \Config\Database::connect();
        
        $query = "SELECT rtm.resource_type, qt.question_type_id, qt.resource_type_id, qt.question_type, qt.image_path, qt.icon_path
                  FROM resource_type_master rtm
                  LEFT JOIN question_types qt ON rtm.resource_type_id = qt.resource_type_id
                  WHERE rtm.status = 1 AND qt.question_uploads = 1
                  ORDER BY qt.resource_type_id, qt.question_type_id";
        
        $result = $db->query($query)->getResultArray();
        
        return $result;
    }

    /**
     * Get question standard list
     */
    public function questionStandard($condition = '')
    {
        $db = \Config\Database::connect();
        
        // The table likely uses 'id' as primary key instead of 'question_standard_id'
        // Map it to 'question_standard_id' for frontend compatibility
        $whereClause = '';
        if (!empty($condition)) {
            $whereClause = $condition;
        }
        
        $query = "SELECT id as question_standard_id, question_standard, status 
                  FROM question_standard 
                  {$whereClause}
                  ORDER BY id";
        
        try {
            $result = $db->query($query)->getResultArray();
            return $result;
        } catch (\Exception $e) {
            // Log the error for debugging
            log_message('error', 'questionStandard query error: ' . $e->getMessage());
            // Return empty array if table doesn't exist or has different structure
            return [];
        }
    }

    /**
     * Get question topic list
     */
    public function questionTopic($condition = '')
    {
        $db = \Config\Database::connect();
        
        // Build WHERE clause
        $whereClause = '';
        if (!empty($condition)) {
            $whereClause = $condition;
        }
        
        $query = "SELECT question_topic_id, question_topic, status 
                  FROM question_topic 
                  {$whereClause}
                  ORDER BY question_topic_id";
        
        $result = $db->query($query)->getResultArray();
        
        return $result;
    }

    /**
     * Get question subtopic list
     */
    public function questionSubTopic($condition = '')
    {
        $db = \Config\Database::connect();
        
        $query = "SELECT sub_topic_id, question_topic_id, sub_topic, status 
                  FROM sub_topic 
                  {$condition}
                  ORDER BY sub_topic_id";
        
        $result = $db->query($query)->getResultArray();
        
        return $result;
    }

    /**
     * Get answer list for content
     */
    public function answerList($params)
    {
        $db = \Config\Database::connect();
        
        $condition = "";
        // Only add student-specific fields if we have a valid student_content_id
        if (isset($params['student_id']) && $params['student_id'] > 0 && 
            isset($params['student_content_id']) && $params['student_content_id'] > 0) {
            $studentContentId = (int)$params['student_content_id'];
            $condition = ",COALESCE((SELECT COALESCE(student_answer,'') FROM student_answers WHERE student_content_id = {$studentContentId} AND answer_id = a.answer_id),'') as student_answer,
                            COALESCE((SELECT COALESCE(jiixdata,'') FROM student_answers WHERE student_content_id = {$studentContentId} AND answer_id = a.answer_id),'') as jiixdata,
                            COALESCE((SELECT COALESCE(roughdata,'') FROM student_answers WHERE student_content_id = {$studentContentId} AND answer_id = a.answer_id),'') as roughdata,
                            COALESCE((SELECT COALESCE(rough_image_url,'') FROM student_answers WHERE student_content_id = {$studentContentId} AND answer_id = a.answer_id),'') as rough_image_url,
                            COALESCE((SELECT COALESCE(rough_image_thumb_url,'') FROM student_answers WHERE student_content_id = {$studentContentId} AND answer_id = a.answer_id),'') as rough_image_thumb_url,
                            COALESCE((SELECT COALESCE(student_answer_image,'') FROM student_answers WHERE student_content_id = {$studentContentId} AND answer_id = a.answer_id),'') as student_answer_image,
                            COALESCE((SELECT COALESCE(student_roughdata,'') FROM student_answers WHERE student_content_id = {$studentContentId} AND answer_id = a.answer_id),'') as student_roughdata,
                           (SELECT status FROM student_content WHERE id = {$studentContentId}) as student_content_status,
                           COALESCE((SELECT COALESCE(annotation,'') FROM student_content WHERE id = {$studentContentId}),'') as student_annotation,
                           COALESCE((select suggestion_query from student_suggestions where content_id={$params['content_id']} AND class_id = {$params['class_id']} AND student_id = {$params['student_id']} AND answer_id = a.answer_id),'') as student_feedback,
                           COALESCE((SELECT COALESCE(editor_answer,'') FROM student_answers WHERE student_content_id = {$studentContentId} AND answer_id = a.answer_id),'') as student_editor_answer";
        }
        
        $query = "SELECT a.answer_id, a.question_no, 
                  COALESCE(a.question,'') AS question, a.mob_options, 
                  COALESCE(a.section_heading,'') AS heading,a.display_order,a.content_id, a.question_type_id,
                   a.has_sub_question,a.page_no,
                  COALESCE(a.sub_question_no, '') AS sub_question_no, COALESCE(a.options, '') AS options, a.array, a.answer,
                  COALESCE(a.editor_answer) as editor_answer, a.auto_grade,  a.points, 
                  COALESCE(a.difficulty, '') AS difficulty, 
                  COALESCE(a.allow_exact_match, '') AS allow_exact_match,
                  COALESCE(a.allow_any_text, '') AS allow_any_text, 
                  COALESCE(a.match_case, '') AS match_case, 
                  COALESCE(a.answer_explanation, '') as answer_explanation,
                  COALESCE(a.minimum_line, '') AS minimum_line $condition
                  FROM answers a
                  WHERE a.content_id = '{$params['content_id']}' AND a.status = 1
                  ORDER BY a.answer_id ASC";
        
        log_message('debug', '🔍 [ANSWER LIST] Query: ' . $query);
        log_message('debug', '🔍 [ANSWER LIST] Content ID: ' . ($params['content_id'] ?? 'NOT SET'));
        log_message('debug', '🔍 [ANSWER LIST] Params: ' . json_encode($params));
        
        $results = $db->query($query)->getResultArray();
        
        log_message('debug', '🔍 [ANSWER LIST] Results count: ' . count($results));
        log_message('debug', '🔍 [ANSWER LIST] Results (flat): ' . json_encode($results));
        
        // Also check what's in the database without status filter
        $checkQuery = "SELECT answer_id, content_id, question_no, question, status, section_heading FROM answers WHERE content_id = '{$params['content_id']}' ORDER BY answer_id ASC";
        $allResults = $db->query($checkQuery)->getResultArray();
        log_message('debug', '🔍 [ANSWER LIST] All answers in DB (including status != 1): ' . json_encode($allResults));
        
        // Group answers by section heading into the structure expected by frontend
        // Expected structure: [{heading: "...", section: [{has_sub_question: 0, sub_questions: [...]}]}]
        $groupedAnswers = [];
        foreach ($results as $answer) {
            // Ensure given_answer is set from student_answer if available
            if (!isset($answer['given_answer']) && isset($answer['student_answer'])) {
                $answer['given_answer'] = $answer['student_answer'];
            } elseif (!isset($answer['given_answer'])) {
                $answer['given_answer'] = '';
            }
            
            // Ensure question is set and not null or the string "undefined"
            if (!isset($answer['question']) || $answer['question'] === null || $answer['question'] === 'undefined' || $answer['question'] === 'null') {
                $answer['question'] = '';
            }
            
            // Clean up any "undefined" strings that might have been set
            if (isset($answer['question']) && (strtolower($answer['question']) === 'undefined' || strtolower($answer['question']) === 'null')) {
                $answer['question'] = '';
            }
            
            // Ensure options/array are properly initialized
            if (isset($answer['options']) && is_string($answer['options']) && !empty($answer['options'])) {
                $decoded = json_decode($answer['options'], true);
                if ($decoded !== null) {
                    $answer['array'] = $decoded;
                    $answer['mob_options'] = $decoded;
                }
            }
            
            $heading = $answer['heading'] ?? '';
            $hasSubQuestion = isset($answer['has_sub_question']) ? (int)$answer['has_sub_question'] : 0;
            
            // Use heading as key, or 'default' if empty
            $headingKey = $heading !== '' ? $heading : 'default';
            
            // Initialize section group if it doesn't exist
            if (!isset($groupedAnswers[$headingKey])) {
                $groupedAnswers[$headingKey] = [
                    'heading' => $heading,
                    'section' => []
                ];
            }
            
            // Find or create a section with matching has_sub_question
            $sectionFound = false;
            foreach ($groupedAnswers[$headingKey]['section'] as &$section) {
                if ($section['has_sub_question'] == $hasSubQuestion) {
                    $section['sub_questions'][] = $answer;
                    $sectionFound = true;
                    break;
                }
            }
            
            // If no matching section found, create a new one
            if (!$sectionFound) {
                $groupedAnswers[$headingKey]['section'][] = [
                    'has_sub_question' => $hasSubQuestion,
                    'sub_questions' => [$answer]
                ];
            }
        }
        
        // Convert associative array to indexed array
        $finalResults = array_values($groupedAnswers);
        
        log_message('debug', '🔍 [ANSWER LIST] Grouped results count: ' . count($finalResults));
        log_message('debug', '🔍 [ANSWER LIST] Grouped results: ' . json_encode($finalResults));
        
        return $finalResults;
    }
    
    /**
     * Get or create student_content record (lazy creation)
     */
    public function getOrCreateStudentContent($studentId, $contentId, $classContentId, $classId, $userId)
    {
        $db = \Config\Database::connect();
        
        // Check if student_content exists
        $studentContent = $db->table('student_content')
            ->where('student_id', $studentId)
            ->where('content_id', $contentId)
            ->where('class_content_id', $classContentId)
            ->get()
            ->getRowArray();
        
        if ($studentContent) {
            // Ensure class access exists
            $accessModel = new \App\Models\V1\StudentContentClassAccessModel();
            $hasAccess = $accessModel->where([
                'student_content_id' => $studentContent['id'],
                'class_id' => $classId
            ])->first();
            
            if (!$hasAccess) {
                $accessModel->addClassAccess($studentContent['id'], $classId, $classContentId, $userId);
            }
            
            return $studentContent;
        }
        
        // Create new student_content record
        $data = [
            'student_id' => $studentId,
            'content_id' => $contentId,
            'class_content_id' => $classContentId,
            'grade_id' => $this->getGradeIdFromClassContent($classContentId),
            'status' => 1, // Yet to start
            'created_by' => $userId,
            'created_date' => date('Y-m-d H:i:s'),
            'modified_by' => $userId,
            'modified_date' => date('Y-m-d H:i:s')
        ];
        
        // Get start/end dates from class_content
        $classContent = $db->table('class_content')
            ->where('id', $classContentId)
            ->get()
            ->getRowArray();
        
        if ($classContent) {
            $data['start_date'] = $classContent['start_date'];
            $data['end_date'] = $classContent['end_date'];
        }
        
        $studentContentId = $db->table('student_content')->insert($data);
        
        // Create class access
        $accessModel = new \App\Models\V1\StudentContentClassAccessModel();
        $accessModel->addClassAccess($studentContentId, $classId, $classContentId, $userId);
        
        return $db->table('student_content')->where('id', $studentContentId)->get()->getRowArray();
    }
    
    /**
     * Get grade_id from class_content
     */
    private function getGradeIdFromClassContent($classContentId)
    {
        $db = \Config\Database::connect();
        $classContent = $db->table('class_content')
            ->select('classes.grade_id')
            ->join('classes', 'classes.id = class_content.class_id')
            ->where('class_content.id', $classContentId)
            ->get()
            ->getRowArray();
        
        return $classContent ? $classContent['grade_id'] : 0;
    }
    
    /**
     * Get class curriculum with consolidated view (no duplicates)
     */
    public function getClassCurriculumConsolidated($classId)
    {
        $db = \Config\Database::connect();
        
        $sql = "
            SELECT 
                c.content_id,
                c.name as title,
                c.content_type as type,
                cc.id as class_content_id,
                cc.start_date,
                cc.end_date,
                cc.all_student,
                GROUP_CONCAT(DISTINCT 
                    CASE WHEN cc.all_student = 0 THEN up.first_name END 
                    SEPARATOR ', '
                ) as individual_students,
                COUNT(DISTINCT CASE WHEN cc.all_student = 0 THEN u.user_id END) as individual_count,
                MAX(cc.all_student) as has_class_assignment
            FROM class_content cc
            INNER JOIN content c ON cc.content_id = c.content_id
            LEFT JOIN student_content sc ON cc.id = sc.class_content_id
            LEFT JOIN user u ON sc.student_id = u.user_id AND cc.all_student = 0
            LEFT JOIN user_profile up ON u.user_id = up.user_id
            WHERE cc.class_id = ?
            AND cc.status = 1
            GROUP BY c.content_id, cc.id
            ORDER BY cc.start_date DESC, c.name
        ";
        
        return $db->query($sql, [$classId])->getResultArray();
    }
    
    /**
     * Get student's visible content (from all their classes)
     */
    public function getStudentAvailableContent($studentId)
    {
        $db = \Config\Database::connect();
        
        $sql = "
            SELECT DISTINCT
                c.content_id,
                c.name as title,
                c.content_type as type,
                cc.id as class_content_id,
                cc.class_id,
                cc.start_date,
                cc.end_date,
                cc.all_student,
                sc.id as student_content_id,
                sc.status,
                sc.laq_id,
                CASE 
                    WHEN sc.id IS NULL THEN 'Not Started'
                    WHEN sc.status = 1 THEN 'Yet to Start'
                    WHEN sc.status = 2 THEN 'In Progress'
                    WHEN sc.status = 3 THEN 'Verified'
                    WHEN sc.status = 4 THEN 'Completed'
                    WHEN sc.status = 5 THEN 'Corrected'
                    WHEN sc.status = 6 THEN 'Pending Verification'
                END as status_text
            FROM class_content cc
            INNER JOIN content c ON cc.content_id = c.content_id
            INNER JOIN student_class scs ON cc.class_id = scs.class_id
            LEFT JOIN student_content sc ON c.content_id = sc.content_id 
                AND sc.student_id = ?
                AND sc.class_content_id = cc.id
            WHERE scs.student_id = ?
            AND cc.status = 1
            AND (
                cc.all_student = 1 
                OR EXISTS (
                    SELECT 1 FROM student_content sc2 
                    WHERE sc2.class_content_id = cc.id 
                    AND sc2.student_id = ?
                )
            )
            AND CURDATE() BETWEEN cc.start_date AND cc.end_date
            ORDER BY cc.start_date, c.name
        ";
        
        return $db->query($sql, [$studentId, $studentId, $studentId])->getResultArray();
    }
    
    /**
     * Get teacher's gradable content (via class access)
     */
    public function getTeacherGradableContent($teacherId, $classId)
    {
        $db = \Config\Database::connect();
        
        $sql = "
            SELECT 
                sc.*,
                c.name as title,
                up.first_name,
                up.last_name,
                scca.class_id
            FROM student_content sc
            INNER JOIN student_content_class_access scca ON sc.id = scca.student_content_id
            INNER JOIN content c ON sc.content_id = c.content_id
            INNER JOIN user u ON sc.student_id = u.user_id
            INNER JOIN user_profile up ON u.user_id = up.user_id
            WHERE scca.class_id = ?
            AND sc.status IN (3, 5, 6)
            ORDER BY sc.modified_date DESC
        ";
        
        return $db->query($sql, [$classId])->getResultArray();
    }
}
