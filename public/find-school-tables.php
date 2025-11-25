<?php
// Find school-related tables
header('Content-Type: application/json');

try {
    $host = 'localhost';
    $username = 'root';
    $password = 'root';

    $pdo = new PDO("mysql:host=$host;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SHOW DATABASES");
    $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $results = [];
    foreach ($databases as $db) {
        if (in_array($db, ['information_schema', 'mysql', 'performance_schema', 'sys'])) {
            continue;
        }

        try {
            $pdo->exec("USE `$db`");
            // Look for any table with 'school' in the name
            $tables = $pdo->query("SHOW TABLES LIKE '%school%'")->fetchAll(PDO::FETCH_COLUMN);
            if (count($tables) > 0) {
                $results[$db] = $tables;
            }
        } catch (Exception $e) {
            // Skip if can't access
        }
    }

    echo json_encode([
        'success' => true,
        'databases_with_school_tables' => $results
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
