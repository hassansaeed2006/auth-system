<!DOCTYPE html>
<!-- Registration Page -->
<!-- Allows new users to create an account with 2FA setup -->
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
            <div id="registration2faSection" class="setup-2fa-section" style="display: none;">
                <h3>Complete 2FA Setup</h3>
                <p>Scan the QR code in Google Authenticator, Microsoft Authenticator, or Authy.</p>
                <div id="registerQrCodeContainer" class="qr-container"></div>
                <div class="form-group">
                    <label for="registerSecretKey">Secret Key (backup):</label>
                    <code id="registerSecretKey"></code>
                </div>
                <form id="registerVerify2faForm" onsubmit="handleRegistrationVerify2FA(event)">
                    <div class="form-group">
                        <label for="registerTwoFACode">Enter 6-digit code</label>
                        <input type="text" id="registerTwoFACode" maxlength="6" pattern="[0-9]{6}" required>
                    </div>
                    <button type="submit" class="btn btn-success btn-block">Verify 2FA and Finish</button>
                </form>
            </div>
            
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

                    if (data.two_fa_setup && data.user_id) {
                        sessionStorage.setItem('register_2fa_user_id', String(data.user_id));
                        document.getElementById('registerSecretKey').textContent = data.two_fa_setup.secret;
                        document.getElementById('registerQrCodeContainer').innerHTML = `<img src="${data.two_fa_setup.qr_code}" alt="Registration 2FA QR Code">`;
                        document.getElementById('registerForm').style.display = 'none';
                        document.getElementById('registration2faSection').style.display = 'block';
                        return;
                    }

                    setTimeout(() => { window.location.href = 'login.php'; }, 1500);
                }
            } catch (error) {
                showAlert(alertEl, 'Registration failed', 'error');
            }
        }

        document.getElementById('registerTwoFACode').addEventListener('input', function (e) {
            e.target.value = e.target.value.replace(/[^0-9]/g, '').slice(0, 6);
        });

        async function handleRegistrationVerify2FA(event) {
            event.preventDefault();
            const code = document.getElementById('registerTwoFACode').value;
            const userId = parseInt(sessionStorage.getItem('register_2fa_user_id') || '0', 10);
            const alertEl = document.getElementById('alert');

            if (!userId || code.length !== 6) {
                showAlert(alertEl, 'Invalid 2FA setup state. Please register again.', 'error');
                return;
            }

            try {
                const response = await fetch('api.php?action=verify2fa-registration', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: userId, code: parseInt(code, 10) })
                });
                const data = await response.json();
                if (data.error) {
                    showAlert(alertEl, data.error, 'error');
                } else {
                    sessionStorage.removeItem('register_2fa_user_id');
                    showAlert(alertEl, data.success || '2FA configured successfully. You can now login.', 'success');
                    setTimeout(() => { window.location.href = 'login.php'; }, 1500);
                }
            } catch (error) {
                showAlert(alertEl, 'Could not verify registration 2FA', 'error');
            }
        }
    </script>
</body>
</html>
