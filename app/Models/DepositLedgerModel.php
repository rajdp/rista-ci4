<?php

namespace App\Models;

use CodeIgniter\Model;

class DepositLedgerModel extends Model
{
    protected $table = 't_deposit_ledger';
    protected $primaryKey = 'deposit_id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'school_id',
        'enrollment_id',
        'amount_cents',
        'direction',
        'note',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = null; // No updated_at field

    /**
     * Record a deposit charge
     *
     * @param int $schoolId
     * @param int $enrollmentId
     * @param int $amountCents
     * @param string|null $note
     * @return int|false Insert ID or false on failure
     */
    public function recordCharge(int $schoolId, int $enrollmentId, int $amountCents, ?string $note = null)
    {
        return $this->insert([
            'school_id' => $schoolId,
            'enrollment_id' => $enrollmentId,
            'amount_cents' => $amountCents,
            'direction' => 'charge',
            'note' => $note,
        ]);
    }

    /**
     * Record a deposit refund
     *
     * @param int $schoolId
     * @param int $enrollmentId
     * @param int $amountCents
     * @param string|null $note
     * @return int|false Insert ID or false on failure
     */
    public function recordRefund(int $schoolId, int $enrollmentId, int $amountCents, ?string $note = null)
    {
        return $this->insert([
            'school_id' => $schoolId,
            'enrollment_id' => $enrollmentId,
            'amount_cents' => -abs($amountCents), // Negative for refund
            'direction' => 'refund',
            'note' => $note,
        ]);
    }

    /**
     * Record applying deposit as credit
     *
     * @param int $schoolId
     * @param int $enrollmentId
     * @param int $amountCents
     * @param string|null $note
     * @return int|false Insert ID or false on failure
     */
    public function recordCredit(int $schoolId, int $enrollmentId, int $amountCents, ?string $note = null)
    {
        return $this->insert([
            'school_id' => $schoolId,
            'enrollment_id' => $enrollmentId,
            'amount_cents' => -abs($amountCents), // Negative for credit application
            'direction' => 'apply_credit',
            'note' => $note,
        ]);
    }

    /**
     * Get current deposit balance for an enrollment
     *
     * @param int $enrollmentId
     * @return int Balance in cents (can be negative)
     */
    public function getBalance(int $enrollmentId): int
    {
        $result = $this->selectSum('amount_cents')
            ->where('enrollment_id', $enrollmentId)
            ->first();

        return (int)($result['amount_cents'] ?? 0);
    }

    /**
     * Get all ledger entries for an enrollment
     *
     * @param int $enrollmentId
     * @return array
     */
    public function getLedgerEntries(int $enrollmentId): array
    {
        return $this->where('enrollment_id', $enrollmentId)
            ->orderBy('created_at', 'ASC')
            ->findAll();
    }
}


