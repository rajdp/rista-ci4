<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ModifyStudentContentStructure extends Migration
{
    public function up()
    {
        // Step 1: Add temporary columns to track migration status
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
    }

    public function down()
    {
        // Remove migration helper columns
        $this->forge->dropColumn('student_content', ['migrated', 'original_class_id']);
    }
}
