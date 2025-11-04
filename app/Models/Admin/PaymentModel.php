<?php

namespace App\Models\Admin;

use CodeIgniter\Model;

class PaymentModel extends Model
{
    protected $table = 'payments';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'student_id',
        'fee_plan_id',
        'student_fee_plan_id',
        'amount',
        'payment_date',
        'payment_method',
        'receipt_number',
        'notes',
        'recorded_by',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function listForStudent(int $studentId): array
    {
        return $this->where('student_id', $studentId)
            ->orderBy('payment_date', 'DESC')
            ->findAll();
    }

    public function totalPaidForStudent(int $studentId): float
    {
        $amount = $this->selectSum('amount')
            ->where('student_id', $studentId)
            ->get()
            ->getRowArray();

        return isset($amount['amount']) ? (float) $amount['amount'] : 0.0;
    }
}
