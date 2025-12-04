<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ReportCardSeeder extends Seeder
{
    public function run()
    {
        // Seed default grading scales
        $this->seedGradingScales();

        // Seed starter templates
        $this->seedTemplates();
    }

    private function seedGradingScales()
    {
        // Elementary Scale (A-F, percentage-based)
        $elementaryScale = [
            ['min' => 90, 'max' => 100, 'letter' => 'A', 'gpa' => 4.0, 'description' => 'Excellent'],
            ['min' => 80, 'max' => 89, 'letter' => 'B', 'gpa' => 3.0, 'description' => 'Good'],
            ['min' => 70, 'max' => 79, 'letter' => 'C', 'gpa' => 2.0, 'description' => 'Satisfactory'],
            ['min' => 60, 'max' => 69, 'letter' => 'D', 'gpa' => 1.0, 'description' => 'Needs Improvement'],
            ['min' => 0, 'max' => 59, 'letter' => 'F', 'gpa' => 0.0, 'description' => 'Failing'],
        ];

        // Secondary Scale with GPA (4.0 scale)
        $secondaryScale = [
            ['min' => 93, 'max' => 100, 'letter' => 'A', 'gpa' => 4.0, 'description' => 'Excellent'],
            ['min' => 90, 'max' => 92, 'letter' => 'A-', 'gpa' => 3.7, 'description' => 'Excellent'],
            ['min' => 87, 'max' => 89, 'letter' => 'B+', 'gpa' => 3.3, 'description' => 'Very Good'],
            ['min' => 83, 'max' => 86, 'letter' => 'B', 'gpa' => 3.0, 'description' => 'Good'],
            ['min' => 80, 'max' => 82, 'letter' => 'B-', 'gpa' => 2.7, 'description' => 'Good'],
            ['min' => 77, 'max' => 79, 'letter' => 'C+', 'gpa' => 2.3, 'description' => 'Satisfactory'],
            ['min' => 73, 'max' => 76, 'letter' => 'C', 'gpa' => 2.0, 'description' => 'Satisfactory'],
            ['min' => 70, 'max' => 72, 'letter' => 'C-', 'gpa' => 1.7, 'description' => 'Satisfactory'],
            ['min' => 67, 'max' => 69, 'letter' => 'D+', 'gpa' => 1.3, 'description' => 'Below Average'],
            ['min' => 63, 'max' => 66, 'letter' => 'D', 'gpa' => 1.0, 'description' => 'Below Average'],
            ['min' => 60, 'max' => 62, 'letter' => 'D-', 'gpa' => 0.7, 'description' => 'Below Average'],
            ['min' => 0, 'max' => 59, 'letter' => 'F', 'gpa' => 0.0, 'description' => 'Failing'],
        ];

        // IB Scale (1-7)
        $ibScale = [
            ['min' => 7, 'max' => 7, 'letter' => '7', 'gpa' => 4.0, 'description' => 'Excellent'],
            ['min' => 6, 'max' => 6, 'letter' => '6', 'gpa' => 3.5, 'description' => 'Very Good'],
            ['min' => 5, 'max' => 5, 'letter' => '5', 'gpa' => 3.0, 'description' => 'Good'],
            ['min' => 4, 'max' => 4, 'letter' => '4', 'gpa' => 2.5, 'description' => 'Satisfactory'],
            ['min' => 3, 'max' => 3, 'letter' => '3', 'gpa' => 2.0, 'description' => 'Mediocre'],
            ['min' => 2, 'max' => 2, 'letter' => '2', 'gpa' => 1.0, 'description' => 'Poor'],
            ['min' => 1, 'max' => 1, 'letter' => '1', 'gpa' => 0.0, 'description' => 'Very Poor'],
        ];

        $scales = [
            [
                'school_id' => 1, // Default school
                'name' => 'Elementary Standard Scale',
                'scale_json' => json_encode($elementaryScale),
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'school_id' => 1,
                'name' => 'Secondary GPA Scale',
                'scale_json' => json_encode($secondaryScale),
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'school_id' => 1,
                'name' => 'IB Scale (1-7)',
                'scale_json' => json_encode($ibScale),
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];

        $this->db->table('t_rc_scale')->insertBatch($scales);
    }

    private function seedTemplates()
    {
        // Elementary Report Card Template
        $elementarySchema = [
            'header' => [
                'show_school_logo' => true,
                'show_student_info' => ['name', 'id', 'grade', 'homeroom'],
                'show_term_info' => true,
            ],
            'sections' => [
                [
                    'id' => 'subjects',
                    'title' => 'Subject Grades',
                    'type' => 'subjects_grid',
                    'columns' => ['subject', 'grade', 'teacher_comments'],
                    'subjects' => ['Math', 'English Language Arts', 'Science', 'Social Studies', 'Art', 'Physical Education', 'Music'],
                ],
                [
                    'id' => 'attendance',
                    'title' => 'Attendance',
                    'type' => 'attendance',
                    'fields' => ['days_present', 'days_absent', 'days_tardy'],
                ],
                [
                    'id' => 'conduct',
                    'title' => 'Work Habits & Conduct',
                    'type' => 'rubric',
                    'items' => [
                        'Follows directions',
                        'Completes work on time',
                        'Works well with others',
                        'Shows respect',
                    ],
                    'scale' => ['Excellent', 'Good', 'Satisfactory', 'Needs Improvement'],
                ],
                [
                    'id' => 'comments',
                    'title' => 'Teacher Comments',
                    'type' => 'long_text',
                    'max_length' => 500,
                ],
            ],
            'footer' => [
                'signatures' => ['teacher', 'principal'],
                'show_date' => true,
            ],
        ];

        // Secondary Report Card Template
        $secondarySchema = [
            'header' => [
                'show_school_logo' => true,
                'show_student_info' => ['name', 'id', 'grade', 'advisor'],
                'show_term_info' => true,
                'show_gpa' => true,
            ],
            'sections' => [
                [
                    'id' => 'subjects',
                    'title' => 'Course Grades',
                    'type' => 'subjects_grid',
                    'columns' => ['course_code', 'course_name', 'credits', 'grade', 'gpa', 'teacher'],
                ],
                [
                    'id' => 'summary',
                    'title' => 'Academic Summary',
                    'type' => 'summary',
                    'fields' => ['term_gpa', 'cumulative_gpa', 'credits_earned', 'credits_attempted', 'class_rank'],
                ],
                [
                    'id' => 'attendance',
                    'title' => 'Attendance',
                    'type' => 'attendance',
                    'fields' => ['days_present', 'days_absent', 'days_tardy', 'periods_absent'],
                ],
                [
                    'id' => 'advisor_comments',
                    'title' => 'Advisor Comments',
                    'type' => 'long_text',
                    'max_length' => 750,
                ],
            ],
            'footer' => [
                'signatures' => ['advisor', 'principal'],
                'show_date' => true,
                'show_seal' => true,
            ],
        ];

        $templates = [
            [
                'school_id' => 1,
                'name' => 'Elementary Report Card',
                'version' => 1,
                'is_active' => 1,
                'schema_json' => json_encode($elementarySchema),
                'created_by' => 1, // Admin user
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'school_id' => 1,
                'name' => 'Secondary Report Card',
                'version' => 1,
                'is_active' => 1,
                'schema_json' => json_encode($secondarySchema),
                'created_by' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];

        $this->db->table('t_rc_template')->insertBatch($templates);
    }
}
