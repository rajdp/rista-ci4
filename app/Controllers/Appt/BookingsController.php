<?php

namespace App\Controllers\Appt;

use App\Models\Appt\BookingModel;
use App\Models\Appt\GuestModel;
use App\Models\SelfRegistrationModel;
use App\Services\Appt\IcsService;
use App\Services\Appt\NotificationService;
use App\Services\Appt\PolicyService;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\HTTP\ResponseInterface;

class BookingsController extends ApptController
{
    protected BookingModel $bookingModel;
    protected GuestModel $guestModel;
    protected NotificationService $notifications;
    protected PolicyService $policyService;
    protected IcsService $icsService;

    public function __construct()
    {
        parent::__construct();
        $this->bookingModel = new BookingModel();
        $this->guestModel = new GuestModel();
        $this->notifications = service('apptnotifications');
        $this->policyService = service('apptpolicy');
        $this->icsService = service('icsservice');
    }

    public function index(): ResponseInterface
    {
        $params = $this->request->getGet();
        $token = $this->requireToken();
        if ($token instanceof ResponseInterface) {
            return $token;
        }

        $schoolId = $this->resolveSchoolId($token, $params);
        $adminUserId = (int) ($params['admin_user_id'] ?? $this->getUserId($token));
        $from = $params['from'] ?? date(DATE_ATOM, strtotime('-7 days'));
        $to = $params['to'] ?? date(DATE_ATOM, strtotime('+30 days'));
        $status = $params['status'] ?? null;

        $fromUtc = date('Y-m-d H:i:s', strtotime($from));
        $toUtc = date('Y-m-d H:i:s', strtotime($to));

        $builder = $this->bookingModel->where('school_id', $schoolId)
            ->where('admin_user_id', $adminUserId)
            ->where('start_at_utc <', $toUtc)
            ->where('end_at_utc >', $fromUtc);

        if (!empty($status)) {
            $builder->where('status', $status);
        }

        $rows = $builder->orderBy('start_at_utc', 'ASC')->findAll();
        if (!empty($rows)) {
            $rows = $this->hydrateStudentContext($rows);
        }

        return $this->successResponse($rows);
    }

    public function book(): ResponseInterface
    {
        $payload = $this->jsonPayload();
        $token = $this->validateToken();
        $schoolId = $this->resolveSchoolId($token, $payload);
        $adminUserId = (int) ($payload['admin_user_id'] ?? 0);

        if (!$schoolId || !$adminUserId) {
            return $this->errorResponse('school_id and admin_user_id are required');
        }

        $data = [
            'school_id' => $schoolId,
            'admin_user_id' => $adminUserId,
            'created_by' => $payload['created_by'] ?? 'student',
            'student_id' => $payload['student_id'] ?? null,
            'parent_id' => $payload['parent_id'] ?? null,
            'title' => $payload['title'] ?? 'Meeting',
            'topic' => $payload['topic'] ?? null,
            'location_type' => $payload['location_type'] ?? 'video',
            'location_details' => $payload['location_details'] ?? null,
            'start_at_utc' => date('Y-m-d H:i:s', strtotime($payload['start_at_iso'] ?? 'now')),
            'end_at_utc' => date('Y-m-d H:i:s', strtotime($payload['end_at_iso'] ?? '+30 minutes')),
            'status' => 'confirmed',
        ];

        if ($this->hasOverlap($data['school_id'], $data['admin_user_id'], $data['start_at_utc'], $data['end_at_utc'])) {
            return $this->errorResponse('Slot already booked. Please pick another time.', 409);
        }

        try {
            $apptId = $this->bookingModel->insert($data, true);
        } catch (DatabaseException $e) {
            $errorCode = (int) $e->getCode();
            
            // Handle duplicate entry error (1062) - likely a cancelled appointment slot
            if ($errorCode === 1062 || strpos($e->getMessage(), 'Duplicate entry') !== false || strpos($e->getMessage(), 'ux_host_slot') !== false) {
                // Try to reuse the cancelled slot
                $apptId = $this->reuseCancelledSlot($data);
                if ($apptId) {
                    log_message('info', sprintf(
                        'Booking reused cancelled slot (appt_id: %d, school_id: %d, admin_user_id: %d)',
                        $apptId,
                        $data['school_id'],
                        $data['admin_user_id']
                    ));
                } else {
                    // Couldn't reuse, return error
                    return $this->errorResponse('Slot already booked. Please pick another time.', 409);
                }
            } else {
                // Some other database error
                log_message('error', sprintf(
                    'Booking insert failed (school_id: %d, admin_user_id: %d): %s',
                    $data['school_id'],
                    $data['admin_user_id'],
                    $e->getMessage()
                ));
                return $this->errorResponse('Unable to create appointment. Please try again.', 500);
            }
        }

        if (!empty($payload['invitee_email'])) {
            $this->guestModel->insert([
                'appt_id' => $apptId,
                'email' => $payload['invitee_email'],
                'name' => $payload['invitee_name'] ?? null,
                'role' => $payload['created_by'] ?? 'other',
            ]);
        }

        $this->notifications->log($apptId, 'confirmation');

        $ics = $this->icsService->makeEvent(array_merge($data, ['appt_id' => $apptId]));

        return $this->successResponse([
            'appt_id' => $apptId,
            'ics' => base64_encode($ics),
        ], 'Appointment booked');
    }

