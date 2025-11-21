<?php
// Public Proxy & Frontend - Deploy to https://vapp-vexnetnetworkofficial.wasmer.app/

define('PRIVATE_BACKEND', 'http://cloudipv.myartsonline.com/p/');
define('ALLOWED_ACTIONS', ['sync', 'getkey', 'checkkey', 'loadscript', 'verify']);

$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);

session_start();

// Load admin config
$admin_cfg = __DIR__ . '/admin_config.php';
if (file_exists($admin_cfg)) {
    $ADMIN = include $admin_cfg;
} else {
    $ADMIN = ['admin_user' => 'admin', 'admin_pass' => 'changeme'];
}

// ========== AUTH ROUTES ==========

if ($path === '/login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $post = json_decode(file_get_contents('php://input'), true) ?: [];
    $user = $post['user'] ?? '';
    $pass = $post['pass'] ?? '';
    
    if ($user === $ADMIN['admin_user'] && $pass === $ADMIN['admin_pass']) {
        $_SESSION['vapp_user'] = $user;
        $_SESSION['vapp_user_token'] = bin2hex(random_bytes(16));
        echo json_encode(['success' => true, 'token' => $_SESSION['vapp_user_token']]);
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid credentials']);
    }
    exit;
}

if ($path === '/logout') {
    session_destroy();
    header('Location: /');
    exit;
}

// ========== DASHBOARD ==========

