<?php
require_once __DIR__ . '/../core/Model.php';

class Budget extends Model {
    protected $table = 'budgets';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->user_id = $_SESSION['user_id'] ?? null;
    }

    public function getAllWithProgress($userId = null) {
        $sql = "SELECT b.*, c.name as category_name,
                COALESCE(SUM(t.amount), 0) as spent,
                (COALESCE(SUM(t.amount), 0) / b.amount * 100) as percentage
                FROM {$this->table} b
                LEFT JOIN categories c ON b.category_id = c.id
                LEFT JOIN transactions t ON t.category_id = b.category_id 
                    AND t.type = 'expense' 
                    AND t.transaction_date BETWEEN b.start_date AND b.end_date
                    AND t.user_id = ?
                WHERE b.user_id = ?
                GROUP BY b.id, b.amount, b.start_date, b.end_date, c.name
                ORDER BY b.start_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId ?? $_SESSION['user_id'], $userId ?? $_SESSION['user_id']]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAll($userId = null) {
        $sql = "SELECT b.*, c.name as category_name 
                FROM {$this->table} b
                LEFT JOIN categories c ON b.category_id = c.id
                WHERE b.user_id = ?
                ORDER BY b.start_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId ?? $_SESSION['user_id']]);
        return $stmt->fetchAll();
    }

    public function create($data, $userId = null) {
        $sql = "INSERT INTO {$this->table} (user_id, category_id, amount, start_date, end_date) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $userId ?? $_SESSION['user_id'],
            $data['category_id'],
            $data['amount'],
            $data['start_date'],
            $data['end_date']
        ]);
    }

    public function update($id, $data, $userId = null) {
        $sql = "UPDATE {$this->table} 
                SET category_id = ?, amount = ?, start_date = ?, end_date = ? 
                WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['category_id'],
            $data['amount'],
            $data['start_date'],
            $data['end_date'],
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
        $sql = "SELECT b.*, c.name as category_name 
                FROM {$this->table} b
                LEFT JOIN categories c ON b.category_id = c.id
                WHERE b.id = ? AND b.user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id, $userId ?? $_SESSION['user_id']]);
        return $stmt->fetch();
    }

    public function allByUser() {
        $query = "SELECT b.*, 
                        c.name as category_name, 
                        w.name as wallet_name,
                        COALESCE(SUM(t.amount), 0) as spent_amount
                 FROM budgets b
                 LEFT JOIN categories c ON b.category_id = c.id
                 LEFT JOIN wallets w ON b.wallet_id = w.id
                 LEFT JOIN transactions t ON 
                    (b.category_id IS NULL OR t.category_id = b.category_id) AND
                    (b.wallet_id IS NULL OR t.wallet_id = b.wallet_id) AND
                    t.transaction_date BETWEEN b.start_date AND COALESCE(b.end_date, CURDATE())
                 WHERE b.user_id = :user_id
                 GROUP BY b.id
                 ORDER BY b.start_date DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} 