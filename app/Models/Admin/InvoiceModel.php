<?php

namespace App\Models\Admin;

use CodeIgniter\Model;

class InvoiceModel extends Model
{
    protected $table = 'invoices';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'school_id',
        'student_id',
        'enrollment_id',
        'student_fee_plan_id',
        'due_date',
        'amount_due',
        'total_cents',
        'amount_paid',
        'status',
        'invoice_number',
        'issued_at',
        'pdf_path',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function listForStudent(int $studentId): array
    {
        return $this->where('student_id', $studentId)
            ->orderBy('issued_at', 'DESC')
            ->findAll();
    }
}
