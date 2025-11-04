<?php

namespace App\Models\Admin;

use CodeIgniter\Model;

class ReportCardModel extends Model
{
    protected $table = 'report_cards';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'exam_id',
        'student_id',
        'status',
        'generated_at',
        'pdf_path',
        'share_token',
        'expires_at',
        'metadata',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function findByExamAndStudent(int $examId, int $studentId): ?array
    {
        return $this->where('exam_id', $examId)
            ->where('student_id', $studentId)
            ->first();
    }
}
