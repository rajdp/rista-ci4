<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateReportCardsTables extends Migration
{
    public function up()
    {
        // Table 1: t_rc_template (Report Card Templates - versioned)
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
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => false,
            ],
            'version' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
                'default' => 1,
            ],
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 1,
            ],
            'schema_json' => [
                'type' => 'TEXT',
                'null' => false,
                'comment' => 'JSON defining sections/fields/scales (stored as TEXT)',
            ],
            'created_by' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
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
            ],
        ]);
        $this->forge->addKey('template_id', true);
        $this->forge->addKey(['school_id', 'name', 'version'], false, true, 'ux_school_name_version');
        $this->forge->addKey(['school_id', 'is_active'], false, false, 'ix_school_active');
        $this->forge->createTable('t_rc_template', true, [
            'ENGINE' => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_general_ci',
        ]);

        // Table 2: t_rc_scale (Grading Scales)
        $this->forge->addField([
            'scale_id' => [
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
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => false,
            ],
            'scale_json' => [
                'type' => 'TEXT',
                'null' => false,
                'comment' => 'e.g., [{min:90,max:100,letter:A,gpa:4.0}, ...]',
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
            ],
        ]);
        $this->forge->addKey('scale_id', true);
        $this->forge->addKey(['school_id', 'name'], false, true, 'ux_school_name');
        $this->forge->createTable('t_rc_scale', true, [
            'ENGINE' => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_general_ci',
        ]);

        // Table 3: t_report_card (Report Card Master)
        $this->forge->addField([
            'rc_id' => [
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
            'student_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
            ],
            'class_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
                'comment' => 'References class table',
            ],
            'term' => [
                'type' => 'VARCHAR',
                'constraint' => 60,
                'null' => false,
                'comment' => 'e.g., Fall, Q1, 2025 Spring',
            ],
            'academic_year' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
                'comment' => 'e.g., 2025-26',
            ],
            'template_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
            ],
            'template_version' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['draft', 'ready', 'issued', 'revised', 'revoked'],
                'null' => false,
                'default' => 'draft',
            ],
            'issued_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'issued_by' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'current_version' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
                'default' => 1,
                'comment' => 'increments on revision',
            ],
            'created_by' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
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
            ],
        ]);
        $this->forge->addKey('rc_id', true);
        $this->forge->addKey(['school_id', 'student_id', 'term', 'academic_year'], false, false, 'ix_school_student_term');
        $this->forge->addKey(['school_id', 'status', 'issued_at'], false, false, 'ix_school_status');
        $this->forge->createTable('t_report_card', true, [
            'ENGINE' => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_general_ci',
        ]);

        // Table 4: t_report_card_version (Immutable Version Snapshots)
        $this->forge->addField([
            'rc_ver_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'rc_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
            ],
            'version' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
                'comment' => '1..n',
            ],
            'payload_json' => [
                'type' => 'LONGTEXT',
                'null' => false,
                'comment' => 'full rendered content (sections, grades, comments)',
            ],
            'summary_json' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'cached aggregates (GPA, totals) for search/perf',
            ],
            'created_by' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ],
        ]);
        $this->forge->addKey('rc_ver_id', true);
        $this->forge->addKey(['rc_id', 'version'], false, true, 'ux_rc_version');
        $this->forge->addKey('rc_id', false, false, 'ix_rc');
        $this->forge->createTable('t_report_card_version', true, [
            'ENGINE' => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_general_ci',
        ]);

        // Table 5: t_rc_event (Email Events & Access Audit)
        $this->forge->addField([
            'event_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'rc_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
            ],
            'event_type' => [
                'type' => 'ENUM',
                'constraint' => ['email_sent', 'email_failed', 'portal_view', 'revoked', 'reissued'],
                'null' => false,
            ],
            'actor_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'meta_json' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'provider refs, error message, ip/ua for views',
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ],
        ]);
        $this->forge->addKey('event_id', true);
        $this->forge->addKey(['rc_id', 'event_type', 'created_at'], false, false, 'ix_rc_event');
        $this->forge->createTable('t_rc_event', true, [
            'ENGINE' => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_general_ci',
        ]);
    }

    public function down()
    {
        // Drop tables in reverse order
        $this->forge->dropTable('t_rc_event', true);
        $this->forge->dropTable('t_report_card_version', true);
        $this->forge->dropTable('t_report_card', true);
        $this->forge->dropTable('t_rc_scale', true);
        $this->forge->dropTable('t_rc_template', true);
    }
}
