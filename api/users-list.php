<?php
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
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
