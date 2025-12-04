<?php

namespace App\Models;

use CodeIgniter\Model;

class DunningStepModel extends Model
{
    protected $table = 't_dunning_step';
    protected $primaryKey = 'step_id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'school_id',
        'day_offset',
        'action',
        'email_template_id',
        'retry_rule',
    ];

    // Dates
    protected $useTimestamps = false;
    protected $createdField = 'created_at';

    // Validation
    protected $validationRules = [
        'school_id' => 'required|integer',
        'day_offset' => 'required|integer',
        'action' => 'required|in_list[email,retry_charge,both]',
    ];

    protected $skipValidation = false;

    /**
     * Get steps by school
     */
    public function getBySchool(int $schoolId): array
    {
        return $this->where('school_id', $schoolId)
            ->orderBy('day_offset', 'ASC')
            ->findAll();
    }

    /**
     * Replace steps for school
     */
    public function replaceSteps(int $schoolId, array $steps): bool
    {
        // Delete existing steps
        $this->where('school_id', $schoolId)->delete();

        // Insert new steps
        $data = [];
        foreach ($steps as $step) {
            $data[] = array_merge($step, [
                'school_id' => $schoolId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        if (empty($data)) {
            return true;
        }

        return $this->insertBatch($data) !== false;
    }

    /**
     * Get applicable steps for days past due
     */
    public function getApplicableSteps(int $schoolId, int $daysPastDue): array
    {
        return $this->where('school_id', $schoolId)
            ->where('day_offset', $daysPastDue)
            ->findAll();
    }
}
