<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCourseDecisionFieldsToSelfRegistrationCourses extends Migration
{
    public function up()
    {
        $table = 'student_self_registration_courses';

        if (! $this->db->fieldExists('decision_status', $table)) {
            $this->forge->addColumn($table, [
                'decision_status' => [
                    'type' => 'ENUM',
                    'constraint' => ['pending', 'approved', 'waitlisted', 'declined'],
                    'default' => 'pending',
                    'null' => false,
                    'after' => 'course_name',
                ],
            ]);
        }

        if (! $this->db->fieldExists('approved_schedule_id', $table)) {
            $this->forge->addColumn($table, [
                'approved_schedule_id' => [
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'unsigned' => true,
                    'null' => true,
                    'after' => 'decision_status',
                ],
            ]);
        }

        if (! $this->db->fieldExists('approved_schedule_title', $table)) {
            $this->forge->addColumn($table, [
                'approved_schedule_title' => [
                    'type' => 'VARCHAR',
                    'constraint' => 150,
                    'null' => true,
                    'after' => 'approved_schedule_id',
                ],
            ]);
        }

        if (! $this->db->fieldExists('approved_schedule_start', $table)) {
            $this->forge->addColumn($table, [
                'approved_schedule_start' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'approved_schedule_title',
                ],
            ]);
        }

        if (! $this->db->fieldExists('approved_schedule_end', $table)) {
            $this->forge->addColumn($table, [
                'approved_schedule_end' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'approved_schedule_start',
                ],
            ]);
        }

        if (! $this->db->fieldExists('approved_fee_amount', $table)) {
            $this->forge->addColumn($table, [
                'approved_fee_amount' => [
                    'type' => 'DECIMAL',
                    'constraint' => '12,2',
                    'null' => true,
                    'after' => 'approved_schedule_end',
                ],
            ]);
        }

        if (! $this->db->fieldExists('decision_notes', $table)) {
            $this->forge->addColumn($table, [
                'decision_notes' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'approved_fee_amount',
                ],
            ]);
        }
    }

    public function down()
    {
        $table = 'student_self_registration_courses';
        $columns = [
            'decision_status',
            'approved_schedule_id',
            'approved_schedule_title',
            'approved_schedule_start',
            'approved_schedule_end',
            'approved_fee_amount',
            'decision_notes',
        ];

        foreach ($columns as $column) {
            if ($this->db->fieldExists($column, $table)) {
                $this->forge->dropColumn($table, $column);
            }
        }
    }
}


