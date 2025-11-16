<?php

namespace App\Services\Appt;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

class PolicyService
{
    private BaseConnection $db;
    private array $defaultPolicy = [
        'lead_time_min' => 120,
        'buffer_min' => 10,
        'max_per_day' => 8,
        'timezone' => 'America/New_York',
    ];

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? Database::connect();
    }

    public function getPolicy(int $schoolId): array
    {
        $row = $this->db->table('t_feature_flag')
            ->select('flag_value')
            ->where('school_id', $schoolId)
            ->where('flag_key', 'appointments.policy')
            ->get()
            ->getRowArray();

        if (empty($row['flag_value'])) {
            return $this->defaultPolicy;
        }

        $decoded = json_decode($row['flag_value'], true);
        if (!is_array($decoded)) {
            return $this->defaultPolicy;
        }

        return array_merge($this->defaultPolicy, $decoded);
    }

    public function savePolicy(int $schoolId, array $policy): array
    {
        $normalized = array_merge($this->defaultPolicy, array_intersect_key($policy, $this->defaultPolicy));
        $this->db->table('t_feature_flag')->replace([
            'school_id' => $schoolId,
            'flag_key' => 'appointments.policy',
            'flag_value' => json_encode($normalized),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $normalized;
    }
}
