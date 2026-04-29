<?php
require_once __DIR__ . '/config.php';
if (empty($_SESSION['2fa_pending']) || empty($_SESSION['2fa_user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2FA Verification - Auth System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="navbar-brand">Auth System</div>
        </div>
    </nav>

    <div class="container auth-container">
        <div class="auth-card">
            <h2>2FA Verification</h2>
            <p class="subtitle">Enter the 6-digit code from your authenticator app</p>
            <div id="alert" class="alert" style="display: none;"></div>
            
            <form id="verify2faForm" onsubmit="handleVerify2FA(event)">
                <div class="form-group">
                    <label for="code">2FA Code</label>
                    <input 
                        type="text" 
                        id="code" 
                        name="code" 
                        maxlength="6" 
                        placeholder="000000" 
                        pattern="[0-9]{6}"
                        required
                    >
                </div>

                <button type="submit" class="btn btn-primary btn-block">Verify</button>
            </form>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        // Auto-format input to only accept numbers
        document.getElementById('code').addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/[^0-9]/g, '').slice(0, 6);
        });

        async function handleVerify2FA(event) {
            event.preventDefault();
            
            const code = document.getElementById('code').value;
            const alertEl = document.getElementById('alert');
            
            if (code.length !== 6) {
                showAlert(alertEl, 'Please enter a 6-digit code', 'error');
                return;
            }
            
            try {
                const response = await fetch('api.php?action=verify2fa', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ code: parseInt(code) })
                });
                
                const data = await response.json();
                
                if (data.error) {
                    showAlert(alertEl, data.error, 'error');
                } else if (data.success) {
                    localStorage.setItem('token', data.token);
                    localStorage.setItem('user', JSON.stringify(data.user));
                    window.location.href = 'dashboard.php';
                }
            } catch (error) {
                showAlert(alertEl, '2FA verification failed', 'error');
            }
        }
    </script>
</body>
</html>
