<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SelfRegistrationModel;
use App\Services\SelfRegistrationPromotionService;
use App\Traits\RestTrait;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Database;
use Config\Services;
use RuntimeException;

class SelfRegistration extends BaseController
{
    use RestTrait;

    protected SelfRegistrationPromotionService $promotionService;
    protected SelfRegistrationModel $selfRegistrationModel;

    protected array $allowedStatuses = [
        'pending',
        'in_review',
        'needs_info',
        'approved',
        'rejected',
        'converted',
        'archived'
    ];

    protected array $allowedNoteTypes = ['internal', 'request', 'response', 'history'];
    protected array $allowedCourseDecisionStatuses = ['pending', 'approved', 'waitlisted', 'declined'];
    protected array $assignableRoleLabels = [
        2 => 'School Admin',
        4 => 'School Staff',
        6 => 'Corporate Admin',
        7 => 'Registrar'
    ];
    protected array $userNameCache = [];

    protected array $fieldLabels = [
        'student_first_name' => 'Student first name',
        'student_last_name' => 'Student last name',
        'email' => 'Student email',
        'mobile' => 'Student mobile',
        'date_of_birth' => 'Date of birth',
        'is_minor' => 'Minor flag',
        'address_line1' => 'Address line 1',
        'address_line2' => 'Address line 2',
        'city' => 'City',
        'state' => 'State/Province',
        'postal_code' => 'Postal/ZIP code',
        'country' => 'Country',
        'schedule_preference' => 'Schedule preference',
        'payment_method' => 'Payment method',
        'autopay_authorized' => 'Autopay authorization',
        'payment_reference' => 'Payment reference',
        'guardian1_name' => 'Primary guardian name',
        'guardian1_email' => 'Primary guardian email',
        'guardian1_phone' => 'Primary guardian phone',
        'guardian2_name' => 'Secondary guardian name',
        'guardian2_email' => 'Secondary guardian email',
        'guardian2_phone' => 'Secondary guardian phone',
    ];

    public function __construct()
    {
        $this->promotionService = new SelfRegistrationPromotionService();
        $this->selfRegistrationModel = new SelfRegistrationModel();
    }

    public function list(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);

            $token = $this->validateToken();
            if (!$token) {
                return $this->unauthorizedResponse('Access token required');
            }

            $defaultSchoolId = $this->getSchoolId($token);
            if ($defaultSchoolId) {
                $payload['school_id'] = $payload['school_id'] ?? (int) $defaultSchoolId;
            }

            $limit = (int) ($payload['limit'] ?? 25);
            $limit = max(1, min(100, $limit));
            $page = max(1, (int) ($payload['page'] ?? 1));
            $offset = ($page - 1) * $limit;

            $registrations = $this->selfRegistrationModel->listRegistrations($payload, $limit, $offset);
            $total = $this->selfRegistrationModel->countRegistrations($payload);

