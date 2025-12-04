<?php

namespace App\Models;

use CodeIgniter\Model;

class SubscriptionModel extends Model
{
    protected $table = 't_subscription';
    protected $primaryKey = 'subscription_id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'school_id',
        'student_id',
        'course_id',
        'term',
        'status',
        'amount_cents',
        'anchor_day',
        'next_billing_date',
        'autopay_enabled',
        'deposit_cents',
        'deposit_type',
        'proration_policy',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Validation
    protected $validationRules = [
        'school_id' => 'required|integer',
        'student_id' => 'required|integer',
        'course_id' => 'required|integer',
        'term' => 'required|in_list[one_time,monthly,quarterly,annual]',
        'status' => 'permit_empty|in_list[active,on_hold,canceled,completed]',
        'amount_cents' => 'required|integer',
        'autopay_enabled' => 'permit_empty|in_list[0,1]',
        'deposit_type' => 'permit_empty|in_list[none,refundable,non_refundable]',
        'proration_policy' => 'permit_empty|in_list[none,daily,half_month]',
    ];

    protected $validationMessages = [
        'school_id' => [
            'required' => 'School ID is required',
        ],
        'student_id' => [
            'required' => 'Student ID is required',
        ],
        'course_id' => [
            'required' => 'Course ID is required',
        ],
        'term' => [
            'required' => 'Billing term is required',
            'in_list' => 'Invalid billing term',
        ],
        'amount_cents' => [
            'required' => 'Amount is required',
        ],
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = [];
    protected $afterInsert = [];
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = [];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    /**
     * Get active subscriptions for a school
     */
    public function getActiveBySchool(int $schoolId, array $filters = []): array
    {
        $builder = $this->where('school_id', $schoolId)
            ->where('status', 'active');

        if (!empty($filters['student_id'])) {
            $builder->where('student_id', $filters['student_id']);
        }

        if (!empty($filters['course_id'])) {
            $builder->where('course_id', $filters['course_id']);
        }

        if (!empty($filters['term'])) {
            $builder->where('term', $filters['term']);
        }

        return $builder->findAll();
    }

    /**
     * Get subscriptions due for billing
     */
    public function getDueForBilling(int $schoolId, string $date): array
    {
        return $this->where('school_id', $schoolId)
            ->where('status', 'active')
            ->where('next_billing_date <=', $date)
            ->findAll();
    }

    /**
     * Get subscription with student and course details
     */
    public function getWithDetails(int $subscriptionId): ?array
    {
        return $this->select('t_subscription.*, user.full_name as student_name, user.email as student_email, tbl_course.course_name')
            ->join('user', 'user.user_id = t_subscription.student_id', 'left')
            ->join('tbl_course', 'tbl_course.id = t_subscription.course_id', 'left')
            ->where('t_subscription.subscription_id', $subscriptionId)
            ->first();
    }

    /**
     * Update next billing date
     */
    public function updateNextBillingDate(int $subscriptionId, string $nextDate): bool
    {
        return $this->update($subscriptionId, [
            'next_billing_date' => $nextDate,
        ]);
    }

    /**
     * Toggle autopay
     */
    public function toggleAutopay(int $subscriptionId, bool $enabled): bool
    {
        return $this->update($subscriptionId, [
            'autopay_enabled' => $enabled ? 1 : 0,
        ]);
    }

    /**
     * Cancel subscription
     */
    public function cancelSubscription(int $subscriptionId, string $reason = ''): bool
    {
        return $this->update($subscriptionId, [
            'status' => 'canceled',
        ]);
    }

    /**
     * Put subscription on hold
     */
    public function holdSubscription(int $subscriptionId): bool
    {
        return $this->update($subscriptionId, [
            'status' => 'on_hold',
        ]);
    }

    /**
     * Resume held subscription
     */
    public function resumeSubscription(int $subscriptionId): bool
    {
        return $this->update($subscriptionId, [
            'status' => 'active',
        ]);
    }

    /**
     * Get subscription count by school
     */
    public function getCountBySchool(int $schoolId, string $status = 'active'): int
    {
        return $this->where('school_id', $schoolId)
            ->where('status', $status)
            ->countAllResults();
    }
}
