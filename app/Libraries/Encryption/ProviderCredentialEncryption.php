<?php

namespace App\Libraries\Encryption;

use CodeIgniter\Encryption\Encryption;
use Config\Encryption as EncryptionConfig;

class ProviderCredentialEncryption
{
    protected $encryption;

    public function __construct()
    {
        $config = new EncryptionConfig();
        $this->encryption = \Config\Services::encrypter($config);
    }

    /**
     * Encrypt provider credentials before storing in database
     *
     * @param array $credentials Credentials array
     * @return string Base64-encoded encrypted string
     */
    public function encryptCredentials(array $credentials): string
    {
        $json = json_encode($credentials);
        $encrypted = $this->encryption->encrypt($json);
        return base64_encode($encrypted);
    }

    /**
     * Decrypt provider credentials from database
     *
     * @param string $encrypted Base64-encoded encrypted string
     * @return array Decrypted credentials array
     * @throws \RuntimeException If decryption fails
     */
    public function decryptCredentials(string $encrypted): array
    {
        try {
            $decoded = base64_decode($encrypted);
            $decrypted = $this->encryption->decrypt($decoded);
            return json_decode($decrypted, true) ?? [];
        } catch (\Exception $e) {
            log_message('error', 'Failed to decrypt credentials: ' . $e->getMessage());
            throw new \RuntimeException('Invalid or corrupted credentials');
        }
    }

    /**
     * Validate credentials against provider schema
     *
     * @param array $credentials Credentials to validate
     * @param array $schema Provider schema from database
     * @return array ['valid' => bool, 'missing' => array, 'errors' => array]
     */
    public function validateCredentials(array $credentials, array $schema): array
    {
        $missing = [];
        $errors = [];

        // Check required fields
        foreach ($schema['required'] ?? [] as $field) {
            if (!isset($credentials[$field]) || $credentials[$field] === '') {
                $missing[] = $field;
            }
        }

        // Validate field formats if specified
        if (isset($schema['validation'])) {
            foreach ($credentials as $key => $value) {
                if (isset($schema['validation'][$key])) {
                    $validation = $schema['validation'][$key];
                    if ($validation['type'] === 'url' && !filter_var($value, FILTER_VALIDATE_URL)) {
                        $errors[$key] = 'Invalid URL format';
                    } elseif ($validation['type'] === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[$key] = 'Invalid email format';
                    } elseif ($validation['type'] === 'phone' && !preg_match('/^\+?[1-9]\d{1,14}$/', $value)) {
                        $errors[$key] = 'Invalid phone format';
                    }
                }
            }
        }

        return [
            'valid' => empty($missing) && empty($errors),
            'missing' => $missing,
            'errors' => $errors
        ];
    }

    /**
     * Mask sensitive credentials for display
     *
     * @param array $credentials Original credentials
     * @return array Masked credentials
     */
    public function maskCredentials(array $credentials): array
    {
        $sensitivePatterns = [
            'password', 'secret', 'token', 'key', 'auth',
            'api_key', 'api_secret', 'auth_token', 'access_key',
            'private_key', 'client_secret'
        ];

        $masked = [];
        foreach ($credentials as $key => $value) {
            $keyLower = strtolower($key);
            $isSensitive = false;

            foreach ($sensitivePatterns as $pattern) {
                if (str_contains($keyLower, $pattern)) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive && is_string($value)) {
                $masked[$key] = $this->maskString($value);
            } elseif (is_array($value)) {
                $masked[$key] = $this->maskCredentials($value);
            } else {
                $masked[$key] = $value;
            }
        }

        return $masked;
    }

    /**
     * Mask a string value showing only first and last 2 characters
     */
    protected function maskString(string $value): string
    {
        $length = strlen($value);
        if ($length <= 4) {
            return str_repeat('*', $length);
        }
        return substr($value, 0, 2) . str_repeat('*', $length - 4) . substr($value, -2);
    }

    /**
     * Check if credentials have changed
     */
    public function credentialsChanged(array $newCredentials, string $encryptedOld): bool
    {
        try {
            $oldCredentials = $this->decryptCredentials($encryptedOld);
            return $newCredentials !== $oldCredentials;
        } catch (\Exception $e) {
            return true;
        }
    }

    /**
     * Re-encrypt credentials with a new key (for key rotation)
     */
    public function reEncryptCredentials(string $encrypted, $newEncrypter): string
    {
        $credentials = $this->decryptCredentials($encrypted);
        $newEncrypted = $newEncrypter->encrypt(json_encode($credentials));
        return base64_encode($newEncrypted);
    }
}