            return $this->successResponse([
                'items' => $registrations,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => (int) ceil($total / $limit)
                ]
            ], 'Registrations retrieved successfully');
        } catch (RuntimeException $e) {
            return $this->errorResponse($e->getMessage());
        } catch (\Throwable $e) {
            log_message('error', 'SelfRegistration::list - ' . $e->getMessage());
            return $this->errorResponse('Unable to retrieve registrations');
        }
    }

    public function detail(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);
            $registrationId = (int) ($payload['registration_id'] ?? 0);

            if ($registrationId <= 0) {
                return $this->errorResponse('registration_id is required');
            }

            $token = $this->validateToken();
            if (!$token) {
                return $this->unauthorizedResponse('Access token required');
            }

            $record = $this->selfRegistrationModel->getRegistrationDetail($registrationId);
            if (empty($record)) {
                return $this->errorResponse('Registration not found', 404);
            }

            $defaultSchoolId = $this->getSchoolId($token);
            if ($defaultSchoolId && (int) $record['registration']['school_id'] !== (int) $defaultSchoolId) {
                return $this->unauthorizedResponse('You do not have access to this registration');
            }

            return $this->successResponse($record, 'Registration detail loaded');
        } catch (RuntimeException $e) {
            return $this->errorResponse($e->getMessage());
        } catch (\Throwable $e) {
            log_message('error', 'SelfRegistration::detail - ' . $e->getMessage());
            return $this->errorResponse('Unable to load registration');
        }
    }

    public function updateStatus(): ResponseInterface
    {
        log_message('debug', '🔵 SelfRegistration::updateStatus() called');
        try {
            $payload = (array) ($this->request->getJSON() ?? []);
            $registrationId = (int) ($payload['registration_id'] ?? 0);
            $status = isset($payload['status']) ? strtolower(trim((string) $payload['status'])) : '';
            
            log_message('debug', '🔵 Payload received: ' . json_encode($payload));

            if ($registrationId <= 0) {
                log_message('error', '🔴 registration_id is missing or invalid');
                return $this->errorResponse('registration_id is required');
            }

            if (!in_array($status, $this->allowedStatuses, true)) {
                return $this->errorResponse('Invalid status value');
            }

            $token = $this->validateToken();
            if (!$token) {
                return $this->unauthorizedResponse('Access token required');
            }

            $actorId = (int) $this->getUserId($token);
            if (!$actorId) {
                return $this->unauthorizedResponse('Unable to resolve user from token');
            }

            $record = $this->selfRegistrationModel->getRegistrationById($registrationId);
            if (empty($record)) {
                return $this->errorResponse('Registration not found', 404);
            }

            $defaultSchoolId = $this->getSchoolId($token);
            if ($defaultSchoolId && (int) $record['school_id'] !== (int) $defaultSchoolId) {
                return $this->unauthorizedResponse('You do not have access to this registration');
            }

            $currentStatus = strtolower((string) ($record['status'] ?? ''));
            $statusChanged = $status !== $currentStatus;

            $updateData = [];
            if ($statusChanged) {
                $updateData['status'] = $status;
                $updateData['last_status_at'] = date('Y-m-d H:i:s');
            }

            if (array_key_exists('assigned_to_user_id', $payload)) {
                $newAssignedTo = $payload['assigned_to_user_id'] !== null && $payload['assigned_to_user_id'] !== ''
                    ? (int) $payload['assigned_to_user_id']
                    : null;
                $currentAssignedTo = array_key_exists('assigned_to_user_id', $record) && $record['assigned_to_user_id'] !== null && $record['assigned_to_user_id'] !== ''
                    ? (int) $record['assigned_to_user_id']
                    : null;

                if ($newAssignedTo !== $currentAssignedTo) {
                    $updateData['assigned_to_user_id'] = $newAssignedTo;
                }
            }

            if (array_key_exists('priority', $payload)) {
                $newPriority = $payload['priority'];
                $currentPriority = $record['priority'] ?? null;

                if ($newPriority !== $currentPriority) {
                    $updateData['priority'] = $newPriority;
                }
            }

            if (!empty($updateData)) {
                $updateData['updated_at'] = date('Y-m-d H:i:s');
                $this->selfRegistrationModel->updateRegistration($registrationId, $updateData);
            }

            if ($statusChanged) {
                $this->selfRegistrationModel->createRegistrationNote([
                    'registration_id' => $registrationId,
                    'note_type' => 'history',
                    'message' => sprintf('Status changed to %s', ucfirst($status)),
                    'created_by' => $actorId,
                    'metadata' => null
                ]);
            }

            if (!empty($payload['note'])) {
                $noteType = $payload['note_type'] ?? 'internal';
                $noteType = in_array($noteType, $this->allowedNoteTypes, true) ? $noteType : 'internal';

                $this->selfRegistrationModel->createRegistrationNote([
                    'registration_id' => $registrationId,
                    'note_type' => $noteType,
                    'message' => trim((string) $payload['note']),
                    'created_by' => $actorId,
                    'metadata' => isset($payload['note_metadata']) ? json_encode($payload['note_metadata']) : null
                ]);
            }

            return $this->successResponse(null, 'Registration updated successfully');
        } catch (RuntimeException $e) {
            return $this->errorResponse($e->getMessage());
        } catch (\Throwable $e) {
            log_message('error', 'SelfRegistration::updateStatus - ' . $e->getMessage());
            return $this->errorResponse('Unable to update registration');
        }
    }

    public function updateSummary(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);
            $registrationId = (int) ($payload['registration_id'] ?? 0);

            if ($registrationId <= 0) {
                return $this->errorResponse('registration_id is required');
            }

            $token = $this->validateToken();
            if (!$token) {
                return $this->unauthorizedResponse('Access token required');
            }

            $actorId = (int) $this->getUserId($token);
            if (!$actorId) {
                return $this->unauthorizedResponse('Unable to resolve user from token');
            }

            $record = $this->selfRegistrationModel->getRegistrationById($registrationId);
            if (empty($record)) {
                return $this->errorResponse('Registration not found', 404);
            }

            $defaultSchoolId = $this->getSchoolId($token);
            if ($defaultSchoolId && (int) $record['school_id'] !== (int) $defaultSchoolId) {
                return $this->unauthorizedResponse('You do not have access to this registration');
            }

            $validation = Services::validation();
            $validation->setRules([
                'student_first_name' => 'required|min_length[1]',
                'student_last_name' => 'required|min_length[1]',
                'student_email' => 'required|valid_email',
                'student_mobile' => 'permit_empty|max_length[32]',
                'guardian1_email' => 'permit_empty|valid_email',
                'guardian2_email' => 'permit_empty|valid_email'
            ]);

            if (!$validation->run($payload)) {
                $errors = $validation->getErrors();
                return $this->errorResponse(implode(', ', $errors));
            }

            $updateData = [
                'student_first_name' => trim((string) ($payload['student_first_name'] ?? '')),
                'student_last_name' => trim((string) ($payload['student_last_name'] ?? '')),
                'email' => trim(strtolower((string) ($payload['student_email'] ?? ''))),
                'mobile' => trim((string) ($payload['student_mobile'] ?? '')),
                'guardian1_name' => trim((string) ($payload['guardian1_name'] ?? '')),
                'guardian1_email' => trim(strtolower((string) ($payload['guardian1_email'] ?? ''))),
                'guardian1_phone' => trim((string) ($payload['guardian1_phone'] ?? '')),
                'guardian2_name' => trim((string) ($payload['guardian2_name'] ?? '')),
                'guardian2_email' => trim(strtolower((string) ($payload['guardian2_email'] ?? ''))),
                'guardian2_phone' => trim((string) ($payload['guardian2_phone'] ?? '')),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $labelSubset = array_intersect_key(
                $this->fieldLabels,
                array_flip([
                    'student_first_name',
                    'student_last_name',
                    'email',
                    'mobile',
                    'guardian1_name',
                    'guardian1_email',
                    'guardian1_phone',
                    'guardian2_name',
                    'guardian2_email',
                    'guardian2_phone'
                ])
            );

            $changes = $this->collectChanges($record, $updateData, $labelSubset);
            if (empty($changes)) {
                return $this->successResponse(null, 'No changes detected');
            }

            $this->selfRegistrationModel->updateRegistration($registrationId, $updateData);
            $this->recordHistoryNote(
                $registrationId,
                $actorId,
                'Summary updated: ' . implode(', ', array_column($changes, 'label')),
                $changes
            );

            return $this->successResponse(null, 'Summary updated successfully');
        } catch (RuntimeException $e) {
            return $this->errorResponse($e->getMessage());
        } catch (\Throwable $e) {
            log_message('error', 'SelfRegistration::updateSummary - ' . $e->getMessage());
            return $this->errorResponse('Unable to update summary');
        }
    }

    public function updateStudent(): ResponseInterface
    {
        log_message('debug', '🔵 SelfRegistration::updateStudent() called');
        try {
            $payload = (array) ($this->request->getJSON() ?? []);
            $registrationId = (int) ($payload['registration_id'] ?? 0);
            
            log_message('debug', '🔵 Student update payload: ' . json_encode($payload));

            if ($registrationId <= 0) {
                log_message('error', '🔴 registration_id is missing');
                return $this->errorResponse('registration_id is required');
            }

            $token = $this->validateToken();
            if (!$token) {
                return $this->unauthorizedResponse('Access token required');
            }

            $actorId = (int) $this->getUserId($token);
            if (!$actorId) {
                return $this->unauthorizedResponse('Unable to resolve user from token');
            }

            $record = $this->selfRegistrationModel->getRegistrationById($registrationId);
            if (empty($record)) {
                log_message('error', '🔴 Registration not found: ' . $registrationId);
                return $this->errorResponse('Registration not found', 404);
            }

            $defaultSchoolId = $this->getSchoolId($token);
            if ($defaultSchoolId && (int) $record['school_id'] !== (int) $defaultSchoolId) {
                return $this->unauthorizedResponse('You do not have access to this registration');
            }

            // Clean up date_of_birth - remove if empty or null
            if (isset($payload['date_of_birth']) && (empty($payload['date_of_birth']) || trim($payload['date_of_birth']) === '')) {
                unset($payload['date_of_birth']);
            }

            $validation = Services::validation();
            $validation->setRules([
                'student_mobile' => 'permit_empty|max_length[32]',
                'date_of_birth' => 'permit_empty|valid_date[Y-m-d]',
                'is_minor' => 'permit_empty|in_list[0,1,true,false]',
                'address_line1' => 'permit_empty|max_length[255]',
                'address_line2' => 'permit_empty|max_length[255]',
                'city' => 'permit_empty|max_length[100]',
                'state' => 'permit_empty|max_length[100]',
                'postal_code' => 'permit_empty|max_length[20]',
                'country' => 'permit_empty|max_length[100]',
                'schedule_preference' => 'permit_empty|max_length[255]',
                'payment_method' => 'permit_empty|in_list[card,ach,cash,check,waived,pending]',
                'payment_reference' => 'permit_empty|max_length[190]',
                'autopay_authorized' => 'permit_empty|in_list[0,1,true,false]'
            ]);

            if (!$validation->run($payload)) {
                $errors = $validation->getErrors();
                log_message('error', '🔴 Validation failed: ' . json_encode($errors));
                log_message('error', '🔴 Payload was: ' . json_encode($payload));
                return $this->errorResponse(implode(', ', $errors));
            }

            $dateOfBirth = trim((string) ($payload['date_of_birth'] ?? ''));
            $paymentMethod = strtolower(trim((string) ($payload['payment_method'] ?? '')));
            $paymentMethod = $paymentMethod !== '' ? $paymentMethod : (string) ($record['payment_method'] ?? 'pending');

            $updateData = [
                'mobile' => trim((string) ($payload['student_mobile'] ?? '')),
                'date_of_birth' => $dateOfBirth !== '' ? $dateOfBirth : null,
                'is_minor' => $this->normalizeBoolean($payload['is_minor'] ?? null, (int) ($record['is_minor'] ?? 0)),
                'address_line1' => $this->emptyStringToNull($payload['address_line1'] ?? null),
                'address_line2' => $this->emptyStringToNull($payload['address_line2'] ?? null),
                'city' => $this->emptyStringToNull($payload['city'] ?? null),
                'state' => $this->emptyStringToNull($payload['state'] ?? null),
                'postal_code' => $this->emptyStringToNull($payload['postal_code'] ?? null),
                'country' => $this->emptyStringToNull($payload['country'] ?? null),
                'schedule_preference' => $this->emptyStringToNull($payload['schedule_preference'] ?? null),
                'payment_method' => $paymentMethod,
                'payment_reference' => $this->emptyStringToNull($payload['payment_reference'] ?? null),
                'autopay_authorized' => $this->normalizeBoolean($payload['autopay_authorized'] ?? null, (int) ($record['autopay_authorized'] ?? 0)),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $labelSubset = array_intersect_key(
                $this->fieldLabels,
                array_flip([
                    'mobile',
                    'date_of_birth',
                    'is_minor',
                    'address_line1',
                    'address_line2',
                    'city',
                    'state',
                    'postal_code',
                    'country',
                    'schedule_preference',
                    'payment_method',
                    'payment_reference',
                    'autopay_authorized'
                ])
            );

            $changes = $this->collectChanges($record, $updateData, $labelSubset);
            if (empty($changes)) {
                return $this->successResponse(null, 'No changes detected');
            }

            $this->selfRegistrationModel->updateRegistration($registrationId, $updateData);
            $this->recordHistoryNote(
                $registrationId,
                $actorId,
                'Student profile updated: ' . implode(', ', array_column($changes, 'label')),
                $changes
            );

            return $this->successResponse(null, 'Student details updated successfully');
        } catch (RuntimeException $e) {
            return $this->errorResponse($e->getMessage());
        } catch (\Throwable $e) {
            log_message('error', 'SelfRegistration::updateStudent - ' . $e->getMessage());
            return $this->errorResponse('Unable to update student details');
        }
    }

    public function updateGuardians(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);
            $registrationId = (int) ($payload['registration_id'] ?? 0);

            if ($registrationId <= 0) {
                return $this->errorResponse('registration_id is required');
            }

            $token = $this->validateToken();
            if (!$token) {
                return $this->unauthorizedResponse('Access token required');
            }

            $actorId = (int) $this->getUserId($token);
            if (!$actorId) {
                return $this->unauthorizedResponse('Unable to resolve user from token');
            }

            $record = $this->selfRegistrationModel->getRegistrationById($registrationId);
            if (empty($record)) {
                return $this->errorResponse('Registration not found', 404);
            }

            $defaultSchoolId = $this->getSchoolId($token);
            if ($defaultSchoolId && (int) $record['school_id'] !== (int) $defaultSchoolId) {
                return $this->unauthorizedResponse('You do not have access to this registration');
            }

            $validation = Services::validation();
            $validation->setRules([
                'guardian1_email' => 'permit_empty|valid_email',
                'guardian2_email' => 'permit_empty|valid_email',
                'guardian1_phone' => 'permit_empty|max_length[32]',
                'guardian2_phone' => 'permit_empty|max_length[32]',
                'guardian1_name' => 'permit_empty|max_length[150]',
                'guardian2_name' => 'permit_empty|max_length[150]'
            ]);

            if (!$validation->run($payload)) {
                $errors = $validation->getErrors();
                return $this->errorResponse(implode(', ', $errors));
            }

            $updateData = [
                'guardian1_name' => trim((string) ($payload['guardian1_name'] ?? '')),
                'guardian1_email' => trim(strtolower((string) ($payload['guardian1_email'] ?? ''))),
                'guardian1_phone' => trim((string) ($payload['guardian1_phone'] ?? '')),
                'guardian2_name' => trim((string) ($payload['guardian2_name'] ?? '')),
                'guardian2_email' => trim(strtolower((string) ($payload['guardian2_email'] ?? ''))),
                'guardian2_phone' => trim((string) ($payload['guardian2_phone'] ?? '')),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $labelSubset = array_intersect_key(
                $this->fieldLabels,
                array_flip([
                    'guardian1_name',
                    'guardian1_email',
                    'guardian1_phone',
                    'guardian2_name',
                    'guardian2_email',
                    'guardian2_phone'
                ])
            );

            $changes = $this->collectChanges($record, $updateData, $labelSubset);
            if (empty($changes)) {
                return $this->successResponse(null, 'No changes detected');
            }

            $this->selfRegistrationModel->updateRegistration($registrationId, $updateData);
            $this->recordHistoryNote(
                $registrationId,
                $actorId,
                'Guardian contacts updated: ' . implode(', ', array_column($changes, 'label')),
                $changes
            );

            return $this->successResponse(null, 'Guardian details updated successfully');
        } catch (RuntimeException $e) {
            return $this->errorResponse($e->getMessage());
        } catch (\Throwable $e) {
            log_message('error', 'SelfRegistration::updateGuardians - ' . $e->getMessage());
            return $this->errorResponse('Unable to update guardian details');
        }
    }

    public function addNote(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);
            $registrationId = (int) ($payload['registration_id'] ?? 0);
            $studentId = (int) ($payload['student_user_id'] ?? 0);
            $message = trim((string) ($payload['message'] ?? ''));

            if ($registrationId <= 0 && $studentId <= 0) {
                return $this->errorResponse('registration_id or student_user_id is required');
            }

            if ($message === '') {
                return $this->errorResponse('message is required');
            }

            $token = $this->validateToken();
            if (!$token) {
                return $this->unauthorizedResponse('Access token required');
            }

            $actorId = (int) $this->getUserId($token);
            if (!$actorId) {
                return $this->unauthorizedResponse('Unable to resolve user from token');
            }

            $defaultSchoolId = (int) ($this->getSchoolId($token) ?? 0);
            $record = null;

            if ($registrationId > 0) {
                $record = $this->selfRegistrationModel->getRegistrationById($registrationId);
                if (empty($record)) {
                    return $this->errorResponse('Registration not found', 404);
                }

                if ($defaultSchoolId && (int) $record['school_id'] !== $defaultSchoolId) {
                    return $this->unauthorizedResponse('You do not have access to this registration');
                }
            } elseif ($studentId > 0 && !$this->canAccessStudent($studentId, $defaultSchoolId)) {
                return $this->unauthorizedResponse('You do not have access to this student');
            }

            $noteType = $payload['note_type'] ?? 'internal';
            $noteType = in_array($noteType, $this->allowedNoteTypes, true) ? $noteType : 'internal';

            $noteId = $this->selfRegistrationModel->createRegistrationNote([
                'registration_id' => $registrationId,
                'student_user_id' => $studentId ?: ($record['converted_student_user_id'] ?? null),
                'note_type' => $noteType,
                'message' => $message,
                'created_by' => $actorId,
                'created_by_name' => $this->resolveUserName($actorId),
                'metadata' => $payload['metadata'] ?? null,
                'interaction_type' => $payload['interaction_type'] ?? 'workflow',
                'channel' => $payload['channel'] ?? 'internal',
                'origin' => $payload['origin'] ?? 'manual',
                'entity_type' => $payload['entity_type'] ?? 'registration',
                'entity_id' => $payload['entity_id'] ?? ($registrationId ? (string) $registrationId : null),
            ]);

            $notes = $registrationId > 0
                ? $this->selfRegistrationModel->getRegistrationNotes($registrationId)
                : $this->selfRegistrationModel->getTimelineNotes([
                    'student_user_id' => $studentId,
                ]);

            return $this->successResponse([
                'note_id' => $noteId,
                'notes' => $notes
            ], 'Note added successfully');
        } catch (RuntimeException $e) {
            return $this->errorResponse($e->getMessage());
        } catch (\Throwable $e) {
            log_message('error', 'SelfRegistration::addNote - ' . $e->getMessage());
            return $this->errorResponse('Unable to add note');
        }
    }

    public function sendMessage(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);
            $registrationId = (int) ($payload['registration_id'] ?? 0);
            $channel = strtolower(trim((string) ($payload['channel'] ?? '')));
            $message = trim((string) ($payload['message'] ?? ''));
            $subject = isset($payload['subject']) ? trim((string) $payload['subject']) : '';
            $sendCopy = isset($payload['send_copy']) ? $this->normalizeBoolean($payload['send_copy'], 0) : 0;

            if ($registrationId <= 0) {
                return $this->errorResponse('registration_id is required');
            }

            if (!in_array($channel, ['email', 'sms'], true)) {
                return $this->errorResponse('channel must be email or sms');
            }

            if ($message === '') {
                return $this->errorResponse('message cannot be empty');
            }

            if ($channel === 'email' && $subject === '') {
                return $this->errorResponse('Email subject is required');
            }

            $token = $this->validateToken();
            if (!$token) {
                return $this->unauthorizedResponse('Access token required');
            }

            $actorId = (int) $this->getUserId($token);
            if (!$actorId) {
                return $this->unauthorizedResponse('Unable to resolve user from token');
            }

            $detail = $this->selfRegistrationModel->getRegistrationDetail($registrationId);
            if (empty($detail)) {
                return $this->errorResponse('Registration not found', 404);
            }

            $registration = $detail['registration'];
            $defaultSchoolId = $this->getSchoolId($token);
            if ($defaultSchoolId && (int) ($registration['school_id'] ?? 0) !== (int) $defaultSchoolId) {
                return $this->unauthorizedResponse('You do not have access to this registration');
            }

            $providerConfig = $this->selfRegistrationModel->getCommunicationProvider(
                (int) ($registration['school_id'] ?? 0),
                $channel
            );

            $recipient = '';
            if ($channel === 'email') {
                $recipient = trim((string) ($payload['email'] ?? $registration['email'] ?? ''));
                if ($recipient === '' || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                    return $this->errorResponse('Valid email address required');
                }
            } else {
                if (!$providerConfig) {
                    return $this->errorResponse('SMS provider not configured for this school');
                }
                $recipient = trim((string) ($payload['mobile'] ?? $registration['mobile'] ?? ''));
                if ($recipient === '') {
                    return $this->errorResponse('Mobile number is required for SMS');
                }
            }

            $metadata = [
                'channel' => $channel,
                'subject' => $subject ?: null,
                'send_copy' => (bool) $sendCopy,
                'provider' => $providerConfig,
                'payload' => $payload
            ];

            $status = 'sent';
            $errorMessage = null;

            if ($channel === 'email') {
                $email = Services::email();
                if ($providerConfig && !empty($providerConfig['from_address'])) {
                    $email->setFrom(
                        $providerConfig['from_address'],
                        $providerConfig['from_name'] ?? null
                    );
                }
                $email->setTo($recipient);
                if ($sendCopy && !empty($registration['guardian1_email'])) {
                    $email->setCC($registration['guardian1_email']);
                }
                $email->setSubject($subject);
                $email->setMessage($message);

                if (!$email->send()) {
                    $status = 'failed';
                    $errorMessage = 'Unable to send email';
                    log_message('error', 'SelfRegistration::sendMessage email failed: ' . $email->printDebugger(['headers']));
                }
            } else {
                // Placeholder for SMS provider integration; for now we log and mark as sent.
                log_message('info', sprintf(
                    'SelfRegistration::sendMessage SMS to %s for registration %d via %s: %s',
                    $recipient,
                    $registrationId,
                    $providerConfig['provider'] ?? 'unconfigured',
                    $message
                ));
            }

            $messageId = $this->selfRegistrationModel->logCommunication([
                'registration_id' => $registrationId,
                'channel' => $channel,
                'recipient' => $recipient,
                'subject' => $subject ?: null,
                'message' => $message,
                'status' => $status,
                'error_message' => $errorMessage,
                'metadata' => $metadata,
                'sent_by' => $actorId,
                'created_by_name' => $this->resolveUserName($actorId),
                'sent_at' => date('Y-m-d H:i:s'),
                'origin' => 'automatic'
            ]);

            $messages = $this->selfRegistrationModel->getRegistrationMessages($registrationId);

            $this->recordHistoryNote(
                $registrationId,
                $actorId,
                sprintf('Sent %s message to %s', strtoupper($channel), $recipient),
                [
                    'message' => [
                        'message_id' => $messageId,
                        'channel' => $channel,
                        'recipient' => $recipient,
                        'subject' => $subject ?: null,
                        'status' => $status
                    ]
                ]
            );

            if ($status === 'failed') {
                return $this->errorResponse($errorMessage ?? 'Unable to send message');
            }

            return $this->successResponse([
                'message_id' => $messageId,
                'messages' => $messages
            ], 'Message sent successfully');
        } catch (RuntimeException $e) {
            return $this->errorResponse($e->getMessage());
        } catch (\Throwable $e) {
            log_message('error', 'SelfRegistration::sendMessage - ' . $e->getMessage());
            return $this->errorResponse('Unable to send message');
        }
    }

    public function reviewDocument(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);
            $documentId = (int) ($payload['document_id'] ?? 0);
            $registrationId = isset($payload['registration_id']) ? (int) $payload['registration_id'] : 0;
            $status = isset($payload['status']) ? strtolower(trim((string) $payload['status'])) : '';
            $notes = isset($payload['notes']) ? trim((string) $payload['notes']) : null;

            if ($documentId <= 0) {
                return $this->errorResponse('document_id is required');
            }

            if (!in_array($status, ['pending', 'approved', 'rejected'], true)) {
                return $this->errorResponse('Invalid status value');
            }

            $token = $this->validateToken();
            if (!$token) {
                return $this->unauthorizedResponse('Access token required');
            }

            $actorId = (int) $this->getUserId($token);
            if (!$actorId) {
                return $this->unauthorizedResponse('Unable to resolve user from token');
            }

            $document = $this->selfRegistrationModel->getDocumentById($documentId);
            if (empty($document)) {
                return $this->errorResponse('Document not found', 404);
            }

            $documentRegistrationId = (int) ($document['registration_id'] ?? 0);
            if ($registrationId && $registrationId !== $documentRegistrationId) {
                return $this->errorResponse('Document does not belong to the specified registration');
            }

            $registration = $this->selfRegistrationModel->getRegistrationById($documentRegistrationId);
            if (empty($registration)) {
                return $this->errorResponse('Registration not found for document', 404);
            }

            $defaultSchoolId = $this->getSchoolId($token);
            if ($defaultSchoolId && (int) $registration['school_id'] !== (int) $defaultSchoolId) {
                return $this->unauthorizedResponse('You do not have access to this registration');
            }

            $updatePayload = [
                'review_status' => $status,
                'review_notes' => $notes !== '' ? $notes : null,
            ];

            $this->selfRegistrationModel->updateDocumentReview($documentId, $updatePayload);

            $statusLabel = ucfirst($status);
            $noteMessage = sprintf(
                'Document "%s" marked %s',
                $document['original_name'] ?? ('#' . $documentId),
                $statusLabel
            );

            if ($notes) {
                $noteMessage .= ': ' . $notes;
            }

            $this->selfRegistrationModel->createRegistrationNote([
                'registration_id' => $documentRegistrationId,
                'note_type' => 'history',
                'message' => $noteMessage,
                'created_by' => $actorId,
                'metadata' => json_encode([
                    'document_id' => $documentId,
                    'status' => $status,
                    'notes' => $notes,
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            ]);

            $detail = $this->selfRegistrationModel->getRegistrationDetail($documentRegistrationId);

            return $this->successResponse([
                'registration_id' => $documentRegistrationId,
                'documents' => $detail['documents'] ?? [],
                'notes' => $detail['notes'] ?? [],
            ], 'Document review updated');
        } catch (RuntimeException $e) {
            return $this->errorResponse($e->getMessage());
        } catch (\Throwable $e) {
            log_message('error', 'SelfRegistration::reviewDocument - ' . $e->getMessage());
            return $this->errorResponse('Unable to update document review');
        }
    }

    public function assignees(): ResponseInterface
    {
        try {
            $token = $this->validateToken();
            if (!$token) {
                return $this->unauthorizedResponse('Access token required');
            }

            $schoolId = $this->getSchoolId($token);
            $users = $this->selfRegistrationModel->getAssignableUsers($schoolId ? (int) $schoolId : null);

            $response = array_map(function (array $user): array {
                $roleId = (int) ($user['role_id'] ?? 0);
                $roleLabel = $this->assignableRoleLabels[$roleId] ?? 'User';

                return [
                    'user_id' => (int) $user['user_id'],
                    'role_id' => $roleId,
                    'role_label' => $roleLabel,
                    'name' => $user['name'],
                    'email' => $user['email'],
                ];
            }, $users);

            return $this->successResponse([
                'users' => $response
            ], 'Assignable users loaded');
        } catch (RuntimeException $e) {
            return $this->errorResponse($e->getMessage());
        } catch (\Throwable $e) {
            log_message('error', 'SelfRegistration::assignees - ' . $e->getMessage());
            return $this->errorResponse('Unable to load assignees');
        }
    }

    /**
     * Convert a pending self-registration into an active student record.
     */
    public function promote(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);
            $registrationId = (int) ($payload['registration_id'] ?? 0);

            if ($registrationId <= 0) {
                return $this->errorResponse('registration_id is required');
            }

            $token = $this->validateToken();
            if (! $token) {
                return $this->unauthorizedResponse('Access token required');
            }

            $actorId = $this->getUserId($token);
            if (! $actorId) {
                return $this->unauthorizedResponse('Unable to resolve user from token');
            }

            $detail = $this->selfRegistrationModel->getRegistrationDetail($registrationId);
            if (empty($detail)) {
                return $this->errorResponse('Registration not found', 404);
            }

            $defaultSchoolId = $this->getSchoolId($token);
            if ($defaultSchoolId && (int) ($detail['registration']['school_id'] ?? 0) !== (int) $defaultSchoolId) {
                return $this->unauthorizedResponse('You do not have access to this registration');
            }

            $courses = $detail['courses'] ?? [];
            $courseDecisions = $this->prepareCourseDecisions($payload['course_decisions'] ?? [], $courses);

            if (!empty($courseDecisions)) {
                $this->selfRegistrationModel->updateCourseDecisions($registrationId, $courseDecisions);
                $this->recordHistoryNote(
                    $registrationId,
                    (int) $actorId,
                    'Course decisions updated for conversion',
                    ['courses' => array_map(static function ($decision) {
                        return [
                            'registration_course_id' => $decision['registration_course_id'],
                            'course_id' => $decision['course_id'] ?? null,
                            'course_name' => $decision['course_name'] ?? null,
                            'decision_status' => $decision['decision_status'],
                            'approved_schedule_id' => $decision['approved_schedule_id'],
                            'approved_schedule_title' => $decision['approved_schedule_title'],
                            'approved_fee_amount' => $decision['approved_fee_amount'],
                            'decision_notes' => $decision['decision_notes'] ?? null,
                        ];
                    }, $courseDecisions)]
                );
            }

            $notes = isset($payload['notes']) ? trim((string) $payload['notes']) : null;
            $sendWelcome = isset($payload['send_welcome_email']) ? $this->normalizeBoolean($payload['send_welcome_email'], 0) : 0;

            $result = $this->promotionService->promote(
                $registrationId,
                (int) $actorId,
                [
                    'notes' => $notes ?: null,
                    'send_welcome_email' => (bool) $sendWelcome
                ]
            );

            if (!empty($result['student_user_id'])) {
                $this->selfRegistrationModel->attachRegistrationNotesToStudent(
                    $registrationId,
                    (int) $result['student_user_id']
                );
            }

            return $this->successResponse($result, 'Registration converted successfully');
        } catch (RuntimeException $e) {
            return $this->errorResponse($e->getMessage());
        } catch (\Throwable $e) {
            log_message('error', 'SelfRegistration::promote - ' . $e->getMessage());
            return $this->errorResponse('Unable to convert registration');
        }
    }

    /**
     * Persist course-level decisions before conversion.
     */
    public function updateCourseDecisions(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);
            $registrationId = (int) ($payload['registration_id'] ?? 0);

            if ($registrationId <= 0) {
                return $this->errorResponse('registration_id is required');
            }

            if (empty($payload['course_decisions']) || !is_array($payload['course_decisions'])) {
                return $this->errorResponse('Provide course decisions to save');
            }

            $token = $this->validateToken();
            if (! $token) {
                return $this->unauthorizedResponse('Access token required');
            }

            $actorId = $this->getUserId($token);
            if (! $actorId) {
                return $this->unauthorizedResponse('Unable to resolve user from token');
            }

            $detail = $this->selfRegistrationModel->getRegistrationDetail($registrationId);
            if (empty($detail)) {
                return $this->errorResponse('Registration not found', 404);
            }

            $defaultSchoolId = $this->getSchoolId($token);
            if ($defaultSchoolId && (int) ($detail['registration']['school_id'] ?? 0) !== (int) $defaultSchoolId) {
                return $this->unauthorizedResponse('You do not have access to this registration');
            }

            $courses = $detail['courses'] ?? [];
            log_message(
                'debug',
                sprintf(
                    'SelfRegistration::updateCourseDecisions registration_id=%d incoming_decisions=%s course_ids=%s',
                    $registrationId,
                    json_encode($payload['course_decisions']),
                    json_encode(array_column($courses, 'id'))
                )
            );
            $courseDecisions = $this->prepareCourseDecisions($payload['course_decisions'], $courses);

            if (empty($courseDecisions)) {
                log_message(
                    'error',
                    sprintf(
                        'SelfRegistration::updateCourseDecisions - Unable to map decisions. Payload=%s | Courses=%s',
                        json_encode($payload['course_decisions']),
                        json_encode(array_column($courses, 'id'))
                    )
                );
                return $this->errorResponse('Unable to process the provided course decisions');
            }

            $this->selfRegistrationModel->updateCourseDecisions($registrationId, $courseDecisions);
            $this->recordHistoryNote(
                $registrationId,
                (int) $actorId,
                'Course decisions updated',
                [
                    'courses' => array_map(static function ($decision) {
                        return [
                            'registration_course_id' => $decision['registration_course_id'],
                            'course_id' => $decision['course_id'] ?? null,
                            'course_name' => $decision['course_name'] ?? null,
                            'decision_status' => $decision['decision_status'],
                            'approved_schedule_id' => $decision['approved_schedule_id'],
                            'approved_schedule_title' => $decision['approved_schedule_title'],
                            'approved_fee_amount' => $decision['approved_fee_amount'],
                            'decision_notes' => $decision['decision_notes'] ?? null,
                        ];
                    }, $courseDecisions)
                ]
            );

            $updatedDetail = $this->selfRegistrationModel->getRegistrationDetail($registrationId);

            return $this->successResponse([
                'registration' => $updatedDetail['registration'] ?? $detail['registration'],
                'courses' => $updatedDetail['courses'] ?? $courses,
            ], 'Course decisions saved');
        } catch (RuntimeException $e) {
            return $this->errorResponse($e->getMessage());
        } catch (\Throwable $e) {
            log_message('error', 'SelfRegistration::updateCourseDecisions - ' . $e->getMessage());
            return $this->errorResponse('Unable to save course decisions');
        }
    }

    /**
     * @param array<int,mixed> $decisions
     * @param array<int,array<string,mixed>> $courses
     * @return array<int,array<string,mixed>>
     */
    private function prepareCourseDecisions(array $decisions, array $courses): array
    {
        if (empty($decisions)) {
            return [];
        }

        $courseLookup = [];
        $debugCourseKeys = [];
        $courseByCourseId = [];
        foreach ($courses as $course) {
            $rowId = isset($course['registration_course_id'])
                ? (int) $course['registration_course_id']
                : (isset($course['id']) ? (int) $course['id'] : 0);
            if ($rowId <= 0) {
                continue;
            }

            $scheduleLookup = [];
            if (!empty($course['available_schedules']) && is_array($course['available_schedules'])) {
                foreach ($course['available_schedules'] as $schedule) {
                    $scheduleId = isset($schedule['schedule_id']) ? (int) $schedule['schedule_id'] : 0;
                    if ($scheduleId > 0) {
                        $scheduleLookup[$scheduleId] = $schedule;
                    }
                }
            }

            $course['__schedule_lookup'] = $scheduleLookup;
            $courseLookup[$rowId] = $course;
            $debugCourseKeys[] = $rowId;

            $catalogCourseId = isset($course['course_id']) ? (int) $course['course_id'] : 0;
            if ($catalogCourseId > 0) {
                $courseByCourseId[$catalogCourseId][] = $course;
            }
        }

        $normalized = [];
        log_message('debug', sprintf(
            'SelfRegistration::prepareCourseDecisions - available row ids=%s',
            json_encode(array_keys($courseLookup))
        ));

        foreach ($decisions as $decision) {
            if (is_object($decision)) {
                $decision = (array) $decision;
            }
            if (!is_array($decision)) {
                log_message('error', sprintf(
                    'SelfRegistration::prepareCourseDecisions - skipping non-array decision=%s',
                    json_encode($decision)
                ));
                continue;
            }

            $rowId = isset($decision['registration_course_id'])
                ? (int) $decision['registration_course_id']
                : (isset($decision['id']) ? (int) $decision['id'] : 0);

            log_message('debug', sprintf(
                'SelfRegistration::prepareCourseDecisions - evaluating decision=%s resolved_row_id=%d',
                json_encode($decision),
                $rowId
            ));

            $course = null;
            if ($rowId > 0 && isset($courseLookup[$rowId])) {
                $course = $courseLookup[$rowId];
            } elseif (!empty($decision['course_id'])) {
                $courseId = (int) $decision['course_id'];
                if ($courseId > 0 && !empty($courseByCourseId[$courseId])) {
                    // If multiple matches exist, pick the first one (they should share the same rowId)
                    $course = $courseByCourseId[$courseId][0];
                    $rowId = isset($course['registration_course_id'])
                        ? (int) $course['registration_course_id']
                        : (isset($course['id']) ? (int) $course['id'] : $rowId);
                }
            }

            if ($rowId <= 0 || $course === null) {
                if (count($courseLookup) === 1) {
                    $onlyRowId = array_key_first($courseLookup);
                    log_message(
                        'debug',
                        sprintf(
                            'SelfRegistration::prepareCourseDecisions - falling back to sole course id=%d for decision=%s',
                            $onlyRowId,
                            json_encode($decision)
                        )
                    );
                    $course = $courseLookup[$onlyRowId];
                    $rowId = $onlyRowId;
                } else {
                    log_message(
                        'error',
                        sprintf(
                            'SelfRegistration::prepareCourseDecisions - unable to match decision=%s available_row_ids=%s original_row_id=%s debug_course_keys=%s',
                            json_encode($decision),
                            json_encode(array_keys($courseLookup)),
                            $rowId,
                            json_encode($debugCourseKeys)
                        )
                    );
                    continue;
                }
            }

            $status = strtolower(trim((string) ($decision['decision_status'] ?? 'pending')));
            if (!in_array($status, $this->allowedCourseDecisionStatuses, true)) {
                $status = 'pending';
            }

            $scheduleId = isset($decision['approved_schedule_id']) ? (int) $decision['approved_schedule_id'] : null;
            $schedule = null;
            if ($scheduleId && isset($course['__schedule_lookup'][$scheduleId])) {
                $schedule = $course['__schedule_lookup'][$scheduleId];
            }

            if ($status === 'approved' && $scheduleId === null && !empty($course['__schedule_lookup'])) {
                $courseLabel = $course['course_name'] ?? $course['catalog_course_name'] ?? ('#' . ($course['course_id'] ?? $rowId));
                throw new RuntimeException(sprintf('Course "%s" must have a schedule selected before approval', $courseLabel));
            }

            $fee = $decision['approved_fee_amount'] ?? ($schedule['cost'] ?? $course['approved_fee_amount'] ?? $course['fee_amount'] ?? $course['catalog_fees'] ?? null);
            if ($fee !== null && $fee !== '') {
                $fee = (float) $fee;
            } else {
                $fee = null;
            }

            $normalizedDecision = [
                'registration_course_id' => $rowId,
                'course_id' => $course['course_id'] ?? null,
                'course_name' => $course['course_name'] ?? $course['catalog_course_name'] ?? null,
                'decision_status' => $status,
                'approved_schedule_id' => $scheduleId ?: null,
                'approved_schedule_title' => $schedule['schedule_title'] ?? ($decision['approved_schedule_title'] ?? $course['approved_schedule_title'] ?? $course['schedule_title'] ?? null),
                'approved_schedule_start' => $schedule['course_start_date'] ?? ($decision['approved_schedule_start'] ?? $course['approved_schedule_start'] ?? null),
                'approved_schedule_end' => $schedule['course_end_date'] ?? ($decision['approved_schedule_end'] ?? $course['approved_schedule_end'] ?? null),
                'approved_fee_amount' => $fee,
                'decision_notes' => isset($decision['decision_notes']) ? trim((string) $decision['decision_notes']) : ($course['decision_notes'] ?? null),
            ];

            // Add new fields if status is approved
            if ($status === 'approved') {
                $normalizedDecision['start_date'] = $decision['start_date'] ?? date('Y-m-d');
                $normalizedDecision['fee_term'] = $decision['fee_term'] ?? '1';
                $normalizedDecision['next_billing_date'] = $decision['next_billing_date'] ?? null;
                $normalizedDecision['deposit'] = isset($decision['deposit']) && $decision['deposit'] !== '' ? (float) $decision['deposit'] : null;
                $normalizedDecision['onboarding_fee'] = isset($decision['onboarding_fee']) && $decision['onboarding_fee'] !== '' ? (float) $decision['onboarding_fee'] : null;
                $normalizedDecision['registration_fee'] = $normalizedDecision['onboarding_fee']; // Alias for compatibility
                $normalizedDecision['prorated_fee'] = isset($decision['prorated_fee']) && $decision['prorated_fee'] !== '' ? (float) $decision['prorated_fee'] : null;
                
                // Get class_id from decision, schedule, or course
                $classId = $decision['class_id'] ?? null;
                if (!$classId && $schedule) {
                    $classId = $schedule['class_id'] ?? null;
                }
                $normalizedDecision['class_id'] = $classId ? (int) $classId : null;
            }

            $normalized[] = $normalizedDecision;
        }

        return $normalized;
    }

    private function emptyStringToNull($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $stringValue = is_string($value) ? trim($value) : trim((string) $value);
        return $stringValue === '' ? null : $stringValue;
    }

    private function normalizeBoolean($value, int $default): int
    {
        if ($value === null) {
            return $default;
        }

        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (is_numeric($value)) {
            return ((int) $value) === 1 ? 1 : 0;
        }

        $stringValue = strtolower(trim((string) $value));
        if ($stringValue === '') {
            return $default;
        }

        return in_array($stringValue, ['1', 'true', 'yes', 'on'], true) ? 1 : 0;
    }

    /**
     * @param array<string, mixed> $current
     * @param array<string, mixed> $updateData
     * @param array<string, string> $fieldLabels
     * @return array<string, array<string, mixed>>
     */
    private function collectChanges(array $current, array $updateData, array $fieldLabels): array
    {
        $changes = [];

        foreach ($fieldLabels as $field => $label) {
            if (!array_key_exists($field, $updateData)) {
                continue;
            }

            $before = $current[$field] ?? null;
            $after = $updateData[$field];

            if ($field === 'updated_at' || $this->valuesAreEqual($before, $after)) {
                continue;
            }

            $changes[$field] = [
                'label' => $label,
                'from' => $before,
                'to' => $after
            ];
        }

        return $changes;
    }

    private function valuesAreEqual($before, $after): bool
    {
        $normalizedBefore = $this->normalizeComparisonValue($before);
        $normalizedAfter = $this->normalizeComparisonValue($after);

        return $normalizedBefore === $normalizedAfter;
    }

    private function normalizeComparisonValue($value)
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (is_numeric($value)) {
            return (string) (int) $value;
        }

        $stringValue = trim((string) $value);
        return $stringValue === '' ? null : $stringValue;
    }

    /**
     * @param array<string, array<string, mixed>> $changes
     */
    private function recordHistoryNote(int $registrationId, int $actorId, string $message, array $changes): void
    {
        if (empty($changes)) {
            return;
        }

        $encoded = json_encode(['changes' => $changes], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $metadata = $encoded !== false ? $encoded : null;

        $this->selfRegistrationModel->createRegistrationNote([
            'registration_id' => $registrationId,
            'note_type' => 'history',
            'message' => $message,
            'created_by' => $actorId,
            'created_by_name' => $this->resolveUserName($actorId),
            'origin' => 'automatic',
            'metadata' => $metadata
        ]);
    }

    /**
     * Assign class/teacher to registration with conflict checks
     */
    public function assignClass(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);
            $registrationId = (int) ($payload['registration_id'] ?? 0);
            $classId = isset($payload['class_id']) ? (int) $payload['class_id'] : null;
            $teacherId = isset($payload['teacher_id']) ? (int) $payload['teacher_id'] : null;
            $scheduleId = isset($payload['schedule_id']) ? (int) $payload['schedule_id'] : null;

            if ($registrationId <= 0) {
                return $this->errorResponse('registration_id is required');
            }

            $token = $this->validateToken();
            if (!$token) {
                return $this->unauthorizedResponse('Access token required');
            }

            $actorId = (int) $this->getUserId($token);
            if (!$actorId) {
                return $this->unauthorizedResponse('Unable to resolve user from token');
            }

            $record = $this->selfRegistrationModel->getRegistrationById($registrationId);
            if (empty($record)) {
                return $this->errorResponse('Registration not found', 404);
            }

            $defaultSchoolId = $this->getSchoolId($token);
            if ($defaultSchoolId && (int) $record['school_id'] !== (int) $defaultSchoolId) {
                return $this->unauthorizedResponse('You do not have access to this registration');
            }

            // Check conflicts if teacher_id and schedule provided
            if ($teacherId && $scheduleId) {
                $conflicts = $this->checkScheduleConflicts($defaultSchoolId, $teacherId, $scheduleId);
                if (!empty($conflicts)) {
                    return $this->errorResponse('Schedule conflict detected: ' . json_encode($conflicts));
                }
            }

            // Update course decisions if provided
            if (!empty($payload['course_decisions'])) {
                $detail = $this->selfRegistrationModel->getRegistrationDetail($registrationId);
                $courses = $detail['courses'] ?? [];
                $courseDecisions = $this->prepareCourseDecisions($payload['course_decisions'], $courses);
                
                if (!empty($courseDecisions)) {
                    $this->selfRegistrationModel->updateCourseDecisions($registrationId, $courseDecisions);
                }
            }

            // Update assignment
            $updateData = [];
            if ($classId !== null) {
                $updateData['assigned_class_id'] = $classId;
            }
            if ($teacherId !== null) {
                $updateData['assigned_teacher_id'] = $teacherId;
            }

            if (!empty($updateData)) {
                $updateData['updated_at'] = date('Y-m-d H:i:s');
                $this->selfRegistrationModel->updateRegistration($registrationId, $updateData);
                
                $this->recordHistoryNote(
                    $registrationId,
                    $actorId,
                    'Class/teacher assigned',
                    $updateData
                );
            }

            $detail = $this->selfRegistrationModel->getRegistrationDetail($registrationId);

            return $this->successResponse($detail, 'Class assignment updated');
        } catch (RuntimeException $e) {
            return $this->errorResponse($e->getMessage());
        } catch (\Throwable $e) {
            log_message('error', 'SelfRegistration::assignClass - ' . $e->getMessage());
            return $this->errorResponse('Unable to assign class');
        }
    }

    /**
     * Approve registration and create invoice draft
     */
    public function approve(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);
            $registrationId = (int) ($payload['registration_id'] ?? 0);
            $sendInvoice = isset($payload['send_invoice']) ? $this->normalizeBoolean($payload['send_invoice'], 1) : 1;
            $sendAutopayLink = isset($payload['send_autopay_link']) ? $this->normalizeBoolean($payload['send_autopay_link'], 0) : 0;

            if ($registrationId <= 0) {
                return $this->errorResponse('registration_id is required');
            }

            $token = $this->validateToken();
            if (!$token) {
                return $this->unauthorizedResponse('Access token required');
            }

            $actorId = (int) $this->getUserId($token);
            if (!$actorId) {
                return $this->unauthorizedResponse('Unable to resolve user from token');
            }

            $detail = $this->selfRegistrationModel->getRegistrationDetail($registrationId);
            if (empty($detail)) {
                return $this->errorResponse('Registration not found', 404);
            }

            $registration = $detail['registration'];
            $defaultSchoolId = $this->getSchoolId($token);
            if ($defaultSchoolId && (int) ($registration['school_id'] ?? 0) !== (int) $defaultSchoolId) {
                return $this->unauthorizedResponse('You do not have access to this registration');
            }

            // Calculate total fees from course decisions
            $courses = $detail['courses'] ?? [];
            $totalAmount = 0;
            foreach ($courses as $course) {
                if (($course['decision_status'] ?? '') === 'approved') {
                    $totalAmount += (float) ($course['approved_fee_amount'] ?? $course['fee_amount'] ?? 0);
                }
            }

            if ($totalAmount <= 0) {
                return $this->errorResponse('No approved courses with fees found');
            }

            // Create invoice draft (if student already converted, use student_id; otherwise store registration_id)
            $invoiceModel = new \App\Models\Admin\InvoiceModel();
            $invoiceNumber = 'INV-' . date('Ymd-His') . '-' . $registrationId;
            
            // Note: Store registration_id in metadata if table doesn't have the column
            $invoiceData = [
                'student_id' => $registration['converted_student_user_id'] ?? null,
                'due_date' => $payload['due_date'] ?? date('Y-m-d', strtotime('+30 days')),
                'amount_due' => $totalAmount,
                'amount_paid' => 0,
                'status' => 'draft',
                'invoice_number' => $invoiceNumber,
                'issued_at' => date('Y-m-d H:i:s'),
            ];
            
            // Store registration_id reference in metadata if needed (via separate table or JSON field)
            // For now, we'll track it via invoice_number pattern or add to metadata JSON if available

            $invoiceId = $invoiceModel->insert($invoiceData, true);

            // Update registration status to approved
            $this->selfRegistrationModel->updateRegistration($registrationId, [
                'status' => 'approved',
                'last_status_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            // Send invoice if requested
            if ($sendInvoice) {
                $invoice = $invoiceModel->find($invoiceId);
                $this->sendInvoiceEmail($defaultSchoolId, $registration, $invoice, $sendAutopayLink);
            }

            $this->recordHistoryNote(
                $registrationId,
                $actorId,
                'Registration approved, invoice created',
                [
                    'invoice_id' => $invoiceId,
                    'invoice_number' => $invoiceNumber,
                    'amount' => $totalAmount,
                ]
            );

            return $this->successResponse([
                'invoice_id' => $invoiceId,
                'invoice_number' => $invoiceNumber,
                'amount_due' => $totalAmount,
            ], 'Registration approved and invoice created');
        } catch (RuntimeException $e) {
            return $this->errorResponse($e->getMessage());
        } catch (\Throwable $e) {
            log_message('error', 'SelfRegistration::approve - ' . $e->getMessage());
            return $this->errorResponse('Unable to approve registration');
        }
    }

    /**
     * Check existing active enrollments for student in course
     * GET /admin/self-registrations/check-enrollments?student_id=X&course_id=Y
     */
    public function checkEnrollments(): ResponseInterface
    {
        try {
            $studentId = (int) ($this->request->getGet('student_id') ?? 0);
            $courseId = (int) ($this->request->getGet('course_id') ?? 0);
            $classId = (int) ($this->request->getGet('class_id') ?? 0);

            if ($studentId <= 0) {
                return $this->errorResponse('student_id is required');
            }

            $token = $this->validateToken();
            if (!$token) {
                return $this->unauthorizedResponse('Access token required');
            }

            $defaultSchoolId = $this->getSchoolId($token);
            $db = Database::connect();

            $enrollments = [];

            // Check student_courses for active enrollments in the same course
            if ($courseId > 0) {
                $courseEnrollments = $db->table('student_courses')
                    ->select('student_courses.*, tbl_course.course_name, class.class_id, class.class_name')
                    ->join('tbl_course', 'tbl_course.course_id = student_courses.course_id', 'left')
                    ->join('class', 'class.course_id = student_courses.course_id', 'left')
                    ->where('student_courses.student_id', $studentId)
                    ->where('student_courses.course_id', $courseId)
                    ->where('student_courses.status', 'active')
                    ->get()
                    ->getResultArray();

                foreach ($courseEnrollments as $enrollment) {
                    // Get active classes for this enrollment
                    $activeClasses = $db->table('student_class')
                        ->select('student_class.*, class.class_name, class.class_code')
                        ->join('class', 'class.class_id = student_class.class_id', 'left')
                        ->where('student_class.student_id', $studentId)
                        ->where('class.course_id', $courseId)
                        ->where('student_class.status', '1')
                        ->get()
                        ->getResultArray();

                    $enrollments[] = [
                        'course_id' => (int) $enrollment['course_id'],
                        'course_name' => $enrollment['course_name'] ?? null,
                        'enrollment_date' => $enrollment['enrollment_date'] ?? null,
                        'active_classes' => array_map(function ($class) {
                            return [
                                'class_id' => (int) $class['class_id'],
                                'class_name' => $class['class_name'] ?? null,
                                'class_code' => $class['class_code'] ?? null,
                            ];
                        }, $activeClasses)
                    ];
                }
            }

            // Check if student is already active in a specific class
            $classConflict = null;
            if ($classId > 0) {
                $existingClassEnrollment = $db->table('student_class')
                    ->select('student_class.*, class.class_name, class.class_code, class.course_id, tbl_course.course_name')
                    ->join('class', 'class.class_id = student_class.class_id', 'left')
                    ->join('tbl_course', 'tbl_course.course_id = class.course_id', 'left')
                    ->where('student_class.student_id', $studentId)
                    ->where('student_class.class_id', $classId)
                    ->where('student_class.status', '1')
                    ->get()
                    ->getRowArray();

                if ($existingClassEnrollment) {
                    $classConflict = [
                        'class_id' => (int) $existingClassEnrollment['class_id'],
                        'class_name' => $existingClassEnrollment['class_name'] ?? null,
                        'class_code' => $existingClassEnrollment['class_code'] ?? null,
                        'course_id' => (int) ($existingClassEnrollment['course_id'] ?? 0),
                        'course_name' => $existingClassEnrollment['course_name'] ?? null,
                    ];
                }
            }

            return $this->successResponse([
                'enrollments' => $enrollments,
                'class_conflict' => $classConflict,
                'has_conflicts' => !empty($enrollments) || !empty($classConflict)
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'SelfRegistration::checkEnrollments - ' . $e->getMessage());
            return $this->errorResponse('Unable to check enrollments');
        }
    }

    /**
     * Check schedule conflicts for teacher/room
     */
    private function checkScheduleConflicts(?int $schoolId, int $teacherId, int $scheduleId): array
    {
        $db = \Config\Database::connect();
        
        // Get schedule details
        $schedule = $db->table('t_session')
            ->where('id', $scheduleId)
            ->where('school_id', $schoolId)
            ->get()
            ->getRowArray();

        if (!$schedule) {
            return ['error' => 'Schedule not found'];
        }

        $startsAt = $schedule['starts_at'] ?? null;
        $endsAt = $schedule['ends_at'] ?? null;

        if (!$startsAt || !$endsAt) {
            return [];
        }

        // Check teacher conflicts
        $teacherConflicts = $db->table('t_session')
            ->where('school_id', $schoolId)
            ->where('teacher_id', $teacherId)
            ->where('id !=', $scheduleId)
            ->where('starts_at <', $endsAt)
            ->where('ends_at >', $startsAt)
            ->get()
            ->getResultArray();

        $conflicts = [];
        if (!empty($teacherConflicts)) {
            $conflicts['teacher'] = $teacherConflicts;
        }

        // Check room conflicts if room_id exists
        if (!empty($schedule['room_id'])) {
            $roomConflicts = $db->table('t_session')
                ->where('school_id', $schoolId)
                ->where('room_id', $schedule['room_id'])
                ->where('id !=', $scheduleId)
                ->where('starts_at <', $endsAt)
                ->where('ends_at >', $startsAt)
                ->get()
                ->getResultArray();

            if (!empty($roomConflicts)) {
                $conflicts['room'] = $roomConflicts;
            }
        }

        return $conflicts;
    }

    /**
     * Send invoice email
     */
    private function sendInvoiceEmail(int $schoolId, array $registration, array $invoice, bool $includeAutopayLink): void
    {
        $messaging = service('messaging');
        
        $vars = [
            'student_name' => ($registration['student_first_name'] ?? '') . ' ' . ($registration['student_last_name'] ?? ''),
            'invoice_number' => $invoice['invoice_number'],
            'amount_due' => number_format($invoice['amount_due'], 2),
            'due_date' => $invoice['due_date'],
        ];

        if ($includeAutopayLink && $registration['autopay_authorized']) {
            $vars['autopay_link'] = $this->generateAutopayLink($schoolId, $registration['id']);
        }

        $messaging->sendTemplate(
            $schoolId,
            'email',
            'invoice_sent',
            $registration['email'] ?? $registration['guardian1_email'] ?? '',
            $vars
        );
    }

    /**
     * Generate autopay setup link
     */
    private function generateAutopayLink(int $schoolId, int $registrationId): string
    {
        // TODO: Generate secure token and return portal URL
        return '/portal/autopay/setup?reg=' . $registrationId;
    }

    /**
     * Unified timeline endpoint for registration/student notes.
     */
    public function timeline(): ResponseInterface
    {
        if ($this->request->getMethod() === 'options') {
            return $this->handleOptions();
        }

        $token = $this->validateToken();
        if (!$token) {
            return $this->unauthorizedResponse('Access token required');
        }

        $payload = (array) ($this->request->getJSON() ?? []);
        $registrationId = (int) ($payload['registration_id'] ?? 0);
        $studentId = (int) ($payload['student_user_id'] ?? 0);
        $contactId = (int) ($payload['contact_id'] ?? 0);
        $entityType = strtolower(trim((string) ($payload['entity_type'] ?? '')));
        $entityId = isset($payload['entity_id']) ? trim((string) $payload['entity_id']) : '';

        if (
            $registrationId <= 0 &&
            $studentId <= 0 &&
            $contactId <= 0 &&
            ($entityType === '' || $entityId === '')
        ) {
            return $this->errorResponse('Provide registration_id, student_user_id, contact_id, or entity identifiers');
        }

        $schoolId = (int) ($this->getSchoolId($token) ?? 0);

        if ($registrationId > 0 && !$this->canAccessRegistration($registrationId, $schoolId)) {
            return $this->unauthorizedResponse('You do not have access to this registration');
        }

        if ($studentId > 0 && !$this->canAccessStudent($studentId, $schoolId)) {
            return $this->unauthorizedResponse('You do not have access to this student');
        }

        $notes = $this->selfRegistrationModel->getTimelineNotes([
            'registration_id' => $registrationId ?: null,
            'student_user_id' => $studentId ?: null,
            'contact_id' => $contactId ?: null,
            'entity_type' => $entityType ?: null,
            'entity_id' => $entityId ?: null
        ]);

        return $this->successResponse(['notes' => $notes]);
    }

    private function canAccessStudent(int $studentId, int $schoolId): bool
    {
        if ($studentId <= 0) {
            return false;
        }

        if ($schoolId <= 0) {
            return true;
        }

        $db = Database::connect();
        $row = $db->table('user')
            ->select('school_id')
            ->where('user_id', $studentId)
            ->get()
            ->getRowArray();

        if (!$row) {
            return false;
        }

        $schoolField = (string) ($row['school_id'] ?? '');
        if ($schoolField === '') {
            return false;
        }

        $schools = array_filter(array_map('trim', explode(',', $schoolField)));
        return in_array((string) $schoolId, $schools, true);
    }

    private function resolveUserName(int $userId): string
    {
        if ($userId <= 0) {
            return 'System';
        }

        if (isset($this->userNameCache[$userId])) {
            return $this->userNameCache[$userId];
        }

        $db = Database::connect();
        $profile = $db->table('user_profile')
            ->select("CONCAT_WS(' ', first_name, last_name) AS name")
            ->where('user_id', $userId)
            ->get()
            ->getRowArray();

        $name = trim((string) ($profile['name'] ?? ''));
        if ($name === '') {
            $user = $db->table('user')
                ->select('email_id')
                ->where('user_id', $userId)
                ->get()
                ->getRowArray();
            $name = $user['email_id'] ?? ('User #' . $userId);
        }

        $this->userNameCache[$userId] = $name;
        return $name;
    }
}
