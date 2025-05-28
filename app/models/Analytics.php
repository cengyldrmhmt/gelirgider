<?php
require_once __DIR__ . '/../core/Model.php';

class Analytics extends Model {
    protected $table = 'analytics';

    public function getIncome($userId = null, $startDate = null, $endDate = null) {
        $sql = "SELECT 
                    DATE_FORMAT(date, '%Y-%m') as month,
                    SUM(amount) as total_income
                FROM transactions 
                WHERE user_id = ? 
                AND type = 'income'";
        
        $params = [$userId ?? $_SESSION['user_id']];
        
        if ($startDate) {
            $sql .= " AND date >= ?";
            $params[] = $startDate;
        }
        if ($endDate) {
            $sql .= " AND date <= ?";
            $params[] = $endDate;
        }
        
        $sql .= " GROUP BY month ORDER BY month";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getExpenses($userId = null, $startDate = null, $endDate = null) {
        $sql = "SELECT 
                    DATE_FORMAT(date, '%Y-%m') as month,
                    SUM(amount) as total_expense
                FROM transactions 
                WHERE user_id = ? 
                AND type = 'expense'";
        
        $params = [$userId ?? $_SESSION['user_id']];
        
        if ($startDate) {
            $sql .= " AND date >= ?";
            $params[] = $startDate;
        }
        if ($endDate) {
            $sql .= " AND date <= ?";
            $params[] = $endDate;
        }
        
        $sql .= " GROUP BY month ORDER BY month";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCategoryExpenses($userId = null, $startDate = null, $endDate = null) {
        $sql = "SELECT 
                    c.name as category,
                    SUM(t.amount) as total_amount
                FROM transactions t
                JOIN categories c ON t.category_id = c.id
                WHERE t.user_id = ? 
                AND t.type = 'expense'";
        
        $params = [$userId ?? $_SESSION['user_id']];
        
        if ($startDate) {
            $sql .= " AND t.date >= ?";
            $params[] = $startDate;
        }
        if ($endDate) {
            $sql .= " AND t.date <= ?";
            $params[] = $endDate;
        }
        
        $sql .= " GROUP BY c.id, c.name ORDER BY total_amount DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMonthlyBalance($userId = null, $startDate = null, $endDate = null) {
        $sql = "SELECT 
                    DATE_FORMAT(date, '%Y-%m') as month,
                    SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END) as balance
                FROM transactions 
                WHERE user_id = ?";
        
        $params = [$userId ?? $_SESSION['user_id']];
        
        if ($startDate) {
            $sql .= " AND date >= ?";
            $params[] = $startDate;
        }
        if ($endDate) {
            $sql .= " AND date <= ?";
            $params[] = $endDate;
        }
        
        $sql .= " GROUP BY month ORDER BY month";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getBudgetProgress($userId = null) {
        $sql = "SELECT 
                    b.name as budget_name,
                    b.amount as budget_amount,
                    COALESCE(SUM(t.amount), 0) as spent_amount,
                    (COALESCE(SUM(t.amount), 0) / b.amount * 100) as percentage_used
                FROM budgets b
                LEFT JOIN transactions t ON 
                    t.category_id = b.category_id 
                    AND t.type = 'expense'
                    AND t.date BETWEEN b.start_date AND COALESCE(b.end_date, CURDATE())
                WHERE b.user_id = ?
                GROUP BY b.id, b.name, b.amount
                ORDER BY percentage_used DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId ?? $_SESSION['user_id']]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDailyStats($userId, $startDate, $endDate) {
        $sql = "SELECT 
                    DATE(transaction_date) as date,
                    SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
                    SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense,
                    COUNT(*) as transaction_count
                FROM transactions 
                WHERE user_id = ? 
                AND transaction_date BETWEEN ? AND ?
                GROUP BY DATE(transaction_date)
                ORDER BY date";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getCategoryDistribution($userId, $startDate, $endDate) {
        $sql = "SELECT 
                    c.name as category_name,
                    c.type,
                    c.color,
                    COUNT(*) as transaction_count,
                    SUM(t.amount) as total_amount,
                    AVG(t.amount) as average_amount
                FROM transactions t
                JOIN categories c ON t.category_id = c.id
                WHERE t.user_id = ? 
                AND t.transaction_date BETWEEN ? AND ?
                GROUP BY c.id, c.name, c.type, c.color
                ORDER BY total_amount DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getWalletDistribution($userId, $startDate, $endDate) {
        $sql = "SELECT 
                    w.name as wallet_name,
                    w.currency,
                    w.color,
                    COUNT(*) as transaction_count,
                    SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE -t.amount END) as net_amount,
                    SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END) as total_income,
                    SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END) as total_expense
                FROM transactions t
                JOIN wallets w ON t.wallet_id = w.id
                WHERE t.user_id = ? 
                AND t.transaction_date BETWEEN ? AND ?
                GROUP BY w.id, w.name, w.currency, w.color
                ORDER BY net_amount DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getTrendAnalysis($userId) {
        $sql = "SELECT 
                    DATE_FORMAT(transaction_date, '%Y-%m') as month,
                    SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
                    SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense,
                    COUNT(*) as transaction_count,
                    COUNT(DISTINCT category_id) as category_count
                FROM transactions
                WHERE user_id = ?
                GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
                ORDER BY month DESC
                LIMIT 12";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} 