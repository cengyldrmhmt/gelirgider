<?php
require_once __DIR__ . '/../core/Model.php';

class Wallet extends Model {
    protected $table = 'wallets';
    
    public function getActiveCount($userId) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return $result['count'];
    }
    
    /**
     * Gerçek bakiye hesaplama - işlemler bazında
     * Cüzdan bakiyesi = Başlangıç bakiyesi + Gelirler - Giderler
     */
    public function getTotalBalance($userId) {
        // Tüm cüzdanların gerçek bakiyesini hesapla
        $sql = "SELECT 
                    w.id,
                    w.name,
                    w.currency,
                    w.balance as initial_balance,
                    COALESCE(SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END), 0) as total_income,
                    COALESCE(SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END), 0) as total_expense,
                    (w.balance + 
                     COALESCE(SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END), 0) - 
                     COALESCE(SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END), 0)
                    ) as real_balance
                FROM {$this->table} w
                LEFT JOIN transactions t ON w.id = t.wallet_id AND t.user_id = ?
                WHERE w.user_id = ?
                GROUP BY w.id, w.name, w.currency, w.balance";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $userId]);
        $wallets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $totalBalance = 0;
        foreach ($wallets as $wallet) {
            $balance = $wallet['real_balance'];
            
            // Döviz kurları ile TRY'ye çevir
            switch ($wallet['currency']) {
                case 'USD':
                    $balance *= 34.25; // USD/TRY kuru
                    break;
                case 'EUR':
                    $balance *= 37.15; // EUR/TRY kuru
                    break;
                case 'GBP':
                    $balance *= 43.85; // GBP/TRY kuru
                    break;
                case 'TRY':
                default:
                    // TRY için değişiklik yok
                    break;
            }
            
            $totalBalance += $balance;
        }
        
        return $totalBalance;
    }
    
    /**
     * Belirli bir cüzdanın gerçek bakiyesini hesapla
     */
    public function getRealBalance($walletId, $userId) {
        $sql = "SELECT 
                    w.balance as initial_balance,
                    COALESCE(SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END), 0) as total_income,
                    COALESCE(SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END), 0) as total_expense
                FROM {$this->table} w
                LEFT JOIN transactions t ON w.id = t.wallet_id
                WHERE w.id = ? AND w.user_id = ?
                GROUP BY w.id, w.balance";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$walletId, $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            return 0;
        }
        
        return $result['initial_balance'] + $result['total_income'] - $result['total_expense'];
    }
    
    public function getBalancesByCurrency($userId) {
        $sql = "SELECT 
                    w.currency, 
                    SUM(w.balance + 
                        COALESCE(income.total, 0) - 
                        COALESCE(expense.total, 0)
                    ) as total_balance, 
                    COUNT(*) as wallet_count
                FROM {$this->table} w
                LEFT JOIN (
                    SELECT wallet_id, SUM(amount) as total 
                    FROM transactions 
                    WHERE type = 'income' AND user_id = ?
                    GROUP BY wallet_id
                ) income ON w.id = income.wallet_id
                LEFT JOIN (
                    SELECT wallet_id, SUM(amount) as total 
                    FROM transactions 
                    WHERE type = 'expense' AND user_id = ?
                    GROUP BY wallet_id
                ) expense ON w.id = expense.wallet_id
                WHERE w.user_id = ?
                GROUP BY w.currency
                ORDER BY total_balance DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $userId, $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getAll($userId = null) {
        $sql = "SELECT 
                    w.*,
                    COALESCE(SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END), 0) as total_income,
                    COALESCE(SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END), 0) as total_expense,
                    (w.balance + 
                     COALESCE(SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END), 0) - 
                     COALESCE(SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END), 0)
                    ) as real_balance
                FROM {$this->table} w
                LEFT JOIN transactions t ON w.id = t.wallet_id
                WHERE w.user_id = ?
                GROUP BY w.id, w.name, w.balance, w.currency, w.type, w.color, w.icon, w.is_default, w.created_at, w.updated_at
                ORDER BY w.name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId ?? $_SESSION['user_id']]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function create($data, $userId = null) {
        $sql = "INSERT INTO {$this->table} (user_id, name, balance, currency, type, color, icon) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $userId ?? $_SESSION['user_id'],
            $data['name'],
            $data['balance'] ?? 0,
            $data['currency'] ?? 'TRY',
            $data['type'] ?? 'cash',
            $data['color'] ?? '#007bff',
            $data['icon'] ?? 'wallet'
        ]);
    }
    
    public function update($id, $data, $userId = null) {
        $sql = "UPDATE {$this->table} SET name = ?, balance = ?, currency = ?, type = ?, color = ?, icon = ? WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['name'],
            $data['balance'] ?? 0,
            $data['currency'] ?? 'TRY',
            $data['type'] ?? 'cash',
            $data['color'] ?? '#007bff',
            $data['icon'] ?? 'wallet',
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
        $sql = "SELECT 
                    w.*,
                    COALESCE(SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END), 0) as total_income,
                    COALESCE(SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END), 0) as total_expense,
                    (w.balance + 
                     COALESCE(SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END), 0) - 
                     COALESCE(SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END), 0)
                    ) as real_balance
                FROM {$this->table} w
                LEFT JOIN transactions t ON w.id = t.wallet_id
                WHERE w.id = ? AND w.user_id = ?
                GROUP BY w.id, w.name, w.balance, w.currency, w.type, w.color, w.icon, w.is_default, w.created_at, w.updated_at";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id, $userId ?? $_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function updateBalance($id, $amount, $userId = null) {
        $sql = "UPDATE {$this->table} SET balance = balance + ? WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$amount, $id, $userId ?? $_SESSION['user_id']]);
    }
    
    public function createTransaction($data) {
        $sql = "INSERT INTO wallet_transactions (wallet_id, type, amount, description, target_wallet_id, transaction_date) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['wallet_id'],
            $data['type'],
            $data['amount'],
            $data['description'],
            $data['target_wallet_id'] ?? null
        ]);
    }
    
    public function getTransactions($walletId, $userId) {
        // Önce cüzdanın kullanıcıya ait olduğunu kontrol et
        $wallet = $this->get($walletId, $userId);
        if (!$wallet) {
            return [];
        }
        
        $sql = "SELECT * FROM wallet_transactions 
                WHERE wallet_id = ? 
                ORDER BY transaction_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$walletId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllTransactions($userId, $walletId = null, $type = null) {
        $sql = "SELECT 
                    t.*,
                    w.name as wallet_name,
                    w.color as wallet_color,
                    w.icon as wallet_icon,
                    w.currency,
                    c.name as category_name,
                    c.color as category_color
                FROM transactions t
                LEFT JOIN {$this->table} w ON t.wallet_id = w.id
                LEFT JOIN categories c ON t.category_id = c.id
                WHERE t.user_id = ?";
        
        $params = [$userId];
        
        if ($walletId) {
            $sql .= " AND t.wallet_id = ?";
            $params[] = $walletId;
        }
        
        if ($type) {
            $sql .= " AND t.type = ?";
            $params[] = $type;
        }
        
        $sql .= " ORDER BY t.transaction_date DESC, t.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteTransaction($transactionId, $userId) {
        try {
            $this->db->beginTransaction();
            
            // İşlemi kontrol et
            $sql = "SELECT t.*, w.user_id as wallet_user_id 
                    FROM transactions t 
                    LEFT JOIN {$this->table} w ON t.wallet_id = w.id 
                    WHERE t.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$transactionId]);
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$transaction || $transaction['wallet_user_id'] != $userId) {
                throw new Exception('İşlem bulunamadı veya yetkiniz yok.');
            }
            
            // Önce transaction tag'lerini sil
            $tagSql = "DELETE FROM transaction_tags WHERE transaction_id = ?";
            $tagStmt = $this->db->prepare($tagSql);
            $tagStmt->execute([$transactionId]);
            
            // Sonra işlemi sil
            $sql = "DELETE FROM transactions WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$transactionId]);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function getById($id, $userId) {
        return $this->get($id, $userId);
    }
} 