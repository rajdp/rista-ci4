<?php

namespace App\Services\Payment;

/**
 * Interface for payment provider implementations
 */
interface PaymentInterface
{
    /**
     * Create a customer in the payment gateway
     *
     * @param array $customerData Customer information
     * @return array ['success' => bool, 'customer_id' => string, 'data' => array]
     */
    public function createCustomer(array $customerData): array;

    /**
     * Attach a payment method to a customer
     *
     * @param string $customerId Gateway customer ID
     * @param string $paymentMethodToken Token from frontend tokenization
     * @return array ['success' => bool, 'payment_method_id' => string, 'type' => string, ...]
     */
    public function attachPaymentMethod(string $customerId, string $paymentMethodToken): array;

    /**
     * Get payment method details
     *
     * @param string $paymentMethodId Payment method ID
     * @return array ['success' => bool, 'display_info' => array, ...]
     */
    public function getPaymentMethod(string $paymentMethodId): array;

    /**
     * Charge using a saved payment method token
     *
     * @param string $paymentMethodId Payment method ID
     * @param float $amount Amount to charge
     * @param array $options Additional options (description, metadata, etc.)
     * @return array ['success' => bool, 'transaction_id' => string, 'status' => string, ...]
     */
    public function chargeToken(string $paymentMethodId, float $amount, array $options = []): array;

    /**
     * Authorize payment (hold funds without capture)
     *
     * @param string $paymentMethodId Payment method ID
     * @param float $amount Amount to authorize
     * @param array $options Additional options
     * @return array ['success' => bool, 'authorization_id' => string, ...]
     */
    public function authorizePayment(string $paymentMethodId, float $amount, array $options = []): array;

    /**
     * Capture an authorized payment
     *
     * @param string $authorizationId Authorization ID
     * @param float|null $amount Amount to capture (null for full amount)
     * @return array ['success' => bool, 'transaction_id' => string, ...]
     */
    public function capturePayment(string $authorizationId, ?float $amount = null): array;

    /**
     * Refund a transaction
     *
     * @param string $transactionId Transaction ID to refund
     * @param float|null $amount Amount to refund (null for full refund)
     * @return array ['success' => bool, 'refund_id' => string, ...]
     */
    public function refund(string $transactionId, ?float $amount = null): array;

    /**
     * Void/cancel a transaction
     *
     * @param string $transactionId Transaction ID to void
     * @return array ['success' => bool, ...]
     */
    public function voidTransaction(string $transactionId): array;

    /**
     * Delete/detach a payment method
     *
     * @param string $paymentMethodId Payment method ID
     * @return bool Success status
     */
    public function deletePaymentMethod(string $paymentMethodId): bool;

    /**
     * Test connection to the payment gateway
     *
     * @return array ['success' => bool, 'message' => string, ...]
     */
    public function testConnection(): array;

    /**
     * Get transaction details
     *
     * @param string $transactionId Transaction ID
     * @return array Transaction details
     */
    public function getTransaction(string $transactionId): array;
}
