<?php
/**
 * Login Debug Helper Script
 * 
 * This script helps debug login issues by testing:
 * 1. If user exists in database
 * 2. Password hashing verification
 * 3. User status check
 * 
 * Usage: http://localhost:8888/rista_ci4/public/test_login_debug.php?username=YOUR_EMAIL&password=YOUR_PASSWORD
 */

// Load CodeIgniter
require_once '../vendor/autoload.php';

// Bootstrap CodeIgniter
$path = realpath(FCPATH . '../app/Config/Paths.php');
require $path;
$paths = new Config\Paths();
$bootstrap = rtrim($paths->systemDirectory, '\\/ ') . '/bootstrap.php';
require $bootstrap;

$app = Config\Services::codeigniter();
$app->initialize();

// Get request parameters
$username = $_GET['username'] ?? '';
$password = $_GET['password'] ?? '';

if (empty($username) || empty($password)) {
    die(json_encode([
        'error' => 'Please provide username and password as query parameters',
        'example' => 'test_login_debug.php?username=user@example.com&password=yourpassword'
    ]));
}

// Database connection
$db = \Config\Database::connect();

// Salt used in the system
$salt = 'ristainternational';

echo "<h2>Login Debug Report</h2>";
echo "<hr>";

// Step 1: Check if user exists
echo "<h3>Step 1: User Existence Check</h3>";
$userQuery = "SELECT user_id, email_id, password, role_id, status FROM user WHERE email_id = ?";
$userResult = $db->query($userQuery, [$username])->getResultArray();

if (empty($userResult)) {
    echo "<p style='color: red;'>❌ User with email '<strong>$username</strong>' NOT FOUND in database</p>";
    echo "<p>Please check:</p>";
    echo "<ul>";
    echo "<li>Email is correct</li>";
    echo "<li>User exists in the 'user' table</li>";
    echo "</ul>";
    exit;
}

$user = $userResult[0];
echo "<p style='color: green;'>✅ User FOUND in database</p>";
echo "<pre>";
echo "User ID: " . $user['user_id'] . "\n";
echo "Email: " . $user['email_id'] . "\n";
echo "Role ID: " . $user['role_id'] . "\n";
echo "Status: " . $user['status'] . "\n";
echo "Stored Password Hash: " . substr($user['password'], 0, 20) . "...\n";
echo "</pre>";

// Step 2: Check user status
echo "<h3>Step 2: User Status Check</h3>";
if ($user['status'] != 1) {
    echo "<p style='color: red;'>❌ User status is NOT active (status = {$user['status']})</p>";
    echo "<p>Status codes:</p>";
    echo "<ul>";
    echo "<li>1 = Active</li>";
    echo "<li>2 = Inactive</li>";
    echo "<li>3 = Suspended</li>";
    echo "<li>4 = Deleted</li>";
    echo "</ul>";
} else {
    echo "<p style='color: green;'>✅ User status is ACTIVE</p>";
}

// Step 3: Password verification
echo "<h3>Step 3: Password Verification</h3>";

// Test different password hashing methods
$saltedPassword = $salt . $password . $salt;
$hashedPassword1 = md5($saltedPassword);
$hashedPassword2 = md5($password); // Without salt

echo "<p><strong>Testing password hashing methods:</strong></p>";
echo "<pre>";
echo "Input Password: " . $password . "\n";
echo "Salted Password: " . $saltedPassword . "\n";
echo "\n<strong>Method 1 (With Salt - Current System):</strong>\n";
echo "Hash: " . $hashedPassword1 . "\n";
echo "Match: " . ($hashedPassword1 === $user['password'] ? '✅ YES' : '❌ NO') . "\n";
echo "\n<strong>Method 2 (Without Salt - Old System):</strong>\n";
echo "Hash: " . $hashedPassword2 . "\n";
echo "Match: " . ($hashedPassword2 === $user['password'] ? '✅ YES' : '❌ NO') . "\n";
echo "</pre>";

if ($hashedPassword1 === $user['password']) {
    echo "<p style='color: green;'>✅ PASSWORD CORRECT (using salt method)</p>";
} elseif ($hashedPassword2 === $user['password']) {
    echo "<p style='color: orange;'>⚠️ PASSWORD CORRECT but using OLD HASHING METHOD (without salt)</p>";
    echo "<p>The password is stored without salt. The system expects salted passwords.</p>";
    echo "<p><strong>Fix:</strong> Update the password using the change password feature or reset it with proper salt.</p>";
} else {
    echo "<p style='color: red;'>❌ PASSWORD INCORRECT</p>";
    echo "<p>The password you entered does not match the stored hash.</p>";
}

// Step 4: Check user_profile_details
echo "<h3>Step 4: User Profile Details Check</h3>";
$profileQuery = "SELECT upd.school_id, upd.status, upd.individual_teacher 
                 FROM user_profile_details upd 
                 WHERE upd.user_id = ?";
$profileResult = $db->query($profileQuery, [$user['user_id']])->getResultArray();

if (empty($profileResult)) {
    echo "<p style='color: orange;'>⚠️ No profile details found (may be normal for some roles)</p>";
} else {
    $profile = $profileResult[0];
    echo "<p style='color: green;'>✅ Profile details found</p>";
    echo "<pre>";
    echo "School ID: " . ($profile['school_id'] ?? 'NULL') . "\n";
    echo "Profile Status: " . ($profile['status'] ?? 'NULL') . "\n";
    echo "Individual Teacher: " . ($profile['individual_teacher'] ?? 'NULL') . "\n";
    echo "</pre>";
}

// Summary
echo "<hr>";
echo "<h3>Summary & Next Steps</h3>";

$canLogin = true;
$issues = [];

if ($user['status'] != 1) {
    $canLogin = false;
    $issues[] = "User status is not active";
}

if ($hashedPassword1 !== $user['password'] && $hashedPassword2 !== $user['password']) {
    $canLogin = false;
    $issues[] = "Password does not match";
}

if ($canLogin) {
    echo "<p style='color: green; font-weight: bold;'>✅ User CAN LOGIN - All checks passed!</p>";
    echo "<p>If login still fails, check:</p>";
    echo "<ul>";
    echo "<li>Network/API connection</li>";
    echo "<li>Browser console for JavaScript errors</li>";
    echo "<li>Backend server logs</li>";
    echo "</ul>";
} else {
    echo "<p style='color: red; font-weight: bold;'>❌ User CANNOT LOGIN</p>";
    echo "<p><strong>Issues found:</strong></p>";
    echo "<ul>";
    foreach ($issues as $issue) {
        echo "<li>" . $issue . "</li>";
    }
    echo "</ul>";
}

echo "<hr>";
echo "<p><small>Script Location: /Applications/MAMP/htdocs/rista_ci4/public/test_login_debug.php</small></p>";
echo "<p><small>Delete this file after debugging for security reasons.</small></p>";






