<?php

/**
 * JSON Web Token helper (ported from the original CI3 implementation).
 *
 * This helper intentionally mirrors the legacy behaviour so existing payloads
 * continue to work while the application completes its CI4 migration.
 */
class JWT
{
    public static function decode(string $jwt, ?string $key = null, bool $verify = true)
    {
        $tokens = explode('.', $jwt);
        if (count($tokens) !== 3) {
            return false;
        }

        [$headb64, $bodyb64, $cryptob64] = $tokens;

        if (null === ($header = self::jsonDecode(self::urlsafeB64Decode($headb64)))) {
            return false;
        }

        if (null === ($payload = self::jsonDecode(self::urlsafeB64Decode($bodyb64)))) {
            return false;
        }

        $signature = self::urlsafeB64Decode($cryptob64);

        if ($verify) {
            if (empty($header->alg)) {
                return false;
            }

            if ($signature !== self::sign("{$headb64}.{$bodyb64}", $key, $header->alg)) {
                throw new UnexpectedValueException('Signature verification failed');
            }
        }

        return $payload;
    }

    public static function encode($payload, string $key, string $algo = 'HS256'): string
    {
        $header = ['typ' => 'JWT', 'alg' => $algo];

        $segments = [];
        $segments[] = self::urlsafeB64Encode(self::jsonEncode($header));
        $segments[] = self::urlsafeB64Encode(self::jsonEncode($payload));
        $signingInput = implode('.', $segments);

        $signature = self::sign($signingInput, $key, $algo);
        $segments[] = self::urlsafeB64Encode($signature);

        return implode('.', $segments);
    }

    public static function sign(string $message, string $key, string $method = 'HS256')
    {
        $methods = [
            'HS256' => 'sha256',
            'HS384' => 'sha384',
            'HS512' => 'sha512',
        ];

        if (! isset($methods[$method])) {
            throw new DomainException('Algorithm not supported');
        }

        return hash_hmac($methods[$method], $message, $key, true);
    }

    public static function jsonDecode(string $input)
    {
        $obj = json_decode($input);
        if (function_exists('json_last_error') && $errno = json_last_error()) {
            self::handleJsonError($errno);
        } elseif ($obj === null && $input !== 'null') {
            throw new DomainException('Null result with non-null input');
        }

        return $obj;
    }

    public static function jsonEncode($input): string
    {
        $json = json_encode($input);
        if (function_exists('json_last_error') && $errno = json_last_error()) {
            self::handleJsonError($errno);
        } elseif ($json === 'null' && $input !== null) {
            throw new DomainException('Null result with non-null input');
        }

        return $json;
    }

    public static function urlsafeB64Decode(string $input)
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= str_repeat('=', $padlen);
        }

        return base64_decode(strtr($input, '-_', '+/'));
    }

    public static function urlsafeB64Encode(string $input): string
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }

    private static function handleJsonError(int $errno): void
    {
        $messages = [
            JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
            JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
            JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON',
        ];

        $message = $messages[$errno] ?? 'Unknown JSON error: ' . $errno;
        throw new DomainException($message);
    }
}
