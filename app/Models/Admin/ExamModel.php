<?php

namespace App\Models\Admin;

use CodeIgniter\Model;

class ExamModel extends Model
{
    protected $table = 'exams';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'school_id',
        'name',
        'term',
        'class_id',
        'exam_date',
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
            ->orderBy('exam_date', 'DESC')
            ->findAll();
    }
}
