<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Services\BillingScheduler;
use App\Services\InvoiceBuilder;
use App\Services\BillingRunService;
use App\Models\BillingScheduleModel;
use App\Models\BillingRunModel;
use App\Services\Billing\SubscriptionService;
use App\Services\Billing\InvoiceService;
use App\Services\Billing\PolicyService;
use App\Services\Billing\ReportService;

class BillingController extends ResourceController
{
    protected $format = 'json';
    protected BillingScheduler $billingScheduler;
    protected InvoiceBuilder $invoiceBuilder;
    protected BillingRunService $billingRunService;
    protected BillingScheduleModel $scheduleModel;
    protected BillingRunModel $runModel;
    protected $subscriptionService;
    protected $invoiceService;
    protected $policyService;
    protected $reportService;

    public function __construct()
    {
        $this->billingScheduler = new BillingScheduler();
        $this->invoiceBuilder = new InvoiceBuilder();
        $this->billingRunService = new BillingRunService();
        $this->scheduleModel = new BillingScheduleModel();
        $this->runModel = new BillingRunModel();
        $this->subscriptionService = new SubscriptionService();
        $this->invoiceService = new InvoiceService();
        $this->policyService = new PolicyService();
        $this->reportService = new ReportService();
    }

    /**
     * Get school ID from X-School-Id header
     *
     * @return int|null
     */
    protected function getSchoolId(): ?int
    {
        $schoolId = $this->request->getHeaderLine('X-School-Id');
        return $schoolId ? (int)$schoolId : null;
    }

    /**
     * Get billing summary grouped by term and next_billing_date
     * GET /api/billing/summary
     */
    public function summary()
    {
        $schoolId = $this->getSchoolId();
        if (!$schoolId) {
            return $this->respond(['error' => 'X-School-Id header required'], 400);
        }

        $from = $this->request->getGet('from');
        $to = $this->request->getGet('to');
        $term = $this->request->getGet('term');

        $filters = [];
        if ($from) {
            $filters['from_date'] = $from;
        }
        if ($to) {
            $filters['to_date'] = $to;
        }
        if ($term) {
            $filters['term'] = $term;
        }

        $schedules = $this->scheduleModel->getSchedulesWithFilters($schoolId, $filters);

        // Group by term and next_billing_date
        $grouped = [];
        $totals = [
            'due_today' => ['count' => 0, 'total_cents' => 0],
            'next_7_days' => ['count' => 0, 'total_cents' => 0],
            'overdue' => ['count' => 0, 'total_cents' => 0],
        ];

        $today = date('Y-m-d');
        $nextWeek = date('Y-m-d', strtotime('+7 days'));

        foreach ($schedules as $schedule) {
            $term = $schedule['term'];
            $date = $schedule['next_billing_date'] ?? 'no_date';

            if (!isset($grouped[$term])) {
                $grouped[$term] = [];
            }
            if (!isset($grouped[$term][$date])) {
                $grouped[$term][$date] = [];
            }

            // Get course fee to calculate amount
            $courseFeeModel = new \App\Models\CourseFeePlanModel();
            $courseFee = $courseFeeModel->getFeeForCourse($schedule['course_id'], $schoolId);
            $amountCents = $courseFee ? (int)round(($courseFee['default_amount'] ?? 0) * 100) : 0;

            $schedule['amount_cents'] = $amountCents;
            $grouped[$term][$date][] = $schedule;

            // Update totals
            if ($date === $today) {
                $totals['due_today']['count']++;
                $totals['due_today']['total_cents'] += $amountCents;
            } elseif ($date <= $nextWeek && $date > $today) {
                $totals['next_7_days']['count']++;
                $totals['next_7_days']['total_cents'] += $amountCents;
            } elseif ($date < $today && $date !== 'no_date') {
                $totals['overdue']['count']++;
                $totals['overdue']['total_cents'] += $amountCents;
            }
        }

        return $this->respond([
            'success' => true,
            'data' => [
                'grouped' => $grouped,
                'totals' => $totals,
            ]
        ]);
    }

