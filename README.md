# Auth System - Web-Based Authentication

Complete web-based authentication system using HTML/CSS/JavaScript + PHP with MySQL

## Features

✅ User Registration & Login
✅ Password Hashing (bcrypt)
✅ JWT Token-Based Authentication
✅ Two-Factor Authentication (2FA) with QR Code
✅ Role-Based Access Control (RBAC) - 3 Roles (Admin, Manager, User)
✅ Protected Routes by Role
✅ Audit Logging
✅ Modern Responsive UI

## Project Structure

```
auth-system/
├── config.php                 # Database configuration
├── api.php                    # Main API endpoint
├── index.php                  # Home page
├── register.php               # Registration page
├── login.php                  # Login page
├── 2fa-verify.php             # 2FA verification page
├── dashboard.php              # Main dashboard
├── profile.php                # User profile
├── admin.php                  # Admin panel
├── manager.php                # Manager panel
│
├── includes/
│   ├── Auth.php               # Authentication class
│   └── phpqrcode/
│       └── qrlib.php          # QR code library
│
├── api/
│   ├── admin-stats.php        # Admin statistics
│   ├── users-list.php         # All users list
│   └── manager-users.php      # Managed users list
│
└── assets/
    ├── css/
    │   └── style.css          # Main stylesheet
    └── js/
        └── main.js            # Main JavaScript
```

## Database Setup

### 1. Create Database in XAMPP

1. Open phpMyAdmin: http://localhost/phpmyadmin
2. Create database: `auth_system_db`
3. Run the SQL queries from your document

### 2. Database Tables

**Users Table:**
```sql
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## Installation Steps

### Step 1: Place Files in XAMPP

1. Copy entire `auth-system` folder to: `C:\xampp\htdocs\`
2. Path should be: `C:\xampp\htdocs\auth-system\`

### Step 2: Database Setup

1. Open phpMyAdmin: http://localhost/phpmyadmin
2. Create database: `auth_system_db`
3. Select the database
4. Go to "Import" tab
5. Copy and paste the SQL queries from your document
6. Click Import

### Step 3: Configure Database Connection

Edit `config.php` if needed:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Leave empty for default XAMPP
define('DB_NAME', 'auth_system_db');
```

### Step 4: Start XAMPP

1. Open XAMPP Control Panel
2. Start Apache and MySQL services

### Step 5: Access the Application

Open in browser: **http://localhost/auth-system/**

## Test Credentials

After running SQL queries:

**Admin User:**
- Email: admin@example.com
- Password: password123

**Manager User:**
- Email: manager@example.com
- Password: password123

**Regular User:**
- Email: user@example.com
- Password: password123

## System Flow

1. **User Registration**
   - Fill registration form
   - Password is hashed with bcrypt
   - User created in database

2. **Login**
   - Enter email/username and password
   - Password verified against hash
   - If 2FA enabled → redirected to 2FA verification
   - If 2FA disabled → JWT token generated and user logged in

3. **2FA Setup (Optional)**
   - User scans QR code with authenticator app
   - Enters 6-digit code to verify
   - 2FA enabled in database

4. **Protected Routes**
   - User dashboard - accessible to all authenticated users
   - Profile - accessible to all authenticated users
   - Manager panel - only for managers and admins
   - Admin panel - only for admins

## API Endpoints

### Authentication
- `POST api.php?action=register` - Register new user
- `POST api.php?action=login` - Login user
- `POST api.php?action=logout` - Logout user
- `GET api.php?action=verify-auth` - Verify token

### 2FA
- `POST api.php?action=setup2fa` - Setup 2FA
- `POST api.php?action=verify2fa` - Verify 2FA code

### Admin/Manager
- `GET api/admin-stats.php` - Admin statistics
- `GET api/users-list.php` - All users list
- `GET api/manager-users.php` - Managed users list

## Security Features

✅ Passwords hashed with bcrypt (cost 12)
✅ JWT tokens for session management
✅ 2FA with TOTP (Time-based One-Time Password)
✅ Role-based access control
✅ Session-based authentication
✅ CSRF protection via sessions
✅ Input validation
✅ Protected API endpoints

## Roles & Permissions

### Admin
- View system statistics
- View all users
- Manage user roles
- Access admin panel
- Access manager functions

### Manager
- View managed users
- Monitor user activities
- Access manager panel

### User
- View own profile
- Setup 2FA
- Access user dashboard

## Troubleshooting

### Database Connection Error
- Ensure XAMPP MySQL is running
- Check database credentials in `config.php`
- Verify database `auth_system_db` exists

### PHP Errors
- Check PHP error logs in XAMPP
- Ensure all required folders exist: `api/`, `includes/`, `assets/`

### QR Code Not Displaying
- The system uses an online QR code API
- Ensure internet connection is available
- Or download phpqrcode library and install locally

### Session Not Working
- Clear browser cookies
- Check PHP session configuration
- Ensure `config.php` is included properly

## Technologies Used

**Frontend:**
- HTML5
- CSS3
- JavaScript (Vanilla)
- Bootstrap-like responsive design

**Backend:**
- PHP 7.4+
- MySQL 5.7+
- bcrypt for password hashing
- JWT for tokens
- TOTP for 2FA

## File Permissions

Ensure these folders have write permissions:
- `qrcodes/` - for storing QR code images

```bash
chmod 755 qrcodes/
```

## Git Setup

Initialize git repository:

```bash
cd auth-system
git init
git add .
git commit -m "Initial commit: Complete authentication system"
git remote add origin YOUR_GITHUB_URL
git push -u origin main
```

## License

MIT License

## Support

For issues or questions, check the documentation or review the code comments.
