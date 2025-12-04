<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBillingCycleDaysToCourse extends Migration
{
    public function up()
    {
        // Add billing_cycle_days column to tbl_course
        $this->forge->addColumn('tbl_course', [
            'billing_cycle_days' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'comment' => 'Billing frequency in days (null = one-time, positive = recurring)',
                'after' => 'fee_term'
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('tbl_course', 'billing_cycle_days');
    }
}








