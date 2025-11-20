<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRecurringBillingTables extends Migration
{
    public function up()
    {
        // Per enrollment billing state
        $this->forge->addField([
            'schedule_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'school_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
            ],
            'enrollment_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
                'comment' => 'References student_courses.id',
            ],
            'student_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
            ],
            'course_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'term' => [
                'type' => 'ENUM',
                'constraint' => ['one_time', 'weekly', 'monthly', 'yearly'],
                'null' => false,
            ],
            'anchor_day' => [
                'type' => 'TINYINT',
                'constraint' => 2,
                'unsigned' => true,
                'null' => true,
                'comment' => '1..31 for monthly, or day-of-month for yearly',
            ],
            'anchor_month' => [
                'type' => 'TINYINT',
                'constraint' => 2,
                'unsigned' => true,
                'null' => true,
                'comment' => '1..12 for yearly (optional)',
            ],
            'next_billing_date' => [
                'type' => 'DATE',
                'null' => true,
                'comment' => 'NULL for one_time after charged',
            ],
            'deposit_policy' => [
                'type' => 'ENUM',
                'constraint' => ['none', 'refundable', 'non_ref_last_month'],
                'null' => false,
                'default' => 'none',
            ],
            'deposit_cents' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
                'default' => 0,
            ],
            'has_prorated_first' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 0,
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['active', 'paused', 'ended'],
                'null' => false,
                'default' => 'active',
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'on_update' => 'CURRENT_TIMESTAMP',
            ],
        ]);
        $this->forge->addPrimaryKey('schedule_id');
        $this->forge->addUniqueKey('enrollment_id', 'ux_sched_enrollment');
        $this->forge->addKey(['school_id', 'next_billing_date'], false, false, 'ix_sched_school_next');
        $this->forge->addKey(['school_id', 'term', 'status'], false, false, 'ix_sched_school_term');
        $this->forge->createTable('t_billing_schedule', true);

        // Onboarding fee configuration (school-scoped defaults; can also be per-course)
        $this->forge->addField([
            'policy_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'school_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
            ],
            'registration_fee_cents' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
                'default' => 0,
            ],
            'material_fee_cents' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
                'default' => 0,
            ],
            'other_fee_cents' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
                'default' => 0,
            ],
            'effective_date' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 1,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'on_update' => 'CURRENT_TIMESTAMP',
            ],
        ]);
        $this->forge->addPrimaryKey('policy_id');
        $this->forge->addKey(['school_id', 'is_active', 'effective_date'], false, false, 'ix_fee_school');
        $this->forge->createTable('t_fee_policy', true);

        // Deposit ledger (tracks charges, applications, refunds)
        $this->forge->addField([
            'deposit_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'school_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
            ],
            'enrollment_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
                'comment' => 'References student_courses.id',
            ],
            'amount_cents' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'null' => false,
                'comment' => 'Can be negative for refunds',
            ],
            'direction' => [
                'type' => 'ENUM',
                'constraint' => ['charge', 'apply_credit', 'refund'],
                'null' => false,
            ],
            'note' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ],
        ]);
        $this->forge->addPrimaryKey('deposit_id');
        $this->forge->addKey(['enrollment_id', 'created_at'], false, false, 'ix_dep_enrollment');
        $this->forge->createTable('t_deposit_ledger', true);

        // Billing runs (idempotency & audit)
        $this->forge->addField([
            'run_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'school_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
            ],
            'run_date' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'claimed_by' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ],
        ]);
        $this->forge->addPrimaryKey('run_id');
        $this->forge->addUniqueKey(['school_id', 'run_date'], 'ux_run');
        $this->forge->createTable('t_billing_run', true);

        // Optional: mark which schedule was processed in a run (prevent duplicates)
        $this->forge->addField([
            'run_item_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'run_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
            ],
            'schedule_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
            ],
            'invoice_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['invoiced', 'skipped', 'failed'],
                'null' => false,
            ],
            'message' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ],
        ]);
        $this->forge->addPrimaryKey('run_item_id');
        $this->forge->addUniqueKey(['run_id', 'schedule_id'], 'ux_run_schedule');
        $this->forge->createTable('t_billing_run_item', true);

        // Invoice items (line items for invoices)
        $this->forge->addField([
            'invoice_item_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'invoice_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
                'comment' => 'References invoices.id',
            ],
            'description' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'quantity' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
                'default' => 1,
            ],
            'unit_cents' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'null' => false,
                'comment' => 'Price per unit in cents',
            ],
            'total_cents' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'null' => false,
                'comment' => 'Total for this line item in cents',
            ],
            'kind' => [
                'type' => 'ENUM',
                'constraint' => ['recurring', 'proration', 'onboarding', 'deposit', 'credit', 'tax'],
                'null' => false,
            ],
            'course_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'enrollment_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => 'References student_courses.id',
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ],
        ]);
        $this->forge->addPrimaryKey('invoice_item_id');
        $this->forge->addKey('invoice_id', false, false, 'ix_item_invoice');
        $this->forge->addKey(['enrollment_id', 'kind'], false, false, 'ix_item_enrollment');
        $this->forge->createTable('t_invoice_item', true);
    }

    public function down()
    {
        $this->forge->dropTable('t_invoice_item', true);
        $this->forge->dropTable('t_billing_run_item', true);
        $this->forge->dropTable('t_billing_run', true);
        $this->forge->dropTable('t_deposit_ledger', true);
        $this->forge->dropTable('t_fee_policy', true);
        $this->forge->dropTable('t_billing_schedule', true);
    }
}