    public function reschedule(int $apptId): ResponseInterface
    {
        $token = $this->requireToken();
        if ($token instanceof ResponseInterface) {
            return $token;
        }

        $payload = $this->jsonPayload();

        $existing = $this->bookingModel->find($apptId);
        if (!$existing) {
            return $this->errorResponse('Appointment not found', 404);
        }

        $startUtc = date('Y-m-d H:i:s', strtotime($payload['start_at_iso'] ?? $existing['start_at_utc']));
        $endUtc = date('Y-m-d H:i:s', strtotime($payload['end_at_iso'] ?? $existing['end_at_utc']));

        if ($this->hasOverlap((int) $existing['school_id'], (int) $existing['admin_user_id'], $startUtc, $endUtc, $apptId)) {
            return $this->errorResponse('Slot already booked. Please pick another time.', 409);
        }

        $updateData = [
            'start_at_utc' => $startUtc,
            'end_at_utc' => $endUtc,
        ];

        // Allow updating other fields if provided
        if (isset($payload['title'])) {
            $updateData['title'] = $payload['title'];
        }
        if (isset($payload['topic'])) {
            $updateData['topic'] = $payload['topic'] ?: null;
        }
        if (isset($payload['location_type'])) {
            $updateData['location_type'] = $payload['location_type'];
        }
        if (isset($payload['location_details'])) {
            $updateData['location_details'] = $payload['location_details'] ?: null;
        }
        if (isset($payload['student_id'])) {
            $updateData['student_id'] = $payload['student_id'] ?: null;
        }
        if (isset($payload['parent_id'])) {
            $updateData['parent_id'] = $payload['parent_id'] ?: null;
        }

        // Only set status to 'rescheduled' if time actually changed
        if ($startUtc !== $existing['start_at_utc'] || $endUtc !== $existing['end_at_utc']) {
            $updateData['status'] = 'rescheduled';
        }

        $updated = $this->bookingModel->update($apptId, $updateData);

        if (!$updated) {
            return $this->errorResponse('Unable to reschedule appointment', 400);
        }

        // Update guest invitee if provided
        if (!empty($payload['invitee_email'])) {
            $guest = $this->guestModel->where('appt_id', $apptId)->first();
            if ($guest) {
                $this->guestModel->update($guest['guest_id'], [
                    'email' => $payload['invitee_email'],
                    'name' => $payload['invitee_name'] ?? $guest['name'],
                ]);
            } else {
                $this->guestModel->insert([
                    'appt_id' => $apptId,
                    'email' => $payload['invitee_email'],
                    'name' => $payload['invitee_name'] ?? null,
                    'role' => $payload['created_by'] ?? 'other',
                ]);
            }
        }

        $this->notifications->log($apptId, 'reschedule');

        return $this->successResponse(['appt_id' => $apptId], 'Appointment rescheduled');
    }

    public function cancel(int $apptId): ResponseInterface
    {
        $token = $this->requireToken();
        if ($token instanceof ResponseInterface) {
            return $token;
        }

        $payload = $this->jsonPayload();

        $updated = $this->bookingModel->update($apptId, [
            'status' => 'cancelled',
            'cancel_reason' => $payload['reason'] ?? null,
        ]);

        if (!$updated) {
            return $this->errorResponse('Unable to cancel appointment', 400);
        }

        $this->notifications->log($apptId, 'cancel');

        return $this->successResponse(['appt_id' => $apptId], 'Appointment cancelled');
    }

