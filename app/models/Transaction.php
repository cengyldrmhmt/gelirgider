<?php
require_once __DIR__ . '/../core/Model.php';

class Transaction extends Model {
    protected $table = 'transactions';
    
    public function getTotalIncome($userId) {
        $sql = "SELECT COALESCE(SUM(amount), 0) as total FROM {$this->table} WHERE user_id = ? AND type = 'income'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return $result['total'];
    }
    
    public function getTotalExpense($userId) {
        $sql = "SELECT COALESCE(SUM(amount), 0) as total FROM {$this->table} WHERE user_id = ? AND type = 'expense'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return $result['total'];
    }
    
    public function getRecent($userId, $limit = 5) {
        $sql = "SELECT t.*, c.name as category_name 
                FROM {$this->table} t 
                LEFT JOIN categories c ON t.category_id = c.id 
                WHERE t.user_id = ? 
                ORDER BY t.transaction_date DESC, t.created_at DESC
                LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getRecentTransactions($userId, $limit = 5) {
        return $this->getRecent($userId, $limit);
    }
    
    public function create($data, $userId = null) {
        try {
            $this->db->beginTransaction();
            
            $sql = "INSERT INTO transactions (user_id, type, amount, description, transaction_date, category_id, wallet_id) 
                    VALUES (:user_id, :type, :amount, :description, :transaction_date, :category_id, :wallet_id)";
            
            $stmt = $this->db->prepare($sql);
            
            // Varsayılan tarih olarak bugünü kullan
            $transaction_date = $data['transaction_date'] ?? date('Y-m-d H:i:s');
            
            $result = $stmt->execute([
                'user_id' => $userId ?? $_SESSION['user_id'],
                'type' => $data['type'],
                'amount' => $data['amount'],
                'description' => $data['description'] ?? '',
                'transaction_date' => $transaction_date,
                'category_id' => !empty($data['category_id']) ? $data['category_id'] : null,
                'wallet_id' => $data['wallet_id']
            ]);
            
            if ($result) {
                $transactionId = $this->db->lastInsertId();
                $this->db->commit();
                return $transactionId;
            }
            
            $this->db->rollback();
            return false;
        } catch (PDOException $e) {
            $this->db->rollback();
            error_log("Transaction create error: " . $e->getMessage());
            return false;
        }
    }
    
    public function update($id, $data, $userId = null) {
        try {
            $this->db->beginTransaction();
            
            $sql = "UPDATE {$this->table} 
                    SET type = ?, amount = ?, description = ?, transaction_date = ?, category_id = ?, wallet_id = ? 
                    WHERE id = ? AND user_id = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $data['type'],
                $data['amount'],
                $data['description'],
                $data['transaction_date'] ?? $data['date'] ?? date('Y-m-d H:i:s'),
                $data['category_id'],
                $data['wallet_id'],
                $id,
                $userId ?? $_SESSION['user_id']
            ]);
            
            if ($result) {
                $this->db->commit();
                return true;
            }
            
            $this->db->rollback();
            return false;
        } catch (PDOException $e) {
            $this->db->rollback();
            error_log("Transaction update error: " . $e->getMessage());
            return false;
        }
    }
    
    public function saveTransactionTags($transactionId, $tagIds) {
        try {
            $sql = "INSERT INTO transaction_tags (transaction_id, tag_id) VALUES (?, ?)";
            $stmt = $this->db->prepare($sql);
            
            foreach ($tagIds as $tagId) {
                $stmt->execute([$transactionId, $tagId]);
            }
            
            return true;
        } catch (PDOException $e) {
            error_log("Transaction saveTransactionTags error: " . $e->getMessage());
            return false;
        }
    }
    
    public function deleteTransactionTags($transactionId) {
        try {
            $sql = "DELETE FROM transaction_tags WHERE transaction_id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$transactionId]);
        } catch (PDOException $e) {
            error_log("Transaction deleteTransactionTags error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getTransactionTags($transactionId) {
        try {
            $sql = "SELECT t.* FROM tags t 
                    INNER JOIN transaction_tags tt ON t.id = tt.tag_id 
                    WHERE tt.transaction_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$transactionId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Transaction getTransactionTags error: " . $e->getMessage());
            return [];
        }
    }
    
    public function delete($id, $userId = null) {
        $sql = "DELETE FROM {$this->table} WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id, $userId ?? $_SESSION['user_id']]);
    }
    
    public function get($id, $userId = null) {
        $sql = "SELECT t.*, c.name as category_name, w.name as wallet_name
                FROM {$this->table} t
                LEFT JOIN categories c ON t.category_id = c.id
                LEFT JOIN wallets w ON t.wallet_id = w.id
                WHERE t.id = ? AND t.user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id, $userId ?? $_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getAll($userId = null, $filters = []) {
        try {
            $sql = "SELECT t.*, c.name as category_name, w.name as wallet_name,
                    GROUP_CONCAT(DISTINCT tg.name) as tags
                    FROM transactions t
                    LEFT JOIN categories c ON t.category_id = c.id
                    LEFT JOIN wallets w ON t.wallet_id = w.id
                    LEFT JOIN transaction_tags tt ON t.id = tt.transaction_id
                    LEFT JOIN tags tg ON tt.tag_id = tg.id
                    WHERE t.user_id = :user_id";
            
            $params = ['user_id' => $userId ?? $_SESSION['user_id']];
            
            // Tarih filtresi
            if (!empty($filters['start_date'])) {
                $sql .= " AND t.transaction_date >= :start_date";
                $params['start_date'] = $filters['start_date'];
            }
            if (!empty($filters['end_date'])) {
                $sql .= " AND t.transaction_date <= :end_date";
                $params['end_date'] = $filters['end_date'];
            }
            
            // Kategori filtresi
            if (!empty($filters['category_id'])) {
                $sql .= " AND t.category_id = :category_id";
                $params['category_id'] = $filters['category_id'];
            }
            
            // Cüzdan filtresi
            if (!empty($filters['wallet_id'])) {
                $sql .= " AND t.wallet_id = :wallet_id";
                $params['wallet_id'] = $filters['wallet_id'];
            }
            
            // Tip filtresi
            if (!empty($filters['type'])) {
                $sql .= " AND t.type = :type";
                $params['type'] = $filters['type'];
            }
            
            // Etiket filtresi
            if (!empty($filters['tag_id'])) {
                $sql .= " AND EXISTS (
                    SELECT 1 FROM transaction_tags tt2 
                    WHERE tt2.transaction_id = t.id 
                    AND tt2.tag_id = :tag_id
                )";
                $params['tag_id'] = $filters['tag_id'];
            }
            
            $sql .= " GROUP BY t.id ORDER BY t.transaction_date DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Transaction getAll error: " . $e->getMessage());
            return [];
        }
    }

    public function getAllByUser() {
        $query = "SELECT t.*, 
                        c.name as category_name, 
                        w.name as wallet_name
                 FROM transactions t
                 LEFT JOIN categories c ON t.category_id = c.id
                 LEFT JOIN wallets w ON t.wallet_id = w.id
                 WHERE t.user_id = :user_id
                 ORDER BY t.date DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} 