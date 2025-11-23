<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Traits\RestTrait;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Database;

class Dashboard extends BaseController
{
    use RestTrait;

    public function getDashboard(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? $this->request->getGet() ?? []);
            
            $token = $this->validateToken();
            if (!$token) {
                return $this->unauthorizedResponse('Access token required');
            }

            // Get school_id from token, payload, or user profile
            $schoolId = $this->getSchoolId($token);
            
            // Fallback 1: Check if school_id is in request payload
            if (!$schoolId && isset($payload['school_id'])) {
                $schoolId = (int) $payload['school_id'];
            }
            
            // Fallback 2: Get from user profile if available
            if (!$schoolId) {
                $userId = $this->getUserId($token);
                if ($userId) {
                    $db = Database::connect();
                    $user = $db->table('user')
                        ->select('school_id')
                        ->where('user_id', $userId)
                        ->get()
                        ->getRowArray();
                    
                    if ($user && !empty($user['school_id'])) {
                        $schoolId = (int) $user['school_id'];
                    }
                }
            }
            
            if (!$schoolId) {
                return $this->errorResponse('School ID required. Please provide school_id in request or ensure your account is associated with a school.');
            }

            $fromDate = $payload['from'] ?? date('Y-m-d', strtotime('-30 days'));
            $toDate = $payload['to'] ?? date('Y-m-d');

            $db = Database::connect();
            
            // Check if tables exist (for graceful degradation during initial setup)
            $tablesExist = $this->checkTablesExist($db);
            if (!$tablesExist) {
                // Return empty data structure if tables don't exist yet
                return $this->successResponse([
                    'tiles' => $this->getEmptyTiles(),
                    'revenue' => [
                        'mrr_cents' => 0,
                        'arr_cents' => 0,
                        'overdue_cents' => 0,
                    ],
                    'charts' => $this->getEmptyChartData($fromDate, $toDate),
                    'period' => [
                        'from' => $fromDate,
                        'to' => $toDate,
                    ],
                ], 'Dashboard data retrieved (tables not initialized)');
            }

            // Get leads count
            $leads = $db->table('student_self_registrations')
                ->where('school_id', $schoolId)
                ->where('submitted_at >=', $fromDate)
                ->where('submitted_at <=', $toDate . ' 23:59:59')
                ->countAllResults(false);

            // Get enrollments count
            $enrollments = $db->table('student_self_registrations')
                ->where('school_id', $schoolId)
                ->where('status', 'converted')
                ->where('converted_at >=', $fromDate)
                ->where('converted_at <=', $toDate . ' 23:59:59')
                ->countAllResults(false);

            // Calculate conversion rate
            $conversionRate = $leads > 0 ? ($enrollments / $leads) * 100 : 0;

            // Get median days to enroll (from KPI sink or calculate)
            $medianDays = $this->calculateMedianDaysToEnroll($schoolId, $fromDate, $toDate);

            // Get revenue metrics from KPI sinks
            $revenueData = $db->table('t_revenue_daily')
                ->where('school_id', $schoolId)
                ->where('day >=', $fromDate)
                ->where('day <=', $toDate)
                ->selectSum('mrr_cents', 'total_mrr')
                ->selectSum('arr_cents', 'total_arr')
                ->selectAvg('on_time_pay_pct', 'avg_on_time')
                ->selectSum('ar_overdue_cents', 'total_overdue')
                ->get()
                ->getRowArray();

            // Get marketing KPIs
            $marketingData = $db->table('t_marketing_kpi_daily')
                ->where('school_id', $schoolId)
                ->where('day >=', $fromDate)
                ->where('day <=', $toDate)
                ->selectSum('leads', 'total_leads')
                ->selectSum('enrollments', 'total_enrollments')
                ->selectSum('revenue_cents', 'total_revenue')
                ->get()
                ->getRowArray();

            // Get teacher/room utilization (placeholder - would need actual session data)
            $utilization = $this->calculateUtilization($schoolId, $fromDate, $toDate);

            // Get attendance/no-show percentage
            $attendance = $this->calculateAttendance($schoolId, $fromDate, $toDate);

            // Get portal MAU (Monthly Active Users)
            $mau = $this->calculateMAU($schoolId);

            // Get messaging metrics
            $messaging = $this->getMessagingMetrics($schoolId, $fromDate, $toDate);

            // Get docs/consents coverage
            $docsCoverage = $this->calculateDocsCoverage($schoolId);

            // Get time-series data for charts
            $chartData = $this->getChartData($schoolId, $fromDate, $toDate);

