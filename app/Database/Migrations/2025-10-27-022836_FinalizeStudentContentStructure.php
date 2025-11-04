<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class FinalizeStudentContentStructure extends Migration
{
    public function up()
    {
        // Step 1: Remove old indexes that are no longer needed
        $this->forge->dropKey('student_content', 'student_content_update_idx');
        $this->forge->dropKey('student_content', 'FK_student_content_class_id_idx');
        
        // Step 2: Add new indexes for better performance
        $this->forge->addKey('student_content', ['student_id', 'content_id'], false, false, 'idx_student_id_content_id');
        $this->forge->addKey('student_content', 'class_content_id', false, false, 'idx_class_content_id');
        
        // Step 3: Drop old primary key and add new one
        $this->forge->dropPrimaryKey('student_content');
        $this->forge->addPrimaryKey('student_content', 'id');
        $this->forge->addUniqueKey('student_content', ['student_id', 'content_id', 'class_content_id'], 'unique_student_content');
        
        // Step 4: Remove migration helper columns
        $this->forge->dropColumn('student_content', ['migrated', 'original_class_id']);
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
