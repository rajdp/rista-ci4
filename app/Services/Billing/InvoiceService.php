<?php

namespace App\Services\Billing;

use App\Models\BillingInvoiceModel;
use App\Models\InvoiceItemModel;
use App\Models\SubscriptionModel;
use App\Services\Billing\AuditService;

class InvoiceService
{
    protected $invoiceModel;
    protected $invoiceItemModel;
    protected $subscriptionModel;
    protected $auditService;
    protected $db;

    public function __construct()
    {
        $this->invoiceModel = new BillingInvoiceModel();
        $this->invoiceItemModel = new InvoiceItemModel();
        $this->subscriptionModel = new SubscriptionModel();
        $this->auditService = new AuditService();
        $this->db = \Config\Database::connect();
    }

    /**
     * Generate invoice from subscription
     */
    public function generateFromSubscription(int $subscriptionId, ?string $issueDate = null, ?string $dueDate = null, ?int $actorId = null, ?string $actorRole = null): array
    {
        $this->db->transStart();

        try {
            $subscription = $this->subscriptionModel->find($subscriptionId);
            if (!$subscription) {
                $this->db->transRollback();
                return ['success' => false, 'error' => 'Subscription not found', 'data' => null];
            }

            $issueDate = $issueDate ?? date('Y-m-d');
            $dueDate = $dueDate ?? date('Y-m-d', strtotime('+14 days'));

            // Create invoice
            $invoiceData = [
                'school_id' => $subscription['school_id'],
                'student_id' => $subscription['student_id'],
                'subscription_id' => $subscriptionId,
                'status' => 'open',
                'issue_date' => $issueDate,
                'due_date' => $dueDate,
                'currency' => 'USD',
                'subtotal_cents' => $subscription['amount_cents'],
                'tax_cents' => 0,
                'late_fee_cents' => 0,
                'discount_cents' => 0,
                'total_cents' => $subscription['amount_cents'],
                'balance_cents' => $subscription['amount_cents'],
            ];

            $invoiceId = $this->invoiceModel->insert($invoiceData);

            if (!$invoiceId) {
                $errors = $this->invoiceModel->errors();
                log_message('error', 'Invoice insert failed. Validation errors: ' . json_encode($errors));
                log_message('error', 'Invoice data: ' . json_encode($invoiceData));
                $this->db->transRollback();
                return ['success' => false, 'error' => 'Failed to create invoice: ' . json_encode($errors), 'data' => null];
            }

            // Add line item for tuition
            $course = $this->db->table('tbl_course')
                ->where('course_id', $subscription['course_id'])
                ->get()
                ->getRowArray();
            $courseName = $course['course_name'] ?? 'Course Tuition';

            $itemData = [
                'invoice_id' => $invoiceId,
                'description' => $courseName . ' - ' . ucfirst($subscription['term']) . ' Billing',
                'qty' => 1,
                'unit_price_cents' => $subscription['amount_cents'],
                'line_total_cents' => $subscription['amount_cents'],
                'type' => 'tuition',
            ];

            $itemId = $this->invoiceItemModel->insert($itemData);

            if (!$itemId) {
                $errors = $this->invoiceItemModel->errors();
                log_message('error', 'Invoice item insert failed. Validation errors: ' . json_encode($errors));
                log_message('error', 'Item data: ' . json_encode($itemData));
            }

            // Audit log
            $auditResult = $this->auditService->log(
                $subscription['school_id'],
                'invoice',
                $invoiceId,
                'create',
                $actorId,
                $actorRole,
                ['subscription_id' => $subscriptionId, 'amount_cents' => $subscription['amount_cents']]
            );

            if (!$auditResult) {
                log_message('error', 'Audit log failed for invoice ' . $invoiceId);
            }

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                $dbError = $this->db->error();
                log_message('error', 'Transaction failed. DB Error: ' . json_encode($dbError));
                return ['success' => false, 'error' => 'Transaction failed: ' . ($dbError['message'] ?? 'Unknown error'), 'data' => null];
            }

            $invoice = $this->invoiceModel->getWithDetails($invoiceId);

            return ['success' => true, 'data' => $invoice, 'error' => null];

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'InvoiceService::generateFromSubscription error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage(), 'data' => null];
        }
    }

    /**
     * Create manual invoice
     */
    public function createManualInvoice(array $data, array $items, ?int $actorId = null, ?string $actorRole = null): array
    {
        $this->db->transStart();

        try {
            // Validate
            if (empty($data['school_id']) || empty($data['student_id']) || empty($items)) {
                $this->db->transRollback();
                return ['success' => false, 'error' => 'Missing required fields', 'data' => null];
            }

            // Calculate totals from items
            $subtotal = 0;
            $tax = 0;
            $lateFee = 0;

            foreach ($items as $item) {
                $lineTotal = ($item['qty'] ?? 1) * $item['unit_price_cents'];

                if (($item['type'] ?? 'tuition') === 'tax') {
                    $tax += $lineTotal;
                } elseif (($item['type'] ?? 'tuition') === 'late_fee') {
                    $lateFee += $lineTotal;
                } else {
                    $subtotal += $lineTotal;
                }
            }

            $total = $subtotal + $tax + $lateFee;

            // Create invoice
            $invoiceData = [
                'school_id' => $data['school_id'],
                'student_id' => $data['student_id'],
                'subscription_id' => $data['subscription_id'] ?? null,
                'status' => $data['status'] ?? 'draft',
                'issue_date' => $data['issue_date'] ?? date('Y-m-d'),
                'due_date' => $data['due_date'] ?? date('Y-m-d', strtotime('+14 days')),
                'currency' => 'USD',
                'subtotal_cents' => $subtotal,
                'tax_cents' => $tax,
                'late_fee_cents' => $lateFee,
                'discount_cents' => 0,
                'total_cents' => $total,
                'balance_cents' => $total,
                'meta_json' => $data['meta_json'] ?? null,
            ];

            $invoiceId = $this->invoiceModel->insert($invoiceData);

            if (!$invoiceId) {
                $this->db->transRollback();
                return ['success' => false, 'error' => 'Failed to create invoice', 'data' => null];
            }

            // Add line items
            foreach ($items as $item) {
                $itemData = [
                    'invoice_id' => $invoiceId,
                    'description' => $item['description'],
                    'qty' => $item['qty'] ?? 1,
                    'unit_price_cents' => $item['unit_price_cents'],
                    'line_total_cents' => ($item['qty'] ?? 1) * $item['unit_price_cents'],
                    'type' => $item['type'] ?? 'tuition',
                    'meta_json' => $item['meta_json'] ?? null,
                ];

                $this->invoiceItemModel->insert($itemData);
            }

            // Audit log
            $this->auditService->log(
                $data['school_id'],
                'invoice',
                $invoiceId,
                'create',
                $actorId,
                $actorRole,
                ['manual' => true, 'items_count' => count($items)]
            );

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                return ['success' => false, 'error' => 'Transaction failed', 'data' => null];
            }

            $invoice = $this->invoiceModel->getWithDetails($invoiceId);

            return ['success' => true, 'data' => $invoice, 'error' => null];

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'InvoiceService::createManualInvoice error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage(), 'data' => null];
        }
    }

    /**
     * Get invoices with filters
     */
    public function getInvoices(int $schoolId, array $filters = [], int $page = 1, int $limit = 50): array
    {
        try {
            $offset = ($page - 1) * $limit;
            $invoices = $this->invoiceModel->getBySchool($schoolId, $filters, $limit, $offset);

            // Get total count
            $builder = $this->invoiceModel->where('school_id', $schoolId);
            if (!empty($filters['status'])) {
                if (is_array($filters['status'])) {
                    $builder->whereIn('status', $filters['status']);
                } else {
                    $builder->where('status', $filters['status']);
                }
            }
            $total = $builder->countAllResults(false);

            return [
                'success' => true,
                'data' => [
                    'invoices' => $invoices,
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil($total / $limit)
                ],
                'error' => null
            ];

        } catch (\Exception $e) {
            log_message('error', 'InvoiceService::getInvoices error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage(), 'data' => null];
        }
    }

    /**
     * Get invoice details
     */
    public function getInvoiceDetails(int $invoiceId): array
    {
        try {
            $invoice = $this->invoiceModel->getWithDetails($invoiceId);

            if (!$invoice) {
                return ['success' => false, 'error' => 'Invoice not found', 'data' => null];
            }

            return ['success' => true, 'data' => $invoice, 'error' => null];

        } catch (\Exception $e) {
            log_message('error', 'InvoiceService::getInvoiceDetails error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage(), 'data' => null];
        }
    }

    /**
     * Void invoice
     */
    public function voidInvoice(int $invoiceId, string $reason, ?int $actorId = null, ?string $actorRole = null): array
    {
        try {
            $invoice = $this->invoiceModel->find($invoiceId);
            if (!$invoice) {
                return ['success' => false, 'error' => 'Invoice not found', 'data' => null];
            }

            if ($invoice['status'] === 'paid') {
                return ['success' => false, 'error' => 'Cannot void a paid invoice', 'data' => null];
            }

            $updated = $this->invoiceModel->voidInvoice($invoiceId);

            if (!$updated) {
                return ['success' => false, 'error' => 'Failed to void invoice', 'data' => null];
            }

            // Audit log
            $this->auditService->log(
                $invoice['school_id'],
                'invoice',
                $invoiceId,
                'void',
                $actorId,
                $actorRole,
                ['reason' => $reason]
            );

            return ['success' => true, 'data' => ['voided_at' => date('Y-m-d H:i:s')], 'error' => null];

        } catch (\Exception $e) {
            log_message('error', 'InvoiceService::voidInvoice error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage(), 'data' => null];
        }
    }

    /**
     * Generate upcoming invoices (called by cron)
     */
    public function generateUpcomingInvoices(int $schoolId, string $runDate, int $daysAhead = 5): array
    {
        try {
            $targetDate = date('Y-m-d', strtotime($runDate . " +{$daysAhead} days"));

            // Get subscriptions due for billing
            $subscriptions = $this->subscriptionModel->getDueForBilling($schoolId, $targetDate);

            $created = 0;
            $errors = [];

            foreach ($subscriptions as $subscription) {
                // Check if invoice already exists for this billing cycle
                $existing = $this->invoiceModel
                    ->where('subscription_id', $subscription['subscription_id'])
                    ->where('due_date', $subscription['next_billing_date'])
                    ->first();

                if ($existing) {
                    continue; // Skip if already invoiced
                }

                $result = $this->generateFromSubscription(
                    $subscription['subscription_id'],
                    $runDate,
                    $subscription['next_billing_date']
                );

                if ($result['success']) {
                    $created++;
                } else {
                    $errors[] = "Subscription {$subscription['subscription_id']}: {$result['error']}";
                }
            }

            return [
                'success' => true,
                'data' => [
                    'created' => $created,
                    'errors' => $errors
                ],
                'error' => null
            ];

        } catch (\Exception $e) {
            log_message('error', 'InvoiceService::generateUpcomingInvoices error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage(), 'data' => null];
        }
    }
}
