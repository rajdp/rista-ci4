<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class CorsFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $response = service('response');
        
        $allowedOriginsEnv = env('cors.allowedOrigins', 'http://localhost:8211,http://localhost:4211');
        $allowedOrigins = $this->explodeAndTrim($allowedOriginsEnv);
        $allowedDomainSuffixesEnv = env('cors.allowedDomainSuffixes', '.edquill.com,.edquill.test,.edquillcrm.com');
        $allowedDomainSuffixes = $this->explodeAndTrim($allowedDomainSuffixesEnv);

        $origin = $request->getHeaderLine('Origin');

        if (!empty($origin)) {
            if ($this->isExactOriginAllowed($origin, $allowedOrigins) || 
                $this->isDomainSuffixAllowed($origin, $allowedDomainSuffixes) ||
                $this->isLocalhostSubdomain($origin)) {
                $response->setHeader('Access-Control-Allow-Origin', $origin);
            } elseif (!empty($allowedOrigins)) {
                $response->setHeader('Access-Control-Allow-Origin', $allowedOrigins[0]);
            }
        } elseif (!empty($allowedOrigins)) {
            $response->setHeader('Access-Control-Allow-Origin', $allowedOrigins[0]);
        }

        $allowMethods = env('cors.allowedMethods', 'GET, POST, PUT, DELETE, OPTIONS');
        $allowHeaders = env('cors.allowedHeaders', 'Content-Type, Authorization, X-Requested-With, Origin, Accept, Accesstoken, accesstoken, X-School-Id');
        $allowCredentials = filter_var(env('cors.allowCredentials', true), FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';

        $response
            ->setHeader('Access-Control-Allow-Methods', $allowMethods)
            ->setHeader('Access-Control-Allow-Headers', $allowHeaders)
            ->setHeader('Access-Control-Allow-Credentials', $allowCredentials)
            ->setHeader('Access-Control-Max-Age', '3600')
            ->setHeader('Vary', 'Origin');

        // Handle preflight requests
        if ($request->getMethod() === 'options' || $request->getMethod() === 'OPTIONS') {
            return $response->setStatusCode(200);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }

    private function explodeAndTrim(string $input): array
    {
        $items = array_filter(array_map('trim', explode(',', $input)));
        return array_values($items);
    }

    private function isExactOriginAllowed(string $origin, array $allowedOrigins): bool
    {
        return in_array($origin, $allowedOrigins, true);
    }

    private function isDomainSuffixAllowed(string $origin, array $allowedSuffixes): bool
    {
        if (empty($allowedSuffixes)) {
            return false;
        }

        $parsed = parse_url($origin);
        if (!isset($parsed['host'])) {
            return false;
        }

        $host = strtolower($parsed['host']);
        foreach ($allowedSuffixes as $suffix) {
            $suffix = strtolower(trim($suffix));
            if ($suffix === '') {
                continue;
            }

            if ($host === $suffix || $this->endsWith($host, $suffix)) {
                return true;
            }

            if ($suffix[0] === '.' && $this->endsWith($host, $suffix)) {
                return true;
            }
        }

        return false;
    }

    private function endsWith(string $haystack, string $needle): bool
    {
        if ($needle === '') {
            return true;
        }

        $length = strlen($needle);
        return substr($haystack, -$length) === $needle;
    }

    /**
     * Check if origin is a localhost subdomain (e.g., schoolnew.localhost:8211)
     * This allows subdomain-based localhost development
     */
    private function isLocalhostSubdomain(string $origin): bool
    {
        $parsed = parse_url($origin);
        if (!isset($parsed['host'])) {
            return false;
        }

        $host = strtolower($parsed['host']);
        
        // Check for localhost subdomains (e.g., schoolnew.localhost, schoolnew.localhost:8888)
        // Handle both with and without port
        if (strpos($host, 'localhost') !== false || 
            strpos($host, '127.0.0.1') !== false ||
            $host === 'localhost') {
            return true;
        }
        
        // Match patterns like: subdomain.localhost or subdomain.localhost:port
        return preg_match('/^[a-zA-Z0-9-]+\.localhost(:\d+)?$/', $host) === 1;
    }
}
