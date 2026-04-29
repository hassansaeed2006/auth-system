<?php
require_once 'config.php';

class Auth {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    // Hash password using bcrypt
    public function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    
    // Verify password
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    // Generate JWT Token
    public function generateToken($user_id, $email, $role) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'user_id' => $user_id,
            'email' => $email,
            'role' => $role,
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60) // 24 hours
        ]);
        
        $base64UrlHeader = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');
        $base64UrlPayload = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
        
        $signature = hash_hmac('sha256', "$base64UrlHeader.$base64UrlPayload", JWT_SECRET, true);
        $base64UrlSignature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
        
        return "$base64UrlHeader.$base64UrlPayload.$base64UrlSignature";
    }
    
    // Verify JWT Token
    public function verifyToken($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return false;
        
        list($header, $payload, $signature) = $parts;
        
        $base64UrlSignature = rtrim(strtr(base64_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true)), '+/', '-_'), '=');
        
        if ($signature !== $base64UrlSignature) return false;
        
        $decodedPayload = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
        
        if ($decodedPayload['exp'] < time()) return false;
        
        return $decodedPayload;
    }
    
    // Register user
    public function register($name, $email, $username, $password, $role = 'user') {
        // Validate input
        if (empty($name) || empty($email) || empty($username) || empty($password)) {
            return ['error' => 'All fields are required'];
        }
        
        if ($role !== 'admin' && $role !== 'manager' && $role !== 'user') {
            return ['error' => 'Invalid role'];
        }
        
        // Check if user exists
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->bind_param("ss", $email, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return ['error' => 'Email or username already exists'];
        }
        $stmt->close();
        
        // Hash password
        $password_hash = $this->hashPassword($password);
        
        // Insert user
        $stmt = $this->conn->prepare("INSERT INTO users (name, email, username, password_hash, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $email, $username, $password_hash, $role);
        
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => 'User registered successfully', 'user_id' => $this->conn->insert_id];
        } else {
            return ['error' => 'Registration failed'];
        }
    }
    
    // Login user
    public function login($email, $password) {
        if (empty($email) || empty($password)) {
            return ['error' => 'Email and password are required'];
        }
        
        // Find user by email or username
        $stmt = $this->conn->prepare("SELECT id, name, email, username, password_hash, role, two_fa_secret, two_fa_enabled FROM users WHERE (email = ? OR username = ?) AND is_active = 1");
        $stmt->bind_param("ss", $email, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return ['error' => 'Invalid credentials'];
        }
        
        $user = $result->fetch_assoc();
        $stmt->close();
        
        // Verify password
        if (!$this->verifyPassword($password, $user['password_hash'])) {
            return ['error' => 'Invalid credentials'];
        }
        
        // Check if 2FA is enabled
        if ($user['two_fa_enabled']) {
            $_SESSION['2fa_user_id'] = $user['id'];
            $_SESSION['2fa_email'] = $user['email'];
            $_SESSION['2fa_pending'] = true;
            return ['2fa_required' => true, 'user_id' => $user['id']];
        }
        
        // Generate token
        $token = $this->generateToken($user['id'], $user['email'], $user['role']);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['token'] = $token;
        $_SESSION['role'] = $user['role'];
        
        return [
            'success' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ];
    }
    
    // Setup 2FA
    public function setup2FA($user_id) {
        require_once 'includes/phpqrcode/qrlib.php';
        
        $secret = $this->generateSecret();
        
        // Save secret to database
        $stmt = $this->conn->prepare("UPDATE users SET two_fa_secret = ? WHERE id = ?");
        $stmt->bind_param("si", $secret, $user_id);
        $stmt->execute();
        $stmt->close();
        
        // Generate QR code
        $qr_filename = 'qrcodes/' . uniqid() . '.png';
        if (!is_dir('qrcodes')) {
            mkdir('qrcodes', 0755, true);
        }
        
        $user = $this->getUserById($user_id);
        $provisioning_uri = "otpauth://totp/Auth System:{$user['email']}?secret=$secret&issuer=Auth System";
        
        QRcode::png($provisioning_uri, $qr_filename, QR_ECLEVEL_L, 4);
        
        return [
            'secret' => $secret,
            'qr_code' => $qr_filename
        ];
    }
    
    // Verify 2FA code
    public function verify2FA($user_id, $code) {
        // Get user's secret
        $stmt = $this->conn->prepare("SELECT two_fa_secret FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return ['error' => 'User not found'];
        }
        
        $user = $result->fetch_assoc();
        $stmt->close();
        
        if (!$user['two_fa_secret']) {
            return ['error' => '2FA not set up'];
        }
        
        // Verify code
        if ($this->verifyTOTPCode($code, $user['two_fa_secret'])) {
            // Enable 2FA and get user info
            $stmt = $this->conn->prepare("UPDATE users SET two_fa_enabled = 1 WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
            
            $user_data = $this->getUserById($user_id);
            $token = $this->generateToken($user_data['id'], $user_data['email'], $user_data['role']);
            $_SESSION['user_id'] = $user_data['id'];
            $_SESSION['token'] = $token;
            $_SESSION['role'] = $user_data['role'];
            unset($_SESSION['2fa_pending']);
            
            return [
                'success' => '2FA verified',
                'token' => $token,
                'user' => $user_data
            ];
        }
        
        return ['error' => 'Invalid 2FA code'];
    }
    
    // Generate TOTP secret
    private function generateSecret($length = 32) {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= $chars[rand(0, strlen($chars) - 1)];
        }
        return $secret;
    }
    
    // Verify TOTP code
    private function verifyTOTPCode($code, $secret, $window = 1) {
        $time_step = 30;
        $current_time = intval(time() / $time_step);
        
        for ($i = -$window; $i <= $window; $i++) {
            $time = $current_time + $i;
            $hmac = hash_hmac('sha1', pack('N*', 0, $time), $this->base32Decode($secret), true);
            $offset = ord($hmac[19]) & 0xf;
            $otp = (((ord($hmac[$offset]) & 0x7f) << 24) |
                    ((ord($hmac[$offset + 1]) & 0xff) << 16) |
                    ((ord($hmac[$offset + 2]) & 0xff) << 8) |
                    (ord($hmac[$offset + 3]) & 0xff)) % 1000000;
            
            if ($otp == $code) {
                return true;
            }
        }
        
        return false;
    }
    
    // Decode base32
    private function base32Decode($input) {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        $v = 0;
        $vbits = 0;
        
        for ($i = 0; $i < strlen($input); $i++) {
            $c = strpos($alphabet, $input[$i]);
            if ($c === false) continue;
            $v = ($v << 5) | $c;
            $vbits += 5;
            if ($vbits >= 8) {
                $vbits -= 8;
                $output .= chr(($v >> $vbits) & 255);
            }
        }
        
        return $output;
    }
    
    // Get user by ID
    public function getUserById($user_id) {
        $stmt = $this->conn->prepare("SELECT id, name, email, username, role, two_fa_enabled, created_at FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return null;
        }
        
        return $result->fetch_assoc();
    }
    
    // Check if user is authenticated
    public function isAuthenticated() {
        return isset($_SESSION['user_id']) && isset($_SESSION['token']);
    }
    
    // Check role
    public function hasRole($required_role) {
        if (!isset($_SESSION['role'])) {
            return false;
        }
        
        $user_role = $_SESSION['role'];
        
        if ($required_role === 'admin') {
            return $user_role === 'admin';
        } elseif ($required_role === 'manager') {
            return $user_role === 'admin' || $user_role === 'manager';
        } elseif ($required_role === 'user') {
            return true;
        }
        
        return false;
    }
    
    // Logout
    public function logout() {
        session_destroy();
        return ['success' => 'Logged out successfully'];
    }
}

$auth = new Auth($conn);
?>
