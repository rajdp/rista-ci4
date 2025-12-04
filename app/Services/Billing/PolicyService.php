<?php

namespace App\Services\Billing;

use App\Models\LateFeePolicyModel;
use App\Models\DunningStepModel;

class PolicyService
{
    protected $lateFeePolicyModel;
    protected $dunningStepModel;

    public function __construct()
    {
        $this->lateFeePolicyModel = new LateFeePolicyModel();
        $this->dunningStepModel = new DunningStepModel();
    }

    /**
     * Get late fee policy for school
     */
    public function getLateFeePolicy(int $schoolId): array
    {
        try {
            $policy = $this->lateFeePolicyModel->getBySchool($schoolId);

            if (!$policy) {
                // Return default policy
                $policy = [
                    'school_id' => $schoolId,
                    'flat_cents' => 0,
                    'grace_days' => 5,
                    'repeat_every_days' => null,
                    'apply_time' => '03:00:00',
                ];
            }

            return ['success' => true, 'data' => $policy, 'error' => null];

        } catch (\Exception $e) {
            log_message('error', 'PolicyService::getLateFeePolicy error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage(), 'data' => null];
        }
    }

    /**
     * Update late fee policy
     */
    public function updateLateFeePolicy(int $schoolId, array $data): array
    {
        try {
            $updated = $this->lateFeePolicyModel->upsertPolicy($schoolId, $data);

            if (!$updated) {
                return ['success' => false, 'error' => 'Failed to update policy', 'data' => null];
            }

            $policy = $this->lateFeePolicyModel->getBySchool($schoolId);

            return ['success' => true, 'data' => $policy, 'error' => null];

        } catch (\Exception $e) {
            log_message('error', 'PolicyService::updateLateFeePolicy error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage(), 'data' => null];
        }
    }

    /**
     * Get dunning steps for school
     */
    public function getDunningSteps(int $schoolId): array
    {
        try {
            $steps = $this->dunningStepModel->getBySchool($schoolId);

            return ['success' => true, 'data' => $steps, 'error' => null];

        } catch (\Exception $e) {
            log_message('error', 'PolicyService::getDunningSteps error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage(), 'data' => null];
        }
    }

    /**
     * Update dunning steps for school
     */
    public function updateDunningSteps(int $schoolId, array $steps): array
    {
        try {
            $updated = $this->dunningStepModel->replaceSteps($schoolId, $steps);

            if (!$updated) {
                return ['success' => false, 'error' => 'Failed to update dunning steps', 'data' => null];
            }

            $newSteps = $this->dunningStepModel->getBySchool($schoolId);

            return ['success' => true, 'data' => $newSteps, 'error' => null];

        } catch (\Exception $e) {
            log_message('error', 'PolicyService::updateDunningSteps error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage(), 'data' => null];
        }
    }
}
