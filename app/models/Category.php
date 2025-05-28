<?php
require_once __DIR__ . '/../core/Model.php';

class Category extends Model {
    protected $table = 'categories';
    
    public function getExpensesByCategory($userId) {
        $sql = "SELECT c.name, COALESCE(SUM(t.amount), 0) as amount 
                FROM {$this->table} c 
                LEFT JOIN transactions t ON c.id = t.category_id AND t.type = 'expense' AND t.user_id = ? 
                WHERE c.user_id = ? 
                GROUP BY c.id, c.name 
                HAVING amount > 0 
                ORDER BY amount DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getAll($userId = null) {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId ?? $_SESSION['user_id']]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function create($data, $userId = null) {
        $sql = "INSERT INTO {$this->table} (user_id, name, type, color, icon) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['user_id'] ?? ($userId ?? $_SESSION['user_id']),
            $data['name'],
            $data['type'],
            $data['color'],
            $data['icon'] ?? 'ellipsis-h'
        ]);
    }
    
    public function update($id, $data, $userId = null) {
        $sql = "UPDATE {$this->table} SET name = ?, type = ?, color = ?, icon = ? WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['name'],
            $data['type'],
            $data['color'],
            $data['icon'] ?? 'ellipsis-h',
            $id,
            $userId ?? $_SESSION['user_id']
        ]);
    }
    
    public function delete($id, $userId = null) {
        $sql = "DELETE FROM {$this->table} WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id, $userId ?? $_SESSION['user_id']]);
    }
    
    public function get($id, $userId = null) {
        $sql = "SELECT * FROM {$this->table} WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id, $userId ?? $_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getByType($type, $userId = null) {
        $sql = "SELECT * FROM {$this->table} WHERE type = ? AND user_id = ? ORDER BY name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$type, $userId ?? $_SESSION['user_id']]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} 