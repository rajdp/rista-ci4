<?php

namespace App\Controllers\Appt;

use CodeIgniter\HTTP\ResponseInterface;

class SettingsController extends ApptController
{
    public function policy(): ResponseInterface
    {
        $token = $this->validateToken();
        $payload = $this->request->getMethod() === 'post' ? $this->jsonPayload() : $this->request->getGet();
        $schoolId = $this->resolveSchoolId($token, $payload);

        if (!$schoolId) {
            return $this->errorResponse('school_id is required');
        }

        $service = service('apptpolicy');

        if ($this->request->getMethod() === 'post') {
            $token = $this->requireToken();
            if ($token instanceof ResponseInterface) {
                return $token;
            }

            $policy = $service->savePolicy($schoolId, $payload);
            return $this->successResponse($policy, 'Policy updated');
        }

        return $this->successResponse($service->getPolicy($schoolId));
    }
}
