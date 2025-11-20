<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SeedBillingFeatureFlags extends Migration
{
    public function up()
    {
        // Note: This migration seeds default billing feature flags for all existing schools
        // Feature flags are school-scoped, so we need to get all schools first
        
        $db = \Config\Database::connect();
        
        // Get all schools (adjust table name if different)
        $schools = $db->table('school')
            ->select('id')
            ->get()
            ->getResultArray();

        $defaultFlags = [
            'billing.proration.method' => 'daily',
            'billing.deposit.enabled' => 'true',
            'billing.onboarding.enabled' => 'true',
            'billing.email.enabled' => 'true',
        ];

        foreach ($schools as $school) {
            $schoolId = $school['id'];
            
            foreach ($defaultFlags as $key => $value) {
                // Check if flag already exists
                $existing = $db->table('t_feature_flag')
                    ->where('school_id', $schoolId)
                    ->where('flag_key', $key)
                    ->get()
                    ->getRowArray();

                if (!$existing) {
                    $db->table('t_feature_flag')->insert([
                        'school_id' => $schoolId,
                        'flag_key' => $key,
                        'flag_value' => $value,
                    ]);
                }
            }
        }
    }

    public function down()
    {
        // Remove billing feature flags (optional - usually you'd want to keep them)
        $db = \Config\Database::connect();
        
        $billingFlags = [
            'billing.proration.method',
            'billing.deposit.enabled',
            'billing.onboarding.enabled',
            'billing.email.enabled',
        ];

        foreach ($billingFlags as $key) {
            $db->table('t_feature_flag')
                ->where('flag_key', $key)
                ->delete();
        }
    }
}


