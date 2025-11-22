<?php

namespace App\Models;

use CodeIgniter\Model;

class InvoiceItemModel extends Model
{
    protected $table = 't_invoice_item';
    protected $primaryKey = 'invoice_item_id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'invoice_id',
        'student_fee_plan_id',
        'description',
        'quantity',
        'unit_cents',
        'total_cents',
        'kind',
        'course_id',
        'enrollment_id',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = null; // No updated_at field

    /**
     * Get all items for an invoice
     *
     * @param int $invoiceId
     * @return array
     */
    public function getInvoiceItems(int $invoiceId): array
    {
        return $this->where('invoice_id', $invoiceId)
            ->orderBy('kind', 'ASC') // Order: proration, recurring, deposit, onboarding, credit, tax
            ->findAll();
    }

    /**
     * Create invoice item
     *
     * @param array $data
     * @return int|false Insert ID or false on failure
     */
    public function createItem(array $data)
    {
        // Ensure total_cents is calculated if not provided
        if (!isset($data['total_cents']) && isset($data['quantity']) && isset($data['unit_cents'])) {
            $data['total_cents'] = (int)$data['quantity'] * (int)$data['unit_cents'];
        }

        return $this->insert($data);
    }

    /**
     * Get total for an invoice
     *
     * @param int $invoiceId
     * @return int Total in cents
     */
    public function getInvoiceTotal(int $invoiceId): int
    {
        $result = $this->selectSum('total_cents')
            ->where('invoice_id', $invoiceId)
            ->first();

        return (int)($result['total_cents'] ?? 0);
    }

    /**
     * Delete all items for an invoice
     *
     * @param int $invoiceId
     * @return bool
     */
    public function deleteInvoiceItems(int $invoiceId): bool
    {
        return $this->where('invoice_id', $invoiceId)->delete();
    }
}


