<?php

namespace App\Traits;

trait CorsTrait
{
    protected function setCorsHeaders()
    {
        $response = service('response');
        
        // Set CORS headers
        $response->setHeader('Access-Control-Allow-Origin', 'http://localhost:8211')
                ->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, Origin, Accept')
                ->setHeader('Access-Control-Allow-Credentials', 'true')
                ->setHeader('Access-Control-Max-Age', '3600');

        // Handle preflight requests
        if ($this->request->getMethod() === 'OPTIONS') {
            $response->setStatusCode(200);
            $response->send();
            exit();
        }
    }
} 