    private function hydrateStudentContext(array $rows): array
    {
        if (empty($rows)) {
            return $rows;
        }

        $studentIds = array_filter(array_unique(array_map(static function ($row) {
            return isset($row['student_id']) ? (int) $row['student_id'] : 0;
        }, $rows)));

        $db = \Config\Database::connect();
        $studentMap = [];

        if (!empty($studentIds)) {
            $userRows = $db->table('user u')
                ->select('u.user_id, u.email_id, up.first_name, up.last_name')
                ->join('user_profile up', 'up.user_id = u.user_id', 'left')
                ->whereIn('u.user_id', $studentIds)
                ->get()
                ->getResultArray();

            foreach ($userRows as $userRow) {
                $id = (int) ($userRow['user_id'] ?? 0);
                if (!$id) {
                    continue;
                }
                $studentMap[$id] = [
                    'type' => 'student',
                    'id' => $id,
                    'first_name' => $userRow['first_name'] ?? null,
                    'last_name' => $userRow['last_name'] ?? null,
                    'email' => $userRow['email_id'] ?? null,
                ];
            }

            $missingIds = array_values(array_diff($studentIds, array_keys($studentMap)));

            if (!empty($missingIds)) {
                $registrationRows = $db->table('student_self_registrations')
                    ->select('id, student_first_name, student_last_name, email')
                    ->whereIn('id', $missingIds)
                    ->get()
                    ->getResultArray();

                foreach ($registrationRows as $registration) {
                    $id = (int) ($registration['id'] ?? 0);
                    if (!$id) {
                        continue;
                    }
                    $studentMap[$id] = [
                        'type' => 'registration',
                        'id' => $id,
                        'first_name' => $registration['student_first_name'] ?? null,
                        'last_name' => $registration['student_last_name'] ?? null,
                        'email' => $registration['email'] ?? null,
                    ];
                }
            }
        }

        $guestLookup = $this->loadGuestLookup($rows);
        $leadProfiles = $this->loadLeadProfiles($rows, $guestLookup);

        foreach ($rows as &$row) {
            $studentId = isset($row['student_id']) ? (int) $row['student_id'] : 0;
            if ($studentId && isset($studentMap[$studentId])) {
                $info = $studentMap[$studentId];
                $row['student_first_name'] = $info['first_name'] ?? null;
                $row['student_last_name'] = $info['last_name'] ?? null;
                $row['student_email'] = $info['email'] ?? null;
                $row['student_record_type'] = $info['type'];
                if ($info['type'] === 'student') {
                    $row['student_user_id'] = $info['id'];
                    $row['student_registration_id'] = null;
                } else {
                    $row['student_user_id'] = null;
                    $row['student_registration_id'] = $info['id'];
                }
                continue;
            }

            $row['student_user_id'] = null;
            $row['student_registration_id'] = null;

            $leadKey = $this->buildLeadKey($row, $guestLookup);
            if ($leadKey && isset($leadProfiles[$leadKey])) {
                $lead = $leadProfiles[$leadKey];
                $row['student_first_name'] = $lead['student_first_name'] ?? null;
                $row['student_last_name'] = $lead['student_last_name'] ?? null;
                $row['student_email'] = $lead['email'] ?? null;
                $row['student_record_type'] = 'registration';
                $row['student_registration_id'] = $lead['id'] ?? null;
            }
        }
        unset($row);

        return $rows;
    }

    private function loadGuestLookup(array $rows): array
    {
        $apptIds = array_column($rows, 'appt_id');
        if (empty($apptIds)) {
            return [];
        }
        $guestRows = $this->guestModel
            ->select('appt_id, email, role, name')
            ->whereIn('appt_id', $apptIds)
            ->orderBy('role', 'ASC')
            ->findAll();

        $lookup = [];
        foreach ($guestRows as $guest) {
            $email = strtolower(trim($guest['email'] ?? ''));
            if ($email === '') {
                continue;
            }
            $apptId = (int) $guest['appt_id'];
            if (!isset($lookup[$apptId]) || (($guest['role'] ?? '') === 'student')) {
                $lookup[$apptId] = [
                    'email' => $email,
                    'name' => $guest['name'] ?? null,
                    'role' => $guest['role'] ?? null,
                ];
            }
        }

        return $lookup;
    }

