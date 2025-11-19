<?php

namespace App\Services\Payment;

/**
 * Stripe payment provider implementation
 */
class StripeProvider implements PaymentInterface
{
    protected array $config;
    protected array $settings;
    protected string $apiUrl = 'https://api.stripe.com/v1';

    public function __construct(array $credentials, array $settings = [])
    {
        $this->config = $credentials;
        $this->settings = $settings;
    }

    /**
     * Make authenticated API request to Stripe
     */
    protected function apiRequest(string $method, string $endpoint, array $data = []): array
    {
        $ch = curl_init();

        $url = $this->apiUrl . $endpoint;

        $headers = [
            'Authorization: Bearer ' . $this->config['secret_key'],
            'Content-Type: application/x-www-form-urlencoded',
            'Stripe-Version: 2023-10-16'
        ];

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_TIMEOUT => 30
        ]);

        if (!empty($data) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            log_message('error', 'Stripe API error: ' . $error);
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
     * Create Stripe customer
     */
    public function createCustomer(array $customerData): array
    {
        $payload = [
            'email' => $customerData['email'] ?? null,
            'name' => trim(($customerData['first_name'] ?? '') . ' ' . ($customerData['last_name'] ?? '')),
            'phone' => $customerData['phone'] ?? null,
            'metadata' => [
                'student_id' => $customerData['student_id'] ?? null,
                'school_id' => $customerData['school_id'] ?? null
            ]
        ];

        // Remove null values
        $payload = array_filter($payload, fn($v) => $v !== null);

        $response = $this->apiRequest('POST', '/customers', $payload);

        if ($response['http_code'] >= 200 && $response['http_code'] < 300 && isset($response['data']['id'])) {
            return [
                'success' => true,
                'customer_id' => $response['data']['id'],
                'data' => $response['data']
            ];
        }

        return [
            'success' => false,
            'error' => $response['data']['error']['message'] ?? 'Failed to create customer',
            'data' => $response['data'] ?? null
        ];
    }

    /**
     * Attach payment method to customer
     */
    public function attachPaymentMethod(string $customerId, string $paymentMethodToken): array
    {
        // Attach payment method to customer
        $attachResponse = $this->apiRequest(
            'POST',
            '/payment_methods/' . $paymentMethodToken . '/attach',
            ['customer' => $customerId]
        );

        if ($attachResponse['http_code'] >= 200 && $attachResponse['http_code'] < 300) {
            // Set as default payment method
            $this->apiRequest('POST', '/customers/' . $customerId, [
                'invoice_settings' => [
                    'default_payment_method' => $paymentMethodToken
                ]
            ]);

            $pm = $attachResponse['data'];

            return [
                'success' => true,
                'payment_method_id' => $pm['id'],
                'type' => $pm['type'],
                'card' => $pm['card'] ?? null,
                'us_bank_account' => $pm['us_bank_account'] ?? null,
                'customer_id' => $customerId
            ];
        }

        return [
            'success' => false,
            'error' => $attachResponse['data']['error']['message'] ?? 'Failed to attach payment method'
        ];
    }

    /**
     * Get payment method details
     */
    public function getPaymentMethod(string $paymentMethodId): array
    {
        $response = $this->apiRequest('GET', '/payment_methods/' . $paymentMethodId);

        if ($response['http_code'] >= 200 && $response['http_code'] < 300) {
            $pm = $response['data'];

            $displayInfo = ['type' => $pm['type']];

            if ($pm['type'] === 'card' && isset($pm['card'])) {
                $displayInfo = [
                    'type' => 'card',
                    'brand' => $pm['card']['brand'],
                    'last4' => $pm['card']['last4'],
                    'exp_month' => $pm['card']['exp_month'],
                    'exp_year' => $pm['card']['exp_year']
                ];
            } elseif ($pm['type'] === 'us_bank_account' && isset($pm['us_bank_account'])) {
                $displayInfo = [
                    'type' => 'ach',
                    'bank_name' => $pm['us_bank_account']['bank_name'],
                    'last4' => $pm['us_bank_account']['last4'],
                    'account_type' => $pm['us_bank_account']['account_type']
                ];
            }

            return [
                'success' => true,
                'payment_method_id' => $pm['id'],
                'display_info' => $displayInfo,
                'data' => $pm
            ];
        }

        return [
            'success' => false,
            'error' => $response['data']['error']['message'] ?? 'Failed to get payment method',
            'display_info' => ['type' => 'unknown']
        ];
    }

    /**
     * Charge using payment method
     */
    public function chargeToken(string $paymentMethodId, float $amount, array $options = []): array
    {
        $payload = [
            'amount' => (int)($amount * 100), // Convert to cents
            'currency' => $options['currency'] ?? 'usd',
            'payment_method' => $paymentMethodId,
            'description' => $options['description'] ?? '',
            'confirm' => 'true',
            'off_session' => 'true' // For saved cards
        ];

        if (isset($options['customer_id'])) {
            $payload['customer'] = $options['customer_id'];
        }

        if (isset($options['metadata'])) {
            foreach ($options['metadata'] as $key => $value) {
                $payload['metadata[' . $key . ']'] = $value;
            }
        }

        // Set capture method
        $payload['capture_method'] = $options['capture_method'] ?? 'automatic';

        $response = $this->apiRequest('POST', '/payment_intents', $payload);

        if ($response['http_code'] >= 200 && $response['http_code'] < 300) {
            $intent = $response['data'];
            $success = in_array($intent['status'], ['succeeded', 'requires_capture']);

            return [
                'success' => $success,
                'transaction_id' => $intent['id'],
                'status' => $intent['status'],
                'amount' => $intent['amount'] / 100,
                'data' => $intent
            ];
        }

        return [
            'success' => false,
            'error' => $response['data']['error']['message'] ?? 'Charge failed',
            'error_code' => $response['data']['error']['code'] ?? null,
            'decline_code' => $response['data']['error']['decline_code'] ?? null
        ];
    }

    /**
     * Authorize payment
     */
    public function authorizePayment(string $paymentMethodId, float $amount, array $options = []): array
    {
        $options['capture_method'] = 'manual';
        return $this->chargeToken($paymentMethodId, $amount, $options);
    }

    /**
     * Capture authorized payment
     */
    public function capturePayment(string $authorizationId, ?float $amount = null): array
    {
        $payload = [];
        if ($amount !== null) {
            $payload['amount_to_capture'] = (int)($amount * 100);
        }

        $response = $this->apiRequest(
            'POST',
            '/payment_intents/' . $authorizationId . '/capture',
            $payload
        );

        if ($response['http_code'] >= 200 && $response['http_code'] < 300) {
            $intent = $response['data'];
            return [
                'success' => $intent['status'] === 'succeeded',
                'transaction_id' => $intent['id'],
                'amount' => $intent['amount'] / 100,
                'data' => $intent
            ];
        }

        return [
            'success' => false,
            'error' => $response['data']['error']['message'] ?? 'Capture failed'
        ];
    }

    /**
     * Refund transaction
     */
    public function refund(string $transactionId, ?float $amount = null): array
    {
        $payload = [
            'payment_intent' => $transactionId
        ];

        if ($amount !== null) {
            $payload['amount'] = (int)($amount * 100);
        }

        $response = $this->apiRequest('POST', '/refunds', $payload);

        if ($response['http_code'] >= 200 && $response['http_code'] < 300) {
            $refund = $response['data'];
            return [
                'success' => $refund['status'] === 'succeeded',
                'refund_id' => $refund['id'],
                'amount' => $refund['amount'] / 100,
                'data' => $refund
            ];
        }

        return [
            'success' => false,
            'error' => $response['data']['error']['message'] ?? 'Refund failed'
        ];
    }

    /**
     * Void/cancel payment
     */
    public function voidTransaction(string $transactionId): array
    {
        $response = $this->apiRequest(
            'POST',
            '/payment_intents/' . $transactionId . '/cancel'
        );

        if ($response['http_code'] >= 200 && $response['http_code'] < 300) {
            return [
                'success' => $response['data']['status'] === 'canceled',
                'data' => $response['data']
            ];
        }

        return [
            'success' => false,
            'error' => $response['data']['error']['message'] ?? 'Void failed'
        ];
    }

    /**
     * Delete payment method
     */
    public function deletePaymentMethod(string $paymentMethodId): bool
    {
        $response = $this->apiRequest(
            'POST',
            '/payment_methods/' . $paymentMethodId . '/detach'
        );

        return $response['http_code'] >= 200 && $response['http_code'] < 300;
    }

    /**
     * Test connection
     */
    public function testConnection(): array
    {
        $response = $this->apiRequest('GET', '/balance');

        if ($response['http_code'] >= 200 && $response['http_code'] < 300) {
            return [
                'success' => true,
                'message' => 'Connection successful',
                'data' => [
                    'available' => $response['data']['available'] ?? []
                ]
            ];
        }

        return [
            'success' => false,
            'message' => $response['data']['error']['message'] ?? 'Connection failed'
        ];
    }

    /**
     * Get transaction details
     */
    public function getTransaction(string $transactionId): array
    {
        $response = $this->apiRequest('GET', '/payment_intents/' . $transactionId);

        if ($response['http_code'] >= 200 && $response['http_code'] < 300) {
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

    /**
     * Create a SetupIntent for saving payment methods
     */
    public function createSetupIntent(string $customerId, array $options = []): array
    {
        $payload = [
            'customer' => $customerId,
            'payment_method_types' => ['card']
        ];

        if (isset($options['payment_method_types'])) {
            $payload['payment_method_types'] = $options['payment_method_types'];
        }

        $response = $this->apiRequest('POST', '/setup_intents', $payload);

        if ($response['http_code'] >= 200 && $response['http_code'] < 300) {
            return [
                'success' => true,
                'setup_intent_id' => $response['data']['id'],
                'client_secret' => $response['data']['client_secret'],
                'data' => $response['data']
            ];
        }

        return [
            'success' => false,
            'error' => $response['data']['error']['message'] ?? 'Failed to create setup intent'
        ];
    }
}
