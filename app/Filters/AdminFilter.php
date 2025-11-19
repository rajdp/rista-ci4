<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\Authorization;
use App\Services\AuthContext;

class AdminFilter implements FilterInterface
{
    /**
     * Do whatever processing this filter needs to do.
     * By default it should not return anything during
     * normal execution. However, when an abnormal state
     * is found, it should return an instance of
     * CodeIgniter\HTTP\Response. If it does, script
     * execution will end and that Response will be
     * sent back to the client, allowing for error pages,
     * redirects, etc.
     *
     * @param RequestInterface $request
     * @param array|null       $arguments
     *
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        // Check if user is authenticated (AuthFilter should have run first)
        /** @var AuthContext $authContext */
        $authContext = service('authcontext');
        $userPayload = $authContext->getUserPayload();

        if (!$userPayload) {
            return $this->unauthorizedResponse('Authentication required');
        }

        // Check if user has admin role
        if (!Authorization::isAdmin($userPayload)) {
            return $this->forbiddenResponse('Admin access required');
        }

        // Additional admin-specific checks can be added here
        // For example, check if admin account is active, etc.
    }

    /**
     * Allows After filters to inspect and modify the response
     * object as needed. This method does not allow any way
     * to stop execution of other after filters, short of
     * throwing an Exception or Error.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array|null        $arguments
     *
     * @return mixed
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }

    /**
     * Return unauthorized response
     *
     * @param string $message
     * @return ResponseInterface
     */
    private function unauthorizedResponse($message = 'Unauthorized')
    {
        $response = service('response');
        
        $response->setStatusCode(401);
        $response->setJSON([
            'IsSuccess' => false,
            'ResponseObject' => null,
            'ErrorObject' => $message
        ]);
        
        return $response;
    }

    /**
     * Return forbidden response
     *
     * @param string $message
     * @return ResponseInterface
     */
    private function forbiddenResponse($message = 'Forbidden')
    {
        $response = service('response');
        
        $response->setStatusCode(403);
        $response->setJSON([
            'IsSuccess' => false,
            'ResponseObject' => null,
            'ErrorObject' => $message
        ]);
        
        return $response;
    }
}
