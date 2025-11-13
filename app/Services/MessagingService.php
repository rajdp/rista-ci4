<?php

namespace App\Services;

use CodeIgniter\Config\BaseService;
use Config\Database;
use Config\Email;

/**
 * Messaging Service
 * Handles templated messaging (email, SMS, WhatsApp) with consent and quiet hours
 */
class MessagingService extends BaseService
{
    protected $db;
    protected $email;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->email = \Config\Services::email();
    }

    /**
     * Send a templated message
     * 
     * @param int $schoolId School ID
     * @param string $channel 'email', 'sms', or 'whatsapp'
     * @param string $purpose Template purpose key
     * @param string $to Recipient email/phone
     * @param array $vars Template variables
     * @param int|null $parentId Optional parent ID
     * @param int|null $studentId Optional student ID
     * @return bool Success
     */
    public function sendTemplate(int $schoolId, string $channel, string $purpose, 
                                 string $to, array $vars = [], ?int $parentId = null, 
                                 ?int $studentId = null): bool
    {
        try {
            // Check consent and quiet hours
            if (!$this->checkConsent($schoolId, $to, $channel)) {
                log_message('info', "Messaging: Consent check failed for {$to}");
                return false;
            }

            if (!$this->checkQuietHours($schoolId)) {
                log_message('info', "Messaging: Quiet hours active for school {$schoolId}");
                // Queue for later instead of failing
                return $this->queueMessage($schoolId, $channel, $purpose, $to, $vars, $parentId, $studentId);
            }

            // Get template
            $template = $this->getTemplate($schoolId, $channel, $purpose);
            if (!$template) {
                log_message('error', "Messaging: Template not found: {$schoolId}/{$channel}/{$purpose}");
                return false;
            }

            // Render template
            $subject = $this->renderTemplate($template['subject'] ?? '', $vars);
            $body = $this->renderTemplate($template['body'], $vars);

            // Send message
            $success = false;
            $providerId = null;

            if ($channel === 'email') {
                $success = $this->sendEmail($to, $subject, $body);
            } elseif ($channel === 'sms') {
                $success = $this->sendSMS($to, $body);
            } elseif ($channel === 'whatsapp') {
                $success = $this->sendWhatsApp($to, $body);
            }

            // Log message
            $this->logMessage($schoolId, $channel, $parentId, $studentId, 
                             $template['template_id'], $body, $success ? 'sent' : 'failed', 
                             $providerId);

            return $success;
        } catch (\Throwable $e) {
            log_message('error', 'MessagingService error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get message template
     */
    private function getTemplate(int $schoolId, string $channel, string $purpose): ?array
    {
        $row = $this->db->table('t_message_template')
            ->where('school_id', $schoolId)
            ->where('channel', $channel)
            ->where('purpose', $purpose)
            ->where('locale', 'en') // Default to English for now
            ->orderBy('version', 'DESC')
            ->get(1)
            ->getRowArray();

        return $row ?: null;
    }

    /**
     * Render template with variables
     */
    private function renderTemplate(string $template, array $vars): string
    {
        $rendered = $template;
        foreach ($vars as $key => $value) {
            $rendered = str_replace('{{' . $key . '}}', $value, $rendered);
            $rendered = str_replace('{' . $key . '}', $value, $rendered);
        }
        return $rendered;
    }

    /**
     * Send email
     */
    private function sendEmail(string $to, string $subject, string $body): bool
    {
        try {
            $this->email->setTo($to);
            $this->email->setSubject($subject);
            $this->email->setMessage($body);
            
            return $this->email->send();
        } catch (\Throwable $e) {
            log_message('error', 'Email send error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send SMS
     */
    private function sendSMS(string $to, string $body): bool
    {
        // TODO: Integrate with SMS provider (Twilio, etc.)
        log_message('info', "SMS would be sent to {$to}: {$body}");
        return true; // Placeholder
    }

    /**
     * Send WhatsApp
     */
    private function sendWhatsApp(string $to, string $body): bool
    {
        // TODO: Integrate with WhatsApp Business API
        log_message('info', "WhatsApp would be sent to {$to}: {$body}");
        return true; // Placeholder
    }

    /**
     * Check consent for messaging
     */
    private function checkConsent(int $schoolId, string $to, string $channel): bool
    {
        // TODO: Check consent preferences from parent/student records
        // For now, default to true
        return true;
    }

    /**
     * Check if current time is within quiet hours
     */
    private function checkQuietHours(int $schoolId): bool
    {
        // Get quiet hours from feature flags
        $quietHours = $this->getFeatureFlag($schoolId, 'messaging.quiet_hours');
        
        if (!$quietHours) {
            return true; // No quiet hours configured
        }

        $config = json_decode($quietHours, true);
        if (!is_array($config)) {
            return true;
        }

        $startHour = $config['start'] ?? 22; // Default 10 PM
        $endHour = $config['end'] ?? 8; // Default 8 AM

        $currentHour = (int)date('G');

        // Handle overnight quiet hours (e.g., 10 PM - 8 AM)
        if ($startHour > $endHour) {
            return !($currentHour >= $startHour || $currentHour < $endHour);
        } else {
            return !($currentHour >= $startHour && $currentHour < $endHour);
        }
    }

    /**
     * Queue message for later delivery
     */
    private function queueMessage(int $schoolId, string $channel, string $purpose, 
                                 string $to, array $vars, ?int $parentId, ?int $studentId): bool
    {
        // For now, just log. In production, could use a queue system
        log_message('info', "Message queued for later: {$schoolId}/{$channel}/{$purpose} to {$to}");
        return true;
    }

    /**
     * Log message to t_message_log
     */
    private function logMessage(int $schoolId, string $channel, ?int $parentId, ?int $studentId,
                               ?int $templateId, string $renderedBody, string $status, 
                               ?string $providerId = null): void
    {
        $this->db->table('t_message_log')->insert([
            'school_id' => $schoolId,
            'channel' => $channel,
            'to_parent_id' => $parentId,
            'to_student_id' => $studentId,
            'template_id' => $templateId,
            'rendered_body' => $renderedBody,
            'status' => $status,
            'provider_id' => $providerId,
            'sent_at' => $status === 'sent' ? date('Y-m-d H:i:s') : null,
        ]);
    }

    /**
     * Get feature flag value
     */
    private function getFeatureFlag(int $schoolId, string $flagKey): ?string
    {
        $row = $this->db->table('t_feature_flag')
            ->where('school_id', $schoolId)
            ->where('flag_key', $flagKey)
            ->get(1)
            ->getRowArray();

        return $row['flag_value'] ?? null;
    }
}

