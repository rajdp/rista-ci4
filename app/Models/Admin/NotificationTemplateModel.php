<?php

namespace App\Models\Admin;

use CodeIgniter\Model;

class NotificationTemplateModel extends Model
{
    protected $table = 'notification_templates';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'school_id',
        'name',
        'channel',
        'subject',
        'body',
        'placeholders',
        'created_by',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function listForSchool(int $schoolId): array
    {
        return $this->where('school_id', $schoolId)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }
}
