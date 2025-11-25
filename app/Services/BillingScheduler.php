<?php

namespace App\Services;

use App\Models\BillingScheduleModel;
use App\Models\CourseFeePlanModel;

class BillingScheduler
{
    protected $scheduleModel;
    protected $courseFeePlanModel;
    protected $db;

    public function __construct()
    {
        $this->scheduleModel = new BillingScheduleModel();
        $this->courseFeePlanModel = new CourseFeePlanModel();
        $this->db = \Config\Database::connect();
    }

    /**
     * Convert legacy fee_term (1=one-time, 2=recurring) and billing_cycle_days to PRD term enum
     *
     * @param int|null $legacyTerm
     * @param int|null $billingCycleDays
     * @return string 'one_time', 'weekly', 'monthly', or 'yearly'
     */
    protected function convertTerm(?int $legacyTerm, ?int $billingCycleDays = null): string
    {
        if ($legacyTerm == 2 && $billingCycleDays) {
            // Recurring - determine term from billing_cycle_days
            if ($billingCycleDays == 7) {
                return 'weekly';
            } elseif ($billingCycleDays == 30) {
                return 'monthly';
            } elseif ($billingCycleDays >= 365) {
                return 'yearly';
            } else {
                // Other recurring cycles default to monthly
                return 'monthly';
            }
        }
        return 'one_time';
    }

