<?php
/**
 * Endpoint Migration Checker
 * 
 * This script compares frontend API calls with CI4 routes
 * to identify missing endpoints that need migration.
 */

$frontendEndpointsFile = __DIR__ . '/../../edquill-web_angupgrade/web/tmp/frontend_endpoints_all.txt';
$ci4RoutesFile = __DIR__ . '/../tmp/ci4_routes_all.txt';
$outputFile = __DIR__ . '/../MISSING_ENDPOINTS_REPORT.md';

// Read frontend endpoints
$frontendEndpoints = [];
if (file_exists($frontendEndpointsFile)) {
    $lines = file($frontendEndpointsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (!empty($line)) {
            $frontendEndpoints[] = $line;
        }
    }
}

// Read CI4 routes
$ci4Routes = [];
if (file_exists($ci4RoutesFile)) {
    $lines = file($ci4RoutesFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (!empty($line)) {
            $ci4Routes[] = $line;
        }
    }
}

// Normalize routes for comparison (remove leading/trailing slashes, convert to lowercase)
function normalizeRoute($route) {
    $route = trim($route);
    $route = trim($route, '/');
    return strtolower($route);
}

// Create normalized lookup
$ci4RoutesNormalized = [];
foreach ($ci4Routes as $route) {
    $normalized = normalizeRoute($route);
    $ci4RoutesNormalized[$normalized] = $route;
}

// Find missing endpoints
$missing = [];
$found = [];

foreach ($frontendEndpoints as $endpoint) {
    $normalized = normalizeRoute($endpoint);
    
    // Check exact match
    if (isset($ci4RoutesNormalized[$normalized])) {
        $found[] = $endpoint;
        continue;
    }
    
    // Check if route exists with different format (e.g., student/list vs student::list)
    $parts = explode('/', $normalized);
    $controller = $parts[0] ?? '';
    $method = $parts[1] ?? '';
    
    $foundMatch = false;
    foreach ($ci4RoutesNormalized as $normalizedRoute => $originalRoute) {
        // Check if controller::method format matches
        if (strpos($normalizedRoute, strtolower($controller)) !== false && 
            strpos($normalizedRoute, strtolower($method)) !== false) {
            $found[] = $endpoint . " (matches: $originalRoute)";
            $foundMatch = true;
            break;
        }
    }
    
    if (!$foundMatch) {
        $missing[] = $endpoint;
    }
}

// Categorize missing endpoints
$categorized = [
    'student' => [],
    'teacher' => [],
    'content' => [],
    'class' => [],
    'report' => [],
    'admin' => [],
    'user' => [],
    'common' => [],
    'other' => []
];

foreach ($missing as $endpoint) {
    $parts = explode('/', $endpoint);
    $category = $parts[0] ?? 'other';
    
    if (isset($categorized[$category])) {
        $categorized[$category][] = $endpoint;
    } else {
        $categorized['other'][] = $endpoint;
    }
}

// Generate report
$report = "# Missing Endpoints Migration Report\n\n";
$report .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";
$report .= "## Summary\n\n";
$report .= "- **Total Frontend Endpoints**: " . count($frontendEndpoints) . "\n";
$report .= "- **Total CI4 Routes**: " . count($ci4Routes) . "\n";
$report .= "- **Found/Matching**: " . count($found) . "\n";
$report .= "- **Missing**: " . count($missing) . "\n\n";

$report .= "## Missing Endpoints by Category\n\n";

foreach ($categorized as $category => $endpoints) {
    if (count($endpoints) > 0) {
        $report .= "### " . ucfirst($category) . " (" . count($endpoints) . " missing)\n\n";
        foreach ($endpoints as $endpoint) {
            $report .= "- `$endpoint`\n";
        }
        $report .= "\n";
    }
}

$report .= "## All Missing Endpoints (Alphabetical)\n\n";
sort($missing);
foreach ($missing as $endpoint) {
    $report .= "- `$endpoint`\n";
}

// Save report
file_put_contents($outputFile, $report);

echo "Report generated: $outputFile\n";
echo "Missing endpoints: " . count($missing) . "\n";
echo "Found endpoints: " . count($found) . "\n";








