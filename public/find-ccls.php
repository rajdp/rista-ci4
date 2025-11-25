<?php
// Find ccls school
header('Content-Type: application/json');

try {
    $host = 'localhost';
    $dbname = 'edquill_production';
    $username = 'root';
    $password = 'root';

    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check for ccls school
    $stmt = $pdo->prepare("
        SELECT school_id, name, school_key, portal_domain, profile_url, profile_thumb_url, status, institution_type
        FROM school
        WHERE school_key = :key OR portal_domain LIKE :domain OR name LIKE :name
        LIMIT 5
    ");
    $stmt->execute([
        'key' => 'ccls',
        'domain' => '%ccls%',
        'name' => '%ccls%'
    ]);
    $schools = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($schools) > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Found ' . count($schools) . ' matching school(s)',
            'schools' => $schools
        ], JSON_PRETTY_PRINT);
    } else {
        // List all schools with school_key set
        $stmt = $pdo->query("SELECT school_id, name, school_key, portal_domain FROM school WHERE school_key IS NOT NULL LIMIT 10");
        $allSchools = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => false,
            'message' => 'No school found matching ccls',
            'schools_with_keys' => $allSchools
        ], JSON_PRETTY_PRINT);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
