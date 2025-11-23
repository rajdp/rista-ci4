<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLocalTimeColumnsToTeacherAvailability extends Migration
{
    public function up()
    {
        // Check if columns already exist before adding them
        $fields = [
            'start_time_local' => [
                'type' => 'TIME',
                'null' => true,
                'after' => 'day_of_week',
            ],
            'end_time_local' => [
                'type' => 'TIME',
                'null' => true,
                'after' => 'start_time_local',
            ],
            'duration_minutes' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'after' => 'end_time_utc',
            ],
        ];

        foreach ($fields as $fieldName => $fieldDef) {
            if (!$this->db->fieldExists($fieldName, 't_teacher_availability')) {
                $this->forge->addColumn('t_teacher_availability', [$fieldName => $fieldDef]);
            }
        }

        // Also check if 'date' column exists, if not add it (some tables use 'availability_date')
        if (!$this->db->fieldExists('date', 't_teacher_availability')) {
            if ($this->db->fieldExists('availability_date', 't_teacher_availability')) {
                // If availability_date exists, we can use it via the model's resolveDateColumn method
                // But we'll add 'date' as an alias column for compatibility
                $this->forge->addColumn('t_teacher_availability', [
                    'date' => [
                        'type' => 'DATE',
                        'null' => true,
                        'after' => 'school_id',
                        'comment' => 'Alias for availability_date or specific date for one-off availability',
                    ],
                ]);
            } else {
                // Add date column if neither exists
                $this->forge->addColumn('t_teacher_availability', [
                    'date' => [
                        'type' => 'DATE',
                        'null' => true,
                        'after' => 'school_id',
                        'comment' => 'Specific date for one-off availability or start date for recurring blocks',
                    ],
                ]);
            }
        }
    }

    public function down()
    {
        $fieldsToDrop = ['start_time_local', 'end_time_local', 'duration_minutes'];
        
        foreach ($fieldsToDrop as $fieldName) {
            if ($this->db->fieldExists($fieldName, 't_teacher_availability')) {
                $this->forge->dropColumn('t_teacher_availability', $fieldName);
            }
        }

        // Only drop 'date' if it's not the primary date column (i.e., if availability_date exists)
        if ($this->db->fieldExists('date', 't_teacher_availability') && 
            $this->db->fieldExists('availability_date', 't_teacher_availability')) {
            $this->forge->dropColumn('t_teacher_availability', 'date');
        }
    }
}

