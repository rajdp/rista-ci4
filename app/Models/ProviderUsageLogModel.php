<?php

namespace App\Models;

use CodeIgniter\Model;

class ProviderUsageLogModel extends Model
{
    protected $table = 'provider_usage_log';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'school_id',
        'provider_id',
        'action_type',
        'action_subtype',
        'request_data',
        'response_data',
        'status',
        'error_code',
        'error_message',
        'response_time_ms',
        'related_id',
        'related_type',
        'user_id',
        'user_role',
        'ip_address',
        'units_used',
        'estimated_cost'
    ];

    protected $useTimestamps = false;

    /**
     * Log a provider action
     */
    public function logAction(array $data): int
    {
        $logData = [
            'school_id' => $data['school_id'],
            'provider_id' => $data['provider_id'],
            'action_type' => $data['action_type'],
            'action_subtype' => $data['action_subtype'] ?? null,
            'status' => $data['status'],
            'request_data' => isset($data['request_data']) ? json_encode($this->sanitizeData($data['request_data'])) : null,
            'response_data' => isset($data['response_data']) ? json_encode($this->sanitizeData($data['response_data'])) : null,
            'error_code' => $data['error_code'] ?? null,
            'error_message' => $data['error_message'] ?? null,
            'response_time_ms' => $data['response_time_ms'] ?? null,
            'related_id' => $data['related_id'] ?? null,
            'related_type' => $data['related_type'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'user_role' => $data['user_role'] ?? null,
            'ip_address' => $data['ip_address'] ?? null,
            'units_used' => $data['units_used'] ?? 1,
            'estimated_cost' => $data['estimated_cost'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ];

        return $this->insert($logData);
    }

    /**
     * Sanitize sensitive data before logging
     */
    protected function sanitizeData(array $data): array
    {
        $sensitiveKeys = [
            'password', 'secret', 'token', 'key', 'api_key', 'auth_token',
            'card_number', 'cvv', 'cvc', 'account_number', 'routing_number',
            'credentials', 'authorization'
        ];

        foreach ($data as $key => $value) {
            $keyLower = strtolower($key);
            foreach ($sensitiveKeys as $sensitive) {
                if (str_contains($keyLower, $sensitive)) {
                    $data[$key] = '[REDACTED]';
                    break;
                }
            }
            if (is_array($value)) {
                $data[$key] = $this->sanitizeData($value);
            }
        }

        return $data;
    }

    /**
     * Get usage logs for a school
     */
    public function getSchoolLogs(int $schoolId, array $filters = []): array
    {
        $builder = $this->select('provider_usage_log.*, providers.name as provider_name')
            ->join('providers', 'providers.id = provider_usage_log.provider_id')
            ->where('provider_usage_log.school_id', $schoolId);

        if (!empty($filters['provider_id'])) {
            $builder->where('provider_usage_log.provider_id', $filters['provider_id']);
        }

        if (!empty($filters['action_type'])) {
            $builder->where('provider_usage_log.action_type', $filters['action_type']);
        }

        if (!empty($filters['status'])) {
            $builder->where('provider_usage_log.status', $filters['status']);
        }

        if (!empty($filters['from_date'])) {
            $builder->where('provider_usage_log.created_at >=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $builder->where('provider_usage_log.created_at <=', $filters['to_date']);
        }

        return $builder->orderBy('provider_usage_log.created_at', 'DESC')
            ->findAll($filters['limit'] ?? 100, $filters['offset'] ?? 0);
    }

    /**
     * Get usage statistics for a school
     */
    public function getUsageStats(int $schoolId, ?int $providerId = null, ?string $fromDate = null, ?string $toDate = null): array
    {
        $builder = $this->where('school_id', $schoolId);

        if ($providerId) {
            $builder->where('provider_id', $providerId);
        }

        if ($fromDate) {
            $builder->where('created_at >=', $fromDate);
        }

        if ($toDate) {
            $builder->where('created_at <=', $toDate);
        }

        $totalLogs = (clone $builder)->countAllResults(false);
        $successLogs = (clone $builder)->where('status', 'success')->countAllResults(false);
        $failedLogs = (clone $builder)->where('status', 'failed')->countAllResults(false);
        $totalUnits = (clone $builder)->selectSum('units_used')->first()['units_used'] ?? 0;
        $totalCost = (clone $builder)->selectSum('estimated_cost')->first()['estimated_cost'] ?? 0;

        return [
            'total_requests' => $totalLogs,
            'successful_requests' => $successLogs,
            'failed_requests' => $failedLogs,
            'success_rate' => $totalLogs > 0 ? round(($successLogs / $totalLogs) * 100, 2) : 0,
            'total_units' => (int)$totalUnits,
            'total_cost' => (float)$totalCost
        ];
    }

    /**
     * Get action breakdown for a school
     */
    public function getActionBreakdown(int $schoolId, ?string $fromDate = null, ?string $toDate = null): array
    {
        $builder = $this->select('action_type, status, COUNT(*) as count, SUM(units_used) as total_units')
            ->where('school_id', $schoolId);

        if ($fromDate) {
            $builder->where('created_at >=', $fromDate);
        }

        if ($toDate) {
            $builder->where('created_at <=', $toDate);
        }

        return $builder->groupBy(['action_type', 'status'])
            ->findAll();
    }

    /**
     * Clean up old logs
     */
    public function cleanOldLogs(int $daysToKeep = 90): int
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysToKeep} days"));

        return $this->where('created_at <', $cutoffDate)->delete();
    }
}
