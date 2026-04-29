<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/Auth.php';
$guard = $auth->requireAuthentication('admin');
if (isset($guard['error'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Auth System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-admin">
        <div class="container">
            <div class="navbar-brand">Auth System - Admin</div>
            <div class="navbar-menu">
                <div class="navbar-item">
                    <a href="dashboard.php" class="navbar-link">Dashboard</a>
                </div>
                <div class="navbar-item">
                    <a href="profile.php" class="navbar-link">Profile</a>
                </div>
                <div class="navbar-item">
                    <button onclick="logout()" class="btn btn-outline">Logout</button>
                </div>
            </div>
        </div>
    </nav>

    <div class="container admin-container">
        <h1>Admin Dashboard</h1>
        
        <div id="adminContent" class="admin-content">
            <div class="loading">Loading admin data...</div>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        async function loadAdminDashboard() {
            const user = JSON.parse(localStorage.getItem('user') || '{}');
            
            if (!user.id || user.role !== 'admin') {
                window.location.href = 'dashboard.php';
                return;
            }
            
            const content = document.getElementById('adminContent');
            
            // Fetch admin statistics from database
            try {
                const response = await authFetch('api/admin-stats.php');
                const stats = await response.json();
                
                content.innerHTML = `
                    <div class="stats-grid">
                        <div class="stat-card">
                            <h3>Total Users</h3>
                            <p class="stat-number">${stats.total_users || 0}</p>
                        </div>
                        <div class="stat-card">
                            <h3>Admins</h3>
                            <p class="stat-number">${stats.total_admins || 0}</p>
                        </div>
                        <div class="stat-card">
                            <h3>Managers</h3>
                            <p class="stat-number">${stats.total_managers || 0}</p>
                        </div>
                        <div class="stat-card">
                            <h3>Regular Users</h3>
                            <p class="stat-number">${stats.total_regular_users || 0}</p>
                        </div>
                        <div class="stat-card">
                            <h3>2FA Enabled</h3>
                            <p class="stat-number">${stats.two_fa_enabled || 0}</p>
                        </div>
                        <div class="stat-card">
                            <h3>Active Users</h3>
                            <p class="stat-number">${stats.active_users || 0}</p>
                        </div>
                    </div>
                    
                    <div class="users-section">
                        <h2>All Users</h2>
                        <div id="usersList" class="users-list"></div>
                    </div>
                `;
                
                // Load users list
                loadUsersList();
            } catch (error) {
                content.innerHTML = '<p>Failed to load admin data</p>';
            }
        }

        async function loadUsersList() {
            try {
                const response = await authFetch('api/users-list.php');
                const data = await response.json();
                
                if (data.users && data.users.length > 0) {
                    let html = '<table class="users-table"><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>2FA</th><th>Status</th></tr></thead><tbody>';
                    
                    data.users.forEach(user => {
                        html += `
                            <tr>
                                <td>${user.id}</td>
                                <td>${user.name}</td>
                                <td>${user.email}</td>
                                <td><span class="badge badge-${user.role}">${user.role}</span></td>
                                <td>${user.two_fa_enabled ? '✓' : '✗'}</td>
                                <td>${user.is_active ? 'Active' : 'Inactive'}</td>
                            </tr>
                        `;
                    });
                    
                    html += '</tbody></table>';
                    document.getElementById('usersList').innerHTML = html;
                } else {
                    document.getElementById('usersList').innerHTML = '<p>No users found</p>';
                }
            } catch (error) {
                document.getElementById('usersList').innerHTML = '<p>Failed to load users</p>';
            }
        }

        window.addEventListener('load', loadAdminDashboard);
    </script>
</body>
</html>
