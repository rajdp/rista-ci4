<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Traits\RestTrait;
use App\Models\Admin\NotificationTemplateModel;
use App\Models\Admin\NotificationModel;
use App\Models\Admin\NotificationOptoutModel;
use App\Models\Admin\GuardianModel;
use App\Models\Admin\StudentsModel;
use CodeIgniter\HTTP\ResponseInterface;

class Notifications extends BaseController
{
    use RestTrait;

    protected NotificationTemplateModel $templateModel;
    protected NotificationModel $notificationModel;
    protected NotificationOptoutModel $optoutModel;
    protected GuardianModel $guardianModel;
    protected StudentsModel $studentsModel;

    public function __construct()
    {
        $this->templateModel = new NotificationTemplateModel();
        $this->notificationModel = new NotificationModel();
        $this->optoutModel = new NotificationOptoutModel();
        $this->guardianModel = new GuardianModel();
        $this->studentsModel = new StudentsModel();
    }

    public function templates(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);
            if (empty($payload['school_id'])) {
                return $this->errorResponse('school_id is required');
            }

            $templates = $this->templateModel->listForSchool((int) $payload['school_id']);
            foreach ($templates as &$template) {
                if (!empty($template['placeholders'])) {
                    $template['placeholders'] = json_decode($template['placeholders'], true);
                }
            }