    /**
     * Seed billing schedule for an enrollment
     * POST /api/billing/enrollment/{enrollmentId}/seed
     */
    public function seedSchedule($enrollmentId = null)
    {
        $schoolId = $this->getSchoolId();
        if (!$schoolId) {
            return $this->respond(['error' => 'X-School-Id header required'], 400);
        }

        if (!$enrollmentId) {
            return $this->respond(['error' => 'Enrollment ID required'], 400);
        }

        $data = $this->request->getJSON(true) ?? [];

        // Get enrollment info
        $db = \Config\Database::connect();
        $enrollment = $db->table('student_courses')
            ->where('id', $enrollmentId)
            ->where('school_id', $schoolId)
            ->get()
            ->getRowArray();

        if (!$enrollment) {
            return $this->respond(['error' => 'Enrollment not found'], 404);
        }

        $startDate = $data['start_date'] ?? $enrollment['enrollment_date'] ?? date('Y-m-d');

        $result = $this->billingScheduler->seedSchedule(
            $enrollmentId,
            $enrollment['student_id'],
            $enrollment['course_id'],
            $schoolId,
            $startDate,
            [
                'deposit_policy' => $data['deposit_policy'] ?? 'none',
                'deposit_cents' => isset($data['deposit_cents']) ? (int)$data['deposit_cents'] : 0,
                'anchor_day' => $data['anchor_day'] ?? null,
                'anchor_month' => $data['anchor_month'] ?? null,
            ]
        );

        if ($result['success']) {
            return $this->respond($result, 201);
        }

        return $this->respond($result, 400);
    }

    /**
     * Generate invoice now for a schedule
     * POST /api/billing/schedule/{scheduleId}/invoice-now
     */
    public function invoiceNow($scheduleId = null)
    {
        $schoolId = $this->getSchoolId();
        if (!$schoolId) {
            return $this->respond(['error' => 'X-School-Id header required'], 400);
        }

        if (!$scheduleId) {
            return $this->respond(['error' => 'Schedule ID required'], 400);
        }

        $schedule = $this->scheduleModel->find($scheduleId);
        if (!$schedule || $schedule['school_id'] != $schoolId) {
            return $this->respond(['error' => 'Schedule not found'], 404);
        }

        $result = $this->invoiceBuilder->buildInvoice($scheduleId);

        if ($result['success']) {
            return $this->respond($result, 201);
        }

        return $this->respond($result, 400);
    }

    /**
     * Trigger daily billing run
     * POST /api/billing/run
     */
    public function triggerRun()
    {
        $schoolId = $this->getSchoolId();
        if (!$schoolId) {
            return $this->respond(['error' => 'X-School-Id header required'], 400);
        }

        $data = $this->request->getJSON(true) ?? [];
        $runDate = $data['run_date'] ?? null;

        $result = $this->billingRunService->runDailyBilling($schoolId, $runDate);

        return $this->respond([
            'success' => true,
            'data' => $result
        ]);
    }

    /**
     * List billing schedules with filters
     * GET /api/billing/schedules
     */
    public function listSchedules()
    {
        $schoolId = $this->getSchoolId();
        if (!$schoolId) {
            return $this->respond(['error' => 'X-School-Id header required'], 400);
        }

        $filters = [
            'term' => $this->request->getGet('term'),
            'status' => $this->request->getGet('status'),
            'course_id' => $this->request->getGet('course_id'),
            'student_id' => $this->request->getGet('student_id'),
            'from_date' => $this->request->getGet('from_date'),
            'to_date' => $this->request->getGet('to_date'),
        ];

        // Remove null values
        $filters = array_filter($filters, function($value) {
            return $value !== null && $value !== '';
        });

        $schedules = $this->scheduleModel->getSchedulesWithFilters($schoolId, $filters);

        // Enrich with course and student info
        $db = \Config\Database::connect();
        $courseFeeModel = new \App\Models\CourseFeePlanModel();
        
        foreach ($schedules as &$schedule) {
            $courseFee = $courseFeeModel->getFeeForCourse($schedule['course_id'], $schoolId);
            $schedule['course_name'] = $courseFee['course_name'] ?? null;
            $schedule['fee_amount'] = $courseFee['default_amount'] ?? null;
        }

        return $this->respond([
            'success' => true,
            'data' => $schedules
        ]);
    }

    // ==================== NEW SUBSCRIPTION API ====================

    /**
     * Create subscription
     * POST /api/billing/subscriptions
     */
    public function createSubscription()
    {
        $schoolId = $this->getSchoolId();
        if (!$schoolId) {
            return $this->respond(['IsSuccess' => false, 'ErrorObject' => 'X-School-Id required'], 400);
        }

        $data = $this->request->getJSON(true);
        $data['school_id'] = $schoolId;

        $result = $this->subscriptionService->createSubscription($data);

        return $this->respond([
            'IsSuccess' => $result['success'],
            'ResponseObject' => $result['data'],
            'ErrorObject' => $result['error'] ?? ''
        ], $result['success'] ? 200 : 400);
    }

    /**
     * List subscriptions
     * GET /api/billing/subscriptions
     */
    public function listSubscriptions()
    {
        $schoolId = $this->getSchoolId();
        if (!$schoolId) {
            return $this->respond(['IsSuccess' => false, 'ErrorObject' => 'X-School-Id required'], 400);
        }

        $filters = array_filter([
            'student_id' => $this->request->getGet('student_id'),
            'course_id' => $this->request->getGet('course_id'),
            'status' => $this->request->getGet('status'),
            'term' => $this->request->getGet('term'),
        ]);

        $result = $this->subscriptionService->getSubscriptions($schoolId, $filters);

        return $this->respond([
            'IsSuccess' => $result['success'],
            'ResponseObject' => $result['data'],
            'ErrorObject' => $result['error'] ?? ''
        ]);
    }

