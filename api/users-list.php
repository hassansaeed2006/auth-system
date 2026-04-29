<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Auth.php';

header('Content-Type: application/json');

// Check if user is admin
$guard = $auth->requireAuthentication('admin');
if (isset($guard['error'])) {
    echo json_encode($guard);
    exit;
}

// Get all users
$result = $conn->query("
    SELECT id, name, email, username, role, two_fa_enabled, is_active, created_at
    FROM users
    ORDER BY created_at DESC
");

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

echo json_encode(['users' => $users]);
?>
