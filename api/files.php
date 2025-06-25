<?php
session_start();
header('Content-Type: application/json');
require_once '../config.php';
require_once '../functions.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$username = $_SESSION['username'];
$projectSlug = $_GET['project'] ?? '';

if (empty($projectSlug)) {
    http_response_code(400);
    echo json_encode(['error' => 'Project is required']);
    exit;
}

$user = getDB()->getUserByUsername($username);
if (!$user) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
    exit;
}

$project = getDB()->getProject($user['id'], $projectSlug);
if (!$project) {
    http_response_code(404);
    echo json_encode(['error' => 'Project not found']);
    exit;
}

$projectDir = USERS_DIR . '/' . $username . '/' . $projectSlug;

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $file = $_GET['file'] ?? '';
        
        if (empty($file)) {
            // List files
            $files = [];
            $items = scandir($projectDir);
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') continue;
                
                $filePath = $projectDir . '/' . $item;
                if (is_file($filePath)) {
                    $files[] = [
                        'name' => $item,
                        'size' => filesize($filePath),
                        'modified' => filemtime($filePath)
                    ];
                }
            }
            echo json_encode(['files' => $files]);
        } else {
            // Get file content from database first, fallback to filesystem
            $fileData = getDB()->getFile($project['id'], $file);
            if ($fileData) {
                echo json_encode(['content' => $fileData['content']]);
            } else {
                // Fallback to filesystem
                $filePath = $projectDir . '/' . $file;
                if (!file_exists($filePath) || !is_file($filePath)) {
                    http_response_code(404);
                    echo json_encode(['error' => 'File not found']);
                    exit;
                }
                
                $content = file_get_contents($filePath);
                echo json_encode(['content' => $content]);
            }
        }
        break;
        
    case 'POST':
        if (isset($_FILES['files'])) {
            // Handle file upload
            $uploadedFiles = [];
            $errors = [];
            
            foreach ($_FILES['files']['tmp_name'] as $key => $tmpName) {
                $filename = $_FILES['files']['name'][$key];
                $error = $_FILES['files']['error'][$key];
                
                if ($error !== UPLOAD_ERR_OK) {
                    $errors[] = "Failed to upload $filename";
                    continue;
                }
                
                if (!isValidImageFile($filename)) {
                    $errors[] = "$filename is not a valid image file";
                    continue;
                }
                
                $targetPath = $projectDir . '/' . $filename;
                if (move_uploaded_file($tmpName, $targetPath)) {
                    $uploadedFiles[] = $filename;
                } else {
                    $errors[] = "Failed to save $filename";
                }
            }
            
            echo json_encode([
                'uploaded' => $uploadedFiles,
                'errors' => $errors
            ]);
        } else {
            // Save file content
            $input = json_decode(file_get_contents('php://input'), true);
            $file = $input['file'] ?? '';
            $content = $input['content'] ?? '';
            
            if (empty($file)) {
                http_response_code(400);
                echo json_encode(['error' => 'File name is required']);
                exit;
            }
            
            // Only allow certain file types
            $allowedFiles = ['index.html', 'style.css', 'script.js'];
            if (!in_array($file, $allowedFiles)) {
                http_response_code(400);
                echo json_encode(['error' => 'File type not allowed']);
                exit;
            }
            
            // Save to both database and filesystem
            $filePath = $projectDir . '/' . $file;
            $fileType = pathinfo($file, PATHINFO_EXTENSION);
            
            if (file_put_contents($filePath, $content) !== false && 
                getDB()->saveFile($project['id'], $file, $content, $fileType)) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to save file']);
            }
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
?>
