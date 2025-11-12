<?php
// Simple test to check if auth is working
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:8211');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accesstoken');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$headers = getallheaders();
$token = $headers['Accesstoken'] ?? '';

echo json_encode([
    'IsSuccess' => true,
    'ResponseObject' => [
        'message' => 'Auth test successful',
        'token_received' => !empty($token),
        'token_preview' => substr($token, 0, 20) . '...',
        'timestamp' => date('Y-m-d H:i:s')
    ],
    'ErrorObject' => ''
]);
?>
