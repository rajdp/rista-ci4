<?php

namespace App\Services\ReportCard;

use App\Models\ReportCardEventModel;

class AuditService
{
    protected $eventModel;

    public function __construct()
    {
        $this->eventModel = new ReportCardEventModel();
    }

    /**
     * Log an event
     *
     * @param int $rcId Report card ID
     * @param string $eventType Event type
     * @param int|null $actorId User who performed the action
     * @param array|null $metaData Additional metadata
     * @return bool
     */
    public function logEvent(int $rcId, string $eventType, ?int $actorId = null, ?array $metaData = null): bool
    {
        return $this->eventModel->logEvent($rcId, $eventType, $actorId, $metaData);
    }

    /**
     * Log portal view
     *
     * @param int $rcId
     * @param int $userId
     * @param array $requestData IP, user agent, etc.
     * @return bool
     */
    public function logPortalView(int $rcId, int $userId, array $requestData = []): bool
    {
        $metaData = [
            'ip' => $requestData['ip'] ?? $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $requestData['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? null,
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        return $this->logEvent($rcId, 'portal_view', $userId, $metaData);
    }

    /**
     * Log email sent
     *
     * @param int $rcId
     * @param string $recipient
     * @param int $actorId
     * @param array $emailData
     * @return bool
     */
    public function logEmailSent(int $rcId, string $recipient, int $actorId, array $emailData = []): bool
    {
        $metaData = [
            'recipient' => $recipient,
            'subject' => $emailData['subject'] ?? '',
            'provider' => $emailData['provider'] ?? '',
            'message_id' => $emailData['message_id'] ?? '',
        ];

        return $this->logEvent($rcId, 'email_sent', $actorId, $metaData);
    }

    /**
     * Log email failed
     *
     * @param int $rcId
     * @param string $recipient
     * @param int $actorId
     * @param string $error
     * @return bool
     */
    public function logEmailFailed(int $rcId, string $recipient, int $actorId, string $error): bool
    {
        $metaData = [
            'recipient' => $recipient,
            'error' => $error,
        ];

        return $this->logEvent($rcId, 'email_failed', $actorId, $metaData);
    }

    /**
     * Get event timeline for a report card
     *
     * @param int $rcId
     * @return array
     */
    public function getEventTimeline(int $rcId): array
    {
        $events = $this->eventModel->getReportCardEvents($rcId);

        $timeline = [];
        foreach ($events as $event) {
            $meta = json_decode($event['meta_json'] ?? '{}', true);
            $timeline[] = [
                'event_type' => $event['event_type'],
                'actor_id' => $event['actor_id'],
                'created_at' => $event['created_at'],
                'meta' => $meta,
            ];
        }

        return $timeline;
    }

    /**
     * Get analytics data
     *
     * @param int $schoolId
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @return array
     */
    public function getAnalytics(int $schoolId, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        // Email statistics
        $emailStats = $this->eventModel->getEmailStats($schoolId, $dateFrom, $dateTo);

        $analytics = [
            'email_sent' => 0,
            'email_failed' => 0,
            'portal_views' => 0,
            'revocations' => 0,
        ];

        foreach ($emailStats as $stat) {
            $analytics[$stat['event_type']] = (int) $stat['count'];
        }

        return $analytics;
    }
}
