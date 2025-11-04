<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateClassTopicTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'topic_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'class_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
                'comment' => 'References class.class_id',
            ],
            'topic' => [
                'type' => 'VARCHAR',
                'constraint' => 200,
                'null' => false,
            ],
            'start_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'end_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'display_order' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
                'default' => 0,
            ],
            'status' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 1,
                'comment' => '1=Active, 2=Inactive',
            ],
            'created_by' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
                'default' => 0,
            ],
            'created_date' => [
                'type' => 'DATETIME',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ],
            'modified_by' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'modified_date' => [
                'type' => 'DATETIME',
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
            ],
        ]);

        $this->forge->addKey('topic_id', true);
        $this->forge->addKey('class_id');
        $this->forge->addKey(['class_id', 'status']);

        $this->forge->createTable('topic', true);
    }

    public function down()
    {
        $this->forge->dropTable('topic', true);
    }
}
