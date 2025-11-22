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
        // First check if user is authenticated by validating token
        $token = $request->getHeaderLine('Accesstoken');
        
        if (empty($token)) {
            return $this->unauthorizedResponse('Authentication required');
        }

        // Validate token
        try {
            $tokenPayload = Authorization::validateToken($token);
            
            if (!$tokenPayload) {
                return $this->unauthorizedResponse('Invalid or expired token');
            }
        } catch (\Throwable $e) {
            log_message('error', 'AdminFilter: Token validation exception: ' . $e->getMessage());
            return $this->unauthorizedResponse('Invalid token format');
        }

        // Check token timestamp
        try {
            $validToken = Authorization::validateTimestamp($token);
            if (!$validToken) {
                return $this->unauthorizedResponse('Token has expired');
            }
        } catch (\Throwable $e) {
            log_message('error', 'AdminFilter: Token timestamp validation exception: ' . $e->getMessage());
            return $this->unauthorizedResponse('Token validation error');
        }

        // Check if token is still active in database
        $db = \Config\Database::connect();
        $tokenStatus = $db->table('user_token')
            ->select('status')
            ->where('access_token', $token)
            ->get()
            ->getRowArray();

        if (!$tokenStatus || (int)$tokenStatus['status'] !== 1) {
            return $this->unauthorizedResponse('Your session has expired. Please re-login');
        }

        // Set auth context
        /** @var AuthContext $authContext */
        $authContext = service('authcontext');
        $authContext->reset();
        $authContext->setUserPayload($tokenPayload);
        $authContext->setUserId(Authorization::getUserId($tokenPayload));
        $authContext->setSchoolId(Authorization::getSchoolId($tokenPayload));
        $authContext->setIsAdmin(Authorization::isAdmin($tokenPayload));

        // Check if user has admin, registrar, or billing role
        // Support both 'role' and 'role_id' for backward compatibility
        $roleValue = $tokenPayload->role_id ?? $tokenPayload->role ?? null;
        $isAdmin = $roleValue === 'admin' || $roleValue === 1 || $roleValue === '1' || $roleValue === 2 || $roleValue === '2';
        $isRegistrar = $roleValue === 'registrar' || $roleValue === 7 || $roleValue === '7';
        $isBilling = $roleValue === 8 || $roleValue === '8';
        
        if (!$isAdmin && !$isRegistrar && !$isBilling) {
            return $this->forbiddenResponse('Admin, Registrar, or Billing access required');
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
