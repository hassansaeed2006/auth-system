<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'auth_system_db');
define('JWT_SECRET', 'your-jwt-secret-key-change-in-production');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]));
}

$conn->set_charset("utf8mb4");

// Session configuration
ini_set('session.cookie_secure', false); // Set to true in production with HTTPS
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_samesite', 'Lax');

session_start();
?>
