<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddNextBillingDateToUserProfileDetails extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('user_profile_details')) {
            return;
        }

        // Check if column already exists
        if ($this->db->fieldExists('next_billing_date', 'user_profile_details')) {
            return;
        }

        $fields = [
            'next_billing_date' => [
                'type'       => 'DATE',
                'null'       => true,
                'default'    => null,
                'comment'    => 'Next billing date for automatic/manual billing at student level',
            ],
        ];

        $this->forge->addColumn('user_profile_details', $fields);
    }

    public function down()
    {
        if (! $this->db->tableExists('user_profile_details')) {
            return;
        }

        if ($this->db->fieldExists('next_billing_date', 'user_profile_details')) {
            $this->forge->dropColumn('user_profile_details', 'next_billing_date');
        }
    }
}

