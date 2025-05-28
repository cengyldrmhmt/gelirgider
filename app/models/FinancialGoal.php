<?php
require_once __DIR__ . '/../core/Model.php';

class FinancialGoal extends Model {
    protected $table = 'financial_goals';

    public function create($data, $userId = null) {
        $sql = "INSERT INTO {$this->table} (user_id, name, target_amount, current_amount, target_date, category_id) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $userId ?? $_SESSION['user_id'],
            $data['name'],
            $data['target_amount'],
            $data['current_amount'],
            $data['target_date'],
            $data['category_id']
        ]);
    }

    public function update($id, $data, $userId = null) {
        $sql = "UPDATE {$this->table} 
                SET name = ?, target_amount = ?, current_amount = ?, target_date = ?, category_id = ? 
                WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['name'],
            $data['target_amount'],
            $data['current_amount'],
            $data['target_date'],
            $data['category_id'],
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
        $sql = "SELECT g.*, c.name as category_name 
                FROM {$this->table} g
                LEFT JOIN categories c ON g.category_id = c.id
                WHERE g.id = ? AND g.user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id, $userId ?? $_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAll($userId = null) {
        $sql = "SELECT g.*, c.name as category_name 
                FROM {$this->table} g
                LEFT JOIN categories c ON g.category_id = c.id
                WHERE g.user_id = ?
                ORDER BY g.target_date ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId ?? $_SESSION['user_id']]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllWithProgress($userId = null) {
        $sql = "SELECT g.*, c.name as category_name,
                (g.current_amount / g.target_amount * 100) as percentage
                FROM {$this->table} g
                LEFT JOIN categories c ON g.category_id = c.id
                WHERE g.user_id = ?
                ORDER BY g.target_date ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId ?? $_SESSION['user_id']]);
        
        $goals = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Ek hesaplamalar
        foreach ($goals as &$goal) {
            $goal['percentage'] = $goal['percentage'] ?? 0;
            $goal['end_date'] = $goal['target_date']; // Dashboard için uyumluluk
            $goal['name'] = $goal['name'] ?? 'Hedef';
        }
        
        return $goals;
    }

    public function updateProgress($id, $amount, $userId = null) {
        $sql = "UPDATE {$this->table} 
                SET current_amount = current_amount + ? 
                WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$amount, $id, $userId ?? $_SESSION['user_id']]);
    }
} 