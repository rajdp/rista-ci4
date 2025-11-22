<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Traits\RestTrait;
use App\Models\Admin\FeePlanModel;
use App\Models\Admin\StudentFeePlanModel;
use App\Models\Admin\PaymentModel;
use App\Models\Admin\InvoiceModel;
use App\Models\Admin\StudentsModel;
use App\Models\PaymentTransactionModel;
use App\Libraries\SimplePdfGenerator;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Database;

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

            $schoolId = (int) $payload['school_id'];
            $studentId = !empty($payload['student_id']) ? (int) $payload['student_id'] : null;
            $includeInactive = !empty($payload['include_inactive']);

            return $this->successResponse(
                $this->feePlanModel->listForSchool($schoolId, $studentId, $includeInactive),
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

            $assignmentId = null;
            $message = '';

            if (!empty($payload['id'])) {
                // Explicit ID provided - update that record
                $assignmentId = (int) $payload['id'];
                $this->studentFeePlanModel->update($assignmentId, $assignment);
                $message = 'Assignment updated';
            } else {
                // Check if assignment already exists for this student/plan/start_date
                $existing = $this->studentFeePlanModel
                    ->where('student_id', (int) $payload['student_id'])
                    ->where('fee_plan_id', (int) $payload['fee_plan_id'])
                    ->where('start_date', $payload['start_date'])
                    ->first();

                if ($existing) {
                    // Update existing assignment
                    $assignmentId = (int) $existing['id'];
                    $this->studentFeePlanModel->update($assignmentId, $assignment);
                    $message = 'Assignment updated';
                } else {
                    // Before creating new assignment, check if this fee plan is course-specific
                    // and if there are other active assignments for the same course
                    $newPlan = $this->feePlanModel->find((int) $payload['fee_plan_id']);
                    if ($newPlan && strpos($newPlan['name'] ?? '', 'Course:') === 0) {
                        // This is a course-specific plan - check for other active assignments for the same course
                        // Extract course name from plan name (format: "Course: Course Name")
                        $courseName = substr($newPlan['name'], 8); // Remove "Course: " prefix
                        
                        // Get all active assignments for this student
                        $activeAssignments = $this->studentFeePlanModel
                            ->where('student_id', (int) $payload['student_id'])
                            ->where('status', 'active')
                            ->findAll();
                        
                        // Check if any other active assignment uses a fee plan with the same course name
                        foreach ($activeAssignments as $activeAssignment) {
                            $activePlan = $this->feePlanModel->find($activeAssignment['fee_plan_id']);
                            if ($activePlan && strpos($activePlan['name'] ?? '', 'Course:') === 0) {
                                $activeCourseName = substr($activePlan['name'], 8);
                                if ($activeCourseName === $courseName) {
                                    // Found another active assignment for the same course
                                    // Deactivate the old one before creating the new one
                                    $endDate = !empty($payload['start_date']) 
                                        ? date('Y-m-d', strtotime($payload['start_date'] . ' -1 day'))
                                        : date('Y-m-d', strtotime('-1 day'));
                                    
                                    $this->studentFeePlanModel->update($activeAssignment['id'], [
                                        'status' => 'inactive',
                                        'end_date' => $endDate
                                    ]);
                                    log_message('info', "Deactivated old fee plan assignment {$activeAssignment['id']} for course '{$courseName}' before creating new one");
                                }
                            }
                        }
                    }
                    
                    // Create new assignment
                    $assignmentId = $this->studentFeePlanModel->insert($assignment, true);
                    $message = 'Assignment created';
                }
            }

            $result = $this->studentFeePlanModel->find($assignmentId);
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

            // Extract payment_method and ensure it's not defaulted to "Cash"
            $paymentMethod = null;
            if (isset($payload['payment_method']) && $payload['payment_method'] !== '' && $payload['payment_method'] !== null) {
                $paymentMethod = trim($payload['payment_method']);
            }

            // Log the payment_method value for debugging
            log_message('debug', 'Payment record - payment_method from payload: ' . ($paymentMethod ?? 'null'));

            $data = [
                'student_id' => (int) $payload['student_id'],
                'fee_plan_id' => isset($payload['fee_plan_id']) ? (int) $payload['fee_plan_id'] : null,
                'student_fee_plan_id' => isset($payload['student_fee_plan_id']) ? (int) $payload['student_fee_plan_id'] : null,
                'invoice_id' => isset($payload['invoice_id']) ? (int) $payload['invoice_id'] : null,
                'amount' => $payload['amount'],
                'payment_date' => $payload['payment_date'],
                'payment_method' => $paymentMethod,
                'receipt_number' => isset($payload['receipt_number']) && $payload['receipt_number'] !== '' ? $payload['receipt_number'] : null,
                'notes' => isset($payload['notes']) && $payload['notes'] !== '' ? $payload['notes'] : null,
                'recorded_by' => isset($payload['recorded_by']) ? (int) $payload['recorded_by'] : null,
            ];

            // If invoice_id is provided, update invoice status and amount_paid
            if (!empty($payload['invoice_id'])) {
                $invoiceId = (int) $payload['invoice_id'];
                $invoice = $this->invoiceModel->find($invoiceId);
                if ($invoice) {
                    $newAmountPaid = (float) ($invoice['amount_paid'] ?? 0) + (float) $payload['amount'];
                    $newStatus = ($newAmountPaid >= (float) ($invoice['amount_due'] ?? 0)) ? 'paid' : 'sent';
                    
                    $this->invoiceModel->update($invoiceId, [
                        'amount_paid' => $newAmountPaid,
                        'status' => $newStatus
                    ]);
                    
                    // Update next_billing_date if provided and invoice has student_fee_plan_id
                    if (!empty($payload['next_billing_date']) && !empty($invoice['student_fee_plan_id'])) {
                        $studentFeePlanId = (int) $invoice['student_fee_plan_id'];
                        $this->studentFeePlanModel->update($studentFeePlanId, [
                            'next_billing_date' => $payload['next_billing_date']
                        ]);
                        log_message('info', "Updated next_billing_date for student_fee_plan {$studentFeePlanId} to {$payload['next_billing_date']}");
                    }
                }
            }

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
            $schoolId = !empty($payload['school_id']) ? (int) $payload['school_id'] : null;
            
            // Get assignments and join with student_courses to get enrollment_date and completion_date
            $db = Database::connect();
            $assignmentsQuery = $db->table('student_fee_plans sfp')
                ->select('sfp.*, sc.enrollment_date, sc.completion_date, sc.status as course_status')
                ->join('student_courses sc', 'sc.student_fee_plan_id = sfp.id', 'left')
                ->where('sfp.student_id', $studentId)
                ->orderBy('sfp.created_at', 'DESC')
                ->get();
            $assignments = $assignmentsQuery->getResultArray();
            
            $payments = $this->paymentModel->listForStudent($studentId);
            
            // Also fetch payment transactions from payment_transactions table
            $transactionModel = new PaymentTransactionModel();
            $filters = [];
            if ($schoolId) {
                $filters['school_id'] = $schoolId;
            }
            $transactions = $transactionModel->getStudentTransactions($studentId, $filters);
            
            // Convert transactions to payment format for compatibility
            // Include ALL transactions (charges, refunds, voided) for history, but only count valid charges in total
            $transactionPayments = [];
            foreach ($transactions as $transaction) {
                $metadata = is_string($transaction['metadata']) 
                    ? json_decode($transaction['metadata'], true) 
                    : ($transaction['metadata'] ?? []);
                
                $transactionType = $transaction['transaction_type'] ?? 'charge';
                $status = $transaction['status'] ?? 'pending';
                
                // For refunds, amount should be negative
                $amount = (float) $transaction['amount'];
                if ($transactionType === 'refund') {
                    $amount = -$amount; // Negative for refunds
                }
                
                $transactionPayments[] = [
                    'id' => $transaction['id'],
                    'amount' => $amount,
                    'payment_date' => $transaction['created_at'],
                    'paid_on' => $transaction['created_at'],
                    'payment_method' => $this->getPaymentMethodFromTransaction($transaction, $metadata),
                    'description' => $transaction['description'] ?? ($transactionType === 'refund' ? 'Refund' : 'Payment'),
                    'status' => $status,
                    'transaction_type' => $transactionType,
                    'gateway_transaction_id' => $transaction['gateway_transaction_id'] ?? null,
                    'student_fee_plan_id' => null, // May need to link this if available in metadata
                    'parent_transaction_id' => $transaction['parent_transaction_id'] ?? null,
                ];
            }
            
            // Merge old payments with new transactions
            $allPayments = array_merge($payments, $transactionPayments);

            // Fetch all invoices for the student to calculate actual invoiced amounts
            $invoices = $this->invoiceModel->listForStudent($studentId);
            
            // Get next billing date - prioritize student-level, then assignment-level
            $nextBillingDate = null;
            
            // Check student-level next_billing_date
            $db = Database::connect();
            $studentProfile = $db->table('user_profile_details')
                ->where('user_id', $studentId)
                ->where('school_id', $schoolId ?? 0)
                ->get()
                ->getRowArray();
            
            if (!empty($studentProfile['next_billing_date'])) {
                $nextBillingDate = $studentProfile['next_billing_date'];
            } else {
                // Fallback to earliest assignment-level next_billing_date
                foreach ($assignments as $assignment) {
                    if (!empty($assignment['next_billing_date'])) {
                        if ($nextBillingDate === null || $assignment['next_billing_date'] < $nextBillingDate) {
                            $nextBillingDate = $assignment['next_billing_date'];
                        }
                    }
                }
            }
            
            // Calculate total invoiced amount from invoices AFTER next billing date
            // Balance should only consider future invoices and payments
            $totalInvoiced = 0.0;
            $totalInvoicedAll = 0.0; // Keep track of all invoices for reference
            foreach ($invoices as $invoice) {
                $invoiceAmount = 0.0;
                // Use amount_due if available, otherwise use total_cents / 100
                if (isset($invoice['amount_due'])) {
                    $invoiceAmount = (float) $invoice['amount_due'];
                } elseif (isset($invoice['total_cents'])) {
                    $invoiceAmount = (float) $invoice['total_cents'] / 100;
                }
                
                $totalInvoicedAll += $invoiceAmount;
                
                // Only include invoices with due_date >= next_billing_date (future invoices)
                if ($nextBillingDate && isset($invoice['due_date'])) {
                    if ($invoice['due_date'] >= $nextBillingDate) {
                        $totalInvoiced += $invoiceAmount;
                    }
                } elseif (!$nextBillingDate) {
                    // If no next billing date, include all invoices
                    $totalInvoiced += $invoiceAmount;
                }
            }

            // Calculate expected fees from active courses as of today
            // Only include courses that are currently active based on enrollment_date and completion_date
            // A course is active if: enrollment_date <= today AND (completion_date IS NULL OR completion_date >= today)
            $totalExpectedFees = 0.0;
            $today = date('Y-m-d');
            
            foreach ($assignments as $assignment) {
                // Check assignment status
                $assignmentStatus = strtolower($assignment['status'] ?? 'active');
                
                // Skip inactive assignments
                if ($assignmentStatus !== 'active') {
                    continue;
                }
                
                // Get course enrollment dates from student_courses table
                // Also check assignment start_date and end_date for validity period
                $enrollmentDate = $assignment['enrollment_date'] ?? null;
                $completionDate = $assignment['completion_date'] ?? null;
                $assignmentStartDate = $assignment['start_date'] ?? null;
                $assignmentEndDate = $assignment['end_date'] ?? null;
                
                // Check BOTH assignment start_date AND enrollment_date
                // If assignment has a start_date in the future, exclude it (even if enrolled today)
                // If assignment doesn't have start_date, check enrollment_date
                $hasStarted = true;
                if ($assignmentStartDate && $assignmentStartDate > $today) {
                    // Assignment starts in the future - exclude it
                    $hasStarted = false;
                } elseif (!$assignmentStartDate && $enrollmentDate && $enrollmentDate > $today) {
                    // No assignment start_date, but enrollment is in the future - exclude it
                    $hasStarted = false;
                }
                
                // Check end dates - must not have ended
                $hasNotEnded = true;
                if ($assignmentEndDate && $assignmentEndDate < $today) {
                    // Assignment has ended
                    $hasNotEnded = false;
                } elseif (!$assignmentEndDate && $completionDate && $completionDate < $today) {
                    // No assignment end_date, but completion is in the past - exclude it
                    $hasNotEnded = false;
                }
                
                // Only include assignments that are currently active TODAY (started and not ended)
                if ($hasStarted && $hasNotEnded) {
                    $plan = $this->feePlanModel->find($assignment['fee_plan_id']);
                    $amount = $assignment['custom_amount'] ?? $plan['amount'] ?? 0;
                    $discountAmount = (float) ($assignment['discount_amount'] ?? 0);
                    $netAmount = (float) $amount - $discountAmount;
                    
                    // Only add if amount is positive
                    if ($netAmount > 0) {
                        $totalExpectedFees += $netAmount;
                    }
                }
            }

            // Calculate total paid - only include succeeded charges AFTER next billing date
            // Note: For refunds, amount is already negative, so we skip them
            // For voided/cancelled, we also skip them
            $paidTotal = 0.0;
            $paidTotalAll = 0.0; // Keep track of all payments for reference
            foreach ($allPayments as $payment) {
                $status = strtolower($payment['status'] ?? $payment['state'] ?? '');
                $transactionType = strtolower($payment['transaction_type'] ?? 'charge');
                
                // Skip refunds (they have negative amounts and shouldn't be counted)
                if ($transactionType === 'refund') {
                    continue;
                }
                
                // Skip voided/cancelled payments
                if ($status === 'cancelled' || $status === 'void' || $status === 'voided') {
                    continue;
                }
                
                // Only count succeeded charges
                if ($transactionType === 'charge' && $status === 'succeeded') {
                    $amount = (float) $payment['amount'];
                    // Ensure amount is positive (in case of any data inconsistency)
                    if ($amount > 0) {
                        $paidTotalAll += $amount;
                        
                        // Only include payments with payment_date >= next_billing_date (future payments)
                        $paymentDate = $payment['payment_date'] ?? $payment['paid_on'] ?? null;
                        if ($nextBillingDate && $paymentDate) {
                            // Extract date part if it includes time
                            $paymentDateOnly = substr($paymentDate, 0, 10);
                            if ($paymentDateOnly >= $nextBillingDate) {
                                $paidTotal += $amount;
                            }
                        } elseif (!$nextBillingDate) {
                            // If no next billing date, include all payments
                            $paidTotal += $amount;
                        }
                    }
                }
            }

            // Get active custom items for the student (for balance calculation)
            // Use today's date to get all currently active custom items
            // This ensures custom items are always factored into the current balance
            $customItemModel = new \App\Models\StudentCustomItemModel();
            $customItemsTotal = 0.0;
            try {
                if ($schoolId) {
                    // Always use today's date for custom items to get currently active items
                    // Custom items should be factored into the balance regardless of next billing date
                    $customItemsDate = date('Y-m-d');
                    $customItemsTotal = $customItemModel->getTotalAmountForStudent($studentId, $schoolId, $customItemsDate);
                    log_message('debug', sprintf(
                        'Custom Items Calculation - Student: %d, School: %d, Date: %s, Total: %.2f',
                        $studentId,
                        $schoolId,
                        $customItemsDate,
                        $customItemsTotal
                    ));
                }
            } catch (\Throwable $e) {
                // If custom items model fails, log but continue with 0
                log_message('warning', 'Failed to get custom items for balance calculation: ' . $e->getMessage());
                log_message('warning', 'Stack trace: ' . $e->getTraceAsString());
                $customItemsTotal = 0.0;
            }
            
            // Calculate outstanding balance based on TODAY's active courses only:
            // Balance = (Expected Fees Today + Custom Items Today) - Payments (all payments, not filtered by date)
            // This matches the Billing Snapshot calculation: Monthly Fee + Custom Items - Payments
            // Only include fees from courses/assignments that are active TODAY (not future courses)
            // IMPORTANT: DO NOT include invoices in balance - balance is based on active fees TODAY, not past/future invoices
            // Custom items can be negative (discounts) or positive (charges)
            // Use ONLY $totalExpectedFees (which already excludes future courses) - do NOT use $totalInvoiced
            $baseBillingAmount = $totalExpectedFees; // Use only fees from courses active today (excludes future courses)
            // Add custom items active today (they can be negative, so this will subtract for discounts)
            // Example: $275 (fees) + (-$30) (discount) = $245
            $totalBillingAmount = $baseBillingAmount + $customItemsTotal;
            
            // Ensure we're NOT including invoices in the balance calculation
            // $totalInvoiced is calculated above but should NOT be used for balance
            // Balance = Active Fees Today + Custom Items Today - All Payments
            
            // Calculate total paid - include ALL payments (all successful charges, regardless of date)
            // This matches the frontend expectation that Balance = Fees Today - All Payments
            $paidTotalAll = 0.0;
            foreach ($allPayments as $payment) {
                $status = strtolower($payment['status'] ?? $payment['state'] ?? '');
                $transactionType = strtolower($payment['transaction_type'] ?? 'charge');
                
                // Skip refunds (they have negative amounts and shouldn't be counted)
                if ($transactionType === 'refund') {
                    continue;
                }
                
                // Skip voided/cancelled payments
                if ($status === 'cancelled' || $status === 'void' || $status === 'voided') {
                    continue;
                }
                
                // Count all succeeded charges (not filtered by date)
                if ($transactionType === 'charge' && $status === 'succeeded') {
                    $amount = (float) $payment['amount'];
                    if ($amount > 0) {
                        $paidTotalAll += $amount;
                    }
                }
            }
            
            // Outstanding Balance = (Fees from courses active TODAY + Custom Items active TODAY) - All Payments
            // This matches Billing Snapshot: Monthly Fee + Custom Items - Payments
            $outstandingBalance = $totalBillingAmount - $paidTotalAll;
            
            // Debug: Log the calculation to verify
            log_message('info', sprintf(
                'Balance Calculation (TODAY) - Student: %d | Expected Fees (Today): %.2f, Custom Items: %.2f, Total Billing: %.2f, Paid: %.2f, Balance: %.2f',
                $studentId,
                $totalExpectedFees,
                $customItemsTotal,
                $totalBillingAmount,
                $paidTotalAll,
                $outstandingBalance
            ));
            
            // Log which assignments are being included in the balance
            $includedAssignments = [];
            foreach ($assignments as $assignment) {
                $assignmentStatus = strtolower($assignment['status'] ?? 'active');
                if ($assignmentStatus === 'active') {
                    $enrollmentDate = $assignment['enrollment_date'] ?? null;
                    $assignmentStartDate = $assignment['start_date'] ?? null;
                    $assignmentEndDate = $assignment['end_date'] ?? null;
                    $completionDate = $assignment['completion_date'] ?? null;
                    
                    $hasStarted = true;
                    if ($assignmentStartDate && $assignmentStartDate > $today) {
                        $hasStarted = false;
                    } elseif (!$assignmentStartDate && $enrollmentDate && $enrollmentDate > $today) {
                        $hasStarted = false;
                    }
                    
                    $hasNotEnded = true;
                    if ($assignmentEndDate && $assignmentEndDate < $today) {
                        $hasNotEnded = false;
                    } elseif (!$assignmentEndDate && $completionDate && $completionDate < $today) {
                        $hasNotEnded = false;
                    }
                    
                    if ($hasStarted && $hasNotEnded) {
                        $plan = $this->feePlanModel->find($assignment['fee_plan_id']);
                        $amount = $assignment['custom_amount'] ?? $plan['amount'] ?? 0;
                        $discountAmount = (float) ($assignment['discount_amount'] ?? 0);
                        $netAmount = (float) $amount - $discountAmount;
                        if ($netAmount > 0) {
                            $includedAssignments[] = [
                                'id' => $assignment['id'],
                                'start_date' => $assignmentStartDate,
                                'end_date' => $assignmentEndDate,
                                'enrollment_date' => $enrollmentDate,
                                'amount' => $netAmount
                            ];
                        }
                    }
                }
            }
            log_message('info', sprintf(
                'Balance Calculation - Included Assignments: %s | Total Invoiced: %.2f (NOT USED), Expected Fees: %.2f, Custom Items: %.2f, Total Billing: %.2f, Paid: %.2f, Balance: %.2f',
                json_encode($includedAssignments),
                $totalInvoiced,
                $totalExpectedFees,
                $customItemsTotal,
                $totalBillingAmount,
                $paidTotalAll,
                $outstandingBalance
            ));

            // Build assignment details for display (still show fee plan info)
            $planTotals = [];
            foreach ($assignments as $assignment) {
                $plan = $this->feePlanModel->find($assignment['fee_plan_id']);
                $amount = $assignment['custom_amount'] ?? $plan['amount'] ?? 0;
                
                // Use assignment-level next_billing_date if available, otherwise use student-level
                $assignmentNextBillingDate = $assignment['next_billing_date'] ?? $nextBillingDate;
                
                // Calculate paid amount for this specific assignment from payments AFTER next billing date
                $assignmentPaid = 0.0;
                foreach ($allPayments as $payment) {
                    $status = strtolower($payment['status'] ?? $payment['state'] ?? '');
                    $transactionType = strtolower($payment['transaction_type'] ?? 'charge');
                    
                    if ($transactionType === 'charge' && $status === 'succeeded') {
                        if (!empty($payment['student_fee_plan_id']) && (int)$payment['student_fee_plan_id'] === (int)$assignment['id']) {
                            $paymentAmount = (float) $payment['amount'];
                            if ($paymentAmount > 0) {
                                // Only include payments after next billing date
                                $paymentDate = $payment['payment_date'] ?? $payment['paid_on'] ?? null;
                                if ($assignmentNextBillingDate && $paymentDate) {
                                    $paymentDateOnly = substr($paymentDate, 0, 10);
                                    if ($paymentDateOnly >= $assignmentNextBillingDate) {
                                        $assignmentPaid += $paymentAmount;
                                    }
                                } elseif (!$assignmentNextBillingDate) {
                                    // If no next billing date, include all payments
                                    $assignmentPaid += $paymentAmount;
                                }
                            }
                        }
                    }
                }
                
                // Calculate invoiced amount for this assignment from invoices AFTER next billing date
                $assignmentInvoiced = 0.0;
                foreach ($invoices as $invoice) {
                    if (!empty($invoice['student_fee_plan_id']) && (int)$invoice['student_fee_plan_id'] === (int)$assignment['id']) {
                        $invoiceAmount = 0.0;
                        if (isset($invoice['amount_due'])) {
                            $invoiceAmount = (float) $invoice['amount_due'];
                        } elseif (isset($invoice['total_cents'])) {
                            $invoiceAmount = (float) $invoice['total_cents'] / 100;
                        }
                        
                        // Only include invoices with due_date >= next_billing_date
                        if ($assignmentNextBillingDate && isset($invoice['due_date'])) {
                            if ($invoice['due_date'] >= $assignmentNextBillingDate) {
                                $assignmentInvoiced += $invoiceAmount;
                            }
                        } elseif (!$assignmentNextBillingDate) {
                            // If no next billing date, include all invoices
                            $assignmentInvoiced += $invoiceAmount;
                        }
                    }
                }
                
                // Calculate expected fee for this assignment (if no invoices or to supplement)
                // Use enrollment_date and completion_date from student_courses to determine if course is active
                $assignmentExpectedFee = 0.0;
                $assignmentStatus = strtolower($assignment['status'] ?? 'active');
                if ($assignmentStatus === 'active') {
                    // Get course enrollment dates from student_courses table
                    // Also check assignment start_date and end_date for validity period
                    $enrollmentDate = $assignment['enrollment_date'] ?? null;
                    $completionDate = $assignment['completion_date'] ?? null;
                    $assignmentStartDate = $assignment['start_date'] ?? null;
                    $assignmentEndDate = $assignment['end_date'] ?? null;
                    $today = date('Y-m-d');
                    
                    // Check BOTH assignment start_date AND enrollment_date
                    // If assignment has a start_date in the future, exclude it (even if enrolled today)
                    // If assignment doesn't have start_date, check enrollment_date
                    $hasStarted = true;
                    if ($assignmentStartDate && $assignmentStartDate > $today) {
                        // Assignment starts in the future - exclude it
                        $hasStarted = false;
                    } elseif (!$assignmentStartDate && $enrollmentDate && $enrollmentDate > $today) {
                        // No assignment start_date, but enrollment is in the future - exclude it
                        $hasStarted = false;
                    }
                    
                    // Check end dates - must not have ended
                    $hasNotEnded = true;
                    if ($assignmentEndDate && $assignmentEndDate < $today) {
                        // Assignment has ended
                        $hasNotEnded = false;
                    } elseif (!$assignmentEndDate && $completionDate && $completionDate < $today) {
                        // No assignment end_date, but completion is in the past - exclude it
                        $hasNotEnded = false;
                    }
                    
                    // If assignment is currently active TODAY (started and not ended), include it in expected fees
                    if ($hasStarted && $hasNotEnded) {
                        $discountAmount = (float) ($assignment['discount_amount'] ?? 0);
                        $assignmentExpectedFee = (float) $amount - $discountAmount;
                        if ($assignmentExpectedFee < 0) {
                            $assignmentExpectedFee = 0.0;
                        }
                    }
                }
                
                // Use the maximum of invoiced amount or expected fee
                $assignmentBillingAmount = max($assignmentInvoiced, $assignmentExpectedFee);
                
                $planTotals[$assignment['id']] = [
                    'expected' => $assignmentBillingAmount > 0 ? $assignmentBillingAmount : ((float) $amount - (float) $assignment['discount_amount']), // Use billing amount if available, otherwise fallback to plan amount
                    'invoiced' => $assignmentInvoiced,
                    'expected_fee' => $assignmentExpectedFee,
                    'paid' => $assignmentPaid,
                    'balance' => $assignmentBillingAmount > 0 ? ($assignmentBillingAmount - $assignmentPaid) : ((float) $amount - (float) $assignment['discount_amount'] - $assignmentPaid),
                    'plan' => $plan,
                    'assignment' => $assignment,
                ];
            }

            $data = [
                'assignments' => array_values($planTotals),
                'payments' => $allPayments,
                'payment_history' => $allPayments, // Also include as payment_history for frontend compatibility
                'invoices' => $invoices, // Include invoices for reference
                'total_invoiced' => $totalInvoiced, // Total amount from invoices after next billing date
                'total_expected_fees' => $totalExpectedFees, // Total expected fees from active assignments TODAY (excludes future courses)
                'total_custom_items' => $customItemsTotal, // Total from active custom items TODAY (can be negative for discounts)
                'total_billing_amount' => $totalBillingAmount, // Expected fees from courses active TODAY + custom items active TODAY
                'total_paid' => $paidTotalAll, // Total paid (all successful charges, not filtered by date)
                'overall_balance' => $outstandingBalance, // Outstanding balance = (Fees from courses active TODAY + Custom Items active TODAY) - All Payments
                'next_billing_date' => $nextBillingDate, // Include next billing date for reference
            ];

            return $this->successResponse($data, 'Student fee overview generated');
        } catch (\Throwable $e) {
            log_message('error', 'Fees::studentOverview - Exception: ' . $e->getMessage());
            log_message('error', 'Fees::studentOverview - Stack trace: ' . $e->getTraceAsString());
            log_message('error', 'Fees::studentOverview - Student ID: ' . ($studentId ?? 'null') . ', School ID: ' . ($schoolId ?? 'null'));
            return $this->errorResponse('Unable to fetch student overview: ' . $e->getMessage());
        }
    }
    
    /**
     * Extract payment method from transaction data
     */
    private function getPaymentMethodFromTransaction(array $transaction, array $metadata): string
    {
        // Check metadata for manual payment method
        if (!empty($metadata['payment_method'])) {
            return ucfirst($metadata['payment_method']);
        }
        if (!empty($metadata['manual_payment_method'])) {
            return ucfirst($metadata['manual_payment_method']);
        }
        
        // Check provider code
        if (!empty($transaction['provider_code'])) {
            if ($transaction['provider_code'] === 'manual') {
                return 'Manual Payment';
            }
            return ucfirst($transaction['provider_code']);
        }
        
        // Check provider name
        if (!empty($transaction['provider_name'])) {
            return $transaction['provider_name'];
        }
        
        return 'Payment';
    }

    public function generateInvoice(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);
            $required = ['student_id', 'due_date'];

            if (!$this->validateRequiredFields($payload, $required)) {
                return $this->errorResponse('Missing required fields: ' . implode(', ', $this->getMissingFields($payload, $required)));
            }

            // Check if items are provided (multi-item invoice)
            if (empty($payload['items'])) {
                return $this->errorResponse('Invoice items are required');
            }

            // Convert items to array if it's an object
            $items = $payload['items'];
            if (is_object($items)) {
                $items = json_decode(json_encode($items), true);
            }
            if (!is_array($items)) {
                return $this->errorResponse('Invoice items must be an array');
            }

            $student = $this->studentsModel->find((int) $payload['student_id']);
            if (!$student) {
                return $this->errorResponse('Student not found', 404);
            }

            // Generate unique invoice number if not provided or empty
            $invoiceNumber = !empty($payload['invoice_number']) ? trim($payload['invoice_number']) : null;
            if (empty($invoiceNumber)) {
                $invoiceNumber = $this->generateUniqueInvoiceNumber((int) $payload['student_id']);
            }
            $issuedAt = $payload['issued_at'] ?? date('Y-m-d H:i:s');

            // Get active custom items for the student on the due date
            $customItemModel = new \App\Models\StudentCustomItemModel();
            $dueDate = $payload['due_date'];
            $activeCustomItems = $customItemModel->getActiveItemsForStudent(
                (int) $payload['student_id'],
                (int) $student['school_id'],
                $dueDate
            );

            // Add custom items to invoice items if not already included
            // Check if any custom items are already in the items array
            $existingCustomItemDescriptions = [];
            foreach ($items as $item) {
                // Convert item to array if it's an object
                if (is_object($item)) {
                    $item = json_decode(json_encode($item), true);
                }
                if (isset($item['is_custom']) && $item['is_custom']) {
                    $existingCustomItemDescriptions[] = strtolower(trim($item['description']));
                }
            }

            // Add custom items that aren't already in the invoice
            foreach ($activeCustomItems as $customItem) {
                // Convert customItem to array if it's an object
                if (is_object($customItem)) {
                    $customItem = json_decode(json_encode($customItem), true);
                }
                $customDesc = strtolower(trim($customItem['description']));
                if (!in_array($customDesc, $existingCustomItemDescriptions)) {
                    // For custom items, use the actual amount (can be negative for discounts)
                    $items[] = [
                        'description' => $customItem['description'],
                        'amount_due' => (string)$customItem['amount'], // Keep original sign (positive or negative)
                        'is_custom' => true,
                        'is_discount' => $customItem['amount'] < 0
                    ];
                }
            }

            // Calculate total amount from items
            $totalAmount = 0;
            foreach ($items as $item) {
                // Convert item to array if it's an object
                if (is_object($item)) {
                    $item = json_decode(json_encode($item), true);
                }
                // Handle custom items with negative amounts (discounts)
                $amountDue = (float)($item['amount_due'] ?? 0);
                $totalAmount += $amountDue; // This will add negative values for discounts
                $totalAmount += (float)($item['deposit_amount'] ?? 0);
                $totalAmount += (float)($item['one_time_fee'] ?? 0);
            }

            // Use first item's student_fee_plan_id for backward compatibility
            $firstItem = $items[0] ?? [];
            if (is_object($firstItem)) {
                $firstItem = json_decode(json_encode($firstItem), true);
            }
            $studentFeePlanId = !empty($firstItem['student_fee_plan_id']) ? (int) $firstItem['student_fee_plan_id'] : null;

            $invoiceData = [
                'student_id' => (int) $payload['student_id'],
                'student_fee_plan_id' => $studentFeePlanId,
                'due_date' => $payload['due_date'],
                'amount_due' => $totalAmount,
                'amount_paid' => 0,
                'status' => $payload['status'] ?? 'sent',
                'invoice_number' => $invoiceNumber,
                'issued_at' => $issuedAt,
            ];
            
            // Store notes if invoices table has a notes field (we'll check dynamically)
            // For now, we'll skip notes as the table schema doesn't show it

            $invoiceId = !empty($payload['id'])
                ? (int) $payload['id']
                : $this->invoiceModel->insert($invoiceData, true);

            if (!empty($payload['id'])) {
                $this->invoiceModel->update($invoiceId, $invoiceData);
            }

            // Create invoice items if table exists (optional for backward compatibility)
            $db = Database::connect();
            if ($db->tableExists('t_invoice_item')) {
                $invoiceItemModel = new \App\Models\InvoiceItemModel();
                
                // Delete existing items if editing
                if (!empty($payload['id'])) {
                    $invoiceItemModel->deleteInvoiceItems($invoiceId);
                }

                // Insert new items
                foreach ($items as $item) {
                    // Convert item to array if it's an object
                    if (is_object($item)) {
                        $item = json_decode(json_encode($item), true);
                    }
                    
                    $itemAmount = (float)($item['amount_due'] ?? 0);
                    $depositAmount = (float)($item['deposit_amount'] ?? 0);
                    $oneTimeFee = (float)($item['one_time_fee'] ?? 0);
                    $description = $item['description'] ?? 'Fee';
                    
                    // Get student_fee_plan_id from item (null for custom items/discounts)
                    $studentFeePlanId = !empty($item['student_fee_plan_id']) ? (int) $item['student_fee_plan_id'] : null;
                    
                    // Create separate line items for each component
                    // Handle both positive amounts and negative amounts (discounts)
                    if ($itemAmount != 0) {
                        // For custom items, use the description as-is
                        $itemDescription = isset($item['is_custom']) && $item['is_custom'] 
                            ? $description 
                            : $description . ' - ' . ($itemAmount < 0 ? 'Discount' : 'Amount Due');
                        $invoiceItemModel->createItem([
                            'invoice_id' => $invoiceId,
                            'student_fee_plan_id' => $studentFeePlanId,
                            'description' => $itemDescription,
                            'quantity' => 1,
                            'unit_cents' => (int)round($itemAmount * 100), // Preserve sign (negative for discounts)
                            'total_cents' => (int)round($itemAmount * 100),
                            'kind' => $itemAmount < 0 ? 'credit' : (isset($item['is_custom']) && $item['is_custom'] ? 'credit' : 'recurring')
                        ]);
                    }
                    
                    if ($depositAmount > 0) {
                        $invoiceItemModel->createItem([
                            'invoice_id' => $invoiceId,
                            'student_fee_plan_id' => $studentFeePlanId,
                            'description' => $description . ' - Deposit',
                            'quantity' => 1,
                            'unit_cents' => (int)($depositAmount * 100),
                            'total_cents' => (int)($depositAmount * 100),
                            'kind' => 'deposit'
                        ]);
                    }
                    
                    if ($oneTimeFee > 0) {
                        $invoiceItemModel->createItem([
                            'invoice_id' => $invoiceId,
                            'student_fee_plan_id' => $studentFeePlanId,
                            'description' => $description . ' - One-time Fee',
                            'quantity' => 1,
                            'unit_cents' => (int)($oneTimeFee * 100),
                            'total_cents' => (int)($oneTimeFee * 100),
                            'kind' => 'onboarding'
                        ]);
                    }
                }
            }
            
            // Store items data in invoice metadata or notes field for reference (if items table doesn't exist)
            if (!$db->tableExists('t_invoice_item') && !empty($payload['items'])) {
                $itemsData = json_encode($payload['items']);
                // Store in notes field or we could add a metadata field later
                // For now, we'll just calculate the total which is already stored in amount_due
            }

            $invoice = $this->invoiceModel->find($invoiceId);

            // Generate PDF to writable storage using lightweight helper
            $pdfGenerator = new SimplePdfGenerator();
            $pdfPath = $pdfGenerator->generateInvoicePdf($invoice, $student);

            $this->invoiceModel->update($invoiceId, ['pdf_path' => $pdfPath]);
            
            // Return full URL for PDF download
            $baseUrl = rtrim(base_url(), '/');
            $invoice['pdf_path'] = $baseUrl . '/crm/fees/invoice/download/' . $invoiceId;

            // Send email if requested
            if (!empty($payload['send_email'])) {
                // TODO: Implement email sending
            }

            return $this->successResponse($invoice, 'Invoice generated');
        } catch (\Throwable $e) {
            return $this->errorResponse('Unable to generate invoice: ' . $e->getMessage());
        }
    }

    public function listInvoices(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);
            if (empty($payload['student_id'])) {
                return $this->errorResponse('student_id is required');
            }

            $studentId = (int) $payload['student_id'];
            $invoices = $this->invoiceModel->listForStudent($studentId);

            // Load invoice items for each invoice (only if table exists)
            $db = Database::connect();
            if ($db->tableExists('t_invoice_item')) {
                $invoiceItemModel = new \App\Models\InvoiceItemModel();
                foreach ($invoices as &$invoice) {
                    try {
                        $invoice['items'] = $invoiceItemModel->getInvoiceItems($invoice['id']);
                    } catch (\Throwable $e) {
                        // If there's an error loading items, just set empty array
                        $invoice['items'] = [];
                    }
                }
            } else {
                // If items table doesn't exist, just set empty items array
                foreach ($invoices as &$invoice) {
                    $invoice['items'] = [];
                }
            }

            // Update PDF paths to full URLs
            $baseUrl = rtrim(base_url(), '/');
            foreach ($invoices as &$invoice) {
                if (!empty($invoice['pdf_path'])) {
                    $invoice['pdf_path'] = $baseUrl . '/crm/fees/invoice/download/' . $invoice['id'];
                }
            }

            return $this->successResponse($invoices, 'Invoices retrieved');
        } catch (\Throwable $e) {
            return $this->errorResponse('Unable to list invoices: ' . $e->getMessage());
        }
    }

    public function getInvoice(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);
            if (empty($payload['invoice_id'])) {
                return $this->errorResponse('invoice_id is required');
            }

            $invoiceId = (int) $payload['invoice_id'];
            $invoice = $this->invoiceModel->find($invoiceId);

            if (!$invoice) {
                return $this->errorResponse('Invoice not found', 404);
            }

            // Load invoice items if table exists
            $db = Database::connect();
            if ($db->tableExists('t_invoice_item')) {
                $invoiceItemModel = new \App\Models\InvoiceItemModel();
                try {
                    $invoice['items'] = $invoiceItemModel->getInvoiceItems($invoiceId);
                } catch (\Throwable $e) {
                    $invoice['items'] = [];
                }
            } else {
                $invoice['items'] = [];
            }

            // Update PDF path to full URL
            if (!empty($invoice['pdf_path'])) {
                $baseUrl = rtrim(base_url(), '/');
                $invoice['pdf_path'] = $baseUrl . '/crm/fees/invoice/download/' . $invoice['id'];
            }

            return $this->successResponse($invoice, 'Invoice retrieved');
        } catch (\Throwable $e) {
            return $this->errorResponse('Unable to get invoice: ' . $e->getMessage());
        }
    }

    /**
     * Download invoice PDF
     */
    public function downloadInvoice(): ResponseInterface
    {
        try {
            $invoiceId = (int) ($this->request->getUri()->getSegment(5) ?? 0);
            
            if (!$invoiceId) {
                return $this->errorResponse('Invoice ID is required', 400);
            }

            $invoice = $this->invoiceModel->find($invoiceId);
            if (!$invoice) {
                return $this->errorResponse('Invoice not found', 404);
            }

            if (empty($invoice['pdf_path'])) {
                return $this->errorResponse('PDF not found for this invoice', 404);
            }

            // pdf_path is stored as 'invoices/filename.pdf' relative to writable
            $pdfPath = WRITEPATH . $invoice['pdf_path'];
            
            if (!file_exists($pdfPath)) {
                return $this->errorResponse('PDF file not found', 404);
            }

            // Return file download
            return $this->response->download($pdfPath, null);
        } catch (\Throwable $e) {
            return $this->errorResponse('Unable to download invoice: ' . $e->getMessage());
        }
    }

    /**
     * Generate a unique invoice number
     * Format: INV-YYYYMMDD-HHMMSS-STUDENTID-RANDOM
     * Includes timestamp (HHMMSS) to make same-day invoices identifiable
     */
    private function generateUniqueInvoiceNumber(int $studentId): string
    {
        $db = Database::connect();
        $maxAttempts = 10;
        $attempt = 0;
        
        do {
            // Include full timestamp: YYYYMMDD-HHMMSS for better identification
            $date = date('Ymd');
            $time = date('His'); // HHMMSS format
            $random = str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
            $invoiceNumber = sprintf('INV-%s-%s-%d-%s', $date, $time, $studentId, $random);
            
            // Check if this invoice number already exists
            $exists = $db->table('invoices')
                ->where('invoice_number', $invoiceNumber)
                ->countAllResults() > 0;
            
            if (!$exists) {
                return $invoiceNumber;
            }
            
            $attempt++;
            // Add microsecond-based suffix if we're having collisions
            if ($attempt >= 5) {
                $microseconds = substr(str_replace('.', '', microtime(true)), -6);
                $invoiceNumber = sprintf('INV-%s-%d-%s-%s', $timestamp, $studentId, $random, $microseconds);
                $exists = $db->table('invoices')
                    ->where('invoice_number', $invoiceNumber)
                    ->countAllResults() > 0;
                if (!$exists) {
                    return $invoiceNumber;
                }
            }
        } while ($attempt < $maxAttempts);
        
        // Fallback: use timestamp with microseconds and random
        return sprintf('INV-%s-%d-%s', date('YmdHis') . substr(microtime(true), -6), $studentId, uniqid('', true));
    }
}
