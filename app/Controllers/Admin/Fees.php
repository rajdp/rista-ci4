<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Traits\RestTrait;
use App\Models\Admin\FeePlanModel;
use App\Models\Admin\StudentFeePlanModel;
use App\Models\Admin\PaymentModel;
use App\Models\Admin\InvoiceModel;
use App\Models\Admin\StudentsModel;
use App\Libraries\SimplePdfGenerator;
use CodeIgniter\HTTP\ResponseInterface;

class Fees extends BaseController
{
    use RestTrait;

    protected FeePlanModel $feePlanModel;
    protected StudentFeePlanModel $studentFeePlanModel;
    protected PaymentModel $paymentModel;
    protected InvoiceModel $invoiceModel;
    protected StudentsModel $studentsModel;

    public function __construct()
    {
        $this->feePlanModel = new FeePlanModel();
        $this->studentFeePlanModel = new StudentFeePlanModel();
        $this->paymentModel = new PaymentModel();
        $this->invoiceModel = new InvoiceModel();
        $this->studentsModel = new StudentsModel();
    }

    public function listPlans(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);

            if (empty($payload['school_id'])) {
                return $this->errorResponse('school_id is required');
            }

            return $this->successResponse(
                $this->feePlanModel->listForSchool((int) $payload['school_id']),
                'Fee plans retrieved'
            );
        } catch (\Throwable $e) {
            return $this->errorResponse('Unable to load fee plans: ' . $e->getMessage());
        }
    }

    public function savePlan(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);
            $required = ['school_id', 'name', 'plan_type'];

            if (!$this->validateRequiredFields($payload, $required)) {
                return $this->errorResponse('Missing required fields: ' . implode(', ', $this->getMissingFields($payload, $required)));
            }

            $plan = [
                'school_id' => (int) $payload['school_id'],
                'name' => $payload['name'],
                'description' => $payload['description'] ?? null,
                'plan_type' => $payload['plan_type'],
                'amount' => $payload['amount'] ?? '0.00',
                'billing_cycle_days' => $payload['billing_cycle_days'] ?? null,
                'auto_payment_enabled' => !empty($payload['auto_payment_enabled']) ? 1 : 0,
                'metadata' => !empty($payload['metadata']) ? json_encode($payload['metadata']) : null,
            ];

            if (!empty($payload['id'])) {
                $this->feePlanModel->update((int) $payload['id'], $plan);
                $result = $this->feePlanModel->find((int) $payload['id']);
                $message = 'Fee plan updated';
            } else {
                $planId = $this->feePlanModel->insert($plan, true);
                $result = $this->feePlanModel->find($planId);
                $message = 'Fee plan created';
            }

            if (!empty($result['metadata']) && is_string($result['metadata'])) {
                $result['metadata'] = json_decode($result['metadata'], true);
            }

            return $this->successResponse($result, $message);
        } catch (\Throwable $e) {
            return $this->errorResponse('Unable to save fee plan: ' . $e->getMessage());
        }
    }

    public function assignPlan(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);
            $required = ['student_id', 'fee_plan_id', 'start_date'];

            if (!$this->validateRequiredFields($payload, $required)) {
                return $this->errorResponse('Missing required fields: ' . implode(', ', $this->getMissingFields($payload, $required)));
            }

            $assignment = [
                'student_id' => (int) $payload['student_id'],
                'fee_plan_id' => (int) $payload['fee_plan_id'],
                'start_date' => $payload['start_date'],
                'end_date' => $payload['end_date'] ?? null,
                'custom_amount' => $payload['custom_amount'] ?? null,
                'discount_amount' => $payload['discount_amount'] ?? '0.00',
                'status' => $payload['status'] ?? 'active',
                'auto_payment_override' => isset($payload['auto_payment_override']) ? (int) $payload['auto_payment_override'] : null,
            ];

            if (!empty($payload['id'])) {
                $this->studentFeePlanModel->update((int) $payload['id'], $assignment);
                $result = $this->studentFeePlanModel->find((int) $payload['id']);
                $message = 'Assignment updated';
            } else {
                $assignmentId = $this->studentFeePlanModel->insert($assignment, true);
                $result = $this->studentFeePlanModel->find($assignmentId);
                $message = 'Assignment created';
            }

            return $this->successResponse($result, $message);
        } catch (\Throwable $e) {
            return $this->errorResponse('Unable to assign fee plan: ' . $e->getMessage());
        }
    }

    public function recordPayment(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);
            $required = ['student_id', 'amount', 'payment_date'];

            if (!$this->validateRequiredFields($payload, $required)) {
                return $this->errorResponse('Missing required fields: ' . implode(', ', $this->getMissingFields($payload, $required)));
            }

            $data = [
                'student_id' => (int) $payload['student_id'],
                'fee_plan_id' => isset($payload['fee_plan_id']) ? (int) $payload['fee_plan_id'] : null,
                'student_fee_plan_id' => isset($payload['student_fee_plan_id']) ? (int) $payload['student_fee_plan_id'] : null,
                'amount' => $payload['amount'],
                'payment_date' => $payload['payment_date'],
                'payment_method' => $payload['payment_method'] ?? null,
                'receipt_number' => $payload['receipt_number'] ?? null,
                'notes' => $payload['notes'] ?? null,
                'recorded_by' => $payload['recorded_by'] ?? null,
            ];

            if (!empty($payload['id'])) {
                $this->paymentModel->update((int) $payload['id'], $data);
                $result = $this->paymentModel->find((int) $payload['id']);
                $message = 'Payment updated';
            } else {
                $paymentId = $this->paymentModel->insert($data, true);
                $result = $this->paymentModel->find($paymentId);
                $message = 'Payment recorded';
            }

            return $this->successResponse($result, $message);
        } catch (\Throwable $e) {
            return $this->errorResponse('Unable to record payment: ' . $e->getMessage());
        }
    }

    public function studentOverview(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);
            if (empty($payload['student_id'])) {
                return $this->errorResponse('student_id is required');
            }

            $studentId = (int) $payload['student_id'];
            $assignments = $this->studentFeePlanModel->listForStudent($studentId);
            $payments = $this->paymentModel->listForStudent($studentId);

            $planTotals = [];
            foreach ($assignments as $assignment) {
                $plan = $this->feePlanModel->find($assignment['fee_plan_id']);
                $amount = $assignment['custom_amount'] ?? $plan['amount'] ?? 0;
                $planTotals[$assignment['id']] = [
                    'expected' => (float) $amount - (float) $assignment['discount_amount'],
                    'plan' => $plan,
                    'assignment' => $assignment,
                ];
            }

            $paidTotal = 0.0;
            foreach ($payments as $payment) {
                $paidTotal += (float) $payment['amount'];
                if (!empty($payment['student_fee_plan_id']) && isset($planTotals[$payment['student_fee_plan_id']])) {
                    $planTotals[$payment['student_fee_plan_id']]['paid'] = ($planTotals[$payment['student_fee_plan_id']]['paid'] ?? 0) + (float) $payment['amount'];
                }
            }

            foreach ($planTotals as $planId => $totals) {
                $paid = $totals['paid'] ?? 0;
                $planTotals[$planId]['balance'] = $totals['expected'] - $paid;
            }

            $data = [
                'assignments' => array_values($planTotals),
                'payments' => $payments,
                'total_paid' => $paidTotal,
                'overall_balance' => array_sum(array_column($planTotals, 'balance')),
            ];

            return $this->successResponse($data, 'Student fee overview generated');
        } catch (\Throwable $e) {
            return $this->errorResponse('Unable to fetch student overview: ' . $e->getMessage());
        }
    }

    public function generateInvoice(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);
            $required = ['student_id', 'student_fee_plan_id', 'amount_due', 'due_date'];

            if (!$this->validateRequiredFields($payload, $required)) {
                return $this->errorResponse('Missing required fields: ' . implode(', ', $this->getMissingFields($payload, $required)));
            }

            $student = $this->studentsModel->find((int) $payload['student_id']);
            if (!$student) {
                return $this->errorResponse('Student not found', 404);
            }

            $invoiceNumber = $payload['invoice_number'] ?? 'INV-' . date('Ymd-His') . '-' . $payload['student_id'];
            $issuedAt = $payload['issued_at'] ?? date('Y-m-d H:i:s');

            $invoiceData = [
                'student_id' => (int) $payload['student_id'],
                'student_fee_plan_id' => (int) $payload['student_fee_plan_id'],
                'due_date' => $payload['due_date'],
                'amount_due' => $payload['amount_due'],
                'amount_paid' => $payload['amount_paid'] ?? 0,
                'status' => $payload['status'] ?? 'sent',
                'invoice_number' => $invoiceNumber,
                'issued_at' => $issuedAt,
            ];

            $invoiceId = !empty($payload['id'])
                ? (int) $payload['id']
                : $this->invoiceModel->insert($invoiceData, true);

            if (!empty($payload['id'])) {
                $this->invoiceModel->update($invoiceId, $invoiceData);
            }

            $invoice = $this->invoiceModel->find($invoiceId);

            // Generate PDF to writable storage using lightweight helper
            $pdfGenerator = new SimplePdfGenerator();
            $pdfPath = $pdfGenerator->generateInvoicePdf($invoice, $student);

            $this->invoiceModel->update($invoiceId, ['pdf_path' => $pdfPath]);
            $invoice['pdf_path'] = $pdfPath;

            return $this->successResponse($invoice, 'Invoice generated');
        } catch (\Throwable $e) {
            return $this->errorResponse('Unable to generate invoice: ' . $e->getMessage());
        }
    }
}
