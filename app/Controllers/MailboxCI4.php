<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

class MailboxCI4 extends ResourceController
{
    protected $format = 'json';

    /**
     * Get message count for classes (SSE endpoint)
     */
    public function getMessageCount()
    {
        // Write to error log to confirm method is being called
        error_log('🚀 MailboxCI4::getMessageCount() called at ' . date('Y-m-d H:i:s'));
        
        // Clear all output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Set CORS headers
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, Accesstoken');
        
        // Set SSE headers
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');
        
        // Disable output buffering
        @ini_set('output_buffering', 'off');
        @ini_set('zlib.output_compression', '0');
        @ini_set('display_errors', '0');
        
        $params = $this->request->getJSON(true) ?? [];
        
        if (empty($params)) {
            $params = $this->request->getPost() ?? [];
        }
        
        error_log('📦 Params received: ' . json_encode($params));
        
        // Validation
        if (empty($params['platform']) || ($params['platform'] != "web" && $params['platform'] != "ios")) {
            error_log('❌ Validation failed: Platform empty');
            echo 'data: ' . json_encode([
                "IsSuccess" => false,
                "ResponseObject" => null,
                "ErrorObject" => "Platform should not be empty"
            ]) . "\n\n";
            flush();
            exit;
        }
        
        if (empty($params['user_id'])) {
            error_log('❌ Validation failed: User ID empty');
            echo 'data: ' . json_encode([
                "IsSuccess" => false,
                "ResponseObject" => null,
                "ErrorObject" => "User ID should not be empty"
            ]) . "\n\n";
            flush();
            exit;
        }
        
        if (empty($params['class_id']) || !is_array($params['class_id']) || count($params['class_id']) == 0) {
            error_log('❌ Validation failed: Class ID empty or not array');
            echo 'data: ' . json_encode([
                "IsSuccess" => false,
                "ResponseObject" => null,
                "ErrorObject" => "Class ID should not be empty"
            ]) . "\n\n";
            flush();
            exit;
        }

        error_log('✅ All validations passed');

        try {
            // Use direct database query (mailbox model may not be available in CI4)
            $db = \Config\Database::connect();
            
            $messageCount = [];
            $classIds = implode(',', $params['class_id']);
            $userId = $params['user_id'];
            
            // Query to get unread messages - mailbox primary key is 'message_id'
            $query = "SELECT m.class_id, COUNT(*) as message_count 
                      FROM mailbox m
                      LEFT JOIN mailbox_details md ON m.message_id = md.message_id
                      WHERE m.class_id IN ({$classIds}) 
                        AND md.user_id = {$userId} 
                        AND md.is_read = 0
                      GROUP BY m.class_id";
            
            error_log('📊 SQL Query: ' . $query);
            
            $results = $db->query($query)->getResultArray();
            
            error_log('📦 Query returned ' . count($results) . ' rows: ' . json_encode($results));
            
            // Build response with counts for each class
            foreach ($params['class_id'] as $classId) {
                $count = 0;
                foreach ($results as $row) {
                    if ($row['class_id'] == $classId) {
                        $count = (int)$row['message_count'];
                        break;
                    }
                }
                
                $messageCount[] = [
                    'class_id' => $classId,
                    'newMessage' => $count
                ];
            }

            error_log('✅ Prepared response with ' . count($messageCount) . ' items: ' . json_encode($messageCount));
            
            // Return SSE format
            echo 'data: ' . json_encode($messageCount) . "\n\n";
            flush();
            
            error_log('✅ SSE response sent successfully');
            exit;
            
        } catch (\Exception $e) {
            error_log('❌ Exception in getMessageCount: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            echo 'data: ' . json_encode([
                "error" => $e->getMessage()
            ]) . "\n\n";
            flush();
            exit;
        }
    }
}

