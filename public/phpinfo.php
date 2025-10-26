<?php
// Basic PHP configuration test
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Output basic information
echo "<h1>PHP Configuration Test</h1>";
echo "<pre>";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Script Path: " . __FILE__ . "\n";
echo "Current Working Directory: " . getcwd() . "\n";
echo "</pre>";

// Display all PHP configuration
phpinfo(); 