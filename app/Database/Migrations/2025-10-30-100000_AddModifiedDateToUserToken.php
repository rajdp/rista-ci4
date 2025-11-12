<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddModifiedDateToUserToken extends Migration
{
    public function up()
    {
        // Add modified_date column to user_token table
        $this->forge->addColumn('user_token', [
            'modified_date' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'created_date',
                'comment' => 'Last modification timestamp',
            ],
        ]);
    }

    public function down()
    {
        // Remove modified_date column
        $this->forge->dropColumn('user_token', 'modified_date');
    }
}