    /**
     * Update subscription
     * PATCH /api/billing/subscriptions/{id}
     */
    public function updateSubscription($id)
    {
        $schoolId = $this->getSchoolId();
        if (!$schoolId) {
            return $this->respond(['IsSuccess' => false, 'ErrorObject' => 'X-School-Id required'], 400);
        }

        $data = $this->request->getJSON(true);
        $result = $this->subscriptionService->updateSubscription($id, $data);

        return $this->respond([
            'IsSuccess' => $result['success'],
            'ResponseObject' => $result['data'],
            'ErrorObject' => $result['error'] ?? ''
        ], $result['success'] ? 200 : 400);
    }

    // ==================== NEW INVOICE API ====================

    /**
     * Generate invoice
     * POST /api/billing/invoices/generate
     */
    public function generateInvoice()
    {
        $schoolId = $this->getSchoolId();
        if (!$schoolId) {
            return $this->respond(['IsSuccess' => false, 'ErrorObject' => 'X-School-Id required'], 400);
        }

        $data = $this->request->getJSON(true);

        if (!empty($data['subscription_id'])) {
            $result = $this->invoiceService->generateFromSubscription(
                $data['subscription_id'],
                $data['issue_date'] ?? null,
                $data['due_date'] ?? null
            );
        } else {
            $data['school_id'] = $schoolId;
            $items = $data['items'] ?? [];
            unset($data['items']);
            $result = $this->invoiceService->createManualInvoice($data, $items);
        }

        return $this->respond([
            'IsSuccess' => $result['success'],
            'ResponseObject' => $result['data'],
            'ErrorObject' => $result['error'] ?? ''
        ], $result['success'] ? 201 : 400);
    }

    /**
     * List invoices
     * GET /api/billing/invoices
     */
    public function listInvoices()
    {
        $schoolId = $this->getSchoolId();
        if (!$schoolId) {
            return $this->respond(['IsSuccess' => false, 'ErrorObject' => 'X-School-Id required'], 400);
        }

        $filters = array_filter([
            'status' => $this->request->getGet('status'),
            'student_id' => $this->request->getGet('student_id'),
            'subscription_id' => $this->request->getGet('subscription_id'),
            'due_from' => $this->request->getGet('due_from'),
            'due_to' => $this->request->getGet('due_to'),
            'search' => $this->request->getGet('search'),
        ]);

        $page = (int) ($this->request->getGet('page') ?? 1);
        $limit = (int) ($this->request->getGet('limit') ?? 50);

        $result = $this->invoiceService->getInvoices($schoolId, $filters, $page, $limit);

        return $this->respond([
            'IsSuccess' => $result['success'],
            'ResponseObject' => $result['data'],
            'ErrorObject' => $result['error'] ?? ''
        ]);
    }

    /**
     * Get invoice details
     * GET /api/billing/invoices/{id}
     */
    public function getInvoice($id)
    {
        $schoolId = $this->getSchoolId();
        if (!$schoolId) {
            return $this->respond(['IsSuccess' => false, 'ErrorObject' => 'X-School-Id required'], 400);
        }

        $result = $this->invoiceService->getInvoiceDetails($id);

        return $this->respond([
            'IsSuccess' => $result['success'],
            'ResponseObject' => $result['data'],
            'ErrorObject' => $result['error'] ?? ''
        ], $result['success'] ? 200 : 404);
    }

    /**
     * Void invoice
     * POST /api/billing/invoices/{id}/void
     */
    public function voidInvoice($id)
    {
        $schoolId = $this->getSchoolId();
        if (!$schoolId) {
            return $this->respond(['IsSuccess' => false, 'ErrorObject' => 'X-School-Id required'], 400);
        }

        $data = $this->request->getJSON(true);
        $reason = $data['reason'] ?? 'No reason provided';

        $result = $this->invoiceService->voidInvoice($id, $reason);

        return $this->respond([
            'IsSuccess' => $result['success'],
            'ResponseObject' => $result['data'],
            'ErrorObject' => $result['error'] ?? ''
        ], $result['success'] ? 200 : 400);
    }

    // ==================== POLICIES API ====================

