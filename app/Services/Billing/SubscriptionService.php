<?php

namespace App\Services\Billing;

use App\Models\SubscriptionModel;
use App\Models\BillingInvoiceModel;
use App\Services\Billing\AuditService;

class SubscriptionService
{
    protected $subscriptionModel;
    protected $invoiceModel;
    protected $auditService;

    public function __construct()
    {
        $this->subscriptionModel = new SubscriptionModel();
        $this->invoiceModel = new BillingInvoiceModel();
        $this->auditService = new AuditService();
    }

    /**
     * Create a new subscription
     */
    public function createSubscription(array $data, ?int $actorId = null, ?string $actorRole = null): array
    {
        try {
            // Validate required fields
            if (empty($data['school_id']) || empty($data['student_id']) || empty($data['course_id'])) {
                return ['success' => false, 'error' => 'Missing required fields', 'data' => null];
            }

            // Compute next billing date
            $nextBillingDate = $this->computeNextBillingDate(
                $data['term'],
                $data['anchor_day'] ?? null,
                $data['start_date'] ?? date('Y-m-d')
            );

            $data['next_billing_date'] = $nextBillingDate;
            $data['status'] = $data['status'] ?? 'active';
            $data['autopay_enabled'] = $data['autopay_enabled'] ?? 0;

            // Insert subscription
            $subscriptionId = $this->subscriptionModel->insert($data);

            if (!$subscriptionId) {
                return ['success' => false, 'error' => 'Failed to create subscription', 'data' => null];
            }

            // Audit log
            $this->auditService->log(
                $data['school_id'],
                'subscription',
                $subscriptionId,
                'create',
                $actorId,
                $actorRole,
                ['term' => $data['term'], 'amount_cents' => $data['amount_cents']]
            );

            $subscription = $this->subscriptionModel->find($subscriptionId);

            return [
                'success' => true,
                'data' => $subscription,
                'error' => null
            ];

        } catch (\Exception $e) {
            log_message('error', 'SubscriptionService::createSubscription error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage(), 'data' => null];
        }
    }

    /**
     * Update subscription
     */
    public function updateSubscription(int $subscriptionId, array $data, ?int $actorId = null, ?string $actorRole = null): array
    {
        try {
            $subscription = $this->subscriptionModel->find($subscriptionId);
            if (!$subscription) {
                return ['success' => false, 'error' => 'Subscription not found', 'data' => null];
            }

            // Update
            $updated = $this->subscriptionModel->update($subscriptionId, $data);

            if (!$updated) {
                return ['success' => false, 'error' => 'Failed to update subscription', 'data' => null];
            }

            // Audit log
            $this->auditService->log(
                $subscription['school_id'],
                'subscription',
                $subscriptionId,
                'update',
                $actorId,
                $actorRole,
                $data
            );

            $subscription = $this->subscriptionModel->find($subscriptionId);

            return ['success' => true, 'data' => $subscription, 'error' => null];

        } catch (\Exception $e) {
            log_message('error', 'SubscriptionService::updateSubscription error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage(), 'data' => null];
        }
    }

    /**
     * Cancel subscription
     */
    public function cancelSubscription(int $subscriptionId, string $reason = '', ?int $actorId = null, ?string $actorRole = null): array
    {
        try {
            $subscription = $this->subscriptionModel->find($subscriptionId);
            if (!$subscription) {
                return ['success' => false, 'error' => 'Subscription not found', 'data' => null];
            }

            $updated = $this->subscriptionModel->cancelSubscription($subscriptionId, $reason);

            if (!$updated) {
                return ['success' => false, 'error' => 'Failed to cancel subscription', 'data' => null];
            }

            // Audit log
            $this->auditService->log(
                $subscription['school_id'],
                'subscription',
                $subscriptionId,
                'cancel',
                $actorId,
                $actorRole,
                ['reason' => $reason]
            );

            return ['success' => true, 'data' => ['subscription_id' => $subscriptionId, 'status' => 'canceled'], 'error' => null];

        } catch (\Exception $e) {
            log_message('error', 'SubscriptionService::cancelSubscription error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage(), 'data' => null];
        }
    }

    /**
     * Toggle autopay
     */
    public function toggleAutopay(int $subscriptionId, bool $enabled, ?int $actorId = null, ?string $actorRole = null): array
    {
        try {
            $subscription = $this->subscriptionModel->find($subscriptionId);
            if (!$subscription) {
                return ['success' => false, 'error' => 'Subscription not found', 'data' => null];
            }

            $updated = $this->subscriptionModel->toggleAutopay($subscriptionId, $enabled);

            if (!$updated) {
                return ['success' => false, 'error' => 'Failed to toggle autopay', 'data' => null];
            }

            // Audit log
            $this->auditService->log(
                $subscription['school_id'],
                'subscription',
                $subscriptionId,
                'toggle_autopay',
                $actorId,
                $actorRole,
                ['enabled' => $enabled]
            );

            return ['success' => true, 'data' => ['autopay_enabled' => $enabled], 'error' => null];

        } catch (\Exception $e) {
            log_message('error', 'SubscriptionService::toggleAutopay error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage(), 'data' => null];
        }
    }

    /**
     * Compute next billing date based on term
     */
    public function computeNextBillingDate(string $term, ?int $anchorDay = null, ?string $startDate = null): string
    {
        $start = $startDate ? strtotime($startDate) : time();
        $anchorDay = $anchorDay ?? date('d', $start);

        switch ($term) {
            case 'monthly':
                // Next month on anchor day
                $nextMonth = date('Y-m', strtotime('+1 month', $start));
                $daysInNextMonth = date('t', strtotime($nextMonth . '-01'));
                $day = min($anchorDay, $daysInNextMonth);
                return $nextMonth . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);

            case 'quarterly':
                // 3 months from now
                return date('Y-m-d', strtotime('+3 months', $start));

            case 'annual':
                // 1 year from now
                return date('Y-m-d', strtotime('+1 year', $start));

            case 'one_time':
            default:
                // One-time, no next billing
                return date('Y-m-d', $start);
        }
    }

    /**
     * Advance billing date after successful payment
     */
    public function advanceBillingDate(int $subscriptionId): array
    {
        try {
            $subscription = $this->subscriptionModel->find($subscriptionId);
            if (!$subscription) {
                return ['success' => false, 'error' => 'Subscription not found', 'data' => null];
            }

            $nextDate = $this->computeNextBillingDate(
                $subscription['term'],
                $subscription['anchor_day'],
                $subscription['next_billing_date']
            );

            $updated = $this->subscriptionModel->updateNextBillingDate($subscriptionId, $nextDate);

            if (!$updated) {
                return ['success' => false, 'error' => 'Failed to advance billing date', 'data' => null];
            }

            return ['success' => true, 'data' => ['next_billing_date' => $nextDate], 'error' => null];

        } catch (\Exception $e) {
            log_message('error', 'SubscriptionService::advanceBillingDate error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage(), 'data' => null];
        }
    }

    /**
     * Get subscriptions with filters
     */
    public function getSubscriptions(int $schoolId, array $filters = []): array
    {
        try {
            $subscriptions = $this->subscriptionModel->getActiveBySchool($schoolId, $filters);

            return ['success' => true, 'data' => $subscriptions, 'error' => null];

        } catch (\Exception $e) {
            log_message('error', 'SubscriptionService::getSubscriptions error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage(), 'data' => null];
        }
    }

    /**
     * Get subscription with details
     */
    public function getSubscriptionDetails(int $subscriptionId): array
    {
        try {
            $subscription = $this->subscriptionModel->getWithDetails($subscriptionId);

            if (!$subscription) {
                return ['success' => false, 'error' => 'Subscription not found', 'data' => null];
            }

            return ['success' => true, 'data' => $subscription, 'error' => null];

        } catch (\Exception $e) {
            log_message('error', 'SubscriptionService::getSubscriptionDetails error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage(), 'data' => null];
        }
    }
}
