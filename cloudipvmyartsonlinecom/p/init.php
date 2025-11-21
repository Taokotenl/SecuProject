<?php
// Initialize database files
define('DB_PATH', __DIR__ . '/data/');

if (!is_dir(DB_PATH)) {
    mkdir(DB_PATH, 0755, true);
}

// Initialize keys.json
$keys_file = DB_PATH . 'keys.json';
if (!file_exists($keys_file)) {
    file_put_contents($keys_file, json_encode([], JSON_PRETTY_PRINT));
    echo "Created keys.json\n";
}

// Initialize scripts.json
$scripts_file = DB_PATH . 'scripts.json';
if (!file_exists($scripts_file)) {
    file_put_contents($scripts_file, json_encode([], JSON_PRETTY_PRINT));
    echo "Created scripts.json\n";
}

// Initialize logs.json
$logs_file = DB_PATH . 'logs.json';
if (!file_exists($logs_file)) {
    file_put_contents($logs_file, json_encode([], JSON_PRETTY_PRINT));
    echo "Created logs.json\n";
}

// Add a sample script
$scripts = json_decode(file_get_contents($scripts_file), true);
if (empty($scripts)) {
    $scripts[] = [
        'id' => 'main-script',
        'code' => 'print("[SDK] Main script loaded successfully!")',
        'version' => '1.0',
        'created_at' => time(),
        'encrypted' => true
    ];
    file_put_contents($scripts_file, json_encode($scripts, JSON_PRETTY_PRINT));
    echo "Added sample script\n";
}

echo "Database initialization complete!\n";
?>
