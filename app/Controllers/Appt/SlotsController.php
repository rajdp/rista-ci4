<?php

namespace App\Controllers\Appt;

use App\Models\Appt\AvailabilityModel;
use App\Services\Appt\PolicyService;
use CodeIgniter\HTTP\ResponseInterface;

class SlotsController extends ApptController
{
    protected AvailabilityModel $availabilityModel;
    protected PolicyService $policyService;

    public function __construct()
    {
        parent::__construct();
        $this->availabilityModel = new AvailabilityModel();
        $this->policyService = service('apptpolicy');
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

        $from = $params['from'] ?? date(DATE_ATOM);
        $to = $params['to'] ?? date(DATE_ATOM, strtotime('+14 days'));
        $duration = (int) ($params['duration_min'] ?? 30);

        try {
            $slotGenerator = service('slotgenerator');
            $policy = $this->policyService->getPolicy($schoolId);
            $slots = $slotGenerator->generate(
                $schoolId,
                $adminUserId,
                new \DateTimeImmutable($from),
                new \DateTimeImmutable($to),
                $duration,
                $policy
            );
        } catch (\Throwable $e) {
            log_message('error', 'Slot generation failed: ' . $e->getMessage());
            return $this->errorResponse('Unable to generate slots', 500);
        }

        return $this->successResponse($slots);
    }
}
