<?php
// Test database connection
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment
$env = parse_ini_file(__DIR__ . '/../.env');
if (!$env) {
    die("Failed to load .env file\n");
}

echo "Database Configuration Test\n";
echo "===========================\n\n";

// Try to connect
$mysqli = new mysqli(
    $env['database_default_hostname'] ?? 'localhost',
    $env['database_default_username'] ?? 'root',
    $env['database_default_password'] ?? 'root',
    $env['database_default_database'] ?? 'edquill_production',
    $env['database_default_port'] ?? 8889
);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error . "\n");
}

echo "✓ Database connection successful!\n\n";
echo "Connected to:\n";
echo "  Host: " . ($env['database_default_hostname'] ?? 'localhost') . "\n";
echo "  Database: " . ($env['database_default_database'] ?? 'edquill_production') . "\n";
echo "  Port: " . ($env['database_default_port'] ?? 8889) . "\n\n";

// Test table existence
$tables = ['students', 'teachers', 'classes', 'content', 'schools', 'users'];
echo "Checking tables:\n";
foreach ($tables as $table) {
    $result = $mysqli->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        echo "  ✓ $table exists\n";
    } else {
        echo "  ✗ $table NOT FOUND\n";
    }
}

$mysqli->close();

