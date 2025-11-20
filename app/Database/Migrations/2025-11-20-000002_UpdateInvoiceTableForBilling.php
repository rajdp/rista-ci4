<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateInvoiceTableForBilling extends Migration
{
    public function up()
    {
        // Check if school_id column exists, add if missing
        if (!$this->db->fieldExists('school_id', 'invoices')) {
            $this->forge->addColumn('invoices', [
                'school_id' => [
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'unsigned' => true,
                    'null' => true,
                    'after' => 'id',
                ],
            ]);
            
            // Add index for school_id
            $this->forge->addKey('school_id', false, false, 'ix_invoices_school');
        }

        // Check if enrollment_id column exists, add if missing
        if (!$this->db->fieldExists('enrollment_id', 'invoices')) {
            $this->forge->addColumn('invoices', [
                'enrollment_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                    'comment' => 'References student_courses.id',
                    'after' => 'student_id',
                ],
            ]);
            
            // Add index for enrollment_id
            $this->forge->addKey('enrollment_id', false, false, 'ix_invoices_enrollment');
        }

        // Update status ENUM to include 'failed' if not already present
        // Note: MySQL doesn't support direct ENUM modification, so we need to use ALTER TABLE
        $this->db->query("
            ALTER TABLE invoices 
            MODIFY COLUMN status ENUM('draft', 'sent', 'paid', 'void', 'failed') 
            NOT NULL DEFAULT 'draft'
        ");

        // Add total_cents column if using cents (optional, for consistency)
        if (!$this->db->fieldExists('total_cents', 'invoices')) {
            $this->forge->addColumn('invoices', [
                'total_cents' => [
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'unsigned' => true,
                    'null' => true,
                    'comment' => 'Total amount in cents (derived from amount_due)',
                    'after' => 'amount_paid',
                ],
            ]);
        }
    }

    public function down()
    {
        // Remove added columns
        if ($this->db->fieldExists('total_cents', 'invoices')) {
            $this->forge->dropColumn('invoices', 'total_cents');
        }

        if ($this->db->fieldExists('enrollment_id', 'invoices')) {
            $this->forge->dropColumn('invoices', 'enrollment_id');
        }

        if ($this->db->fieldExists('school_id', 'invoices')) {
            $this->forge->dropColumn('invoices', 'school_id');
        }

        // Revert status ENUM (remove 'failed')
        $this->db->query("
            ALTER TABLE invoices 
            MODIFY COLUMN status ENUM('draft', 'sent', 'paid', 'void') 
            NOT NULL DEFAULT 'draft'
        ");
    }
}