            return $this->successResponse([
                'tiles' => [
                    'leads' => (int) ($marketingData['total_leads'] ?? $leads),
                    'enrollments' => (int) ($marketingData['total_enrollments'] ?? $enrollments),
                    'conversion_rate' => round($conversionRate, 2),
                    'median_days_to_enroll' => $medianDays,
                    'on_time_pay_pct' => round((float) ($revenueData['avg_on_time'] ?? 0), 2),
                    'dso' => $this->calculateDSO($schoolId),
                    'teacher_utilization' => round($utilization['teacher'], 2),
                    'room_utilization' => round($utilization['room'], 2),
                    'attendance_pct' => round($attendance['attendance'], 2),
                    'no_show_pct' => round($attendance['no_show'], 2),
                    'portal_mau' => $mau,
                    'messaging_open_rate' => round($messaging['open_rate'], 2),
                    'messaging_click_rate' => round($messaging['click_rate'], 2),
                    'docs_coverage' => round($docsCoverage, 2),
                ],
                'revenue' => [
                    'mrr_cents' => (int) ($revenueData['total_mrr'] ?? 0),
                    'arr_cents' => (int) ($revenueData['total_arr'] ?? 0),
                    'overdue_cents' => (int) ($revenueData['total_overdue'] ?? 0),
                ],
                'charts' => $chartData,
                'period' => [
                    'from' => $fromDate,
                    'to' => $toDate,
                ],
            ], 'Dashboard data retrieved');
        } catch (\Throwable $e) {
            log_message('error', 'Dashboard::getDashboard - ' . $e->getMessage());
            log_message('error', 'Dashboard::getDashboard - Stack trace: ' . $e->getTraceAsString());
            return $this->errorResponse('Unable to load dashboard data: ' . $e->getMessage());
        }
    }

    private function calculateMedianDaysToEnroll(int $schoolId, string $fromDate, string $toDate): float
    {
        $db = Database::connect();
        
        $rows = $db->query("
            SELECT DATEDIFF(converted_at, submitted_at) AS days_to_enroll
            FROM student_self_registrations
            WHERE school_id = ? 
            AND status = 'converted'
            AND converted_at >= ? 
            AND converted_at <= ?
            AND converted_at IS NOT NULL
            AND submitted_at IS NOT NULL
            ORDER BY days_to_enroll
        ", [$schoolId, $fromDate, $toDate . ' 23:59:59'])->getResultArray();

        if (empty($rows)) {
            return 0;
        }

        $days = array_column($rows, 'days_to_enroll');
        $count = count($days);
        $middle = floor(($count - 1) / 2);

        if ($count % 2) {
            return (float) $days[$middle];
        } else {
            return (float) (($days[$middle] + $days[$middle + 1]) / 2);
        }
    }

    private function calculateUtilization(int $schoolId, string $fromDate, string $toDate): array
    {
        // Placeholder - would need actual session/availability data
        return ['teacher' => 0, 'room' => 0];
    }

    private function calculateAttendance(int $schoolId, string $fromDate, string $toDate): array
    {
        // Placeholder - would need actual attendance data
        return ['attendance' => 0, 'no_show' => 0];
    }

    private function calculateMAU(int $schoolId): int
    {
        // Placeholder - would need portal login tracking
        return 0;
    }

    private function getMessagingMetrics(int $schoolId, string $fromDate, string $toDate): array
    {
        $db = Database::connect();
        
        $total = $db->table('t_message_log')
            ->where('school_id', $schoolId)
            ->where('sent_at >=', $fromDate)
            ->where('sent_at <=', $toDate . ' 23:59:59')
            ->where('status', 'sent')
            ->countAllResults(false);

        $opened = $db->table('t_message_log')
            ->where('school_id', $schoolId)
            ->where('sent_at >=', $fromDate)
            ->where('sent_at <=', $toDate . ' 23:59:59')
            ->where('status', 'opened')
            ->countAllResults(false);

        $clicked = $db->table('t_message_log')
            ->where('school_id', $schoolId)
            ->where('sent_at >=', $fromDate)
            ->where('sent_at <=', $toDate . ' 23:59:59')
            ->where('status', 'clicked')
            ->countAllResults(false);

        return [
            'open_rate' => $total > 0 ? ($opened / $total) * 100 : 0,
            'click_rate' => $total > 0 ? ($clicked / $total) * 100 : 0,
        ];
    }

    private function calculateDocsCoverage(int $schoolId): float
    {
        // Placeholder - would need document tracking
        return 0;
    }

    private function calculateDSO(int $schoolId): float
    {
        // Days Sales Outstanding - placeholder
        return 0;
    }

    /**
     * Check if required tables exist
     */
    private function checkTablesExist($db): bool
    {
        try {
            $tables = ['student_self_registrations', 't_revenue_daily', 't_marketing_kpi_daily'];
            foreach ($tables as $table) {
                if (!$db->tableExists($table)) {
                    log_message('info', "Dashboard: Table {$table} does not exist");
                    return false;
                }
            }
            return true;
        } catch (\Throwable $e) {
            log_message('error', 'Dashboard: Error checking tables - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get empty tiles structure
     */
    private function getEmptyTiles(): array
    {
        return [
            'leads' => 0,
            'enrollments' => 0,
            'conversion_rate' => 0,
            'median_days_to_enroll' => 0,
            'on_time_pay_pct' => 0,
            'dso' => 0,
            'teacher_utilization' => 0,
            'room_utilization' => 0,
            'attendance_pct' => 0,
            'no_show_pct' => 0,
            'portal_mau' => 0,
            'messaging_open_rate' => 0,
            'messaging_click_rate' => 0,
            'docs_coverage' => 0,
        ];
    }

    /**
     * Get empty chart data structure
     */
    private function getEmptyChartData(string $fromDate, string $toDate): array
    {
        // Generate empty date range
        $dates = [];
        $start = new \DateTime($fromDate);
        $end = new \DateTime($toDate);
        $interval = new \DateInterval('P1D');
        $dateRange = new \DatePeriod($start, $interval, $end->modify('+1 day'));

        foreach ($dateRange as $date) {
            $dates[] = $date->format('M d');
        }

        return [
            'leads_enrollments' => [
                'dates' => $dates,
                'leads' => array_fill(0, count($dates), 0),
                'enrollments' => array_fill(0, count($dates), 0),
            ],
            'revenue' => [
                'dates' => $dates,
                'revenue' => array_fill(0, count($dates), 0),
                'mrr' => array_fill(0, count($dates), 0),
                'arr' => array_fill(0, count($dates), 0),
            ],
            'conversion_rate' => [
                'dates' => $dates,
                'rates' => array_fill(0, count($dates), 0),
            ],
        ];
    }

    /**
     * Get time-series data for charts
     */
    private function getChartData(int $schoolId, string $fromDate, string $toDate): array
    {
        $db = Database::connect();
        
        // Get daily leads and enrollments
        $dailyData = $db->table('t_marketing_kpi_daily')
            ->where('school_id', $schoolId)
            ->where('day >=', $fromDate)
            ->where('day <=', $toDate)
            ->select('day, SUM(leads) as leads, SUM(enrollments) as enrollments, SUM(revenue_cents) as revenue_cents')
            ->groupBy('day')
            ->orderBy('day', 'ASC')
            ->get()
            ->getResultArray();

        // Get daily revenue
        $dailyRevenue = $db->table('t_revenue_daily')
            ->where('school_id', $schoolId)
            ->where('day >=', $fromDate)
            ->where('day <=', $toDate)
            ->select('day, mrr_cents, arr_cents')
            ->orderBy('day', 'ASC')
            ->get()
            ->getResultArray();

        // Build chart data arrays
        $dates = [];
        $leadsData = [];
        $enrollmentsData = [];
        $revenueData = [];
        $mrrData = [];
        $arrData = [];
        $conversionRateData = [];

        // Create a map of dates for quick lookup
        $dailyMap = [];
        foreach ($dailyData as $row) {
            $day = $row['day'];
            $dailyMap[$day] = [
                'leads' => (int) ($row['leads'] ?? 0),
                'enrollments' => (int) ($row['enrollments'] ?? 0),
                'revenue' => (int) ($row['revenue_cents'] ?? 0),
            ];
        }

        $revenueMap = [];
        foreach ($dailyRevenue as $row) {
            $day = $row['day'];
            $revenueMap[$day] = [
                'mrr' => (int) ($row['mrr_cents'] ?? 0),
                'arr' => (int) ($row['arr_cents'] ?? 0),
            ];
        }

        // Generate date range and populate data
        $start = new \DateTime($fromDate);
        $end = new \DateTime($toDate);
        $interval = new \DateInterval('P1D');
        $dateRange = new \DatePeriod($start, $interval, $end->modify('+1 day'));

        foreach ($dateRange as $date) {
            $dayStr = $date->format('Y-m-d');
            $dayLabel = $date->format('M d');
            
            $dates[] = $dayLabel;
            $daily = $dailyMap[$dayStr] ?? ['leads' => 0, 'enrollments' => 0, 'revenue' => 0];
            $revenue = $revenueMap[$dayStr] ?? ['mrr' => 0, 'arr' => 0];
            
            $leadsData[] = $daily['leads'];
            $enrollmentsData[] = $daily['enrollments'];
            $revenueData[] = round($daily['revenue'] / 100, 2); // Convert cents to dollars
            $mrrData[] = round($revenue['mrr'] / 100, 2);
            $arrData[] = round($revenue['arr'] / 100, 2);
            
            // Calculate conversion rate for this day
            $convRate = $daily['leads'] > 0 ? ($daily['enrollments'] / $daily['leads']) * 100 : 0;
            $conversionRateData[] = round($convRate, 2);
        }

        return [
            'leads_enrollments' => [
                'dates' => $dates,
                'leads' => $leadsData,
                'enrollments' => $enrollmentsData,
            ],
            'revenue' => [
                'dates' => $dates,
                'revenue' => $revenueData,
                'mrr' => $mrrData,
                'arr' => $arrData,
            ],
            'conversion_rate' => [
                'dates' => $dates,
                'rates' => $conversionRateData,
            ],
        ];
    }
}

