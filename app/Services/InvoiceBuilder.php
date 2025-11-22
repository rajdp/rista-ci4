<?php

namespace App\Services;

use App\Models\Admin\InvoiceModel;
use App\Models\InvoiceItemModel;
use App\Models\BillingScheduleModel;
use App\Models\FeePolicyModel;
use App\Models\CourseFeePlanModel;
use App\Services\ProrationService;
use App\Services\DepositService;
use App\Libraries\SimplePdfGenerator;

class InvoiceBuilder
{
    protected $invoiceModel;
    protected $invoiceItemModel;
    protected $scheduleModel;
    protected $feePolicyModel;
    protected $courseFeePlanModel;
    protected $prorationService;
    protected $depositService;
    protected $db;

    public function __construct()
    {
        $this->invoiceModel = new InvoiceModel();
        $this->invoiceItemModel = new InvoiceItemModel();
        $this->scheduleModel = new BillingScheduleModel();
        $this->feePolicyModel = new FeePolicyModel();
        $this->courseFeePlanModel = new CourseFeePlanModel();
        $this->prorationService = new ProrationService();
        $this->depositService = new DepositService();
        $this->db = \Config\Database::connect();
    }

    /**
     * Build and create invoice for a billing schedule
     *
     * @param int $scheduleId
     * @param array $options Additional options
     * @return array Result with success status and invoice_id
     */
    public function buildInvoice(int $scheduleId, array $options = []): array
    {
        $this->db->transStart();

        try {
            // Get schedule
            $schedule = $this->scheduleModel->find($scheduleId);
            if (!$schedule) {
                throw new \Exception('Billing schedule not found');
            }

            if ($schedule['status'] !== 'active') {
                throw new \Exception('Schedule is not active');
            }

            // Check if invoice already exists for this due date
            $existingInvoice = $this->checkExistingInvoice($schedule);
            if ($existingInvoice) {
                return [
                    'success' => true,
                    'invoice_id' => $existingInvoice['id'],
                    'message' => 'Invoice already exists for this billing date'
                ];
            }

            // Get course fee info
            $courseFee = $this->courseFeePlanModel->getFeeForCourse($schedule['course_id'], $schedule['school_id']);
            if (!$courseFee) {
                throw new \Exception('Course fee information not found');
            }

            // Convert fee to cents
            $feeCents = (int)round(($courseFee['default_amount'] ?? 0) * 100);

            // Build line items
            $lineItems = [];
            $totalCents = 0;

            // 1. Proration (if first invoice and needed)
            if ($schedule['has_prorated_first'] == 0 && $schedule['term'] !== 'one_time' && $schedule['next_billing_date']) {
                // Get enrollment start date (would need to fetch from student_courses)
                $enrollment = $this->db->table('student_courses')
                    ->where('id', $schedule['enrollment_id'])
                    ->first();
                
                if ($enrollment && $enrollment['enrollment_date']) {
                    $proratedCents = $this->prorationService->calculateProration(
                        $schedule['term'],
                        $feeCents,
                        $enrollment['enrollment_date'],
                        $schedule['next_billing_date']
                    );

                    if ($proratedCents > 0) {
                        $lineItems[] = [
                            'description' => 'Prorated ' . ucfirst($schedule['term']) . ' Fee',
                            'quantity' => 1,
                            'unit_cents' => $proratedCents,
                            'total_cents' => $proratedCents,
                            'kind' => 'proration',
                            'course_id' => $schedule['course_id'],
                            'enrollment_id' => $schedule['enrollment_id'],
                        ];
                        $totalCents += $proratedCents;
                    }
                }
            }

            // 2. Recurring fee (if not one-time or if one-time, this is the only charge)
            if ($schedule['term'] === 'one_time' || $schedule['has_prorated_first'] == 1) {
                $lineItems[] = [
                    'description' => ucfirst($schedule['term']) . ' Fee - ' . ($courseFee['course_name'] ?? 'Course'),
                    'quantity' => 1,
                    'unit_cents' => $feeCents,
                    'total_cents' => $feeCents,
                    'kind' => 'recurring',
                    'course_id' => $schedule['course_id'],
                    'enrollment_id' => $schedule['enrollment_id'],
                ];
                $totalCents += $feeCents;
            }

            // 3. Student custom items (promos, discounts, additional charges)
            $customItemModel = new \App\Models\StudentCustomItemModel();
            $dueDate = $schedule['next_billing_date'] ?? date('Y-m-d');
            $customItems = $customItemModel->getActiveItemsForStudent($schedule['student_id'], $schedule['school_id'], $dueDate);
            
            foreach ($customItems as $customItem) {
                $itemCents = (int)round((float)$customItem['amount'] * 100);
                $lineItems[] = [
                    'description' => $customItem['description'],
                    'quantity' => 1,
                    'unit_cents' => $itemCents,
                    'total_cents' => $itemCents,
                    'kind' => $itemCents >= 0 ? 'credit' : 'credit', // Use 'credit' kind for custom items (can be positive or negative)
                    'course_id' => null,
                    'enrollment_id' => null,
                ];
                $totalCents += $itemCents;
            }

            // 3. Deposit (if first invoice and deposit exists)
            if ($schedule['deposit_cents'] > 0 && $schedule['has_prorated_first'] == 0) {
                $lineItems[] = [
                    'description' => 'Deposit (' . ucfirst(str_replace('_', ' ', $schedule['deposit_policy'])) . ')',
                    'quantity' => 1,
                    'unit_cents' => $schedule['deposit_cents'],
                    'total_cents' => $schedule['deposit_cents'],
                    'kind' => 'deposit',
                    'course_id' => $schedule['course_id'],
                    'enrollment_id' => $schedule['enrollment_id'],
                ];
                $totalCents += $schedule['deposit_cents'];

                // Record deposit charge in ledger
                $this->depositService->recordCharge(
                    $schedule['school_id'],
                    $schedule['enrollment_id'],
                    $schedule['deposit_cents'],
                    'Deposit charged on first invoice'
                );
            }

            // 4. Onboarding fees (if first invoice)
            if ($schedule['has_prorated_first'] == 0) {
                $onboardingFees = $this->feePolicyModel->getTotalOnboardingFees($schedule['school_id']);
                if ($onboardingFees > 0) {
                    $policy = $this->feePolicyModel->getActivePolicy($schedule['school_id']);
                    if ($policy) {
                        // Add individual onboarding fees
                        if ($policy['registration_fee_cents'] > 0) {
                            $lineItems[] = [
                                'description' => 'Registration Fee',
                                'quantity' => 1,
                                'unit_cents' => $policy['registration_fee_cents'],
                                'total_cents' => $policy['registration_fee_cents'],
                                'kind' => 'onboarding',
                                'course_id' => $schedule['course_id'],
                                'enrollment_id' => $schedule['enrollment_id'],
                            ];
                            $totalCents += $policy['registration_fee_cents'];
                        }

                        if ($policy['material_fee_cents'] > 0) {
                            $lineItems[] = [
                                'description' => 'Material Fee',
                                'quantity' => 1,
                                'unit_cents' => $policy['material_fee_cents'],
                                'total_cents' => $policy['material_fee_cents'],
                                'kind' => 'onboarding',
                                'course_id' => $schedule['course_id'],
                                'enrollment_id' => $schedule['enrollment_id'],
                            ];
                            $totalCents += $policy['material_fee_cents'];
                        }

                        if ($policy['other_fee_cents'] > 0) {
                            $lineItems[] = [
                                'description' => 'Other Fee',
                                'quantity' => 1,
                                'unit_cents' => $policy['other_fee_cents'],
                                'total_cents' => $policy['other_fee_cents'],
                                'kind' => 'onboarding',
                                'course_id' => $schedule['course_id'],
                                'enrollment_id' => $schedule['enrollment_id'],
                            ];
                            $totalCents += $policy['other_fee_cents'];
                        }
                    }
                }
            }

            // 5. Credits (e.g., deposit credit on final invoice)
            if ($schedule['deposit_policy'] === 'non_ref_last_month' && $schedule['next_billing_date']) {
                // Check if this might be the final invoice (would need additional logic)
                // For now, we'll apply credit if requested
                $creditAmount = $this->depositService->getAvailableCredit($schedule['enrollment_id']);
                if ($creditAmount > 0) {
                    $lineItems[] = [
                        'description' => 'Deposit Credit Applied',
                        'quantity' => 1,
                        'unit_cents' => -$creditAmount, // Negative for credit
                        'total_cents' => -$creditAmount,
                        'kind' => 'credit',
                        'course_id' => $schedule['course_id'],
                        'enrollment_id' => $schedule['enrollment_id'],
                    ];
                    $totalCents -= $creditAmount;

                    // Record credit application
                    $this->depositService->applyCredit(
                        $schedule['school_id'],
                        $schedule['enrollment_id'],
                        $creditAmount,
                        'Deposit credit applied to final invoice'
                    );
                }
            }

            // 6. Tax (if applicable - placeholder for future implementation)
            // $taxCents = ...;
            // if ($taxCents > 0) { ... }

            // Ensure total is not negative
            $totalCents = max(0, $totalCents);

            // Create invoice
            $invoiceNumber = $this->generateInvoiceNumber($schedule['school_id']);
            $dueDate = $schedule['next_billing_date'] ?? date('Y-m-d');

            $invoiceData = [
                'school_id' => $schedule['school_id'],
                'student_id' => $schedule['student_id'],
                'enrollment_id' => $schedule['enrollment_id'],
                'student_fee_plan_id' => null, // Could link to student_fee_plans if needed
                'due_date' => $dueDate,
                'amount_due' => $totalCents / 100.0, // Convert to dollars
                'total_cents' => $totalCents,
                'amount_paid' => 0,
                'status' => 'sent',
                'invoice_number' => $invoiceNumber,
                'issued_at' => date('Y-m-d H:i:s'),
            ];

            $invoiceId = $this->invoiceModel->insert($invoiceData);

            // Create line items
            foreach ($lineItems as $item) {
                $item['invoice_id'] = $invoiceId;
                $this->invoiceItemModel->createItem($item);
            }

            // Mark schedule as having prorated first (if applicable)
            if ($schedule['has_prorated_first'] == 0) {
                $this->scheduleModel->update($scheduleId, ['has_prorated_first' => 1]);
            }

            // Generate PDF
            $pdfGenerator = new SimplePdfGenerator();
            $invoiceRecord = $this->invoiceModel->find($invoiceId);
            $student = $this->db->table('t_students')
                ->where('id', $schedule['student_id'])
                ->get()
                ->getRowArray();
            
            if ($invoiceRecord && $student) {
                $pdfPath = $pdfGenerator->generateInvoicePdf($invoiceRecord, $student);
                $this->invoiceModel->update($invoiceId, ['pdf_path' => $pdfPath]);
            }

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                throw new \Exception('Transaction failed');
            }

            // Send email if enabled
            $this->sendInvoiceEmail($invoiceId, $lineItems);

            return [
                'success' => true,
                'invoice_id' => $invoiceId,
                'total_cents' => $totalCents,
                'line_items_count' => count($lineItems),
            ];
        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'InvoiceBuilder::buildInvoice failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to build invoice: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check if invoice already exists for this schedule and due date
     *
     * @param array $schedule
     * @return array|null
     */
    protected function checkExistingInvoice(array $schedule): ?array
    {
        return $this->invoiceModel
            ->where('enrollment_id', $schedule['enrollment_id'])
            ->where('due_date', $schedule['next_billing_date'])
            ->where('status !=', 'void')
            ->first();
    }

    /**
     * Generate unique invoice number
     *
     * @param int $schoolId
     * @return string
     */
    protected function generateInvoiceNumber(int $schoolId): string
    {
        $prefix = 'INV-' . date('Ymd') . '-';
        $suffix = str_pad($schoolId, 4, '0', STR_PAD_LEFT) . '-' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        return $prefix . $suffix;
    }

    /**
     * Send invoice email
     *
     * @param int $invoiceId
     * @param array $lineItems
     * @return bool
     */
    protected function sendInvoiceEmail(int $invoiceId, array $lineItems): bool
    {
        try {
            $invoice = $this->invoiceModel->find($invoiceId);
            if (!$invoice) {
                return false;
            }

            // Check if email is enabled via feature flag
            $schoolId = $invoice['school_id'] ?? 0;
            $emailEnabled = $this->getFeatureFlag('billing.email.enabled', $schoolId);
            if ($emailEnabled !== 'true') {
                return false;
            }

            $student = $this->db->table('t_students')
                ->where('id', $invoice['student_id'])
                ->get()
                ->getRowArray();

            if (!$student || empty($student['email'])) {
                return false;
            }

            $email = \Config\Services::email();
            $view = \Config\Services::renderer();

            $email->setTo($student['email']);
            $email->setSubject('Invoice #' . $invoice['invoice_number']);
            $email->setMessage($view->setData([
                'invoice' => $invoice,
                'student' => $student,
                'lineItems' => $lineItems,
                'paymentLink' => base_url('invoices/' . $invoiceId . '/pay')
            ])->render('emails/invoice_created'));

            // Attach PDF if exists
            if (!empty($invoice['pdf_path'])) {
                $pdfPath = WRITEPATH . $invoice['pdf_path'];
                if (file_exists($pdfPath)) {
                    $email->attach($pdfPath);
                }
            }

            return $email->send();
        } catch (\Exception $e) {
            log_message('error', 'Failed to send invoice email: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get feature flag value
     *
     * @param string $key
     * @param int $schoolId
     * @return string
     */
    protected function getFeatureFlag(string $key, int $schoolId): string
    {
        $flag = $this->db->table('t_feature_flag')
            ->where('school_id', $schoolId)
            ->where('flag_key', $key)
            ->get()
            ->getRowArray();

        return $flag['flag_value'] ?? 'true'; // Default to enabled
    }
}

