<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStudentFeePlanIdToInvoiceItems extends Migration
{
    public function up()
    {
        // Check if column already exists before adding
        if (!$this->db->fieldExists('student_fee_plan_id', 't_invoice_item')) {
            $fields = [
                'student_fee_plan_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                    'after' => 'invoice_id',
                    'comment' => 'References student_fee_plans.id - null for custom items/discounts'
                ],
            ];
            $this->forge->addColumn('t_invoice_item', $fields);
            
            // Add index for better query performance
            $this->db->query('CREATE INDEX ix_item_student_fee_plan ON t_invoice_item(student_fee_plan_id)');
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('student_fee_plan_id', 't_invoice_item')) {
            // Drop index first
            $this->db->query('DROP INDEX ix_item_student_fee_plan ON t_invoice_item');
            // Drop column
            $this->forge->dropColumn('t_invoice_item', 'student_fee_plan_id');
        }
    }
}






