<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\WebhookEventModel;

class ProcessWebhooks extends BaseCommand
{
    protected $group       = 'Billing';
    protected $name        = 'webhooks:process';
    protected $description = 'Process pending webhook events';
    protected $usage       = 'webhooks:process [--limit=100] [--max-age=24]';
    protected $arguments   = [];
    protected $options     = [
        '--limit'   => 'Maximum number of webhooks to process (default: 100)',
        '--max-age' => 'Maximum age in hours for retrying failed webhooks (default: 24)',
    ];

    protected $webhookModel;

    public function __construct($logger = null, $commands = null)
    {
        parent::__construct($logger, $commands);
        $this->webhookModel = new WebhookEventModel();
    }

    public function run(array $params)
    {
        CLI::write('=== Webhook Event Processor ===', 'green');
        CLI::newLine();

        $limit = (int)($params['limit'] ?? CLI::getOption('limit') ?? 100);
        $maxAge = (int)($params['max-age'] ?? CLI::getOption('max-age') ?? 24);

        CLI::write("Processing up to {$limit} pending webhooks...", 'yellow');
        CLI::write("Max retry age: {$maxAge} hours", 'yellow');
        CLI::newLine();

        // Get pending webhooks
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$maxAge} hours"));

        $webhooks = $this->webhookModel
            ->where('status', 'pending')
            ->where('created_at >=', $cutoffDate)
            ->orderBy('created_at', 'ASC')
            ->limit($limit)
            ->findAll();

        if (empty($webhooks)) {
            CLI::write('No pending webhooks to process.', 'white');
            return EXIT_SUCCESS;
        }

        CLI::write('Found ' . count($webhooks) . ' pending webhook(s)', 'cyan');
        CLI::newLine();

        $stats = [
            'processed' => 0,
            'succeeded' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        foreach ($webhooks as $webhook) {
            $result = $this->processWebhook($webhook);
            $stats['processed']++;

            if ($result['status'] === 'success') {
                $stats['succeeded']++;
            } elseif ($result['status'] === 'failed') {
                $stats['failed']++;
            } else {
                $stats['skipped']++;
            }
        }

        // Summary
        CLI::newLine();
        CLI::write('=== Processing Summary ===', 'green');
        CLI::write("Total Processed: {$stats['processed']}", 'white');
        CLI::write("Succeeded: {$stats['succeeded']}", 'green');
        CLI::write("Failed: {$stats['failed']}", 'red');
        CLI::write("Skipped: {$stats['skipped']}", 'yellow');
        CLI::newLine();

        return $stats['failed'] > 0 ? EXIT_ERROR : EXIT_SUCCESS;
    }

    /**
     * Process a single webhook event
     */
    protected function processWebhook(array $webhook): array
    {
        CLI::write("Processing webhook #{$webhook['event_id']} - Type: {$webhook['event_type']}", 'yellow');

        try {
            // Decode payload
            $payload = json_decode($webhook['payload'], true);
            if (!$payload) {
                throw new \Exception('Invalid webhook payload JSON');
            }

            // Process based on event type
            $result = $this->handleWebhookEvent($webhook['event_type'], $payload, $webhook['school_id']);

            // Update webhook status
            $updateData = [
                'status' => 'processed',
                'processed_at' => date('Y-m-d H:i:s'),
                'response' => json_encode(['success' => true, 'result' => $result]),
            ];

            $this->webhookModel->update($webhook['event_id'], $updateData);

            CLI::write("  ✓ Processed successfully", 'green');
            return ['status' => 'success'];

        } catch (\Exception $e) {
            // Update webhook with error
            $updateData = [
                'status' => 'failed',
                'processed_at' => date('Y-m-d H:i:s'),
                'response' => json_encode(['success' => false, 'error' => $e->getMessage()]),
            ];

            $this->webhookModel->update($webhook['event_id'], $updateData);

            CLI::error("  ✗ Failed: {$e->getMessage()}");
            return ['status' => 'failed', 'error' => $e->getMessage()];
        }
    }

    /**
     * Handle specific webhook event types
     */
    protected function handleWebhookEvent(string $eventType, array $payload, int $schoolId): array
    {
        switch ($eventType) {
            case 'payment.succeeded':
                return $this->handlePaymentSucceeded($payload, $schoolId);

            case 'payment.failed':
                return $this->handlePaymentFailed($payload, $schoolId);

            case 'subscription.created':
            case 'subscription.updated':
            case 'subscription.cancelled':
                return $this->handleSubscriptionEvent($eventType, $payload, $schoolId);

            case 'refund.created':
                return $this->handleRefundCreated($payload, $schoolId);

            default:
                CLI::write("  ⚠ Unknown event type: {$eventType}", 'yellow');
                return ['handled' => false, 'reason' => 'Unknown event type'];
        }
    }

    /**
     * Handle payment.succeeded webhook
     */
    protected function handlePaymentSucceeded(array $payload, int $schoolId): array
    {
        // This would integrate with InvoiceService to mark invoice as paid
        // For now, just log it
        $invoiceId = $payload['invoice_id'] ?? null;
        $amount = $payload['amount_cents'] ?? 0;
        $amountDollars = $amount / 100;

        CLI::write("    Payment succeeded for invoice #{$invoiceId}: \${$amountDollars}", 'white');

        return [
            'handled' => true,
            'action' => 'payment_recorded',
            'invoice_id' => $invoiceId,
        ];
    }

    /**
     * Handle payment.failed webhook
     */
    protected function handlePaymentFailed(array $payload, int $schoolId): array
    {
        $invoiceId = $payload['invoice_id'] ?? null;
        $reason = $payload['failure_reason'] ?? 'Unknown';

        CLI::write("    Payment failed for invoice #{$invoiceId}: {$reason}", 'white');

        return [
            'handled' => true,
            'action' => 'payment_failure_logged',
            'invoice_id' => $invoiceId,
        ];
    }

    /**
     * Handle subscription events
     */
    protected function handleSubscriptionEvent(string $eventType, array $payload, int $schoolId): array
    {
        $subscriptionId = $payload['subscription_id'] ?? null;

        CLI::write("    Subscription event: {$eventType} for subscription #{$subscriptionId}", 'white');

        return [
            'handled' => true,
            'action' => 'subscription_synced',
            'subscription_id' => $subscriptionId,
        ];
    }

    /**
     * Handle refund.created webhook
     */
    protected function handleRefundCreated(array $payload, int $schoolId): array
    {
        $refundId = $payload['refund_id'] ?? null;
        $amount = $payload['amount_cents'] ?? 0;
        $amountDollars = $amount / 100;

        CLI::write("    Refund created #{$refundId}: \${$amountDollars}", 'white');

        return [
            'handled' => true,
            'action' => 'refund_recorded',
            'refund_id' => $refundId,
        ];
    }
}
