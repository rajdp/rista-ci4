<?php

namespace App\Services\Billing;

use App\Models\BillingInvoiceModel;
use App\Models\DunningStepModel;
use App\Services\Billing\AuditService;

class DunningService
{
    protected $invoiceModel;
    protected $dunningStepModel;
    protected $auditService;

    public function __construct()
    {
        $this->invoiceModel = new BillingInvoiceModel();
        $this->dunningStepModel = new DunningStepModel();
        $this->auditService = new AuditService();
    }

    /**
     * Execute dunning steps for a school (called by cron)
     */
    public function executeDunningSteps(int $schoolId, string $runDate): array
    {
        try {
            // Get overdue invoices
            $invoices = $this->invoiceModel->getOverdue($schoolId, $runDate);

            $processed = 0;
            $errors = [];

            foreach ($invoices as $invoice) {
                // Calculate days past due
                $dueDate = strtotime($invoice['due_date']);
                $currentDate = strtotime($runDate);
                $daysPastDue = floor(($currentDate - $dueDate) / 86400);

                // Get applicable dunning steps for this day offset
                $steps = $this->dunningStepModel->getApplicableSteps($schoolId, $daysPastDue);

                foreach ($steps as $step) {
                    $result = $this->executeStep($invoice, $step, $daysPastDue);

                    if ($result['success']) {
                        $processed++;
                    } else {
                        $errors[] = "Invoice {$invoice['invoice_id']}, Step {$step['step_id']}: {$result['error']}";
                    }
                }
            }

            return [
                'success' => true,
                'data' => [
                    'processed' => $processed,
                    'errors' => $errors
                ],
                'error' => null
            ];

        } catch (\Exception $e) {
            log_message('error', 'DunningService::executeDunningSteps error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage(), 'data' => null];
        }
    }

    /**
     * Execute a specific dunning step
     */
    protected function executeStep(array $invoice, array $step, int $daysPastDue): array
    {
        try {
            $actions = [];

            // Email action
            if (in_array($step['action'], ['email', 'both'])) {
                $emailResult = $this->sendDunningEmail($invoice, $step, $daysPastDue);
                $actions['email'] = $emailResult['success'];
            }

            // Retry charge action
            if (in_array($step['action'], ['retry_charge', 'both'])) {
                $chargeResult = $this->retryCharge($invoice, $step);
                $actions['charge'] = $chargeResult['success'];
            }

            // Audit log
            $this->auditService->log(
                $invoice['school_id'],
                'invoice',
                $invoice['invoice_id'],
                'dunning_step_executed',
                null,
                'system',
                [
                    'step_id' => $step['step_id'],
                    'day_offset' => $step['day_offset'],
                    'days_past_due' => $daysPastDue,
                    'actions' => $actions
                ]
            );

            return ['success' => true, 'data' => $actions, 'error' => null];

        } catch (\Exception $e) {
            log_message('error', 'DunningService::executeStep error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage(), 'data' => null];
        }
    }

    /**
     * Send dunning email
     */
    protected function sendDunningEmail(array $invoice, array $step, int $daysPastDue): array
    {
        try {
            // Get student email
            $userModel = new \App\Models\Admin\UserModel();
            $student = $userModel->find($invoice['student_id']);

            if (!$student || empty($student['email'])) {
                return ['success' => false, 'error' => 'Student email not found', 'data' => null];
            }

            // Determine email tone based on days past due
            if ($daysPastDue === 0) {
                $subject = "Your EdQuill invoice is ready (Invoice #{$invoice['invoice_no']})";
                $template = 'billing/invoice_ready';
            } elseif ($daysPastDue <= 3) {
                $subject = "Reminder: Invoice #{$invoice['invoice_no']} is now past due";
                $template = 'billing/soft_reminder';
            } elseif ($daysPastDue <= 7) {
                $subject = "Action needed: Invoice #{$invoice['invoice_no']} is past due";
                $template = 'billing/action_needed';
            } else {
                $subject = "Urgent: Invoice #{$invoice['invoice_no']} is seriously past due";
                $template = 'billing/urgent_notice';
            }

            // Send email via CodeIgniter's email service
            $email = \Config\Services::email();
            $email->setTo($student['email']);
            $email->setFrom('billing@edquill.com', 'EdQuill Billing');
            $email->setSubject($subject);

            // Simple email body (in production, use proper email templates)
            $totalAmount = $invoice['total_cents'] / 100;
            $balanceAmount = $invoice['balance_cents'] / 100;

            $body = "Dear {$student['full_name']},\n\n";
            $body .= "Invoice #{$invoice['invoice_no']} for \${$totalAmount} is ";
            $body .= ($daysPastDue > 0) ? "{$daysPastDue} days past due.\n\n" : "ready for payment.\n\n";
            $body .= "Amount due: \${$balanceAmount}\n";
            $body .= "Due date: {$invoice['due_date']}\n\n";
            $body .= "Please log in to your EdQuill account to pay this invoice.\n\n";
            $body .= "Thank you,\nEdQuill Billing Team";

            $email->setMessage($body);

            if (!$email->send()) {
                log_message('error', 'Failed to send dunning email: ' . $email->printDebugger(['headers']));
                return ['success' => false, 'error' => 'Failed to send email', 'data' => null];
            }

            return ['success' => true, 'data' => ['sent_at' => date('Y-m-d H:i:s')], 'error' => null];

        } catch (\Exception $e) {
            log_message('error', 'DunningService::sendDunningEmail error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage(), 'data' => null];
        }
    }

    /**
     * Retry charge for invoice
     */
    protected function retryCharge(array $invoice, array $step): array
    {
        try {
            // Check if autopay is enabled
            $subscriptionModel = new \App\Models\SubscriptionModel();
            $subscription = $subscriptionModel->find($invoice['subscription_id'] ?? 0);

            if (!$subscription || !$subscription['autopay_enabled']) {
                return ['success' => false, 'error' => 'Autopay not enabled', 'data' => null];
            }

            // Get default payment method
            $pmModel = new \App\Models\StudentPaymentMethodModel();
            $paymentMethod = $pmModel->where('student_id', $invoice['student_id'])
                ->where('is_default', 1)
                ->first();

            if (!$paymentMethod) {
                return ['success' => false, 'error' => 'No default payment method', 'data' => null];
            }

            // Attempt charge via payment service
            // This would integrate with existing PaymentService
            // For now, we'll log the attempt
            log_message('info', "Dunning retry charge attempted for invoice {$invoice['invoice_id']}");

            return ['success' => true, 'data' => ['attempted_at' => date('Y-m-d H:i:s')], 'error' => null];

        } catch (\Exception $e) {
            log_message('error', 'DunningService::retryCharge error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage(), 'data' => null];
        }
    }
}
