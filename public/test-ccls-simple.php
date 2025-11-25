<?php
// Simple test script to check ccls school in database
header('Content-Type: application/json');

try {
    // Connect to database using PDO
    $host = 'localhost';
    $dbname = 'edquill_production';
    $username = 'root';
    $password = 'root';

    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check for ccls school
    $stmt = $pdo->prepare("
        SELECT school_id, school_name, school_key, tenant_key, portal_enabled, profile_url, logo
        FROM school
        WHERE school_key = :key OR tenant_key = :key2
        LIMIT 1
    ");
    $stmt->execute(['key' => 'ccls', 'key2' => 'ccls']);
    $school = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($school) {
        echo json_encode([
            'success' => true,
            'message' => 'School found',
            'school' => $school,
            'note' => 'portal_enabled value: ' . ($school['portal_enabled'] ?? 'NULL')
        ], JSON_PRETTY_PRINT);
    } else {
        // List all schools to help debug
        $stmt = $pdo->query("SELECT school_id, school_name, school_key, tenant_key, portal_enabled FROM school LIMIT 10");
        $allSchools = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => false,
            'message' => 'No school found with key or tenant_key = ccls',
            'sample_schools' => $allSchools
        ], JSON_PRETTY_PRINT);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
