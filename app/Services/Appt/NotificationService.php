<?php

namespace App\Services\Appt;

use App\Models\Appt\NotificationModel;

class NotificationService
{
    public function __construct(private NotificationModel $notificationModel)
    {
    }

    public function log(int $apptId, string $purpose, string $channel = 'email', string $status = 'queued', ?string $providerId = null): void
    {
        $this->notificationModel->insert([
            'appt_id' => $apptId,
            'channel' => $channel,
            'purpose' => $purpose,
            'status' => $status,
            'provider_id' => $providerId,
            'sent_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
