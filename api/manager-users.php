<?php
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

// Check if user is admin or manager
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
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