    private function loadLeadProfiles(array $rows, array $guestLookup): array
    {
        $requests = [];
        foreach ($rows as $row) {
            if (!empty($row['student_id'])) {
                continue;
            }
            $key = $this->buildLeadKey($row, $guestLookup);
            if ($key && !isset($requests[$key])) {
                $requests[$key] = [
                    'email' => explode('|', $key)[0],
                    'school_id' => $row['school_id'] ?? null
                ];
            }
        }

        if (empty($requests)) {
            return [];
        }

        $model = new SelfRegistrationModel();
        $profiles = [];
        foreach ($requests as $key => $info) {
            if (empty($info['email']) || empty($info['school_id'])) {
                continue;
            }
            $profile = $model->where('school_id', $info['school_id'])
                ->where('LOWER(email)', $info['email'])
                ->orderBy('submitted_at', 'DESC')
                ->first();
            if ($profile) {
                $profiles[$key] = $profile;
            }
        }

        return $profiles;
    }

    private function buildLeadKey(array $booking, array $guestLookup): ?string
    {
        $apptId = (int) ($booking['appt_id'] ?? 0);
        if (!$apptId || empty($guestLookup[$apptId]['email'])) {
            return null;
        }
        $schoolId = $booking['school_id'] ?? null;
        return $guestLookup[$apptId]['email'] . '|' . (string) $schoolId;
    }

