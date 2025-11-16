<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterCategorySubjectIdToText extends Migration
{
    public function up()
    {
        // Change subject_id from INT to TEXT to support JSON array
        $fields = [
            'subject_id' => [
                'type' => 'TEXT',
                'null' => true,
            ],
        ];
        
        $this->forge->modifyColumn('tbl_course_category', $fields);
    }

    public function down()
    {
        // Revert back to INT
        $fields = [
            'subject_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
        ];
        
        $this->forge->modifyColumn('tbl_course_category', $fields);
    }
}