            return $this->successResponse($templates, 'Templates retrieved');
        } catch (\Throwable $e) {
            return $this->errorResponse('Unable to load templates: ' . $e->getMessage());
        }
    }

    public function saveTemplate(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);
            $required = ['school_id', 'name', 'body'];

            if (!$this->validateRequiredFields($payload, $required)) {
                return $this->errorResponse('Missing required fields: ' . implode(', ', $this->getMissingFields($payload, $required)));
            }

            $templateData = [
                'school_id' => (int) $payload['school_id'],
                'name' => $payload['name'],
                'channel' => $payload['channel'] ?? 'both',
                'subject' => $payload['subject'] ?? null,
                'body' => $payload['body'],
                'placeholders' => !empty($payload['placeholders']) ? json_encode($payload['placeholders']) : null,
                'created_by' => $payload['created_by'] ?? null,
            ];

            if (!empty($payload['id'])) {
                $this->templateModel->update((int) $payload['id'], $templateData);
                $template = $this->templateModel->find((int) $payload['id']);
                $message = 'Template updated';
            } else {
                $templateId = $this->templateModel->insert($templateData, true);
                $template = $this->templateModel->find($templateId);
                $message = 'Template created';
            }

            if (!empty($template['placeholders'])) {
                $template['placeholders'] = json_decode($template['placeholders'], true);
            }

            return $this->successResponse($template, $message);
        } catch (\Throwable $e) {
            return $this->errorResponse('Unable to save template: ' . $e->getMessage());
        }
    }

    public function queue(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);
            $required = ['template_id', 'recipient_type', 'recipient_id', 'channel'];

            if (!$this->validateRequiredFields($payload, $required)) {
                return $this->errorResponse('Missing required fields: ' . implode(', ', $this->getMissingFields($payload, $required)));
            }

            $template = $this->templateModel->find((int) $payload['template_id']);
            if (!$template) {
                return $this->errorResponse('Template not found', 404);
            }

            $recipientType = $payload['recipient_type'];
            $recipientId = (int) $payload['recipient_id'];
            $channel = $payload['channel'];
            $studentId = $payload['student_id'] ?? null;

            if ($this->optoutModel->isOptedOut($recipientType, $recipientId, $channel)) {
                return $this->errorResponse('Recipient has opted out of this channel', 409);
            }

            $variables = $payload['variables'] ?? [];
            if ($studentId) {
                $student = $this->studentsModel->find((int) $studentId);
                if ($student) {
                    $variables = array_merge([
                        'student_name' => trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? '')),
                    ], $variables);
                }
            }

            if ($recipientType === 'guardian') {
                $guardian = $this->guardianModel->find($recipientId);
                if ($guardian) {
                    $variables = array_merge([
                        'guardian_name' => trim(($guardian['first_name'] ?? '') . ' ' . ($guardian['last_name'] ?? '')),
                    ], $variables);
                }
            }

            $content = $this->renderTemplate($template, $variables);

            $data = [
                'template_id' => (int) $payload['template_id'],
                'school_id' => $template['school_id'],
                'recipient_type' => $recipientType,
                'recipient_id' => $recipientId,
                'student_id' => $studentId,
                'channel' => $channel,
                'status' => 'queued',
                'scheduled_at' => $payload['scheduled_at'] ?? null,
                'payload' => json_encode($content),
            ];

            $notificationId = $this->notificationModel->queue($data);
            $notification = $this->notificationModel->find($notificationId);
            if (!empty($notification['payload'])) {
                $notification['payload'] = json_decode($notification['payload'], true);
            }

            return $this->successResponse($notification, 'Notification queued');
        } catch (\Throwable $e) {
            return $this->errorResponse('Unable to queue notification: ' . $e->getMessage());
        }
    }

    public function list(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);

            $builder = $this->notificationModel->builder();

            if (!empty($payload['school_id'])) {
                $builder->where('school_id', (int) $payload['school_id']);
            }
            if (!empty($payload['status'])) {
                $builder->where('status', $payload['status']);
            }
            if (!empty($payload['recipient_type'])) {
                $builder->where('recipient_type', $payload['recipient_type']);
            }

            $builder->orderBy('created_at', 'DESC');
            $results = $builder->get()->getResultArray();

            foreach ($results as &$row) {
                if (!empty($row['payload'])) {
                    $row['payload'] = json_decode($row['payload'], true);
                }
            }

            return $this->successResponse($results, 'Notifications retrieved');
        } catch (\Throwable $e) {
            return $this->errorResponse('Unable to list notifications: ' . $e->getMessage());
        }
    }

    public function setOptout(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);
            $required = ['contact_type', 'contact_id', 'channel', 'opted_out'];

            if (!$this->validateRequiredFields($payload, $required)) {
                return $this->errorResponse('Missing required fields: ' . implode(', ', $this->getMissingFields($payload, $required)));
            }

            $contactType = $payload['contact_type'];
            $contactId = (int) $payload['contact_id'];
            $channel = $payload['channel'];

            if (!empty($payload['opted_out'])) {
                $data = [
                    'contact_type' => $contactType,
                    'contact_id' => $contactId,
                    'channel' => $channel,
                    'reason' => $payload['reason'] ?? null,
                    'metadata' => !empty($payload['metadata']) ? json_encode($payload['metadata']) : null,
                    'created_at' => date('Y-m-d H:i:s'),
                ];

                $existing = $this->optoutModel
                    ->where('contact_type', $contactType)
                    ->where('contact_id', $contactId)
                    ->where('channel', $channel)
                    ->first();

                if ($existing) {
                    $this->optoutModel->update($existing['id'], $data);
                } else {
                    $this->optoutModel->insert($data);
                }
            } else {
                $this->optoutModel
                    ->where('contact_type', $contactType)
                    ->where('contact_id', $contactId)
                    ->where('channel', $channel)
                    ->delete();
            }

            return $this->successResponse(true, 'Opt-out preferences updated');
        } catch (\Throwable $e) {
            return $this->errorResponse('Unable to update opt-out: ' . $e->getMessage());
        }
    }

    private function renderTemplate(array $template, array $variables): array
    {
        $replacements = [];
        foreach ($variables as $key => $value) {
            $replacements['{' . $key . '}'] = $value;
        }

        $subject = $template['subject'] ?? '';
        $body = $template['body'] ?? '';

        if (!empty($replacements)) {
            $subject = strtr($subject, $replacements);
            $body = strtr($body, $replacements);
        }

        return [
            'subject' => $subject,
            'body' => $body,
            'variables' => $variables,
        ];
    }
}
