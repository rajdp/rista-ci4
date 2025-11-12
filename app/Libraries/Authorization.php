<?php

namespace App\Libraries;

use App\Libraries\JWT as LegacyJWT;

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
        $jwtConfig = config('Jwt');
        $jwtKey = $jwtConfig->key;
        return LegacyJWT::decode($token, $jwtKey);
    }

    /**
     * Generate JWT token
     *
     * @param array $data
     * @return string
     */
    public static function generateToken($data)
    {
        $jwtConfig = config('Jwt');
        $jwtKey = $jwtConfig->key;
        return LegacyJWT::encode($data, $jwtKey);
    }

    /**
     * Check if user has admin role
     *
     * @param object $tokenPayload
     * @return bool
     */
    public static function isAdmin($tokenPayload)
    {
        // Support both 'role' and 'role_id' for backward compatibility
        $roleValue = $tokenPayload->role_id ?? $tokenPayload->role ?? null;
        return $roleValue === 'admin' || $roleValue === 1 || $roleValue === '1';
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
        // Support both 'role' and 'role_id' for backward compatibility
        $roleValue = $tokenPayload->role_id ?? $tokenPayload->role ?? null;
        return $roleValue == $role;
    }

    /**
     * Get user ID from token
     *
     * @param object $tokenPayload
     * @return int|null
     */
    public static function getUserId($tokenPayload)
    {
        // Support both 'id' and 'user_id' for backward compatibility
        if (isset($tokenPayload->user_id)) {
            return $tokenPayload->user_id;
        }
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
