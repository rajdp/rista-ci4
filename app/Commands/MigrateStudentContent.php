<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class MigrateStudentContent extends BaseCommand
{
    protected $group = 'Database';
    protected $name = 'migrate:student-content';
    protected $description = 'Consolidate duplicate student_content records and update access table';

    public function run(array $params)
    {
        $db = \Config\Database::connect();
        
        CLI::write('Starting student_content consolidation...', 'yellow');
        
        // Find duplicates
        $duplicates = $db->query("
            SELECT student_id, content_id, COUNT(*) as count
            FROM student_content
            GROUP BY student_id, content_id
            HAVING count > 1
        ")->getResultArray();
        
        CLI::write("Found " . count($duplicates) . " duplicate groups", 'green');
        
        foreach ($duplicates as $duplicate) {
            $this->consolidateDuplicates($duplicate['student_id'], $duplicate['content_id'], $db);
        }
        
        CLI::write('Consolidation complete!', 'green');
    }
    
    private function consolidateDuplicates($studentId, $contentId, $db)
    {
        // Get all records for this student+content combination
        $records = $db->query("
            SELECT sc.*, scca.class_id as access_class_id
            FROM student_content sc
            LEFT JOIN student_content_class_access scca ON sc.id = scca.student_content_id
            WHERE sc.student_id = ? AND sc.content_id = ?
            ORDER BY sc.status DESC, sc.modified_date DESC
        ", [$studentId, $contentId])->getResultArray();
        
        if (count($records) <= 1) return;
        
        // Keep the record with most progress (highest status, most recent modification)
        $keepRecord = $records[0];
        $removeRecords = array_slice($records, 1);
        
        CLI::write("Consolidating student {$studentId}, content {$contentId}: keeping record {$keepRecord['id']}", 'cyan');
        
        // Transfer class access from records to be removed
        foreach ($removeRecords as $removeRecord) {
            if ($removeRecord['access_class_id']) {
                // Check if this class access already exists for the kept record
                $existingAccess = $db->query("
                    SELECT id FROM student_content_class_access 
                    WHERE student_content_id = ? AND class_id = ?
                ", [$keepRecord['id'], $removeRecord['access_class_id']])->getRowArray();
                
                if (!$existingAccess) {
                    // Add class access to the kept record
                    $db->query("
                        INSERT INTO student_content_class_access 
                        (student_content_id, class_id, class_content_id, created_date, created_by)
                        VALUES (?, ?, ?, ?, ?)
                    ", [
                        $keepRecord['id'],
                        $removeRecord['access_class_id'],
                        $removeRecord['class_content_id'],
                        $removeRecord['created_date'],
                        $removeRecord['created_by']
                    ]);
                    
                    CLI::write("  Added class access for class {$removeRecord['access_class_id']}", 'white');
                }
            }
            
            // Remove the duplicate record
            $db->query("DELETE FROM student_content WHERE id = ?", [$removeRecord['id']]);
            CLI::write("  Removed duplicate record {$removeRecord['id']}", 'red');
        }
    }
}











