<?php
require_once __DIR__ . '/../core/Model.php';

class Report extends Model {
    protected $table = 'reports';

    public function create($data, $userId = null) {
        $sql = "INSERT INTO {$this->table} (user_id, title, type, start_date, end_date, filters) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $userId ?? $_SESSION['user_id'],
            $data['title'],
            $data['type'],
            $data['start_date'],
            $data['end_date'],
            json_encode($data['filters'])
        ]);
    }

    public function update($id, $data, $userId = null) {
        $sql = "UPDATE {$this->table} 
                SET title = ?, type = ?, start_date = ?, end_date = ?, filters = ? 
                WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['title'],
            $data['type'],
            $data['start_date'],
            $data['end_date'],
            json_encode($data['filters']),
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
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $result['filters'] = json_decode($result['filters'], true);
        }
        return $result;
    }

    public function getAll($userId = null) {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId ?? $_SESSION['user_id']]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($results as &$result) {
            $result['filters'] = json_decode($result['filters'], true);
        }
        return $results;
    }

    public function generateReport($id, $userId = null) {
        $report = $this->get($id, $userId);
        if (!$report) {
            return null;
        }

        $filters = $report['filters'];
        $startDate = $report['start_date'];
        $endDate = $report['end_date'];

        switch ($report['type']) {
            case 'income_expense':
                return $this->generateIncomeExpenseReport($startDate, $endDate, $filters, $userId);
            case 'category_analysis':
                return $this->generateCategoryAnalysisReport($startDate, $endDate, $filters, $userId);
            case 'budget_tracking':
                return $this->generateBudgetTrackingReport($startDate, $endDate, $filters, $userId);
            default:
                return null;
        }
    }

    private function generateIncomeExpenseReport($startDate, $endDate, $filters, $userId = null) {
        $sql = "SELECT 
                    DATE_FORMAT(date, '%Y-%m') as month,
                    SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
                    SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense
                FROM transactions 
                WHERE user_id = ? 
                AND date BETWEEN ? AND ?
                GROUP BY month
                ORDER BY month";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId ?? $_SESSION['user_id'], $startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function generateCategoryAnalysisReport($startDate, $endDate, $filters, $userId = null) {
        $sql = "SELECT 
                    c.name as category,
                    SUM(t.amount) as total_amount,
                    COUNT(t.id) as transaction_count
                FROM transactions t
                JOIN categories c ON t.category_id = c.id
                WHERE t.user_id = ? 
                AND t.date BETWEEN ? AND ?
                GROUP BY c.id, c.name
                ORDER BY total_amount DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId ?? $_SESSION['user_id'], $startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function generateBudgetTrackingReport($startDate, $endDate, $filters, $userId = null) {
        $sql = "SELECT 
                    b.name as budget_name,
                    b.amount as budget_amount,
                    COALESCE(SUM(t.amount), 0) as spent_amount,
                    (COALESCE(SUM(t.amount), 0) / b.amount * 100) as percentage_used
                FROM budgets b
                LEFT JOIN transactions t ON 
                    t.category_id = b.category_id 
                    AND t.type = 'expense'
                    AND t.date BETWEEN ? AND ?
                WHERE b.user_id = ?
                GROUP BY b.id, b.name, b.amount
                ORDER BY percentage_used DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$startDate, $endDate, $userId ?? $_SESSION['user_id']]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getIncome($userId, $startDate, $endDate) {
        $sql = "SELECT 
                    t.transaction_date,
                    t.amount,
                    t.description,
                    COALESCE(c.name, 'Kategori Yok') as category_name,
                    COALESCE(w.name, 'Cüzdan Yok') as wallet_name
                FROM transactions t
                LEFT JOIN categories c ON t.category_id = c.id
                LEFT JOIN wallets w ON t.wallet_id = w.id
                WHERE t.user_id = ? 
                AND t.type = 'income'
                AND t.transaction_date BETWEEN ? AND ?
                ORDER BY t.transaction_date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getExpense($userId, $startDate, $endDate) {
        $sql = "SELECT 
                    t.transaction_date,
                    t.amount,
                    t.description,
                    COALESCE(c.name, 'Kategori Yok') as category_name,
                    COALESCE(w.name, 'Cüzdan Yok') as wallet_name
                FROM transactions t
                LEFT JOIN categories c ON t.category_id = c.id
                LEFT JOIN wallets w ON t.wallet_id = w.id
                WHERE t.user_id = ? 
                AND t.type = 'expense'
                AND t.transaction_date BETWEEN ? AND ?
                ORDER BY t.transaction_date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getCategoryStats($userId, $startDate, $endDate) {
        $sql = "SELECT 
                    COALESCE(c.name, 'Kategori Yok') as category_name,
                    COALESCE(c.type, 'unknown') as type,
                    COUNT(*) as transaction_count,
                    SUM(t.amount) as total_amount
                FROM transactions t
                LEFT JOIN categories c ON t.category_id = c.id
                WHERE t.user_id = ? 
                AND t.transaction_date BETWEEN ? AND ?
                GROUP BY c.id, c.name, c.type
                ORDER BY total_amount DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getWalletStats($userId, $startDate, $endDate) {
        $sql = "SELECT 
                    COALESCE(w.name, 'Cüzdan Yok') as wallet_name,
                    COALESCE(w.currency, 'TRY') as currency,
                    COUNT(*) as transaction_count,
                    SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE -t.amount END) as net_amount
                FROM transactions t
                LEFT JOIN wallets w ON t.wallet_id = w.id
                WHERE t.user_id = ? 
                AND t.transaction_date BETWEEN ? AND ?
                GROUP BY w.id, w.name, w.currency
                ORDER BY net_amount DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getMonthlyStats($userId) {
        $sql = "SELECT 
                    DATE_FORMAT(transaction_date, '%Y-%m') as month,
                    COUNT(*) as transaction_count,
                    SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
                    SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense
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