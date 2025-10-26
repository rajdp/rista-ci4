<?php
defined('BASEPATH') or exit('No direct script access allowed');
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: AccessToken");
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');

require APPPATH . '/libraries/REST_Controller.php';

class Sitecontent extends REST_Controller
{
    protected $jsonarr = array();
    protected $headers;
    protected $urlAuth;
    protected $controller;
    protected $site_content_service;

    function __construct()
    {
        parent::__construct();
        include APPPATH . 'modules/v1/services/SiteContentService.php';
        $this->site_content_service = new SiteContentService;



        header("Access-Control-Allow-Origin: *");
        $this->controller = uri_string();
        
        // Bypass authentication for CI4 routes (without v1/ prefix)
        $ci4Routes = ['sitecontent/categoryList', 'sitecontent/listContent', 'sitecontent/seoList', 
                      'sitecontent/categoryAdd', 'sitecontent/categoryEdit', 'sitecontent/addContent',
                      'sitecontent/editContent', 'sitecontent/addSeo', 'sitecontent/editSeo'];
        if (in_array($this->controller, $ci4Routes)) {
            $this->output->set_status_header(200);
            return;
        }
        
        $urlAuth = $this->verifyAuthUrl();
        $headers = $this->input->request_headers();
        if ($urlAuth) {
            $excludeurl = $this->excludefunction();
            if ($excludeurl != 'true') {
                if (isset($headers['Accesstoken'])) {
                    $this->output->set_status_header(200);
                    $headers['Accesstoken'];
                } else {
                    $this->jsonarr['ErrorObject'] = "Unauthorized User";
                    $this->jsonarr['IsSuccess'] = false;
                    $this->printjson($this->jsonarr);
                    $this->output->set_status_header(401);
                    exit();
                }
            } else {
                $this->output->set_status_header(200);
                return true;
            }
        } else {
            $this->output->set_status_header(200);
            $this->jsonarr['ErrorObject'] = "The requested url is not found.";
            $this->jsonarr['IsSuccess'] = false;
            $this->printjson($this->jsonarr);
            exit();
        }
    }

    public function verifyAuthUrl()
    {
        $this->allowedRoutes = array(
            'v1/sitecontent/categoryAdd',
            'v1/sitecontent/categoryEdit',
            'v1/sitecontent/categoryList',
            'v1/sitecontent/addSeo',
            'v1/sitecontent/editSeo',
            'v1/sitecontent/seoList',
            'v1/sitecontent/addContent',
            'v1/sitecontent/editContent',
            'v1/sitecontent/listContent',
            // CI4 routes without v1 prefix
            'sitecontent/categoryAdd',
            'sitecontent/categoryEdit',
            'sitecontent/categoryList',
            'sitecontent/addSeo',
            'sitecontent/editSeo',
            'sitecontent/seoList',
            'sitecontent/addContent',
            'sitecontent/editContent',
            'sitecontent/listContent'
         );
        foreach ($this->allowedRoutes as $routeString) {
            if ($this->controller == $routeString) {
                return true;
            }
        }
        return false;
    }

    public function excludefunction()
    {
        $this->excludeRoutes = array(
            'sitecontent/categoryList',
            'sitecontent/listContent',
            'sitecontent/seoList'
        );
        foreach ($this->excludeRoutes as $routeString) {
            if ($this->controller == $routeString) {
                return 'true';
            }
        }
    }

    public function addContent_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents("php://input"), true);
        $this->jsonarr = $this->site_content_service->addBlogger($params);
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->printjson($this->jsonarr);
    }

    public function editContent_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents("php://input"), true);
        $this->jsonarr = $this->site_content_service->updateBlogger($params);
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->printjson($this->jsonarr);
    }
    public function listContent_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents("php://input"), true);
        $this->jsonarr = $this->site_content_service->bloggerList($params);
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->printjson($this->jsonarr);
    }

    public function viewCount()
    {
        $params = json_decode(file_get_contents("php://input"), true);
        $this->jsonarr = $this->blogger_service->viewCount($params);
        return $this->common_service->printJson($this->jsonarr);
    }
    public function deleteBlogger()
    {
        $params = json_decode(file_get_contents("php://input"), true);
        $this->jsonarr = $this->blogger_service->deleteBlogger($params);
        return $this->common_service->printJson($this->jsonarr);
    }

    public function addSeo_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents("php://input"), true);
        $this->jsonarr = $this->site_content_service->addBlogSeo($params);
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->printjson($this->jsonarr);
    }

    public function editSeo_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents("php://input"), true);
        $this->jsonarr = $this->site_content_service->updateBlogSeo($params);
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->printjson($this->jsonarr);
    }

    public function seoList_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents("php://input"), true);
        $this->jsonarr = $this->site_content_service->blogSeo($params);
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->printjson($this->jsonarr);
    }

    public function categoryList_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents("php://input"), true);
        $this->jsonarr = $this->site_content_service->categoryList($params);
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->printjson($this->jsonarr);
    }

    public function categoryAdd_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents("php://input"), true);
        $this->jsonarr = $this->site_content_service->categoryAdd($params);
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->printjson($this->jsonarr);
    }

    public function categoryEdit_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents("php://input"), true);
        $this->jsonarr = $this->site_content_service->categoryEdit($params);
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->printjson($this->jsonarr);
    }

    private function printjson($jsonarr)
    {
        echo json_encode($jsonarr);
    }

}
