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
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        // Create project
        $input = json_decode(file_get_contents('php://input'), true);
        $projectName = trim($input['name'] ?? '');
        
        if (empty($projectName)) {
            http_response_code(400);
            echo json_encode(['error' => 'Project name is required']);
            exit;
        }
        
        $slug = createProject($username, $projectName);
        if ($slug) {
            echo json_encode(['success' => true, 'slug' => $slug]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create project']);
        }
        break;
        
    case 'DELETE':
        // Delete project
        $slug = $_GET['slug'] ?? '';
        
        if (empty($slug)) {
            http_response_code(400);
            echo json_encode(['error' => 'Project slug is required']);
            exit;
        }
        
        if (deleteProject($username, $slug)) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete project']);
        }
        break;
        
    case 'PUT':
        // Update project (custom domain)
        $input = json_decode(file_get_contents('php://input'), true);
        $slug = $input['slug'] ?? '';
        $customDomain = trim($input['custom_domain'] ?? '');
        
        if (empty($slug)) {
            http_response_code(400);
            echo json_encode(['error' => 'Project slug is required']);
            exit;
        }
        
        $user = getDB()->getUserByUsername($username);
        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            exit;
        }
        
        if (getDB()->updateProjectDomain($user['id'], $slug, $customDomain)) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update project']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
?>
