<?php
require_once 'database.php';

function getDB() {
    static $db = null;
    if ($db === null) {
        $db = new Database();
    }
    return $db;
}

function sanitizeUsername($username) {
    // Remove non-alphanumeric characters except dots and hyphens
    $username = preg_replace('/[^a-zA-Z0-9.-]/', '', $username);
    // Convert to lowercase
    $username = strtolower($username);
    // Limit length
    return substr($username, 0, 20);
}

function isUsernameAvailable($username) {
    return getDB()->isUsernameAvailable($username);
}

function suggestUsername($baseUsername) {
    $suggestions = [];
    
    // Try with numbers
    for ($i = 1; $i <= 99; $i++) {
        $suggestion = $baseUsername . $i;
        if (isUsernameAvailable($suggestion)) {
            $suggestions[] = $suggestion;
            if (count($suggestions) >= 3) break;
        }
    }
    
    // Try with .dev suffix
    $devSuggestion = $baseUsername . '.dev';
    if (isUsernameAvailable($devSuggestion)) {
        $suggestions[] = $devSuggestion;
    }
    
    return $suggestions;
}

function createUser($googleId, $name, $email, $picture, $username) {
    $userDir = USERS_DIR . '/' . $username;
    
    // Still create directory for file storage
    if (!is_dir($userDir) && !mkdir($userDir, 0755, true)) {
        return false;
    }
    
    return getDB()->createUser($googleId, $name, $email, $picture, $username);
}

function getUserByGoogleId($googleId) {
    return getDB()->getUserByGoogleId($googleId);
}

function getUserMetadata($username) {
    $user = getDB()->getUserByUsername($username);
    if (!$user) return false;
    
    $projects = getDB()->getUserProjects($user['id']);
    $user['projects'] = $projects;
    
    return $user;
}

function updateUserMetadata($username, $metadata) {
    // This function is now handled by individual database operations
    return true;
}

function generateProjectSlug($name) {
    $slug = strtolower(trim($name));
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    return trim($slug, '-');
}

function createProject($username, $projectName) {
    $user = getDB()->getUserByUsername($username);
    if (!$user) return false;
    
    $slug = generateProjectSlug($projectName);
    
    // Ensure unique slug
    $originalSlug = $slug;
    $counter = 1;
    while (getDB()->projectExists($user['id'], $slug)) {
        $slug = $originalSlug . '-' . $counter;
        $counter++;
    }
    
    $projectDir = USERS_DIR . '/' . $username . '/' . $slug;
    if (!mkdir($projectDir, 0755, true)) {
        return false;
    }
    
    // Create project in database
    if (!getDB()->createProject($user['id'], $projectName, $slug)) {
        return false;
    }
    
    // Get project ID
    $project = getDB()->getProject($user['id'], $slug);
    if (!$project) return false;
    
    // Create default files
    $defaultHtml = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($projectName) . '</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Welcome to ' . htmlspecialchars($projectName) . '</h1>
    <p>Start editing to create your website!</p>
    <script src="script.js"></script>
</body>
</html>';
    
    $defaultCss = 'body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    line-height: 1.6;
}

h1 {
    color: #333;
    text-align: center;
}

p {
    color: #666;
    text-align: center;
}';
    
    $defaultJs = '// Add your JavaScript here
console.log("Welcome to ' . $projectName . '!");';
    
    // Save files to both filesystem and database
    file_put_contents($projectDir . '/index.html', $defaultHtml);
    file_put_contents($projectDir . '/style.css', $defaultCss);
    file_put_contents($projectDir . '/script.js', $defaultJs);
    
    getDB()->saveFile($project['id'], 'index.html', $defaultHtml, 'html');
    getDB()->saveFile($project['id'], 'style.css', $defaultCss, 'css');
    getDB()->saveFile($project['id'], 'script.js', $defaultJs, 'javascript');
    
    return $slug;
}

function projectExists($username, $slug) {
    $user = getDB()->getUserByUsername($username);
    if (!$user) return false;
    
    return getDB()->projectExists($user['id'], $slug);
}

function deleteProject($username, $slug) {
    $user = getDB()->getUserByUsername($username);
    if (!$user) return false;
    
    // Remove directory
    $projectDir = USERS_DIR . '/' . $username . '/' . $slug;
    if (is_dir($projectDir)) {
        removeDirectory($projectDir);
    }
    
    return getDB()->deleteProject($user['id'], $slug);
}

function removeDirectory($dir) {
    if (!is_dir($dir)) return false;
    
    $files = array_diff(scandir($dir), array('.', '..'));
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            removeDirectory($path);
        } else {
            unlink($path);
        }
    }
    return rmdir($dir);
}

function isValidImageFile($filename) {
    $allowedExtensions = ['png', 'jpg', 'jpeg', 'svg'];
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($extension, $allowedExtensions);
}
?>
