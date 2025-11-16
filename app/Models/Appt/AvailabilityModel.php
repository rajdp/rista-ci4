<?php

namespace App\Models\Appt;

use CodeIgniter\Model;

class AvailabilityModel extends Model
{
    protected $table = 't_appt_availability';
    protected $primaryKey = 'availability_id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'school_id',
        'admin_user_id',
        'dow',
        'start_time',
        'end_time',
        'slot_duration_min',
        'is_active',
    ];

    public function getActiveWindows(int $schoolId, int $adminUserId): array
    {
        return $this->where([
            'school_id' => $schoolId,
            'admin_user_id' => $adminUserId,
            'is_active' => 1,
        ])->orderBy('dow ASC, start_time ASC')->findAll();
    }
}
