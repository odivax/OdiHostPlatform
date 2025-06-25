<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['project'])) {
    header('Location: /dashboard.php');
    exit;
}

$username = $_SESSION['username'];
$projectSlug = $_GET['project'];
$metadata = getUserMetadata($username);

// Verify project exists
$project = null;
foreach ($metadata['projects'] as $p) {
    if ($p['slug'] === $projectSlug) {
        $project = $p;
        break;
    }
}

if (!$project) {
    header('Location: /dashboard.php');
    exit;
}

$projectDir = USERS_DIR . '/' . $username . '/' . $projectSlug;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit <?= htmlspecialchars($project['name']) ?> - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="editor-page">
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <a href="/dashboard.php" class="back-link">← Dashboard</a>
                <h1><?= htmlspecialchars($project['name']) ?></h1>
            </div>
            <div class="nav-actions">
                <button class="btn btn-sm" onclick="saveProject()">Save</button>
                <a href="https://<?= htmlspecialchars($username) ?>.odivax.com/<?= htmlspecialchars($projectSlug) ?>" 
                   target="_blank" class="btn btn-sm">Preview</a>
            </div>
        </div>
    </nav>

    <div class="editor-container">
        <div class="editor-sidebar">
            <div class="file-tabs">
                <button class="tab-btn active" data-file="index.html">index.html</button>
                <button class="tab-btn" data-file="style.css">style.css</button>
                <button class="tab-btn" data-file="script.js">script.js</button>
            </div>
            
            <div class="file-manager">
                <h4>Files</h4>
                <div class="upload-area">
                    <input type="file" id="fileUpload" accept=".png,.jpg,.jpeg,.svg" multiple style="display: none;">
                    <button class="btn btn-sm" onclick="document.getElementById('fileUpload').click()">
                        Upload Images
                    </button>
                </div>
                <div id="fileList" class="file-list"></div>
            </div>
            
            <div class="project-settings">
                <h4>Settings</h4>
                <div class="form-group">
                    <label for="customDomain">Custom Domain</label>
                    <input type="text" id="customDomain" placeholder="mysite.com" 
                           value="<?= htmlspecialchars($project['custom_domain'] ?? '') ?>">
                    <button class="btn btn-sm" onclick="updateCustomDomain()">Update</button>
                </div>
                <?php if (!empty($project['custom_domain'])): ?>
                <div class="dns-instructions">
                    <h5>DNS Setup</h5>
                    <p>Add this A record to your domain:</p>
                    <code>@ IN A YOUR_SERVER_IP</code>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="editor-main">
            <div id="codeEditor"></div>
        </div>

        <div class="preview-panel">
            <div class="preview-header">
                <h4>Live Preview</h4>
                <button class="btn btn-sm" onclick="refreshPreview()">Refresh</button>
            </div>
            <iframe id="previewFrame" src="https://<?= htmlspecialchars($username) ?>.odivax.com/<?= htmlspecialchars($projectSlug) ?>"></iframe>
        </div>
    </div>

    <!-- Monaco Editor -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.34.1/min/vs/loader.min.js"></script>
    <script>
        window.PROJECT_SLUG = '<?= htmlspecialchars($projectSlug) ?>';
        window.PREVIEW_URL = 'https://<?= htmlspecialchars($username) ?>.odivax.com/<?= htmlspecialchars($projectSlug) ?>';
    </script>
    <script src="assets/js/editor.js"></script>
</body>
</html>
