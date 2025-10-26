<?php

namespace App\Libraries;

use CodeIgniter\Config\Services;

class Authorization
{
    /**
     * Validate timestamp for token
     *
     * @param string $token
     * @return object|false
     */
    public static function validateTimestamp($token)
    {
        $token = self::validateToken($token);
        if ($token != false && (time() - $token->timestamp < (config('Jwt')->tokenTimeout * 60))) {
            return $token;
        }
        return false;
    }

    /**
     * Validate JWT token
     *
     * @param string $token
     * @return object|false
     */
    public static function validateToken($token)
    {
        $jwtKey = config('Jwt')->key;
        return JWT::decode($token, $jwtKey);
    }

    /**
     * Generate JWT token
     *
     * @param array $data
     * @return string
     */
    public static function generateToken($data)
    {
        $jwtKey = config('Jwt')->key;
        return JWT::encode($data, $jwtKey);
    }

    /**
     * Check if user has admin role
     *
     * @param object $tokenPayload
     * @return bool
     */
    public static function isAdmin($tokenPayload)
    {
        return isset($tokenPayload->role) && $tokenPayload->role === 'admin';
    }

    /**
     * Check if user has specific role
     *
     * @param object $tokenPayload
     * @param string $role
     * @return bool
     */
    public static function hasRole($tokenPayload, $role)
    {
        return isset($tokenPayload->role) && $tokenPayload->role === $role;
    }

    /**
     * Get user ID from token
     *
     * @param object $tokenPayload
     * @return int|null
     */
    public static function getUserId($tokenPayload)
    {
        return isset($tokenPayload->id) ? $tokenPayload->id : null;
    }

    /**
     * Get school ID from token
     *
     * @param object $tokenPayload
     * @return int|null
     */
    public static function getSchoolId($tokenPayload)
    {
        return isset($tokenPayload->school_id) ? $tokenPayload->school_id : null;
    }
}
