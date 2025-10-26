<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class DayWiseReport extends BaseCommand
{
    protected $group = 'Reports';
    protected $name = 'reports:daywise';
    protected $description = 'Generate day-wise activity report';
    protected $usage = 'reports:daywise [options]';
    protected $arguments = [];
    protected $options = [
        '--date-from' => 'Start date for the report (Y-m-d format)',
        '--date-to' => 'End date for the report (Y-m-d format)',
        '--school-id' => 'Specific school ID to filter by',
        '--output' => 'Output file path for the report'
    ];

    public function run(array $params)
    {
        $dateFrom = $params['date-from'] ?? date('Y-m-01'); // First day of current month
        $dateTo = $params['date-to'] ?? date('Y-m-d');
        $schoolId = $params['school-id'] ?? null;
        $outputPath = $params['output'] ?? $this->getDefaultOutputPath();
        
        CLI::write("Generating day-wise activity report...", 'green');
        CLI::write("Date range: {$dateFrom} to {$dateTo}", 'blue');
        
        if ($schoolId) {
            CLI::write("School ID: {$schoolId}", 'blue');
        }
        
        try {
            $reportData = $this->generateReport($dateFrom, $dateTo, $schoolId);
            
            if (empty($reportData)) {
                CLI::write('No data found for the specified criteria', 'yellow');
                return true;
            }
            
            $filename = $this->saveReport($reportData, $outputPath);
            
            if ($filename) {
                CLI::write("Report generated successfully: {$filename}", 'green');
                $this->logReportGeneration($filename, $dateFrom, $dateTo, $schoolId);
                return true;
            } else {
                CLI::write('Failed to save report', 'red');
                return false;
            }
        } catch (\Exception $e) {
            CLI::write('Report generation failed: ' . $e->getMessage(), 'red');
            return false;
        }
    }

    /**
     * Generate report data
     */
    private function generateReport($dateFrom, $dateTo, $schoolId = null)
    {
        $db = \Config\Database::connect();
        
        // Get daily activity summary
        $builder = $db->table('student_activity sa');
        $builder->select('
            DATE(sa.created_date) as activity_date,
            COUNT(DISTINCT sa.student_id) as unique_students,
            COUNT(sa.id) as total_activities,
            COUNT(CASE WHEN sa.activity_type = "login" THEN 1 END) as logins,
            COUNT(CASE WHEN sa.activity_type = "content_view" THEN 1 END) as content_views,
            COUNT(CASE WHEN sa.activity_type = "quiz_attempt" THEN 1 END) as quiz_attempts,
            COUNT(CASE WHEN sa.activity_type = "assignment_submit" THEN 1 END) as assignment_submissions
        ');
        $builder->join('students s', 's.id = sa.student_id', 'left');
        $builder->where('sa.created_date >=', $dateFrom . ' 00:00:00');
        $builder->where('sa.created_date <=', $dateTo . ' 23:59:59');
        
        if ($schoolId) {
            $builder->where('s.school_id', $schoolId);
        }
        
        $builder->groupBy('DATE(sa.created_date)');
        $builder->orderBy('activity_date', 'ASC');
        
        return $builder->get()->getResultArray();
    }

    /**
     * Save report to file
     */
    private function saveReport($reportData, $outputPath)
    {
        $filename = 'daywise_activity_report_' . date('Y-m-d_H-i-s') . '.csv';
        $fullPath = $outputPath . DIRECTORY_SEPARATOR . $filename;
        
        // Ensure output directory exists
        if (!is_dir($outputPath)) {
            mkdir($outputPath, 0755, true);
        }
        
        $file = fopen($fullPath, 'w');
        
        if (!$file) {
            return false;
        }
        
        // Write CSV header
        fputcsv($file, [
            'Date',
            'Unique Students',
            'Total Activities',
            'Logins',
            'Content Views',
            'Quiz Attempts',
            'Assignment Submissions'
        ]);
        
        // Write data rows
        foreach ($reportData as $row) {
            fputcsv($file, [
                $row['activity_date'],
                $row['unique_students'],
                $row['total_activities'],
                $row['logins'],
                $row['content_views'],
                $row['quiz_attempts'],
                $row['assignment_submissions']
            ]);
        }
        
        fclose($file);
        
        return $fullPath;
    }

    /**
     * Get default output path
     */
    private function getDefaultOutputPath()
    {
        return WRITEPATH . 'reports' . DIRECTORY_SEPARATOR . 'daywise';
    }

    /**
     * Log report generation
     */
    private function logReportGeneration($filename, $dateFrom, $dateTo, $schoolId)
    {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'report_file' => $filename,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'school_id' => $schoolId,
            'status' => 'success'
        ];
        
        $logFile = WRITEPATH . 'logs' . DIRECTORY_SEPARATOR . 'reports_' . date('Y-m-d') . '.log';
        file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
    }
}
