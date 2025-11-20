<?php

namespace App\Services;

class ProrationService
{
    /**
     * Calculate monthly proration
     *
     * @param int $feeMonthlyCents Monthly fee in cents
     * @param string $startDate Start date (Y-m-d)
     * @param string $anchorDate Anchor date (Y-m-d)
     * @param string $method 'daily' or 'half_up' (default: 'daily')
     * @return int Prorated amount in cents
     */
    public function calculateMonthlyProration(int $feeMonthlyCents, string $startDate, string $anchorDate, string $method = 'daily'): int
    {
        $start = new \DateTime($startDate);
        $anchor = new \DateTime($anchorDate);
        
        // Days used = inclusive days from start_date to anchor_date-1
        $anchorMinusOne = clone $anchor;
        $anchorMinusOne->modify('-1 day');
        
        $daysUsed = $start->diff($anchorMinusOne)->days + 1; // +1 for inclusive
        if ($daysUsed < 0) {
            return 0;
        }
        
        // Days in the month containing the anchor date
        $daysInMonth = (int)$anchor->format('t');
        
        // Calculate proration
        $proratedCents = (int)round($feeMonthlyCents * ($daysUsed / $daysInMonth));
        
        return max(0, $proratedCents);
    }

    /**
     * Calculate yearly proration
     *
     * @param int $feeYearlyCents Yearly fee in cents
     * @param string $startDate Start date (Y-m-d)
     * @param string $anchorDate Anchor date (Y-m-d)
     * @param string $method 'daily' or 'half_up' (default: 'daily')
     * @return int Prorated amount in cents
     */
    public function calculateYearlyProration(int $feeYearlyCents, string $startDate, string $anchorDate, string $method = 'daily'): int
    {
        $start = new \DateTime($startDate);
        $anchor = new \DateTime($anchorDate);
        
        // Days used = inclusive days from start_date to anchor_date-1
        $anchorMinusOne = clone $anchor;
        $anchorMinusOne->modify('-1 day');
        
        $daysUsed = $start->diff($anchorMinusOne)->days + 1; // +1 for inclusive
        if ($daysUsed < 0) {
            return 0;
        }
        
        // Days in the year containing the anchor date
        $year = (int)$anchor->format('Y');
        $isLeapYear = (($year % 4 == 0 && $year % 100 != 0) || ($year % 400 == 0));
        $daysInYear = $isLeapYear ? 366 : 365;
        
        // Calculate proration
        $proratedCents = (int)round($feeYearlyCents * ($daysUsed / $daysInYear));
        
        return max(0, $proratedCents);
    }

    /**
     * Calculate weekly proration
     *
     * @param int $feeWeeklyCents Weekly fee in cents
     * @param string $startDate Start date (Y-m-d)
     * @param string $anchorDate Anchor date (Y-m-d)
     * @param string $method 'daily' or 'half_up' (default: 'daily')
     * @return int Prorated amount in cents
     */
    public function calculateWeeklyProration(int $feeWeeklyCents, string $startDate, string $anchorDate, string $method = 'daily'): int
    {
        $start = new \DateTime($startDate);
        $anchor = new \DateTime($anchorDate);
        
        // Days used = inclusive days from start_date to anchor_date-1
        $anchorMinusOne = clone $anchor;
        $anchorMinusOne->modify('-1 day');
        
        $daysUsed = $start->diff($anchorMinusOne)->days + 1; // +1 for inclusive
        if ($daysUsed < 0) {
            return 0;
        }
        
        // Days in a week
        $daysInWeek = 7;
        
        // Calculate proration
        $proratedCents = (int)round($feeWeeklyCents * ($daysUsed / $daysInWeek));
        
        return max(0, $proratedCents);
    }

    /**
     * Calculate proration based on term
     *
     * @param string $term 'weekly', 'monthly', or 'yearly'
     * @param int $feeCents Fee in cents
     * @param string $startDate Start date (Y-m-d)
     * @param string $anchorDate Anchor date (Y-m-d)
     * @param string $method 'daily' or 'half_up'
     * @return int Prorated amount in cents
     */
    public function calculateProration(string $term, int $feeCents, string $startDate, string $anchorDate, string $method = 'daily'): int
    {
        if ($term === 'weekly') {
            return $this->calculateWeeklyProration($feeCents, $startDate, $anchorDate, $method);
        } elseif ($term === 'monthly') {
            return $this->calculateMonthlyProration($feeCents, $startDate, $anchorDate, $method);
        } elseif ($term === 'yearly') {
            return $this->calculateYearlyProration($feeCents, $startDate, $anchorDate, $method);
        }
        
        return 0; // No proration for one_time
    }
}

