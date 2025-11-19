<?php

namespace App\Services\Appt;

use CodeIgniter\Database\BaseConnection;
use Config\Database;
use DateTimeZone;

class PolicyService
{
    private BaseConnection $db;
    private array $defaultPolicy = [
        'lead_time_min' => 120,
        'buffer_min' => 0,
        'max_per_day' => 8,
        'timezone' => 'America/New_York',
    ];

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? Database::connect();
    }

    public function getPolicy(int $schoolId): array
    {
        $policy = $this->defaultPolicy;

        $storedPolicy = $this->fetchPolicySetting($schoolId);
        if (is_array($storedPolicy)) {
            $policy = array_merge($policy, $storedPolicy);
        }

        $timezone = $this->resolveTimezoneFromSettings($schoolId);
        if ($timezone) {
            $policy['timezone'] = $timezone;
        }

        return $policy;
    }

    public function savePolicy(int $schoolId, array $policy): array
    {
        $normalized = array_merge($this->defaultPolicy, array_intersect_key($policy, $this->defaultPolicy));
        if (!empty($normalized['timezone'])) {
            $this->persistTimezoneSetting($schoolId, $normalized['timezone']);
        }
        $this->persistPolicySetting($schoolId, $normalized);

        return $this->getPolicy($schoolId);
    }

    private function resolveTimezoneFromSettings(int $schoolId): ?string
    {
        $row = $this->db->table('admin_settings_school ass')
            ->select('ass.value as raw_value, tz.utc_timezone, tz.time_zone')
            ->join('time_zone tz', 'tz.id = ass.value', 'left')
            ->where('ass.school_id', $schoolId)
            ->where('ass.name', 'timezone')
            ->get()
            ->getRowArray();

        $tz = $row['utc_timezone'] ?? $row['time_zone'] ?? $row['raw_value'] ?? null;
        if (!$tz) {
            return null;
        }

        $tz = trim((string) $tz);
        // If label contains "(...)" strip it.
        if (strpos($tz, '(') !== false) {
            $tz = trim(substr($tz, 0, strpos($tz, '(')));
        }

        return $this->isValidTimezone($tz) ? $tz : null;
    }

    private function isValidTimezone(string $timezone): bool
    {
        try {
            new DateTimeZone($timezone);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function persistTimezoneSetting(int $schoolId, string $timezone): void
    {
        $timezone = trim($timezone);
        if ($timezone === '') {
            return;
        }

        $value = $this->matchTimezoneId($timezone) ?? $timezone;

        $this->upsertSetting($schoolId, 'timezone', $value, 'Primary timezone for appointments');
    }

    private function matchTimezoneId(string $timezone): ?string
    {
        $builder = $this->db->table('time_zone');
        $builder->select('id');
        $builder->groupStart()
            ->where('utc_timezone', $timezone)
            ->orWhere('time_zone', $timezone)
            ->groupEnd();
        $row = $builder->get()->getRowArray();
        return $row ? (string) $row['id'] : null;
    }

    private function fetchPolicySetting(int $schoolId): ?array
    {
        $row = $this->db->table('admin_settings_school')
            ->select('value')
            ->where('school_id', $schoolId)
            ->where('name', 'appointments.policy')
            ->get()
            ->getRowArray();

        if (empty($row['value'])) {
            return null;
        }

        $decoded = json_decode($row['value'], true);
        return is_array($decoded) ? $decoded : null;
    }

    private function persistPolicySetting(int $schoolId, array $policy): void
    {
        $this->upsertSetting($schoolId, 'appointments.policy', json_encode($policy), 'Serialized appointments policy');
    }

    private function upsertSetting(int $schoolId, string $name, string $value, string $description = ''): void
    {
        $builder = $this->db->table('admin_settings_school');
        $existing = $builder->select('id')
            ->where('school_id', $schoolId)
            ->where('name', $name)
            ->get()
            ->getRowArray();

        $data = [
            'name' => $name,
            'description' => $description !== '' ? $description : $name,
            'value' => $value,
            'school_id' => $schoolId,
            'settings' => 0,
            'status' => 1,
            'modified_date' => date('Y-m-d H:i:s'),
        ];

        $action = 'inserted';
        if ($existing) {
            $builder->set($data)
                ->where('id', $existing['id'])
                ->update();
            $action = 'updated';
        } else {
            $data['sys_time'] = date('Y-m-d H:i:s');
            $builder->insert($data);
        }
        $this->logSettingChange($schoolId, $name, $action);
    }

    private function logSettingChange(int $schoolId, string $setting, string $action): void
    {
        $message = sprintf('[AppointmentsPolicy] %s %s for school %d', ucfirst($action), $setting, $schoolId);
        log_message('debug', $message);

        $file = WRITEPATH . 'logs/appointments.log';
        $line = date('Y-m-d H:i:s') . ' ' . $message . PHP_EOL;
        @file_put_contents($file, $line, FILE_APPEND);
    }
}
