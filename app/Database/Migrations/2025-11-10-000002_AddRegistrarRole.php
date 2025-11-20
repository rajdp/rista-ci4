<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRegistrarRole extends Migration
{
    private const ROLE_ID = 7;
    private const ROLE_NAME = 'Registrar';
    private const ROLE_DESCRIPTION = 'Admissions & Enrollment Coordinator';

    public function up()
    {
        if (!$this->db->tableExists('roles')) {
            return;
        }

        $builder = $this->db->table('roles');
        $builder->where('role_id', self::ROLE_ID)
            ->orWhere('LOWER(name)', strtolower(self::ROLE_NAME));

        $existing = $builder->get()->getRowArray();

        if (!$existing) {
            $this->db->table('roles')->insert([
                'role_id' => self::ROLE_ID,
                'name' => self::ROLE_NAME,
                'description' => self::ROLE_DESCRIPTION,
            ]);
        }
    }

    public function down()
    {
        if (!$this->db->tableExists('roles')) {
            return;
        }

        $this->db->table('roles')
            ->groupStart()
                ->where('role_id', self::ROLE_ID)
                ->orWhere('LOWER(name)', strtolower(self::ROLE_NAME))
            ->groupEnd()
            ->delete();
    }
}





