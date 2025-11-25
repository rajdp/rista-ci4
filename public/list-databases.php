<?php
// List available databases
header('Content-Type: application/json');

try {
    $host = 'localhost';
    $username = 'root';
    $password = 'root';

    $pdo = new PDO("mysql:host=$host;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SHOW DATABASES");
    $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // For each database, check if it has a schools table
    $dbWithSchools = [];
    foreach ($databases as $db) {
        if (in_array($db, ['information_schema', 'mysql', 'performance_schema', 'sys'])) {
            continue;
        }

        try {
            $pdo->exec("USE `$db`");
            $tables = $pdo->query("SHOW TABLES LIKE 'schools'")->fetchAll();
            if (count($tables) > 0) {
                // Count schools in this database
                $count = $pdo->query("SELECT COUNT(*) FROM schools")->fetchColumn();
                $dbWithSchools[] = [
                    'database' => $db,
                    'schools_count' => $count
                ];
            }
        } catch (Exception $e) {
            // Skip if can't access
        }
    }

    echo json_encode([
        'success' => true,
        'all_databases' => $databases,
        'databases_with_schools' => $dbWithSchools
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
