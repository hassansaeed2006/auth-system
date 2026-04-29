<?php
// Configuration file for the Authentication System
// This file contains database settings, JWT secret, and session configurations

// Database Configuration
// Update these values according to your database setup
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'auth_system_db');

// JWT Secret Key
// IMPORTANT: Change this to a secure random string in production
define('JWT_SECRET', 'your-jwt-secret-key-change-in-production');

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection and handle errors
if ($conn->connect_error) {
    die(json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]));
}

// Set character set for proper UTF-8 support
$conn->set_charset("utf8mb4");

// Session configuration for security
// In production with HTTPS, set cookie_secure to true
ini_set('session.cookie_secure', false); // Set to true in production with HTTPS
ini_set('session.cookie_httponly', true); // Prevent JavaScript access to session cookie
ini_set('session.cookie_samesite', 'Lax'); // CSRF protection

// Start PHP session
session_start();
?>
