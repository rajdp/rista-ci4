<?php
/**
 * Script to create crm_followups table
 * Run this from command line: php create_followups_table.php
 */

// Bootstrap CodeIgniter
require __DIR__ . '/vendor/autoload.php';

$pathsConfig = FCPATH . '../app/Config/Paths.php';
require realpath($pathsConfig) ?: $pathsConfig;

$paths = new Config\Paths();
$bootstrap = rtrim($paths->systemDirectory, '\\/ ') . '/bootstrap.php';
require realpath($bootstrap) ?: $bootstrap;

$app = Config\Services::codeigniter();
$app->initialize();
$context = is_cli() ? 'php-cli' : 'web';
$app->setContext($context);

$db = \Config\Database::connect();

try {
    $forge = \Config\Database::forge();
    
    // Check if table exists
    if ($db->tableExists('crm_followups')) {
        echo "Table 'crm_followups' already exists.\n";
        exit(0);
    }
    
    // Create the table using the migration
    $migration = new \App\Database\Migrations\CreateFollowUpsTable();
    $migration->up();
    
    echo "Table 'crm_followups' created successfully!\n";
    
    // Mark migration as run
    $db->table('migrations')->insert([
        'version' => '2025-11-20-000001',
        'class' => 'App\\Database\\Migrations\\CreateFollowUpsTable',
        'group' => 'default',
        'namespace' => 'App',
        'time' => time(),
        'batch' => 1
    ]);
    
    echo "Migration marked as complete.\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}


