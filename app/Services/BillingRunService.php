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

    /**
     * Check and process manual billing for students with next_billing_date due
     * For students without automatic payment methods, create invoices if payment not received
     *
     * @param int $schoolId
     * @param string|null $runDate Date in Y-m-d format, defaults to today
     * @return array Summary of manual billing checks
     */
    public function checkManualBilling(int $schoolId, ?string $runDate = null): array
    {
        if ($runDate === null) {
            $runDate = date('Y-m-d');
        }

        $summary = [
            'run_date' => $runDate,
            'checked' => 0,
            'invoices_created' => 0,
            'already_paid' => 0,
            'errors' => [],
        ];

        // Get students with next_billing_date <= runDate
        $builder = $this->db->table('user_profile_details upd');
        $builder->select('upd.user_id as student_id, upd.school_id, upd.next_billing_date,
                         u.email_id, up.first_name, up.last_name');
        $builder->join('user u', 'u.user_id = upd.user_id', 'left');
        $builder->join('user_profile up', 'u.user_id = up.user_id', 'left');
        $builder->where('upd.school_id', $schoolId);
        $builder->where('upd.next_billing_date <=', $runDate);
        $builder->where('upd.next_billing_date IS NOT NULL');
        $builder->where('u.role_id', 5); // Students only
        $builder->where('upd.status', 1); // Active students only

        $students = $builder->get()->getResultArray();

        foreach ($students as $student) {
            $summary['checked']++;
            $this->db->transStart();

            try {
                $studentId = $student['student_id'];
                $nextBillingDate = $student['next_billing_date'];

                // Check if student has automatic payment method
                $hasAutoPayment = $this->paymentMethodModel
                    ->where('student_id', $studentId)
                    ->where('school_id', $schoolId)
                    ->where('is_active', 1)
                    ->where('is_default', 1)
                    ->countAllResults() > 0;

                // Skip if student has automatic payment (handled by runDailyBilling)
                if ($hasAutoPayment) {
                    $this->db->transComplete();
                    continue;
                }

                // Check if there's already an invoice for this billing date
                $existingInvoice = $this->db->table('invoices')
                    ->where('student_id', $studentId)
                    ->where('school_id', $schoolId)
                    ->where('due_date', $nextBillingDate)
                    ->where('status !=', 'void')
                    ->get()
                    ->getRowArray();

                if ($existingInvoice) {
                    // Check if invoice is already paid
                    if ($existingInvoice['status'] === 'paid' || 
                        ($existingInvoice['amount_paid'] ?? 0) >= ($existingInvoice['amount_due'] ?? 0)) {
                        $summary['already_paid']++;
                        $this->db->transComplete();
                        continue;
                    }
                    // Invoice exists but not paid - already flagged as pending
                    $this->db->transComplete();
                    continue;
                }

                // Check if payment has been received for this billing period
                // Look for payments made on or after the next_billing_date
                $paymentReceived = $this->db->table('payments p')
                    ->join('invoices i', 'i.id = p.invoice_id', 'left')
                    ->where('i.student_id', $studentId)
                    ->where('i.school_id', $schoolId)
                    ->where('p.status', 'captured')
                    ->where('p.created_at >=', $nextBillingDate . ' 00:00:00')
                    ->countAllResults() > 0;

                if ($paymentReceived) {
                    // Payment received, no need to create invoice
                    $summary['already_paid']++;
                    $this->db->transComplete();
                    continue;
                }

                // No invoice exists and payment not received - create invoice and flag as pending
                // For manual billing, we need to create a basic invoice
                // This would typically link to a billing schedule or fee plan
                // For now, create a placeholder invoice that can be updated later

                $invoiceNumber = 'INV-MANUAL-' . date('Ymd') . '-' . $studentId . '-' . rand(1000, 9999);
                
                $invoiceData = [
                    'school_id' => $schoolId,
                    'student_id' => $studentId,
                    'enrollment_id' => null, // Will be set if linked to enrollment
                    'student_fee_plan_id' => null,
                    'due_date' => $nextBillingDate,
                    'amount_due' => 0, // Will be updated when fee plan is assigned
                    'total_cents' => 0,
                    'amount_paid' => 0,
                    'status' => 'pending', // Flag as payment pending
                    'invoice_number' => $invoiceNumber,
                    'issued_at' => date('Y-m-d H:i:s'),
                ];

                $invoiceId = $this->db->table('invoices')->insert($invoiceData);

                // Send invoice email notification
                try {
                    $this->sendManualBillingInvoiceEmail($invoiceId, $student);
                } catch (\Exception $emailError) {
                    log_message('warning', 'Failed to send manual billing invoice email: ' . $emailError->getMessage());
                }

                $summary['invoices_created']++;
                $this->db->transComplete();

            } catch (\Exception $e) {
                $this->db->transRollback();
                $summary['errors'][] = [
                    'student_id' => $student['student_id'] ?? null,
                    'error' => $e->getMessage(),
                ];
                log_message('error', 'BillingRunService::checkManualBilling failed for student: ' . ($student['student_id'] ?? 'unknown') . ' - ' . $e->getMessage());
            }
        }

        return $summary;
    }

    /**
     * Send invoice email for manual billing
     *
     * @param int $invoiceId
     * @param array $student
     * @return bool
     */
    protected function sendManualBillingInvoiceEmail(int $invoiceId, array $student): bool
    {
        try {
            $invoice = $this->db->table('invoices')
                ->where('id', $invoiceId)
                ->get()
                ->getRowArray();

            if (!$invoice || empty($student['email_id'])) {
                return false;
            }

            $email = \Config\Services::email();
            $view = \Config\Services::renderer();

            $email->setTo($student['email_id']);
            $email->setSubject('Payment Pending - Invoice #' . $invoice['invoice_number']);
            $email->setMessage($view->setData([
                'invoice' => $invoice,
                'student' => $student,
                'paymentLink' => base_url('invoices/' . $invoiceId . '/pay')
            ])->render('emails/payment_pending'));

            return $email->send();
        } catch (\Exception $e) {
            log_message('error', 'Failed to send manual billing invoice email: ' . $e->getMessage());
            return false;
        }
    }
}


