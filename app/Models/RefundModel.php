<?php

namespace App\Models;

use CodeIgniter\Model;

class RefundModel extends Model
{
    protected $table = 't_refund';
    protected $primaryKey = 'refund_id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'school_id',
        'txn_id',
        'amount_cents',
        'provider',
        'provider_refund_id',
        'reason',
    ];

    // Dates
    protected $useTimestamps = false;
    protected $createdField = 'created_at';

    // Validation
    protected $validationRules = [
        'school_id' => 'required|integer',
        'txn_id' => 'required|integer',
        'amount_cents' => 'required|integer',
        'provider' => 'required|max_length[32]',
    ];

    protected $skipValidation = false;

    /**
     * Get refunds by transaction
     */
    public function getByTransaction(int $txnId): array
    {
        return $this->where('txn_id', $txnId)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    /**
     * Get total refunded for transaction
     */
    public function getTotalRefundedForTransaction(int $txnId): int
    {
        $result = $this->select('SUM(amount_cents) as total')
            ->where('txn_id', $txnId)
            ->first();

        return $result['total'] ?? 0;
    }

    /**
     * Get refunds by school with date range
     */
    public function getBySchool(int $schoolId, string $fromDate, string $toDate): array
    {
        return $this->where('school_id', $schoolId)
            ->where('created_at >=', $fromDate)
            ->where('created_at <=', $toDate)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }
}
