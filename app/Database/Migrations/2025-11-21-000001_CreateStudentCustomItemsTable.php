<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateStudentCustomItemsTable extends Migration
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
            'student_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
                'comment' => 'References user.user_id',
            ],
            'school_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
            ],
            'description' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
                'comment' => 'User-entered description of the item',
            ],
            'amount' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'null' => false,
                'comment' => 'Amount (positive for charges, negative for discounts)',
            ],
            'start_date' => [
                'type' => 'DATE',
                'null' => false,
                'comment' => 'Validity start date',
            ],
            'end_date' => [
                'type' => 'DATE',
                'null' => true,
                'comment' => 'Validity end date (optional)',
            ],
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
                'comment' => 'Whether this item is currently active',
            ],
            'created_by' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
                'comment' => 'User who created this item',
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
        
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['student_id', 'school_id'], false, false, 'idx_student_school');
        $this->forge->addKey(['start_date', 'end_date'], false, false, 'idx_dates');
        $this->forge->addKey('is_active', false, false, 'idx_active');
        
        // Check if table already exists before creating
        if (!$this->db->tableExists('student_custom_items')) {
            $this->forge->createTable('student_custom_items', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('student_custom_items', true);
    }
}

