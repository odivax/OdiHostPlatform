<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}

$username = $_SESSION['username'];
$metadata = getUserMetadata($username);

if (!$metadata) {
    header('Location: /auth/logout.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <h1><?= APP_NAME ?></h1>
            </div>
            <div class="nav-user">
                <img src="<?= htmlspecialchars($_SESSION['picture']) ?>" alt="Profile" class="nav-avatar">
                <span><?= htmlspecialchars($_SESSION['name']) ?></span>
                <a href="/auth/logout.php" class="btn btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-header">
            <h2>Your Projects</h2>
            <button class="btn btn-primary" onclick="createProject()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                New Project
            </button>
        </div>

        <div class="projects-grid" id="projectsGrid">
            <?php if (empty($metadata['projects'])): ?>
            <div class="empty-state">
                <h3>No projects yet</h3>
                <p>Create your first project to get started</p>
                <button class="btn btn-primary" onclick="createProject()">Create Project</button>
            </div>
            <?php else: ?>
            <?php foreach ($metadata['projects'] as $project): ?>
            <div class="project-card" data-slug="<?= htmlspecialchars($project['slug']) ?>">
                <div class="project-header">
                    <h3><?= htmlspecialchars($project['name']) ?></h3>
                    <div class="project-actions">
                        <button class="btn btn-sm" onclick="editProject('<?= htmlspecialchars($project['slug']) ?>')">Edit</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteProject('<?= htmlspecialchars($project['slug']) ?>')">Delete</button>
                    </div>
                </div>
                <div class="project-info">
                    <p><strong>URL:</strong> 
                        <a href="https://<?= htmlspecialchars($username) ?>.odivax.com/<?= htmlspecialchars($project['slug']) ?>" target="_blank">
                            <?= htmlspecialchars($username) ?>.odivax.com/<?= htmlspecialchars($project['slug']) ?>
                        </a>
                    </p>
                    <?php if (!empty($project['custom_domain'])): ?>
                    <p><strong>Custom Domain:</strong> 
                        <a href="https://<?= htmlspecialchars($project['custom_domain']) ?>" target="_blank">
                            <?= htmlspecialchars($project['custom_domain']) ?>
                        </a>
                    </p>
                    <?php endif; ?>
                    <p><strong>Created:</strong> <?= date('M j, Y', strtotime($project['created_at'])) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Create Project Modal -->
    <div id="createProjectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Create New Project</h3>
                <button class="close" onclick="closeModal('createProjectModal')">&times;</button>
            </div>
            <form id="createProjectForm">
                <div class="form-group">
                    <label for="projectName">Project Name</label>
                    <input type="text" id="projectName" name="projectName" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" onclick="closeModal('createProjectModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Project</button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>
