<?php

namespace App\Services;

use App\Models\DepositLedgerModel;
use App\Models\BillingScheduleModel;

class DepositService
{
    protected $ledgerModel;
    protected $scheduleModel;

    public function __construct()
    {
        $this->ledgerModel = new DepositLedgerModel();
        $this->scheduleModel = new BillingScheduleModel();
    }

    /**
     * Record deposit charge
     *
     * @param int $schoolId
     * @param int $enrollmentId
     * @param int $amountCents
     * @param string|null $note
     * @return int|false Ledger entry ID or false
     */
    public function recordCharge(int $schoolId, int $enrollmentId, int $amountCents, ?string $note = null)
    {
        return $this->ledgerModel->recordCharge($schoolId, $enrollmentId, $amountCents, $note);
    }

    /**
     * Record deposit refund
     *
     * @param int $schoolId
     * @param int $enrollmentId
     * @param int $amountCents
     * @param string|null $note
     * @return int|false Ledger entry ID or false
     */
    public function recordRefund(int $schoolId, int $enrollmentId, int $amountCents, ?string $note = null)
    {
        return $this->ledgerModel->recordRefund($schoolId, $enrollmentId, $amountCents, $note);
    }

    /**
     * Apply deposit as credit (for non-refundable last-month deposits)
     *
     * @param int $schoolId
     * @param int $enrollmentId
     * @param int $amountCents
     * @param string|null $note
     * @return int|false Ledger entry ID or false
     */
    public function applyCredit(int $schoolId, int $enrollmentId, int $amountCents, ?string $note = null)
    {
        return $this->ledgerModel->recordCredit($schoolId, $enrollmentId, $amountCents, $note);
    }

    /**
     * Get current deposit balance
     *
     * @param int $enrollmentId
     * @return int Balance in cents
     */
    public function getBalance(int $enrollmentId): int
    {
        return $this->ledgerModel->getBalance($enrollmentId);
    }

    /**
     * Check if deposit should be applied as credit on final invoice
     *
     * @param int $enrollmentId
     * @return bool
     */
    public function shouldApplyCredit(int $enrollmentId): bool
    {
        $schedule = $this->scheduleModel->getByEnrollment($enrollmentId);
        if (!$schedule || $schedule['deposit_policy'] !== 'non_ref_last_month') {
            return false;
        }

        // Check if this is the final invoice (next_billing_date would be null after this)
        // This logic should be handled by the caller when creating the final invoice
        return true;
    }

    /**
     * Get deposit amount available for credit
     *
     * @param int $enrollmentId
     * @return int Amount in cents
     */
    public function getAvailableCredit(int $enrollmentId): int
    {
        $schedule = $this->scheduleModel->getByEnrollment($enrollmentId);
        if (!$schedule || $schedule['deposit_policy'] !== 'non_ref_last_month') {
            return 0;
        }

        // Return the original deposit amount (not the current balance)
        return (int)$schedule['deposit_cents'];
    }
}


