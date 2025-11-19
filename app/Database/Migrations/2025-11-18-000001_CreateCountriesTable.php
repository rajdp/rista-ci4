<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCountriesTable extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('countries')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 128,
                    'default' => null,
                ],
                'iso2' => [
                    'type' => 'CHAR',
                    'constraint' => 2,
                    'null' => true,
                ],
                'iso3' => [
                    'type' => 'CHAR',
                    'constraint' => 3,
                    'null' => true,
                ],
                'phonecode' => [
                    'type' => 'VARCHAR',
                    'constraint' => 8,
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'TIMESTAMP',
                    'null' => false,
                    'default' => 'CURRENT_TIMESTAMP',
                ],
                'updated_at' => [
                    'type' => 'TIMESTAMP',
                    'null' => true,
                    'default' => null,
                    'on_update' => 'CURRENT_TIMESTAMP',
                ],
            ]);

            $this->forge->addKey('id', true);
            $this->forge->createTable('countries');

            $this->db->table('countries')->insertBatch([
                ['name' => 'United States', 'iso2' => 'US', 'iso3' => 'USA', 'phonecode' => '1'],
                ['name' => 'Canada', 'iso2' => 'CA', 'iso3' => 'CAN', 'phonecode' => '1'],
                ['name' => 'United Kingdom', 'iso2' => 'GB', 'iso3' => 'GBR', 'phonecode' => '44'],
                ['name' => 'India', 'iso2' => 'IN', 'iso3' => 'IND', 'phonecode' => '91'],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('countries')) {
            $this->forge->dropTable('countries');
        }
    }
}
