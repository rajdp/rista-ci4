<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAppointmentsTables extends Migration
{
    public function up()
    {
        // t_appt_availability
        $this->forge->addField([
            'availability_id'   => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'school_id'         => ['type' => 'BIGINT', 'unsigned' => true],
            'admin_user_id'     => ['type' => 'BIGINT', 'unsigned' => true],
            'dow'               => ['type' => 'TINYINT', 'null' => false],
            'start_time'        => ['type' => 'TIME', 'null' => false],
            'end_time'          => ['type' => 'TIME', 'null' => false],
            'slot_duration_min' => ['type' => 'SMALLINT', 'null' => false, 'default' => 30],
            'is_active'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'        => ['type' => 'TIMESTAMP', 'null' => false, 'default' => 'CURRENT_TIMESTAMP'],
            'updated_at'        => ['type' => 'TIMESTAMP', 'null' => false, 'default' => 'CURRENT_TIMESTAMP', 'on_update' => 'CURRENT_TIMESTAMP'],
        ]);
        $this->forge->addKey('availability_id', true);
        $this->forge->addKey(['school_id', 'admin_user_id', 'dow']);
        $this->forge->createTable('t_appt_availability', true);

        // t_appt_exception
        $this->forge->addField([
            'exception_id'   => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'school_id'      => ['type' => 'BIGINT', 'unsigned' => true],
            'admin_user_id'  => ['type' => 'BIGINT', 'unsigned' => true],
            'date'           => ['type' => 'DATE', 'null' => false],
            'start_time'     => ['type' => 'TIME', 'null' => false],
            'end_time'       => ['type' => 'TIME', 'null' => false],
            'type'           => ['type' => 'ENUM', 'constraint' => ['closed', 'open_override'], 'null' => false],
            'reason'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'     => ['type' => 'TIMESTAMP', 'null' => false, 'default' => 'CURRENT_TIMESTAMP'],
        ]);
        $this->forge->addKey('exception_id', true);
        $this->forge->addKey(['school_id', 'admin_user_id', 'date']);
        $this->forge->createTable('t_appt_exception', true);

        // t_appt_booking
        $this->forge->addField([
            'appt_id'          => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'school_id'        => ['type' => 'BIGINT', 'unsigned' => true],
            'admin_user_id'    => ['type' => 'BIGINT', 'unsigned' => true],
            'created_by'       => ['type' => 'ENUM', 'constraint' => ['admin', 'student', 'parent'], 'null' => false],
            'student_id'       => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'parent_id'        => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'title'            => ['type' => 'VARCHAR', 'constraint' => 120, 'default' => 'Meeting'],
            'topic'            => ['type' => 'TEXT', 'null' => true],
            'location_type'    => ['type' => 'ENUM', 'constraint' => ['in_person', 'phone', 'video'], 'default' => 'video'],
            'location_details' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'start_at_utc'     => ['type' => 'DATETIME', 'null' => false],
            'end_at_utc'       => ['type' => 'DATETIME', 'null' => false],
            'status'           => ['type' => 'ENUM', 'constraint' => ['pending', 'confirmed', 'cancelled', 'completed', 'no_show', 'rescheduled'], 'default' => 'confirmed'],
            'reschedule_of_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'cancel_reason'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'       => ['type' => 'TIMESTAMP', 'null' => false, 'default' => 'CURRENT_TIMESTAMP'],
            'updated_at'       => ['type' => 'TIMESTAMP', 'null' => false, 'default' => 'CURRENT_TIMESTAMP', 'on_update' => 'CURRENT_TIMESTAMP'],
        ]);
        $this->forge->addKey('appt_id', true);
        $this->forge->addKey(['school_id', 'start_at_utc']);
        $this->forge->addKey(['school_id', 'admin_user_id', 'start_at_utc']);
        $this->forge->addUniqueKey(['school_id', 'admin_user_id', 'start_at_utc', 'end_at_utc'], 'ux_host_slot');
        $this->forge->createTable('t_appt_booking', true);

        // t_appt_guest
        $this->forge->addField([
            'guest_id'  => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'appt_id'   => ['type' => 'BIGINT', 'unsigned' => true],
            'name'      => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'email'     => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'role'      => ['type' => 'ENUM', 'constraint' => ['student', 'parent', 'other'], 'default' => 'other'],
        ]);
        $this->forge->addKey('guest_id', true);
        $this->forge->addKey(['appt_id']);
        $this->forge->addUniqueKey(['appt_id', 'email'], 'ux_appt_email');
        $this->forge->createTable('t_appt_guest', true);

        // t_appt_notification
        $this->forge->addField([
            'notif_id'    => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'appt_id'     => ['type' => 'BIGINT', 'unsigned' => true],
            'channel'     => ['type' => 'ENUM', 'constraint' => ['email', 'sms'], 'null' => false],
            'purpose'     => ['type' => 'ENUM', 'constraint' => ['confirmation', 'reschedule', 'cancel', 'reminder24h', 'reminder1h'], 'null' => false],
            'status'      => ['type' => 'ENUM', 'constraint' => ['queued', 'sent', 'failed'], 'default' => 'queued'],
            'provider_id' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
            'sent_at'     => ['type' => 'TIMESTAMP', 'null' => true],
        ]);
        $this->forge->addKey('notif_id', true);
        $this->forge->addKey(['appt_id', 'purpose', 'sent_at']);
        $this->forge->createTable('t_appt_notification', true);
    }

    public function down()
    {
        $this->forge->dropTable('t_appt_notification', true);
        $this->forge->dropTable('t_appt_guest', true);
        $this->forge->dropTable('t_appt_booking', true);
        $this->forge->dropTable('t_appt_exception', true);
        $this->forge->dropTable('t_appt_availability', true);
    }
}
