<?php
// API Endpoint for Authentication System
// This file handles all API requests for user authentication, registration, and management

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/Auth.php';

// Set response header to JSON
header('Content-Type: application/json');

// Get the action parameter from the request
$action = $_GET['action'] ?? '';

// Handle different API actions based on the 'action' parameter
if ($action === 'register') {
    // Handle user registration
    $data = json_decode(file_get_contents("php://input"), true);
    
    $name = $data['name'] ?? '';
    $email = $data['email'] ?? '';
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';
    $role = $data['role'] ?? 'user';
    
    $result = $auth->register($name, $email, $username, $password, $role);
    echo json_encode($result);
    
} elseif ($action === 'login') {
    // Handle user login
    $data = json_decode(file_get_contents("php://input"), true);
    
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    
    $result = $auth->login($email, $password);
    echo json_encode($result);
    
} elseif ($action === 'setup2fa') {
    // Setup Two-Factor Authentication for authenticated user
    $guard = $auth->requireAuthentication();
    if (isset($guard['error'])) {
        echo json_encode($guard);
        exit;
    }

    $result = $auth->setup2FA($guard['user']['id']);
    echo json_encode($result);
    
} elseif ($action === 'verify2fa') {
    // Verify 2FA code during login
    $data = json_decode(file_get_contents("php://input"), true);
    $code = $data['code'] ?? '';
    
    if (!isset($_SESSION['2fa_user_id']) || empty($_SESSION['2fa_pending'])) {
        echo json_encode(['error' => 'No pending 2FA']);
        exit;
    }
    
    $result = $auth->verify2FA($_SESSION['2fa_user_id'], $code);
    echo json_encode($result);

} elseif ($action === 'verify2fa-registration') {
    // Verify 2FA code during registration
    $data = json_decode(file_get_contents("php://input"), true);
    $user_id = (int)($data['user_id'] ?? 0);
    $code = $data['code'] ?? '';

    if ($user_id <= 0) {
        echo json_encode(['error' => 'Invalid user id']);
        exit;
    }

    $result = $auth->verify2FA($user_id, $code);
    if (isset($result['success'])) {
        $auth->logout();
        $result['success'] = 'Registration 2FA verified successfully. Please login.';
        unset($result['token'], $result['user']);
    }
    echo json_encode($result);
    
} elseif ($action === 'logout') {
    // Handle user logout
    $result = $auth->logout();
    echo json_encode($result);
    
} elseif ($action === 'verify-auth') {
    // Verify if user is authenticated
    $user = $auth->getAuthenticatedUserFromRequest();
    if ($user) {
        echo json_encode([
            'is_authenticated' => true,
            'user' => $user
        ]);
    } else {
        echo json_encode(['is_authenticated' => false]);
    }
    
} elseif ($action === 'change-password') {
    // Handle password change for authenticated user
    $guard = $auth->requireAuthentication();
    if (isset($guard['error'])) {
        echo json_encode($guard);
        exit;
    }

    $data = json_decode(file_get_contents("php://input"), true);
    $current_password = $data['current_password'] ?? '';
    $new_password = $data['new_password'] ?? '';

    $result = $auth->changePassword($guard['user']['id'], $current_password, $new_password);
    echo json_encode($result);
    
} else {
    // Invalid action requested
    echo json_encode(['error' => 'Invalid action']);
}
?>
?>
