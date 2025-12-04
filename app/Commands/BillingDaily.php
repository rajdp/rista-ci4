<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\Billing\SubscriptionService;
use App\Services\Billing\InvoiceService;
use App\Services\Billing\LateFeeService;
use App\Services\Billing\DunningService;

class BillingDaily extends BaseCommand
{
    protected $group       = 'Billing';
    protected $name        = 'billing:daily';
    protected $description = 'Run daily billing tasks: generate invoices, apply late fees, execute dunning steps';
    protected $usage       = 'billing:daily [--date=YYYY-MM-DD] [--school-id=X]';
    protected $arguments   = [];
    protected $options     = [
        '--date'      => 'Override current date (format: YYYY-MM-DD)',
        '--school-id' => 'Process only specific school ID',
    ];

    protected $subscriptionService;
    protected $invoiceService;
    protected $lateFeeService;
    protected $dunningService;
    protected $db;

    public function __construct($logger = null, $commands = null)
    {
        parent::__construct($logger, $commands);

        $this->subscriptionService = new SubscriptionService();
        $this->invoiceService = new InvoiceService();
        $this->lateFeeService = new LateFeeService();
        $this->dunningService = new DunningService();
        $this->db = \Config\Database::connect();
    }

    public function run(array $params)
    {
        CLI::write('=== EdQuill Daily Billing Tasks ===', 'green');
        CLI::newLine();

        // Get run date
        $runDate = $params['date'] ?? CLI::getOption('date') ?? date('Y-m-d');
        CLI::write("Run Date: {$runDate}", 'yellow');

        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $runDate)) {
            CLI::error('Invalid date format. Use YYYY-MM-DD');
            return EXIT_ERROR;
        }

        // Get schools to process
        $schoolId = $params['school-id'] ?? CLI::getOption('school-id');

        if ($schoolId) {
            $schools = $this->db->table('school')
                ->where('school_id', $schoolId)
                ->get()
                ->getResultArray();
        } else {
            $schools = $this->db->table('school')
                ->get()
                ->getResultArray();
        }

        if (empty($schools) || (!$schools[0])) {
            CLI::error('No schools found to process');
            return EXIT_ERROR;
        }

        CLI::write('Processing ' . count($schools) . ' school(s)...', 'yellow');
        CLI::newLine();

        $totalStats = [
            'invoices_generated' => 0,
            'late_fees_applied' => 0,
            'dunning_steps_executed' => 0,
            'errors' => 0,
        ];

        foreach ($schools as $school) {
            if (!$school) continue;

            $schoolName = $school['school_name'] ?? $school['name'] ?? 'Unknown';
            CLI::write("School: {$schoolName} (ID: {$school['school_id']})", 'cyan');

            // Task 1: Generate invoices from due subscriptions
            $invoiceResult = $this->generateInvoices($school['school_id'], $runDate);
            $totalStats['invoices_generated'] += $invoiceResult['generated'];
            $totalStats['errors'] += $invoiceResult['errors'];

            // Task 2: Apply late fees to overdue invoices
            $lateFeeResult = $this->applyLateFees($school['school_id'], $runDate);
            $totalStats['late_fees_applied'] += $lateFeeResult['applied'];
            $totalStats['errors'] += $lateFeeResult['errors'];

            // Task 3: Execute dunning workflow
            $dunningResult = $this->executeDunning($school['school_id'], $runDate);
            $totalStats['dunning_steps_executed'] += $dunningResult['processed'];
            $totalStats['errors'] += $dunningResult['errors'];

            CLI::newLine();
        }

        // Summary
        CLI::write('=== Daily Billing Summary ===', 'green');
        CLI::write("Invoices Generated: {$totalStats['invoices_generated']}", 'white');
        CLI::write("Late Fees Applied: {$totalStats['late_fees_applied']}", 'white');
        CLI::write("Dunning Steps Executed: {$totalStats['dunning_steps_executed']}", 'white');
        CLI::write("Errors: {$totalStats['errors']}", $totalStats['errors'] > 0 ? 'red' : 'white');
        CLI::newLine();

        return $totalStats['errors'] > 0 ? EXIT_ERROR : EXIT_SUCCESS;
    }

    /**
     * Generate invoices from subscriptions due for billing
     */
    protected function generateInvoices(int $schoolId, string $runDate): array
    {
        CLI::write('  → Generating invoices...', 'yellow');

        try {
            $result = $this->invoiceService->generateUpcomingInvoices($schoolId, $runDate);

            if ($result['success']) {
                $generated = $result['data']['generated'] ?? 0;
                $errors = count($result['data']['errors'] ?? []);

                CLI::write("    Generated {$generated} invoice(s)", 'green');

                if ($errors > 0) {
                    CLI::write("    {$errors} error(s) occurred", 'red');
                    foreach ($result['data']['errors'] as $error) {
                        CLI::write("      - {$error}", 'red');
                    }
                }

                return ['generated' => $generated, 'errors' => $errors];
            } else {
                CLI::error("    Failed: {$result['error']}");
                return ['generated' => 0, 'errors' => 1];
            }

        } catch (\Exception $e) {
            CLI::error("    Exception: {$e->getMessage()}");
            return ['generated' => 0, 'errors' => 1];
        }
    }

    /**
     * Apply late fees to overdue invoices
     */
    protected function applyLateFees(int $schoolId, string $runDate): array
    {
        CLI::write('  → Applying late fees...', 'yellow');

        try {
            $result = $this->lateFeeService->applyLateFees($schoolId, $runDate);

            if ($result['success']) {
                $applied = $result['data']['applied'] ?? 0;
                $errors = count($result['data']['errors'] ?? []);

                if ($applied > 0) {
                    CLI::write("    Applied late fees to {$applied} invoice(s)", 'green');
                } else {
                    CLI::write("    No late fees to apply", 'white');
                }

                if ($errors > 0) {
                    CLI::write("    {$errors} error(s) occurred", 'red');
                    foreach ($result['data']['errors'] as $error) {
                        CLI::write("      - {$error}", 'red');
                    }
                }

                return ['applied' => $applied, 'errors' => $errors];
            } else {
                CLI::error("    Failed: {$result['error']}");
                return ['applied' => 0, 'errors' => 1];
            }

        } catch (\Exception $e) {
            CLI::error("    Exception: {$e->getMessage()}");
            return ['applied' => 0, 'errors' => 1];
        }
    }

    /**
     * Execute dunning workflow steps
     */
    protected function executeDunning(int $schoolId, string $runDate): array
    {
        CLI::write('  → Executing dunning workflow...', 'yellow');

        try {
            $result = $this->dunningService->executeDunningSteps($schoolId, $runDate);

            if ($result['success']) {
                $processed = $result['data']['processed'] ?? 0;
                $errors = count($result['data']['errors'] ?? []);

                if ($processed > 0) {
                    CLI::write("    Executed {$processed} dunning step(s)", 'green');
                } else {
                    CLI::write("    No dunning steps to execute", 'white');
                }

                if ($errors > 0) {
                    CLI::write("    {$errors} error(s) occurred", 'red');
                    foreach ($result['data']['errors'] as $error) {
                        CLI::write("      - {$error}", 'red');
                    }
                }

                return ['processed' => $processed, 'errors' => $errors];
            } else {
                CLI::error("    Failed: {$result['error']}");
                return ['processed' => 0, 'errors' => 1];
            }

        } catch (\Exception $e) {
            CLI::error("    Exception: {$e->getMessage()}");
            return ['processed' => 0, 'errors' => 1];
        }
    }
}