    /**
     * Auto-assign booking to an available staff member.
     * For external portal use - no authentication required.
     */
    public function autoAssign(): ResponseInterface
    {
        $payload = $this->jsonPayload();
        
        // Log the received payload for debugging (without sensitive data)
        log_message('debug', sprintf('[AutoAssign] Received payload: %s', json_encode(array_merge($payload, ['email' => isset($payload['email']) ? '[REDACTED]' : null]))));

        // Resolve school_id from school_key or direct school_id
        $schoolId = null;
        if (!empty($payload['school_key'])) {
            $db = \Config\Database::connect();
            $builder = $db->table('school');
            $builder->select('school_id');
            $builder->where('school_key', $payload['school_key']);
            $builder->where('status', 1);
            $school = $builder->get()->getRowArray();
            if ($school) {
                $schoolId = (int) $school['school_id'];
            }
        } elseif (!empty($payload['school_id'])) {
            $schoolId = (int) $payload['school_id'];
        }

        if (!$schoolId) {
            return $this->errorResponse('school_id or school_key is required');
        }

        $startAtIso = $payload['start_at_iso'] ?? null;
        $endAtIso = $payload['end_at_iso'] ?? null;

        if (!$startAtIso || !$endAtIso) {
            return $this->errorResponse('start_at_iso and end_at_iso are required');
        }

        // Validate and parse dates
        try {
            $fromDate = new \DateTimeImmutable($startAtIso);
            $toDate = new \DateTimeImmutable($endAtIso);
        } catch (\Exception $e) {
            log_message('error', sprintf('Invalid date format in auto-assign: start=%s, end=%s, error=%s', $startAtIso, $endAtIso, $e->getMessage()));
            return $this->errorResponse('Invalid date format. Please provide dates in ISO 8601 format (e.g., 2024-01-15T10:00:00Z)');
        }

        // Validate that start is before end
        if ($fromDate >= $toDate) {
            return $this->errorResponse('start_at_iso must be before end_at_iso');
        }

        // Validate location_type if provided
        $validLocationTypes = ['video', 'phone', 'in_person'];
        $locationType = $payload['location_type'] ?? 'video';
        if (!in_array($locationType, $validLocationTypes, true)) {
            return $this->errorResponse('Invalid location_type. Must be one of: ' . implode(', ', $validLocationTypes));
        }

        // Validate created_by if provided
        $validCreatedBy = ['student', 'parent', 'admin'];
        $createdBy = $payload['created_by'] ?? 'student';
        if (!in_array($createdBy, $validCreatedBy, true)) {
            return $this->errorResponse('Invalid created_by. Must be one of: ' . implode(', ', $validCreatedBy));
        }

        $startUtc = $fromDate->format('Y-m-d H:i:s');
        $endUtc = $toDate->format('Y-m-d H:i:s');

        // Get all active staff for this school
        $hosts = $this->loadHosts($schoolId);

        if (empty($hosts)) {
            return $this->errorResponse('No staff available for this school', 404);
        }

        // Try to find an available staff member
        $slotGenerator = service('slotgenerator');
        $policy = $this->policyService->getPolicy($schoolId);

        // Calculate duration in minutes
        $duration = (int) (($toDate->getTimestamp() - $fromDate->getTimestamp()) / 60);

        foreach ($hosts as $host) {
            $adminUserId = (int) $host['admin_user_id'];

            // Check if this staff member has an overlapping booking
            if ($this->hasOverlap($schoolId, $adminUserId, $startUtc, $endUtc)) {
                continue;
            }

            // Check if this staff member has availability for this slot
            try {
                $slots = $slotGenerator->generate(
                    $schoolId,
                    $adminUserId,
                    $fromDate,
                    $toDate,
                    $duration,
                    $policy
                );

                // Check if the requested time slot is in the available slots
                foreach ($slots as $slot) {
                    // Compare dates more flexibly by parsing them
                    try {
                        $slotStart = new \DateTimeImmutable($slot['start']);
                        $slotEnd = new \DateTimeImmutable($slot['end']);
                        // Allow 1 minute tolerance for timezone/format differences
                        $startDiff = abs($slotStart->getTimestamp() - $fromDate->getTimestamp());
                        $endDiff = abs($slotEnd->getTimestamp() - $toDate->getTimestamp());
                        
                        if ($startDiff <= 60 && $endDiff <= 60) {
                        $data = [
                            'school_id' => $schoolId,
                            'admin_user_id' => $adminUserId,
                            'created_by' => $createdBy,
                            'student_id' => $payload['student_id'] ?? null,
                            'parent_id' => $payload['parent_id'] ?? null,
                            'title' => $payload['title'] ?? 'Appointment',
                            'topic' => $payload['topic'] ?? null,
                            'note' => $payload['note'] ?? null,
                            'location_type' => $locationType,
                            'location_details' => $payload['location_details'] ?? null,
                            'start_at_utc' => $startUtc,
                            'end_at_utc' => $endUtc,
                            'status' => 'confirmed',
                        ];

                        try {
                            $apptId = $this->bookingModel->insert($data, true);
                        } catch (DatabaseException $e) {
                            $errorCode = (int) $e->getCode();

                            if ($errorCode === 1062) {
                                $apptId = $this->reuseCancelledSlot($data);
                                if ($apptId) {
                                    log_message('info', sprintf(
                                        'Auto-assign reused cancelled slot (appt_id: %d, school_id: %d, admin_user_id: %d)',
                                        $apptId,
                                        $schoolId,
                                        $adminUserId
                                    ));
                                } else {
                                    log_message('warning', sprintf(
                                        'Auto-assign booking insert skipped due to duplicate slot (school_id: %d, admin_user_id: %d): %s',
                                        $schoolId,
                                        $adminUserId,
                                        $e->getMessage()
                                    ));
                                    continue 2;
                                }
                            } else {
                                log_message('error', sprintf(
                                    'Auto-assign booking insert failed (school_id: %d, admin_user_id: %d): %s',
                                    $schoolId,
                                    $adminUserId,
                                    $e->getMessage()
                                ));

                                return $this->errorResponse('Unable to create appointment right now. Please try again.', 500);
                            }
                        }

                        if (!$apptId) {
                            log_message('error', sprintf(
                                'Auto-assign insert returned empty result (school_id: %d, admin_user_id: %d)',
                                $schoolId,
                                $adminUserId
                            ));
                            continue 2;
                        }

                        // Add guest information if provided
                        if (!empty($payload['invitee_email'])) {
                            $this->insertGuestIfNew([
                                'appt_id' => $apptId,
                                'email' => $payload['invitee_email'],
                                'name' => $payload['invitee_name'] ?? null,
                                'role' => $createdBy === 'admin' ? 'other' : $createdBy,
                            ]);
                        }

                        // If name, email, or mobile provided (for anonymous users), store as guest
                        if (empty($payload['student_id']) && !empty($payload['email'])) {
                            $this->insertGuestIfNew([
                                'appt_id' => $apptId,
                                'email' => $payload['email'],
                                'name' => $payload['name'] ?? null,
                                'mobile' => $payload['mobile'] ?? null,
                                'role' => $createdBy === 'admin' ? 'student' : $createdBy,
                            ]);
                        }

                        $this->notifications->log($apptId, 'confirmation');

                        $ics = $this->icsService->makeEvent(array_merge($data, ['appt_id' => $apptId]));

                        return $this->successResponse([
                            'appointment_id' => $apptId,
                            'message' => 'Appointment booked successfully',
                            'ics' => base64_encode($ics),
                        ], 'Appointment booked');
                        }
                    } catch (\Exception $e) {
                        // Skip this slot if date parsing fails
                        log_message('debug', sprintf('Skipping slot due to date parse error: %s', $e->getMessage()));
                        continue;
                    }
                }
            } catch (\Throwable $e) {
                log_message('warning', "Skipping staff {$adminUserId} in auto-assign: " . $e->getMessage());
                continue;
            }
        }

        $alternatives = $this->buildAlternativeSlots(
            $schoolId,
            $hosts,
            $fromDate,
            $duration,
            $policy
        );

        return $this->restResponse(
            [
                'alternative_slots' => $alternatives
            ],
            false,
            'No staff available for the requested time slot. Please choose an alternative slot.',
            409
        );
    }

