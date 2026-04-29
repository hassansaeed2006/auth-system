<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Auth.php';

header('Content-Type: application/json');

// Check if user is admin or manager
$guard = $auth->requireAuthentication('manager');
if (isset($guard['error'])) {
    echo json_encode($guard);
    exit;
}

// Get all regular users
$result = $conn->query("
    SELECT id, name, email, username, created_at
    FROM users
    WHERE role = 'user'
    ORDER BY created_at DESC
");

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

echo json_encode(['users' => $users]);
?>
