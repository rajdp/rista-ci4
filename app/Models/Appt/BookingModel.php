<?php

namespace App\Models\Appt;

use CodeIgniter\Model;

class BookingModel extends Model
{
    protected $table = 't_appt_booking';
    protected $primaryKey = 'appt_id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'school_id',
        'admin_user_id',
        'created_by',
        'student_id',
        'parent_id',
        'title',
        'topic',
        'location_type',
        'location_details',
        'start_at_utc',
        'end_at_utc',
        'status',
        'reschedule_of_id',
        'cancel_reason',
    ];

    public function upcomingForHost(int $schoolId, int $adminUserId, string $fromUtc, string $toUtc): array
    {
        return $this->where('school_id', $schoolId)
            ->where('admin_user_id', $adminUserId)
            ->where('start_at_utc <', $toUtc)
            ->where('end_at_utc >', $fromUtc)
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('start_at_utc', 'ASC')
            ->findAll();
    }
}
