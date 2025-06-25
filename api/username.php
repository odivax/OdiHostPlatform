<?php
header('Content-Type: application/json');
require_once '../config.php';
require_once '../functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$username = sanitizeUsername($input['username'] ?? '');

if (empty($username)) {
    echo json_encode(['available' => false, 'error' => 'Username is required']);
    exit;
}

if (strlen($username) < 3) {
    echo json_encode(['available' => false, 'error' => 'Username must be at least 3 characters']);
    exit;
}

$available = isUsernameAvailable($username);

if ($available) {
    echo json_encode(['available' => true]);
} else {
    $suggestions = suggestUsername($username);
    echo json_encode([
        'available' => false,
        'suggestions' => $suggestions
    ]);
}
?>
