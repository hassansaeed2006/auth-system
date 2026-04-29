<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/Auth.php';

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
    $guard = $auth->requireAuthentication();
    if (isset($guard['error'])) {
        echo json_encode($guard);
        exit;
    }

    $result = $auth->setup2FA($guard['user']['id']);
    echo json_encode($result);
    
} elseif ($action === 'verify2fa') {
    $data = json_decode(file_get_contents("php://input"), true);
    $code = $data['code'] ?? '';
    
    if (!isset($_SESSION['2fa_user_id']) || empty($_SESSION['2fa_pending'])) {
        echo json_encode(['error' => 'No pending 2FA']);
        exit;
    }
    
    $result = $auth->verify2FA($_SESSION['2fa_user_id'], $code);
    echo json_encode($result);

} elseif ($action === 'verify2fa-registration') {
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
    $result = $auth->logout();
    echo json_encode($result);
    
} elseif ($action === 'verify-auth') {
    $user = $auth->getAuthenticatedUserFromRequest();
    if ($user) {
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
