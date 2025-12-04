<?php
/**
 * Manual migration runner for Report Cards tables only
 * Run this with: php run_report_cards_migration.php
 */

require __DIR__ . '/vendor/autoload.php';

// Load CodeIgniter
$paths = new Config\Paths();
$bootstrap = rtrim(realpath(ROOTPATH . APPPATH . 'Config/Boot/production.php'), '/');
require $bootstrap;

$db = \Config\Database::connect();

echo "Starting Report Cards migration...\n\n";

try {
    // Load the migration class
    require APPPATH . 'Database/Migrations/2025-12-02-000001_CreateReportCardsTables.php';

    $migration = new \App\Database\Migrations\CreateReportCardsTables();
    $migration->up();

    echo "✅ Migration completed successfully!\n\n";
    echo "Tables created:\n";
    echo "  - t_rc_template\n";
    echo "  - t_rc_scale\n";
    echo "  - t_report_card\n";
    echo "  - t_report_card_version\n";
    echo "  - t_rc_event\n\n";

    // Mark migration as run in migrations table
    $db->table('migrations')->insert([
        'version' => '2025-12-02-000001',
        'class' => 'App\Database\Migrations\CreateReportCardsTables',
        'group' => 'default',
        'namespace' => 'App',
        'time' => time(),
        'batch' => 9999
    ]);

    echo "✅ Migration recorded in database\n\n";
    echo "Next step: Run the seeder\n";
    echo "  php spark db:seed ReportCardSeeder\n\n";

} catch (\Throwable $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    exit(1);
}
