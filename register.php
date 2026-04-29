<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Auth System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="navbar-brand">Auth System</div>
            <div class="navbar-menu">
                <div class="navbar-item">
                    <a href="index.php" class="navbar-link">Home</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container auth-container">
        <div class="auth-card">
            <h2>Register</h2>
            <div id="alert" class="alert" style="display: none;"></div>
            
            <form id="registerForm" onsubmit="handleRegister(event)">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="form-group">
                    <label for="confirmPassword">Confirm Password</label>
                    <input type="password" id="confirmPassword" name="confirmPassword" required>
                </div>

                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role">
                        <option value="user">User</option>
                        <option value="manager">Manager</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Register</button>
            </form>

            <p class="auth-link">Already have an account? <a href="login.php">Login</a></p>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        async function handleRegister(event) {
            event.preventDefault();
            
            const name = document.getElementById('name').value;
            const email = document.getElementById('email').value;
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const role = document.getElementById('role').value;
            const alertEl = document.getElementById('alert');
            
            if (password !== confirmPassword) {
                showAlert(alertEl, 'Passwords do not match', 'error');
                return;
            }
            
            try {
                const response = await fetch('api.php?action=register', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ name, email, username, password, role })
                });
                
                const data = await response.json();
                
                if (data.error) {
                    showAlert(alertEl, data.error, 'error');
                } else if (data.success) {
                    showAlert(alertEl, data.success, 'success');
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 2000);
                }
            } catch (error) {
                showAlert(alertEl, 'Registration failed', 'error');
            }
        }
    </script>
</body>
</html>
