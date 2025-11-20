<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Database\Migrations\CreateFollowUpsTable;

class CreateFollowUpsTable extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'followups:create-table';
    protected $description  = 'Create the crm_followups table';

    public function run(array $params)
    {
        $db = \Config\Database::connect();
        
        // Check if table exists
        if ($db->tableExists('crm_followups')) {
            CLI::write('Table crm_followups already exists.', 'yellow');
            return;
        }
        
        try {
            CLI::write('Creating crm_followups table...', 'yellow');
            
            $migration = new \App\Database\Migrations\CreateFollowUpsTable();
            $migration->up();
            
            CLI::write('Table crm_followups created successfully!', 'green');
            
            // Mark migration as run
            $migrationTable = $db->table('migrations');
            $exists = $migrationTable->where('version', '2025-11-20-000001')->get()->getRowArray();
            
            if (!$exists) {
                $migrationTable->insert([
                    'version' => '2025-11-20-000001',
                    'class' => 'App\\Database\\Migrations\\CreateFollowUpsTable',
                    'group' => 'default',
                    'namespace' => 'App',
                    'time' => time(),
                    'batch' => 1
                ]);
                CLI::write('Migration marked as complete.', 'green');
            }
            
        } catch (\Exception $e) {
            CLI::error('Error: ' . $e->getMessage());
            CLI::error($e->getTraceAsString());
        }
    }
}


