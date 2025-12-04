<?php

namespace App\Services\ReportCard;

use App\Models\ReportCardModel;
use App\Models\ReportCardVersionModel;
use App\Models\ReportCardEventModel;
use App\Services\ReportCard\AuditService;
use App\Services\ReportCard\PdfService;

class EmailService
{
    protected $reportCardModel;
    protected $versionModel;
    protected $eventModel;
    protected $auditService;
    protected $pdfService;
    protected $email;

    public function __construct()
    {
        $this->reportCardModel = new ReportCardModel();
        $this->versionModel = new ReportCardVersionModel();
        $this->eventModel = new ReportCardEventModel();
        $this->auditService = new AuditService();
        $this->pdfService = new PdfService();
        $this->email = \Config\Services::email();
    }

    /**
     * Send report card via email
     *
     * @param int $rcId Report card ID
     * @param array $recipients Array of email addresses (optional, will fetch from student/guardian)
     * @param int $actorId User sending the email
     * @param bool $includePdf Whether to attach PDF
     * @return array
     */
    public function sendEmail(int $rcId, array $recipients = [], int $actorId, bool $includePdf = true): array
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

        // Get recipients if not provided
        if (empty($recipients)) {
            $recipients = $this->getRecipients($reportCard['student_id'], $reportCard['school_id']);
        }

        if (empty($recipients)) {
            return [
                'IsSuccess' => false,
                'Message' => 'No recipients found',
            ];
        }

        $results = [
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        foreach ($recipients as $recipient) {
            // Check idempotency - has this recipient already received this report card?
            if ($this->eventModel->wasEmailSent($rcId, $recipient)) {
                $results['skipped']++;
                continue;
            }

            try {
                // Prepare email
                $subject = $this->buildSubject($payload);
                $body = $this->buildEmailBody($payload, $reportCard);

                $this->email->clear();
                $this->email->setTo($recipient);
                $this->email->setSubject($subject);
                $this->email->setMessage($body);

                // Attach PDF if requested
                if ($includePdf) {
                    $pdfResult = $this->pdfService->generate($rcId);
                    if ($pdfResult['IsSuccess']) {
                        $this->email->attach($pdfResult['Data']['path']);
                    }
                }

                // Send email
                if ($this->email->send()) {
                    $results['sent']++;
                    $this->auditService->logEmailSent($rcId, $recipient, $actorId, [
                        'subject' => $subject,
                        'provider' => 'CodeIgniter Email',
                    ]);
                } else {
                    $results['failed']++;
                    $error = $this->email->printDebugger(['headers']);
                    $results['errors'][] = "Failed to send to $recipient: $error";
                    $this->auditService->logEmailFailed($rcId, $recipient, $actorId, $error);
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Error sending to $recipient: " . $e->getMessage();
                $this->auditService->logEmailFailed($rcId, $recipient, $actorId, $e->getMessage());
            }
        }

        return [
            'IsSuccess' => true,
            'Data' => $results,
        ];
    }

    /**
     * Bulk send emails for multiple report cards
     *
     * @param array $rcIds Array of report card IDs
     * @param int $actorId
     * @param bool $includePdf
     * @return array
     */
    public function bulkSend(array $rcIds, int $actorId, bool $includePdf = true): array
    {
        $results = [
            'total' => count($rcIds),
            'processed' => 0,
            'sent' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($rcIds as $rcId) {
            $result = $this->sendEmail($rcId, [], $actorId, $includePdf);
            $results['processed']++;

            if ($result['IsSuccess']) {
                $results['sent'] += $result['Data']['sent'];
                $results['failed'] += $result['Data']['failed'];
                $results['errors'] = array_merge($results['errors'], $result['Data']['errors']);
            }
        }

        return [
            'IsSuccess' => true,
            'Data' => $results,
        ];
    }

    /**
     * Get recipients (student + guardians)
     */
    protected function getRecipients($studentId, $schoolId): array
    {
        $db = \Config\Database::connect();

        // Get student email
        $student = $db->table('user')
            ->select('email')
            ->where('id', $studentId)
            ->get()
            ->getRowArray();

        $recipients = [];
        if ($student && !empty($student['email'])) {
            $recipients[] = $student['email'];
        }

        // Get guardian emails (from user_profile_details or similar table)
        // TODO: Adjust based on actual guardian relationship table
        $guardians = $db->table('user_profile_details')
            ->select('parent_email')
            ->where('user_id', $studentId)
            ->get()
            ->getResultArray();

        foreach ($guardians as $guardian) {
            if (!empty($guardian['parent_email'])) {
                $recipients[] = $guardian['parent_email'];
            }
        }

        return array_unique(array_filter($recipients));
    }

    /**
     * Build email subject
     */
    protected function buildSubject(array $payload): string
    {
        $studentName = $payload['student']['name'] ?? 'Student';
        $term = $payload['term'] ?? '';
        $year = $payload['academic_year'] ?? '';

        return "Report Card for $studentName - $term $year";
    }

    /**
     * Build email body (HTML)
     */
    protected function buildEmailBody(array $payload, array $reportCard): string
    {
        $studentName = $payload['student']['name'] ?? 'Student';
        $term = $payload['term'] ?? '';
        $year = $payload['academic_year'] ?? '';
        $schoolName = $payload['school']['name'] ?? '';

        $html = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #f4f4f4; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .footer { background: #f4f4f4; padding: 10px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>$schoolName</h2>
                    <p>Report Card</p>
                </div>
                <div class='content'>
                    <p>Dear Parent/Guardian,</p>
                    <p>The report card for <strong>$studentName</strong> is now available for the <strong>$term $year</strong> term.</p>
                    <p>Please see the attached PDF document for the complete report card.</p>
                    <p>If you have any questions or concerns, please contact the school office.</p>
                    <p>Best regards,<br>$schoolName</p>
                </div>
                <div class='footer'>
                    <p>This is an automated message. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        return $html;
    }
}
