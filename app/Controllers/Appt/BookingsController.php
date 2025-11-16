<?php

namespace App\Controllers\Appt;

use App\Models\Appt\BookingModel;
use App\Models\Appt\GuestModel;
use App\Services\Appt\IcsService;
use App\Services\Appt\NotificationService;
use App\Services\Appt\PolicyService;
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

        $apptId = $this->bookingModel->insert($data, true);

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
}
