<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

class Book extends ResourceController
{
    protected $jsonarr = [];
    protected $format = 'json';
    protected $bookModel;
    protected $commonModel;
    protected $contentModel;
    protected $allowedRoutes;
    protected $excludeRoutes;
    protected $controller;

    protected function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        
        $this->bookModel = new \App\Models\V1\BookModel();
        $this->commonModel = new \App\Models\V1\CommonModel();
        $this->contentModel = new \App\Models\V1\ContentModel();
    }

    public function verifyAuthUrl()
    {
        $this->allowedRoutes = [
            'book/add',
            'book/edit',
            'book/list',
            'book/bulkUpload'
        ];
        
        $currentRoute = $this->request->getUri()->getPath();
        foreach ($this->allowedRoutes as $routeString) {
            if (strpos($currentRoute, $routeString) !== false) {
                return true;
            }
        }
        return false;
    }

