<?php

namespace App\Models\Admin;

use CodeIgniter\Model;

class NotificationModel extends Model
{
    protected $table = 'notifications';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'template_id',
        'school_id',
        'recipient_type',
        'recipient_id',
        'student_id',
        'channel',
        'status',
        'scheduled_at',
        'sent_at',
        'payload',
        'error_message',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function queue(array $data): int
    {
        return $this->insert($data);
    }
}
