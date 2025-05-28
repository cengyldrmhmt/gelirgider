<?php
require_once __DIR__ . '/../core/Model.php';

class ScheduledPayment extends Model {
    protected $table = 'scheduled_payments';

    public function create($data, $userId = null) {
        $sql = "INSERT INTO {$this->table} (user_id, title, amount, frequency, start_date, end_date, category_id, wallet_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $userId ?? $_SESSION['user_id'],
            $data['title'],
            $data['amount'],
            $data['frequency'],
            $data['start_date'],
            $data['end_date'],
            $data['category_id'],
            $data['wallet_id']
        ]);
    }

    public function update($id, $data, $userId = null) {
        $sql = "UPDATE {$this->table} 
                SET title = ?, amount = ?, frequency = ?, start_date = ?, end_date = ?, category_id = ?, wallet_id = ? 
                WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['title'],
            $data['amount'],
            $data['frequency'],
            $data['start_date'],
            $data['end_date'],
            $data['category_id'],
            $data['wallet_id'],
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
        $sql = "SELECT p.*, c.name as category_name, w.name as wallet_name 
                FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN wallets w ON p.wallet_id = w.id
                WHERE p.id = ? AND p.user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id, $userId ?? $_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAll($userId = null) {
        $sql = "SELECT p.*, c.name as category_name, w.name as wallet_name 
                FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN wallets w ON p.wallet_id = w.id
                WHERE p.user_id = ?
                ORDER BY p.start_date ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId ?? $_SESSION['user_id']]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function allByUser($userId = null) {
        $sql = "SELECT p.*, c.name as category_name, w.name as wallet_name 
                FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN wallets w ON p.wallet_id = w.id
                WHERE p.user_id = ?
                ORDER BY p.start_date ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId ?? $_SESSION['user_id']]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUpcoming($userId = null) {
        $sql = "SELECT p.*, c.name as category_name, w.name as wallet_name 
                FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN wallets w ON p.wallet_id = w.id
                WHERE p.user_id = ? AND p.start_date >= CURDATE()
                ORDER BY p.start_date ASC
                LIMIT 5";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId ?? $_SESSION['user_id']]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} 