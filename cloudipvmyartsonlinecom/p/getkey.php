<?php
require_once 'config.php';
require_once 'crypto.php';
require_once 'database.php';

header('Content-Type: application/json');

// Maintenance check
$maintenance_file = DB_PATH . 'maintenance.json';
if (file_exists($maintenance_file)) {
    $m = json_decode(file_get_contents($maintenance_file), true) ?? ['enabled'=>false];
    if (!empty($m['enabled'])) {
        http_response_code(503);
        echo json_encode(['success' => false, 'error' => 'MAINTENANCE']);
        exit;
    }
}

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

$ttl = $input['ttl'] ?? ($_GET['ttl'] ?? 86400);
$max_uses = $input['max_uses'] ?? ($_GET['max_uses'] ?? 5);
$signature = $input['signature'] ?? null;
$timestamp = $input['timestamp'] ?? null;
$uuid = $input['uuid'] ?? ($_GET['uuid'] ?? null);

// Verify if signature is provided
if ($signature !== null && !$crypto->verifyRequest($input, $signature, $timestamp)) {
    http_response_code(403);
    log_request('getkey', 'failed', ['reason' => 'signature_mismatch']);
    echo json_encode(['success' => false, 'error' => 'Signature verification failed']);
    exit;
}

// Create key
$new_key = $db->createKey($ttl, $max_uses, $uuid ?? '');
$token = $crypto->generateToken($new_key);

$response = [
    'success' => true,
    'status' => 'success',
    'key' => $new_key,
    'token' => $token,
    'ttl' => $ttl,
    'max_uses' => $max_uses,
    'uuid' => $uuid ?? null,
    'make_url' => (isset($_SERVER['HTTP_HOST']) ? (isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'https') . '://' . $_SERVER['HTTP_HOST'] : '') . dirname($_SERVER['REQUEST_URI']) . '/makekey.php?uuid=' . urlencode($uuid ?? $new_key),
    'timestamp' => time()
];

log_request('getkey', 'success', ['key' => $new_key]);

echo json_encode($response);
?>
