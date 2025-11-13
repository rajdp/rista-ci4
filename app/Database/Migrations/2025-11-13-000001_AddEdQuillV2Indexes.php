<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEdQuillV2Indexes extends Migration
{
    public function up()
    {
        // Add lead queue index to student_self_registrations
        if (!$this->db->indexExists('student_self_registrations', 'ix_selfreg_queue')) {
            $this->db->query("
                ALTER TABLE student_self_registrations
                ADD INDEX ix_selfreg_queue (school_id, status, submitted_at)
            ");
        }

        // Add school-scoped indexes to existing tables (if they don't exist)
        $indexes = [
            't_session' => ['ix_session_school' => '(school_id, starts_at)'],
            't_invoice' => ['ix_invoice_school' => '(school_id, status, due_date)'],
            't_submission' => ['ix_subm_school' => '(school_id, submitted_at)'],
        ];

        foreach ($indexes as $table => $indexList) {
            if ($this->db->tableExists($table)) {
                foreach ($indexList as $indexName => $columns) {
                    if (!$this->db->indexExists($table, $indexName)) {
                        $this->db->query("
                            ALTER TABLE {$table}
                            ADD INDEX {$indexName} {$columns}
                        ");
                    }
                }
            }
        }

        // Double-booking protection (if teacher_id/room_id columns exist)
        // Note: These will only be added if the columns exist
        if ($this->db->tableExists('t_session')) {
            // Check if teacher_id exists
            if ($this->db->fieldExists('teacher_id', 't_session') && 
                !$this->db->indexExists('t_session', 'ux_teacher_slot')) {
                $this->db->query("
                    ALTER TABLE t_session
                    ADD UNIQUE KEY ux_teacher_slot (school_id, teacher_id, starts_at, ends_at)
                ");
            }

            // Check if room_id exists
            if ($this->db->fieldExists('room_id', 't_session') && 
                !$this->db->indexExists('t_session', 'ux_room_slot')) {
                $this->db->query("
                    ALTER TABLE t_session
                    ADD UNIQUE KEY ux_room_slot (school_id, room_id, starts_at, ends_at)
                ");
            }
        }
    }

    public function down()
    {
        // Remove indexes
        if ($this->db->indexExists('student_self_registrations', 'ix_selfreg_queue')) {
            $this->db->query("ALTER TABLE student_self_registrations DROP INDEX ix_selfreg_queue");
        }

        $indexes = [
            't_session' => ['ix_session_school', 'ux_teacher_slot', 'ux_room_slot'],
            't_invoice' => ['ix_invoice_school'],
            't_submission' => ['ix_subm_school'],
        ];

        foreach ($indexes as $table => $indexList) {
            if ($this->db->tableExists($table)) {
                foreach ($indexList as $indexName) {
                    if ($this->db->indexExists($table, $indexName)) {
                        $this->db->query("ALTER TABLE {$table} DROP INDEX {$indexName}");
                    }
                }
            }
        }
    }
}

