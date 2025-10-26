<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;

class TestApi extends ResourceController
{
    use ResponseTrait;

    public function index()
    {
        return $this->respond([
            'status' => 'success',
            'message' => 'API is working!'
        ]);
    }
} 