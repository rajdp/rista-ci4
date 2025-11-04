<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTeacherAvailability extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'teacher_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'school_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'date' => [
                'type' => 'DATE',
                'null' => true,
                'comment' => 'Specific date for one-off availability or start date for recurring blocks',
            ],
            'day_of_week' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => true,
                'comment'    => 'Three-letter day code (Mon, Tue...) for recurring blocks',
            ],
            'is_recurring' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'comment'    => '1 = weekly recurring block',
            ],
            'start_time_local' => [
                'type' => 'TIME',
                'null' => false,
            ],
            'end_time_local' => [
                'type' => 'TIME',
                'null' => false,
            ],
            'spans_midnight' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'start_time_utc' => [
                'type' => 'TIME',
                'null' => false,
            ],
            'end_time_utc' => [
                'type' => 'TIME',
                'null' => false,
            ],
            'duration_minutes' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => false,
            ],
            'recurrence_end' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'timezone' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => false,
            ],
            'notes' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['teacher_id', 'date']);
        $this->forge->addKey(['teacher_id', 'day_of_week']);
        $this->forge->addKey('school_id');
        $this->forge->createTable('t_teacher_availability');
    }

    public function down()
    {
        $this->forge->dropTable('t_teacher_availability');
    }
}
