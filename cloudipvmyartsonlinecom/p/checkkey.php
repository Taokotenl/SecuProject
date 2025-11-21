<?php
require_once 'config.php';
require_once 'crypto.php';
require_once 'database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$crypto = new Crypto(PRIVATE_SECRET_KEY);
$db = new Database(DB_PATH, DB_TYPE);

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    log_request('checkkey', 'failed', ['reason' => 'invalid_input']);
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$key_value = $input['key'] ?? null;
$signature = $input['signature'] ?? null;
$timestamp = $input['timestamp'] ?? null;

if (!$key_value) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Key not provided']);
    exit;
}

if ($signature !== null && !$crypto->verifyRequest($input, $signature, $timestamp)) {
    http_response_code(403);
    log_request('checkkey', 'failed', ['reason' => 'auth_failed']);
    echo json_encode(['success' => false, 'error' => 'Authentication failed']);
    exit;
}

$key = $db->getKey($key_value);

if (!$key) {
    http_response_code(404);
    log_request('checkkey', 'failed', [
        'reason' => 'key_not_found', 
        'key' => $key_value,
        'db_path' => DB_PATH,
        'file_exists' => file_exists(DB_PATH . 'keys.json')
    ]);
    echo json_encode(['success' => false, 'error' => 'Key not found or expired']);
    exit;
}

if ($key['uses'] >= $key['max_uses']) {
    http_response_code(403);
    log_request('checkkey', 'failed', ['reason' => 'max_uses_exceeded']);
    echo json_encode(['success' => false, 'error' => 'Key usage limit exceeded']);
    exit;
}

$db->incrementKeyUses($key_value);

$response = [
    'success' => true,
    'status' => 'success',
    'valid' => true,
    'uses_remaining' => $key['max_uses'] - ($key['uses'] + 1),
    'key' => $key_value,
    'timestamp' => time()
];

log_request('checkkey', 'success', ['key' => $key_value]);

echo json_encode($response);
?>