    /**
     * Seed billing schedule on enrollment
     *
     * @param int $enrollmentId student_courses.id
     * @param int $studentId
     * @param int $courseId
     * @param int $schoolId
     * @param string $startDate Enrollment start date (Y-m-d)
     * @param array $options Additional options (deposit_policy, deposit_cents, anchor_day override)
     * @return array Result with success status and schedule_id
     */
    public function seedSchedule(int $enrollmentId, int $studentId, int $courseId, int $schoolId, string $startDate, array $options = []): array
    {
        try {
            // Check if billing schedule table exists
            if (!$this->db->tableExists('t_billing_schedule')) {
                log_message('warning', 'BillingScheduler::seedSchedule - Table t_billing_schedule does not exist. Skipping billing schedule creation.');
                return [
                    'success' => false,
                    'message' => 'Billing schedule table not available'
                ];
            }
            
            // Get course fee information
            $courseFee = $this->courseFeePlanModel->getFeeForCourse($courseId, $schoolId);
            if (!$courseFee) {
                return [
                    'success' => false,
                    'message' => 'Course fee information not found'
                ];
            }

            // Determine term
            $term = $this->convertTerm($courseFee['fee_term'] ?? null, $courseFee['billing_cycle_days'] ?? null);
            
            // Calculate anchor day/month
            $startDateTime = new \DateTime($startDate);
            $anchorDay = $options['anchor_day'] ?? null;
            $anchorMonth = null;
            
            if ($term === 'weekly') {
                // For weekly, anchor_day is the day of week (0=Sunday, 6=Saturday) from start date
                // But we'll use the start date's day of week as the anchor
                $anchorDay = $anchorDay ?? (int)$startDateTime->format('w');
            } elseif ($term === 'monthly' || $term === 'yearly') {
                // For monthly/yearly, use day of month
                $anchorDay = $anchorDay ?? (int)$startDateTime->format('d');
            } else {
                // For one_time or unknown terms, set a default anchor day (won't be used but prevents null error)
                $anchorDay = $anchorDay ?? 1;
            }
            
            if ($term === 'yearly') {
                $anchorMonth = $options['anchor_month'] ?? (int)$startDateTime->format('m');
            }

            // Ensure anchorDay is always an integer
            $anchorDay = (int)($anchorDay ?? 1);

            // Use provided next_billing_date if available, otherwise compute it
            $nextBillingDate = $options['next_billing_date'] ?? null;
            if (!$nextBillingDate) {
                $nextBillingDate = $this->computeNextBillingDate($term, $startDate, $anchorDay, $anchorMonth);
            }

            // Check if proration is needed
            $hasProratedFirst = 0;
            if ($term !== 'one_time' && $nextBillingDate !== $startDate) {
                // Start date is not exactly on anchor, so proration will be needed
                $hasProratedFirst = 0; // Will be set to 1 after first invoice
            }

            // Get deposit info
            $depositPolicy = $options['deposit_policy'] ?? 'none';
            $depositCents = $options['deposit_cents'] ?? 0;
            if ($depositPolicy === 'none') {
                $depositCents = 0;
            }

            // Check if schedule already exists
            $existing = $this->scheduleModel->getByEnrollment($enrollmentId);
            
            $scheduleData = [
                'school_id' => $schoolId,
                'enrollment_id' => $enrollmentId,
                'student_id' => $studentId,
                'course_id' => $courseId,
                'term' => $term,
                'anchor_day' => $anchorDay,
                'anchor_month' => $anchorMonth,
                'next_billing_date' => $nextBillingDate,
                'deposit_policy' => $depositPolicy,
                'deposit_cents' => $depositCents,
                'has_prorated_first' => $hasProratedFirst,
                'status' => 'active',
            ];

            if ($existing) {
                // Update existing schedule
                $this->scheduleModel->update($existing['schedule_id'], $scheduleData);
                $scheduleId = $existing['schedule_id'];
            } else {
                // Create new schedule
                $scheduleId = $this->scheduleModel->insert($scheduleData);
            }

            return [
                'success' => true,
                'schedule_id' => $scheduleId,
                'next_billing_date' => $nextBillingDate,
                'term' => $term,
            ];
        } catch (\Exception $e) {
            log_message('error', 'BillingScheduler::seedSchedule failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to seed billing schedule: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Compute next billing date based on term and anchor
     *
     * @param string $term 'one_time', 'weekly', 'monthly', or 'yearly'
     * @param string $startDate Start date (Y-m-d)
     * @param int $anchorDay Day of month (1-31) for monthly/yearly, or day of week (0-6) for weekly
     * @param int|null $anchorMonth Month (1-12) for yearly
     * @return string|null Next billing date (Y-m-d) or null for one_time
     */
    protected function computeNextBillingDate(string $term, string $startDate, int $anchorDay, ?int $anchorMonth = null): ?string
    {
        $start = new \DateTime($startDate);
        
        if ($term === 'one_time') {
            return $startDate; // Or could be a chosen date
        }

        if ($term === 'weekly') {
            // For weekly, add 7 days from start date
            // The anchor_day stores the preferred day of week, but we'll bill 7 days from start
            $targetDate = clone $start;
            $targetDate->modify('+7 days');
            return $targetDate->format('Y-m-d');
        }

        if ($term === 'monthly') {
            // Next occurrence of anchor_day >= start_date
            $targetDate = clone $start;
            $targetDate->setDate((int)$targetDate->format('Y'), (int)$targetDate->format('m'), $anchorDay);
            
            // If target is before start, move to next month
            if ($targetDate < $start) {
                $targetDate->modify('+1 month');
            }
            
            // Handle month-end: if anchor_day > days in month, use last day
            $lastDayOfMonth = (int)$targetDate->format('t');
            if ($anchorDay > $lastDayOfMonth) {
                $targetDate->setDate((int)$targetDate->format('Y'), (int)$targetDate->format('m'), $lastDayOfMonth);
            }
            
            return $targetDate->format('Y-m-d');
        }

        if ($term === 'yearly') {
            // Next occurrence of (anchor_month, anchor_day) >= start_date
            $targetDate = clone $start;
            $targetDate->setDate((int)$targetDate->format('Y'), $anchorMonth ?? (int)$start->format('m'), $anchorDay);
            
            // If target is before start, move to next year
            if ($targetDate < $start) {
                $targetDate->modify('+1 year');
            }
            
            // Handle month-end: if anchor_day > days in month, use last day
            $lastDayOfMonth = (int)$targetDate->format('t');
            if ($anchorDay > $lastDayOfMonth) {
                $targetDate->setDate((int)$targetDate->format('Y'), (int)$targetDate->format('m'), $lastDayOfMonth);
            }
            
            return $targetDate->format('Y-m-d');
        }

        return null;
    }

    /**
     * Update schedule status
     *
     * @param int $scheduleId
     * @param string $status 'active', 'paused', or 'ended'
     * @return bool
     */
    public function updateStatus(int $scheduleId, string $status): bool
    {
        return $this->scheduleModel->update($scheduleId, ['status' => $status]);
    }
}

