<?php
require_once 'config.php';
require_once 'crypto.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$crypto = new Crypto(AES_KEY, PRIVATE_SECRET_KEY);

$response = [
    'status' => 'success',
    'timestamp' => time(),
    'nodes' => [
        ['id' => 'node-1', 'status' => 'online', 'region' => 'US-East'],
        ['id' => 'node-2', 'status' => 'online', 'region' => 'EU-West'],
        ['id' => 'node-3', 'status' => 'online', 'region' => 'ASIA-SG']
    ]
];

$encrypted = $crypto->encrypt($response);
log_request('sync', 'success', []);

echo json_encode($encrypted);
?>
