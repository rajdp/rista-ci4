<?php

namespace App\Controllers\Appt;

use App\Models\Appt\AvailabilityModel;
use CodeIgniter\HTTP\ResponseInterface;

class AvailabilityController extends ApptController
{
    protected AvailabilityModel $availabilityModel;

    public function __construct()
    {
        parent::__construct();
        $this->availabilityModel = new AvailabilityModel();
    }

    public function index(): ResponseInterface
    {
        $payload = $this->request->getGet();
        $token = $this->validateToken();
        $schoolId = $this->resolveSchoolId($token, $payload);
        $adminUserId = (int) ($payload['admin_user_id'] ?? 0);

        if (!$schoolId || !$adminUserId) {
            return $this->errorResponse('school_id and admin_user_id are required');
        }

        $windows = $this->availabilityModel->where([
            'school_id' => $schoolId,
            'admin_user_id' => $adminUserId,
        ])->orderBy('dow ASC, start_time ASC')->findAll();

        return $this->successResponse(['windows' => $windows]);
    }

    public function save(): ResponseInterface
    {
        $token = $this->requireToken();
        if ($token instanceof ResponseInterface) {
            return $token;
        }

        $payload = $this->jsonPayload();
        $schoolId = $this->resolveSchoolId($token, $payload);
        $adminUserId = (int) ($payload['admin_user_id'] ?? $this->getUserId($token));
        $windows = $payload['windows'] ?? [];

        if (!$schoolId || !$adminUserId) {
            return $this->errorResponse('admin_user_id and school_id are required');
        }

        if (!is_array($windows) || empty($windows)) {
            return $this->errorResponse('windows payload is required');
        }

        $batch = [];
        foreach ($windows as $window) {
            $batch[] = [
                'school_id' => $schoolId,
                'admin_user_id' => $adminUserId,
                'dow' => (int) ($window['dow'] ?? 0),
                'start_time' => $window['start_time'] ?? '09:00:00',
                'end_time' => $window['end_time'] ?? '17:00:00',
                'slot_duration_min' => (int) ($window['slot_duration_min'] ?? 30),
                'is_active' => (int) ($window['is_active'] ?? 1),
            ];
        }

        $db = $this->availabilityModel->db;
        $db->transStart();
        $this->availabilityModel->where('school_id', $schoolId)->where('admin_user_id', $adminUserId)->delete();
        $this->availabilityModel->insertBatch($batch);
        $db->transComplete();

        return $this->successResponse(['updated' => count($batch)], 'Availability updated');
    }
}
