<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\Authorization;

class AuthFilter implements FilterInterface
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
        // Get the current route
        $router = service('router');
        $route = $router->getMatchedRoute();
        $controller = $route[0] ?? '';
        $method = $route[1] ?? '';

        // Routes that don't require authentication
        $excludedRoutes = [
            'user/login',
            'user/register',
            'user/forgotPassword',
            'user/resetPassword',
            'user/dashBoard',
            'user/records',
            'user/content',
            'user/myProfile',
            'user/configValues',
            'auth/token',
            'common/countries',
            'common/states',
            'common/cities',
            'common/fileUpload',
            'common/tagsList',
            'common/country',
            'grade/list',
            'grade/allStudentList',
            'subject/list',
            'batch/list',
            'course/list',
            'course/seoList',
            'course/faqList',
            'student/list',
            'student/StudentFromClassList',
            'teacher/list',
            'contentcreator/list',
            'classes/list',
            'classes/teacherList',
            'classes/getCommentCount',
            'classes/classDetail',
            'classes/overallClassAttendance',
            'classes/zoomInstantCreation',
            'classes/attendance',
            'common/tagsList',
            'content/sortMaster',
            'content/specifiedClassList',
            'school/registration',
            'school/announcementList',
            'sitecontent/categoryList',
            'sitecontent/listContent',
            'sitecontent/seoList',
            'sitecontent/categoryAdd',
            'sitecontent/categoryEdit',
            'sitecontent/addContent',
            'sitecontent/editContent',
            'sitecontent/addSeo',
            'sitecontent/editSeo',
            'report/classList',
            'report/gradeReport',
            'category/list',
            'test',
            'test-api'
        ];

        $currentRoute = $controller . '/' . $method;
        
        // Check if current route is excluded
        foreach ($excludedRoutes as $excludedRoute) {
            if (strpos($currentRoute, $excludedRoute) !== false) {
                return; // Allow request to continue
            }
        }

        // Get token from header
        $token = $request->getHeaderLine('Accesstoken');
        
        log_message('debug', 'AuthFilter: Route = ' . $currentRoute);
        log_message('debug', 'AuthFilter: Token = ' . ($token ? substr($token, 0, 20) . '...' : 'EMPTY'));
        
        if (empty($token)) {
            log_message('error', 'AuthFilter: No access token provided for route: ' . $currentRoute);
            return $this->unauthorizedResponse('Access token required');
        }

        // Validate token with exception handling
        try {
            $tokenPayload = Authorization::validateToken($token);
            
            if (!$tokenPayload) {
                log_message('debug', 'AuthFilter: Token validation returned false');
                return $this->unauthorizedResponse('Invalid or expired token');
            }
        } catch (\Throwable $e) {
            log_message('error', 'AuthFilter: Token validation exception: ' . $e->getMessage());
            return $this->unauthorizedResponse('Invalid token format');
        }

        // Check token timestamp
        try {
            $validToken = Authorization::validateTimestamp($token);
            if (!$validToken) {
                log_message('debug', 'AuthFilter: Token timestamp validation failed');
                return $this->unauthorizedResponse('Token has expired');
            }
        } catch (\Throwable $e) {
            log_message('error', 'AuthFilter: Token timestamp validation exception: ' . $e->getMessage());
            return $this->unauthorizedResponse('Token validation error');
        }

        // Check if token is still active in database (for multiple login control)
        $db = \Config\Database::connect();
        $tokenStatus = $db->table('user_token')
            ->select('status')
            ->where('access_token', $token)
            ->get()
            ->getRowArray();

        if (!$tokenStatus || (int)$tokenStatus['status'] !== 1) {
            log_message('error', 'AuthFilter: Token not active in database. Status = ' . ($tokenStatus ? $tokenStatus['status'] : 'NOT FOUND'));
            return $this->unauthorizedResponse('Your session has expired. You have logged in from another device. Please re-login');
        }
        
        log_message('debug', 'AuthFilter: Token validated successfully for route: ' . $currentRoute);

        // Store user info in request for use in controllers
        $request->user = $tokenPayload;
        $request->user_id = Authorization::getUserId($tokenPayload);
        $request->school_id = Authorization::getSchoolId($tokenPayload);
        $request->is_admin = Authorization::isAdmin($tokenPayload);
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
}
