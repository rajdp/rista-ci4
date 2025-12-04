<?php

namespace App\Models;

use CodeIgniter\Model;

class InvoiceItemModel extends Model
{
    protected $table = 't_invoice_item';
    protected $primaryKey = 'item_id'; // Fixed: actual column name is item_id, not invoice_item_id
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'invoice_id',
        'description',
        'qty', // Fixed: actual column name is qty, not quantity
        'unit_price_cents', // Fixed: actual column name is unit_price_cents, not unit_cents
        'line_total_cents', // Fixed: actual column name is line_total_cents, not total_cents
        'type', // Fixed: actual column name is type, not kind
        'meta_json',
    ];

    protected $useTimestamps = false; // Fixed: table has no timestamp columns
    protected $createdField = '';
    protected $updatedField = '';

    /**
     * Get all items for an invoice
     *
     * @param int $invoiceId
     * @return array
     */
    public function getInvoiceItems(int $invoiceId): array
    {
        return $this->where('invoice_id', $invoiceId)
            ->orderBy('type', 'ASC') // Order by type: tuition, deposit, material, registration, late_fee, adjustment, tax
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
        // Ensure line_total_cents is calculated if not provided
        if (!isset($data['line_total_cents']) && isset($data['qty']) && isset($data['unit_price_cents'])) {
            $data['line_total_cents'] = (int)$data['qty'] * (int)$data['unit_price_cents'];
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
        $result = $this->selectSum('line_total_cents')
            ->where('invoice_id', $invoiceId)
            ->first();

        return (int)($result['line_total_cents'] ?? 0);
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


