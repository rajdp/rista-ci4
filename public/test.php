<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__DIR__) . '/writable/logs/php_error.log');

// Test file writing
$logFile = dirname(__DIR__) . '/writable/logs/test.log';
file_put_contents($logFile, date('Y-m-d H:i:s') . " - Test entry from test.php\n", FILE_APPEND);

// Test database connection
$mysqli = new mysqli('localhost', 'root', 'root', 'edquill_production', 8889);

if ($mysqli->connect_error) {
    die('Database connection error: ' . $mysqli->connect_error);
}

echo "Database connection successful\n";
echo "PHP version: " . PHP_VERSION . "\n";
echo "Error reporting level: " . error_reporting() . "\n";
echo "Display errors: " . ini_get('display_errors') . "\n";
echo "Error log path: " . ini_get('error_log') . "\n";
echo "Current working directory: " . getcwd() . "\n";

// Force an error to test error reporting
$undefined_variable; 