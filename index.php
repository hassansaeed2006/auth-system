<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auth System - Home</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="navbar-brand">Auth System</div>
            <div class="navbar-menu" id="navbarMenu">
                <div class="navbar-item">
                    <a href="index.php" class="navbar-link active">Home</a>
                </div>
                <div class="navbar-item" id="authNav">
                    <a href="login.php" class="navbar-link">Login</a>
                    <a href="register.php" class="navbar-link">Register</a>
                </div>
                <div class="navbar-item" id="userNav" style="display: none;">
                    <a href="dashboard.php" class="navbar-link">Dashboard</a>
                    <a href="profile.php" class="navbar-link">Profile</a>
                    <button onclick="logout()" class="btn btn-outline">Logout</button>
                </div>
            </div>
        </div>
    </nav>

    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>Secure Authentication System</h1>
                <p>Enterprise-grade authentication with 2FA support</p>
                <div class="hero-buttons">
                    <a href="login.php" class="btn btn-primary">Login</a>
                    <a href="register.php" class="btn btn-secondary">Register</a>
                </div>
            </div>
        </div>
    </section>

    <section class="features">
        <div class="container">
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">🔐</div>
                    <h3>Secure</h3>
                    <p>Password hashing with bcrypt and JWT tokens</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🔑</div>
                    <h3>2FA</h3>
                    <p>Two-factor authentication for enhanced security</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">👥</div>
                    <h3>RBAC</h3>
                    <p>Role-based access control with 3 roles</p>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2026 Auth System. All rights reserved.</p>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>
