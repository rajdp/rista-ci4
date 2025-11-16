<?php

namespace App\Models\Appt;

use CodeIgniter\Model;

class NotificationModel extends Model
{
    protected $table = 't_appt_notification';
    protected $primaryKey = 'notif_id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'appt_id',
        'channel',
        'purpose',
        'status',
        'provider_id',
        'sent_at',
    ];
}
