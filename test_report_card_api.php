<?php

/**
 * Simple test script to verify Report Card API endpoints
 * Run from command line: php test_report_card_api.php
 */

// Configuration
$baseUrl = 'http://localhost:8888/rista_ci4/public';
$testSchoolId = 59;
$testStudentIds = [2488, 2487];

// Test credentials (adjust as needed)
$adminEmail = 'admin@ccls.com';
$adminPassword = '222222'; // CHANGE THIS

echo "=== Report Card API Test ===\n\n";

// Step 1: Login to get token
echo "Step 1: Logging in...\n";
$loginData = json_encode([
    'email' => $adminEmail,
    'password' => $adminPassword
]);

$ch = curl_init($baseUrl . '/user/login');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $loginData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$loginResponse = curl_exec($ch);
$loginHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($loginHttpCode !== 200) {
    echo "ERROR: Login failed (HTTP $loginHttpCode)\n";
    echo "Response: $loginResponse\n";
    echo "\nPlease update the admin password in this script and try again.\n";
    exit(1);
}

$loginData = json_decode($loginResponse, true);
if (!isset($loginData['token'])) {
    echo "ERROR: No token in login response\n";
    echo "Response: $loginResponse\n";
    exit(1);
}

$token = $loginData['token'];
echo "✓ Login successful! Token obtained.\n\n";

// Step 2: Test Scale List
echo "Step 2: Testing /reportcard/scale/list\n";
$scaleData = json_encode(['school_id' => $testSchoolId]);

$ch = curl_init($baseUrl . '/reportcard/scale/list');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $scaleData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accesstoken: ' . $token
]);

$scaleResponse = curl_exec($ch);
$scaleHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $scaleHttpCode\n";
if ($scaleHttpCode === 200) {
    $scaleResult = json_decode($scaleResponse, true);
    if ($scaleResult && isset($scaleResult['Data'])) {
        echo "✓ Success! Found " . count($scaleResult['Data']) . " grading scales:\n";
        foreach ($scaleResult['Data'] as $scale) {
            echo "  - {$scale['name']} (ID: {$scale['scale_id']})\n";
        }
    } else {
        echo "⚠ Response: $scaleResponse\n";
    }
} else {
    echo "✗ Failed\n";
    echo "Response: $scaleResponse\n";
}
echo "\n";

// Step 3: Test Template List
echo "Step 3: Testing /reportcard/template/list\n";
$templateData = json_encode(['school_id' => $testSchoolId]);

$ch = curl_init($baseUrl . '/reportcard/template/list');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $templateData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accesstoken: ' . $token
]);

$templateResponse = curl_exec($ch);
$templateHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $templateHttpCode\n";
if ($templateHttpCode === 200) {
    $templateResult = json_decode($templateResponse, true);
    if ($templateResult && isset($templateResult['Data'])) {
        echo "✓ Success! Found " . count($templateResult['Data']) . " templates:\n";
        foreach ($templateResult['Data'] as $template) {
            echo "  - {$template['name']} (ID: {$template['template_id']}, Version: {$template['version']})\n";
        }
    } else {
        echo "⚠ Response: $templateResponse\n";
    }
} else {
    echo "✗ Failed\n";
    echo "Response: $templateResponse\n";
}
echo "\n";

// Step 4: Test Generate (OPTIONAL - uncomment to test)
/*
echo "Step 4: Testing /reportcard/generate\n";
$generateData = json_encode([
    'template_id' => 1,
    'student_ids' => $testStudentIds,
    'term' => 'Fall 2025',
    'academic_year' => '2025-26'
]);

$ch = curl_init($baseUrl . '/reportcard/generate');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $generateData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accesstoken: ' . $token
]);

$generateResponse = curl_exec($ch);
$generateHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $generateHttpCode\n";
echo "Response: $generateResponse\n";
echo "\n";
*/

echo "=== Test Complete ===\n";
echo "\nNext Steps:\n";
echo "1. If tests passed, the backend is working correctly!\n";
echo "2. Uncomment Step 4 to test report card generation\n";
echo "3. Use the Angular service to integrate with frontend\n";
echo "4. See test_report_cards_api.md for more curl examples\n";
