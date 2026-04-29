# XAMPP Auth System - Complete Setup Guide

## 📁 Project Location
- **Path:** `C:\xampp\htdocs\auth-system`
- **Access:** http://localhost/auth-system/

---

## 🗄️ DATABASE SETUP (STEP 1)

### 1. Start XAMPP
- Open XAMPP Control Panel
- Start **Apache** and **MySQL**
- Verify both are running (green indicators)

### 2. Create Database in phpMyAdmin
1. Open **http://localhost/phpmyadmin** in your browser
2. Click **"New"** on the left sidebar
3. **Database name:** `auth_system_db`
4. **Collation:** `utf8mb4_unicode_ci`
5. Click **"Create"**

### 3. Import SQL Queries
1. Select the database **`auth_system_db`**
2. Click on **"Import"** tab
3. **Choose File** - Select the SQL file or copy-paste:

```sql
-- Create Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    username VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(10) NOT NULL CHECK (role IN ('admin', 'manager', 'user')),
    two_fa_secret VARCHAR(255) NULL,
    two_fa_enabled BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_username (username),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Audit Logs Table
CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Admin User (password: password123)
INSERT INTO users (name, email, username, password_hash, role, two_fa_enabled, is_active) 
VALUES ('Admin User', 'admin@example.com', 'admin', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5YmMxSUGywa44', 'admin', FALSE, TRUE);

-- Insert Manager User (password: password123)
INSERT INTO users (name, email, username, password_hash, role, two_fa_enabled, is_active) 
VALUES ('Manager User', 'manager@example.com', 'manager', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5YmMxSUGywa44', 'manager', FALSE, TRUE);

-- Insert Regular User (password: password123)
INSERT INTO users (name, email, username, password_hash, role, two_fa_enabled, is_active) 
VALUES ('Regular User', 'user@example.com', 'user', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5YmMxSUGywa44', 'user', FALSE, TRUE);
```

4. Click **"Import"**
5. Verify tables are created

---

## 🚀 ACCESSING THE APPLICATION

### 1. Open in Browser
- URL: **http://localhost/auth-system/**

### 2. Test Credentials

**Admin Account:**
```
Email: admin@example.com
Password: password123
Role: Admin
```

**Manager Account:**
```
Email: manager@example.com
Password: password123
Role: Manager
```

**Regular User Account:**
```
Email: user@example.com
Password: password123
Role: User
```

---

## 📋 FEATURES & PAGES

### Public Pages
- **Home (/)** - Landing page with features
- **Register (/register.php)** - Create new account
- **Login (/login.php)** - Login to account

### Protected Pages (Require Login)
- **Dashboard (/dashboard.php)** - User dashboard
- **Profile (/profile.php)** - User profile & 2FA setup
- **2FA Verify (/2fa-verify.php)** - 2FA verification

### Admin Only
- **Admin Panel (/admin.php)** - View all users, statistics

### Manager & Admin
- **Manager Panel (/manager.php)** - View managed users

---

## 🔐 SECURITY FEATURES

1. **Password Hashing**
   - Uses bcrypt with cost 12
   - Passwords never stored as plain text
   - Comparison done securely

2. **JWT Tokens**
   - Generated after successful login
   - Stored in browser localStorage
   - Used for API authentication

3. **2FA (Two-Factor Authentication)**
   - Generate secret key
   - Display QR code
   - Scan with Google Authenticator, Microsoft Authenticator, or Authy
   - Verify 6-digit code
   - 30-second time-based TOTP

4. **Role-Based Access Control**
   - 3 roles: Admin, Manager, User
   - Different permissions for each role
   - Protected routes based on role

5. **Session Management**
   - PHP sessions for backend
   - localStorage for client-side tokens
   - Automatic logout on logout click

---

## 🛠️ TESTING THE SYSTEM

### Test Registration
1. Go to **http://localhost/auth-system/register.php**
2. Fill in the form with new details
3. Choose a role (User, Manager, or Admin)
4. Click Register
5. Verify user is created in phpMyAdmin

### Test Login
1. Go to **http://localhost/auth-system/login.php**
2. Enter credentials: `admin@example.com` / `password123`
3. Click Login
4. Redirected to Dashboard

### Test 2FA
1. Login to your account
2. Go to Profile
3. Click "Setup 2FA"
4. Scan QR code with authenticator app
5. Enter 6-digit code
6. 2FA enabled
7. Next login will require 2FA verification

### Test Role-Based Access
- **Admin:** Can access admin.php and manager.php
- **Manager:** Can access manager.php only
- **User:** Cannot access admin or manager pages

