<?php

namespace App\Models\Admin;

use CodeIgniter\Model;

class NotificationOptoutModel extends Model
{
    protected $table = 'notification_optouts';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'contact_type',
        'contact_id',
        'channel',
        'reason',
        'metadata',
        'created_at',
    ];

    protected $useTimestamps = false;

    public function isOptedOut(string $contactType, int $contactId, string $channel): bool
    {
        return $this->where([
            'contact_type' => $contactType,
            'contact_id' => $contactId,
            'channel' => $channel,
        ])->countAllResults() > 0;
    }
}
