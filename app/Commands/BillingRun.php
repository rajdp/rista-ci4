<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\BillingRunService;

class BillingRun extends BaseCommand
{
    protected $group = 'Billing';
    protected $name = 'billing:run';
    protected $description = 'Run daily billing for a school (processes due schedules, creates invoices, attempts charges)';
    protected $usage = 'billing:run --school=<school_id> [--date=<YYYY-MM-DD>]';
    protected $arguments = [];
    protected $options = [
        '--school' => 'School ID (required)',
        '--date' => 'Date to run billing for (YYYY-MM-DD, defaults to today)',
    ];

    public function run(array $params)
    {
        $schoolId = CLI::getOption('school');
        if (!$schoolId) {
            CLI::error('--school option is required');
            CLI::write('Usage: ' . $this->usage, 'yellow');
            return;
        }

        $schoolId = (int)$schoolId;
        $runDate = CLI::getOption('date') ?? date('Y-m-d');

        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $runDate)) {
            CLI::error('Invalid date format. Use YYYY-MM-DD');
            return;
        }

        CLI::write("Starting billing run for school {$schoolId} on {$runDate}", 'green');

        try {
            $billingRunService = new BillingRunService();
            $claimedBy = gethostname() . '-' . getmypid();
            
            $result = $billingRunService->runDailyBilling($schoolId, $runDate, $claimedBy);

            CLI::write("Billing run completed:", 'cyan');
            CLI::write("  Total schedules: {$result['total_schedules']}", 'white');
            CLI::write("  Processed: {$result['processed']}", 'green');
            CLI::write("  Invoiced: {$result['invoiced']}", 'green');
            CLI::write("  Charged: {$result['charged']}", 'green');
            CLI::write("  Skipped: {$result['skipped']}", 'yellow');
            CLI::write("  Failed: {$result['failed']}", 'red');

            if (!empty($result['errors'])) {
                CLI::write("\nErrors:", 'red');
                foreach ($result['errors'] as $error) {
                    CLI::write("  Schedule {$error['schedule_id']}: {$error['error']}", 'red');
                }
            }

            if ($result['failed'] > 0) {
                return 1; // Exit with error code
            }

            return 0;
        } catch (\Exception $e) {
            CLI::error('Billing run failed: ' . $e->getMessage());
            log_message('error', 'BillingRun command failed: ' . $e->getMessage());
            return 1;
        }
    }
}


