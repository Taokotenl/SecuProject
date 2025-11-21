<?php
class Database {
    private $type;
    private $path;
    
    public function __construct($path, $type = 'json') {
        $this->type = $type;
        $this->path = $path;
    }
    
    public function getKey($key_value) {
        if ($this->type === 'json') {
            $file = $this->path . 'keys.json';
            if (!file_exists($file)) return null;
            
            $keys = json_decode(file_get_contents($file), true) ?? [];
            foreach ($keys as $key) {
                if ($key['key'] === $key_value) {
                    // Check TTL
                    if ($key['ttl'] > 0 && (time() - $key['created_at']) > $key['ttl']) {
                        return null; // Expired
                    }
                    return $key;
                }
            }
        }
        return null;
    }
    
    public function createKey($ttl = 86400, $max_uses = 5, $uuid = '') {
        $key = $this->generateKey();
        $key_data = [
            'key' => $key,
            'ttl' => $ttl,
            'max_uses' => $max_uses,
            'uses' => 0,
            'hwid' => '',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'created_at' => time(),
            'uuid' => $uuid
        ];
        
        if ($this->type === 'json') {
            $file = $this->path . 'keys.json';
            if (!is_dir($this->path)) {
                mkdir($this->path, 0755, true);
            }
            $keys = file_exists($file) ? json_decode(file_get_contents($file), true) ?? [] : [];
            $keys[] = $key_data;
            file_put_contents($file, json_encode($keys, JSON_PRETTY_PRINT));
        }
        
        return $key;
    }
    
    public function incrementKeyUses($key_value) {
        if ($this->type === 'json') {
            $file = $this->path . 'keys.json';
            $keys = json_decode(file_get_contents($file), true) ?? [];
            
            foreach ($keys as &$key) {
                if ($key['key'] === $key_value) {
                    $key['uses']++;
                    file_put_contents($file, json_encode($keys, JSON_PRETTY_PRINT));
                    return true;
                }
            }
        }
        return false;
    }
    
    public function getScript($script_id) {
        $file = $this->path . 'scripts.json';
        if (!file_exists($file)) return null;
        
        $scripts = json_decode(file_get_contents($file), true) ?? [];
        foreach ($scripts as $script) {
            if ($script['id'] === $script_id) {
                // Only return enabled scripts (default to enabled if flag missing)
                if (isset($script['enabled']) && $script['enabled'] === false) {
                    return null;
                }
                return $script;
            }
        }
        return null;
    }
    
    public function createScript($id, $code, $version = '1.0', $owner_uuid = '') {
        $script = [
            'id' => $id,
            'code' => $code,
            'version' => $version,
            'created_at' => time(),
            'encrypted' => true,
            'enabled' => true,
            'owner_uuid' => $owner_uuid
        ];
        
        if ($this->type === 'json') {
            $file = $this->path . 'scripts.json';
            $scripts = file_exists($file) ? json_decode(file_get_contents($file), true) ?? [] : [];
            $scripts[] = $script;
            file_put_contents($file, json_encode($scripts, JSON_PRETTY_PRINT));
        }
        
        return $script;
    }
    
    public function checkBan($ip) {
        if ($this->type === 'json') {
            $file = $this->path . 'violations.json';
            if (!file_exists($file)) return false;
            
            $violations = json_decode(file_get_contents($file), true) ?? [];
            if (isset($violations[$ip]) && !empty($violations[$ip]['banned'])) {
                return true;
            }
        }
        return false;
    }

    public function recordViolation($ip) {
        if ($this->type === 'json') {
            $file = $this->path . 'violations.json';
            $violations = file_exists($file) ? json_decode(file_get_contents($file), true) ?? [] : [];
            
            if (!isset($violations[$ip])) {
                $violations[$ip] = [
                    'count' => 0,
                    'first_violation' => time(),
                    'last_violation' => 0,
                    'banned' => false
                ];
            }
            
            // Reset count if last violation was more than 7 days ago
            if (time() - $violations[$ip]['first_violation'] > 604800) {
                $violations[$ip]['count'] = 0;
                $violations[$ip]['first_violation'] = time();
            }
            
            $violations[$ip]['count']++;
            $violations[$ip]['last_violation'] = time();
            
            // Ban if more than 5 violations
            if ($violations[$ip]['count'] > 5) {
                $violations[$ip]['banned'] = true;
            }
            
            file_put_contents($file, json_encode($violations, JSON_PRETTY_PRINT));
            return $violations[$ip]['banned'];
        }
        return false;
    }
    
    private function generateKey() {
        $part1 = strtoupper(bin2hex(random_bytes(4)));
        $part2 = strtoupper(bin2hex(random_bytes(4)));
        $part3 = strtoupper(bin2hex(random_bytes(4)));
        return "$part1-$part2-$part3";
    }
}
?>