    private function buildAlternativeSlots(
        int $schoolId,
        array $hosts,
        \DateTimeImmutable $fromDate,
        int $duration,
        array $policy,
        int $limit = 5
    ): array {
        $slotGenerator = service('slotgenerator');
        $searchEnd = $fromDate->modify('+3 days');
        $seen = [];

        foreach ($hosts as $host) {
            $adminUserId = (int) $host['admin_user_id'];

            try {
                $slots = $slotGenerator->generate(
                    $schoolId,
                    $adminUserId,
                    $fromDate,
                    $searchEnd,
                    $duration,
                    $policy
                );
            } catch (\Throwable $e) {
                log_message('warning', sprintf(
                    'Unable to build alternative slots for admin %d: %s',
                    $adminUserId,
                    $e->getMessage()
                ));
                continue;
            }

            foreach ($slots as $slot) {
                if ($slot['start'] < $fromDate->format(\DateTimeInterface::ATOM)) {
                    continue;
                }

                $key = $slot['start'] . '_' . $slot['end'];
                if (isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = [
                    'start' => $slot['start'],
                    'end' => $slot['end'],
                    'admin_user_id' => $adminUserId,
                ];

                if (count($seen) >= $limit) {
                    break 2;
                }
            }
        }

        if (empty($seen)) {
            return [];
        }

        $alternatives = array_values($seen);
        usort($alternatives, static function ($a, $b) {
            return strcmp($a['start'], $b['start']);
        });

        return $alternatives;
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

    private function hasOverlap(int $schoolId, int $adminUserId, string $startUtc, string $endUtc, ?int $ignoreApptId = null): bool
    {
        $builder = $this->bookingModel->where('school_id', $schoolId)
            ->where('admin_user_id', $adminUserId)
            ->where('status !=', 'cancelled')
            ->where('start_at_utc <', $endUtc)
            ->where('end_at_utc >', $startUtc);

        if ($ignoreApptId) {
            $builder->where('appt_id !=', $ignoreApptId);
        }

        return (bool) $builder->countAllResults();
    }

    private function insertGuestIfNew(array $data): void
    {
        if (empty($data['appt_id']) || empty($data['email'])) {
            return;
        }

        $applied = $this->guestModel
            ->where('appt_id', $data['appt_id'])
            ->where('email', $data['email'])
            ->first();

        if (!$applied) {
            $this->guestModel->insert($data);
        }
    }

    private function reuseCancelledSlot(array $data): ?int
    {
        $existing = $this->bookingModel->where([
            'school_id' => $data['school_id'],
            'admin_user_id' => $data['admin_user_id'],
            'start_at_utc' => $data['start_at_utc'],
            'end_at_utc' => $data['end_at_utc'],
        ])->first();

        if ($existing && ($existing['status'] ?? '') === 'cancelled') {
            $apptId = (int) $existing['appt_id'];
            $updateData = $data;
            unset($updateData['school_id'], $updateData['admin_user_id'], $updateData['start_at_utc'], $updateData['end_at_utc']);
            $updateData['status'] = 'confirmed';
            $updateData['cancel_reason'] = null; // Clear cancellation reason
            $this->bookingModel->update($apptId, $updateData);
            return $apptId;
        }

        return null;
    }
}
