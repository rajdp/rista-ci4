<?php
/**
 * Check if vendor directory is complete
 * Access: https://edserver.edquillcrm.com/public/check_vendor.php
 * DELETE THIS FILE AFTER CHECKING
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Vendor Directory Check</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .ok{color:green;} .error{color:red;}</style>";

$basePath = dirname(__DIR__);
$vendorPath = $basePath . '/vendor';

echo "<h2>1. Vendor Directory</h2>";
if (is_dir($vendorPath)) {
    echo "<p class='ok'>✓ vendor/ directory exists</p>";
    echo "<p>Path: $vendorPath</p>";
} else {
    echo "<p class='error'>✗ vendor/ directory NOT FOUND</p>";
    echo "<p>Expected at: $vendorPath</p>";
    exit;
}

echo "<h2>2. CodeIgniter Framework</h2>";
$ciPath = $vendorPath . '/codeigniter4/framework';
if (is_dir($ciPath)) {
    echo "<p class='ok'>✓ CodeIgniter framework directory exists</p>";
} else {
    echo "<p class='error'>✗ CodeIgniter framework directory NOT FOUND</p>";
    echo "<p>Expected at: $ciPath</p>";
    exit;
}

echo "<h2>3. Exception Classes</h2>";
$exceptionsPath = $ciPath . '/system/Exceptions';
if (is_dir($exceptionsPath)) {
    echo "<p class='ok'>✓ Exceptions directory exists</p>";
    
    $requiredExceptions = [
        'InvalidArgumentException.php',
        'FrameworkException.php',
        'PageNotFoundException.php',
        'ConfigException.php'
    ];
    
    foreach ($requiredExceptions as $exception) {
        $file = $exceptionsPath . '/' . $exception;
        if (file_exists($file)) {
            $size = filesize($file);
            echo "<p class='ok'>✓ $exception exists ($size bytes)</p>";
        } else {
            echo "<p class='error'>✗ $exception NOT FOUND</p>";
        }
    }
} else {
    echo "<p class='error'>✗ Exceptions directory NOT FOUND</p>";
    echo "<p>Expected at: $exceptionsPath</p>";
}

echo "<h2>4. Autoloader</h2>";
$autoloadPath = $vendorPath . '/autoload.php';
if (file_exists($autoloadPath)) {
    echo "<p class='ok'>✓ autoload.php exists</p>";
    
    // Try to load it
    try {
        require_once $autoloadPath;
        echo "<p class='ok'>✓ Autoloader loaded successfully</p>";
        
        // Try to check if class can be found
        if (class_exists('CodeIgniter\Exceptions\InvalidArgumentException')) {
            echo "<p class='ok'>✓ InvalidArgumentException class can be loaded</p>";
        } else {
            echo "<p class='error'>✗ InvalidArgumentException class NOT FOUND even after autoload</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>✗ Error loading autoloader: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p class='error'>✗ autoload.php NOT FOUND</p>";
    echo "<p>Expected at: $autoloadPath</p>";
}

echo "<h2>5. DotEnv File</h2>";
$dotEnvPath = $ciPath . '/system/Config/DotEnv.php';
if (file_exists($dotEnvPath)) {
    echo "<p class='ok'>✓ DotEnv.php exists</p>";
    
    // Check line 179
    $lines = file($dotEnvPath);
    if (isset($lines[178])) { // Line 179 is index 178 (0-based)
        echo "<p>Line 179 content:</p>";
        echo "<pre>" . htmlspecialchars($lines[178]) . "</pre>";
    }
} else {
    echo "<p class='error'>✗ DotEnv.php NOT FOUND</p>";
}

echo "<h2>6. Check .env File Syntax</h2>";
$envPath = $basePath . '/.env';
if (file_exists($envPath)) {
    echo "<p class='ok'>✓ .env file exists</p>";
    
    // Check for common syntax issues
    $envContent = file_get_contents($envPath);
    $lines = explode("\n", $envContent);
    
    $issues = [];
    foreach ($lines as $num => $line) {
        $lineNum = $num + 1;
        $line = trim($line);
        
        // Skip comments and empty lines
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        
        // Check for unquoted URLs or values with spaces
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $value = trim($value);
            
            // Check for unquoted URLs
            if (strpos($value, 'http://') === 0 || strpos($value, 'https://') === 0) {
                if (!preg_match('/^["\'].*["\']$/', $value)) {
                    $issues[] = "Line $lineNum: URL value should be quoted: $line";
                }
            }
            
            // Check for values with spaces that aren't quoted
            if (strpos($value, ' ') !== false && !preg_match('/^["\'].*["\']$/', $value)) {
                $issues[] = "Line $lineNum: Value with spaces should be quoted: $line";
            }
        }
    }
    
    if (empty($issues)) {
        echo "<p class='ok'>✓ No obvious syntax issues found</p>";
    } else {
        echo "<p class='error'>⚠ Potential syntax issues:</p>";
        echo "<ul>";
        foreach ($issues as $issue) {
            echo "<li class='error'>$issue</li>";
        }
        echo "</ul>";
    }
} else {
    echo "<p class='error'>✗ .env file NOT FOUND</p>";
}

echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p><strong>If InvalidArgumentException.php exists but class can't be loaded:</strong></p>";
echo "<ol>";
echo "<li>Regenerate autoloader: <code>composer dump-autoload</code></li>";
echo "<li>Or reinstall: <code>composer install --no-dev</code></li>";
echo "<li>Check file permissions</li>";
echo "</ol>";

echo "<p><strong>DELETE THIS FILE AFTER CHECKING!</strong></p>";
?>








