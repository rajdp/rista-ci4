<?php

namespace App\Models\Admin;

use CodeIgniter\Model;

class StudentFeePlanModel extends Model
{
    protected $table = 'student_fee_plans';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'student_id',
        'fee_plan_id',
        'start_date',
        'end_date',
        'custom_amount',
        'discount_amount',
        'status',
        'auto_payment_override',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function getActivePlanForStudent(int $studentId): ?array
    {
        return $this->where('student_id', $studentId)
            ->where('status', 'active')
            ->orderBy('created_at', 'DESC')
            ->first();
    }

    public function listForStudent(int $studentId): array
    {
        return $this->where('student_id', $studentId)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }
}
