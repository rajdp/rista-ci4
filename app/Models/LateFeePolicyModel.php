<?php

namespace App\Models;

use CodeIgniter\Model;

class LateFeePolicyModel extends Model
{
    protected $table = 't_late_fee_policy';
    protected $primaryKey = 'policy_id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'school_id',
        'flat_cents',
        'grace_days',
        'repeat_every_days',
        'apply_time',
    ];

    // Dates
    protected $useTimestamps = false;
    protected $createdField = 'created_at';

    // Validation
    protected $validationRules = [
        'school_id' => 'required|integer',
        'flat_cents' => 'permit_empty|integer',
        'grace_days' => 'permit_empty|integer',
    ];

    protected $skipValidation = false;

    /**
     * Get policy by school
     */
    public function getBySchool(int $schoolId): ?array
    {
        return $this->where('school_id', $schoolId)->first();
    }

    /**
     * Upsert policy for school
     */
    public function upsertPolicy(int $schoolId, array $data): bool
    {
        $existing = $this->getBySchool($schoolId);

        if ($existing) {
            return $this->update($existing['policy_id'], $data);
        }

        $data['school_id'] = $schoolId;
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->insert($data) !== false;
    }
}
