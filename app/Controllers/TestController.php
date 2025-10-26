<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\Response;

class TestController extends Controller
{
    public function __construct()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    }

    public function index()
    {
        try {
            return $this->response->setBody("This is the index method of TestController");
        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setBody("Error: " . $e->getMessage());
        }
    }

    public function hello($name = 'World')
    {
        try {
            return $this->response->setBody("Hello, {$name}! This is a test route.");
        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setBody("Error: " . $e->getMessage());
        }
    }
} 