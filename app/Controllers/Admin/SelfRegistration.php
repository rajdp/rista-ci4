<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SelfRegistrationModel;
use App\Services\SelfRegistrationPromotionService;
use App\Traits\RestTrait;
use CodeIgniter\HTTP\ResponseInterface;
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
            $message = trim((string) ($payload['message'] ?? ''));

            if ($registrationId <= 0 || $message === '') {
                return $this->errorResponse('registration_id and message are required');
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

            $noteType = $payload['note_type'] ?? 'internal';
            $noteType = in_array($noteType, $this->allowedNoteTypes, true) ? $noteType : 'internal';

            $noteId = $this->selfRegistrationModel->createRegistrationNote([
                'registration_id' => $registrationId,
                'note_type' => $noteType,
                'message' => $message,
                'created_by' => $actorId,
                'metadata' => isset($payload['metadata']) ? json_encode($payload['metadata']) : null
            ]);

            $notes = $this->selfRegistrationModel->getRegistrationNotes($registrationId);

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
                'metadata' => json_encode($metadata),
                'sent_by' => $actorId,
                'sent_at' => date('Y-m-d H:i:s')
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

            return $this->successResponse($result, 'Registration converted successfully');
        } catch (RuntimeException $e) {
            return $this->errorResponse($e->getMessage());
        } catch (\Throwable $e) {
            log_message('error', 'SelfRegistration::promote - ' . $e->getMessage());
            return $this->errorResponse('Unable to convert registration');
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
        foreach ($courses as $course) {
            $rowId = isset($course['id']) ? (int) $course['id'] : 0;
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
        }

        $normalized = [];
        foreach ($decisions as $decision) {
            if (!is_array($decision)) {
                continue;
            }

            $rowId = isset($decision['registration_course_id'])
                ? (int) $decision['registration_course_id']
                : (isset($decision['id']) ? (int) $decision['id'] : 0);

            if ($rowId <= 0 || !isset($courseLookup[$rowId])) {
                continue;
            }

            $course = $courseLookup[$rowId];
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

            $normalized[] = [
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
            'metadata' => $metadata
        ]);
    }
}
