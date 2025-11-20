<?php

namespace App\Models;

use CodeIgniter\Model;

class BillingRunItemModel extends Model
{
    protected $table = 't_billing_run_item';
    protected $primaryKey = 'run_item_id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'run_id',
        'schedule_id',
        'invoice_id',
        'status',
        'message',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = null; // No updated_at field

    /**
     * Check if schedule was already processed in a run
     *
     * @param int $runId
     * @param int $scheduleId
     * @return array|null
     */
    public function getRunItem(int $runId, int $scheduleId): ?array
    {
        return $this->where('run_id', $runId)
            ->where('schedule_id', $scheduleId)
            ->first();
    }

    /**
     * Create run item record
     *
     * @param int $runId
     * @param int $scheduleId
     * @param string $status 'invoiced', 'skipped', or 'failed'
     * @param int|null $invoiceId
     * @param string|null $message
     * @return int|false Insert ID or false on failure
     */
    public function createRunItem(int $runId, int $scheduleId, string $status, ?int $invoiceId = null, ?string $message = null)
    {
        return $this->insert([
            'run_id' => $runId,
            'schedule_id' => $scheduleId,
            'invoice_id' => $invoiceId,
            'status' => $status,
            'message' => $message,
        ]);
    }

    /**
     * Get all items for a run
     *
     * @param int $runId
     * @return array
     */
    public function getRunItems(int $runId): array
    {
        return $this->where('run_id', $runId)
            ->orderBy('created_at', 'ASC')
            ->findAll();
    }
}


