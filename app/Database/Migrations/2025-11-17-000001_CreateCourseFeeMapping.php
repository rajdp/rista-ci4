<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCourseFeeMapping extends Migration
{
    public function up()
    {
        // Course Fee Plans - Maps courses to their default fee plans
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'school_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'course_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'comment' => 'References course ID',
            ],
            'fee_plan_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => 'Default fee plan for this course',
            ],
            'default_amount' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
                'comment' => 'Override amount (if not using fee plan)',
            ],
            'billing_cycle_days' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'comment' => 'Billing frequency in days',
            ],
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['school_id', 'course_id']);
        $this->forge->addKey('fee_plan_id');
        $this->forge->createTable('course_fee_plans');

        // Student Courses - Track which courses are assigned to students
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'student_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'comment' => 'User ID of student',
            ],
            'course_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'school_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'registration_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => 'Link to registration if enrolled via registration',
            ],
            'enrollment_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'completion_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['active', 'completed', 'dropped', 'suspended'],
                'default' => 'active',
            ],
            'fee_amount' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
                'comment' => 'Actual fee charged for this student',
            ],
            'student_fee_plan_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => 'Link to student_fee_plans table',
            ],
            'added_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['student_id', 'school_id']);
        $this->forge->addKey('course_id');
        $this->forge->addKey('registration_id');
        $this->forge->createTable('student_courses');

        // Course Class Mapping - Links courses to classes (many-to-many)
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'course_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'class_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'school_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'auto_enroll' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
                'comment' => 'Auto-enroll students in this class when course is added',
            ],
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['course_id', 'class_id']);
        $this->forge->addKey('school_id');
        $this->forge->createTable('course_class_mapping');
    }

    public function down()
    {
        $this->forge->dropTable('course_fee_plans', true);
        $this->forge->dropTable('student_courses', true);
        $this->forge->dropTable('course_class_mapping', true);
    }
}
