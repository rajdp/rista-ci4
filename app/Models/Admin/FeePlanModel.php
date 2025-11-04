<?php

namespace App\Models\Admin;

use CodeIgniter\Model;

class FeePlanModel extends Model
{
    protected $table = 'fee_plans';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'school_id',
        'name',
        'description',
        'plan_type',
        'amount',
        'billing_cycle_days',
        'auto_payment_enabled',
        'metadata',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function listForSchool(int $schoolId): array
    {
        return $this->where('school_id', $schoolId)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }
}
