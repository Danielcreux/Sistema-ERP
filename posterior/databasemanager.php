<?php
class DatabaseManager {
    private static $instance = null;
    private $db;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        if ($this->db === null) {
            $database = new Database();
            $this->db = $database->getConnection();
        }
        return $this->db;
    }
}
?>