    /**
     * Get late fee policy
     * GET /api/billing/policies/late_fee
     */
    public function getLateFeePolicy()
    {
        $schoolId = $this->getSchoolId();
        if (!$schoolId) {
            return $this->respond(['IsSuccess' => false, 'ErrorObject' => 'X-School-Id required'], 400);
        }

        $result = $this->policyService->getLateFeePolicy($schoolId);

        return $this->respond([
            'IsSuccess' => $result['success'],
            'ResponseObject' => $result['data'],
            'ErrorObject' => $result['error'] ?? ''
        ]);
    }

    /**
     * Update late fee policy
     * PUT /api/billing/policies/late_fee
     */
    public function updateLateFeePolicy()
    {
        $schoolId = $this->getSchoolId();
        if (!$schoolId) {
            return $this->respond(['IsSuccess' => false, 'ErrorObject' => 'X-School-Id required'], 400);
        }

        $data = $this->request->getJSON(true);
        $result = $this->policyService->updateLateFeePolicy($schoolId, $data);

        return $this->respond([
            'IsSuccess' => $result['success'],
            'ResponseObject' => $result['data'],
            'ErrorObject' => $result['error'] ?? ''
        ], $result['success'] ? 200 : 400);
    }

    /**
     * Get dunning policy
     * GET /api/billing/policies/dunning
     */
    public function getDunningPolicy()
    {
        $schoolId = $this->getSchoolId();
        if (!$schoolId) {
            return $this->respond(['IsSuccess' => false, 'ErrorObject' => 'X-School-Id required'], 400);
        }

        $result = $this->policyService->getDunningSteps($schoolId);

        return $this->respond([
            'IsSuccess' => $result['success'],
            'ResponseObject' => $result['data'],
            'ErrorObject' => $result['error'] ?? ''
        ]);
    }

    /**
     * Update dunning policy
     * PUT /api/billing/policies/dunning
     */
    public function updateDunningPolicy()
    {
        $schoolId = $this->getSchoolId();
        if (!$schoolId) {
            return $this->respond(['IsSuccess' => false, 'ErrorObject' => 'X-School-Id required'], 400);
        }

        $data = $this->request->getJSON(true);
        $steps = $data['steps'] ?? [];

        $result = $this->policyService->updateDunningSteps($schoolId, $steps);

        return $this->respond([
            'IsSuccess' => $result['success'],
            'ResponseObject' => $result['data'],
            'ErrorObject' => $result['error'] ?? ''
        ], $result['success'] ? 200 : 400);
    }

    // ==================== REPORTS API ====================

    /**
     * Pending payments report
     * GET /api/billing/reports/pending
     */
    public function pendingPaymentsReport()
    {
        $schoolId = $this->getSchoolId();
        if (!$schoolId) {
            return $this->respond(['IsSuccess' => false, 'ErrorObject' => 'X-School-Id required'], 400);
        }

        $filters = array_filter([
            'status' => $this->request->getGet('status'),
            'due_from' => $this->request->getGet('due_from'),
            'due_to' => $this->request->getGet('due_to'),
            'course_id' => $this->request->getGet('course_id'),
        ]);

        $result = $this->reportService->getPendingPaymentsReport($schoolId, $filters);

        $format = $this->request->getGet('format');
        if ($format === 'csv') {
            $csv = $this->reportService->exportToCSV($result['data']['invoices'] ?? [], 'pending_payments.csv');
            return $this->response->download('pending_payments.csv', $csv);
        }

        return $this->respond([
            'IsSuccess' => $result['success'],
            'ResponseObject' => $result['data'],
            'ErrorObject' => $result['error'] ?? ''
        ]);
    }

    /**
     * Aging report
     * GET /api/billing/reports/aging
     */
    public function agingReport()
    {
        $schoolId = $this->getSchoolId();
        if (!$schoolId) {
            return $this->respond(['IsSuccess' => false, 'ErrorObject' => 'X-School-Id required'], 400);
        }

        $asOfDate = $this->request->getGet('as_of_date');
        $result = $this->reportService->getAgingReport($schoolId, $asOfDate);

        return $this->respond([
            'IsSuccess' => $result['success'],
            'ResponseObject' => $result['data'],
            'ErrorObject' => $result['error'] ?? ''
        ]);
    }

    /**
     * KPI metrics
     * GET /api/billing/reports/kpis
     */
    public function kpisReport()
    {
        $schoolId = $this->getSchoolId();
        if (!$schoolId) {
            return $this->respond(['IsSuccess' => false, 'ErrorObject' => 'X-School-Id required'], 400);
        }

        $fromDate = $this->request->getGet('from_date') ?? date('Y-m-01');
        $toDate = $this->request->getGet('to_date') ?? date('Y-m-t');

        $result = $this->reportService->getKPIs($schoolId, $fromDate, $toDate);

        return $this->respond([
            'IsSuccess' => $result['success'],
            'ResponseObject' => $result['data'],
            'ErrorObject' => $result['error'] ?? ''
        ]);
    }
}


