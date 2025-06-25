<?php
session_start();
require_once '../config.php';
require_once '../functions.php';

if (!isset($_GET['code'])) {
    header('Location: /auth/login.php');
    exit;
}

$code = $_GET['code'];

// Exchange code for access token
$tokenUrl = 'https://oauth2.googleapis.com/token';
$tokenData = [
    'client_id' => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'code' => $code,
    'grant_type' => 'authorization_code',
    'redirect_uri' => GOOGLE_REDIRECT_URI
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $tokenUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    header('Location: /auth/login.php?error=auth_failed');
    exit;
}

$tokenResponse = json_decode($response, true);
$accessToken = $tokenResponse['access_token'];

// Get user info from Google
$userInfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo?access_token=' . $accessToken;
$userInfo = file_get_contents($userInfoUrl);
$user = json_decode($userInfo, true);

if (!$user || !isset($user['id'])) {
    header('Location: /auth/login.php?error=user_info_failed');
    exit;
}

// Check if user exists
$existingUser = getUserByGoogleId($user['id']);

if ($existingUser) {
    // User exists, log them in
    $_SESSION['user_id'] = $existingUser['google_id'];
    $_SESSION['username'] = $existingUser['username'];
    $_SESSION['name'] = $existingUser['name'];
    $_SESSION['picture'] = $existingUser['picture'];
    
    header('Location: /dashboard.php');
} else {
    // New user, store their info and redirect to username selection
    $_SESSION['temp_user'] = [
        'google_id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'picture' => $user['picture']
    ];
    
    header('Location: /auth/username.php');
}
exit;
?>
