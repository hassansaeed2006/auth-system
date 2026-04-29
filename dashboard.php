<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/Auth.php';
$guard = $auth->requireAuthentication();
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
    <title>Dashboard - Auth System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="navbar-brand">Auth System</div>
            <div class="navbar-menu">
                <div class="navbar-item">
                    <a href="user.php" class="navbar-link">User</a>
                </div>
                <div class="navbar-item">
                    <a href="profile.php" class="navbar-link">Profile</a>
                </div>
                <div class="navbar-item" id="adminNav" style="display: none;">
                    <a href="admin.php" class="navbar-link">Admin</a>
                </div>
                <div class="navbar-item" id="managerNav" style="display: none;">
                    <a href="manager.php" class="navbar-link">Manager</a>
                </div>
                <div class="navbar-item">
                    <button onclick="logout()" class="btn btn-outline">Logout</button>
                </div>
            </div>
        </div>
    </nav>

    <div class="container dashboard-container">
        <div class="dashboard-header">
            <h1>Welcome, <span id="userName">User</span>!</h1>
            <p>Role: <span id="userRole" class="badge">User</span></p>
        </div>

        <div id="dashboardContent" class="dashboard-content">
            <div class="loading">Loading...</div>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        async function loadDashboard() {
            const user = JSON.parse(localStorage.getItem('user') || '{}');
            
            if (!user.id) {
                window.location.href = 'login.php';
                return;
            }
            
            document.getElementById('userName').textContent = user.name || user.email;
            document.getElementById('userRole').textContent = user.role.toUpperCase();
            document.getElementById('userRole').className = 'badge badge-' + user.role;
            
            // Show role-specific navigation
            if (user.role === 'admin') {
                document.getElementById('adminNav').style.display = 'block';
            } else if (user.role === 'manager') {
                document.getElementById('managerNav').style.display = 'block';
            }
            
            // Load dashboard data
            loadDashboardData(user.role);
        }

        function loadDashboardData(role) {
            const content = document.getElementById('dashboardContent');
            
            if (role === 'admin') {
                content.innerHTML = `
                    <div class="dashboard-grid">
                        <div class="dashboard-card">
                            <h3>System Overview</h3>
                            <p>Manage all users and system settings</p>
                            <a href="admin.php" class="btn btn-primary">Go to Admin Panel</a>
                        </div>
                        <div class="dashboard-card">
                            <h3>Manager Access</h3>
                            <p>View managed users</p>
                            <a href="manager.php" class="btn btn-primary">Go to Manager Panel</a>
                        </div>
                        <div class="dashboard-card">
                            <h3>Profile</h3>
                            <p>View and edit your profile</p>
                            <a href="profile.php" class="btn btn-primary">Go to Profile</a>
                        </div>
                    </div>
                `;
            } else if (role === 'manager') {
                content.innerHTML = `
                    <div class="dashboard-grid">
                        <div class="dashboard-card">
                            <h3>Manage Users</h3>
                            <p>View and manage user accounts</p>
                            <a href="manager.php" class="btn btn-primary">Go to Manager Panel</a>
                        </div>
                        <div class="dashboard-card">
                            <h3>Profile</h3>
                            <p>View and edit your profile</p>
                            <a href="profile.php" class="btn btn-primary">Go to Profile</a>
                        </div>
                    </div>
                `;
            } else {
                content.innerHTML = `
                    <div class="dashboard-grid">
                        <div class="dashboard-card">
                            <h3>User Page</h3>
                            <p>Access your user-only route</p>
                            <a href="user.php" class="btn btn-primary">Go to User Page</a>
                        </div>
                        <div class="dashboard-card">
                            <h3>Profile</h3>
                            <p>View and edit your profile</p>
                            <a href="profile.php" class="btn btn-primary">Go to Profile</a>
                        </div>
                    </div>
                `;
            }
        }

        // Load dashboard on page load
        window.addEventListener('load', loadDashboard);
    </script>
</body>
</html>
