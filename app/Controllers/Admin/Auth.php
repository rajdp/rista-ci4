<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Traits\RestTrait;
use App\Libraries\Authorization;
use CodeIgniter\HTTP\ResponseInterface;

class Auth extends BaseController
{
    use RestTrait;

    /**
     * Handle GET/POST requests for admin authentication tokens.
     */
    public function token(): ResponseInterface
    {
        $method = strtolower($this->request->getMethod());

        if ($method === 'options') {
            return $this->handleOptions();
        }

        if ($method === 'get') {
            // Generate a test token for development
            $tokenPayload = [
                'id' => 1,
                'role' => 'admin',
                'school_id' => 1,
                'timestamp' => time(),
            ];

            $token = Authorization::generateToken($tokenPayload);

            return $this->successResponse([
                'token' => $token,
                'user' => $tokenPayload
            ], 'Token generated successfully');
        }

        if ($method === 'post') {
            $accessToken = $this->request->getHeaderLine('Accesstoken');

            if (empty($accessToken)) {
                return $this->unauthorizedResponse('Access token required');
            }

            $decoded = Authorization::validateToken($accessToken);

            if (!$decoded) {
                return $this->unauthorizedResponse('Invalid or expired token');
            }

            // Check if token has expired
            $validToken = Authorization::validateTimestamp($accessToken);
            if (!$validToken) {
                return $this->unauthorizedResponse('Token has expired');
            }

            return $this->successResponse(json_decode(json_encode($decoded), true), 'Token validated successfully');
        }

        return $this->errorResponse('Unsupported method', 405);
    }

    /**
     * Admin login endpoint
     */
    public function login(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            if (!$data) {
                return $this->errorResponse('Invalid request data');
            }

            // Validate required fields
            $requiredFields = ['username', 'password'];
            if (!$this->validateRequiredFields((array)$data, $requiredFields)) {
                $missing = $this->getMissingFields((array)$data, $requiredFields);
                return $this->errorResponse('Missing required fields: ' . implode(', ', $missing));
            }

            // TODO: Implement actual admin login logic
            // For now, return a mock response
            if ($data->username === 'admin' && $data->password === 'admin123') {
                $tokenPayload = [
                    'id' => 1,
                    'role' => 'admin',
                    'school_id' => 1,
                    'timestamp' => time(),
                ];

                $token = Authorization::generateToken($tokenPayload);

                return $this->successResponse([
                    'token' => $token,
                    'user' => $tokenPayload
                ], 'Login successful');
            }

            return $this->unauthorizedResponse('Invalid credentials');

        } catch (\Exception $e) {
            return $this->errorResponse('Login failed: ' . $e->getMessage());
        }
    }

    /**
     * Admin logout endpoint
     */
    public function logout(): ResponseInterface
    {
        // In a stateless JWT system, logout is typically handled client-side
        // by removing the token from storage
        return $this->successResponse(null, 'Logout successful');
    }

    /**
     * Validate admin token
     */
    public function validateToken(): ResponseInterface
    {
        $token = $this->request->getHeaderLine('Accesstoken');
        
        if (empty($token)) {
            return $this->unauthorizedResponse('Access token required');
        }

        $decoded = Authorization::validateToken($token);
        
        if (!$decoded) {
            return $this->unauthorizedResponse('Invalid token');
        }

        if (!Authorization::isAdmin($decoded)) {
            return $this->unauthorizedResponse('Admin access required');
        }

        return $this->successResponse([
            'valid' => true,
            'user' => $decoded
        ], 'Token is valid');
    }
}
