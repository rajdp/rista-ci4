<?php

namespace App\Services\Billing;

use App\Models\BillingInvoiceModel;
use App\Models\InvoiceItemModel;
use App\Models\LateFeePolicyModel;
use App\Services\Billing\AuditService;

class LateFeeService
{
    protected $invoiceModel;
    protected $invoiceItemModel;
    protected $policyModel;
    protected $auditService;

    public function __construct()
    {
        $this->invoiceModel = new BillingInvoiceModel();
        $this->invoiceItemModel = new InvoiceItemModel();
        $this->policyModel = new LateFeePolicyModel();
        $this->auditService = new AuditService();
    }

    /**
     * Apply late fees for a school (called by cron)
     */
    public function applyLateFees(int $schoolId, string $runDate): array
    {
        try {
            // Get school's late fee policy
            $policy = $this->policyModel->getBySchool($schoolId);

            if (!$policy || $policy['flat_cents'] <= 0) {
                // No late fee policy or zero fee
                return ['success' => true, 'data' => ['applied' => 0], 'error' => null];
            }

            $graceDays = $policy['grace_days'] ?? 0;
            $graceEndDate = date('Y-m-d', strtotime($runDate . " -{$graceDays} days"));

            // Get overdue invoices
            $invoices = $this->invoiceModel->getOverdue($schoolId, $graceEndDate);

            $applied = 0;
            $errors = [];

            foreach ($invoices as $invoice) {
                // Check if late fee already applied
                $existingLateFee = $this->invoiceItemModel
                    ->where('invoice_id', $invoice['invoice_id'])
                    ->where('type', 'late_fee')
                    ->first();

                if ($existingLateFee) {
                    // Check if repeat policy
                    if ($policy['repeat_every_days']) {
                        $lastApplied = strtotime($existingLateFee['created_at'] ?? $invoice['updated_at']);
                        $daysSinceLastFee = floor((time() - $lastApplied) / 86400);

                        if ($daysSinceLastFee < $policy['repeat_every_days']) {
                            continue; // Not time for repeat yet
                        }
                    } else {
                        continue; // Late fee already applied once
                    }
                }

                // Apply late fee
                $result = $this->applyLateFeeToInvoice($invoice['invoice_id'], $policy['flat_cents'], $schoolId);

                if ($result['success']) {
                    $applied++;
                } else {
                    $errors[] = "Invoice {$invoice['invoice_id']}: {$result['error']}";
                }
            }

            return [
                'success' => true,
                'data' => [
                    'applied' => $applied,
                    'errors' => $errors
                ],
                'error' => null
            ];

        } catch (\Exception $e) {
            log_message('error', 'LateFeeService::applyLateFees error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage(), 'data' => null];
        }
    }

    /**
     * Apply late fee to specific invoice
     */
    public function applyLateFeeToInvoice(int $invoiceId, int $lateFeeAmountCents, int $schoolId): array
    {
        try {
            $invoice = $this->invoiceModel->find($invoiceId);
            if (!$invoice) {
                return ['success' => false, 'error' => 'Invoice not found', 'data' => null];
            }

            // Add late fee line item
            $itemData = [
                'invoice_id' => $invoiceId,
                'description' => 'Late Fee',
                'qty' => 1,
                'unit_price_cents' => $lateFeeAmountCents,
                'line_total_cents' => $lateFeeAmountCents,
                'type' => 'late_fee',
            ];

            $itemId = $this->invoiceItemModel->insert($itemData);

            if (!$itemId) {
                return ['success' => false, 'error' => 'Failed to add late fee item', 'data' => null];
            }

            // Update invoice totals
            $updated = $this->invoiceModel->addLateFee($invoiceId, $lateFeeAmountCents);

            if (!$updated) {
                return ['success' => false, 'error' => 'Failed to update invoice totals', 'data' => null];
            }

            // Audit log
            $this->auditService->log(
                $schoolId,
                'invoice',
                $invoiceId,
                'late_fee_applied',
                null,
                'system',
                ['amount_cents' => $lateFeeAmountCents]
            );

            return ['success' => true, 'data' => ['late_fee_applied' => $lateFeeAmountCents], 'error' => null];

        } catch (\Exception $e) {
            log_message('error', 'LateFeeService::applyLateFeeToInvoice error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage(), 'data' => null];
        }
    }
}
