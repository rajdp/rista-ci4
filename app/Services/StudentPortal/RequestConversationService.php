<?php

namespace App\Services\StudentPortal;

use App\Models\RequestConversationModel;
use App\Models\StudentPortalAuditModel;

class RequestConversationService
{
    protected $conversationModel;
    protected $auditModel;

    public function __construct()
    {
        $this->conversationModel = new RequestConversationModel();
        $this->auditModel = new StudentPortalAuditModel();
    }

    /**
     * Add message to conversation
     *
     * @param int $schoolId
     * @param string $requestType ['profile_change', 'absence', 'special_request', 'document']
     * @param int $requestId
     * @param int $authorId
     * @param int $authorRoleId
     * @param string $message
     * @param bool $isInternal (admin-only notes)
     * @return array
     */
    public function addMessage(
        int $schoolId,
        string $requestType,
        int $requestId,
        int $authorId,
        int $authorRoleId,
        string $message,
        bool $isInternal = false
    ): array {
        try {
            // Validate request type
            $validTypes = ['profile_change', 'absence', 'special_request', 'document'];
            if (!in_array($requestType, $validTypes)) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'Invalid request type',
                ];
            }

            // Validate message is not empty
            if (empty(trim($message))) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'Message cannot be empty',
                ];
            }

            // Students cannot create internal notes
            if ($authorRoleId == 5 && $isInternal) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'Students cannot create internal notes',
                ];
            }

            $conversationId = $this->conversationModel->addMessage(
                $schoolId,
                $requestType,
                $requestId,
                $authorId,
                $authorRoleId,
                $message,
                $isInternal
            );

            if (!$conversationId) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'Failed to add message',
                ];
            }

            // Log the action
            $this->auditModel->logAction(
                $schoolId,
                $requestType,
                $requestId,
                'add_message',
                $authorId,
                $authorRoleId,
                ['is_internal' => $isInternal],
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            );

            log_message('info', "RequestConversationService: Added message {$conversationId} to {$requestType} {$requestId}");

            return [
                'success' => true,
                'data' => ['conversation_id' => $conversationId],
                'error' => null,
            ];
        } catch (\Exception $e) {
            log_message('error', 'RequestConversationService::addMessage - ' . $e->getMessage());
            return [
                'success' => false,
                'data' => null,
                'error' => 'An error occurred while adding the message',
            ];
        }
    }

    /**
     * Get conversation thread
     *
     * @param string $requestType
     * @param int $requestId
     * @param int $userId
     * @param int $roleId
     * @return array
     */
    public function getConversation(string $requestType, int $requestId, int $userId, int $roleId): array
    {
        try {
            // Validate request type
            $validTypes = ['profile_change', 'absence', 'special_request', 'document'];
            if (!in_array($requestType, $validTypes)) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'Invalid request type',
                ];
            }

            // Students should not see internal notes
            $includeInternal = ($roleId == 2 || $roleId == 7); // Admin or Registrar

            $messages = $this->conversationModel->getConversation($requestType, $requestId, $includeInternal);

            return [
                'success' => true,
                'data' => $messages,
                'error' => null,
            ];
        } catch (\Exception $e) {
            log_message('error', 'RequestConversationService::getConversation - ' . $e->getMessage());
            return [
                'success' => false,
                'data' => null,
                'error' => 'An error occurred while retrieving the conversation',
            ];
        }
    }

    /**
     * Count messages in conversation
     *
     * @param string $requestType
     * @param int $requestId
     * @param bool $includeInternal
     * @return array
     */
    public function countMessages(string $requestType, int $requestId, bool $includeInternal = false): array
    {
        try {
            $count = $this->conversationModel->countMessages($requestType, $requestId, $includeInternal);

            return [
                'success' => true,
                'data' => ['count' => $count],
                'error' => null,
            ];
        } catch (\Exception $e) {
            log_message('error', 'RequestConversationService::countMessages - ' . $e->getMessage());
            return [
                'success' => false,
                'data' => null,
                'error' => 'An error occurred while counting messages',
            ];
        }
    }
}
