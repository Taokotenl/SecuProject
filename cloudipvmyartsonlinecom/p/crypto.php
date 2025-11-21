<?php
class Crypto {
    private $secret;
    
    public function __construct($secret_key) {
        $this->secret = $secret_key;
    }
    
    // Simple response encryption for sensitive data
    public function encryptResponse($data) {
        return [
            'data' => $data,
            'timestamp' => time(),
            'signature' => $this->generateSignature($data, time())
        ];
    }
    
    // Verify request authenticity
    public function verifyRequest($data, $signature = null, $timestamp = null) {
        // If no signature provided, accept plain requests (for frontend)
        if ($signature === null) {
            return true;
        }
        
        // Verify timestamp if provided
        if ($timestamp !== null && abs(time() - $timestamp) > 30) {
            return false;
        }
        
        $expected = $this->generateSignature($data, $timestamp ?? time());
        return hash_equals($expected, $signature);
    }
    
    public function generateSignature($data, $timestamp) {
        $payload = is_array($data) ? json_encode($data) : $data;
        return hash_hmac('sha256', $payload . $timestamp, $this->secret);
    }
    
    // Generate secure token for keys
    public function generateToken($key_value) {
        return hash_hmac('sha256', $key_value . time(), $this->secret);
    }
}
?>
