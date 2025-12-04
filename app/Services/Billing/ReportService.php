<?php

namespace App\Services\Billing;

use App\Models\BillingInvoiceModel;
use App\Models\PaymentTxnModel;
use App\Models\SubscriptionModel;

class ReportService
{
    protected $invoiceModel;
    protected $paymentModel;
    protected $subscriptionModel;

    public function __construct()
    {
        $this->invoiceModel = new BillingInvoiceModel();
        $this->paymentModel = new PaymentTxnModel();
        $this->subscriptionModel = new SubscriptionModel();
    }

    /**
     * Get pending payments report
     */
    public function getPendingPaymentsReport(int $schoolId, array $filters = []): array
    {
        try {
            $invoices = $this->invoiceModel->getBySchool($schoolId, array_merge($filters, [
                'status' => ['open', 'past_due']
            ]));

            // Calculate totals
            $totalDue = 0;
            $totalOverdue = 0;
            $today = date('Y-m-d');

            foreach ($invoices as &$invoice) {
                $totalDue += $invoice['balance_cents'];

                if ($invoice['due_date'] < $today) {
                    $totalOverdue += $invoice['balance_cents'];
                    $invoice['days_overdue'] = floor((strtotime($today) - strtotime($invoice['due_date'])) / 86400);
                } else {
                    $invoice['days_overdue'] = 0;
                }
            }

            return [
                'success' => true,
                'data' => [
                    'invoices' => $invoices,
                    'totals' => [
                        'total_due_cents' => $totalDue,
                        'total_overdue_cents' => $totalOverdue,
                        'count' => count($invoices),
                    ]
                ],
                'error' => null
            ];

        } catch (\Exception $e) {
            log_message('error', 'ReportService::getPendingPaymentsReport error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage(), 'data' => null];
        }
    }

    /**
     * Get aging report
     */
    public function getAgingReport(int $schoolId, ?string $asOfDate = null): array
    {
        try {
            $asOf = $asOfDate ?? date('Y-m-d');
            $invoices = $this->invoiceModel->getOverdue($schoolId, $asOf);

            // Bucket invoices by age
            $buckets = [
                '0-30' => ['count' => 0, 'amount_cents' => 0, 'invoices' => []],
                '31-60' => ['count' => 0, 'amount_cents' => 0, 'invoices' => []],
                '61-90' => ['count' => 0, 'amount_cents' => 0, 'invoices' => []],
                '90+' => ['count' => 0, 'amount_cents' => 0, 'invoices' => []],
            ];

            foreach ($invoices as $invoice) {
                $daysOverdue = floor((strtotime($asOf) - strtotime($invoice['due_date'])) / 86400);

                if ($daysOverdue <= 30) {
                    $bucket = '0-30';
                } elseif ($daysOverdue <= 60) {
                    $bucket = '31-60';
                } elseif ($daysOverdue <= 90) {
                    $bucket = '61-90';
                } else {
                    $bucket = '90+';
                }

                $buckets[$bucket]['count']++;
                $buckets[$bucket]['amount_cents'] += $invoice['balance_cents'];
                $buckets[$bucket]['invoices'][] = $invoice;
            }

            return ['success' => true, 'data' => ['buckets' => $buckets, 'as_of_date' => $asOf], 'error' => null];

        } catch (\Exception $e) {
            log_message('error', 'ReportService::getAgingReport error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage(), 'data' => null];
        }
    }

    /**
     * Get KPI metrics
     */
    public function getKPIs(int $schoolId, string $fromDate, string $toDate): array
    {
        try {
            // Monthly Recurring Revenue (MRR)
            $activeSubscriptions = $this->subscriptionModel->where('school_id', $schoolId)
                ->where('status', 'active')
                ->where('term', 'monthly')
                ->findAll();

            $mrr = 0;
            foreach ($activeSubscriptions as $sub) {
                $mrr += $sub['amount_cents'];
            }

            // Payment stats
            $paymentStats = $this->paymentModel->getStats($schoolId, $fromDate, $toDate);

            // Invoice stats
            $invoiceStats = $this->invoiceModel->getStats($schoolId);

            // Days Sales Outstanding (DSO)
            $totalAR = $invoiceStats['total_outstanding_cents'];
            $avgDailySales = $paymentStats['total_collected_cents'] / max(1, (strtotime($toDate) - strtotime($fromDate)) / 86400);
            $dso = $avgDailySales > 0 ? round($totalAR / $avgDailySales, 2) : 0;

            return [
                'success' => true,
                'data' => [
                    'mrr_cents' => $mrr,
                    'arr_cents' => $mrr * 12,
                    'dso' => $dso,
                    'success_rate' => $paymentStats['success_rate'],
                    'on_time_rate' => 0, // Would need additional logic
                    'total_collected_cents' => $paymentStats['total_collected_cents'],
                    'total_outstanding_cents' => $totalAR,
                    'failed_payments_count' => $paymentStats['failed_count'],
                ],
                'error' => null
            ];

        } catch (\Exception $e) {
            log_message('error', 'ReportService::getKPIs error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage(), 'data' => null];
        }
    }

    /**
     * Export report to CSV
     */
    public function exportToCSV(array $data, string $filename = 'report.csv'): string
    {
        try {
            $csv = fopen('php://temp', 'r+');

            if (!empty($data)) {
                // Write headers
                fputcsv($csv, array_keys($data[0]));

                // Write data
                foreach ($data as $row) {
                    fputcsv($csv, $row);
                }
            }

            rewind($csv);
            $output = stream_get_contents($csv);
            fclose($csv);

            return $output;

        } catch (\Exception $e) {
            log_message('error', 'ReportService::exportToCSV error: ' . $e->getMessage());
            return '';
        }
    }
}
