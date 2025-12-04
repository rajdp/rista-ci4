<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class StudentPortalSeeder extends Seeder
{
    public function run()
    {
        // Seed default special request types for all schools
        $schools = $this->db->table('school')->select('school_id')->get()->getResultArray();

        foreach ($schools as $school) {
            $schoolId = $school['school_id'];

            $requestTypes = [
                [
                    'school_id' => $schoolId,
                    'type_key' => 'schedule_change',
                    'display_name' => 'Schedule Change Request',
                    'description' => 'Request to modify class schedule or change sections',
                    'is_active' => 1,
                    'requires_admin_approval' => 1,
                    'auto_assign_to_role' => null,
                    'display_order' => 10,
                ],
                [
                    'school_id' => $schoolId,
                    'type_key' => 'academic_accommodation',
                    'display_name' => 'Academic Accommodation',
                    'description' => 'Request for special academic accommodations or support',
                    'is_active' => 1,
                    'requires_admin_approval' => 1,
                    'auto_assign_to_role' => null,
                    'display_order' => 20,
                ],
                [
                    'school_id' => $schoolId,
                    'type_key' => 'make_up_work',
                    'display_name' => 'Make-Up Work Request',
                    'description' => 'Request for make-up assignments or assessments',
                    'is_active' => 1,
                    'requires_admin_approval' => 1,
                    'auto_assign_to_role' => null,
                    'display_order' => 30,
                ],
                [
                    'school_id' => $schoolId,
                    'type_key' => 'class_transfer',
                    'display_name' => 'Class Transfer',
                    'description' => 'Request to transfer to a different class or section',
                    'is_active' => 1,
                    'requires_admin_approval' => 1,
                    'auto_assign_to_role' => null,
                    'display_order' => 40,
                ],
                [
                    'school_id' => $schoolId,
                    'type_key' => 'grade_appeal',
                    'display_name' => 'Grade Appeal',
                    'description' => 'Appeal a grade or request grade review',
                    'is_active' => 1,
                    'requires_admin_approval' => 1,
                    'auto_assign_to_role' => null,
                    'display_order' => 50,
                ],
                [
                    'school_id' => $schoolId,
                    'type_key' => 'other',
                    'display_name' => 'Other Request',
                    'description' => 'Other types of requests not listed above',
                    'is_active' => 1,
                    'requires_admin_approval' => 1,
                    'auto_assign_to_role' => null,
                    'display_order' => 100,
                ],
            ];

            // Check if request types already exist for this school
            $existingCount = $this->db->table('t_special_request_type')
                ->where('school_id', $schoolId)
                ->countAllResults();

            if ($existingCount == 0) {
                $this->db->table('t_special_request_type')->insertBatch($requestTypes);
            }
        }
    }
}
