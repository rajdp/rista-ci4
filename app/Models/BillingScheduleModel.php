<?php

namespace App\Models;

use CodeIgniter\Model;

class BillingScheduleModel extends Model
{
    protected $table = 't_billing_schedule';
    protected $primaryKey = 'schedule_id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'school_id',
        'enrollment_id',
        'student_id',
        'course_id',
        'term',
        'anchor_day',
        'anchor_month',
        'next_billing_date',
        'deposit_policy',
        'deposit_cents',
        'has_prorated_first',
        'status',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Get due schedules for a school
     *
     * @param int $schoolId
     * @param string|null $date Date in Y-m-d format, defaults to today
     * @return array
     */
    public function getDueSchedules(int $schoolId, ?string $date = null): array
    {
        if ($date === null) {
            $date = date('Y-m-d');
        }

        return $this->where('school_id', $schoolId)
            ->where('status', 'active')
            ->where('next_billing_date <=', $date)
            ->where('next_billing_date IS NOT NULL')
            ->findAll();
    }

    /**
     * Get schedule by enrollment ID
     *
     * @param int $enrollmentId
     * @return array|null
     */
    public function getByEnrollment(int $enrollmentId): ?array
    {
        try {
            $builder = $this->where('enrollment_id', $enrollmentId);
            $result = $builder->get();
            
            // Check if query failed
            if ($result === false) {
                log_message('error', 'BillingScheduleModel::getByEnrollment - Query failed for enrollment_id: ' . $enrollmentId);
                return null;
            }
            
            return $result->getRowArray() ?: null;
        } catch (\Exception $e) {
            log_message('error', 'BillingScheduleModel::getByEnrollment - Exception: ' . $e->getMessage());
            return null;
        } catch (\Throwable $e) {
            log_message('error', 'BillingScheduleModel::getByEnrollment - Throwable: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Advance next billing date by one term
     *
     * @param int $scheduleId
     * @param string $term 'weekly', 'monthly', or 'yearly'
     * @return bool
     */
    public function advanceNextBillingDate(int $scheduleId, string $term): bool
    {
        $schedule = $this->find($scheduleId);
        if (!$schedule || !$schedule['next_billing_date']) {
            return false;
        }

        $currentDate = new \DateTime($schedule['next_billing_date']);
        $anchorDay = $schedule['anchor_day'] ?? (int)$currentDate->format('d');
        $anchorMonth = $schedule['anchor_month'] ?? (int)$currentDate->format('m');

        if ($term === 'weekly') {
            // Add one week (7 days)
            $nextDate = clone $currentDate;
            $nextDate->modify('+1 week');
        } elseif ($term === 'monthly') {
            // Add one month
            $nextDate = clone $currentDate;
            $nextDate->modify('+1 month');
            
            // Apply anchor day with month-end handling
            $targetDay = min($anchorDay, $nextDate->format('t'));
            $nextDate->setDate((int)$nextDate->format('Y'), (int)$nextDate->format('m'), $targetDay);
        } elseif ($term === 'yearly') {
            // Add one year
            $nextDate = clone $currentDate;
            $nextDate->modify('+1 year');
            
            // Apply anchor month and day with month-end handling
            $targetDay = min($anchorDay, $nextDate->format('t'));
            $nextDate->setDate((int)$nextDate->format('Y'), $anchorMonth, $targetDay);
        } else {
            // One-time: set to NULL
            return $this->update($scheduleId, ['next_billing_date' => null]);
        }

        return $this->update($scheduleId, [
            'next_billing_date' => $nextDate->format('Y-m-d'),
            'has_prorated_first' => 1, // Mark as prorated after first invoice
        ]);
    }

    /**
     * Get schedules with filters
     *
     * @param int $schoolId
     * @param array $filters
     * @return array
     */
    public function getSchedulesWithFilters(int $schoolId, array $filters = []): array
    {
        $builder = $this->where('school_id', $schoolId);

        if (!empty($filters['term'])) {
            $builder->where('term', $filters['term']);
        }

        if (!empty($filters['status'])) {
            $builder->where('status', $filters['status']);
        }

        if (!empty($filters['course_id'])) {
            $builder->where('course_id', $filters['course_id']);
        }

        if (!empty($filters['student_id'])) {
            $builder->where('student_id', $filters['student_id']);
        }

        if (!empty($filters['from_date'])) {
            $builder->where('next_billing_date >=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $builder->where('next_billing_date <=', $filters['to_date']);
        }

        return $builder->orderBy('next_billing_date', 'ASC')
            ->findAll();
    }
}

