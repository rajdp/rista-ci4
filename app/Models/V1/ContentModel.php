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
                  $condition
                  (SELECT CONCAT_WS(' ', first_name, last_name) FROM user_profile 
                  WHERE user_id = c.created_by) as created_by
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
        
        $query = "SELECT question_id, audo_grade 
                  FROM text_questions 
                  WHERE content_id = '$contentId' AND status = 1";
        
        return $db->query($query)->getResultArray();
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
}
