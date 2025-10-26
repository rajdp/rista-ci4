<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Traits\CorsTrait;

class BaseController extends ResourceController
{
    use ResponseTrait;
    use CorsTrait;

    protected $format = 'json';
    protected $models = [];
    protected $helpers = ['url', 'form', 'text', 'authorization', 'jwt'];
    protected $modelPath = 'App\Models';

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);
        
        // CORS is handled globally by CorsFilter - removed from here to avoid duplicate headers
        // $this->setCorsHeaders();
        
        // Load models
        foreach ($this->models as $model) {
            $this->$model = model("App\\Models\\{$model}");
        }
        
        // Load helpers
        helper($this->helpers);
    }

    protected function getRequestData()
    {
        $json = $this->request->getJSON();
        return $json ? (array)$json : $this->request->getPost();
    }

    protected function getRequestHeaders()
    {
        return $this->request->getHeaders();
    }

    protected function validateRequest($rules)
    {
        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }
        return true;
    }

    protected function benchmark($start)
    {
        $end = microtime(true);
        return number_format($end - $start, 4);
    }

    protected function success($data = null, $message = 'Success', $code = 200)
    {
        return $this->respond([
            'status' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    protected function fail($messages, int $status = 400, ?string $code = null, string $customMessage = '')
    {
        if (is_string($messages)) {
            $messages = [$messages];
        }
        
        return $this->respond([
            'status' => false,
            'message' => $customMessage ?: (is_array($messages) ? implode(', ', $messages) : $messages),
            'data' => null
        ], $status);
    }
} 
