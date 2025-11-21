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

// Get request data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    log_request('getkey', 'failed', ['reason' => 'invalid_input']);
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$ttl = $input['ttl'] ?? 86400;
$max_uses = $input['max_uses'] ?? 5;
$signature = $input['signature'] ?? null;
$timestamp = $input['timestamp'] ?? null;

// Verify if signature is provided
if ($signature !== null && !$crypto->verifyRequest($input, $signature, $timestamp)) {
    http_response_code(403);
    log_request('getkey', 'failed', ['reason' => 'signature_mismatch']);
    echo json_encode(['success' => false, 'error' => 'Signature verification failed']);
    exit;
}

// Create key
$new_key = $db->createKey($ttl, $max_uses);
$token = $crypto->generateToken($new_key);

$response = [
    'success' => true,
    'status' => 'success',
    'key' => $new_key,
    'token' => $token,
    'ttl' => $ttl,
    'max_uses' => $max_uses,
    'timestamp' => time()
];

log_request('getkey', 'success', ['key' => $new_key]);

echo json_encode($response);
?>
