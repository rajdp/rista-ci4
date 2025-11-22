<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropCourseFeePlansTable extends Migration
{
    public function up()
    {
        // Drop course_fee_plans table as billing_cycle_days is now stored in tbl_course
        if ($this->db->tableExists('course_fee_plans')) {
            $this->forge->dropTable('course_fee_plans', true);
            log_message('info', 'Dropped course_fee_plans table - billing_cycle_days is now in tbl_course');
        }
    }

    public function down()
    {
        // Recreate course_fee_plans table structure (but data will be lost)
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'school_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'course_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'comment' => 'References course ID',
            ],
            'fee_plan_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => 'Default fee plan for this course',
            ],
            'default_amount' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
                'comment' => 'Override amount (if not using fee plan)',
            ],
            'billing_cycle_days' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'comment' => 'Billing frequency in days',
            ],
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['school_id', 'course_id']);
        $this->forge->addKey('fee_plan_id');
        $this->forge->createTable('course_fee_plans');
    }
}

