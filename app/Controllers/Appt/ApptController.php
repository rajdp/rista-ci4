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
        if ($json !== null && is_array($json)) {
            return $json;
        }
        
        // Fall back to POST data
        $post = $this->request->getPost();
        if (!empty($post)) {
            return $post;
        }
        
        // Last resort: try to parse raw body as JSON
        $rawBody = $this->request->getBody();
        if (!empty($rawBody)) {
            $decoded = json_decode($rawBody, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }
        
        return [];
    }
}
