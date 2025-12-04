<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateStudentContentClassAccess extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'student_content_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'class_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'class_content_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'created_date' => [
                'type' => 'DATETIME',
            ],
            'created_by' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['student_content_id', 'class_id'], 'unique_access');
        $this->forge->addKey('student_content_id', false, false, 'idx_student_content_id');
        $this->forge->addKey('class_id', false, false, 'idx_class_id');
        $this->forge->addKey('class_content_id', false, false, 'idx_class_content_id');
        
        $this->forge->addForeignKey('student_content_id', 'student_content', 'id', 'CASCADE', 'CASCADE', 'fk_scca_student_content');
        $this->forge->addForeignKey('class_id', 'classes', 'id', 'CASCADE', 'CASCADE', 'fk_scca_class');
        
        $this->forge->createTable('student_content_class_access', true); // true = IF NOT EXISTS
    }

    public function down()
    {
        $this->forge->dropTable('student_content_class_access', true); // true = IF EXISTS
    }
}
