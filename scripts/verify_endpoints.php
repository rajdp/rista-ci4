<?php
/**
 * Verify Missing Endpoints
 * Checks if endpoints actually exist in CI4 routes
 */

$routesFile = __DIR__ . '/../app/Config/Routes.php';
$routesContent = file_get_contents($routesFile);

$missingEndpoints = [
    'classes/addAnnouncementComments',
    'classes/getAnnouncementComments',
    'classes/updateComments',
    'content/questionSkill',
    'mailbox/sendMessage',
    'mailbox/update',
    'student/addCategory',
    'student/editCategory',
    'student/getCurrentDateTime',
    'student/getModuleSubject',
    'student/getOpenAiFeedback',
    'student/googleRegister',
    'student/listCategory',
    'student/updateContentStartTime',
    'studentlogin/class-detail',
    'user/googleAuthenticate',
];

echo "=== ENDPOINT VERIFICATION ===\n\n";

$actuallyMissing = [];
$found = [];

foreach ($missingEndpoints as $endpoint) {
    // Check for exact match
    $exactMatch = (strpos($routesContent, "'$endpoint'") !== false || 
                   strpos($routesContent, "\"$endpoint\"") !== false);
    
    // Check for controller::method format
    $parts = explode('/', $endpoint);
    $controller = ucfirst($parts[0] ?? '');
    $method = $parts[1] ?? '';
    
    $controllerMethodMatch = false;
    if (!empty($controller) && !empty($method)) {
        // Convert camelCase to method name
        $methodCamel = lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $method))));
        $controllerMethodMatch = (stripos($routesContent, "$controller::$method") !== false ||
                                  stripos($routesContent, "$controller::$methodCamel") !== false);
    }
    
    if ($exactMatch || $controllerMethodMatch) {
        $found[] = $endpoint;
    } else {
        $actuallyMissing[] = $endpoint;
    }
}

echo "=== FOUND (Exist in CI4) ===\n";
foreach ($found as $endpoint) {
    echo "✓ $endpoint\n";
}

echo "\n=== ACTUALLY MISSING ===\n";
foreach ($actuallyMissing as $endpoint) {
    echo "✗ $endpoint\n";
}

echo "\n=== SUMMARY ===\n";
echo "Found: " . count($found) . "\n";
echo "Missing: " . count($actuallyMissing) . "\n";

// Generate detailed report
$report = "# Actually Missing Endpoints - Migration Required\n\n";
$report .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";
$report .= "## Summary\n\n";
$report .= "- **Total Checked:** " . count($missingEndpoints) . "\n";
$report .= "- **Found in CI4:** " . count($found) . "\n";
$report .= "- **Actually Missing:** " . count($actuallyMissing) . "\n\n";

$report .= "## Missing Endpoints by Category\n\n";

$categories = [];
foreach ($actuallyMissing as $endpoint) {
    $parts = explode('/', $endpoint);
    $cat = $parts[0] ?? 'other';
    if (!isset($categories[$cat])) {
        $categories[$cat] = [];
    }
    $categories[$cat][] = $endpoint;
}

foreach ($categories as $cat => $endpoints) {
    $report .= "### " . ucfirst($cat) . " (" . count($endpoints) . ")\n\n";
    foreach ($endpoints as $ep) {
        $report .= "- `$ep`\n";
    }
    $report .= "\n";
}

$report .= "## Migration Priority\n\n";
$report .= "### High Priority (Core Functionality)\n";
$highPriority = ['student/updateContentStartTime', 'mailbox/sendMessage', 'mailbox/update', 
                 'classes/addAnnouncementComments', 'classes/getAnnouncementComments', 'classes/updateComments'];
foreach ($highPriority as $ep) {
    if (in_array($ep, $actuallyMissing)) {
        $report .= "- `$ep`\n";
    }
}

$report .= "\n### Medium Priority\n";
$mediumPriority = ['content/questionSkill', 'student/getCurrentDateTime', 'student/getModuleSubject'];
foreach ($mediumPriority as $ep) {
    if (in_array($ep, $actuallyMissing)) {
        $report .= "- `$ep`\n";
    }
}

$report .= "\n### Low Priority (Can use proxy)\n";
$lowPriority = ['student/googleRegister', 'user/googleAuthenticate', 'student/getOpenAiFeedback',
                'student/addCategory', 'student/editCategory', 'student/listCategory'];
foreach ($lowPriority as $ep) {
    if (in_array($ep, $actuallyMissing)) {
        $report .= "- `$ep`\n";
    }
}

file_put_contents(__DIR__ . '/../MISSING_ENDPOINTS_FINAL.md', $report);
echo "\nReport saved to: MISSING_ENDPOINTS_FINAL.md\n";

