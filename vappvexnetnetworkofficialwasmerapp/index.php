<?php
// Public Proxy & Frontend - Deploy to https://vapp-vexnetnetworkofficial.wasmer.app/

define('PRIVATE_BACKEND', 'http://cloudipv.myartsonline.com/p/');
define('ALLOWED_ACTIONS', ['sync', 'getkey', 'checkkey', 'loadscript', 'verify']);

// Simple Router
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);

if ($path === '/sdk' || $path === '/sdk.lua') {
    header('Content-Type: text/plain; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    
    $sdk_content = file_get_contents(__DIR__ . '/roblox-sdk/sdk.lua');
    if ($sdk_content === false) {
        http_response_code(404);
        echo '-- SDK file not found';
        exit;
    }
    
    echo $sdk_content;
    exit;
}

if ($path === '/loader' || $path === '/loader.lua') {
    header('Content-Type: text/plain; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    
    $loader_content = file_get_contents(__DIR__ . '/roblox-sdk/loader.lua');
    if ($loader_content === false) {
        http_response_code(404);
        echo '-- Loader file not found';
        exit;
    }
    
    echo $loader_content;
    exit;
}

if ($path === '/library' || $path === '/library.lua') {
    header('Content-Type: text/plain; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    
    $library_content = file_get_contents(__DIR__ . '/roblox-sdk/library.lua');
    if ($library_content === false) {
        http_response_code(404);
        echo '-- Library file not found';
        exit;
    }
    
    echo $library_content;
    exit;
}

if ($path === '/ui' || $path === '/ui.lua') {
    header('Content-Type: text/plain; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    
    $ui_content = file_get_contents(__DIR__ . '/examples/key-login-example.lua');
    if ($ui_content === false) {
        http_response_code(404);
        echo '-- UI file not found';
        exit;
    }
    
    echo $ui_content;
    exit;
}

if ($path === '/makekey') {
    // Check if user came from Linkvertise
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    if (strpos($referer, 'linkvertise.com') === false) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied. Please use the correct link.']);
        exit;
    }
    
    // Make key via backend
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, PRIVATE_BACKEND . 'getkey.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['ttl' => 86400, 'max_uses' => 5]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'User-Agent: VexNetProxy/1.0'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if ($data && $data['success']) {
            $key = $data['key'];
            // Serve key page with copy button and URL manipulation
            ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Key - VexNet</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0a0e27 0%, #1a1f3a 100%);
            color: #e0e0e0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: rgba(26, 31, 58, 0.9);
            border: 2px solid #00d4ff;
            border-radius: 16px;
            padding: 3rem;
            max-width: 500px;
            width: 90%;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 212, 255, 0.3);
        }
        h1 {
            color: #00d4ff;
            margin-bottom: 1.5rem;
            font-size: 2rem;
        }
        .key-display {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #00d4ff;
            border-radius: 8px;
            padding: 1.5rem;
            margin: 2rem 0;
            font-family: monospace;
            font-size: 1.2rem;
            color: #00ff00;
            word-break: break-all;
        }
        .copy-btn {
            background: #00d4ff;
            color: #0a0e27;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
        }
        .copy-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 212, 255, 0.5);
        }
        .copy-btn.copied {
            background: #00ff00;
        }
        .info {
            margin-top: 1.5rem;
            color: #a0a0a0;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎉 Your Key is Ready!</h1>
        <p style="color: #a0a0a0; margin-bottom: 1rem;">Copy your key below:</p>
        <div class="key-display" id="keyDisplay"><?php echo htmlspecialchars($key); ?></div>
        <button class="copy-btn" id="copyBtn" onclick="copyKey()">📋 Copy Key</button>
        <div class="info">
            <p>⏰ Valid for 24 hours</p>
            <p>🔢 Maximum 5 uses</p>
        </div>
    </div>
    
    <script>
        const key = "<?php echo $key; ?>";
        
        // Immediately change URL to /loading
        history.replaceState(null, '', '/loading');
        
        // After 500ms, change to /key/{key}
        setTimeout(() => {
            history.replaceState(null, '', '/key/' + key);
        }, 500);
        
        function copyKey() {
            navigator.clipboard.writeText(key).then(() => {
                const btn = document.getElementById('copyBtn');
                btn.textContent = '✓ Copied!';
                btn.classList.add('copied');
                
                setTimeout(() => {
                    btn.textContent = '📋 Copy Key';
                    btn.classList.remove('copied');
                }, 2000);
            });
        }
    </script>
</body>
</html>
            <?php
            exit;
        }
    }
    
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to generate key']);
    exit;
}

