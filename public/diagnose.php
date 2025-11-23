<?php
/**
 * CodeIgniter 4 Diagnostic Script
 * Access this file to check common issues
 * URL: https://edserver.edquillcrm.com/public/diagnose.php
 * 
 * DELETE THIS FILE AFTER DIAGNOSIS FOR SECURITY
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>CodeIgniter 4 Diagnostic</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .ok{color:green;} .error{color:red;} .warning{color:orange;}</style>";

$errors = [];
$warnings = [];

// Check 1: PHP Version
echo "<h2>1. PHP Version</h2>";
$phpVersion = PHP_VERSION;
$minVersion = '8.1';
if (version_compare($phpVersion, $minVersion, '>=')) {
    echo "<p class='ok'>✓ PHP Version: $phpVersion (Required: $minVersion+)</p>";
} else {
    echo "<p class='error'>✗ PHP Version: $phpVersion (Required: $minVersion+)</p>";
    $errors[] = "PHP version must be 8.1 or higher";
}

// Check 2: .env File
echo "<h2>2. .env File</h2>";
$envPath = dirname(__DIR__) . '/.env';
if (file_exists($envPath)) {
    echo "<p class='ok'>✓ .env file exists</p>";
    
    // Read .env content (safely)
    $envContent = file_get_contents($envPath);
    
    // Check for required variables
    $requiredVars = [
        'database.default.hostname',
        'database.default.database',
        'database.default.username',
        'database.default.password',
        'app.baseURL'
    ];
    
    foreach ($requiredVars as $var) {
        if (strpos($envContent, $var) !== false) {
            echo "<p class='ok'>✓ Found: $var</p>";
        } else {
            echo "<p class='warning'>⚠ Missing: $var</p>";
            $warnings[] = "Missing $var in .env";
        }
    }
    
    // Check encryption key
    if (strpos($envContent, 'encryption.key') !== false && 
        preg_match('/encryption\.key\s*=\s*["\']?[^"\'\s]+["\']?/', $envContent)) {
        echo "<p class='ok'>✓ Encryption key is set</p>";
    } else {
        echo "<p class='error'>✗ Encryption key is missing or empty</p>";
        $errors[] = "Encryption key not set. Run: php spark key:generate";
    }
    
} else {
    echo "<p class='error'>✗ .env file NOT FOUND at: $envPath</p>";
    $errors[] = ".env file is missing. Create it from .env.test or .env.production template";
}

// Check 3: Database Connection
echo "<h2>3. Database Connection</h2>";
if (file_exists($envPath)) {
    // Parse .env file
    $envVars = [];
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; // Skip comments
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            $value = trim($value, "'\"");
            $envVars[$key] = $value;
        }
    }
    
    $dbHost = $envVars['database.default.hostname'] ?? 'localhost';
    $dbName = $envVars['database.default.database'] ?? '';
    $dbUser = $envVars['database.default.username'] ?? '';
    $dbPass = $envVars['database.default.password'] ?? '';
    $dbPort = $envVars['database.default.port'] ?? '3306';
    
    if ($dbName && $dbUser) {
        try {
            $mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
            if ($mysqli->connect_error) {
                echo "<p class='error'>✗ Database connection failed: " . $mysqli->connect_error . "</p>";
                $errors[] = "Database connection failed. Check credentials in .env";
            } else {
                echo "<p class='ok'>✓ Database connection successful</p>";
                echo "<p>Host: $dbHost | Database: $dbName | User: $dbUser</p>";
                $mysqli->close();
            }
        } catch (Exception $e) {
            echo "<p class='error'>✗ Database error: " . $e->getMessage() . "</p>";
            $errors[] = "Database error: " . $e->getMessage();
        }
    } else {
        echo "<p class='warning'>⚠ Cannot test database - credentials not found in .env</p>";
    }
}

// Check 4: File Permissions
echo "<h2>4. File Permissions</h2>";
$writablePath = dirname(__DIR__) . '/writable';
if (is_writable($writablePath)) {
    echo "<p class='ok'>✓ writable/ directory is writable</p>";
} else {
    echo "<p class='error'>✗ writable/ directory is NOT writable</p>";
    $errors[] = "Set permissions: chmod -R 755 writable/";
}

$subdirs = ['cache', 'logs', 'session', 'uploads'];
foreach ($subdirs as $subdir) {
    $path = $writablePath . '/' . $subdir;
    if (is_dir($path)) {
        if (is_writable($path)) {
            echo "<p class='ok'>✓ writable/$subdir/ is writable</p>";
        } else {
            echo "<p class='error'>✗ writable/$subdir/ is NOT writable</p>";
            $errors[] = "Set permissions: chmod 755 writable/$subdir/";
        }
    } else {
        echo "<p class='warning'>⚠ writable/$subdir/ directory does not exist</p>";
    }
}

// Check 5: Required Files
echo "<h2>5. Required Files</h2>";
$requiredFiles = [
    'public/index.php' => dirname(__DIR__) . '/public/index.php',
    'app/Config/Paths.php' => dirname(__DIR__) . '/app/Config/Paths.php',
    'app/Config/Routes.php' => dirname(__DIR__) . '/app/Config/Routes.php',
    'app/Controllers/User.php' => dirname(__DIR__) . '/app/Controllers/User.php',
    'public/.htaccess' => dirname(__DIR__) . '/public/.htaccess',
];

foreach ($requiredFiles as $name => $path) {
    if (file_exists($path)) {
        echo "<p class='ok'>✓ $name exists</p>";
    } else {
        echo "<p class='error'>✗ $name NOT FOUND</p>";
        $errors[] = "Missing file: $name";
    }
}

// Check 6: Composer Autoload
echo "<h2>6. Composer Autoload</h2>";
$autoloadPath = dirname(__DIR__) . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    echo "<p class='ok'>✓ Composer autoload exists</p>";
} else {
    echo "<p class='warning'>⚠ Composer autoload NOT FOUND</p>";
    $warnings[] = "Run: composer install";
}

// Check 7: CodeIgniter Bootstrap
echo "<h2>7. CodeIgniter Bootstrap Test</h2>";
try {
    // Try to load CodeIgniter
    define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
    require FCPATH . '../app/Config/Paths.php';
    $paths = new Config\Paths();
    
    if (file_exists($paths->systemDirectory . '/Boot.php')) {
        echo "<p class='ok'>✓ CodeIgniter system files found</p>";
    } else {
        echo "<p class='error'>✗ CodeIgniter system files NOT FOUND</p>";
        $errors[] = "CodeIgniter framework files missing";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error loading CodeIgniter: " . $e->getMessage() . "</p>";
    $errors[] = "CodeIgniter bootstrap error: " . $e->getMessage();
}

// Summary
echo "<h2>Summary</h2>";
if (empty($errors) && empty($warnings)) {
    echo "<p class='ok'><strong>✓ All checks passed! Your setup looks good.</strong></p>";
    echo "<p>If you're still getting 500 errors, check the error logs:</p>";
    echo "<pre>tail -f writable/logs/log-*.log</pre>";
} else {
    if (!empty($errors)) {
        echo "<p class='error'><strong>✗ Errors Found:</strong></p>";
        echo "<ul>";
        foreach ($errors as $error) {
            echo "<li class='error'>$error</li>";
        }
        echo "</ul>";
    }
    
    if (!empty($warnings)) {
        echo "<p class='warning'><strong>⚠ Warnings:</strong></p>";
        echo "<ul>";
        foreach ($warnings as $warning) {
            echo "<li class='warning'>$warning</li>";
        }
        echo "</ul>";
    }
}

echo "<hr>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Fix any errors listed above</li>";
echo "<li>Check error logs: <code>writable/logs/log-*.log</code></li>";
echo "<li>Test your API endpoint again</li>";
echo "<li><strong>DELETE THIS FILE</strong> after diagnosis for security</li>";
echo "</ol>";

?>

