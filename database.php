<?php
class Database {
    private $pdo;
    
    public function __construct() {
        $host = getenv('PGHOST');
        $port = getenv('PGPORT');
        $dbname = getenv('PGDATABASE');
        $username = getenv('PGUSER');
        $password = getenv('PGPASSWORD');
        
        $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
        
        try {
            $this->pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    public function createUser($googleId, $name, $email, $picture, $username) {
        $stmt = $this->pdo->prepare("
            INSERT INTO users (google_id, name, email, picture, username) 
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$googleId, $name, $email, $picture, $username]);
    }
    
    public function getUserByGoogleId($googleId) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE google_id = ?");
        $stmt->execute([$googleId]);
        return $stmt->fetch();
    }
    
    public function getUserByUsername($username) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }
    
    public function isUsernameAvailable($username) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetchColumn() == 0;
    }
    
    public function createProject($userId, $name, $slug) {
        $stmt = $this->pdo->prepare("
            INSERT INTO projects (user_id, name, slug) 
            VALUES (?, ?, ?)
        ");
        return $stmt->execute([$userId, $name, $slug]);
    }
    
    public function getUserProjects($userId) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM projects 
            WHERE user_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    public function getProject($userId, $slug) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM projects 
            WHERE user_id = ? AND slug = ?
        ");
        $stmt->execute([$userId, $slug]);
        return $stmt->fetch();
    }
    
    public function deleteProject($userId, $slug) {
        $stmt = $this->pdo->prepare("
            DELETE FROM projects 
            WHERE user_id = ? AND slug = ?
        ");
        return $stmt->execute([$userId, $slug]);
    }
    
    public function updateProjectDomain($userId, $slug, $customDomain) {
        $stmt = $this->pdo->prepare("
            UPDATE projects 
            SET custom_domain = ? 
            WHERE user_id = ? AND slug = ?
        ");
        return $stmt->execute([$customDomain, $userId, $slug]);
    }
    
    public function saveFile($projectId, $filename, $content, $fileType = 'text') {
        $stmt = $this->pdo->prepare("
            INSERT INTO project_files (project_id, filename, content, file_type, updated_at) 
            VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
            ON CONFLICT (project_id, filename) 
            DO UPDATE SET content = EXCLUDED.content, updated_at = CURRENT_TIMESTAMP
        ");
        return $stmt->execute([$projectId, $filename, $content, $fileType]);
    }
    
    public function getFile($projectId, $filename) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM project_files 
            WHERE project_id = ? AND filename = ?
        ");
        $stmt->execute([$projectId, $filename]);
        return $stmt->fetch();
    }
    
    public function getProjectFiles($projectId) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM project_files 
            WHERE project_id = ? 
            ORDER BY filename
        ");
        $stmt->execute([$projectId]);
        return $stmt->fetchAll();
    }
    
    public function projectExists($userId, $slug) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM projects 
            WHERE user_id = ? AND slug = ?
        ");
        $stmt->execute([$userId, $slug]);
        return $stmt->fetchColumn() > 0;
    }
    
    public function getProjectByDomain($domain) {
        $stmt = $this->pdo->prepare("
            SELECT p.*, u.username 
            FROM projects p 
            JOIN users u ON p.user_id = u.id 
            WHERE p.custom_domain = ?
        ");
        $stmt->execute([$domain]);
        return $stmt->fetch();
    }
}
?>