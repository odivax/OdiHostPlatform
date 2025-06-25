<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

// Get the host and extract subdomain
$host = $_SERVER['HTTP_HOST'];
$hostParts = explode('.', $host);

// Check if this is the main domain or a subdomain
// For development, also check if we're on localhost/127.0.0.1
$isMainDomain = (count($hostParts) < 3) || 
                ($hostParts[0] === 'www') || 
                (in_array($host, ['localhost:5000', '127.0.0.1:5000']) && empty(trim($_SERVER['REQUEST_URI'], '/')));

if (!$isMainDomain) {
    // This is a subdomain request
    $username = $hostParts[0];
    
    // Extract project name from URL path
    $path = trim($_SERVER['REQUEST_URI'], '/');
    $pathParts = explode('/', $path);
    $projectName = !empty($pathParts[0]) ? $pathParts[0] : 'default';
    
    // Check for custom domain mapping
    $customDomainUser = checkCustomDomain($host);
    if ($customDomainUser) {
        $username = $customDomainUser['username'];
        $projectName = $customDomainUser['project'];
    }
    
    // Serve the project
    serveProject($username, $projectName, $path);
} else {
    // This is the main domain - redirect to dashboard or login
    // Handle specific routes first
    $path = trim($_SERVER['REQUEST_URI'], '/');
    $pathParts = explode('/', $path);
    
    // If path starts with api, auth, assets, or is dashboard/editor, let it pass through
    if (in_array($pathParts[0], ['api', 'auth', 'assets']) || 
        in_array($path, ['dashboard.php', 'editor.php'])) {
        return false; // Let PHP handle the request normally
    }
    
    // For root or other paths, redirect appropriately
    if (isset($_SESSION['user_id'])) {
        header('Location: /dashboard.php');
    } else {
        header('Location: /auth/login.php');
    }
}
exit;

function serveProject($username, $projectName, $path) {
    $userDir = USERS_DIR . '/' . $username;
    $projectDir = $userDir . '/' . $projectName;
    
    if (!is_dir($projectDir)) {
        http_response_code(404);
        echo "Project not found";
        return;
    }
    
    // Determine which file to serve
    $filePath = '';
    if (empty($path) || $path === $projectName) {
        $filePath = $projectDir . '/index.html';
    } else {
        // Remove project name from path if it exists
        $relativePath = str_replace($projectName . '/', '', $path);
        $filePath = $projectDir . '/' . $relativePath;
    }
    
    if (!file_exists($filePath)) {
        $filePath = $projectDir . '/index.html';
    }
    
    if (!file_exists($filePath)) {
        http_response_code(404);
        echo "File not found";
        return;
    }
    
    // Serve the file with appropriate content type
    $extension = pathinfo($filePath, PATHINFO_EXTENSION);
    $contentType = getContentType($extension);
    
    header('Content-Type: ' . $contentType);
    readfile($filePath);
}

function checkCustomDomain($host) {
    require_once 'functions.php';
    $result = getDB()->getProjectByDomain($host);
    if ($result) {
        return ['username' => $result['username'], 'project' => $result['slug']];
    }
    return false;
}

function getContentType($extension) {
    $contentTypes = [
        'html' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'svg' => 'image/svg+xml',
        'gif' => 'image/gif'
    ];
    
    return isset($contentTypes[$extension]) ? $contentTypes[$extension] : 'text/plain';
}
?>
