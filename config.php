<?php
// Application Configuration
define('APP_NAME', 'OdiHost');
define('APP_URL', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://' . $_SERVER['HTTP_HOST']);
define('USERS_DIR', __DIR__ . '/users');

// Google OAuth Configuration
define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: 'your-google-client-id');
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: 'your-google-client-secret');
define('GOOGLE_REDIRECT_URI', APP_URL . '/auth/callback.php');

// Ensure users directory exists
if (!is_dir(USERS_DIR)) {
    mkdir(USERS_DIR, 0755, true);
}

// Session configuration - only set if session hasn't started
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? '1' : '0');
    ini_set('session.use_strict_mode', '1');
}
?>
