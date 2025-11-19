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
        return $this->request->getJSON(true) ?? $this->request->getPost() ?? [];
    }
}
