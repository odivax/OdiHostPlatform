<?php
function sanitizeUsername($username) {
    // Remove non-alphanumeric characters except dots and hyphens
    $username = preg_replace('/[^a-zA-Z0-9.-]/', '', $username);
    // Convert to lowercase
    $username = strtolower($username);
    // Limit length
    return substr($username, 0, 20);
}

function isUsernameAvailable($username) {
    $userDir = USERS_DIR . '/' . $username;
    return !is_dir($userDir);
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
    
    if (!mkdir($userDir, 0755, true)) {
        return false;
    }
    
    $metadata = [
        'username' => $username,
        'google_id' => $googleId,
        'name' => $name,
        'email' => $email,
        'picture' => $picture,
        'created_at' => date('Y-m-d H:i:s'),
        'projects' => []
    ];
    
    $metadataFile = $userDir . '/metadata.json';
    return file_put_contents($metadataFile, json_encode($metadata, JSON_PRETTY_PRINT));
}

function getUserByGoogleId($googleId) {
    $usersDir = USERS_DIR;
    if (!is_dir($usersDir)) return false;
    
    $users = scandir($usersDir);
    foreach ($users as $user) {
        if ($user === '.' || $user === '..') continue;
        
        $metadataFile = $usersDir . '/' . $user . '/metadata.json';
        if (file_exists($metadataFile)) {
            $metadata = json_decode(file_get_contents($metadataFile), true);
            if ($metadata['google_id'] === $googleId) {
                return $metadata;
            }
        }
    }
    return false;
}

function getUserMetadata($username) {
    $metadataFile = USERS_DIR . '/' . $username . '/metadata.json';
    if (!file_exists($metadataFile)) return false;
    
    return json_decode(file_get_contents($metadataFile), true);
}

function updateUserMetadata($username, $metadata) {
    $metadataFile = USERS_DIR . '/' . $username . '/metadata.json';
    return file_put_contents($metadataFile, json_encode($metadata, JSON_PRETTY_PRINT));
}

function generateProjectSlug($name) {
    $slug = strtolower(trim($name));
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    return trim($slug, '-');
}

function createProject($username, $projectName) {
    $metadata = getUserMetadata($username);
    if (!$metadata) return false;
    
    $slug = generateProjectSlug($projectName);
    
    // Ensure unique slug
    $originalSlug = $slug;
    $counter = 1;
    while (projectExists($username, $slug)) {
        $slug = $originalSlug . '-' . $counter;
        $counter++;
    }
    
    $projectDir = USERS_DIR . '/' . $username . '/' . $slug;
    if (!mkdir($projectDir, 0755, true)) {
        return false;
    }
    
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
    
    file_put_contents($projectDir . '/index.html', $defaultHtml);
    file_put_contents($projectDir . '/style.css', $defaultCss);
    file_put_contents($projectDir . '/script.js', $defaultJs);
    
    // Update metadata
    $project = [
        'name' => $projectName,
        'slug' => $slug,
        'created_at' => date('Y-m-d H:i:s'),
        'custom_domain' => ''
    ];
    
    $metadata['projects'][] = $project;
    updateUserMetadata($username, $metadata);
    
    return $slug;
}

function projectExists($username, $slug) {
    $metadata = getUserMetadata($username);
    if (!$metadata) return false;
    
    foreach ($metadata['projects'] as $project) {
        if ($project['slug'] === $slug) {
            return true;
        }
    }
    return false;
}

function deleteProject($username, $slug) {
    $metadata = getUserMetadata($username);
    if (!$metadata) return false;
    
    // Remove from metadata
    $metadata['projects'] = array_filter($metadata['projects'], function($project) use ($slug) {
        return $project['slug'] !== $slug;
    });
    
    // Remove directory
    $projectDir = USERS_DIR . '/' . $username . '/' . $slug;
    if (is_dir($projectDir)) {
        removeDirectory($projectDir);
    }
    
    return updateUserMetadata($username, $metadata);
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
