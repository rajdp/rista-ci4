<?php

namespace App\Models;

use CodeIgniter\Model;

class WebhookEventModel extends Model
{
    protected $table = 't_webhook_event';
    protected $primaryKey = 'event_id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'school_id',
        'provider',
        'event_type',
        'event_idempotency',
        'payload',
        'processed',
    ];

    // Dates
    protected $useTimestamps = false;
    protected $createdField = 'created_at';

    // Validation
    protected $validationRules = [
        'school_id' => 'required|integer',
        'provider' => 'required|max_length[32]',
        'event_type' => 'required|max_length[64]',
        'payload' => 'required',
    ];

    protected $skipValidation = false;

    /**
     * Get pending events
     */
    public function getPending(int $limit = 100): array
    {
        return $this->where('processed', 0)
            ->orderBy('created_at', 'ASC')
            ->findAll($limit);
    }

    /**
     * Mark as processed
     */
    public function markProcessed(int $eventId): bool
    {
        return $this->update($eventId, ['processed' => 1]);
    }

    /**
     * Find by idempotency key
     */
    public function findByIdempotency(string $idempotency): ?array
    {
        return $this->where('event_idempotency', $idempotency)->first();
    }

    /**
     * Get events by provider and type
     */
    public function getByProviderAndType(string $provider, string $eventType, int $limit = 50): array
    {
        return $this->where('provider', $provider)
            ->where('event_type', $eventType)
            ->orderBy('created_at', 'DESC')
            ->findAll($limit);
    }

    /**
     * Cleanup old processed events
     */
    public function cleanupOldEvents(int $daysOld = 90): int
    {
        $date = date('Y-m-d', strtotime("-{$daysOld} days"));

        return $this->where('processed', 1)
            ->where('created_at <', $date)
            ->delete();
    }
}
