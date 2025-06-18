<?php
// config/database.php
class Database {
    private $host = 'localhost';
    private $db_name = 'omaki_db';
    private $username = 'root';
    private $password = '';
    private $conn;

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
    $db = new mysqli('localhost', 'root', '', 'omaki_db');
    if ($db->connect_error) {
        die('Could not establish database connection, please review your settings');
    }
    $db->set_charset("utf8mb4");
    return $db;
}
?>