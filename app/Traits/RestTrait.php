<?php

namespace App\Traits;

use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\Authorization;

trait RestTrait
{
    /**
     * Standard JSON response format
     *
     * @param mixed $data
     * @param bool $success
     * @param string $message
     * @param int $statusCode
     * @return ResponseInterface
     */
    protected function restResponse($data = null, $success = true, $message = '', $statusCode = 200)
    {
        $response = [
            'IsSuccess' => $success,
            'ResponseObject' => $data,
            'ErrorObject' => $success ? '' : $message
        ];

        return $this->respond($response, $statusCode);
    }

    /**
     * Success response
     *
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @return ResponseInterface
     */
    protected function successResponse($data = null, $message = 'Success', $statusCode = 200)
    {
        return $this->restResponse($data, true, $message, $statusCode);
    }

    /**
     * Error response
     *
     * @param string $message
     * @param int $statusCode
     * @return ResponseInterface
     */
    protected function errorResponse($message = 'Error', $statusCode = 400)
    {
        return $this->restResponse(null, false, $message, $statusCode);
    }

    /**
     * Unauthorized response
     *
     * @param string $message
     * @return ResponseInterface
     */
    protected function unauthorizedResponse($message = 'Unauthorized')
    {
        return $this->restResponse(null, false, $message, 401);
    }

    /**
     * Validate required fields
     *
     * @param array $data
     * @param array $requiredFields
     * @return bool
     */
    protected function validateRequiredFields($data, $requiredFields)
    {
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get missing required fields
     *
     * @param array $data
     * @param array $requiredFields
     * @return array
     */
    protected function getMissingFields($data, $requiredFields)
    {
        $missing = [];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missing[] = $field;
            }
        }
        return $missing;
    }

    /**
     * Validate JWT token from request
     *
     * @return object|false
     */
    protected function validateToken()
    {
        $token = $this->request->getHeaderLine('Accesstoken');
        
        if (empty($token)) {
            return false;
        }

        return Authorization::validateToken($token);
    }

    /**
     * Check if user is admin
     *
     * @param object $tokenPayload
     * @return bool
     */
    protected function isAdmin($tokenPayload)
    {
        return Authorization::isAdmin($tokenPayload);
    }

    /**
     * Get user ID from token
     *
     * @param object $tokenPayload
     * @return int|null
     */
    protected function getUserId($tokenPayload)
    {
        return Authorization::getUserId($tokenPayload);
    }

    /**
     * Get school ID from token
     *
     * @param object $tokenPayload
     * @return int|null
     */
    protected function getSchoolId($tokenPayload)
    {
        return Authorization::getSchoolId($tokenPayload);
    }

    /**
     * Handle CORS preflight requests
     *
     * @return ResponseInterface
     */
    protected function handleOptions()
    {
        return $this->respond(['status' => 'ok'], 200);
    }

    /**
     * Log API request
     *
     * @param string $endpoint
     * @param array $data
     * @param string $type
     */
    protected function logRequest($endpoint, $data = [], $type = 'request')
    {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'endpoint' => $endpoint,
            'type' => $type,
            'data' => $data,
            'ip' => $this->request->getIPAddress(),
            'user_agent' => $this->request->getUserAgent()
        ];

        log_message('info', '[API_LOG] ' . json_encode($logData));
    }

    /**
     * Sanitize input data
     *
     * @param array $data
     * @return array
     */
    protected function sanitizeInput($data)
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = trim(strip_tags($value));
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }

    /**
     * Generate pagination data
     *
     * @param int $page
     * @param int $limit
     * @param int $total
     * @return array
     */
    protected function getPaginationData($page, $limit, $total)
    {
        $totalPages = ceil($total / $limit);
        
        return [
            'current_page' => $page,
            'per_page' => $limit,
            'total' => $total,
            'total_pages' => $totalPages,
            'has_next' => $page < $totalPages,
            'has_prev' => $page > 1
        ];
    }
}
