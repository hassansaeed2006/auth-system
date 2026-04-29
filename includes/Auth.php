<?php
// Authentication Class
// Handles user authentication, registration, JWT tokens, and 2FA functionality

require_once __DIR__ . '/../config.php';

// Auth class for managing user authentication and security
class Auth {
    private $conn; // Database connection
    private const ROLE_ADMIN = 'admin'; // Administrator role
    private const ROLE_MANAGER = 'manager'; // Manager role
    private const ROLE_USER = 'user'; // Regular user role
    
    // Constructor - initializes with database connection
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    // Hash password using bcrypt for secure storage
    public function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    
    // Verify password against stored hash
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
        // Validate input fields
        if (empty($name) || empty($email) || empty($username) || empty($password)) {
            return ['error' => 'All fields are required'];
        }
        
        if (!in_array($role, [self::ROLE_ADMIN, self::ROLE_MANAGER, self::ROLE_USER], true)) {
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
        
        // Generate 2FA secret during registration (required by flow)
        $secret = $this->generateSecret();

        // Insert user
        $stmt = $this->conn->prepare("INSERT INTO users (name, email, username, password_hash, role, two_fa_secret, two_fa_enabled) VALUES (?, ?, ?, ?, ?, ?, 0)");
        $stmt->bind_param("ssssss", $name, $email, $username, $password_hash, $role, $secret);
        
        if ($stmt->execute()) {
            $stmt->close();
            $user_id = $this->conn->insert_id;
            $setupData = $this->create2FASetupData($user_id, $secret);
            return [
                'success' => 'User registered successfully. Complete 2FA setup before first login.',
                'user_id' => $user_id,
                'two_fa_setup' => $setupData
            ];
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
        
        // Require 2FA for every login
        if (!empty($user['two_fa_secret'])) {
            $_SESSION['2fa_user_id'] = $user['id'];
            $_SESSION['2fa_email'] = $user['email'];
            $_SESSION['2fa_pending'] = true;
            unset($_SESSION['user_id'], $_SESSION['token'], $_SESSION['role']);
            return ['2fa_required' => true, 'user_id' => $user['id']];
        }
        
        // Generate token
        $token = $this->generateToken($user['id'], $user['email'], $user['role']);
        $this->establishSessionAndCookie($user['id'], $user['role'], $token);
        
        return [
            'success' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'username' => $user['username'],
                'role' => $user['role']
            ]
        ];
    }
    
    // Setup 2FA
    public function setup2FA($user_id) {
        $user = $this->getUserById($user_id);
        if (!$user) {
            return ['error' => 'User not found'];
        }

        $stmt = $this->conn->prepare("SELECT two_fa_secret FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        $secret = $row['two_fa_secret'] ?: $this->generateSecret();

        if (empty($row['two_fa_secret'])) {
            $updateStmt = $this->conn->prepare("UPDATE users SET two_fa_secret = ? WHERE id = ?");
            $updateStmt->bind_param("si", $secret, $user_id);
            $updateStmt->execute();
            $updateStmt->close();
        }

        return $this->create2FASetupData($user_id, $secret);
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
            $this->establishSessionAndCookie($user_data['id'], $user_data['role'], $token);
            unset($_SESSION['2fa_pending']);
            unset($_SESSION['2fa_user_id'], $_SESSION['2fa_email']);
            
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
        return $this->getAuthenticatedUserFromRequest() !== null;
    }
    
    // Check role
    public function hasRole($required_role) {
        $authUser = $this->getAuthenticatedUserFromRequest();
        if (!$authUser || !isset($authUser['role'])) {
            return false;
        }

        $user_role = $authUser['role'];
        return $required_role === $user_role;
    }
    
    // Logout
    public function logout() {
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool)$params['secure'], (bool)$params['httponly']);
        }
        setcookie('auth_token', '', time() - 3600, '/', '', false, true);
        session_destroy();
        return ['success' => 'Logged out successfully'];
    }

    public function getAuthenticatedUserFromRequest() {
        $token = $this->extractTokenFromRequest();
        if (!$token && isset($_SESSION['token'])) {
            $token = $_SESSION['token'];
        }
        if (!$token) {
            return null;
        }

        $payload = $this->verifyToken($token);
        if (!$payload || !isset($payload['user_id'])) {
            return null;
        }

        $user = $this->getUserById((int)$payload['user_id']);
        if (!$user || $user['email'] !== ($payload['email'] ?? null) || $user['role'] !== ($payload['role'] ?? null)) {
            return null;
        }

        return $user;
    }

    public function requireAuthentication($requiredRole = null) {
        $user = $this->getAuthenticatedUserFromRequest();
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Authentication required'];
        }

        if ($requiredRole && !$this->hasRole($requiredRole)) {
            http_response_code(403);
            return ['error' => 'Access denied'];
        }

        return ['user' => $user];
    }

    private function establishSessionAndCookie($userId, $role, $token) {
        $_SESSION['user_id'] = $userId;
        $_SESSION['token'] = $token;
        $_SESSION['role'] = $role;
        setcookie('auth_token', $token, [
            'expires' => time() + (24 * 60 * 60),
            'path' => '/',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }

    private function extractTokenFromRequest() {
        if (!empty($_SERVER['HTTP_AUTHORIZATION']) && preg_match('/Bearer\s+(.+)/i', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
            return trim($matches[1]);
        }

        if (!empty($_COOKIE['auth_token'])) {
            return $_COOKIE['auth_token'];
        }

        return null;
    }

    private function create2FASetupData($user_id, $secret) {
        require_once __DIR__ . '/phpqrcode/qrlib.php';

        $qrDir = __DIR__ . '/../qrcodes';
        if (!is_dir($qrDir)) {
            mkdir($qrDir, 0755, true);
        }

        $qrFilename = uniqid('qr_', true) . '.png';
        $qrAbsolutePath = $qrDir . DIRECTORY_SEPARATOR . $qrFilename;
        $qrRelativePath = 'qrcodes/' . $qrFilename;

        $user = $this->getUserById($user_id);
        $email = rawurlencode($user['email']);
        $issuer = rawurlencode('Auth System');
        $provisioning_uri = "otpauth://totp/Auth%20System:{$email}?secret={$secret}&issuer={$issuer}";

        QRcode::png($provisioning_uri, $qrAbsolutePath, QR_ECLEVEL_L, 4);

        return [
            'secret' => $secret,
            'qr_code' => $qrRelativePath
        ];
    }

    // Change user password
    public function changePassword($user_id, $current_password, $new_password) {
        // Validate input parameters
        if (empty($current_password) || empty($new_password)) {
            return ['error' => 'Current password and new password are required'];
        }

        if (strlen($new_password) < 8) {
            return ['error' => 'New password must be at least 8 characters long'];
        }

        // Get user from database
        $stmt = $this->conn->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return ['error' => 'User not found'];
        }

        $user = $result->fetch_assoc();
        $stmt->close();

        // Verify current password is correct
        if (!$this->verifyPassword($current_password, $user['password_hash'])) {
            return ['error' => 'Current password is incorrect'];
        }

        // Hash new password
        $new_password_hash = $this->hashPassword($new_password);

        // Update password in database
        $stmt = $this->conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->bind_param("si", $new_password_hash, $user_id);

        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => 'Password changed successfully'];
        } else {
            return ['error' => 'Failed to change password'];
        }
    }
}

$auth = new Auth($conn);
?>
