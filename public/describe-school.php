<?php
// Describe school table structure
header('Content-Type: application/json');

try {
    $host = 'localhost';
    $dbname = 'edquill_production';
    $username = 'root';
    $password = 'root';

    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("DESCRIBE school");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Also get first row as sample
    $sample = $pdo->query("SELECT * FROM school LIMIT 1")->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'columns' => $columns,
        'sample_row' => $sample,
        'column_names' => array_column($columns, 'Field')
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
