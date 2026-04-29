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

// Get statistics
$result = $conn->query("
    SELECT 
        COUNT(*) as total_users,
        SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as total_admins,
        SUM(CASE WHEN role = 'manager' THEN 1 ELSE 0 END) as total_managers,
        SUM(CASE WHEN role = 'user' THEN 1 ELSE 0 END) as total_regular_users,
        SUM(CASE WHEN two_fa_enabled = 1 THEN 1 ELSE 0 END) as two_fa_enabled,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_users
    FROM users
");

$stats = $result->fetch_assoc();
echo json_encode($stats);
?>
