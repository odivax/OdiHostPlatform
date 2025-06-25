<?php
// Shared Hosting Configuration Template
// Copy this to config.php and modify the values below

// Application Configuration
define('APP_NAME', 'OdiHost');
define('APP_URL', 'https://yourdomain.com'); // Change to your actual domain
define('USERS_DIR', __DIR__ . '/users');

// MySQL Database Configuration (Required for shared hosting)
define('DB_HOST', 'localhost'); // Usually localhost on shared hosting
define('DB_NAME', 'your_database_name'); // Your MySQL database name
define('DB_USER', 'your_database_username'); // Your MySQL username
define('DB_PASS', 'your_database_password'); // Your MySQL password

// Google OAuth Configuration
define('GOOGLE_CLIENT_ID', 'your_google_client_id_here');
define('GOOGLE_CLIENT_SECRET', 'your_google_client_secret_here');
define('GOOGLE_REDIRECT_URI', APP_URL . '/auth/callback.php');

// Ensure users directory exists
if (!is_dir(USERS_DIR)) {
    mkdir(USERS_DIR, 0755, true);
}

// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? '1' : '0');
    ini_set('session.use_strict_mode', '1');
}
?>