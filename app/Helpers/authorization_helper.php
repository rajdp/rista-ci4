<?php

use Config\Jwt;

// Load JWT helper class directly if not already loaded
if (!class_exists('JWT')) {
    $jwtHelperPath = APPPATH . 'Helpers/jwt_helper.php';
    if (file_exists($jwtHelperPath)) {
        require_once $jwtHelperPath;
    }
}

/**
 * Legacy AUTHORIZATION helper ported to CI4.
 */
class AUTHORIZATION
{
    protected static function getConfig(): Jwt
    {
        return config('Jwt');
    }

    public static function validateTimestamp(string $token)
    {
        $decoded = self::validateToken($token);

        if ($decoded === false) {
            return false;
        }

        $timestamp = property_exists($decoded, 'timestamp') ? (int) $decoded->timestamp : null;
        if ($timestamp === null) {
            return false;
        }

        $timeout = self::getConfig()->tokenTimeout * 60;
        return (time() - $timestamp) < $timeout ? $decoded : false;
    }

    public static function validateToken(string $token)
    {
        $config = self::getConfig();

        try {
            // Ensure JWT helper class is loaded
            if (!class_exists('JWT', false)) {
                $jwtHelperPath = APPPATH . 'Helpers/jwt_helper.php';
                if (file_exists($jwtHelperPath)) {
                    require_once $jwtHelperPath;
                } else {
                    throw new \RuntimeException('JWT helper file not found at: ' . $jwtHelperPath);
                }
            }
            
            // Double-check the class exists and is the correct one (not Config\Jwt)
            if (!class_exists('JWT', false)) {
                throw new \RuntimeException('JWT helper class could not be loaded');
            }
            
            // Ensure we're calling the global JWT class, not Config\Jwt
            if (!method_exists('JWT', 'decode')) {
                throw new \RuntimeException('JWT::decode method not found');
            }
            
            // Get the key from config
            $key = $config->key ?? null;
            if (empty($key)) {
                throw new \RuntimeException('JWT key not configured');
            }
            
            // Explicitly use the global JWT class (not namespaced)
            return \JWT::decode($token, $key);
        } catch (\Throwable $exception) {
            log_message('error', 'JWT validation failed: ' . $exception->getMessage() . ' | File: ' . $exception->getFile() . ' | Line: ' . $exception->getLine());
            return false;
        }
    }

    public static function generateToken(array $data): string
    {
        $config = self::getConfig();
        
        // Ensure JWT helper class is loaded
        if (!class_exists('JWT', false)) {
            $jwtHelperPath = APPPATH . 'Helpers/jwt_helper.php';
            if (file_exists($jwtHelperPath)) {
                require_once $jwtHelperPath;
            }
        }
        
        $payload = array_merge($data, [
            'timestamp' => $data['timestamp'] ?? time(),
            'exp' => $data['exp'] ?? (time() + ($config->tokenTimeout * 60)),
        ]);

        $key = $config->key ?? null;
        if (empty($key)) {
            throw new \RuntimeException('JWT key not configured');
        }

        // Explicitly use the global JWT class (not namespaced)
        return \JWT::encode($payload, $key);
    }
}
