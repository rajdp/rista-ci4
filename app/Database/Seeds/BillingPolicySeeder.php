<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class BillingPolicySeeder extends Seeder
{
    public function run()
    {
        // Get all active schools
        $schools = $this->db->table('school')
            ->select('school_id')
            ->get()
            ->getResultArray();

        foreach ($schools as $school) {
            $schoolId = $school['school_id'];

            // Insert default late fee policy
            $this->db->table('t_late_fee_policy')->ignore(true)->insert([
                'school_id' => $schoolId,
                'flat_cents' => 0, // $0 by default - schools can configure
                'grace_days' => 5, // 5 day grace period
                'repeat_every_days' => null, // Apply once only
                'apply_time' => '03:00:00',
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            // Insert default dunning steps
            $dunningSteps = [
                [
                    'school_id' => $schoolId,
                    'day_offset' => 0, // Due date
                    'action' => 'email',
                    'email_template_id' => null,
                    'retry_rule' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                ],
                [
                    'school_id' => $schoolId,
                    'day_offset' => 3, // 3 days after due
                    'action' => 'email',
                    'email_template_id' => null,
                    'retry_rule' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                ],
                [
                    'school_id' => $schoolId,
                    'day_offset' => 7, // 7 days after due
                    'action' => 'both', // Email and retry charge
                    'email_template_id' => null,
                    'retry_rule' => 'next_day',
                    'created_at' => date('Y-m-d H:i:s'),
                ],
                [
                    'school_id' => $schoolId,
                    'day_offset' => 14, // 14 days after due
                    'action' => 'both',
                    'email_template_id' => null,
                    'retry_rule' => 'next_day',
                    'created_at' => date('Y-m-d H:i:s'),
                ],
            ];

            $this->db->table('t_dunning_step')->insertBatch($dunningSteps);
        }

        log_message('info', 'Default billing policies seeded for ' . count($schools) . ' schools');
    }
}
