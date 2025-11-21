<?php
// Private Backend Configuration
define('PRIVATE_SECRET_KEY', 'your-secret-key-change-this-' . hash('sha256', 'production'));
define('AES_KEY', base64_encode(hash('sha256', 'your-aes-key-change-this', true)));
define('ALLOWED_PUBLIC_IPS', ['*']); // Configure in production
define('TIME_TOLERANCE', 3); // 3 seconds
define('NONCE_EXPIRY', 300); // 5 minutes
define('DB_TYPE', 'json'); // 'json' or 'sqlite'
define('DB_PATH', __DIR__ . '/data/');

// Initialize directories
if (!is_dir(DB_PATH)) {
    mkdir(DB_PATH, 0755, true);
}

// Logging function
function log_request($action, $status, $details = []) {
    $log_file = DB_PATH . 'logs.json';
    $logs = file_exists($log_file) ? json_decode(file_get_contents($log_file), true) ?? [] : [];
    
    $logs[] = [
        'timestamp' => time(),
        'action' => $action,
        'status' => $status,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'details' => $details
    ];
    
    file_put_contents($log_file, json_encode($logs, JSON_PRETTY_PRINT));
}

// Set CORS headers for proxy requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Signature, X-Timestamp, X-Nonce');
header('Content-Type: application/json');
?>
