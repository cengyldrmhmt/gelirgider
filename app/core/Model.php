<?php
require_once __DIR__ . '/Database.php';

class Model {
    protected $db;
    protected $table;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create($data, $userId = null) {
        $fields = array_keys($data);
        $values = array_values($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $query = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") 
                 VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $this->db->prepare($query);
        return $stmt->execute($values);
    }

    public function update($id, $data, $userId = null) {
        $fields = array_keys($data);
        $values = array_values($data);
        $set = array_map(function($field) {
            return "$field = ?";
        }, $fields);
        
        $query = "UPDATE {$this->table} 
                 SET " . implode(', ', $set) . "
                 WHERE id = ? AND user_id = ?";
        
        $values[] = $id;
        $values[] = $userId ?? $_SESSION['user_id'];
        
        $stmt = $this->db->prepare($query);
        return $stmt->execute($values);
    }

    public function delete($id, $userId = null) {
        $query = "DELETE FROM {$this->table} WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$id, $userId ?? $_SESSION['user_id']]);
    }

    public function get($id, $userId = null) {
        $query = "SELECT * FROM {$this->table} WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id, $userId ?? $_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAll($userId = null) {
        $query = "SELECT * FROM {$this->table} WHERE user_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId ?? $_SESSION['user_id']]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} 