<?php
/**
 * Test CodeIgniter Bootstrap
 * Access: https://edserver.edquillcrm.com/public/test-bootstrap.php
 * DELETE THIS FILE AFTER TESTING
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>CodeIgniter Bootstrap Test</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .ok{color:green;} .error{color:red;}</style>";

try {
    // Test 1: Define FCPATH
    define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
    echo "<p class='ok'>✓ FCPATH defined: " . FCPATH . "</p>";
    
    // Test 2: Load Paths
    $pathsFile = FCPATH . '../app/Config/Paths.php';
    if (file_exists($pathsFile)) {
        echo "<p class='ok'>✓ Paths.php exists</p>";
        require $pathsFile;
    } else {
        echo "<p class='error'>✗ Paths.php NOT FOUND at: $pathsFile</p>";
        exit;
    }
    
    // Test 3: Create Paths object
    $paths = new Config\Paths();
    echo "<p class='ok'>✓ Paths object created</p>";
    
    // Test 4: Check directories
    echo "<h2>Directory Check</h2>";
    
    $dirs = [
        'App Directory' => $paths->appDirectory,
        'System Directory' => $paths->systemDirectory,
        'Writable Directory' => $paths->writableDirectory,
    ];
    
    foreach ($dirs as $name => $path) {
        if (is_dir($path)) {
            echo "<p class='ok'>✓ $name exists: $path</p>";
        } else {
            echo "<p class='error'>✗ $name NOT FOUND: $path</p>";
        }
    }
    
    // Test 5: Check autoloader
    echo "<h2>Autoloader Check</h2>";
    $autoloadPath = dirname(__DIR__) . '/vendor/autoload.php';
    if (file_exists($autoloadPath)) {
        echo "<p class='ok'>✓ autoload.php exists</p>";
        require_once $autoloadPath;
        echo "<p class='ok'>✓ Autoloader loaded</p>";
    } else {
        echo "<p class='error'>✗ autoload.php NOT FOUND</p>";
    }
    
    // Test 6: Try to load exception class
    echo "<h2>Exception Class Check</h2>";
    $exceptionPath = $paths->systemDirectory . '/Exceptions/InvalidArgumentException.php';
    if (file_exists($exceptionPath)) {
        echo "<p class='ok'>✓ InvalidArgumentException.php exists</p>";
        
        if (class_exists('CodeIgniter\Exceptions\InvalidArgumentException')) {
            echo "<p class='ok'>✓ InvalidArgumentException class can be loaded</p>";
        } else {
            echo "<p class='error'>✗ InvalidArgumentException class NOT FOUND (autoloader issue)</p>";
        }
    } else {
        echo "<p class='error'>✗ InvalidArgumentException.php NOT FOUND at: $exceptionPath</p>";
    }
    
    // Test 7: Check .env file
    echo "<h2>.env File Check</h2>";
    $envPath = dirname(__DIR__) . '/.env';
    if (file_exists($envPath)) {
        echo "<p class='ok'>✓ .env file exists</p>";
        
        // Check for syntax issues
        $envContent = file_get_contents($envPath);
        if (preg_match('/session\.savePath\s*=\s*WRITEPATH\s*\./', $envContent)) {
            echo "<p class='error'>✗ .env has unquoted session.savePath (fix: session.savePath = null)</p>";
        } else {
            echo "<p class='ok'>✓ session.savePath looks correct</p>";
        }
        
        if (strpos($envContent, "encryption.key = 'your-generated-key-here'") !== false) {
            echo "<p class='error'>✗ encryption.key still has placeholder value</p>";
        } else {
            echo "<p class='ok'>✓ encryption.key is set</p>";
        }
    } else {
        echo "<p class='error'>✗ .env file NOT FOUND</p>";
    }
    
    echo "<hr>";
    echo "<h2>Summary</h2>";
    echo "<p>If all checks pass, the bootstrap should work. Check error logs for specific errors.</p>";
    
} catch (Exception $e) {
    echo "<p class='error'><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<p><strong>DELETE THIS FILE AFTER TESTING!</strong></p>";
?>






