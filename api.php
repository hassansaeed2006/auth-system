<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/Auth.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'register') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    $name = $data['name'] ?? '';
    $email = $data['email'] ?? '';
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';
    $role = $data['role'] ?? 'user';
    
    $result = $auth->register($name, $email, $username, $password, $role);
    echo json_encode($result);
    
} elseif ($action === 'login') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    
    $result = $auth->login($email, $password);
    echo json_encode($result);
    
} elseif ($action === 'setup2fa') {
    if (!$auth->isAuthenticated()) {
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    $result = $auth->setup2FA($_SESSION['user_id']);
    echo json_encode($result);
    
} elseif ($action === 'verify2fa') {
    $data = json_decode(file_get_contents("php://input"), true);
    $code = $data['code'] ?? '';
    
    if (!isset($_SESSION['2fa_user_id'])) {
        echo json_encode(['error' => 'No pending 2FA']);
        exit;
    }
    
    $result = $auth->verify2FA($_SESSION['2fa_user_id'], $code);
    echo json_encode($result);
    
} elseif ($action === 'logout') {
    $result = $auth->logout();
    echo json_encode($result);
    
} elseif ($action === 'verify-auth') {
    if ($auth->isAuthenticated()) {
        $user = $auth->getUserById($_SESSION['user_id']);
        echo json_encode([
            'is_authenticated' => true,
            'user' => $user
        ]);
    } else {
        echo json_encode(['is_authenticated' => false]);
    }
    
} else {
    echo json_encode(['error' => 'Invalid action']);
}
?>
