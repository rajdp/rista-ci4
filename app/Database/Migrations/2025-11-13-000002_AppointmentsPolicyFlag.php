<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AppointmentsPolicyFlag extends Migration
{
    public function up()
    {
        $sql = "REPLACE INTO t_feature_flag (school_id, flag_key, flag_value, updated_at)
                SELECT s.school_id, 'appointments.policy',
                       '{\"lead_time_min\":120,\"buffer_min\":10,\"max_per_day\":8}',
                       NOW()
                FROM school s";
        $this->db->query($sql);
    }

    public function down()
    {
        $this->db->table('t_feature_flag')->where('flag_key', 'appointments.policy')->delete();
    }
}
