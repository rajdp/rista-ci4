<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Services\BillingScheduler;
use App\Services\InvoiceBuilder;
use App\Services\BillingRunService;
use App\Models\BillingScheduleModel;
use App\Models\BillingRunModel;

class BillingController extends ResourceController
{
    protected $format = 'json';
    protected BillingScheduler $billingScheduler;
    protected InvoiceBuilder $invoiceBuilder;
    protected BillingRunService $billingRunService;
    protected BillingScheduleModel $scheduleModel;
    protected BillingRunModel $runModel;

    public function __construct()
    {
        $this->billingScheduler = new BillingScheduler();
        $this->invoiceBuilder = new InvoiceBuilder();
        $this->billingRunService = new BillingRunService();
        $this->scheduleModel = new BillingScheduleModel();
        $this->runModel = new BillingRunModel();
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
}


