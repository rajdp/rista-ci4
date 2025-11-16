<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/app/Config/Paths.php';

$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';

// Manually set up the framework services we need.
$config = config('App');
\Config\Services::autoloader()->initialize(new Config\Autoload(), new Config\Modules());

$request = \Config\Services::request();
$request->setJSON(json_encode([
    'username' => 'admin@edquill.com',
    'password' => '111111',
    'platform' => 'web',
]));

$controller = new \App\Controllers\User();

ob_start();
$response = $controller->login();
$output = ob_get_clean();

var_dump($response);
echo PHP_EOL;
var_dump($output);



