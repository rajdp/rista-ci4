<?php

namespace App\Models;

use CodeIgniter\Model;

class PaymentTransactionModel extends Model
{
    protected $table = 'payment_transactions';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'school_id',
        'student_id',
        'payment_method_id',
        'provider_id',
        'transaction_type',
        'amount',
        'currency',
        'gateway_transaction_id',
        'gateway_response',
        'gateway_fee',
        'status',
        'failure_code',
        'failure_message',
        'retry_count',
        'invoice_id',
        'enrollment_id',
        'fee_id',
        'course_id',
        'description',
        'internal_notes',
        'metadata',
        'refunded_amount',
        'refund_reason',
        'parent_transaction_id',
        'receipt_url',
        'receipt_sent',
        'receipt_sent_at',
        'processed_by',
        'processed_by_ip'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'school_id' => 'required|integer',
        'student_id' => 'required|integer',
        'provider_id' => 'required|integer',
        'transaction_type' => 'required|in_list[charge,refund,authorization,capture,void]',
        'amount' => 'required|decimal',
        'status' => 'required|in_list[pending,processing,succeeded,failed,refunded,partially_refunded,cancelled,disputed]'
    ];

    /**
     * Get transactions for a student
     */
    public function getStudentTransactions(int $studentId, array $filters = []): array
    {
        $builder = $this->select('payment_transactions.*, providers.name as provider_name')
            ->join('providers', 'providers.id = payment_transactions.provider_id')
            ->where('payment_transactions.student_id', $studentId);

        $this->applyFilters($builder, $filters);

        return $builder->orderBy('payment_transactions.created_at', 'DESC')
            ->findAll($filters['limit'] ?? 100, $filters['offset'] ?? 0);
    }

    /**
     * Get transactions for a school
     */
    public function getSchoolTransactions(int $schoolId, array $filters = []): array
    {
        $builder = $this->select('payment_transactions.*, providers.name as provider_name')
            ->join('providers', 'providers.id = payment_transactions.provider_id')
            ->where('payment_transactions.school_id', $schoolId);

        $this->applyFilters($builder, $filters);

        return $builder->orderBy('payment_transactions.created_at', 'DESC')
            ->findAll($filters['limit'] ?? 100, $filters['offset'] ?? 0);
    }

    /**
     * Apply common filters to query builder
     */
    protected function applyFilters($builder, array $filters): void
    {
        if (!empty($filters['status'])) {
            $builder->where('payment_transactions.status', $filters['status']);
        }

        if (!empty($filters['transaction_type'])) {
            $builder->where('payment_transactions.transaction_type', $filters['transaction_type']);
        }

        if (!empty($filters['from_date'])) {
            $builder->where('payment_transactions.created_at >=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $builder->where('payment_transactions.created_at <=', $filters['to_date']);
        }

        if (!empty($filters['min_amount'])) {
            $builder->where('payment_transactions.amount >=', $filters['min_amount']);
        }

        if (!empty($filters['max_amount'])) {
            $builder->where('payment_transactions.amount <=', $filters['max_amount']);
        }

        if (!empty($filters['invoice_id'])) {
            $builder->where('payment_transactions.invoice_id', $filters['invoice_id']);
        }

        if (!empty($filters['enrollment_id'])) {
            $builder->where('payment_transactions.enrollment_id', $filters['enrollment_id']);
        }
    }

    /**
     * Get transaction by gateway transaction ID
     */
    public function getByGatewayId(string $gatewayTransactionId): ?array
    {
        return $this->where('gateway_transaction_id', $gatewayTransactionId)->first();
    }

    /**
     * Get refunds for a transaction
     */
    public function getRefunds(int $parentTransactionId): array
    {
        return $this->where('parent_transaction_id', $parentTransactionId)
            ->where('transaction_type', 'refund')
            ->findAll();
    }

    /**
     * Calculate total refunded amount for a transaction
     */
    public function getTotalRefunded(int $transactionId): float
    {
        $result = $this->selectSum('amount')
            ->where('parent_transaction_id', $transactionId)
            ->where('transaction_type', 'refund')
            ->where('status', 'succeeded')
            ->first();

        return (float)($result['amount'] ?? 0);
    }

    /**
     * Get transaction summary for school
     */
    public function getSchoolSummary(int $schoolId, string $period = 'month'): array
    {
        $startDate = match ($period) {
            'day' => date('Y-m-d'),
            'week' => date('Y-m-d', strtotime('-7 days')),
            'month' => date('Y-m-01'),
            'year' => date('Y-01-01'),
            default => date('Y-m-01')
        };

        return [
            'total_charges' => $this->selectSum('amount')
                ->where('school_id', $schoolId)
                ->where('transaction_type', 'charge')
                ->where('status', 'succeeded')
                ->where('created_at >=', $startDate)
                ->first()['amount'] ?? 0,

            'total_refunds' => $this->selectSum('amount')
                ->where('school_id', $schoolId)
                ->where('transaction_type', 'refund')
                ->where('status', 'succeeded')
                ->where('created_at >=', $startDate)
                ->first()['amount'] ?? 0,

            'transaction_count' => $this->where('school_id', $schoolId)
                ->where('transaction_type', 'charge')
                ->where('status', 'succeeded')
                ->where('created_at >=', $startDate)
                ->countAllResults(),

            'failed_count' => $this->where('school_id', $schoolId)
                ->where('status', 'failed')
                ->where('created_at >=', $startDate)
                ->countAllResults()
        ];
    }

    /**
     * Update transaction status
     */
    public function updateStatus(int $transactionId, string $status, ?string $message = null): bool
    {
        $data = ['status' => $status];
        if ($message) {
            $data['failure_message'] = $message;
        }
        return $this->update($transactionId, $data);
    }

    /**
     * Mark receipt as sent
     */
    public function markReceiptSent(int $transactionId): bool
    {
        return $this->update($transactionId, [
            'receipt_sent' => 1,
            'receipt_sent_at' => date('Y-m-d H:i:s')
        ]);
    }
}
