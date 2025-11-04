<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateStudentAssignContentTable extends Migration
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
            'class_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
                'comment' => 'Foreign key to classes table',
            ],
            'content_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
                'comment' => 'Foreign key to content table',
            ],
            'start_date' => [
                'type' => 'DATE',
                'null' => false,
                'comment' => 'Assignment start date',
            ],
            'end_date' => [
                'type' => 'DATE',
                'null' => false,
                'default' => '0000-00-00',
                'comment' => 'Assignment end date (0000-00-00 means no end date)',
            ],
            'start_time' => [
                'type' => 'TIME',
                'null' => false,
                'default' => '00:00:00',
                'comment' => 'Daily start time for content access',
            ],
            'end_time' => [
                'type' => 'TIME',
                'null' => false,
                'default' => '23:59:00',
                'comment' => 'Daily end time for content access',
            ],
            'status' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
                'comment' => '1=Active, 0=Inactive',
            ],
            'created_by' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
                'comment' => 'User ID who created the assignment',
            ],
            'created_date' => [
                'type' => 'DATETIME',
                'null' => false,
                'comment' => 'Record creation timestamp',
            ],
        ]);

        // Primary Key
        $this->forge->addPrimaryKey('id');

        // Indexes for better query performance
        $this->forge->addKey('class_id', false, false, 'idx_class_id');
        $this->forge->addKey('content_id', false, false, 'idx_content_id');
        $this->forge->addKey('status', false, false, 'idx_status');
        $this->forge->addKey(['class_id', 'content_id'], false, false, 'idx_class_content');

        // Foreign Keys (optional - uncomment if you want to enforce referential integrity)
        // Note: Make sure the referenced tables exist before enabling these
        // $this->forge->addForeignKey('class_id', 'classes', 'id', 'CASCADE', 'CASCADE', 'fk_sac_class_id');
        // $this->forge->addForeignKey('content_id', 'content', 'id', 'CASCADE', 'CASCADE', 'fk_sac_content_id');
        // $this->forge->addForeignKey('created_by', 'users', 'id', 'SET NULL', 'CASCADE', 'fk_sac_created_by');

        // Create the table
        $this->forge->createTable('student_assign_content');
    }

    public function down()
    {
        // Drop the table
        $this->forge->dropTable('student_assign_content');
    }
}