---

## 📊 PROJECT STRUCTURE

```
C:\xampp\htdocs\auth-system\
│
├── config.php                    # Database configuration
├── api.php                       # API endpoint for all actions
├── index.php                     # Home page
├── register.php                  # Registration page
├── login.php                     # Login page
├── 2fa-verify.php                # 2FA verification
├── dashboard.php                 # Dashboard
├── profile.php                   # Profile page
├── admin.php                     # Admin panel
├── manager.php                   # Manager panel
│
├── includes/
│   ├── Auth.php                  # Authentication class (JWT, passwords, 2FA)
│   └── phpqrcode/
│       └── qrlib.php             # QR code generation
│
├── api/
│   ├── admin-stats.php           # Admin statistics
│   ├── users-list.php            # All users
│   └── manager-users.php         # Managed users
│
├── assets/
│   ├── css/
│   │   └── style.css             # All styling
│   └── js/
│       └── main.js               # JavaScript functions
│
├── qrcodes/                      # QR code images folder
├── README.md                     # Documentation
├── .gitignore                    # Git ignore file
└── .git/                         # Git repository
```

---

## 🔧 CONFIGURATION

### Database Connection (config.php)
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');              // Empty for default XAMPP
define('DB_NAME', 'auth_system_db');
```

### JWT Secret (config.php)
```php
define('JWT_SECRET', 'your-jwt-secret-key-change-in-production');
```

---

## 📝 API ENDPOINTS

### Authentication
| Endpoint | Method | Body | Description |
|----------|--------|------|-------------|
| `api.php?action=register` | POST | name, email, username, password, role | Register user |
| `api.php?action=login` | POST | email, password | Login user |
| `api.php?action=logout` | POST | - | Logout user |
| `api.php?action=verify-auth` | GET | - | Verify token |

### 2FA
| Endpoint | Method | Body | Description |
|----------|--------|------|-------------|
| `api.php?action=setup2fa` | POST | - | Setup 2FA |
| `api.php?action=verify2fa` | POST | code | Verify 2FA code |

### Admin/Manager
| Endpoint | Method | Description |
|----------|--------|-------------|
| `api/admin-stats.php` | GET | Get admin statistics |
| `api/users-list.php` | GET | Get all users |
| `api/manager-users.php` | GET | Get managed users |

---

## 🐛 TROUBLESHOOTING

### Problem: "Database connection failed"
**Solution:**
- Ensure MySQL is running in XAMPP
- Check credentials in `config.php`
- Verify database `auth_system_db` exists in phpMyAdmin

### Problem: Blank page or errors
**Solution:**
- Check XAMPP error logs
- Ensure all folders exist: `includes/`, `api/`, `assets/`
- Verify PHP version is 7.4 or higher

### Problem: QR code not showing
**Solution:**
- Ensure internet connection (uses online QR API)
- Clear browser cache
- Try a different browser

### Problem: 2FA code always fails
**Solution:**
- Ensure device time is synced
- Try within 30-second window
- Clear database 2FA_secret and try setup again

---

## 💾 GIT REPOSITORY

### Initialize Git (already done)
```bash
cd C:\xampp\htdocs\auth-system
git init
git add .
git commit -m "Initial commit"
```

### Add to GitHub
```bash
git remote add origin https://github.com/YOUR_USERNAME/auth-system.git
git branch -M main
git push -u origin main
```

### Make New Commits
```bash
git add .
git commit -m "Your commit message"
git push
```

---

## 📋 QUICK CHECKLIST

- [x] XAMPP installed and running
- [ ] Database created: `auth_system_db`
- [ ] SQL queries imported
- [ ] Project files in: `C:\xampp\htdocs\auth-system`
- [ ] Apache and MySQL started
- [ ] Browser access: `http://localhost/auth-system/`
- [ ] Test login with demo credentials
- [ ] Test registration
- [ ] Test 2FA setup
- [ ] Verify all roles work

---

## 🎯 NEXT STEPS

1. **Test the application thoroughly**
2. **Customize the CSS/branding**
3. **Add email verification (optional)**
4. **Deploy to production server**
5. **Set up HTTPS**
6. **Configure environment variables**
7. **Enable audit logging**
8. **Add password reset functionality**

---

## 📞 SUPPORT

For issues:
1. Check README.md
2. Review code comments
3. Check XAMPP error logs
4. Verify database tables in phpMyAdmin

---

**Created:** 2026
**Version:** 1.0.0
**License:** MIT
