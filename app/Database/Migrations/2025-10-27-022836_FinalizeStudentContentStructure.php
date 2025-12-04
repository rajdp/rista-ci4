<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class FinalizeStudentContentStructure extends Migration
{
    public function up()
    {
        // Check if table exists first
        if (!$this->db->tableExists('student_content')) {
            return; // Skip if table doesn't exist
        }

        // Step 1: Remove old indexes that are no longer needed (only if they exist)
        $indexes = $this->db->getIndexData('student_content');
        $indexNames = array_map(fn($idx) => $idx->name, $indexes);

        if (in_array('student_content_update_idx', $indexNames)) {
            $this->forge->dropKey('student_content', 'student_content_update_idx');
        }
        if (in_array('FK_student_content_class_id_idx', $indexNames)) {
            $this->forge->dropKey('student_content', 'FK_student_content_class_id_idx');
        }

        // Step 2: Add new indexes for better performance (only if they don't exist)
        if (!in_array('idx_student_id_content_id', $indexNames)) {
            $this->forge->addKey('student_content', ['student_id', 'content_id'], false, false, 'idx_student_id_content_id');
        }
        if (!in_array('idx_class_content_id', $indexNames)) {
            $this->forge->addKey('student_content', 'class_content_id', false, false, 'idx_class_content_id');
        }

        // Step 3: Add unique key if it doesn't exist
        if (!in_array('unique_student_content', $indexNames)) {
            $this->forge->addUniqueKey('student_content', ['student_id', 'content_id', 'class_content_id'], 'unique_student_content');
        }

        // Step 4: Remove migration helper columns if they exist
        $fields = $this->db->getFieldNames('student_content');
        $columnsToDrop = [];
        if (in_array('migrated', $fields)) {
            $columnsToDrop[] = 'migrated';
        }
        if (in_array('original_class_id', $fields)) {
            $columnsToDrop[] = 'original_class_id';
        }
        if (!empty($columnsToDrop)) {
            $this->forge->dropColumn('student_content', $columnsToDrop);
        }
    }

    public function down()
    {
        // Rollback: Add back migration helper columns
        $this->forge->addColumn('student_content', [
            'migrated' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'comment' => 'Migration tracking flag'
            ],
            'original_class_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
                'comment' => 'Preserved for migration reference'
            ]
        ]);
        
        // Rollback: Restore old indexes
        $this->forge->addKey('student_content', ['student_id', 'content_id', 'class_id'], false, false, 'student_content_update_idx');
        $this->forge->addKey('student_content', 'class_id', false, false, 'FK_student_content_class_id_idx');
        
        // Rollback: Remove new indexes
        $this->forge->dropKey('student_content', 'idx_student_id_content_id');
        $this->forge->dropKey('student_content', 'idx_class_content_id');
        $this->forge->dropKey('student_content', 'unique_student_content');
        
        // Rollback: Restore old primary key structure
        $this->forge->dropPrimaryKey('student_content');
        $this->forge->addPrimaryKey('student_content', ['id', 'class_id']);
    }
}
