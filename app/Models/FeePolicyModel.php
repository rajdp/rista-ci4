<?php

namespace App\Models;

use CodeIgniter\Model;

class FeePolicyModel extends Model
{
    protected $table = 't_fee_policy';
    protected $primaryKey = 'policy_id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'school_id',
        'registration_fee_cents',
        'material_fee_cents',
        'other_fee_cents',
        'effective_date',
        'is_active',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Get active fee policy for a school
     *
     * @param int $schoolId
     * @param string|null $date Date to check effective date, defaults to today
     * @return array|null
     */
    public function getActivePolicy(int $schoolId, ?string $date = null): ?array
    {
        if ($date === null) {
            $date = date('Y-m-d');
        }

        return $this->where('school_id', $schoolId)
            ->where('is_active', 1)
            ->where('effective_date <=', $date)
            ->orderBy('effective_date', 'DESC')
            ->first();
    }

    /**
     * Get total onboarding fees in cents
     *
     * @param int $schoolId
     * @param string|null $date
     * @return int
     */
    public function getTotalOnboardingFees(int $schoolId, ?string $date = null): int
    {
        $policy = $this->getActivePolicy($schoolId, $date);
        if (!$policy) {
            return 0;
        }

        return (int)$policy['registration_fee_cents'] 
            + (int)$policy['material_fee_cents'] 
            + (int)$policy['other_fee_cents'];
    }
}


