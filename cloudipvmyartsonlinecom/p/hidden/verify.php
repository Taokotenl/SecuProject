<?php
require_once '../config.php';
require_once '../crypto.php';

header('Content-Type: application/json');

$crypto = new Crypto(AES_KEY, PRIVATE_SECRET_KEY);

$input = json_decode(file_get_contents('php://input'), true);

$encrypted_data = $input['cipher'] ?? null;
$timestamp = $input['timestamp'] ?? null;
$nonce = $input['nonce'] ?? null;
$signature = $input['signature'] ?? null;

if (!$encrypted_data || !$timestamp || !$nonce || !$signature) {
    http_response_code(400);
    echo json_encode(['verified' => false, 'error' => 'Invalid request']);
    exit;
}

$request_data = $crypto->decrypt($encrypted_data, $timestamp, $nonce);

if (!$request_data || !$crypto->verifySignature($signature, $timestamp, $nonce, $request_data)) {
    http_response_code(403);
    log_request('verify', 'failed', ['reason' => 'verification_failed']);
    echo json_encode(['verified' => false]);
    exit;
}

$sdk_version = $request_data['sdk_version'] ?? null;
$sdk_sig = $request_data['sdk_sig'] ?? null;
$verify_salt = $request_data['verify_salt'] ?? null;

$expected_sig = "VX2025ANTI";
$expected_salt = "vexnet_secure_2025";

if ($sdk_sig !== $expected_sig || $verify_salt !== $expected_salt) {
    http_response_code(403);
    log_request('verify', 'failed', ['reason' => 'invalid_sdk_signature']);
    echo json_encode(['verified' => false]);
    exit;
}

$server_timestamp = time();
$server_sig_raw = $expected_sig . $server_timestamp . $expected_salt;
$server_signature = base64_encode($server_sig_raw);

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$token_payload = json_encode([
    'ip' => $ip,
    'ts' => $server_timestamp,
    'salt' => $expected_salt
]);
$verification_token = base64_encode($crypto->encrypt($token_payload));

log_request('verify', 'success', ['sdk_version' => $sdk_version]);

echo json_encode([
    'verified' => true,
    'timestamp' => $server_timestamp,
    'server_signature' => $server_signature,
    'verification_token' => $verification_token // Send token to client
]);
?>
