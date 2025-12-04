<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateStudentPortalTables extends Migration
{
    public function up()
    {
        // Table 1: t_student_profile_change_request
        $this->forge->addField([
            'request_id' => [
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
                'comment' => 'References user.user_id',
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['pending', 'in_review', 'approved', 'rejected', 'needs_info', 'cancelled'],
                'null' => false,
                'default' => 'pending',
            ],
            'request_type' => [
                'type' => 'ENUM',
                'constraint' => ['personal_info', 'contact_info', 'address', 'emergency_contact', 'other'],
                'null' => false,
                'default' => 'personal_info',
            ],
            'changes_json' => [
                'type' => 'TEXT',
                'null' => false,
                'comment' => 'JSON: {"field_name": {"old": "value", "new": "value"}}',
            ],
            'reason' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'reviewed_by' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'reviewed_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'admin_notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'rejection_reason' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
                'null' => true,
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
        $this->forge->addKey('request_id', true);
        $this->forge->addKey(['school_id', 'student_id', 'status'], false, false, 'ix_school_student_status');
        $this->forge->addKey(['school_id', 'status', 'created_at'], false, false, 'ix_school_status_created');
        $this->forge->addKey('reviewed_by', false, false, 'ix_reviewed_by');
        $this->forge->createTable('t_student_profile_change_request', true, [
            'ENGINE' => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_general_ci',
        ]);

        // Table 2: t_student_absence_request
        $this->forge->addField([
            'absence_id' => [
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
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['pending', 'approved', 'rejected', 'cancelled'],
                'null' => false,
                'default' => 'pending',
            ],
            'start_date' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'end_date' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'absence_type' => [
                'type' => 'ENUM',
                'constraint' => ['illness', 'family_emergency', 'medical_appointment', 'religious', 'school_activity', 'other'],
                'null' => false,
            ],
            'reason' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'is_advance_notice' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 1,
                'comment' => '1=advance, 0=retroactive',
            ],
            'class_ids' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Comma-separated class IDs or NULL for all',
            ],
            'has_documentation' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 0,
            ],
            'reviewed_by' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'reviewed_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'admin_notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'rejection_reason' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
                'null' => true,
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
        $this->forge->addKey('absence_id', true);
        $this->forge->addKey(['school_id', 'student_id', 'status'], false, false, 'ix_school_student_status');
        $this->forge->addKey(['school_id', 'start_date', 'status'], false, false, 'ix_school_date_status');
        $this->forge->addKey('reviewed_by', false, false, 'ix_reviewed_by');
        $this->forge->createTable('t_student_absence_request', true, [
            'ENGINE' => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_general_ci',
        ]);

        // Table 3: t_student_special_request
        $this->forge->addField([
            'request_id' => [
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
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['pending', 'in_review', 'approved', 'rejected', 'needs_info', 'completed', 'cancelled'],
                'null' => false,
                'default' => 'pending',
            ],
            'request_type' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
                'comment' => 'schedule_change, accommodation, make_up_work, etc.',
            ],
            'title' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'priority' => [
                'type' => 'ENUM',
                'constraint' => ['low', 'normal', 'high', 'urgent'],
                'null' => false,
                'default' => 'normal',
            ],
            'related_class_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'related_course_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'assigned_to' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
                'comment' => 'Admin user assigned to handle',
            ],
            'assigned_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'reviewed_by' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'reviewed_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'resolution_notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'due_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'completed_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
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
        $this->forge->addKey('request_id', true);
        $this->forge->addKey(['school_id', 'student_id', 'status'], false, false, 'ix_school_student_status');
        $this->forge->addKey(['school_id', 'request_type', 'status'], false, false, 'ix_school_type_status');
        $this->forge->addKey('assigned_to', false, false, 'ix_assigned_to');
        $this->forge->addKey(['school_id', 'status', 'created_at'], false, false, 'ix_school_status_created');
        $this->forge->createTable('t_student_special_request', true, [
            'ENGINE' => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_general_ci',
        ]);

        // Table 4: t_request_conversation
        $this->forge->addField([
            'conversation_id' => [
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
            'request_type' => [
                'type' => 'ENUM',
                'constraint' => ['profile_change', 'absence', 'special_request', 'document'],
                'null' => false,
            ],
            'request_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
                'comment' => 'Foreign key to specific request table',
            ],
            'author_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
                'comment' => 'User who wrote this message',
            ],
            'author_role_id' => [
                'type' => 'TINYINT',
                'constraint' => 3,
                'null' => false,
                'comment' => 'Role at time of writing',
            ],
            'message' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'is_internal' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 0,
                'comment' => '1=admin-only note, 0=visible to student',
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ],
        ]);
        $this->forge->addKey('conversation_id', true);
        $this->forge->addKey(['school_id', 'request_type', 'request_id', 'created_at'], false, false, 'ix_school_request');
        $this->forge->addKey('author_id', false, false, 'ix_author');
        $this->forge->createTable('t_request_conversation', true, [
            'ENGINE' => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_general_ci',
        ]);

        // Table 5: t_student_document
        $this->forge->addField([
            'document_id' => [
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
            'document_type' => [
                'type' => 'ENUM',
                'constraint' => ['transcript', 'medical', 'permission_form', 'id', 'insurance', 'immunization', 'other'],
                'null' => false,
            ],
            'original_filename' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'stored_filename' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
                'comment' => 'Hashed/unique filename on disk',
            ],
            'file_path' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
                'null' => false,
                'comment' => 'Relative path from upload root',
            ],
            'file_size_bytes' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
            ],
            'mime_type' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['pending', 'approved', 'rejected', 'expired', 'replaced'],
                'null' => false,
                'default' => 'pending',
            ],
            'reviewed_by' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'reviewed_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'review_notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'expiration_date' => [
                'type' => 'DATE',
                'null' => true,
                'comment' => 'For documents that expire',
            ],
            'related_request_type' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'comment' => 'profile_change, absence, special_request',
            ],
            'related_request_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'is_deleted' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 0,
            ],
            'deleted_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'uploaded_at' => [
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
        $this->forge->addKey('document_id', true);
        $this->forge->addKey(['school_id', 'student_id', 'status', 'is_deleted'], false, false, 'ix_school_student_status');
        $this->forge->addKey(['school_id', 'document_type', 'status'], false, false, 'ix_school_type_status');
        $this->forge->addKey('reviewed_by', false, false, 'ix_reviewed_by');
        $this->forge->addKey(['related_request_type', 'related_request_id'], false, false, 'ix_related_request');
        $this->forge->createTable('t_student_document', true, [
            'ENGINE' => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_general_ci',
        ]);

        // Table 6: t_special_request_type
        $this->forge->addField([
            'type_id' => [
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
            'type_key' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
                'comment' => 'Unique key like schedule_change',
            ],
            'display_name' => [
                'type' => 'VARCHAR',
                'constraint' => 150,
                'null' => false,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 1,
            ],
            'requires_admin_approval' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 1,
            ],
            'auto_assign_to_role' => [
                'type' => 'TINYINT',
                'constraint' => 3,
                'null' => true,
                'comment' => 'Role ID to auto-assign, or NULL',
            ],
            'display_order' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
                'default' => 100,
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
        $this->forge->addKey('type_id', true);
        $this->forge->addKey(['school_id', 'type_key'], false, true, 'ux_school_type_key');
        $this->forge->addKey(['school_id', 'is_active', 'display_order'], false, false, 'ix_school_active_order');
        $this->forge->createTable('t_special_request_type', true, [
            'ENGINE' => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_general_ci',
        ]);

        // Table 7: t_student_portal_audit
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
            'entity_type' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
                'comment' => 'profile, absence_request, document, etc.',
            ],
            'entity_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
            ],
            'action' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
                'comment' => 'create, update, approve, reject, delete, etc.',
            ],
            'actor_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
            ],
            'actor_role_id' => [
                'type' => 'TINYINT',
                'constraint' => 3,
                'null' => false,
            ],
            'changes_json' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Before/after values if applicable',
            ],
            'ip_address' => [
                'type' => 'VARCHAR',
                'constraint' => 45,
                'null' => true,
            ],
            'user_agent' => [
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
        $this->forge->addKey('audit_id', true);
        $this->forge->addKey(['school_id', 'entity_type', 'entity_id', 'created_at'], false, false, 'ix_school_entity');
        $this->forge->addKey(['actor_id', 'created_at'], false, false, 'ix_actor');
        $this->forge->createTable('t_student_portal_audit', true, [
            'ENGINE' => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_general_ci',
        ]);
    }

    public function down()
    {
        // Drop tables in reverse order
        $this->forge->dropTable('t_student_portal_audit', true);
        $this->forge->dropTable('t_special_request_type', true);
        $this->forge->dropTable('t_student_document', true);
        $this->forge->dropTable('t_request_conversation', true);
        $this->forge->dropTable('t_student_special_request', true);
        $this->forge->dropTable('t_student_absence_request', true);
        $this->forge->dropTable('t_student_profile_change_request', true);
    }
}
