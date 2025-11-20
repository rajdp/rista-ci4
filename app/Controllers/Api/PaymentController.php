<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Services\PaymentService;
use App\Models\PaymentTransactionModel;
use App\Models\StudentPaymentMethodModel;

class PaymentController extends ResourceController
{
    protected $format = 'json';
    protected PaymentService $paymentService;
    protected PaymentTransactionModel $transactionModel;
    protected StudentPaymentMethodModel $paymentMethodModel;

    public function __construct()
    {
        $this->paymentService = new PaymentService();
        $this->transactionModel = new PaymentTransactionModel();
        $this->paymentMethodModel = new StudentPaymentMethodModel();
    }

    /**
     * Initialize payment (get parameters for frontend tokenization)
     * POST /api/payments/initialize
     */
    public function initialize()
    {
        $data = $this->request->getJSON(true);

        $required = ['school_id', 'student_id'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return $this->respond([
                    'success' => false,
                    'error' => "Missing required field: {$field}"
                ], 400);
            }
        }

        try {
            $result = $this->paymentService->initializePayment(
                (int)$data['school_id'],
                (int)$data['student_id'],
                (float)($data['amount'] ?? 0),
                $data['metadata'] ?? []
            );

            return $this->respond($result);
        } catch (\Exception $e) {
            log_message('error', 'Payment initialization failed: ' . $e->getMessage());
            return $this->respond([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save payment method token
     * POST /api/payments/methods
     */
    public function saveMethod()
    {
        $data = $this->request->getJSON(true);

        $required = ['school_id', 'student_id', 'payment_token'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                return $this->respond([
                    'success' => false,
                    'error' => "Missing required field: {$field}"
                ], 400);
            }
        }

        // Add request context
        $data['ip_address'] = $this->request->getIPAddress();
        $data['user_agent'] = $this->request->getUserAgent()->getAgentString();

        $result = $this->paymentService->savePaymentMethod($data);

        if ($result['success']) {
            return $this->respond($result, 201);
        }

        return $this->respond($result, 400);
    }

    /**
     * Get student's payment methods
     * GET /api/schools/{schoolId}/students/{studentId}/payment-methods
     */
    public function getStudentMethods($schoolId = null, $studentId = null)
    {
        if (!$schoolId || !$studentId) {
            return $this->respond(['error' => 'School ID and Student ID required'], 400);
        }

        try {
            $methods = $this->paymentService->getStudentPaymentMethods((int)$studentId, (int)$schoolId);
            return $this->respond($methods);
        } catch (\Exception $e) {
            log_message('error', 'Failed to get student payment methods: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            return $this->respond([
                'error' => 'Failed to load payment methods',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Set default payment method
     * PUT /api/schools/{schoolId}/students/{studentId}/payment-methods/{id}/set-default
     */
    public function setDefault($schoolId = null, $studentId = null, $paymentMethodId = null)
    {
        if (!$schoolId || !$studentId || !$paymentMethodId) {
            return $this->respond(['error' => 'School ID, Student ID and Payment method ID required'], 400);
        }

        $method = $this->paymentMethodModel->find($paymentMethodId);
        if (!$method) {
            return $this->respond(['error' => 'Payment method not found'], 404);
        }

        // Verify the method belongs to the student
        if ($method['student_id'] != $studentId) {
            return $this->respond(['error' => 'Payment method not found for this student'], 404);
        }

        $this->paymentMethodModel->setAsDefault((int)$paymentMethodId, (int)$studentId);

        return $this->respond(['success' => true, 'message' => 'Default payment method updated']);
    }

    /**
     * Delete payment method
     * DELETE /api/schools/{schoolId}/students/{studentId}/payment-methods/{id}
     */
    public function deleteMethod($schoolId = null, $studentId = null, $paymentMethodId = null)
    {
        if (!$schoolId || !$studentId || !$paymentMethodId) {
            return $this->respond(['error' => 'School ID, Student ID and Payment method ID required'], 400);
        }

        // Get user ID from session if available
        $userId = session()->get('userId') ?? null;

        $result = $this->paymentService->deletePaymentMethod((int)$paymentMethodId, $userId);

        if ($result['success']) {
            return $this->respond(['success' => true, 'message' => 'Payment method deleted']);
        }

        return $this->respond($result, 400);
    }

    /**
     * Charge student
     * POST /api/payments/charge
     */
    public function charge()
    {
        $data = $this->request->getJSON(true);

        $required = ['student_id', 'amount'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                return $this->respond([
                    'success' => false,
                    'error' => "Missing required field: {$field}"
                ], 400);
            }
        }

        if ($data['amount'] <= 0) {
            return $this->respond([
                'success' => false,
                'error' => 'Amount must be greater than zero'
            ], 400);
        }

        // Add request context
        $data['ip_address'] = $this->request->getIPAddress();
        $data['user_id'] = session()->get('userId') ?? null;

        // Check if this is a manual payment (cash, check, zelle, other)
        $paymentMethodType = $data['payment_method_type'] ?? null;
        $manualPaymentMethod = $data['manual_payment_method'] ?? null;
        
        if ($paymentMethodType && $paymentMethodType !== 'stored' && $manualPaymentMethod) {
            // Handle manual payment recording
            $result = $this->paymentService->recordManualPayment(
                (int)$data['student_id'],
                (float)$data['amount'],
                $manualPaymentMethod,
                $data
            );
        } elseif (isset($data['payment_method_id'])) {
            // Charge specific payment method
            $result = $this->paymentService->chargePaymentMethod((int)$data['payment_method_id'], (float)$data['amount'], $data);
        } else {
            // Charge using default payment method
            $result = $this->paymentService->chargeStudent((int)$data['student_id'], (float)$data['amount'], $data);
        }

        if ($result['success']) {
            return $this->respond($result);
        }

        return $this->respond($result, 400);
    }

    /**
     * Refund transaction
     * POST /api/payments/transactions/{id}/refund
     */
    public function refund($transactionId = null)
    {
        if (!$transactionId) {
            return $this->respond(['error' => 'Transaction ID required'], 400);
        }

        $data = $this->request->getJSON(true);

        $result = $this->paymentService->refundTransaction(
            (int)$transactionId,
            isset($data['amount']) ? (float)$data['amount'] : null,
            $data['reason'] ?? null
        );

        if ($result['success']) {
            return $this->respond($result);
        }

        return $this->respond($result, 400);
    }

    /**
     * Get transaction history for student
     * GET /api/schools/{schoolId}/students/{studentId}/transactions
     */
    public function getStudentTransactions($schoolId = null, $studentId = null)
    {
        if (!$schoolId || !$studentId) {
            return $this->respond(['error' => 'School ID and Student ID required'], 400);
        }

        try {
            $filters = [
                'status' => $this->request->getGet('status'),
                'transaction_type' => $this->request->getGet('type'),
                'from_date' => $this->request->getGet('from'),
                'to_date' => $this->request->getGet('to'),
                'limit' => (int)($this->request->getGet('limit') ?? 100),
                'offset' => (int)($this->request->getGet('offset') ?? 0)
            ];

            $filters = array_filter($filters);

            $transactions = $this->transactionModel->getStudentTransactions((int)$studentId, $filters);

            return $this->respond($transactions);
        } catch (\Exception $e) {
            log_message('error', 'Failed to get student transactions: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            return $this->respond([
                'error' => 'Failed to load transactions',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single transaction
     * GET /api/payments/transactions/{id}
     */
    public function getTransaction($transactionId = null)
    {
        if (!$transactionId) {
            return $this->respond(['error' => 'Transaction ID required'], 400);
        }

        $transaction = $this->transactionModel->find($transactionId);

        if (!$transaction) {
            return $this->respond(['error' => 'Transaction not found'], 404);
        }

        // Decode JSON fields
        $transaction['gateway_response'] = json_decode($transaction['gateway_response'] ?? '{}', true);
        $transaction['metadata'] = json_decode($transaction['metadata'] ?? '{}', true);

        return $this->respond($transaction);
    }

    /**
     * Get school transaction summary
     * GET /api/schools/{schoolId}/payments/summary
     */
    public function getSchoolSummary($schoolId = null)
    {
        if (!$schoolId) {
            return $this->respond(['error' => 'School ID required'], 400);
        }

        $period = $this->request->getGet('period') ?? 'month';
        $summary = $this->transactionModel->getSchoolSummary((int)$schoolId, $period);

        return $this->respond($summary);
    }
}
