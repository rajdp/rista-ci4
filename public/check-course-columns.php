<?php
header('Content-Type: application/json');

try {
    $pdo = new PDO('mysql:host=localhost;dbname=edquill_production', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query('DESCRIBE tbl_course');
    $allColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $relevantColumns = [];
    foreach ($allColumns as $col) {
        $field = $col['Field'];
        if (stripos($field, 'fee') !== false ||
            stripos($field, 'discount') !== false ||
            stripos($field, 'cost') !== false ||
            stripos($field, 'amount') !== false) {
            $relevantColumns[] = $col;
        }
    }

    echo json_encode([
        'success' => true,
        'relevant_columns' => $relevantColumns,
        'all_columns' => array_column($allColumns, 'Field')
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
