<?php

namespace App\Services;

use App\Models\BillingScheduleModel;
use App\Models\BillingRunModel;
use App\Models\BillingRunItemModel;
use App\Models\StudentPaymentMethodModel;
use App\Services\InvoiceBuilder;
use App\Services\PaymentService;

class BillingRunService
{
    protected $scheduleModel;
    protected $runModel;
    protected $runItemModel;
    protected $paymentMethodModel;
    protected $invoiceBuilder;
    protected $paymentService;
    protected $db;

    public function __construct()
    {
        $this->scheduleModel = new BillingScheduleModel();
        $this->runModel = new BillingRunModel();
        $this->runItemModel = new BillingRunItemModel();
        $this->paymentMethodModel = new StudentPaymentMethodModel();
        $this->invoiceBuilder = new InvoiceBuilder();
        $this->paymentService = new PaymentService();
        $this->db = \Config\Database::connect();
    }

    /**
     * Run daily billing for a school
     *
     * @param int $schoolId
     * @param string|null $runDate Date in Y-m-d format, defaults to today
     * @param string|null $claimedBy Process identifier
     * @return array Summary of run results
     */
    public function runDailyBilling(int $schoolId, ?string $runDate = null, ?string $claimedBy = null): array
    {
        if ($runDate === null) {
            $runDate = date('Y-m-d');
        }

        if ($claimedBy === null) {
            $claimedBy = gethostname() . '-' . getmypid();
        }

        // Create or get run record (idempotency)
        $run = $this->runModel->createOrGetRun($schoolId, $runDate, $claimedBy);
        $runId = $run['run_id'];

        // Get due schedules
        $dueSchedules = $this->scheduleModel->getDueSchedules($schoolId, $runDate);

        $summary = [
            'run_id' => $runId,
            'run_date' => $runDate,
            'total_schedules' => count($dueSchedules),
            'processed' => 0,
            'invoiced' => 0,
            'charged' => 0,
            'skipped' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        // Process each schedule
        foreach ($dueSchedules as $schedule) {
            $this->db->transStart();

            try {
                // Check idempotency: has this schedule already been processed in this run?
                $existingItem = $this->runItemModel->getRunItem($runId, $schedule['schedule_id']);
                if ($existingItem) {
                    // Already processed, skip
                    $summary['skipped']++;
                    $this->db->transComplete();
                    continue;
                }

                // Build invoice
                $invoiceResult = $this->invoiceBuilder->buildInvoice($schedule['schedule_id']);
                
                if (!$invoiceResult['success']) {
                    throw new \Exception($invoiceResult['message'] ?? 'Failed to build invoice');
                }

                $invoiceId = $invoiceResult['invoice_id'];
                $invoiceAmount = $invoiceResult['total_cents'] / 100.0; // Convert to dollars

                // Attempt charge if payment method exists
                $charged = false;
                $paymentMethod = $this->paymentMethodModel
                    ->where('student_id', $schedule['student_id'])
                    ->where('school_id', $schoolId)
                    ->where('is_active', 1)
                    ->where('is_default', 1)
                    ->first();

                if ($paymentMethod) {
                    $chargeResult = $this->paymentService->chargePaymentMethod(
                        $paymentMethod['id'],
                        $invoiceAmount,
                        [
                            'description' => 'Invoice #' . $invoiceResult['invoice_id'],
                            'metadata' => [
                                'invoice_id' => $invoiceId,
                                'enrollment_id' => $schedule['enrollment_id'],
                                'schedule_id' => $schedule['schedule_id'],
                            ]
                        ]
                    );

                    if ($chargeResult['success']) {
                        // Update invoice status to paid
                        $this->db->table('invoices')
                            ->where('id', $invoiceId)
                            ->update([
                                'status' => 'paid',
                                'amount_paid' => $invoiceAmount,
                            ]);
                        $charged = true;
                        $summary['charged']++;
                    } else {
                        // Mark invoice as failed
                        $this->db->table('invoices')
                            ->where('id', $invoiceId)
                            ->update(['status' => 'failed']);
                    }
                }

                // Advance next_billing_date (except for one_time)
                if ($schedule['term'] !== 'one_time') {
                    $this->scheduleModel->advanceNextBillingDate($schedule['schedule_id'], $schedule['term']);
                } else {
                    // One-time: set next_billing_date to NULL
                    $this->scheduleModel->update($schedule['schedule_id'], ['next_billing_date' => null]);
                }

                // Record run item
                $this->runItemModel->createRunItem(
                    $runId,
                    $schedule['schedule_id'],
                    'invoiced',
                    $invoiceId,
                    $charged ? 'Invoice created and charged successfully' : 'Invoice created, no payment method'
                );

                $summary['invoiced']++;
                $summary['processed']++;

                $this->db->transComplete();
            } catch (\Exception $e) {
                $this->db->transRollback();
                
                // Record failed run item
                try {
                    $this->runItemModel->createRunItem(
                        $runId,
                        $schedule['schedule_id'],
                        'failed',
                        null,
                        $e->getMessage()
                    );
                } catch (\Exception $itemError) {
                    log_message('error', 'Failed to record run item: ' . $itemError->getMessage());
                }

                $summary['failed']++;
                $summary['errors'][] = [
                    'schedule_id' => $schedule['schedule_id'],
                    'error' => $e->getMessage(),
                ];

                log_message('error', 'BillingRunService: Failed to process schedule ' . $schedule['schedule_id'] . ': ' . $e->getMessage());
            }
        }

        return $summary;
    }
}


