<?php
// Test database connection
require_once __DIR__ . '/../vendor/autoload.php';

echo "Database Configuration Test\n";
echo "===========================\n\n";

// Try to connect using the same config as the app
$mysqli = new mysqli(
    '127.0.0.1',
    'root',
    'root',
    'edquill_production',
    8889
);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error . "\n");
}

echo "✓ Database connection successful!\n\n";
echo "Connected to:\n";
echo "  Host: 127.0.0.1\n";
echo "  Database: edquill_production\n";
echo "  Port: 8889\n\n";

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

