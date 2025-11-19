<?php

namespace App\Services\Payment;

/**
 * CSG Forte payment provider implementation
 */
class ForteProvider implements PaymentInterface
{
    protected array $config;
    protected array $settings;
    protected string $apiUrl;

    public function __construct(array $credentials, array $settings = [])
    {
        $this->config = $credentials;
        $this->settings = $settings;
        $this->apiUrl = ($credentials['sandbox'] ?? false)
            ? 'https://sandbox.forte.net/api/v3'
            : 'https://api.forte.net/v3';
    }

    /**
     * Make authenticated API request to Forte
     */
    protected function apiRequest(string $method, string $endpoint, array $data = []): array
    {
        $ch = curl_init();

        $url = $this->apiUrl . $endpoint;
        $auth = base64_encode($this->config['api_access_id'] . ':' . $this->config['api_secure_key']);

        $headers = [
            'Authorization: Basic ' . $auth,
            'Content-Type: application/json',
            'Accept: application/json',
            'X-Forte-Auth-Organization-Id: ' . $this->config['organization_id']
        ];

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_TIMEOUT => 30
        ]);

        if (!empty($data) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            log_message('error', 'Forte API error: ' . $error);
            return [
                'http_code' => 0,
                'error' => $error,
                'data' => null
            ];
        }

        return [
            'http_code' => $httpCode,
            'data' => json_decode($response, true)
        ];
    }

    /**
     * Create Forte customer
     */
    public function createCustomer(array $customerData): array
    {
        $payload = [
            'first_name' => $customerData['first_name'] ?? '',
            'last_name' => $customerData['last_name'] ?? '',
            'company_name' => $customerData['company_name'] ?? '',
            'customer_id' => (string)($customerData['student_id'] ?? uniqid('cust_'))
        ];

        if (isset($customerData['email'])) {
            $payload['default_billing'] = ['email' => $customerData['email']];
        }

        $response = $this->apiRequest(
            'POST',
            '/organizations/' . $this->config['organization_id'] . '/locations/' . $this->config['location_id'] . '/customers',
            $payload
        );

        if ($response['http_code'] === 201 && isset($response['data']['customer_token'])) {
            return [
                'success' => true,
                'customer_id' => $response['data']['customer_token'],
                'data' => $response['data']
            ];
        }

        return [
            'success' => false,
            'error' => $response['data']['response']['response_desc'] ?? 'Failed to create customer',
            'data' => $response['data'] ?? null
        ];
    }

    /**
     * Attach payment method to customer
     * In Forte, the paymentMethodToken is the customer_token returned from checkout.js
     */
    public function attachPaymentMethod(string $customerId, string $paymentMethodToken): array
    {
        // In Forte's model, the customer_token from save_token mode already includes the paymethod
        // We just return the token as the payment method ID
        return [
            'success' => true,
            'payment_method_id' => $paymentMethodToken,
            'type' => 'token',
            'customer_id' => $customerId
        ];
    }

    /**
     * Get payment method details
     * Since Forte's checkout.js creates a customer_token with embedded paymethod,
     * we retrieve customer details to get payment info
     */
    public function getPaymentMethod(string $paymentMethodId): array
    {
        // The payment_method_id is the customer_token from Forte
        $response = $this->apiRequest(
            'GET',
            '/organizations/' . $this->config['organization_id'] . '/locations/' . $this->config['location_id'] . '/customers/' . $paymentMethodId
        );

        if ($response['http_code'] === 200) {
            $customer = $response['data'];

            // Extract payment method info from customer's default paymethod
            $displayInfo = ['type' => 'unknown'];

            if (isset($customer['default_paymethod'])) {
                $paymethod = $customer['default_paymethod'];
                if (isset($paymethod['card'])) {
                    $displayInfo = [
                        'type' => 'card',
                        'brand' => $paymethod['card']['card_type'] ?? 'unknown',
                        'last4' => $paymethod['card']['last_4'] ?? '****',
                        'exp_month' => $paymethod['card']['expire_month'] ?? null,
                        'exp_year' => $paymethod['card']['expire_year'] ?? null
                    ];
                } elseif (isset($paymethod['echeck'])) {
                    $displayInfo = [
                        'type' => 'ach',
                        'bank_name' => $paymethod['echeck']['bank_name'] ?? 'Unknown Bank',
                        'last4' => $paymethod['echeck']['last_4'] ?? '****',
                        'account_type' => $paymethod['echeck']['account_type'] ?? 'checking'
                    ];
                }
            }

            return [
                'success' => true,
                'payment_method_id' => $paymentMethodId,
                'display_info' => $displayInfo,
                'data' => $customer
            ];
        }

        return [
            'success' => false,
            'error' => $response['data']['response']['response_desc'] ?? 'Failed to get payment method',
            'display_info' => ['type' => 'unknown']
        ];
    }

    /**
     * Charge using customer token
     */
    public function chargeToken(string $paymentMethodId, float $amount, array $options = []): array
    {
        $payload = [
            'action' => 'sale',
            'authorization_amount' => $amount,
            'customer_token' => $paymentMethodId,
            'billing_address' => $options['billing_address'] ?? null,
            'order_number' => $options['order_number'] ?? 'ORD-' . time(),
            'reference_id' => $options['reference_id'] ?? null
        ];

        if (isset($options['description'])) {
            $payload['notes'] = $options['description'];
        }

        $response = $this->apiRequest(
            'POST',
            '/organizations/' . $this->config['organization_id'] . '/locations/' . $this->config['location_id'] . '/transactions',
            $payload
        );

        if ($response['http_code'] === 201) {
            $txn = $response['data'];
            $success = isset($txn['response']['response_code']) && $txn['response']['response_code'] === 'A01';

            return [
                'success' => $success,
                'transaction_id' => $txn['transaction_id'] ?? null,
                'status' => $txn['response']['response_code'] ?? 'failed',
                'message' => $txn['response']['response_desc'] ?? '',
                'amount' => $txn['authorization_amount'] ?? $amount,
                'data' => $txn
            ];
        }

        return [
            'success' => false,
            'error' => $response['data']['response']['response_desc'] ?? 'Transaction failed',
            'error_code' => $response['data']['response']['response_code'] ?? null,
            'data' => $response['data'] ?? null
        ];
    }

    /**
     * Authorize payment (hold funds)
     */
    public function authorizePayment(string $paymentMethodId, float $amount, array $options = []): array
    {
        $payload = [
            'action' => 'authorize',
            'authorization_amount' => $amount,
            'customer_token' => $paymentMethodId,
            'order_number' => $options['order_number'] ?? 'AUTH-' . time()
        ];

        $response = $this->apiRequest(
            'POST',
            '/organizations/' . $this->config['organization_id'] . '/locations/' . $this->config['location_id'] . '/transactions',
            $payload
        );

        if ($response['http_code'] === 201) {
            $txn = $response['data'];
            $success = isset($txn['response']['response_code']) && $txn['response']['response_code'] === 'A01';

            return [
                'success' => $success,
                'authorization_id' => $txn['transaction_id'] ?? null,
                'transaction_id' => $txn['transaction_id'] ?? null,
                'amount' => $txn['authorization_amount'] ?? $amount,
                'data' => $txn
            ];
        }

        return [
            'success' => false,
            'error' => $response['data']['response']['response_desc'] ?? 'Authorization failed'
        ];
    }

    /**
     * Capture authorized payment
     */
    public function capturePayment(string $authorizationId, ?float $amount = null): array
    {
        $payload = [
            'action' => 'capture',
            'authorization_code' => $authorizationId
        ];

        if ($amount !== null) {
            $payload['authorization_amount'] = $amount;
        }

        $response = $this->apiRequest(
            'POST',
            '/organizations/' . $this->config['organization_id'] . '/locations/' . $this->config['location_id'] . '/transactions',
            $payload
        );

        if ($response['http_code'] === 201) {
            $txn = $response['data'];
            return [
                'success' => isset($txn['response']['response_code']) && $txn['response']['response_code'] === 'A01',
                'transaction_id' => $txn['transaction_id'] ?? null,
                'amount' => $txn['authorization_amount'] ?? $amount,
                'data' => $txn
            ];
        }

        return [
            'success' => false,
            'error' => $response['data']['response']['response_desc'] ?? 'Capture failed'
        ];
    }

    /**
     * Refund transaction
     */
    public function refund(string $transactionId, ?float $amount = null): array
    {
        $payload = [
            'action' => 'refund',
            'original_transaction_id' => $transactionId
        ];

        if ($amount !== null) {
            $payload['authorization_amount'] = $amount;
        }

        $response = $this->apiRequest(
            'POST',
            '/organizations/' . $this->config['organization_id'] . '/locations/' . $this->config['location_id'] . '/transactions',
            $payload
        );

        if ($response['http_code'] === 201) {
            $txn = $response['data'];
            return [
                'success' => isset($txn['response']['response_code']) && $txn['response']['response_code'] === 'A01',
                'refund_id' => $txn['transaction_id'] ?? null,
                'amount' => $txn['authorization_amount'] ?? $amount,
                'data' => $txn
            ];
        }

        return [
            'success' => false,
            'error' => $response['data']['response']['response_desc'] ?? 'Refund failed'
        ];
    }

    /**
     * Void transaction
     */
    public function voidTransaction(string $transactionId): array
    {
        $payload = [
            'action' => 'void',
            'original_transaction_id' => $transactionId
        ];

        $response = $this->apiRequest(
            'POST',
            '/organizations/' . $this->config['organization_id'] . '/locations/' . $this->config['location_id'] . '/transactions',
            $payload
        );

        if ($response['http_code'] === 201) {
            $txn = $response['data'];
            return [
                'success' => isset($txn['response']['response_code']) && $txn['response']['response_code'] === 'A01',
                'data' => $txn
            ];
        }

        return [
            'success' => false,
            'error' => $response['data']['response']['response_desc'] ?? 'Void failed'
        ];
    }

    /**
     * Delete payment method (delete customer)
     */
    public function deletePaymentMethod(string $paymentMethodId): bool
    {
        $response = $this->apiRequest(
            'DELETE',
            '/organizations/' . $this->config['organization_id'] . '/locations/' . $this->config['location_id'] . '/customers/' . $paymentMethodId
        );

        return $response['http_code'] === 204 || $response['http_code'] === 200;
    }

    /**
     * Test connection
     */
    public function testConnection(): array
    {
        $response = $this->apiRequest(
            'GET',
            '/organizations/' . $this->config['organization_id']
        );

        if ($response['http_code'] === 200) {
            return [
                'success' => true,
                'message' => 'Connection successful',
                'data' => [
                    'organization_name' => $response['data']['organization_name'] ?? null
                ]
            ];
        }

        return [
            'success' => false,
            'message' => $response['data']['response']['response_desc'] ?? 'Connection failed'
        ];
    }

    /**
     * Get transaction details
     */
    public function getTransaction(string $transactionId): array
    {
        $response = $this->apiRequest(
            'GET',
            '/organizations/' . $this->config['organization_id'] . '/locations/' . $this->config['location_id'] . '/transactions/' . $transactionId
        );

        if ($response['http_code'] === 200) {
            return [
                'success' => true,
                'data' => $response['data']
            ];
        }

        return [
            'success' => false,
            'error' => 'Transaction not found'
        ];
    }
}
