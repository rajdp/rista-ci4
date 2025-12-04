<?php

namespace App\Models;

use App\Models\V1\BaseModel;

class ReportCardEventModel extends BaseModel
{
    protected $table = 't_rc_event';
    protected $primaryKey = 'event_id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'rc_id',
        'event_type',
        'actor_id',
        'meta_json',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = null; // Events are immutable

    protected $validationRules = [
        'rc_id' => 'required|integer',
        'event_type' => 'required|in_list[email_sent,email_failed,portal_view,revoked,reissued]',
    ];

    protected $validationMessages = [
        'rc_id' => [
            'required' => 'Report card ID is required',
        ],
        'event_type' => [
            'required' => 'Event type is required',
            'in_list' => 'Invalid event type',
        ],
    ];

    /**
     * Log an event
     */
    public function logEvent($rcId, $eventType, $actorId = null, $metaData = null)
    {
        $data = [
            'rc_id' => $rcId,
            'event_type' => $eventType,
            'actor_id' => $actorId,
            'meta_json' => $metaData ? json_encode($metaData) : null,
        ];

        return $this->insert($data);
    }

    /**
     * Get events for a report card
     */
    public function getReportCardEvents($rcId, $eventType = null)
    {
        $builder = $this->where('rc_id', $rcId);

        if ($eventType) {
            $builder->where('event_type', $eventType);
        }

        return $builder->orderBy('created_at', 'DESC')->findAll();
    }

    /**
     * Check if email was already sent (idempotency)
     */
    public function wasEmailSent($rcId, $recipient)
    {
        $events = $this->where('rc_id', $rcId)
                       ->where('event_type', 'email_sent')
                       ->findAll();

        foreach ($events as $event) {
            if ($event['meta_json']) {
                $meta = json_decode($event['meta_json'], true);
                if (isset($meta['recipient']) && $meta['recipient'] === $recipient) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get portal view count
     */
    public function getPortalViewCount($rcId)
    {
        return $this->where('rc_id', $rcId)
                    ->where('event_type', 'portal_view')
                    ->countAllResults();
    }

    /**
     * Get email statistics
     */
    public function getEmailStats($schoolId, $dateFrom = null, $dateTo = null)
    {
        $builder = $this->db->table('t_rc_event e')
                           ->select('e.event_type, COUNT(*) as count')
                           ->join('t_report_card rc', 'rc.rc_id = e.rc_id')
                           ->where('rc.school_id', $schoolId)
                           ->whereIn('e.event_type', ['email_sent', 'email_failed'])
                           ->groupBy('e.event_type');

        if ($dateFrom) {
            $builder->where('e.created_at >=', $dateFrom);
        }

        if ($dateTo) {
            $builder->where('e.created_at <=', $dateTo);
        }

        return $builder->get()->getResultArray();
    }
}
