<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEdQuillV2SchoolScopedTables extends Migration
{
    public function up()
    {
        // Event Outbox (school-scoped)
        $this->forge->addField([
            'id' => [
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
            'event_type' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => false,
            ],
            'payload_json' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ],
            'claimed_by' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
            ],
            'claimed_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'processed_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['school_id', 'event_type', 'created_at'], false, false, 'ix_outbox_lookup');
        $this->forge->addKey(['processed_at', 'claimed_by', 'created_at'], false, false, 'ix_outbox_claim');
        $this->forge->createTable('t_event_outbox', true);

        // Audit Log (school-scoped)
        $this->forge->addField([
            'audit_id' => [
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
            'actor_user_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'entity_type' => [
                'type' => 'VARCHAR',
                'constraint' => 40,
                'null' => true,
            ],
            'entity_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'action' => [
                'type' => 'VARCHAR',
                'constraint' => 40,
                'null' => true,
            ],
            'before_json' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'after_json' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ],
        ]);
        $this->forge->addKey('audit_id', true);
        $this->forge->addKey(['school_id', 'entity_type', 'entity_id', 'created_at'], false, false, 'ix_audit_lookup');
        $this->forge->createTable('t_audit_log', true);

        // Feature Flags (school-scoped)
        $this->forge->addField([
            'school_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
            ],
            'flag_key' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => false,
            ],
            'flag_value' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'on_update' => 'CURRENT_TIMESTAMP',
            ],
        ]);
        $this->forge->addPrimaryKey(['school_id', 'flag_key']);
        $this->forge->createTable('t_feature_flag', true);

        // Message Templates (school-scoped)
        $this->forge->addField([
            'template_id' => [
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
            'channel' => [
                'type' => 'ENUM',
                'constraint' => ['email', 'sms', 'whatsapp'],
                'null' => false,
            ],
            'purpose' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => false,
            ],
            'subject' => [
                'type' => 'VARCHAR',
                'constraint' => 160,
                'null' => true,
            ],
            'body' => [
                'type' => 'MEDIUMTEXT',
                'null' => false,
            ],
            'locale' => [
                'type' => 'VARCHAR',
                'constraint' => 8,
                'null' => false,
                'default' => 'en',
            ],
            'version' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
                'default' => 1,
            ],
        ]);
        $this->forge->addKey('template_id', true);
        $this->forge->addUniqueKey(['school_id', 'channel', 'purpose', 'version'], 'ux_tpl');
        $this->forge->createTable('t_message_template', true);

        // Message Log (school-scoped)
        $this->forge->addField([
            'msg_id' => [
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
            'channel' => [
                'type' => 'ENUM',
                'constraint' => ['email', 'sms', 'whatsapp'],
                'null' => false,
            ],
            'to_parent_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'to_student_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'template_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'rendered_body' => [
                'type' => 'MEDIUMTEXT',
                'null' => false,
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['queued', 'sent', 'failed', 'bounced', 'delivered', 'opened', 'clicked'],
                'null' => false,
                'default' => 'queued',
            ],
            'provider_id' => [
                'type' => 'VARCHAR',
                'constraint' => 128,
                'null' => true,
            ],
            'sent_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'opened_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'clicked_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('msg_id', true);
        $this->forge->addKey(['school_id', 'to_parent_id', 'sent_at'], false, false, 'ix_msg_by_parent');
        $this->forge->addKey(['school_id', 'channel', 'sent_at'], false, false, 'ix_msg_by_channel');
        $this->forge->createTable('t_message_log', true);

        // Marketing KPI Daily (school-scoped)
        // MySQL 5.7 doesn't support functional indexes, so we use a generated column for the PK
        $this->db->query("
            CREATE TABLE IF NOT EXISTS t_marketing_kpi_daily (
                school_id BIGINT UNSIGNED NOT NULL,
                day DATE NOT NULL,
                source VARCHAR(64) NULL DEFAULT '',
                leads INT NOT NULL DEFAULT 0,
                enrollments INT NOT NULL DEFAULT 0,
                revenue_cents BIGINT NOT NULL DEFAULT 0,
                PRIMARY KEY (school_id, day, source)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Revenue Daily (school-scoped)
        $this->forge->addField([
            'school_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
            ],
            'day' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'mrr_cents' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'null' => false,
                'default' => 0,
            ],
            'arr_cents' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'null' => false,
                'default' => 0,
            ],
            'on_time_pay_pct' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'null' => false,
                'default' => 0.00,
            ],
            'ar_overdue_cents' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'null' => false,
                'default' => 0,
            ],
        ]);
        $this->forge->addPrimaryKey(['school_id', 'day']);
        $this->forge->createTable('t_revenue_daily', true);
    }

    public function down()
    {
        $this->forge->dropTable('t_revenue_daily', true);
        $this->forge->dropTable('t_marketing_kpi_daily', true);
        $this->forge->dropTable('t_message_log', true);
        $this->forge->dropTable('t_message_template', true);
        $this->forge->dropTable('t_feature_flag', true);
        $this->forge->dropTable('t_audit_log', true);
        $this->forge->dropTable('t_event_outbox', true);
    }
}

