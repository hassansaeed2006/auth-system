<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Panel - Auth System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-manager">
        <div class="container">
            <div class="navbar-brand">Auth System - Manager</div>
            <div class="navbar-menu">
                <div class="navbar-item">
                    <a href="dashboard.php" class="navbar-link">Dashboard</a>
                </div>
                <div class="navbar-item">
                    <a href="profile.php" class="navbar-link">Profile</a>
                </div>
                <div class="navbar-item" id="adminLink" style="display: none;">
                    <a href="admin.php" class="navbar-link">Admin</a>
                </div>
                <div class="navbar-item">
                    <button onclick="logout()" class="btn btn-outline">Logout</button>
                </div>
            </div>
        </div>
    </nav>

    <div class="container manager-container">
        <h1>Manager Dashboard</h1>
        
        <div id="managerContent" class="manager-content">
            <div class="loading">Loading manager data...</div>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        async function loadManagerDashboard() {
            const user = JSON.parse(localStorage.getItem('user') || '{}');
            
            if (!user.id || (user.role !== 'admin' && user.role !== 'manager')) {
                window.location.href = 'dashboard.php';
                return;
            }
            
            // Show admin link if user is admin
            if (user.role === 'admin') {
                document.getElementById('adminLink').style.display = 'block';
            }
            
            const content = document.getElementById('managerContent');
            
            try {
                const response = await fetch('api/manager-users.php');
                const data = await response.json();
                
                if (data.users && data.users.length > 0) {
                    let html = `
                        <div class="manager-info">
                            <h2>Managed Users (${data.users.length})</h2>
                        </div>
                        <table class="users-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Username</th>
                                    <th>Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;
                    
                    data.users.forEach(user => {
                        const joinDate = new Date(user.created_at).toLocaleDateString();
                        html += `
                            <tr>
                                <td>${user.id}</td>
                                <td>${user.name}</td>
                                <td>${user.email}</td>
                                <td>${user.username}</td>
                                <td>${joinDate}</td>
                            </tr>
                        `;
                    });
                    
                    html += '</tbody></table>';
                    content.innerHTML = html;
                } else {
                    content.innerHTML = '<p>No users to manage</p>';
                }
            } catch (error) {
                content.innerHTML = '<p>Failed to load manager data</p>';
            }
        }

        window.addEventListener('load', loadManagerDashboard);
    </script>
</body>
</html>
