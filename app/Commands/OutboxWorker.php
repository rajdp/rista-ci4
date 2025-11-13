<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

class OutboxWorker extends BaseCommand
{
    protected $group = 'Workers';
    protected $name = 'outbox:worker';
    protected $description = 'Process t_event_outbox (school-scoped)';
    protected $usage = 'outbox:worker [options]';
    protected $arguments = [];
    protected $options = [
        '--batch-size' => 'Number of events to process per batch (default: 50)',
        '--sleep-ms' => 'Sleep time in milliseconds when no events found (default: 250)',
    ];

    public function run(array $params)
    {
        $db = Database::connect();
        $workerId = gethostname() . '-' . getmypid();
        $batchSize = (int)($params['batch-size'] ?? CLI::getOption('batch-size') ?? 50);
        $sleepMs = (int)($params['sleep-ms'] ?? CLI::getOption('sleep-ms') ?? 250);

        CLI::write("Outbox Worker started (ID: {$workerId})", 'green');
        CLI::write("Batch size: {$batchSize}, Sleep: {$sleepMs}ms", 'blue');

        while (true) {
            try {
                // Claim a small batch atomically (no SKIP LOCKED in MySQL 5.7)
                // We use UPDATE with LIMIT to claim rows
                $db->query("
                    UPDATE t_event_outbox
                    SET claimed_by = ?, claimed_at = NOW()
                    WHERE processed_at IS NULL AND claimed_by IS NULL
                    ORDER BY id ASC
                    LIMIT ?
                ", [$workerId, $batchSize]);

                // Fetch claimed rows
                $rows = $db->query("
                    SELECT * FROM t_event_outbox
                    WHERE claimed_by = ? AND processed_at IS NULL
                    ORDER BY id ASC
                    LIMIT ?
                ", [$workerId, $batchSize])->getResultArray();

                if (empty($rows)) {
                    usleep($sleepMs * 1000); // Convert ms to microseconds
                    continue;
                }

                CLI::write("Processing " . count($rows) . " event(s)...", 'cyan');

                foreach ($rows as $row) {
                    try {
                        $payload = json_decode($row['payload_json'], true);
                        if (!is_array($payload)) {
                            $payload = [];
                        }

                        // Route to appropriate handler
                        $handled = $this->handleEvent($row['event_type'], $row, $payload);

                        if ($handled) {
                            // Mark as processed
                            $db->table('t_event_outbox')
                                ->where('id', $row['id'])
                                ->update(['processed_at' => date('Y-m-d H:i:s')]);
                            
                            CLI::write("  ✓ Processed: {$row['event_type']} (ID: {$row['id']})", 'green');
                        } else {
                            CLI::write("  ✗ Unknown event type: {$row['event_type']} (ID: {$row['id']})", 'yellow');
                            // Still mark as processed to avoid infinite retries
                            $db->table('t_event_outbox')
                                ->where('id', $row['id'])
                                ->update(['processed_at' => date('Y-m-d H:i:s')]);
                        }
                    } catch (\Throwable $e) {
                        log_message('error', 'Outbox error [ID: ' . $row['id'] . ']: ' . $e->getMessage());
                        CLI::write("  ✗ Error processing event ID {$row['id']}: " . $e->getMessage(), 'red');
                        
                        // Release claim on error (optional - could retry later)
                        $db->table('t_event_outbox')
                            ->where('id', $row['id'])
                            ->update([
                                'claimed_by' => null,
                                'claimed_at' => null
                            ]);
                    }
                }
            } catch (\Throwable $e) {
                log_message('error', 'Outbox worker error: ' . $e->getMessage());
                CLI::write("Worker error: " . $e->getMessage(), 'red');
                usleep($sleepMs * 1000);
            }
        }
    }

    /**
     * Route events to appropriate handlers
     */
    private function handleEvent(string $eventType, array $row, array $payload): bool
    {
        $handlers = service('handlers', false);
        
        if (!$handlers) {
            // Fallback: try to load handlers service
            $handlers = new \App\Services\EventHandlers();
        }

        switch ($eventType) {
            case 'selfreg.status.updated':
                return $handlers->selfregStatus($row, $payload);
            
            case 'selfreg.converted':
                return $handlers->selfregConverted($row, $payload);
            
            case 'session.reminder':
                return $handlers->sessionReminder($row, $payload);
            
            case 'attendance.no_show':
                return $handlers->noShow($row, $payload);
            
            case 'invoice.open':
                return $handlers->invoiceOpen($row, $payload);
            
            case 'invoice.paid':
                return $handlers->invoicePaid($row, $payload);
            
            default:
                return false;
        }
    }
}

