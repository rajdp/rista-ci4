<?php

namespace App\Models;

use App\Models\V1\BaseModel;

class ReportCardVersionModel extends BaseModel
{
    protected $table = 't_report_card_version';
    protected $primaryKey = 'rc_ver_id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'rc_id',
        'version',
        'payload_json',
        'summary_json',
        'created_by',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = null; // Versions are immutable

    protected $validationRules = [
        'rc_id' => 'required|integer',
        'version' => 'required|integer',
        'payload_json' => 'required',
        'created_by' => 'required|integer',
    ];

    protected $validationMessages = [
        'rc_id' => [
            'required' => 'Report card ID is required',
        ],
        'version' => [
            'required' => 'Version number is required',
        ],
        'payload_json' => [
            'required' => 'Report card data is required',
        ],
    ];

    /**
     * Get latest version for a report card
     */
    public function getLatestVersion($rcId)
    {
        return $this->where('rc_id', $rcId)
                    ->orderBy('version', 'DESC')
                    ->first();
    }

    /**
     * Get specific version
     */
    public function getVersion($rcId, $version)
    {
        return $this->where('rc_id', $rcId)
                    ->where('version', $version)
                    ->first();
    }

    /**
     * Get all versions for a report card
     */
    public function getAllVersions($rcId)
    {
        return $this->where('rc_id', $rcId)
                    ->orderBy('version', 'DESC')
                    ->findAll();
    }

    /**
     * Create new version
     */
    public function createVersion($rcId, $payloadJson, $summaryJson, $userId)
    {
        // Get current max version
        $currentVersion = $this->where('rc_id', $rcId)
                              ->orderBy('version', 'DESC')
                              ->first();

        $newVersionNumber = $currentVersion ? ($currentVersion['version'] + 1) : 1;

        $data = [
            'rc_id' => $rcId,
            'version' => $newVersionNumber,
            'payload_json' => $payloadJson,
            'summary_json' => $summaryJson,
            'created_by' => $userId,
        ];

        return $this->insert($data) ? $newVersionNumber : false;
    }
}
