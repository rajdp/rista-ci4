<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBillingPaymentsTables extends Migration
{
    public function up()
    {
        // t_subscription - Subscription management (extends t_billing_schedule concept)
        $this->forge->addField([
            'subscription_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'school_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
            ],
            'student_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
            ],
            'course_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
            ],
            'term' => [
                'type' => 'ENUM',
                'constraint' => ['one_time', 'monthly', 'quarterly', 'annual'],
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['active', 'on_hold', 'canceled', 'completed'],
                'default' => 'active',
            ],
            'amount_cents' => [
                'type' => 'INT',
                'comment' => 'base recurring amount without tax/fees',
            ],
            'anchor_day' => [
                'type' => 'TINYINT',
                'null' => true,
                'comment' => 'preferred billing day (1-28)',
            ],
            'next_billing_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'autopay_enabled' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'deposit_cents' => [
                'type' => 'INT',
                'default' => 0,
            ],
            'deposit_type' => [
                'type' => 'ENUM',
                'constraint' => ['none', 'refundable', 'non_refundable'],
                'default' => 'none',
            ],
            'proration_policy' => [
                'type' => 'ENUM',
                'constraint' => ['none', 'daily', 'half_month'],
                'default' => 'daily',
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
        ]);
        $this->forge->addKey('subscription_id', true);
        $this->forge->addKey(['school_id', 'student_id', 'status']);
        $this->forge->addKey(['school_id', 'next_billing_date']);
        $this->forge->createTable('t_subscription', true);

        // t_invoice - Invoice management
        $this->forge->addField([
            'invoice_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'school_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
            ],
            'student_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
            ],
            'subscription_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'null' => true,
            ],
            'invoice_no' => [
                'type' => 'VARCHAR',
                'constraint' => 40,
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['draft', 'open', 'paid', 'voided', 'failed', 'past_due'],
                'default' => 'draft',
            ],
            'issue_date' => [
                'type' => 'DATE',
            ],
            'due_date' => [
                'type' => 'DATE',
            ],
            'currency' => [
                'type' => 'CHAR',
                'constraint' => 3,
                'default' => 'USD',
            ],
            'subtotal_cents' => [
                'type' => 'INT',
                'default' => 0,
            ],
            'tax_cents' => [
                'type' => 'INT',
                'default' => 0,
            ],
            'late_fee_cents' => [
                'type' => 'INT',
                'default' => 0,
            ],
            'discount_cents' => [
                'type' => 'INT',
                'default' => 0,
            ],
            'total_cents' => [
                'type' => 'INT',
                'default' => 0,
            ],
            'balance_cents' => [
                'type' => 'INT',
                'default' => 0,
                'comment' => 'remaining due',
            ],
            'meta_json' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
        ]);
        $this->forge->addKey('invoice_id', true);
        $this->forge->addUniqueKey(['school_id', 'invoice_no']);
        $this->forge->addKey(['school_id', 'status', 'due_date']);
        $this->forge->addKey(['student_id']);
        $this->forge->createTable('t_invoice', true);

        // t_invoice_item - Invoice line items
        $this->forge->addField([
            'item_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'invoice_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
            ],
            'description' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'qty' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 1.0,
            ],
            'unit_price_cents' => [
                'type' => 'INT',
            ],
            'line_total_cents' => [
                'type' => 'INT',
                'comment' => 'qty * unit_price',
            ],
            'type' => [
                'type' => 'ENUM',
                'constraint' => ['tuition', 'deposit', 'material', 'registration', 'late_fee', 'adjustment', 'tax'],
                'default' => 'tuition',
            ],
            'meta_json' => [
                'type' => 'TEXT',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('item_id', true);
        $this->forge->addKey(['invoice_id']);
        $this->forge->createTable('t_invoice_item', true);

        // t_payment_method - Tokenized payment methods
        $this->forge->addField([
            'pm_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'school_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
            ],
            'student_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
            ],
            'provider' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'comment' => 'e.g., stripe, forte',
            ],
            'fingerprint' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
                'comment' => 'for dedupe display only',
            ],
            'token' => [
                'type' => 'VARCHAR',
                'constraint' => 191,
                'comment' => 'tokenized (PCI SAQ-A)',
            ],
            'brand' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'null' => true,
            ],
            'last4' => [
                'type' => 'VARCHAR',
                'constraint' => 4,
                'null' => true,
            ],
            'exp_month' => [
                'type' => 'TINYINT',
                'null' => true,
            ],
            'exp_year' => [
                'type' => 'SMALLINT',
                'null' => true,
            ],
            'is_default' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
        ]);
        $this->forge->addKey('pm_id', true);
        $this->forge->addKey(['school_id', 'student_id', 'is_default']);
        $this->forge->createTable('t_payment_method', true);

        // t_payment_txn - Payment transactions
        $this->forge->addField([
            'txn_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'school_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
            ],
            'invoice_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
            ],
            'amount_cents' => [
                'type' => 'INT',
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['initiated', 'succeeded', 'failed', 'refunded', 'voided'],
            ],
            'provider' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
            ],
            'provider_txn_id' => [
                'type' => 'VARCHAR',
                'constraint' => 96,
                'null' => true,
            ],
            'error_code' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
            ],
            'error_msg' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'idempotency_key' => [
                'type' => 'VARCHAR',
                'constraint' => 96,
                'null' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
        ]);
        $this->forge->addKey('txn_id', true);
        $this->forge->addKey(['school_id', 'invoice_id', 'status']);
        $this->forge->addKey(['idempotency_key']);
        $this->forge->createTable('t_payment_txn', true);

        // t_refund - Refund transactions
        $this->forge->addField([
            'refund_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'school_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
            ],
            'txn_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
            ],
            'amount_cents' => [
                'type' => 'INT',
            ],
            'provider' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
            ],
            'provider_refund_id' => [
                'type' => 'VARCHAR',
                'constraint' => 96,
                'null' => true,
            ],
            'reason' => [
                'type' => 'VARCHAR',
                'constraint' => 128,
                'null' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
        ]);
        $this->forge->addKey('refund_id', true);
        $this->forge->addKey(['txn_id']);
        $this->forge->createTable('t_refund', true);

        // t_late_fee_policy - Late fee configuration per school
        $this->forge->addField([
            'policy_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'school_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
            ],
            'flat_cents' => [
                'type' => 'INT',
                'default' => 0,
            ],
            'grace_days' => [
                'type' => 'TINYINT',
                'default' => 0,
            ],
            'repeat_every_days' => [
                'type' => 'TINYINT',
                'null' => true,
                'comment' => 'null = once only',
            ],
            'apply_time' => [
                'type' => 'TIME',
                'default' => '03:00:00',
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
        ]);
        $this->forge->addKey('policy_id', true);
        $this->forge->addUniqueKey(['school_id']);
        $this->forge->createTable('t_late_fee_policy', true);

        // t_dunning_step - Dunning workflow configuration
        $this->forge->addField([
            'step_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'school_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
            ],
            'day_offset' => [
                'type' => 'INT',
                'comment' => 'days after due_date',
            ],
            'action' => [
                'type' => 'ENUM',
                'constraint' => ['email', 'retry_charge', 'both'],
                'default' => 'email',
            ],
            'email_template_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'null' => true,
            ],
            'retry_rule' => [
                'type' => 'ENUM',
                'constraint' => ['immediate', 'next_day', 'next_window'],
                'null' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
        ]);
        $this->forge->addKey('step_id', true);
        $this->forge->addKey(['school_id', 'day_offset']);
        $this->forge->createTable('t_dunning_step', true);

        // t_webhook_event - Payment gateway webhook events
        $this->forge->addField([
            'event_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'school_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
            ],
            'provider' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
            ],
            'event_type' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
            ],
            'event_idempotency' => [
                'type' => 'VARCHAR',
                'constraint' => 96,
                'null' => true,
            ],
            'payload' => [
                'type' => 'LONGTEXT',
            ],
            'processed' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
        ]);
        $this->forge->addKey('event_id', true);
        $this->forge->addKey(['school_id', 'event_type', 'processed']);
        $this->forge->addKey(['event_idempotency']);
        $this->forge->createTable('t_webhook_event', true);

        // t_audit_log - Audit trail for billing operations
        $this->forge->addField([
            'audit_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'school_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
            ],
            'actor_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'null' => true,
            ],
            'actor_role' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'null' => true,
            ],
            'entity' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'comment' => 'e.g., invoice, subscription, payment',
            ],
            'entity_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
            ],
            'action' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'comment' => 'e.g., create, update, charge, refund, email',
            ],
            'meta_json' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
        ]);
        $this->forge->addKey('audit_id', true);
        $this->forge->addKey(['school_id', 'entity', 'entity_id', 'created_at']);
        $this->forge->createTable('t_audit_log', true);

        log_message('info', 'Billing and payments tables created successfully');
    }

    public function down()
    {
        $this->forge->dropTable('t_audit_log', true);
        $this->forge->dropTable('t_webhook_event', true);
        $this->forge->dropTable('t_dunning_step', true);
        $this->forge->dropTable('t_late_fee_policy', true);
        $this->forge->dropTable('t_refund', true);
        $this->forge->dropTable('t_payment_txn', true);
        $this->forge->dropTable('t_payment_method', true);
        $this->forge->dropTable('t_invoice_item', true);
        $this->forge->dropTable('t_invoice', true);
        $this->forge->dropTable('t_subscription', true);

        log_message('info', 'Billing and payments tables dropped successfully');
    }
}
