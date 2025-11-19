<?php
/**
 * Script to activate a student by email
 * Usage: php activate_student.php <email>
 * Example: php activate_student.php stu@edquill.com
 */

// Database configuration (from app/Config/Database.php)
$dbConfig = [
    'hostname' => '127.0.0.1',
    'username' => 'root',
    'password' => 'root',
    'database' => 'edquill_production',
    'port' => 8889
];

$email = $argv[1] ?? null;

if (!$email) {
    echo "Usage: php activate_student.php <email>\n";
    echo "Example: php activate_student.php stu@edquill.com\n";
    exit(1);
}

// Connect to database
$mysqli = new mysqli(
    $dbConfig['hostname'],
    $dbConfig['username'],
    $dbConfig['password'],
    $dbConfig['database'],
    $dbConfig['port']
);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error . "\n");
}

try {
    // Find the user
    $stmt = $mysqli->prepare("SELECT user_id, email_id, role_id, status, school_id FROM user WHERE email_id = ? AND role_id = 5");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if (!$user) {
        echo "Student not found with email: $email\n";
        $mysqli->close();
        exit(1);
    }
    
    echo "Found student: {$user['email_id']} (User ID: {$user['user_id']})\n";
    
    // Get current status from user_profile_details
    $stmt = $mysqli->prepare("SELECT status, school_id FROM user_profile_details WHERE user_id = ?");
    $stmt->bind_param("i", $user['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $profileDetails = $result->fetch_assoc();
    $stmt->close();
    
    if ($profileDetails) {
        echo "Current status in user_profile_details: {$profileDetails['status']}\n";
        
        // Update status to 1 (active)
        $stmt = $mysqli->prepare("UPDATE user_profile_details SET status = 1 WHERE user_id = ?");
        $stmt->bind_param("i", $user['user_id']);
        $stmt->execute();
        $stmt->close();
        
        echo "✓ Updated user_profile_details status to 1 (active)\n";
    } else {
        echo "Warning: No user_profile_details record found. Creating one...\n";
        
        // Get school_id from user table
        $schoolId = $user['school_id'] ?? 1;
        
        $stmt = $mysqli->prepare("INSERT INTO user_profile_details (user_id, school_id, status, created_date) VALUES (?, ?, 1, NOW())");
        $stmt->bind_param("ii", $user['user_id'], $schoolId);
        $stmt->execute();
        $stmt->close();
        
        echo "✓ Created user_profile_details with status 1 (active)\n";
    }
    
    // Also ensure user table status is 1
    if ($user['status'] != 1) {
        $stmt = $mysqli->prepare("UPDATE user SET status = 1, modified_date = NOW() WHERE user_id = ?");
        $stmt->bind_param("i", $user['user_id']);
        $stmt->execute();
        $stmt->close();
        echo "✓ Updated user table status to 1 (active)\n";
    } else {
        echo "✓ User table status is already 1 (active)\n";
    }
    
    echo "\nStudent activated successfully! They should now be able to log in.\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
} finally {
    $mysqli->close();
}
