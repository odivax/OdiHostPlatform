# OdiHost - Shared Hosting Setup Guide

## Prerequisites

- Shared hosting account with PHP 8.0+ support
- MySQL database access
- phpMyAdmin access
- Google Cloud Console account for OAuth setup

## Step 1: Database Setup (phpMyAdmin)

1. **Login to phpMyAdmin** from your hosting control panel

2. **Create a new database**:
   - Click "Databases" tab
   - Enter database name: `odihost_db` (or your preferred name)
   - Select Collation: `utf8mb4_unicode_ci`
   - Click "Create"

3. **Create database tables** by running these SQL commands:

```sql
-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    google_id VARCHAR(100) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    picture TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Projects table  
CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    custom_domain VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_slug (user_id, slug),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Project files table
CREATE TABLE project_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    content LONGTEXT,
    file_type VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_project_file (project_id, filename),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Step 2: Google OAuth Setup

1. **Go to Google Cloud Console**: https://console.cloud.google.com

2. **Create a new project**:
   - Click "Select a project" → "New Project"
   - Enter project name: "OdiHost"
   - Click "Create"

3. **Enable Google+ API**:
   - Go to "APIs & Services" → "Library"
   - Search for "Google+ API"
   - Click and enable it

4. **Create OAuth credentials**:
   - Go to "APIs & Services" → "Credentials"
   - Click "Create Credentials" → "OAuth client ID"
   - Select "Web application"
   - Add authorized redirect URI: `https://yourdomain.com/auth/callback.php`
   - Click "Create"
   - **Save the Client ID and Client Secret**

## Step 3: File Upload

1. **Upload all OdiHost files** to your hosting directory (usually `public_html` or `www`)

2. **Set folder permissions**:
   - `users/` folder: 755 (create if doesn't exist)
   - All PHP files: 644
   - `assets/` folder and contents: 644

## Step 4: Configuration

1. **Copy the configuration template**:
   - Copy `shared_hosting_config.php` to `config.php`
   - Or edit `config.php` directly with these values:

```php
<?php
// Application Configuration
define('APP_NAME', 'OdiHost');
define('APP_URL', 'https://yourdomain.com'); // Change to your domain
define('USERS_DIR', __DIR__ . '/users');

// MySQL Database Configuration
define('DB_HOST', 'localhost'); // Usually localhost on shared hosting
define('DB_NAME', 'your_db_name'); // Database name from Step 1
define('DB_USER', 'your_db_user'); // Database username
define('DB_PASS', 'your_db_password'); // Database password

// Google OAuth Configuration
define('GOOGLE_CLIENT_ID', 'your_google_client_id');
define('GOOGLE_CLIENT_SECRET', 'your_google_client_secret');
define('GOOGLE_REDIRECT_URI', APP_URL . '/auth/callback.php');

// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? '1' : '0');
    ini_set('session.use_strict_mode', '1');
}

// Ensure users directory exists
if (!is_dir(USERS_DIR)) {
    mkdir(USERS_DIR, 0755, true);
}
?>
```

2. **Run the installation script**:
   - Visit `https://yourdomain.com/install.php` in your browser
   - This will automatically create the database tables
   - Delete `install.php` after successful installation

## Step 5: Domain Setup (Optional)

### For Subdomain Hosting (username.yourdomain.com):

1. **Add wildcard DNS record**:
   - In your domain DNS settings
   - Add A record: `*.yourdomain.com` → Your server IP

2. **Configure subdomain in hosting**:
   - Add wildcard subdomain in cPanel
   - Point to your OdiHost directory

### For Custom Domains:

Users can point their domains to your server and add them via the platform.

## Step 6: Security Setup

1. **Update .htaccess** (if not already present):
```apache
RewriteEngine On

# Force HTTPS (recommended)
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Remove trailing slashes
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)/$ /$1 [R=301,L]

# Handle all requests through index.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_URI} !^/(api|auth|assets|dashboard\.php|editor\.php)
RewriteRule ^(.*)$ /index.php [L]

# Security headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options SAMEORIGIN
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

# Prevent access to sensitive files
<Files "*.json">
    Order Allow,Deny
    Deny from all
</Files>

<Files ".htaccess">
    Order Allow,Deny
    Deny from all
</Files>
</apache>
```

## Step 7: Testing

1. **Test the installation**:
   - Visit `https://yourdomain.com`
   - Should redirect to login page
   - Try Google OAuth login
   - Create a test project
   - Test the code editor

2. **Test subdomain routing** (if configured):
   - Create a project
   - Visit `https://username.yourdomain.com/projectname`

## Troubleshooting

### Common Issues:

1. **Database connection errors**:
   - Verify database credentials in `config.php`
   - Check if MySQL extension is enabled in PHP

2. **Google OAuth errors**:
   - Verify redirect URI matches exactly
   - Check if domain is added to authorized domains

3. **File permissions**:
   - Ensure `users/` folder is writable (755)
   - Check PHP error logs in hosting control panel

4. **Subdomain not working**:
   - Verify wildcard DNS is properly configured
   - Check if hosting supports wildcard subdomains

### Error Logs:

- Check PHP error logs in your hosting control panel
- Enable error reporting temporarily by adding to `config.php`:
```php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
```

## Support

For additional support:
- Check hosting documentation for specific configurations
- Verify PHP version compatibility (8.0+ recommended)
- Ensure all required PHP extensions are enabled (PDO, MySQL, cURL, JSON)