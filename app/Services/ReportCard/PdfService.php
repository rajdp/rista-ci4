<?php

namespace App\Services\ReportCard;

use App\Models\ReportCardModel;
use App\Models\ReportCardVersionModel;

class PdfService
{
    protected $reportCardModel;
    protected $versionModel;

    public function __construct()
    {
        $this->reportCardModel = new ReportCardModel();
        $this->versionModel = new ReportCardVersionModel();
    }

    /**
     * Generate PDF for a report card
     *
     * @param int $rcId Report card ID
     * @return array Result with path or signed URL
     */
    public function generate(int $rcId): array
    {
        // Get report card
        $reportCard = $this->reportCardModel->find($rcId);
        if (!$reportCard) {
            return [
                'IsSuccess' => false,
                'Message' => 'Report card not found',
            ];
        }

        // Get latest version
        $version = $this->versionModel->getLatestVersion($rcId);
        if (!$version) {
            return [
                'IsSuccess' => false,
                'Message' => 'Report card version not found',
            ];
        }

        $payload = json_decode($version['payload_json'], true);

        // Generate HTML
        $html = $this->generateHtml($payload, $reportCard);

        // Generate PDF filename
        $studentId = $reportCard['student_id'];
        $term = str_replace(' ', '_', $reportCard['term']);
        $year = str_replace(' ', '_', $reportCard['academic_year']);
        $filename = "report_card_{$studentId}_{$term}_{$year}.pdf";

        // PDF storage path
        $storagePath = WRITEPATH . 'uploads/report_cards/';
        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        $pdfPath = $storagePath . $filename;

        try {
            // Generate PDF using available library
            // Option 1: Use dompdf (if available)
            if (class_exists('Dompdf\Dompdf')) {
                $this->generateWithDompdf($html, $pdfPath);
            }
            // Option 2: Use existing SimplePdfGenerator (if available)
            elseif (class_exists('App\Libraries\SimplePdfGenerator')) {
                $this->generateWithSimplePdf($html, $pdfPath);
            }
            // Option 3: Fallback - save HTML (for development)
            else {
                file_put_contents($pdfPath, $html);
                log_message('warning', 'No PDF library available, saving as HTML');
            }

            // Generate signed URL
            $signedUrl = $this->generateSignedUrl($rcId, $filename);

            return [
                'IsSuccess' => true,
                'Data' => [
                    'path' => $pdfPath,
                    'filename' => $filename,
                    'signed_url' => $signedUrl,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'IsSuccess' => false,
                'Message' => 'PDF generation failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Generate signed download URL
     *
     * @param int $rcId
     * @param string $filename
     * @param int $expiryMinutes
     * @return string
     */
    public function generateSignedUrl(int $rcId, string $filename, int $expiryMinutes = 15): string
    {
        $expires = time() + ($expiryMinutes * 60);
        $secret = getenv('encryption.key') ?? 'default-secret-key';

        $signature = hash_hmac('sha256', $rcId . $filename . $expires, $secret);

        return site_url("reportcard/download?rc_id=$rcId&expires=$expires&signature=$signature");
    }

    /**
     * Verify signed URL
     *
     * @param int $rcId
     * @param string $filename
     * @param int $expires
     * @param string $signature
     * @return bool
     */
    public function verifySignedUrl(int $rcId, string $filename, int $expires, string $signature): bool
    {
        // Check expiry
        if (time() > $expires) {
            return false;
        }

        $secret = getenv('encryption.key') ?? 'default-secret-key';
        $expectedSignature = hash_hmac('sha256', $rcId . $filename . $expires, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Generate HTML for PDF
     */
    protected function generateHtml(array $payload, array $reportCard): string
    {
        // Load view with payload data
        return view('report_cards/template', [
            'payload' => $payload,
            'reportCard' => $reportCard,
        ]);
    }

    /**
     * Generate PDF using Dompdf
     */
    protected function generateWithDompdf(string $html, string $outputPath): void
    {
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        file_put_contents($outputPath, $dompdf->output());
    }

    /**
     * Generate PDF using SimplePdfGenerator
     */
    protected function generateWithSimplePdf(string $html, string $outputPath): void
    {
        $pdfGenerator = new \App\Libraries\SimplePdfGenerator();
        $pdfGenerator->generate($html, $outputPath);
    }
}
