<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDocumentationRequirementsToCourse extends Migration
{
    public function up()
    {
        // Add documentation_requirements column to tbl_course table
        $fields = [
            'documentation_requirements' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'other_details'
            ]
        ];
        
        $this->forge->addColumn('tbl_course', $fields);
    }

    public function down()
    {
        // Remove documentation_requirements column
        $this->forge->dropColumn('tbl_course', 'documentation_requirements');
    }
}




