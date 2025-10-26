<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ContentOverDueEmail extends BaseCommand
{
    protected $group = 'Content';
    protected $name = 'content:overdue-email';
    protected $description = 'Send overdue content notifications';
    protected $usage = 'content:overdue-email [options]';
    protected $arguments = [];
    protected $options = [
        '--days' => 'Number of days overdue to check for',
        '--limit' => 'Maximum number of emails to send',
        '--dry-run' => 'Show what would be sent without actually sending'
    ];

    public function run(array $params)
    {
        $days = (int)($params['days'] ?? 1);
        $limit = (int)($params['limit'] ?? 100);
        $dryRun = isset($params['dry-run']) || CLI::getOption('dry-run');
        
        CLI::write("Checking for overdue content...", 'green');
        CLI::write("Days overdue: {$days}, Limit: {$limit}, Dry Run: " . ($dryRun ? 'Yes' : 'No'), 'blue');
        
        try {
            $overdueContent = $this->getOverdueContent($days, $limit);
            
            if (empty($overdueContent)) {
                CLI::write('No overdue content found', 'yellow');
                return true;
            }
            
            CLI::write("Found " . count($overdueContent) . " overdue content items", 'blue');
            
            $sent = 0;
            $failed = 0;
            
            foreach ($overdueContent as $content) {
                if ($dryRun) {
                    CLI::write("Would notify: {$content['title']} (Student: {$content['student_name']})", 'cyan');
                    $sent++;
                } else {
                    if ($this->sendOverdueNotification($content)) {
                        $this->markAsNotified($content['id']);
                        $sent++;
                        CLI::write("Notified: {$content['title']} (Student: {$content['student_name']})", 'green');
                    } else {
                        $failed++;
                        CLI::write("Failed: {$content['title']} (Student: {$content['student_name']})", 'red');
                    }
                }
            }
            
            CLI::write("Overdue content notification process completed", 'green');
            CLI::write("Sent: {$sent}, Failed: {$failed}", 'blue');
            
            return true;
        } catch (\Exception $e) {
            CLI::write('Overdue content notification process failed: ' . $e->getMessage(), 'red');
            return false;
        }
    }

    /**
     * Get overdue content
     */
    private function getOverdueContent($days, $limit)
    {
        $db = \Config\Database::connect();
        
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $builder = $db->table('student_content sc');
        $builder->select('sc.*, s.first_name, s.last_name, s.email, c.title, c.due_date');
        $builder->join('students s', 's.id = sc.student_id', 'left');
        $builder->join('content c', 'c.id = sc.content_id', 'left');
        $builder->where('sc.status', 'assigned');
        $builder->where('c.due_date <', $cutoffDate);
        $builder->where('sc.overdue_notified', 0);
        $builder->orderBy('c.due_date', 'ASC');
        $builder->limit($limit);
        
        return $builder->get()->getResultArray();
    }

    /**
     * Send overdue notification
     */
    private function sendOverdueNotification($content)
    {
        try {
            $email = \Config\Services::email();
            
            $subject = "Overdue Assignment: {$content['title']}";
            $message = $this->buildOverdueMessage($content);
            
            $email->setTo($content['email']);
            $email->setSubject($subject);
            $email->setMessage($message);
            
            if ($email->send()) {
                return true;
            } else {
                $this->logError('Overdue email send failed: ' . $email->printDebugger());
                return false;
            }
        } catch (\Exception $e) {
            $this->logError('Overdue notification error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Build overdue message
     */
    private function buildOverdueMessage($content)
    {
        $studentName = $content['first_name'] . ' ' . $content['last_name'];
        $dueDate = date('F j, Y', strtotime($content['due_date']));
        $daysOverdue = floor((time() - strtotime($content['due_date'])) / (60 * 60 * 24));
        
        $message = "Dear {$studentName},\n\n";
        $message .= "This is a reminder that your assignment '{$content['title']}' was due on {$dueDate}.\n";
        $message .= "It is now {$daysOverdue} day(s) overdue.\n\n";
        $message .= "Please complete and submit your assignment as soon as possible.\n\n";
        $message .= "If you have any questions, please contact your teacher.\n\n";
        $message .= "Best regards,\n";
        $message .= "EdQuill Team";
        
        return $message;
    }

    /**
     * Mark as notified
     */
    private function markAsNotified($contentId)
    {
        $db = \Config\Database::connect();
        
        $db->table('student_content')
           ->where('id', $contentId)
           ->update([
               'overdue_notified' => 1,
               'overdue_notified_date' => date('Y-m-d H:i:s')
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
        
        $logFile = WRITEPATH . 'logs' . DIRECTORY_SEPARATOR . 'overdue_errors_' . date('Y-m-d') . '.log';
        file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
    }
}
