<?php
// config/database.php
// Database configuration with environment variable support
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;

    public function __construct() {
        // Use environment variables for Docker deployment
        $this->host = getenv('DB_HOST') ?: 'mysql';
        $this->db_name = getenv('DB_NAME') ?: 'omaki_db';
        $this->username = getenv('DB_USER') ?: 'omaki_user';
        $this->password = getenv('DB_PASSWORD') ?: 'omaki_password';
    }

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->db_name);
            
            if ($this->conn->connect_error) {
                die('Could not establish database connection: ' . $this->conn->connect_error);
            }
            
            // Set charset to utf8mb4 to support emojis
            $this->conn->set_charset("utf8mb4");
            
        } catch(Exception $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
        
        return $this->conn;
    }
    
    public function closeConnection() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}

// Simple connection function (legacy style)
function getDbConnection() {
    $host = getenv('DB_HOST') ?: 'mysql';
    $username = getenv('DB_USER') ?: 'omaki_user';
    $password = getenv('DB_PASSWORD') ?: 'omaki_password';
    $db_name = getenv('DB_NAME') ?: 'omaki_db';
    
    $db = new mysqli($host, $username, $password, $db_name);
    if ($db->connect_error) {
        die('Could not establish database connection, please review your settings');
    }
    $db->set_charset("utf8mb4");
    return $db;
}
?>