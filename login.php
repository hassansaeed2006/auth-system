<!DOCTYPE html>
<!-- Login Page -->
<!-- Allows users to authenticate with email/username and password -->
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Auth System</title>
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
            <h2>Login</h2>
            <div id="alert" class="alert" style="display: none;"></div>
            
            <form id="loginForm" onsubmit="handleLogin(event)">
                <div class="form-group">
                    <label for="email">Email or Username</label>
                    <input type="text" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>

            <p class="auth-link">Don't have an account? <a href="register.php">Register</a></p>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        // Handle login form submission
        async function handleLogin(event) {
            event.preventDefault();
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const alertEl = document.getElementById('alert');
            
            try {
                const response = await fetch('api.php?action=login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ email, password })
                });
                
                const data = await response.json();
                
                if (data.error) {
                    showAlert(alertEl, data.error, 'error');
                } else if (data['2fa_required']) {
                    sessionStorage.setItem('2fa_user_id', data.user_id);
                    window.location.href = '2fa-verify.php';
                } else if (data.success) {
                    localStorage.setItem('token', data.token);
                    localStorage.setItem('user', JSON.stringify(data.user));
                    window.location.href = 'dashboard.php';
                }
            } catch (error) {
                showAlert(alertEl, 'Login failed', 'error');
            }
        }
    </script>
</body>
</html>
