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
        
        // Get allowed origins from environment
        $allowedOrigins = env('cors.allowedOrigins', 'http://localhost:8211,http://localhost:4211');
        $origins = explode(',', $allowedOrigins);
        
        // Get the origin from the request
        $origin = $request->getHeaderLine('Origin');
        
        // Check if origin is allowed
        if (in_array($origin, $origins)) {
            $response->setHeader('Access-Control-Allow-Origin', $origin);
        } else {
            // Default to first allowed origin for development
            $response->setHeader('Access-Control-Allow-Origin', $origins[0]);
        }
        
        // Set CORS headers
        $response->setHeader('Access-Control-Allow-Methods', env('cors.allowedMethods', 'GET, POST, PUT, DELETE, OPTIONS'))
                ->setHeader('Access-Control-Allow-Headers', env('cors.allowedHeaders', 'Content-Type, Authorization, X-Requested-With, Origin, Accept, Accesstoken, accesstoken'))
                ->setHeader('Access-Control-Allow-Credentials', env('cors.allowCredentials', 'true'))
                ->setHeader('Access-Control-Max-Age', '3600');

        // Handle preflight requests
        if ($request->getMethod() === 'options' || $request->getMethod() === 'OPTIONS') {
            return $response->setStatusCode(200);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}
