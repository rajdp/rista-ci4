<?php
/**
 * Test Missing Endpoints
 * 
 * Tests each missing endpoint to see if it exists in CI4
 */

// Don't require Routes.php - just parse it as text

$missingEndpoints = [
    'admin/student-class/add',
    'admin/student-class/get-active-classes-for-course',
    'admin/student-class/get-class-courses',
    'admin/student-class/get-student-courses',
    'admin/student-class/remove',
    'admin/student-class/update',
    'admin/student-courses/update',
    'admin/student-courses/update-status',
    'admin/student-custom-items/add',
    'admin/student-custom-items/delete',
    'admin/student-custom-items/list',
    'admin/student-custom-items/update',
    'classes/addAnnouncementComments',
    'classes/classAddNotes',
    'classes/getAnnouncementComments',
    'classes/getClassNotes',
    'classes/list',
    'classes/updateComments',
    'content/addStudentAnswer',
    'content/contentDetail',
    'content/listPassage',
    'content/questionSkill',
    'content/testType',
    'course/list',
    'mailbox/listMessages',
    'mailbox/sendMessage',
    'mailbox/update',
    'school/announcementList',
    'student/getCurrentDateTime',
    'student/getModuleSubject',
    'student/updateContentStartTime',
    'user/logout',
];

// Read CI4 routes
$routesFile = __DIR__ . '/../app/Config/Routes.php';
$routesContent = file_get_contents($routesFile);

$existingRoutes = [];
preg_match_all("/routes->(?:post|get|put|delete|match)\(['\"]([^'\"]+)['\"]/", $routesContent, $matches);
if (!empty($matches[1])) {
    $existingRoutes = $matches[1];
}

echo "=== ENDPOINT MIGRATION STATUS ===\n\n";
echo "Total missing endpoints to check: " . count($missingEndpoints) . "\n";
echo "Total CI4 routes found: " . count($existingRoutes) . "\n\n";

$actuallyMissing = [];
$found = [];

foreach ($missingEndpoints as $endpoint) {
    $foundRoute = false;
    
    // Check exact match
    if (in_array($endpoint, $existingRoutes)) {
        $found[] = $endpoint;
        $foundRoute = true;
    } else {
        // Check partial matches (controller::method format)
        $parts = explode('/', $endpoint);
        $controller = $parts[0] ?? '';
        $method = $parts[1] ?? '';
        
        foreach ($existingRoutes as $route) {
            // Check if route contains controller and method
            if (stripos($route, $controller) !== false && 
                (empty($method) || stripos($route, $method) !== false)) {
                $found[] = "$endpoint (similar to: $route)";
                $foundRoute = true;
                break;
            }
        }
    }
    
    if (!$foundRoute) {
        $actuallyMissing[] = $endpoint;
    }
}

echo "=== FOUND/MATCHED ===\n";
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

// Save to file
$report = "# Actually Missing Endpoints\n\n";
$report .= "Total: " . count($actuallyMissing) . "\n\n";
foreach ($actuallyMissing as $endpoint) {
    $report .= "- `$endpoint`\n";
}

file_put_contents(__DIR__ . '/../ACTUALLY_MISSING_ENDPOINTS.md', $report);
echo "\nReport saved to: ACTUALLY_MISSING_ENDPOINTS.md\n";