if ($path === '/dashboard') {
    if (empty($_SESSION['vapp_user_token'])) {
        header('Location: /');
        exit;
    }
    
    header('Content-Type: text/html; charset=utf-8');
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Vapp Proxy</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #0f1727 0%, #1a2844 100%);
            color: #e0e8f0;
            min-height: 100vh;
        }
        .sidebar {
            position: fixed; left: 0; top: 0; width: 260px; height: 100vh;
            background: rgba(10, 20, 40, 0.8); backdrop-filter: blur(10px);
            border-right: 1px solid rgba(100, 180, 220, 0.1);
            padding: 24px 16px;
            overflow-y: auto;
        }
        .logo { font-size: 24px; font-weight: 800; color: #00d4ff; margin-bottom: 32px; letter-spacing: 1px; }
        .nav-item {
            padding: 12px 16px; margin: 8px 0; border-radius: 8px;
            cursor: pointer; transition: all 0.3s;
            color: #a8b8c8; text-decoration: none; display: block;
        }
        .nav-item:hover { background: rgba(0, 212, 255, 0.1); color: #00d4ff; }
        .nav-item.active { background: rgba(0, 212, 255, 0.2); color: #00d4ff; }
        .logout-btn { margin-top: 32px; padding: 10px 16px; background: rgba(255, 80, 80, 0.2);
            color: #ff6b6b; border-radius: 8px; border: none; cursor: pointer; width: 100%; }
        .main { margin-left: 260px; padding: 32px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; }
        .header h1 { font-size: 32px; font-weight: 700; }
        .user-info { display: flex; align-items: center; gap: 12px; }
        .avatar { width: 40px; height: 40px; background: linear-gradient(135deg, #00d4ff, #0099ff);
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            color: #021122; font-weight: 700; }
        .card {
            background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(10px);
            border: 1px solid rgba(100, 180, 220, 0.1); border-radius: 12px;
            padding: 24px; margin-bottom: 24px;
        }
        .card h2 { margin-bottom: 16px; color: #00d4ff; font-size: 20px; }
        .form-group { margin-bottom: 16px; }
        label { display: block; margin-bottom: 6px; color: #a8b8c8; font-weight: 500; }
        input { width: 100%; padding: 10px 12px; background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(100, 180, 220, 0.2); border-radius: 8px;
            color: #e0e8f0; font-size: 14px; }
        input:focus { outline: none; border-color: #00d4ff; box-shadow: 0 0 8px rgba(0, 212, 255, 0.2); }
        .btn-primary { padding: 10px 24px; background: linear-gradient(135deg, #00d4ff, #0099ff);
            color: #021122; border: none; border-radius: 8px; font-weight: 600;
            cursor: pointer; transition: all 0.3s; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 16px rgba(0, 212, 255, 0.3); }
        .result { background: rgba(0, 0, 0, 0.3); padding: 12px; border-radius: 8px;
            font-family: 'Courier New', monospace; font-size: 12px; max-height: 300px;
            overflow-y: auto; margin-top: 12px; white-space: pre-wrap; word-break: break-all; }
        .success { color: #00d966; }
        .error { color: #ff6b6b; }
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
        .stat { background: rgba(0, 212, 255, 0.05); border: 1px solid rgba(0, 212, 255, 0.2);
            border-radius: 12px; padding: 20px; text-align: center; }
        .stat-number { font-size: 28px; font-weight: 700; color: #00d4ff; }
        .stat-label { color: #a8b8c8; margin-top: 4px; }
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main { margin-left: 0; padding: 16px; }
            .stats { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">🔐 Vapp</div>
        <a href="#" class="nav-item active" onclick="switchTab('overview')">Overview</a>
        <a href="#" class="nav-item" onclick="switchTab('keys')">Manage Keys</a>
        <a href="#" class="nav-item" onclick="switchTab('settings')">Settings</a>
        <button class="logout-btn" onclick="logout()">Logout</button>
    </div>

    <div class="main">
        <div class="header">
            <h1>Dashboard</h1>
            <div class="user-info">
                <div class="avatar"><?php echo strtoupper($ADMIN['admin_user'][0]); ?></div>
                <span><?php echo htmlspecialchars($ADMIN['admin_user']); ?></span>
            </div>
        </div>

        <!-- Overview Tab -->
        <div id="tab-overview" class="tab-content">
            <div class="stats">
                <div class="stat">
                    <div class="stat-number">12</div>
                    <div class="stat-label">Active Scripts</div>
                </div>
                <div class="stat">
                    <div class="stat-number">284</div>
                    <div class="stat-label">Keys Generated</div>
                </div>
                <div class="stat">
                    <div class="stat-number">98%</div>
                    <div class="stat-label">Uptime</div>
                </div>
            </div>

            <div class="card">
                <h2>System Status</h2>
                <p style="color: #a8b8c8;">All systems operational ✓</p>
                <div style="margin-top: 16px;">
                    <button class="btn-primary" onclick="switchTab('settings')">Manage System</button>
                </div>
            </div>
        </div>

        <!-- Keys Tab -->
        <div id="tab-keys" class="tab-content" style="display:none;">
            <div class="card">
                <h2>Request API Key</h2>
                <div class="form-group">
                    <label>Script ID</label>
                    <input type="text" id="scriptId" placeholder="Enter your script ID">
                </div>
                <button class="btn-primary" onclick="requestKey()">Request Key</button>
                <div id="keyResult" class="result" style="display:none;"></div>
            </div>
        </div>

        <!-- Settings Tab -->
        <div id="tab-settings" class="tab-content" style="display:none;">
            <div class="card">
                <h2>Maintenance Mode</h2>
                <button class="btn-primary" onclick="toggleMaintenance()">Toggle Maintenance</button>
                <div id="mainResult" class="result" style="display:none;"></div>
            </div>

            <div class="card">
                <h2>Manage Scripts</h2>
                <button class="btn-primary" onclick="loadScripts()">Load Scripts</button>
                <div id="scriptResult" class="result" style="display:none;"></div>
            </div>
        </div>
    </div>

    <script>
        function switchTab(name) {
            document.querySelectorAll('.tab-content').forEach(t => t.style.display = 'none');
            document.getElementById('tab-' + name).style.display = 'block';
            document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
            event.target.classList.add('active');
        }

        async function requestKey() {
            const sid = document.getElementById('scriptId').value.trim();
            const result = document.getElementById('keyResult');
            if (!sid) { result.textContent = 'Please enter script ID'; result.style.display = 'block'; return; }
            result.textContent = 'Requesting...';
            result.style.display = 'block';
            try {
                const res = await fetch('/getkey', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ script_id: sid })
                });
                const text = await res.text();
                try {
                    const j = JSON.parse(text);
                    result.innerHTML = '<span class="' + (j.success ? 'success' : 'error') + '">' + JSON.stringify(j, null, 2) + '</span>';
                } catch (e) {
                    result.textContent = text;
                }
            } catch (e) {
                result.innerHTML = '<span class="error">Error: ' + e.message + '</span>';
            }
        }

        async function toggleMaintenance() {
            const result = document.getElementById('mainResult');
            result.textContent = 'Toggling...';
            result.style.display = 'block';
            try {
                const res = await fetch('/admin/maintenance', { method: 'POST', body: new FormData() });
                result.textContent = await res.text();
            } catch (e) {
                result.innerHTML = '<span class="error">Error: ' + e.message + '</span>';
            }
        }

        async function loadScripts() {
            const result = document.getElementById('scriptResult');
            result.textContent = 'Loading...';
            result.style.display = 'block';
            try {
                const res = await fetch('/admin/manage');
                result.textContent = await res.text();
            } catch (e) {
                result.innerHTML = '<span class="error">Error: ' + e.message + '</span>';
            }
        }

        function logout() {
            if (confirm('Logout?')) window.location.href = '/logout';
        }
    </script>
</body>
</html>
    <?php
    exit;
}

// ========== ADMIN ROUTES ==========

if (strpos($path, '/admin') === 0) {
    if (empty($_SESSION['vapp_user_token'])) {
        http_response_code(401);
        echo 'Unauthorized';
        exit;
    }

    $sub = rtrim(substr($path, strlen('/admin')), "/");
    if ($sub === '') $sub = '/';

    if ($sub === '/manage') {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $backend = PRIVATE_BACKEND . 'manage.php';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $backend);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $headers = ['User-Agent: VexNetProxy-Admin/1.0','X-Admin: 1'];
            if (!empty($_SESSION['vapp_user_token'])) $headers[] = 'Authorization: Bearer ' . $_SESSION['vapp_user_token'];
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $resp = curl_exec($ch);
            curl_close($ch);
            $data = @json_decode($resp, true) ?: [];
            header('Content-Type: text/html; charset=utf-8');
            echo '<h2>Manage Scripts</h2>';
            echo '<p><a href="/dashboard">Back</a></p>';
            if (empty($data['scripts'])) { echo '<p>No scripts found</p>'; exit; }
            echo '<table border="1" cellpadding="6"><tr><th>ID</th><th>Version</th><th>Enabled</th><th>Action</th></tr>';
            foreach ($data['scripts'] as $s) {
                $enabled = isset($s['enabled']) && $s['enabled'] ? 'Yes' : 'No';
                echo '<tr><td>' . htmlspecialchars($s['id']) . '</td><td>' . htmlspecialchars($s['version'] ?? '') . '</td><td>' . $enabled . '</td>';
                echo '<td>';
                $action = (isset($s['enabled']) && $s['enabled']) ? 'Disable' : 'Enable';
                echo '<form method="POST" action="/admin/toggle" style="display:inline"><input type="hidden" name="id" value="' . htmlspecialchars($s['id']) . '">';
                echo '<input type="hidden" name="enabled" value="' . (isset($s['enabled']) && $s['enabled'] ? '0' : '1') . '">';
                echo '<button type="submit">' . $action . '</button></form>';
                echo '</td></tr>';
            }
            echo '</table>';
            exit;
        }
    }

    if ($sub === '/toggle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = $_POST['id'] ?? null;
        $enabled = isset($_POST['enabled']) ? ($_POST['enabled'] === '1') : null;
        if (!$id || $enabled === null) { echo 'Missing parameters'; exit; }
        $backend = PRIVATE_BACKEND . 'manage.php';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $backend);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['action'=>'toggle','id'=>$id,'enabled'=>$enabled]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $headers = ['Content-Type: application/json','User-Agent: VexNetProxy-Admin/1.0','X-Admin: 1'];
        if (!empty($_SESSION['vapp_user_token'])) $headers[] = 'Authorization: Bearer ' . $_SESSION['vapp_user_token'];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $resp = curl_exec($ch);
        curl_close($ch);
        header('Content-Type: text/html; charset=utf-8');
        echo '<h2>Toggle Result</h2><pre>' . htmlspecialchars($resp) . '</pre><p><a href="/admin/manage">Back</a></p>';
        exit;
    }

    if ($sub === '/maintenance') {
        $backend = PRIVATE_BACKEND . 'set_maintenance.php';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $mode = $_POST['mode'] ?? 'on';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $backend);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['mode'=>$mode]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $headers = ['Content-Type: application/json','User-Agent: VexNetProxy-Admin/1.0','X-Admin: 1'];
            if (!empty($_SESSION['vapp_user_token'])) $headers[] = 'Authorization: Bearer ' . $_SESSION['vapp_user_token'];
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $resp = curl_exec($ch);
            curl_close($ch);
            header('Content-Type: text/html; charset=utf-8');
            echo '<h2>Maintenance Result</h2><pre>' . htmlspecialchars($resp) . '</pre><p><a href="/admin">Back</a></p>';
            exit;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $backend);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $headers = ['User-Agent: VexNetProxy-Admin/1.0','X-Admin: 1'];
        if (!empty($_SESSION['vapp_user_token'])) $headers[] = 'Authorization: Bearer ' . $_SESSION['vapp_user_token'];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $resp = curl_exec($ch);
        curl_close($ch);
        $data = @json_decode($resp, true) ?: [];
        $enabled = !empty($data['maintenance']) && !empty($data['maintenance']['enabled']);
        header('Content-Type: text/html; charset=utf-8');
        echo '<h2>Maintenance Mode</h2>';
        echo '<p>Currently: ' . ($enabled ? '<strong>ON</strong>' : '<strong>OFF</strong>') . '</p>';
        echo '<form method="POST"><button name="mode" value="on">Enable</button> <button name="mode" value="off">Disable</button></form>';
        echo '<p><a href="/admin">Back</a></p>';
        exit;
    }

    header('HTTP/1.1 404 Not Found');
    echo 'Not Found';
    exit;
}

// ========== SECURE GETKEY API ==========

if ($path === '/getkey' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    if (empty($_SESSION['vapp_user_token'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        exit;
    }

    $post = file_get_contents('php://input');
    $data = json_decode($post, true) ?: [];
    $script_id = $data['script_id'] ?? null;
    if (!$script_id) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Missing script_id']); exit; }

    $tokensFile = __DIR__ . '/data/tokens.json';
    if (!file_exists($tokensFile)) { http_response_code(500); echo json_encode(['success'=>false,'error'=>'Tokens storage missing']); exit; }
    $tokens = json_decode(file_get_contents($tokensFile), true) ?: [];

    if (empty($tokens[$script_id])) {
        http_response_code(404);
        echo json_encode(['success'=>false,'error'=>'No token configured for this script']);
        exit;
    }

    $entry = $tokens[$script_id];
    $payload = [
        'token' => $entry['token'],
        'script_id' => $script_id,
        'provider' => $entry['provider'] ?? 'https://linkvertise.com/',
        'method' => $entry['method'] ?? 'post'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, PRIVATE_BACKEND . 'makekey.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json','User-Agent: VappProxy/1.0']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $resp = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($resp === false) {
        http_response_code(502);
        echo json_encode(['success'=>false,'error'=>'Backend error: '.$err]);
        exit;
    }

    http_response_code($http ?: 200);
    echo $resp;
    exit;
}

// ========== HOME PAGE ==========
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vapp Proxy - Secure Access</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0f1727 0%, #1a2844 100%);
            color: #e0e8f0; min-height: 100vh;
        }
        .navbar {
            display: flex; justify-content: space-between; align-items: center;
            padding: 20px 40px; backdrop-filter: blur(10px); border-bottom: 1px solid rgba(100, 180, 220, 0.1);
        }
        .logo { font-size: 28px; font-weight: 800; color: #00d4ff; letter-spacing: 2px; }
        .nav-links { display: flex; gap: 16px; }
        .nav-links a, .nav-links button {
            padding: 10px 20px; border-radius: 8px; text-decoration: none;
            cursor: pointer; border: none; font-weight: 600; transition: all 0.3s;
        }
        .nav-links a.primary, .nav-links button.primary {
            background: linear-gradient(135deg, #00d4ff, #0099ff); color: #021122;
        }
        .nav-links a.primary:hover { transform: translateY(-2px); box-shadow: 0 8px 16px rgba(0, 212, 255, 0.3); }
        .nav-links a.secondary { color: #a8b8c8; border: 1px solid rgba(100, 180, 220, 0.3); }
        .nav-links a.secondary:hover { color: #00d4ff; border-color: rgba(0, 212, 255, 0.5); }
        .container { max-width: 1200px; margin: 60px auto; padding: 0 40px; }
        .hero { text-align: center; margin-bottom: 80px; }
        .hero h1 { font-size: 48px; font-weight: 800; margin-bottom: 16px;
            background: linear-gradient(135deg, #00d4ff, #0099ff); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .hero p { font-size: 18px; color: #a8b8c8; margin-bottom: 32px; }
        .hero-btn { padding: 14px 32px; background: linear-gradient(135deg, #00d4ff, #0099ff);
            color: #021122; border: none; border-radius: 8px; font-size: 16px; font-weight: 700;
            cursor: pointer; transition: all 0.3s; }
        .hero-btn:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0, 212, 255, 0.4); }
        .features { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 60px; }
        .feature { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(10px);
            border: 1px solid rgba(100, 180, 220, 0.1); border-radius: 12px; padding: 32px; text-align: center; }
        .feature h3 { color: #00d4ff; margin-bottom: 12px; }
        .feature p { color: #a8b8c8; line-height: 1.6; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.8); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: linear-gradient(135deg, rgba(15, 23, 39, 0.95), rgba(26, 40, 68, 0.95));
            border: 1px solid rgba(100, 180, 220, 0.2); border-radius: 16px; padding: 40px;
            width: 100%; max-width: 420px; }
        .modal-header { font-size: 24px; font-weight: 700; margin-bottom: 24px; color: #00d4ff; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; margin-bottom: 6px; color: #a8b8c8; }
        .form-group input { width: 100%; padding: 12px; background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(100, 180, 220, 0.2); border-radius: 8px; color: #e0e8f0; font-size: 14px; }
        .form-group input:focus { outline: none; border-color: #00d4ff; box-shadow: 0 0 8px rgba(0, 212, 255, 0.2); }
        .submit-btn { width: 100%; padding: 12px; background: linear-gradient(135deg, #00d4ff, #0099ff);
            color: #021122; border: none; border-radius: 8px; font-weight: 700; cursor: pointer;
            transition: all 0.3s; }
        .submit-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 16px rgba(0, 212, 255, 0.3); }
        .close-btn { float: right; font-size: 28px; cursor: pointer; color: #a8b8c8; }
        .close-btn:hover { color: #00d4ff; }
        @media (max-width: 768px) {
            .navbar { padding: 16px 20px; }
            .container { padding: 0 20px; }
            .hero h1 { font-size: 32px; }
            .features { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="logo">🔐 Vapp</div>
        <div class="nav-links">
            <button class="secondary" onclick="openModal('login')">Sign In</button>
            <button class="primary" onclick="openModal('signup')">Sign Up</button>
        </div>
    </div>

    <div class="container">
        <div class="hero">
            <h1>Secure API Key Management</h1>
            <p>Manage your scripts and generate secure keys with ease</p>
            <button class="hero-btn" onclick="openModal('login')">Get Started</button>
        </div>

        <div class="features">
            <div class="feature">
                <h3>🔒 Secure</h3>
                <p>End-to-end encrypted key management with server-side token storage</p>
            </div>
            <div class="feature">
                <h3>⚡ Fast</h3>
                <p>Instant key generation and verification with optimized backend</p>
            </div>
            <div class="feature">
                <h3>📊 Manage</h3>
                <p>Full dashboard control over scripts and access tokens</p>
            </div>
        </div>
    </div>

    <!-- Login Modal -->
    <div id="loginModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('login')">&times;</span>
            <div class="modal-header">Sign In</div>
            <div class="form-group">
                <label>Username</label>
                <input type="text" id="loginUser" placeholder="Enter username">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" id="loginPass" placeholder="Enter password">
            </div>
            <button class="submit-btn" onclick="handleLogin()">Sign In</button>
            <p style="margin-top: 16px; text-align: center; color: #a8b8c8;">
                Don't have an account? <a href="#" onclick="switchModal('signup')" style="color: #00d4ff; text-decoration: none;">Sign Up</a>
            </p>
        </div>
    </div>

    <!-- Signup Modal -->
    <div id="signupModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('signup')">&times;</span>
            <div class="modal-header">Sign Up</div>
            <p style="color: #a8b8c8; margin-bottom: 16px; font-size: 14px;">Contact admin for registration</p>
            <div class="form-group">
                <label>Email</label>
                <input type="email" id="signupEmail" placeholder="Enter email">
            </div>
            <div class="form-group">
                <label>Username</label>
                <input type="text" id="signupUser" placeholder="Choose username">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" id="signupPass" placeholder="Create password">
            </div>
            <button class="submit-btn" onclick="handleSignup()">Create Account</button>
            <p style="margin-top: 16px; text-align: center; color: #a8b8c8;">
                Already have an account? <a href="#" onclick="switchModal('login')" style="color: #00d4ff; text-decoration: none;">Sign In</a>
            </p>
        </div>
    </div>

    <script>
        function openModal(type) {
            document.getElementById(type + 'Modal').classList.add('active');
        }

        function closeModal(type) {
            document.getElementById(type + 'Modal').classList.remove('active');
        }

        function switchModal(type) {
            document.querySelectorAll('.modal').forEach(m => m.classList.remove('active'));
            openModal(type);
            event.preventDefault();
        }

        async function handleLogin() {
            const user = document.getElementById('loginUser').value.trim();
            const pass = document.getElementById('loginPass').value.trim();

            if (!user || !pass) {
                alert('Please fill all fields');
                return;
            }

            try {
                const res = await fetch('/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user, pass })
                });

                const data = await res.json();
                if (data.success) {
                    alert('Login successful!');
                    window.location.href = '/dashboard';
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (e) {
                alert('Login failed: ' + e.message);
            }
        }

        function handleSignup() {
            alert('Please contact the administrator for account registration');
        }

        // Close modal when clicking outside
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>