<?php

namespace App\Controllers\Appt;

use App\Models\Appt\AvailabilityModel;
use App\Services\Appt\PolicyService;
use CodeIgniter\HTTP\ResponseInterface;

class SlotsController extends ApptController
{
    protected AvailabilityModel $availabilityModel;
    protected PolicyService $policyService;

    public function __construct()
    {
        parent::__construct();
        $this->availabilityModel = new AvailabilityModel();
        $this->policyService = service('apptpolicy');
    }

    public function index(): ResponseInterface
    {
        $params = $this->request->getGet();
        $token = $this->validateToken();
        $schoolId = $this->resolveSchoolId($token, $params);
        $adminUserId = (int) ($params['admin_user_id'] ?? 0);

        if (!$schoolId || !$adminUserId) {
            return $this->errorResponse('school_id and admin_user_id are required');
        }

        $from = $params['from'] ?? date(DATE_ATOM);
        $to = $params['to'] ?? date(DATE_ATOM, strtotime('+14 days'));
        $duration = (int) ($params['duration_min'] ?? 30);

        try {
            $slotGenerator = service('slotgenerator');
            $policy = $this->policyService->getPolicy($schoolId);
            $slots = $slotGenerator->generate(
                $schoolId,
                $adminUserId,
                new \DateTimeImmutable($from),
                new \DateTimeImmutable($to),
                $duration,
                $policy
            );
        } catch (\Throwable $e) {
            log_message('error', 'Slot generation failed: ' . $e->getMessage());
            return $this->errorResponse('Unable to generate slots', 500);
        }

        return $this->successResponse($slots);
    }

    /**
     * Get aggregated availability across all staff for external portal.
     * Hides staff names and returns combined availability counts.
     */
    public function aggregated(): ResponseInterface
    {
        $params = $this->request->getGet();

        // For external portal, school_key is used instead of requiring auth token
        $schoolKey = $params['school_key'] ?? null;
        $schoolId = null;

        if ($schoolKey) {
            $db = \Config\Database::connect();
            $builder = $db->table('school');
            $builder->select('school_id');
            $builder->where('school_key', $schoolKey);
            $builder->where('status', 1);
            $school = $builder->get()->getRowArray();
            if ($school) {
                $schoolId = (int) $school['school_id'];
            }
        }

        // Fallback to school_id if provided
        if (!$schoolId && !empty($params['school_id'])) {
            $schoolId = (int) $params['school_id'];
        }

        if (!$schoolId) {
            return $this->errorResponse('school_key or school_id is required');
        }

        $from = $params['from'] ?? date(DATE_ATOM);
        $to = $params['to'] ?? date(DATE_ATOM, strtotime('+14 days'));
        $duration = (int) ($params['duration_min'] ?? 30);

        try {
            // Get all active staff for this school
            $hosts = $this->loadHosts($schoolId);

            if (empty($hosts)) {
                return $this->successResponse(['slots' => []]);
            }

            $slotGenerator = service('slotgenerator');
            $policy = $this->policyService->getPolicy($schoolId);
            $fromDate = new \DateTimeImmutable($from);
            $toDate = new \DateTimeImmutable($to);

            // Collect all slots from all staff
            $allSlots = [];
            foreach ($hosts as $host) {
                $adminUserId = (int) $host['admin_user_id'];
                try {
                    $staffSlots = $slotGenerator->generate(
                        $schoolId,
                        $adminUserId,
                        $fromDate,
                        $toDate,
                        $duration,
                        $policy
                    );

                    foreach ($staffSlots as $slot) {
                        $key = $slot['start'] . '_' . $slot['end'];
                        if (!isset($allSlots[$key])) {
                            $allSlots[$key] = [
                                'start' => $slot['start'],
                                'end' => $slot['end'],
                                'available_staff' => 0,
                                'hosts' => []
                            ];
                        }
                        $allSlots[$key]['available_staff']++;
                        $allSlots[$key]['hosts'][] = [
                            'admin_user_id' => $adminUserId,
                            'host_name' => $host['display_name'] ?? 'Unknown'
                        ];
                    }
                } catch (\Throwable $e) {
                    // Skip this staff member if their slots can't be generated
                    log_message('warning', "Skipping staff {$adminUserId} in aggregated slots: " . $e->getMessage());
                    continue;
                }
            }

            // Convert to indexed array and sort by start time
            $aggregatedSlots = array_values($allSlots);
            usort($aggregatedSlots, function($a, $b) {
                return strcmp($a['start'], $b['start']);
            });

            return $this->successResponse(['slots' => $aggregatedSlots]);

        } catch (\Throwable $e) {
            log_message('error', 'Aggregated slot generation failed: ' . $e->getMessage());
            return $this->errorResponse('Unable to generate aggregated slots', 500);
        }
    }

    /**
     * Load active staff members for a school
     */
    private function loadHosts(int $schoolId): array
    {
        $db = \Config\Database::connect();
        $builder = $db->table('user u');
        $builder->select('u.user_id AS admin_user_id, COALESCE(up.first_name, "") AS first_name, COALESCE(up.last_name, "") AS last_name');
        $builder->join('user_profile up', 'up.user_id = u.user_id', 'left');
        // Support both single school_id match and comma-separated school_id values
        $builder->groupStart()
            ->where('u.school_id', $schoolId)
            ->orWhere("FIND_IN_SET('{$schoolId}', u.school_id) >", 0, false)
            ->groupEnd();
        $builder->where('u.status', 1);
        // Filter for admin/staff roles - exclude students (role_id 5) and parents (role_id 8)
        $builder->whereNotIn('u.role_id', [5, 8]);
        $builder->orderBy('up.first_name ASC');

        $rows = $builder->get()->getResultArray();

        return array_map(static function (array $row) {
            return [
                'admin_user_id' => (int) $row['admin_user_id'],
                'display_name' => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''))
            ];
        }, $rows);
    }
}
