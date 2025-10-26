<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class DatabaseBackup extends BaseCommand
{
    protected $group = 'Database';
    protected $name = 'db:backup';
    protected $description = 'Create a database backup';
    protected $usage = 'db:backup [options]';
    protected $arguments = [];
    protected $options = [
        '--output' => 'Output file path for the backup',
        '--compress' => 'Compress the backup file'
    ];

    public function run(array $params)
    {
        $outputPath = $params['output'] ?? $this->getDefaultOutputPath();
        $compress = isset($params['compress']) || CLI::getOption('compress');
        
        CLI::write('Starting database backup...', 'green');
        
        try {
            $backupFile = $this->createBackup($outputPath, $compress);
            
            if ($backupFile) {
                CLI::write("Database backup created successfully: {$backupFile}", 'green');
                
                // Log the backup
                $this->logBackup($backupFile);
                
                // Clean old backups if needed
                $this->cleanOldBackups();
                
                return true;
            } else {
                CLI::write('Failed to create database backup', 'red');
                return false;
            }
        } catch (\Exception $e) {
            CLI::write('Database backup failed: ' . $e->getMessage(), 'red');
            return false;
        }
    }

    /**
     * Create database backup
     */
    private function createBackup($outputPath, $compress = false)
    {
        $db = \Config\Database::connect();
        $config = $db->getConfig();
        
        $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        $fullPath = $outputPath . DIRECTORY_SEPARATOR . $filename;
        
        // Ensure output directory exists
        if (!is_dir($outputPath)) {
            mkdir($outputPath, 0755, true);
        }
        
        // Build mysqldump command
        $command = sprintf(
            'mysqldump --host=%s --port=%s --user=%s --password=%s %s > %s',
            escapeshellarg($config['hostname']),
            escapeshellarg($config['port']),
            escapeshellarg($config['username']),
            escapeshellarg($config['password']),
            escapeshellarg($config['database']),
            escapeshellarg($fullPath)
        );
        
        // Execute backup command
        $result = shell_exec($command . ' 2>&1');
        
        if (file_exists($fullPath) && filesize($fullPath) > 0) {
            if ($compress) {
                $this->compressBackup($fullPath);
            }
            return $fullPath;
        }
        
        return false;
    }

    /**
     * Compress backup file
     */
    private function compressBackup($filePath)
    {
        $compressedPath = $filePath . '.gz';
        
        if (function_exists('gzopen')) {
            $fp_out = gzopen($compressedPath, 'wb9');
            $fp_in = fopen($filePath, 'rb');
            
            while (!feof($fp_in)) {
                gzwrite($fp_out, fread($fp_in, 1024 * 512));
            }
            
            fclose($fp_in);
            gzclose($fp_out);
            
            // Remove original file
            unlink($filePath);
            
            return $compressedPath;
        }
        
        return $filePath;
    }

    /**
     * Get default output path
     */
    private function getDefaultOutputPath()
    {
        return WRITEPATH . 'backups' . DIRECTORY_SEPARATOR . 'database';
    }

    /**
     * Log backup information
     */
    private function logBackup($backupFile)
    {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'backup_file' => $backupFile,
            'file_size' => filesize($backupFile),
            'status' => 'success'
        ];
        
        $logFile = WRITEPATH . 'logs' . DIRECTORY_SEPARATOR . 'backup_' . date('Y-m-d') . '.log';
        file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
    }

    /**
     * Clean old backups (keep last 7 days)
     */
    private function cleanOldBackups()
    {
        $backupDir = $this->getDefaultOutputPath();
        $cutoffDate = date('Y-m-d', strtotime('-7 days'));
        
        if (is_dir($backupDir)) {
            $files = glob($backupDir . DIRECTORY_SEPARATOR . 'backup_*.sql*');
            
            foreach ($files as $file) {
                $fileDate = date('Y-m-d', filemtime($file));
                
                if ($fileDate < $cutoffDate) {
                    unlink($file);
                    CLI::write("Removed old backup: " . basename($file), 'yellow');
                }
            }
        }
    }
}
