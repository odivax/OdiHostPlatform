<?php
// Router for PHP built-in server
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// If it's a real file, serve it
if (file_exists(__DIR__ . $uri) && is_file(__DIR__ . $uri)) {
    return false;
}

// Otherwise, route through index.php
require_once __DIR__ . '/index.php';
?>