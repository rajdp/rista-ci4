<?php
// Test script to check ccls school in database
header('Content-Type: application/json');

// Define paths
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
$pathsConfig = FCPATH . '../app/Config/Paths.php';
require_once $pathsConfig;
$paths = new Config\Paths();
require_once dirname(FCPATH) . '/vendor/autoload.php';

try {
    $db = \Config\Database::connect();

    // Check for ccls school
    $school = $db->table('schools')
        ->select('school_id, school_name, school_key, tenant_key, portal_enabled, profile_url, logo')
        ->where('school_key', 'ccls')
        ->orWhere('tenant_key', 'ccls')
        ->get()
        ->getRowArray();

    if ($school) {
        echo json_encode([
            'success' => true,
            'message' => 'School found',
            'school' => $school,
            'note' => 'portal_enabled: ' . ($school['portal_enabled'] ?? 'NULL')
        ], JSON_PRETTY_PRINT);
    } else {
        // Also check case insensitive
        $allSchools = $db->table('schools')
            ->select('school_id, school_name, school_key, tenant_key, portal_enabled')
            ->where('LOWER(school_key)', 'ccls')
            ->orWhere('LOWER(tenant_key)', 'ccls')
            ->get()
            ->getResultArray();

        if (!empty($allSchools)) {
            echo json_encode([
                'success' => true,
                'message' => 'School found (case insensitive)',
                'schools' => $allSchools
            ], JSON_PRETTY_PRINT);
        } else {
            // List all schools to help debug
            $allSchools = $db->table('schools')
                ->select('school_id, school_name, school_key, tenant_key')
                ->limit(10)
                ->get()
                ->getResultArray();

            echo json_encode([
                'success' => false,
                'message' => 'No school found with key or tenant_key = ccls',
                'sample_schools' => $allSchools
            ], JSON_PRETTY_PRINT);
        }
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