// Check if request is for the API (starts with /api/)
if (strpos($path, '/api/') !== false) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        exit(0);
    }

    $path_parts = explode('/', trim($path, '/'));
    $action = end($path_parts);

    if (!in_array($action, ALLOWED_ACTIONS)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        exit;
    }

    $postData = file_get_contents('php://input');

    $endpoint = PRIVATE_BACKEND . $action . '.php';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'User-Agent: VexNetProxy/1.0'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($response === false) {
        http_response_code(502);
        echo json_encode(['success' => false, 'error' => 'Backend unavailable: ' . curl_error($ch)]);
        curl_close($ch);
        exit;
    }
    
    curl_close($ch);

    http_response_code($httpCode);
    echo $response;
    exit;
}

// If not an API request, serve the HTML Frontend
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Key System - Secure Access</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary: #0a0e27;
            --secondary: #1a1f3a;
            --accent: #00d4ff;
            --text: #e0e0e0;
            --text-secondary: #a0a0a0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: var(--text);
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        .navbar {
            background: rgba(10, 14, 39, 0.95);
            border-bottom: 2px solid var(--accent);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--accent);
            letter-spacing: 2px;
        }
        
        .navbar-nav {
            display: flex;
            gap: 2rem;
            list-style: none;
        }
        
        .navbar-nav a {
            color: var(--text);
            text-decoration: none;
            cursor: pointer;
            transition: color 0.3s;
        }
        
        .navbar-nav a:hover, .navbar-nav a.active {
            color: var(--accent);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .section {
            display: none;
            animation: fadeIn 0.5s ease-in;
        }
        
        .section.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .card {
            background: rgba(26, 31, 58, 0.8);
            border: 1px solid rgba(0, 212, 255, 0.3);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            backdrop-filter: blur(10px);
        }
        
        .card-title {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--accent);
        }
        
        .input-group {
            margin-bottom: 1rem;
        }
        
        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-secondary);
        }
        
        .input-group input,
        .input-group textarea,
        .input-group select {
            width: 100%;
            padding: 0.75rem;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid var(--accent);
            border-radius: 6px;
            color: var(--text);
            font-family: monospace;
        }
        
        .input-group input:focus,
        .input-group textarea:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 10px rgba(0, 212, 255, 0.5);
        }
        
        .button-group {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .btn {
            flex: 1;
            padding: 0.75rem 1.5rem;
            background: var(--accent);
            color: var(--primary);
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 212, 255, 0.4);
        }
        
        .btn-secondary {
            background: transparent;
            border: 2px solid var(--accent);
            color: var(--accent);
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .result-box {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid var(--accent);
            border-radius: 6px;
            padding: 1rem;
            margin-top: 1rem;
            font-family: monospace;
            font-size: 0.9rem;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .result-box.success {
            border-color: #00ff00;
            color: #00ff00;
        }
        
        .result-box.error {
            border-color: #ff0000;
            color: #ff6b6b;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .stat-card {
            background: rgba(0, 212, 255, 0.1);
            border: 1px solid var(--accent);
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            color: var(--accent);
            font-weight: bold;
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        
        .loading {
            display: inline-block;
            width: 8px;
            height: 8px;
            background: var(--accent);
            border-radius: 50%;
            animation: pulse 1.5s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
        
        .footer {
            text-align: center;
            padding: 2rem;
            color: var(--text-secondary);
            border-top: 1px solid rgba(0, 212, 255, 0.2);
            margin-top: 3rem;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">KEY SYSTEM</div>
        <ul class="navbar-nav">
            <li><a class="nav-link active" onclick="showSection('getkey')">Get Key</a></li>
            <li><a class="nav-link" onclick="showSection('checkkey')">Check Key</a></li>
            <li><a class="nav-link" onclick="showSection('console')">Console</a></li>
        </ul>
    </nav>
    
    <div class="container">
        <!-- Get Key Section -->
        <section id="getkey" class="section active">
            <div class="card">
                <div class="card-title">Generate Access Key</div>
                <p style="margin-bottom: 1.5rem; color: var(--text-secondary);">Create a new key with custom TTL and usage limits.</p>
                
                <div class="input-group">
                    <label>Key TTL (seconds)</label>
                    <input type="number" id="ttl" value="86400" min="3600" step="3600">
                    <small style="color: var(--text-secondary);">Default: 24 hours</small>
                </div>
                
                <div class="input-group">
                    <label>Max Uses</label>
                    <input type="number" id="maxUses" value="5" min="1" max="100">
                </div>
                
                <div class="button-group">
                    <button class="btn" onclick="generateKey()">Generate Key</button>
                    <!-- Added copy button for generated key -->
                    <button class="btn btn-secondary" id="copyKeyBtn" onclick="copyGeneratedKey()" style="display: none;">Copy Key</button>
                </div>
                
                <div id="getkey-result" class="result-box" style="display: none;"></div>
            </div>
        </section>
        
        <!-- Check Key Section -->
        <section id="checkkey" class="section">
            <div class="card">
                <div class="card-title">Validate Key</div>
                <p style="margin-bottom: 1.5rem; color: var(--text-secondary);">Check if a key is valid and get usage information.</p>
                
                <div class="input-group">
                    <label>Enter Key</label>
                    <input type="text" id="checkKeyInput" placeholder="XXXX-XXXX-XXXX">
                </div>
                
                <div class="button-group">
                    <button class="btn" onclick="checkKey()">Validate</button>
                </div>
                
                <div id="checkkey-result" class="result-box" style="display: none;"></div>
            </div>
        </section>
        
        <!-- Console Section -->
        <section id="console" class="section">
            <div class="card">
                <div class="card-title">System Console</div>
                <div id="console-output" class="result-box" style="height: 400px;">
                    <div>[Loader] System initialized...</div>
                </div>
                <div class="button-group">
                    <button class="btn-secondary" onclick="clearConsole()" style="border-color: var(--text-secondary); color: var(--text-secondary);">Clear Console</button>
                </div>
            </div>
        </section>
    </div>
    
    <div class="footer">
        <p>Key System v1.0 - Secure Access Management</p>
        <p style="font-size: 0.85rem; margin-top: 0.5rem;">Backend: Private Domain | Public Proxy: vapp.wasmer.app</p>
    </div>
    
    <script>
        // Anti-devtools detection
        let devtools_open = false;
        
        function checkDevtools() {
            const threshold = 160;
            if (window.outerHeight - window.innerHeight > threshold ||
                window.outerWidth - window.innerWidth > threshold) {
                if (!devtools_open) {
                    devtools_open = true;
                    console.log("%cDeveloper Tools Detected", "color: red; font-size: 20px;");
                }
            } else {
                devtools_open = false;
            }
        }
        
        setInterval(checkDevtools, 500);
        
        // Disable right-click
        document.addEventListener('contextmenu', (e) => e.preventDefault());
        
        // Disable copy
        document.addEventListener('copy', (e) => e.preventDefault());
        
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && (e.key === 'c' || e.key === 'x')) {
                e.preventDefault();
            }
            if (e.key === 'F12' || (e.ctrlKey && e.shiftKey && e.key === 'I')) {
                e.preventDefault();
            }
        });
        
        function logToConsole(message) {
            const consoleOutput = document.getElementById('console-output');
            const timestamp = new Date().toLocaleTimeString();
            consoleOutput.innerHTML += `<div>[${timestamp}] ${message}</div>`;
            consoleOutput.scrollTop = consoleOutput.scrollHeight;
        }
        
        function clearConsole() {
            document.getElementById('console-output').innerHTML = '<div>[Cleared]</div>';
        }
        
        function showSection(sectionId) {
            document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
            
            document.getElementById(sectionId).classList.add('active');
            event.target.classList.add('active');
        }
        
        let lastGeneratedKey = null;
        
        async function generateKey() {
            const ttl = document.getElementById('ttl').value;
            const maxUses = document.getElementById('maxUses').value;
            const resultBox = document.getElementById('getkey-result');
            const copyBtn = document.getElementById('copyKeyBtn');
            
            resultBox.textContent = 'Generating key...';
            resultBox.className = 'result-box';
            resultBox.style.display = 'block';
            copyBtn.style.display = 'none';
            
            logToConsole('Requesting key generation...');
            
            try {
                const response = await fetch('/api/getkey', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ttl: parseInt(ttl), max_uses: parseInt(maxUses) })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    lastGeneratedKey = data.key;
                    resultBox.textContent = `Key: ${data.key}\nTTL: ${data.ttl}s\nMax Uses: ${data.max_uses}`;
                    resultBox.className = 'result-box success';
                    copyBtn.style.display = 'block';
                    logToConsole(`Key generated: ${data.key}`);
                } else {
                    resultBox.textContent = `Error: ${data.error || 'Unknown error'}`;
                    resultBox.className = 'result-box error';
                    logToConsole(`Error: ${data.error}`);
                }
            } catch (error) {
                resultBox.textContent = `Error: ${error.message}`;
                resultBox.className = 'result-box error';
                logToConsole(`Request failed: ${error.message}`);
            }
        }
        
        function copyGeneratedKey() {
            if (!lastGeneratedKey) return;
            
            navigator.clipboard.writeText(lastGeneratedKey).then(() => {
                const btn = document.getElementById('copyKeyBtn');
                const originalText = btn.textContent;
                btn.textContent = '✓ Copied!';
                
                setTimeout(() => {
                    btn.textContent = originalText;
                }, 2000);
                
                logToConsole(`Key copied to clipboard: ${lastGeneratedKey}`);
            });
        }
        
        async function checkKey() {
            const key = document.getElementById('checkKeyInput').value;
            const resultBox = document.getElementById('checkkey-result');
            
            if (!key) {
                resultBox.textContent = 'Please enter a key';
                resultBox.className = 'result-box error';
                resultBox.style.display = 'block';
                return;
            }
            
            resultBox.textContent = 'Validating...';
            resultBox.className = 'result-box';
            resultBox.style.display = 'block';
            
            logToConsole(`Validating key: ${key}`);
            
            try {
                const response = await fetch('/api/checkkey', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ key: key })
                });
                
                const data = await response.json();
                
                if (data.success && data.valid) {
                    resultBox.textContent = `Valid: YES\nUses Remaining: ${data.uses_remaining}`;
                    resultBox.className = 'result-box success';
                    logToConsole(`Key valid - Uses remaining: ${data.uses_remaining}`);
                } else {
                    resultBox.textContent = `Error: ${data.error || 'Key invalid or expired'}`;
                    resultBox.className = 'result-box error';
                    logToConsole('Key validation failed');
                }
            } catch (error) {
                resultBox.textContent = `Error: ${error.message}`;
                resultBox.className = 'result-box error';
                logToConsole(`Request failed: ${error.message}`);
            }
        }
        
        window.addEventListener('load', () => {
            logToConsole('System initialized and ready');
        });
    </script>
</body>
</html>
