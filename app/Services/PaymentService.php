<?php

namespace App\Services;

use App\Models\SchoolProviderConfigModel;
use App\Models\StudentPaymentMethodModel;
use App\Models\PaymentTransactionModel;
use App\Models\ProviderUsageLogModel;
use App\Libraries\Encryption\ProviderCredentialEncryption;
use App\Services\Payment\StripeProvider;
use App\Services\Payment\ForteProvider;
use App\Services\Payment\PaymentInterface;

/**
 * PaymentService - Main orchestrator for payment operations
 */
class PaymentService
{
    protected SchoolProviderConfigModel $providerConfigModel;
    protected StudentPaymentMethodModel $paymentMethodModel;
    protected PaymentTransactionModel $transactionModel;
    protected ProviderUsageLogModel $usageLogModel;
    protected ?ProviderCredentialEncryption $encryption = null;

    public function __construct()
    {
        $this->providerConfigModel = new SchoolProviderConfigModel();
        $this->paymentMethodModel = new StudentPaymentMethodModel();
        $this->transactionModel = new PaymentTransactionModel();
        $this->usageLogModel = new ProviderUsageLogModel();
        // Encryption is initialized lazily when needed
    }

    /**
     * Get encryption instance (lazy initialization)
     */
    protected function getEncryption(): ProviderCredentialEncryption
    {
        if ($this->encryption === null) {
            $this->encryption = new ProviderCredentialEncryption();
        }
        return $this->encryption;
    }

    /**
     * Get payment provider instance for school
     */
    protected function getProvider(int $schoolId): PaymentInterface
    {
        $config = $this->providerConfigModel->getEnabledProvider($schoolId, 'payment', 1);

        if (!$config) {
            throw new \RuntimeException('No payment provider configured for this school');
        }

        return $this->instantiateProvider($config);
    }

    /**
     * Instantiate provider from configuration
     */
    protected function instantiateProvider(array $config): PaymentInterface
    {
        $credentials = $this->getEncryption()->decryptCredentials($config['credentials']);
        $settings = isset($config['settings']) ? json_decode($config['settings'], true) : [];

        switch ($config['provider_code']) {
            case 'stripe':
                return new StripeProvider($credentials, $settings ?? []);
            case 'forte':
                return new ForteProvider($credentials, $settings ?? []);
            default:
                throw new \RuntimeException('Unsupported payment provider: ' . $config['provider_code']);
        }
    }

    /**
     * Initialize payment for enrollment (get parameters for frontend tokenization)
     */
    public function initializePayment(int $schoolId, int $studentId, float $amount = 0, array $metadata = []): array
    {
        $config = $this->providerConfigModel->getEnabledProvider($schoolId, 'payment', 1);

        if (!$config) {
            throw new \RuntimeException('No payment provider configured');
        }

        $credentials = $this->getEncryption()->decryptCredentials($config['credentials']);
        $settings = isset($config['settings']) ? json_decode($config['settings'], true) : [];

        $result = [
            'provider' => $config['provider_code'],
            'provider_name' => $config['provider_name'],
            'student_id' => $studentId,
            'school_id' => $schoolId
        ];

        switch ($config['provider_code']) {
            case 'forte':
                $result = array_merge($result, $this->initializeForte($credentials, $settings ?? [], $studentId, $amount, $metadata));
                break;
            case 'stripe':
                $result = array_merge($result, $this->initializeStripe($credentials, $settings ?? [], $studentId, $amount, $metadata));
                break;
        }

        return $result;
    }

    /**
     * Initialize Forte payment
     */
    protected function initializeForte(array $credentials, array $settings, int $studentId, float $amount, array $metadata): array
    {
        $utcTime = gmdate("YmdHis");
        $orderNumber = 'STU-' . $studentId . '-' . time();

        // Build signature string for tokenization
        $signatureString = $credentials['api_access_id']
            . $credentials['location_id']
            . 'save_token'
            . '3.0'
            . $utcTime
            . $orderNumber
            . '';  // total_amount empty for tokenization

        $signature = hash_hmac('sha256', $signatureString, $credentials['api_secure_key']);

        return [
            'api_access_id' => $credentials['api_access_id'],
            'location_id' => $credentials['location_id'],
            'organization_id' => $credentials['organization_id'],
            'method' => 'save_token',
            'version_number' => '3.0',
            'utc_time' => $utcTime,
            'signature' => $signature,
            'order_number' => $orderNumber,
            'allowed_types' => $credentials['allowed_types'] ?? 'visa,mc,amex,disc,echeck',
            'checkout_url' => ($credentials['sandbox'] ?? false)
                ? 'https://sandbox.forte.net/checkout/v1/js'
                : 'https://checkout.forte.net/v1/js',
            'swipe' => $credentials['swipe'] ?? 'false'
        ];
    }

