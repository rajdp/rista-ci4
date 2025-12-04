<?php

namespace App\Controllers\Appt;

use App\Controllers\BaseController;
use App\Models\V1\SchoolModel;
use App\Traits\RestTrait;
use CodeIgniter\HTTP\ResponseInterface;

abstract class ApptController extends BaseController
{
    use RestTrait;

    protected SchoolModel $schoolModel;

    public function __construct()
    {
        $this->schoolModel = new SchoolModel();
    }

    protected function requireToken()
    {
        $token = $this->validateToken();
        if (!$token) {
            return $this->unauthorizedResponse('Access token is required');
        }
        return $token;
    }

    protected function resolveSchoolId($token, array $payload = []): ?int
    {
        if ($token) {
            $schoolIdFromToken = $this->getSchoolId($token);
            if (!empty($schoolIdFromToken)) {
                return (int) $schoolIdFromToken;
            }
        }

        $schoolId = $payload['school_id'] ?? $this->request->getGet('school_id');
        if (!empty($schoolId)) {
            return (int) $schoolId;
        }

        $schoolKey = $payload['school_key'] ?? $this->request->getGet('school_key');
        if (!empty($schoolKey)) {
            $row = $this->schoolModel->select('school_id')->where('LOWER(school_key)', strtolower($schoolKey))->get()->getRowArray();
            if (!empty($row['school_id'])) {
                return (int) $row['school_id'];
            }
        }

        return null;
    }

    protected function fetchSchoolMeta(int $schoolId): array
    {
        $row = $this->schoolModel->select('school_id, name')->where('school_id', $schoolId)->get()->getRowArray();
        return [
            'id' => $row['school_id'] ?? $schoolId,
            'display_name' => $row['name'] ?? 'School',
            'timezone' => 'America/New_York',
        ];
    }

    protected function jsonPayload(): array
    {
        // Try to get JSON payload first
        $json = $this->request->getJSON(true);
        log_message('info', '[jsonPayload] getJSON() returned: ' . (is_null($json) ? 'NULL' : (is_array($json) ? 'ARRAY[' . count($json) . ']' : gettype($json))));
        
        if ($json !== null && is_array($json)) {
            log_message('info', '[jsonPayload] ✅ Successfully parsed via getJSON: ' . json_encode(array_keys($json)));
            return $json;
        }
        
        // Fall back to POST data
        $post = $this->request->getPost();
        log_message('info', '[jsonPayload] getPost() returned: ' . (empty($post) ? 'EMPTY' : 'ARRAY[' . count($post) . ']'));
        
        if (!empty($post)) {
            log_message('info', '[jsonPayload] ✅ Using POST data: ' . json_encode(array_keys($post)));
            return $post;
        }
        
        // Last resort: try to parse raw body as JSON
        $rawBody = $this->request->getBody();
        $bodyLength = strlen($rawBody);
        log_message('info', '[jsonPayload] Raw body length: ' . $bodyLength);
        
        if (!empty($rawBody)) {
            // Show first 200 chars of body for debugging
            $preview = substr($rawBody, 0, 200);
            log_message('info', '[jsonPayload] Raw body preview: ' . $preview);
            
            $decoded = json_decode($rawBody, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                log_message('info', '[jsonPayload] ✅ Successfully decoded raw body: ' . json_encode(array_keys($decoded)));
                return $decoded;
            } else {
                log_message('error', '[jsonPayload] ❌ Failed to decode raw body. JSON error: ' . json_last_error_msg());
            }
        }
        
        log_message('warning', '[jsonPayload] ❌ No payload found in request');
        return [];
    }
}
