<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class StudentPlatformReport extends BaseCommand
{
    protected $group = 'Reports';
    protected $name = 'reports:student-platform';
    protected $description = 'Generate student platform-wise answer report';
    protected $usage = 'reports:student-platform [options]';
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
        
        CLI::write("Generating student platform report...", 'green');
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
        
        $builder = $db->table('student_answers sa');
        $builder->select('
            s.id as student_id,
            s.first_name,
            s.last_name,
            s.email,
            sc.school_name,
            c.title as content_title,
            sa.platform,
            sa.created_date,
            sa.score,
            sa.total_questions,
            sa.correct_answers
        ');
        $builder->join('students s', 's.id = sa.student_id', 'left');
        $builder->join('schools sc', 'sc.id = s.school_id', 'left');
        $builder->join('content c', 'c.id = sa.content_id', 'left');
        $builder->where('sa.created_date >=', $dateFrom . ' 00:00:00');
        $builder->where('sa.created_date <=', $dateTo . ' 23:59:59');
        
        if ($schoolId) {
            $builder->where('s.school_id', $schoolId);
        }
        
        $builder->orderBy('sa.created_date', 'DESC');
        
        return $builder->get()->getResultArray();
    }

    /**
     * Save report to file
     */
    private function saveReport($reportData, $outputPath)
    {
        $filename = 'student_platform_report_' . date('Y-m-d_H-i-s') . '.csv';
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
            'Student ID',
            'Student Name',
            'Email',
            'School',
            'Content Title',
            'Platform',
            'Date',
            'Score',
            'Total Questions',
            'Correct Answers',
            'Percentage'
        ]);
        
        // Write data rows
        foreach ($reportData as $row) {
            $percentage = $row['total_questions'] > 0 ? 
                round(($row['correct_answers'] / $row['total_questions']) * 100, 2) : 0;
            
            fputcsv($file, [
                $row['student_id'],
                $row['first_name'] . ' ' . $row['last_name'],
                $row['email'],
                $row['school_name'],
                $row['content_title'],
                $row['platform'],
                $row['created_date'],
                $row['score'],
                $row['total_questions'],
                $row['correct_answers'],
                $percentage . '%'
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
        return WRITEPATH . 'reports' . DIRECTORY_SEPARATOR . 'student_platform';
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