    /**
     * Initialize Stripe payment
     */
    protected function initializeStripe(array $credentials, array $settings, int $studentId, float $amount, array $metadata): array
    {
        return [
            'publishable_key' => $credentials['publishable_key'],
            'client_secret' => null
        ];
    }

    /**
     * Save payment method token from frontend callback
     */
    public function savePaymentMethod(array $data): array
    {
        $config = $this->providerConfigModel->getEnabledProvider($data['school_id'], 'payment', 1);

        if (!$config) {
            return ['success' => false, 'error' => 'No payment provider configured'];
        }

        try {
            // Extract token based on provider
            $tokenData = $this->extractTokenData($config['provider_code'], $data['payment_token']);

            // Get payment method details from gateway
            $provider = $this->instantiateProvider($config);
            $methodDetails = $provider->getPaymentMethod($tokenData['payment_method_id']);

            // Use default display info if provider call fails
            $displayInfo = $methodDetails['success']
                ? ($methodDetails['display_info'] ?? ['type' => 'unknown'])
                : $this->extractDisplayInfoFromToken($config['provider_code'], $data['payment_token']);

            // Encrypt token for storage
            $encryptedToken = $this->getEncryption()->encryptCredentials([
                'payment_method_id' => $tokenData['payment_method_id'],
                'customer_id' => $tokenData['customer_id'] ?? null,
                'raw_response' => $tokenData['raw_data'] ?? []
            ]);

            // Set existing default to false if this is default
            if ($data['is_default'] ?? true) {
                $this->paymentMethodModel->where('student_id', $data['student_id'])
                    ->set(['is_default' => false])
                    ->update();
            }

            // Insert payment method
            $paymentMethodId = $this->paymentMethodModel->insert([
                'student_id' => $data['student_id'],
                'school_id' => $data['school_id'],
                'provider_id' => $config['provider_id'],
                'payment_token' => $encryptedToken,
                'token_type' => $displayInfo['type'] ?? 'card',
                'display_info' => json_encode($displayInfo),
                'gateway_customer_id' => $this->getEncryption()->encryptCredentials([
                    'customer_id' => $tokenData['customer_id'] ?? $tokenData['payment_method_id']
                ]),
                'gateway_payment_method_id' => $encryptedToken,
                'gateway_metadata' => json_encode($tokenData['raw_data'] ?? []),
                'is_default' => $data['is_default'] ?? true,
                'is_active' => true,
                'is_verified' => ($displayInfo['type'] ?? 'card') === 'card',
                'billing_address' => json_encode($data['billing_address'] ?? []),
                'authorized_at' => date('Y-m-d H:i:s'),
                'authorized_by' => $data['user_id'] ?? null,
                'authorization_ip' => $data['ip_address'] ?? null,
                'authorization_user_agent' => $data['user_agent'] ?? null,
                'expires_at' => $this->extractExpirationDate($displayInfo)
            ]);

            // Log usage
            $this->logUsage($data['school_id'], $config['provider_id'], 'payment_method_saved', 'success', [
                'student_id' => $data['student_id'],
                'payment_method_id' => $paymentMethodId
            ]);

            return [
                'success' => true,
                'payment_method_id' => $paymentMethodId,
                'display_info' => $displayInfo
            ];

        } catch (\Exception $e) {
            log_message('error', 'Failed to save payment method: ' . $e->getMessage());

            $this->logUsage($data['school_id'], $config['provider_id'] ?? 0, 'payment_method_saved', 'failed', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Failed to save payment method: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Extract token data based on provider type
     */
    protected function extractTokenData(string $providerCode, $paymentToken): array
    {
        if ($providerCode === 'forte') {
            $data = is_string($paymentToken) ? json_decode($paymentToken, true) : $paymentToken;
            return [
                'payment_method_id' => $data['customer_token'] ?? $data['paymethod_token'] ?? null,
                'customer_id' => $data['customer_token'] ?? null,
                'raw_data' => $data
            ];
        } elseif ($providerCode === 'stripe') {
            return [
                'payment_method_id' => is_string($paymentToken) ? $paymentToken : ($paymentToken['id'] ?? null),
                'customer_id' => is_array($paymentToken) ? ($paymentToken['customer'] ?? null) : null,
                'raw_data' => is_array($paymentToken) ? $paymentToken : []
            ];
        }

        return ['payment_method_id' => $paymentToken];
    }

    /**
     * Extract display info from token data
     */
    protected function extractDisplayInfoFromToken(string $providerCode, $paymentToken): array
    {
        if ($providerCode === 'forte') {
            $data = is_string($paymentToken) ? json_decode($paymentToken, true) : $paymentToken;

            if (isset($data['last_4'])) {
                return [
                    'type' => isset($data['card_type']) ? 'card' : 'ach',
                    'brand' => $data['card_type'] ?? 'unknown',
                    'last4' => $data['last_4'],
                    'exp_month' => $data['expire_month'] ?? null,
                    'exp_year' => $data['expire_year'] ?? null
                ];
            }
        }

        return ['type' => 'unknown'];
    }

    /**
     * Extract expiration date from display info
     */
    protected function extractExpirationDate(array $displayInfo): ?string
    {
        if (isset($displayInfo['exp_year']) && isset($displayInfo['exp_month'])) {
            return sprintf('%04d-%02d-01', $displayInfo['exp_year'], $displayInfo['exp_month']);
        }
        return null;
    }

    /**
     * Charge student using saved payment method
     */
    public function chargeStudent(int $studentId, float $amount, array $options = []): array
    {
        // Get default payment method
        $paymentMethod = $this->paymentMethodModel->getDefaultPaymentMethod($studentId);

        if (!$paymentMethod) {
            return [
                'success' => false,
                'error' => 'No payment method found for student'
            ];
        }

        return $this->chargePaymentMethod($paymentMethod['id'], $amount, $options);
    }

    /**
     * Charge specific payment method
     */
    public function chargePaymentMethod(int $paymentMethodId, float $amount, array $options = []): array
    {
        $paymentMethod = $this->paymentMethodModel->find($paymentMethodId);

        if (!$paymentMethod || !$paymentMethod['is_active']) {
            return ['success' => false, 'error' => 'Payment method not found or inactive'];
        }

        try {
            // Get provider config
            $config = $this->providerConfigModel->getConfig($paymentMethod['school_id'], $paymentMethod['provider_id']);
            if (!$config) {
                return ['success' => false, 'error' => 'Provider configuration not found'];
            }

            $provider = $this->instantiateProvider($config);

            // Decrypt token
            $token = $this->getEncryption()->decryptCredentials($paymentMethod['payment_token']);

            // Prepare charge options
            $chargeOptions = array_merge($options, [
                'customer_id' => $token['customer_id'] ?? null,
                'currency' => $options['currency'] ?? 'USD',
                'description' => $options['description'] ?? 'EdQuill Payment',
                'metadata' => array_merge($options['metadata'] ?? [], [
                    'student_id' => $paymentMethod['student_id'],
                    'school_id' => $paymentMethod['school_id'],
                    'payment_method_id' => $paymentMethodId
                ])
            ]);

            // Charge
            $startTime = microtime(true);
            $result = $provider->chargeToken($token['payment_method_id'], $amount, $chargeOptions);
            $responseTime = (int)((microtime(true) - $startTime) * 1000);

            // Record transaction
            $transactionData = [
                'school_id' => $paymentMethod['school_id'],
                'student_id' => $paymentMethod['student_id'],
                'payment_method_id' => $paymentMethodId,
                'provider_id' => $paymentMethod['provider_id'],
                'transaction_type' => 'charge',
                'amount' => $amount,
                'currency' => $chargeOptions['currency'],
                'gateway_transaction_id' => $result['transaction_id'] ?? ('failed_' . time()),
                'gateway_response' => json_encode($result['data'] ?? []),
                'status' => $result['success'] ? 'succeeded' : 'failed',
                'failure_code' => $result['decline_code'] ?? $result['error_code'] ?? null,
                'failure_message' => $result['error'] ?? null,
                'description' => $chargeOptions['description'],
                'metadata' => json_encode($chargeOptions['metadata']),
                'invoice_id' => $options['invoice_id'] ?? null,
                'enrollment_id' => $options['enrollment_id'] ?? null,
                'course_id' => $options['course_id'] ?? null,
                'processed_by' => $options['user_id'] ?? null,
                'processed_by_ip' => $options['ip_address'] ?? null
            ];

            $transactionId = $this->transactionModel->insert($transactionData);

            // Update payment method usage
            if ($result['success']) {
                $this->paymentMethodModel->updateUsageStats($paymentMethodId, $amount);
            }

            // Log usage
            $this->logUsage(
                $paymentMethod['school_id'],
                $paymentMethod['provider_id'],
                'payment_charged',
                $result['success'] ? 'success' : 'failed',
                [
                    'transaction_id' => $transactionId,
                    'amount' => $amount,
                    'gateway_transaction_id' => $result['transaction_id'] ?? null,
                    'response_time_ms' => $responseTime
                ]
            );

            return array_merge($result, [
                'internal_transaction_id' => $transactionId
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Payment charge failed: ' . $e->getMessage());

            // Log failed transaction
            $this->transactionModel->insert([
                'school_id' => $paymentMethod['school_id'],
                'student_id' => $paymentMethod['student_id'],
                'payment_method_id' => $paymentMethodId,
                'provider_id' => $paymentMethod['provider_id'],
                'transaction_type' => 'charge',
                'amount' => $amount,
                'currency' => $options['currency'] ?? 'USD',
                'gateway_transaction_id' => 'error_' . time(),
                'status' => 'failed',
                'failure_message' => $e->getMessage(),
                'processed_by' => $options['user_id'] ?? null
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Record manual payment (cash, check, zelle, other)
     */
    public function recordManualPayment(int $studentId, float $amount, string $paymentMethod, array $options = []): array
    {
        // Get school_id from student or options
        $schoolId = $options['school_id'] ?? null;
        if (!$schoolId) {
            // Try to get from student_user table
            $db = \Config\Database::connect();
            $student = $db->table('student_user')
                ->where('student_id', $studentId)
                ->get()
                ->getRowArray();
            $schoolId = $student['school_id'] ?? null;
        }

        if (!$schoolId) {
            return ['success' => false, 'error' => 'School ID not found'];
        }

        // Get or create a "manual" provider for recording manual payments
        // This is a special provider that doesn't require gateway integration
        $providerModel = new \App\Models\ProviderModel();
        $providerTypeModel = new \App\Models\ProviderTypeModel();
        
        // Get payment provider type
        $paymentType = $providerTypeModel->getByCode('payment');
        if (!$paymentType) {
            return ['success' => false, 'error' => 'Payment provider type not found'];
        }

        $manualProvider = $providerModel->where('code', 'manual')->first();
        
        if (!$manualProvider) {
            // Create manual provider if it doesn't exist
            $providerId = $providerModel->insert([
                'provider_type_id' => $paymentType['id'],
                'code' => 'manual',
                'name' => 'Manual Payment',
                'is_active' => 1
            ], true);
        } else {
            $providerId = $manualProvider['id'];
        }

        // Build transaction data
        $transactionData = [
            'school_id' => $schoolId,
            'student_id' => $studentId,
            'payment_method_id' => null, // No stored payment method for manual payments
            'provider_id' => $providerId,
            'transaction_type' => 'charge',
            'amount' => $amount,
            'currency' => $options['currency'] ?? 'USD',
            'gateway_transaction_id' => 'manual_' . time() . '_' . uniqid(),
            'gateway_response' => json_encode([
                'payment_method' => $paymentMethod,
                'check_number' => $options['check_number'] ?? null,
                'payment_method_details' => $options['payment_method_details'] ?? null,
                'payment_notes' => $options['payment_notes'] ?? null
            ]),
            'status' => 'succeeded', // Manual payments are always considered successful
            'description' => $options['description'] ?? "Manual payment via {$paymentMethod}",
            'metadata' => json_encode(array_merge($options['metadata'] ?? [], [
                'payment_method' => $paymentMethod,
                'check_number' => $options['check_number'] ?? null,
                'payment_method_details' => $options['payment_method_details'] ?? null,
                'payment_notes' => $options['payment_notes'] ?? null,
                'manual_payment' => true
            ])),
            'invoice_id' => $options['invoice_id'] ?? null,
            'enrollment_id' => $options['enrollment_id'] ?? null,
            'course_id' => $options['course_id'] ?? null,
            'internal_notes' => $options['payment_notes'] ?? null,
            'processed_by' => $options['user_id'] ?? null,
            'processed_by_ip' => $options['ip_address'] ?? null
        ];

        try {
            $transactionId = $this->transactionModel->insert($transactionData);

            // Log usage
            $this->logUsage(
                $schoolId,
                $providerId,
                'manual_payment_recorded',
                'success',
                [
                    'transaction_id' => $transactionId,
                    'amount' => $amount,
                    'payment_method' => $paymentMethod
                ]
            );

            return [
                'success' => true,
                'transaction_id' => $transactionData['gateway_transaction_id'],
                'internal_transaction_id' => $transactionId,
                'status' => 'succeeded',
                'amount' => $amount
            ];
        } catch (\Exception $e) {
            log_message('error', 'Manual payment recording failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to record manual payment: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Refund a transaction
     */
    public function refundTransaction(int $transactionId, ?float $amount = null, ?string $reason = null): array
    {
        $transaction = $this->transactionModel->find($transactionId);

        if (!$transaction) {
            return ['success' => false, 'error' => 'Transaction not found'];
        }

        if ($transaction['status'] !== 'succeeded') {
            return ['success' => false, 'error' => 'Can only refund successful transactions'];
        }

        // Check refund amount
        $alreadyRefunded = $this->transactionModel->getTotalRefunded($transactionId);
        $maxRefund = $transaction['amount'] - $alreadyRefunded;

        if ($amount === null) {
            $amount = $maxRefund;
        }

        if ($amount > $maxRefund) {
            return ['success' => false, 'error' => 'Refund amount exceeds available balance'];
        }

        try {
            $config = $this->providerConfigModel->getConfig($transaction['school_id'], $transaction['provider_id']);
            $provider = $this->instantiateProvider($config);

            $result = $provider->refund($transaction['gateway_transaction_id'], $amount);

            // Record refund transaction
            $refundId = $this->transactionModel->insert([
                'school_id' => $transaction['school_id'],
                'student_id' => $transaction['student_id'],
                'payment_method_id' => $transaction['payment_method_id'],
                'provider_id' => $transaction['provider_id'],
                'transaction_type' => 'refund',
                'amount' => $amount,
                'currency' => $transaction['currency'],
                'gateway_transaction_id' => $result['refund_id'] ?? ('refund_' . time()),
                'gateway_response' => json_encode($result['data'] ?? []),
                'status' => $result['success'] ? 'succeeded' : 'failed',
                'failure_message' => $result['error'] ?? null,
                'parent_transaction_id' => $transactionId,
                'refund_reason' => $reason
            ]);

            // Update original transaction
            if ($result['success']) {
                $newRefundedAmount = $alreadyRefunded + $amount;
                $newStatus = $newRefundedAmount >= $transaction['amount'] ? 'refunded' : 'partially_refunded';

                $this->transactionModel->update($transactionId, [
                    'refunded_amount' => $newRefundedAmount,
                    'status' => $newStatus,
                    'refund_reason' => $reason
                ]);
            }

            return array_merge($result, [
                'refund_transaction_id' => $refundId
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Refund failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get student's payment methods
     */
    public function getStudentPaymentMethods(int $studentId, ?int $schoolId = null): array
    {
        $methods = $this->paymentMethodModel->getStudentPaymentMethods($studentId);

        // Filter by school if provided
        if ($schoolId) {
            $methods = array_filter($methods, function($method) use ($schoolId) {
                return $method['school_id'] == $schoolId;
            });
            $methods = array_values($methods);
        }

        // Decode display_info for each method
        foreach ($methods as &$method) {
            $method['display_info'] = json_decode($method['display_info'], true) ?? [];
            // Never return the actual token
            unset($method['payment_token']);
            unset($method['gateway_customer_id']);
            unset($method['gateway_payment_method_id']);
            unset($method['gateway_metadata']);
        }

        return $methods;
    }

    /**
     * Delete payment method
     */
    public function deletePaymentMethod(int $paymentMethodId, ?int $deletedBy = null): array
    {
        $method = $this->paymentMethodModel->find($paymentMethodId);

        if (!$method) {
            return ['success' => false, 'error' => 'Payment method not found'];
        }

        try {
            // Optionally delete from gateway
            $config = $this->providerConfigModel->getConfig($method['school_id'], $method['provider_id']);
            if ($config) {
                $provider = $this->instantiateProvider($config);
                $token = $this->getEncryption()->decryptCredentials($method['payment_token']);
                $provider->deletePaymentMethod($token['payment_method_id']);
            }
        } catch (\Exception $e) {
            // Log but don't fail - we still want to deactivate locally
            log_message('warning', 'Failed to delete payment method from gateway: ' . $e->getMessage());
        }

        // Soft delete locally
        $this->paymentMethodModel->softDelete($paymentMethodId, $deletedBy);

        return ['success' => true];
    }

    /**
     * Log provider usage
     */
    protected function logUsage(int $schoolId, int $providerId, string $action, string $status, array $data = []): void
    {
        $this->usageLogModel->logAction([
            'school_id' => $schoolId,
            'provider_id' => $providerId,
            'action_type' => $action,
            'status' => $status,
            'request_data' => $data,
            'related_id' => $data['student_id'] ?? $data['transaction_id'] ?? null,
            'related_type' => isset($data['student_id']) ? 'student' : (isset($data['transaction_id']) ? 'transaction' : null),
            'response_time_ms' => $data['response_time_ms'] ?? null
        ]);
    }
}
