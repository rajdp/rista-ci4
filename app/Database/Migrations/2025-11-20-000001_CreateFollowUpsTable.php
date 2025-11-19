<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFollowUpsTable extends Migration
{
    public function up()
    {
        // Follow-ups/Todos table (school-scoped)
        $this->forge->addField([
            'followup_id' => [
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
            'action' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
                'comment' => 'Description of the follow-up action',
            ],
            'owner_user_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
                'comment' => 'User assigned to complete this follow-up',
            ],
            'due_date' => [
                'type' => 'DATE',
                'null' => true,
                'comment' => 'Due date for the follow-up',
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['pending', 'in_progress', 'completed', 'cancelled'],
                'null' => false,
                'default' => 'pending',
            ],
            'related_type' => [
                'type' => 'VARCHAR',
                'constraint' => 40,
                'null' => true,
                'comment' => 'Type of related entity (e.g., registration, course_registration)',
            ],
            'related_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
                'comment' => 'ID of the related entity',
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Additional notes about the follow-up',
            ],
            'completed_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
                'comment' => 'When the follow-up was completed',
            ],
            'completed_by' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
                'comment' => 'User who completed the follow-up',
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ],
            'created_by' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
                'comment' => 'User who created the follow-up',
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'on_update' => 'CURRENT_TIMESTAMP',
            ],
        ]);
        $this->forge->addKey('followup_id', true);
        $this->forge->addKey(['school_id', 'owner_user_id', 'due_date'], false, false, 'ix_followup_by_owner_due');
        $this->forge->addKey(['school_id', 'status', 'due_date'], false, false, 'ix_followup_by_status_due');
        $this->forge->addKey(['related_type', 'related_id'], false, false, 'ix_followup_related');
        $this->forge->createTable('crm_followups', true);
    }

    public function down()
    {
        $this->forge->dropTable('crm_followups', true);
    }
}

