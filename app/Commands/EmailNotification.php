<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class EmailNotification extends BaseCommand
{
    protected $group = 'Email';
    protected $name = 'email:send-notifications';
    protected $description = 'Send email notifications to users';
    protected $usage = 'email:send-notifications [options]';
    protected $arguments = [];
    protected $options = [
        '--type' => 'Type of notification to send',
        '--limit' => 'Maximum number of emails to send',
        '--dry-run' => 'Show what would be sent without actually sending'
    ];

    public function run(array $params)
    {
        $type = $params['type'] ?? 'all';
        $limit = (int)($params['limit'] ?? 100);
        $dryRun = isset($params['dry-run']) || CLI::getOption('dry-run');
        
        CLI::write("Starting email notification process...", 'green');
        CLI::write("Type: {$type}, Limit: {$limit}, Dry Run: " . ($dryRun ? 'Yes' : 'No'), 'blue');
        
        try {
            $notifications = $this->getPendingNotifications($type, $limit);
            
            if (empty($notifications)) {
                CLI::write('No pending notifications found', 'yellow');
                return true;
            }
            
            CLI::write("Found " . count($notifications) . " pending notifications", 'blue');
            
            $sent = 0;
            $failed = 0;
            
            foreach ($notifications as $notification) {
                if ($dryRun) {
                    CLI::write("Would send: {$notification['type']} to {$notification['email']}", 'cyan');
                    $sent++;
                } else {
                    if ($this->sendNotification($notification)) {
                        $this->markAsSent($notification['id']);
                        $sent++;
                        CLI::write("Sent: {$notification['type']} to {$notification['email']}", 'green');
                    } else {
                        $failed++;
                        CLI::write("Failed: {$notification['type']} to {$notification['email']}", 'red');
                    }
                }
            }
            
            CLI::write("Email notification process completed", 'green');
            CLI::write("Sent: {$sent}, Failed: {$failed}", 'blue');
            
            return true;
        } catch (\Exception $e) {
            CLI::write('Email notification process failed: ' . $e->getMessage(), 'red');
            return false;
        }
    }

    /**
     * Get pending notifications
     */
    private function getPendingNotifications($type, $limit)
    {
        $db = \Config\Database::connect();
        
        $builder = $db->table('email_notifications');
        $builder->where('status', 'pending');
        $builder->where('scheduled_date <=', date('Y-m-d H:i:s'));
        
        if ($type !== 'all') {
            $builder->where('type', $type);
        }
        
        $builder->orderBy('created_date', 'ASC');
        $builder->limit($limit);
        
        return $builder->get()->getResultArray();
    }

    /**
     * Send notification email
     */
    private function sendNotification($notification)
    {
        try {
            $email = \Config\Services::email();
            
            $email->setTo($notification['email']);
            $email->setSubject($notification['subject']);
            $email->setMessage($notification['message']);
            
            if (!empty($notification['from_email'])) {
                $email->setFrom($notification['from_email'], $notification['from_name'] ?? '');
            }
            
            if ($email->send()) {
                return true;
            } else {
                $this->logError('Email send failed: ' . $email->printDebugger());
                return false;
            }
        } catch (\Exception $e) {
            $this->logError('Email notification error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark notification as sent
     */
    private function markAsSent($notificationId)
    {
        $db = \Config\Database::connect();
        
        $db->table('email_notifications')
           ->where('id', $notificationId)
           ->update([
               'status' => 'sent',
               'sent_date' => date('Y-m-d H:i:s')
           ]);
    }

    /**
     * Log error
     */
    private function logError($message)
    {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'error' => $message
        ];
        
        $logFile = WRITEPATH . 'logs' . DIRECTORY_SEPARATOR . 'email_errors_' . date('Y-m-d') . '.log';
        file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
    }
}
