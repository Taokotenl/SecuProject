<?php
require_once 'config.php';
require_once 'crypto.php';
require_once 'database.php';

header('Content-Type: application/json');

// Block browser access
if (empty($_SERVER['HTTP_X_ROBLOX_SDK'])) {
    http_response_code(403);
    echo json_encode(['error' => 'BLOCKED_BROWSER_ACCESS']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$crypto = new Crypto(AES_KEY, PRIVATE_SECRET_KEY);
$db = new Database(DB_TYPE, DB_PATH);

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if ($db->checkBan($ip)) {
    http_response_code(403);
    echo json_encode(['error' => 'BANNED']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$encrypted_data = $input['cipher'] ?? null;
$timestamp = $input['timestamp'] ?? null;
$nonce = $input['nonce'] ?? null;
$signature = $input['signature'] ?? null;

if (!$encrypted_data || !$timestamp || !$nonce || !$signature) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

$request_data = $crypto->decrypt($encrypted_data, $timestamp, $nonce);

if (!$request_data || !$crypto->verifySignature($signature, $timestamp, $nonce, $request_data)) {
    $db->recordViolation($ip);
    http_response_code(403);
    log_request('loadscript', 'failed', ['reason' => 'auth_failed']);
    echo json_encode(['error' => 'Authentication failed']);
    exit;
}

$verification_token = $request_data['verification_token'] ?? null;
if (!$verification_token) {
    $db->recordViolation($ip);
    http_response_code(403);
    echo json_encode(['error' => 'Missing verification token']);
    exit;
}

$token_json = $crypto->decrypt(base64_decode($verification_token));
$token_data = json_decode($token_json, true);

if (!$token_data || $token_data['ip'] !== $ip || (time() - $token_data['ts'] > 60)) {
    $db->recordViolation($ip);
    http_response_code(403);
    echo json_encode(['error' => 'Invalid or expired verification token']);
    exit;
}

$script_id = $request_data['script_id'] ?? null;
$key = $request_data['key'] ?? null;

if (!$script_id || !$key) {
    $db->recordViolation($ip);
    http_response_code(400);
    echo json_encode(['error' => 'Missing script_id or key']);
    exit;
}

// Verify key first
$key_data = $db->getKey($key);
if (!$key_data) {
    $db->recordViolation($ip);
    http_response_code(403);
    echo json_encode(['error' => 'Invalid key']);
    exit;
}

// Get script
$script = $db->getScript($script_id);
if (!$script) {
    http_response_code(404);
    log_request('loadscript', 'failed', ['reason' => 'script_not_found']);
    echo json_encode(['error' => 'Script not found']);
    exit;
}

$response = [
    'status' => 'success',
    'script' => $script['code'],
    'version' => $script['version'],
    'encrypted' => true
];

$encrypted = $crypto->encrypt($response);
log_request('loadscript', 'success', ['script_id' => $script_id]);

echo json_encode($encrypted);
?>
