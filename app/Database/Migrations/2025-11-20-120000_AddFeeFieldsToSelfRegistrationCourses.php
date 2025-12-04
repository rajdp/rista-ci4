<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFeeFieldsToSelfRegistrationCourses extends Migration
{
    public function up()
    {
        $table = 'student_self_registration_courses';

        if (! $this->db->fieldExists('start_date', $table)) {
            $this->forge->addColumn($table, [
                'start_date' => [
                    'type' => 'DATE',
                    'null' => true,
                    'after' => 'decision_notes',
                ],
            ]);
        }

        if (! $this->db->fieldExists('fee_term', $table)) {
            $this->forge->addColumn($table, [
                'fee_term' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'null' => true,
                    'comment' => '1 = one-time, 2 = recurring',
                    'after' => 'start_date',
                ],
            ]);
        }

        if (! $this->db->fieldExists('next_billing_date', $table)) {
            $this->forge->addColumn($table, [
                'next_billing_date' => [
                    'type' => 'DATE',
                    'null' => true,
                    'after' => 'fee_term',
                ],
            ]);
        }

        if (! $this->db->fieldExists('deposit', $table)) {
            $this->forge->addColumn($table, [
                'deposit' => [
                    'type' => 'DECIMAL',
                    'constraint' => '12,2',
                    'null' => true,
                    'after' => 'next_billing_date',
                ],
            ]);
        }

        if (! $this->db->fieldExists('onboarding_fee', $table)) {
            $this->forge->addColumn($table, [
                'onboarding_fee' => [
                    'type' => 'DECIMAL',
                    'constraint' => '12,2',
                    'null' => true,
                    'after' => 'deposit',
                ],
            ]);
        }

        if (! $this->db->fieldExists('registration_fee', $table)) {
            $this->forge->addColumn($table, [
                'registration_fee' => [
                    'type' => 'DECIMAL',
                    'constraint' => '12,2',
                    'null' => true,
                    'after' => 'onboarding_fee',
                ],
            ]);
        }

        if (! $this->db->fieldExists('prorated_fee', $table)) {
            $this->forge->addColumn($table, [
                'prorated_fee' => [
                    'type' => 'DECIMAL',
                    'constraint' => '12,2',
                    'null' => true,
                    'after' => 'registration_fee',
                ],
            ]);
        }

        if (! $this->db->fieldExists('class_id', $table)) {
            $this->forge->addColumn($table, [
                'class_id' => [
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'unsigned' => true,
                    'null' => true,
                    'after' => 'prorated_fee',
                ],
            ]);
        }
    }

    public function down()
    {
        $table = 'student_self_registration_courses';
        $columns = [
            'start_date',
            'fee_term',
            'next_billing_date',
            'deposit',
            'onboarding_fee',
            'registration_fee',
            'prorated_fee',
            'class_id',
        ];

        foreach ($columns as $column) {
            if ($this->db->fieldExists($column, $table)) {
                $this->forge->dropColumn($table, $column);
            }
        }
    }
}








