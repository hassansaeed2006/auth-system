<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Auth System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="navbar-brand">Auth System</div>
            <div class="navbar-menu">
                <div class="navbar-item">
                    <a href="dashboard.php" class="navbar-link">Dashboard</a>
                </div>
                <div class="navbar-item">
                    <button onclick="logout()" class="btn btn-outline">Logout</button>
                </div>
            </div>
        </div>
    </nav>

    <div class="container profile-container">
        <div class="profile-card">
            <h2>User Profile</h2>
            <div id="alert" class="alert" style="display: none;"></div>
            
            <div id="profileContent" class="profile-content">
                <div class="loading">Loading profile...</div>
            </div>

            <div id="setup2faSection" style="display: none;" class="setup-2fa-section">
                <h3>Setup Two-Factor Authentication</h3>
                <p>Scan this QR code with your authenticator app (Google Authenticator, Microsoft Authenticator, or Authy)</p>
                <div id="qrCodeContainer" class="qr-container"></div>
                
                <div class="form-group">
                    <label for="secretKey">Secret Key (backup):</label>
                    <code id="secretKey"></code>
                </div>

                <form id="enable2faForm" onsubmit="handleEnable2FA(event)">
                    <div class="form-group">
                        <label for="twoFACode">Enter 6-digit code:</label>
                        <input 
                            type="text" 
                            id="twoFACode" 
                            name="twoFACode" 
                            maxlength="6" 
                            placeholder="000000"
                            pattern="[0-9]{6}"
                            required
                        >
                    </div>
                    <button type="submit" class="btn btn-success">Enable 2FA</button>
                    <button type="button" class="btn btn-secondary" onclick="cancelSetup2FA()">Cancel</button>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        async function loadProfile() {
            const user = JSON.parse(localStorage.getItem('user') || '{}');
            
            if (!user.id) {
                window.location.href = 'login.php';
                return;
            }
            
            const content = document.getElementById('profileContent');
            content.innerHTML = `
                <div class="profile-info">
                    <div class="info-row">
                        <label>Name:</label>
                        <span>${user.name || 'N/A'}</span>
                    </div>
                    <div class="info-row">
                        <label>Email:</label>
                        <span>${user.email || 'N/A'}</span>
                    </div>
                    <div class="info-row">
                        <label>Username:</label>
                        <span>${user.username || 'N/A'}</span>
                    </div>
                    <div class="info-row">
                        <label>Role:</label>
                        <span><span class="badge badge-${user.role}">${user.role.toUpperCase()}</span></span>
                    </div>
                    <div class="info-row">
                        <label>2FA Status:</label>
                        <span id="twoFAStatus" class="badge badge-warning">Disabled</span>
                    </div>
                </div>
                
                <button id="setup2faBtn" class="btn btn-primary" onclick="showSetup2FA()">Setup 2FA</button>
            `;
        }

        async function showSetup2FA() {
            try {
                const response = await fetch('api.php?action=setup2fa');
                const data = await response.json();
                
                if (data.error) {
                    showAlert(document.getElementById('alert'), data.error, 'error');
                } else {
                    document.getElementById('secretKey').textContent = data.secret;
                    document.getElementById('qrCodeContainer').innerHTML = `<img src="${data.qr_code}" alt="QR Code">`;
                    document.getElementById('setup2faSection').style.display = 'block';
                    document.getElementById('profileContent').style.display = 'none';
                }
            } catch (error) {
                showAlert(document.getElementById('alert'), 'Failed to setup 2FA', 'error');
            }
        }

        function cancelSetup2FA() {
            document.getElementById('setup2faSection').style.display = 'none';
            document.getElementById('profileContent').style.display = 'block';
        }

        async function handleEnable2FA(event) {
            event.preventDefault();
            const code = document.getElementById('twoFACode').value;
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
                    showAlert(alertEl, '2FA enabled successfully!', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                }
            } catch (error) {
                showAlert(alertEl, 'Failed to enable 2FA', 'error');
            }
        }

        // Auto-format 2FA code input
        document.addEventListener('DOMContentLoaded', function() {
            const codeInput = document.getElementById('twoFACode');
            if (codeInput) {
                codeInput.addEventListener('input', function(e) {
                    e.target.value = e.target.value.replace(/[^0-9]/g, '').slice(0, 6);
                });
            }
        });

        window.addEventListener('load', loadProfile);
    </script>
</body>
</html>
