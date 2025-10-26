<?php

use Config\Jwt;

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

        $timeout = self::getConfig()->timeout * 60;
        return (time() - $timestamp) < $timeout ? $decoded : false;
    }

    public static function validateToken(string $token)
    {
        $config = self::getConfig();

        try {
            return JWT::decode($token, $config->key);
        } catch (\Throwable $exception) {
            log_message('error', 'JWT validation failed: ' . $exception->getMessage());
            return false;
        }
    }

    public static function generateToken(array $data): string
    {
        $config = self::getConfig();
        $payload = array_merge($data, [
            'timestamp' => $data['timestamp'] ?? time(),
            'exp' => $data['exp'] ?? (time() + ($config->timeout * 60)),
        ]);

        return JWT::encode($payload, $config->key);
    }
}
