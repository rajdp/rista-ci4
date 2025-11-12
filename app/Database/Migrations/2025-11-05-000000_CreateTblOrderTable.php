<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTblOrderTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'order_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
                'comment' => 'Foreign key to user table',
            ],
            'course_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
                'comment' => 'Foreign key to tbl_course table',
            ],
            'school_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
                'comment' => 'School identifier',
            ],
            'payment_id' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => 'Payment/Order identifier',
            ],
            'payment_date' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Payment date',
            ],
            'payment_status' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'comment' => '1=Success, 0=Failed',
            ],
            'cart_data' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'JSON data containing course details, schedule, price, quantity',
            ],
            'total_amount' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0.00,
                'comment' => 'Total order amount',
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
                'comment' => 'User ID who created the order',
            ],
            'created_date' => [
                'type' => 'DATETIME',
                'null' => false,
                'comment' => 'Record creation timestamp',
            ],
            'modified_date' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Last modification timestamp',
            ],
        ]);

        // Primary Key
        $this->forge->addPrimaryKey('order_id');

        // Indexes for better query performance
        $this->forge->addKey('user_id', false, false, 'idx_user_id');
        $this->forge->addKey('course_id', false, false, 'idx_course_id');
        $this->forge->addKey('school_id', false, false, 'idx_school_id');
        $this->forge->addKey('payment_id', false, false, 'idx_payment_id');
        $this->forge->addKey('payment_status', false, false, 'idx_payment_status');
        $this->forge->addKey('created_date', false, false, 'idx_created_date');
        $this->forge->addKey('status', false, false, 'idx_status');

        // Create the table
        $this->forge->createTable('tbl_order');
    }

    public function down()
    {
        // Drop the table
        $this->forge->dropTable('tbl_order');
    }
}




