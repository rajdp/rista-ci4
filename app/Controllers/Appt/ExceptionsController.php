<?php

namespace App\Controllers\Appt;

use App\Models\Appt\ExceptionModel;
use CodeIgniter\HTTP\ResponseInterface;

class ExceptionsController extends ApptController
{
    protected ExceptionModel $exceptionModel;

    public function __construct()
    {
        parent::__construct();
        $this->exceptionModel = new ExceptionModel();
    }

    public function index(): ResponseInterface
    {
        $params = $this->request->getGet();
        $token = $this->validateToken();
        $schoolId = $this->resolveSchoolId($token, $params);
        $adminUserId = (int) ($params['admin_user_id'] ?? 0);

        if (!$schoolId || !$adminUserId) {
            return $this->errorResponse('school_id and admin_user_id are required');
        }

        $from = $params['from'] ?? date('Y-m-d');
        $to = $params['to'] ?? date('Y-m-d', strtotime('+30 days'));

        $items = $this->exceptionModel->getInRange($schoolId, $adminUserId, $from, $to);

        return $this->successResponse($items);
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

        if (!$schoolId || !$adminUserId) {
            return $this->errorResponse('admin_user_id and school_id are required');
        }

        $data = [
            'school_id' => $schoolId,
            'admin_user_id' => $adminUserId,
            'date' => $payload['date'] ?? date('Y-m-d'),
            'start_time' => $payload['start_time'] ?? '00:00:00',
            'end_time' => $payload['end_time'] ?? '23:59:59',
            'type' => $payload['type'] ?? 'closed',
            'reason' => $payload['reason'] ?? null,
        ];

        $id = $this->exceptionModel->insert($data, true);

        return $this->successResponse(['exception_id' => $id], 'Exception saved');
    }
}
