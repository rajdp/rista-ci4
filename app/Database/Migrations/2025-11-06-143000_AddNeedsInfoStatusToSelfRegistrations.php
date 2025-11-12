<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddNeedsInfoStatusToSelfRegistrations extends Migration
{
    public function up()
    {
        // Alter the status ENUM to include 'needs_info' if it doesn't already exist
        $this->db->query("
            ALTER TABLE student_self_registrations 
            MODIFY COLUMN status ENUM('pending','in_review','needs_info','approved','rejected','converted','archived') 
            NOT NULL DEFAULT 'pending'
        ");

        // Create student_self_registration_messages table if it doesn't exist
        $this->db->query("
            CREATE TABLE IF NOT EXISTS student_self_registration_messages (
              id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
              registration_id BIGINT UNSIGNED NOT NULL,
              channel ENUM('email','sms') NOT NULL,
              recipient VARCHAR(190) NOT NULL,
              subject VARCHAR(190) NULL,
              message TEXT NOT NULL,
              status ENUM('queued','sent','failed') NOT NULL DEFAULT 'sent',
              error_message VARCHAR(255) NULL,
              metadata JSON NULL,
              sent_by BIGINT UNSIGNED NULL,
              sent_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (id),
              KEY idx_registration_messages_registration (registration_id),
              CONSTRAINT fk_registration_messages_registration
                FOREIGN KEY (registration_id) REFERENCES student_self_registrations(id)
                  ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down()
    {
        // Drop the messages table
        $this->db->query("DROP TABLE IF EXISTS student_self_registration_messages");

        // Revert to the old ENUM without 'needs_info'
        // Note: This will fail if any records have 'needs_info' status
        $this->db->query("
            ALTER TABLE student_self_registrations 
            MODIFY COLUMN status ENUM('pending','in_review','approved','rejected','converted','archived') 
            NOT NULL DEFAULT 'pending'
        ");
    }
}

