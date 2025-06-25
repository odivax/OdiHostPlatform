<?php
// Installation script for OdiHost
// Run this once after uploading files to shared hosting

// Check if already installed
if (file_exists('installed.lock')) {
    die('OdiHost is already installed. Delete installed.lock file to reinstall.');
}

require_once 'config.php';

// Check if database constants are defined
if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER') || !defined('DB_PASS')) {
    die('Please configure database settings in config.php first.');
}

try {
    // Connect to MySQL
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", 
                   DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Create users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            google_id VARCHAR(100) UNIQUE NOT NULL,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            picture TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Create projects table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS projects (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(100) NOT NULL,
            custom_domain VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_slug (user_id, slug),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Create project_files table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS project_files (
            id INT AUTO_INCREMENT PRIMARY KEY,
            project_id INT NOT NULL,
            filename VARCHAR(255) NOT NULL,
            content LONGTEXT,
            file_type VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_project_file (project_id, filename),
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Create installation lock file
    file_put_contents('installed.lock', date('Y-m-d H:i:s'));

    echo "✅ OdiHost installation completed successfully!\n";
    echo "📋 Next steps:\n";
    echo "1. Configure Google OAuth credentials in config.php\n";
    echo "2. Set up wildcard DNS for subdomain hosting (optional)\n";
    echo "3. Visit your domain to test the installation\n";
    echo "4. Delete this install.php file for security\n";

} catch (PDOException $e) {
    die("❌ Database error: " . $e->getMessage() . "\nPlease check your database configuration in config.php");
} catch (Exception $e) {
    die("❌ Installation error: " . $e->getMessage());
}
?>