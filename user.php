<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/Auth.php';
$guard = $auth->requireAuthentication('user');
if (isset($guard['error'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Page - Auth System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="navbar-brand">Auth System - User</div>
            <div class="navbar-menu">
                <div class="navbar-item"><a href="dashboard.php" class="navbar-link">Dashboard</a></div>
                <div class="navbar-item"><a href="profile.php" class="navbar-link">Profile</a></div>
                <div class="navbar-item"><button onclick="logout()" class="btn btn-outline">Logout</button></div>
            </div>
        </div>
    </nav>
    <div class="container profile-container">
        <div class="profile-card">
            <h2>User Page</h2>
            <p>This route is protected for authenticated users with `user` role access.</p>
        </div>
    </div>
    <script src="assets/js/main.js"></script>
</body>
</html>
