<?php
session_start();
require_once '../config.php';
require_once '../functions.php';

if (!isset($_SESSION['temp_user'])) {
    header('Location: /auth/login.php');
    exit;
}

$tempUser = $_SESSION['temp_user'];
$error = '';
$suggestions = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeUsername($_POST['username'] ?? '');
    
    if (empty($username)) {
        $error = 'Username is required';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters';
    } elseif (!isUsernameAvailable($username)) {
        $error = 'Username is not available';
        $suggestions = suggestUsername($username);
    } else {
        // Create user
        if (createUser($tempUser['google_id'], $tempUser['name'], $tempUser['email'], $tempUser['picture'], $username)) {
            $_SESSION['user_id'] = $tempUser['google_id'];
            $_SESSION['username'] = $username;
            $_SESSION['name'] = $tempUser['name'];
            $_SESSION['picture'] = $tempUser['picture'];
            
            unset($_SESSION['temp_user']);
            header('Location: /dashboard.php');
            exit;
        } else {
            $error = 'Failed to create user account';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose Username - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="user-info">
                <img src="<?= htmlspecialchars($tempUser['picture']) ?>" alt="Profile" class="profile-pic">
                <h2>Welcome, <?= htmlspecialchars($tempUser['name']) ?>!</h2>
                <p>Choose a unique username for your hosting account</p>
            </div>
            
            <form method="POST" class="username-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" 
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           placeholder="your-username" required>
                    <small>This will be your subdomain: username.odivax.com</small>
                </div>
                
                <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <?php if (!empty($suggestions)): ?>
                <div class="suggestions">
                    <p>Suggestions:</p>
                    <?php foreach ($suggestions as $suggestion): ?>
                    <button type="button" class="suggestion-btn" 
                            onclick="document.getElementById('username').value='<?= htmlspecialchars($suggestion) ?>'">
                        <?= htmlspecialchars($suggestion) ?>
                    </button>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <button type="submit" class="btn btn-primary">Create Account</button>
            </form>
        </div>
    </div>
</body>
</html>
