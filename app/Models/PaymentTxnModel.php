<?php

namespace App\Models;

use CodeIgniter\Model;

class PaymentTxnModel extends Model
{
    protected $table = 't_payment_txn';
    protected $primaryKey = 'txn_id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'school_id',
        'invoice_id',
        'amount_cents',
        'status',
        'provider',
        'provider_txn_id',
        'error_code',
        'error_msg',
        'idempotency_key',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Validation
    protected $validationRules = [
        'school_id' => 'required|integer',
        'invoice_id' => 'required|integer',
        'amount_cents' => 'required|integer',
        'status' => 'required|in_list[initiated,succeeded,failed,refunded,voided]',
        'provider' => 'required|max_length[32]',
    ];

    protected $skipValidation = false;

    /**
     * Get payments by invoice
     */
    public function getByInvoice(int $invoiceId): array
    {
        return $this->where('invoice_id', $invoiceId)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    /**
     * Get succeeded payments by invoice
     */
    public function getSucceededByInvoice(int $invoiceId): array
    {
        return $this->where('invoice_id', $invoiceId)
            ->where('status', 'succeeded')
            ->findAll();
    }

    /**
     * Check if idempotency key exists
     */
    public function findByIdempotencyKey(string $key): ?array
    {
        return $this->where('idempotency_key', $key)->first();
    }

    /**
     * Get total paid amount for invoice
     */
    public function getTotalPaidForInvoice(int $invoiceId): int
    {
        $result = $this->select('SUM(amount_cents) as total')
            ->where('invoice_id', $invoiceId)
            ->where('status', 'succeeded')
            ->first();

        return $result['total'] ?? 0;
    }

    /**
     * Get failed payments for school
     */
    public function getFailedBySchool(int $schoolId, int $days = 30): array
    {
        $date = date('Y-m-d', strtotime("-{$days} days"));

        return $this->where('school_id', $schoolId)
            ->where('status', 'failed')
            ->where('created_at >=', $date)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    /**
     * Get payment statistics
     */
    public function getStats(int $schoolId, string $fromDate, string $toDate): array
    {
        $results = $this->select('status, COUNT(*) as count, SUM(amount_cents) as total_amount')
            ->where('school_id', $schoolId)
            ->where('created_at >=', $fromDate)
            ->where('created_at <=', $toDate)
            ->groupBy('status')
            ->findAll();

        $stats = [
            'total_transactions' => 0,
            'succeeded_count' => 0,
            'failed_count' => 0,
            'total_collected_cents' => 0,
            'success_rate' => 0,
        ];

        foreach ($results as $row) {
            $stats['total_transactions'] += $row['count'];

            if ($row['status'] === 'succeeded') {
                $stats['succeeded_count'] = $row['count'];
                $stats['total_collected_cents'] = $row['total_amount'] ?? 0;
            } elseif ($row['status'] === 'failed') {
                $stats['failed_count'] = $row['count'];
            }
        }

        if ($stats['total_transactions'] > 0) {
            $stats['success_rate'] = round(($stats['succeeded_count'] / $stats['total_transactions']) * 100, 2);
        }

        return $stats;
    }
}
