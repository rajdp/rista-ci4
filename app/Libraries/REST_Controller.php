<?php

/**
 * REST_Controller Compatibility Layer for CodeIgniter 4
 * 
 * This class provides compatibility for legacy CI3 controllers
 * that extend REST_Controller in a CI4 environment.
 */

// REST_Controller must be in global namespace for legacy CI3 compatibility
if (!class_exists('REST_Controller')) {
    // Use CodeIgniter 4 BaseController if available (preferred for CI4)
    if (class_exists('\App\Controllers\BaseController')) {
        class REST_Controller extends \App\Controllers\BaseController {
            protected $jsonarr = array();
            protected $headers;
            protected $urlAuth;
            protected $controller;
            protected $load;
            protected $input;
            protected $output;
            protected $benchmark;

            public function __construct()
            {
                // Don't call parent::__construct() here - CI4 uses initController() instead
                // The initController will be called by the framework
                
                // Initialize CI3-style loaders and helpers
                $this->load = new LegacyLoader();
                $this->input = new LegacyInput();
                $this->output = new LegacyOutput();
                $this->benchmark = new LegacyBenchmark();
            }
            
            // Override initController to ensure CI4 initialization happens
            public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
            {
                parent::initController($request, $response, $logger);
                
                // Ensure legacy loaders are initialized after parent init
                if (!isset($this->load)) {
                    $this->load = new LegacyLoader();
                    $this->input = new LegacyInput();
                    $this->output = new LegacyOutput();
                    $this->benchmark = new LegacyBenchmark();
                }
            }

            /**
             * Print JSON response (CI3 compatibility)
             */
            protected function printjson($data)
            {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }

            /**
             * Get URI string (CI3 compatibility)
             */
            protected function uri_string()
            {
                if (isset($_SERVER['PATH_INFO'])) {
                    return trim($_SERVER['PATH_INFO'], '/');
                }
                
                $requestUri = $_SERVER['REQUEST_URI'] ?? '';
                $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
                
                $uri = str_replace($scriptName, '', $requestUri);
                $uri = parse_url($uri, PHP_URL_PATH);
                
                return trim($uri, '/');
            }
        }
    } else {
        // Fallback for CI3 compatibility (without parent class)
        class REST_Controller {
            protected $jsonarr = array();
            protected $headers;
            protected $urlAuth;
            protected $controller;
            protected $load;
            protected $input;
            protected $output;
            protected $benchmark;

            public function __construct()
            {
                $this->load = new LegacyLoader();
                $this->input = new LegacyInput();
                $this->output = new LegacyOutput();
                $this->benchmark = new LegacyBenchmark();
            }

            protected function printjson($data)
            {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }

            protected function uri_string()
            {
                if (isset($_SERVER['PATH_INFO'])) {
                    return trim($_SERVER['PATH_INFO'], '/');
                }
                
                $requestUri = $_SERVER['REQUEST_URI'] ?? '';
                $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
                
                $uri = str_replace($scriptName, '', $requestUri);
                $uri = parse_url($uri, PHP_URL_PATH);
                
                return trim($uri, '/');
            }
        }
    }
}

/**
 * Legacy Loader compatibility class
 */
class LegacyLoader
{
    protected $loadedModels = [];

    public function model($model)
    {
        // Check if already loaded
        if (isset($this->loadedModels[$model])) {
            return $this->loadedModels[$model];
        }

        // Try various naming conventions
        $modelName = $model . '_model';
        $modelClass = '\\App\\Models\\V1\\' . ucfirst($model) . 'Model';
        
        if (class_exists($modelClass)) {
            $this->loadedModels[$model] = new $modelClass();
            return $this->loadedModels[$model];
        }
        
        // Fallback to old naming convention
        $oldModelClass = '\\App\\Models\\V1\\' . ucfirst($modelName);
        if (class_exists($oldModelClass)) {
            $this->loadedModels[$model] = new $oldModelClass();
            return $this->loadedModels[$model];
        }
        
        // Try without V1 namespace
        $simpleModelClass = '\\App\\Models\\' . ucfirst($model) . 'Model';
        if (class_exists($simpleModelClass)) {
            $this->loadedModels[$model] = new $simpleModelClass();
            return $this->loadedModels[$model];
        }
        
        log_message('error', "Model {$model} not found");
        return null;
    }
}

/**
 * Legacy Input compatibility class
 */
class LegacyInput
{
    public function request_headers()
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                $headers[$header] = $value;
            }
        }
        return $headers;
    }
}

/**
 * Legacy Output compatibility class
 */
class LegacyOutput
{
    public function set_status_header($code, $text = '')
    {
        http_response_code($code);
    }
}

/**
 * Legacy Benchmark compatibility class
 */
class LegacyBenchmark
{
    protected $marks = [];

    public function mark($name)
    {
        $this->marks[$name] = microtime(true);
    }

    public function elapsed_time($point1 = '', $point2 = '', $decimals = 4)
    {
        if ($point1 === '') {
            return '{elapsed_time}';
        }

        if (!isset($this->marks[$point1])) {
            return '';
        }

        if (!isset($this->marks[$point2])) {
            $this->marks[$point2] = microtime(true);
        }

        return number_format(($this->marks[$point2] - $this->marks[$point1]) * 1000, $decimals);
    }
}
