<?php

namespace App\Models\Appt;

use CodeIgniter\Model;

class ExceptionModel extends Model
{
    protected $table = 't_appt_exception';
    protected $primaryKey = 'exception_id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'school_id',
        'admin_user_id',
        'date',
        'start_time',
        'end_time',
        'type',
        'reason',
    ];

    public function getInRange(int $schoolId, int $adminUserId, string $fromDate, string $toDate): array
    {
        return $this->where('school_id', $schoolId)
            ->where('admin_user_id', $adminUserId)
            ->where('date >=', $fromDate)
            ->where('date <=', $toDate)
            ->orderBy('date ASC, start_time ASC')
            ->findAll();
    }
}
