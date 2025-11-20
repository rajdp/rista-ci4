<?php

namespace App\Models;

use CodeIgniter\Model;

class BillingRunModel extends Model
{
    protected $table = 't_billing_run';
    protected $primaryKey = 'run_id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'school_id',
        'run_date',
        'claimed_by',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = null; // No updated_at field

    /**
     * Create or get existing run for a school and date
     *
     * @param int $schoolId
     * @param string $runDate Date in Y-m-d format
     * @param string|null $claimedBy Host/process identifier
     * @return array Run record
     */
    public function createOrGetRun(int $schoolId, string $runDate, ?string $claimedBy = null): array
    {
        $existing = $this->where('school_id', $schoolId)
            ->where('run_date', $runDate)
            ->first();

        if ($existing) {
            // Update claimed_by if provided
            if ($claimedBy && !$existing['claimed_by']) {
                $this->update($existing['run_id'], ['claimed_by' => $claimedBy]);
                $existing['claimed_by'] = $claimedBy;
            }
            return $existing;
        }

        // Create new run
        $runId = $this->insert([
            'school_id' => $schoolId,
            'run_date' => $runDate,
            'claimed_by' => $claimedBy,
        ]);

        return $this->find($runId);
    }

    /**
     * Get run by ID
     *
     * @param int $runId
     * @return array|null
     */
    public function getRun(int $runId): ?array
    {
        return $this->find($runId);
    }
}


