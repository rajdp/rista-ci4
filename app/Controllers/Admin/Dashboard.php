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

            $schoolId = $this->getSchoolId($token);
            if (!$schoolId) {
                return $this->errorResponse('School ID required');
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
}

