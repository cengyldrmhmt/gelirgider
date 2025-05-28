<?php
// Check if session is already active before starting
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor.']);
    exit;
}

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/Report.php';
require_once __DIR__ . '/../core/Database.php';

class ReportController extends Controller {
    private $reportModel;
    private $db;
    
    public function __construct() {
        $this->reportModel = new Report();
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function index() {
        $userId = $_SESSION['user_id'];
        $startDate = $_GET['start_date'] ?? date('Y-m-01'); // Ayın başı
        $endDate = $_GET['end_date'] ?? date('Y-m-t'); // Ayın sonu
        $reportType = $_GET['report_type'] ?? 'all';
        $period = $_GET['period'] ?? 'monthly'; // daily, weekly, monthly, yearly
        
        $data = [
            // Temel veriler
            'income' => $this->getWalletIncome($userId, $startDate, $endDate),
            'expense' => $this->getWalletExpense($userId, $startDate, $endDate),
            'creditCardExpenses' => $this->getCreditCardExpenses($userId, $startDate, $endDate),
            'creditCardPayments' => $this->getCreditCardPayments($userId, $startDate, $endDate),
            'paymentPlanExpenses' => $this->getPaymentPlanExpenses($userId, $startDate, $endDate),
            
            // İstatistikler
            'categoryStats' => $this->getCategoryStats($userId, $startDate, $endDate),
            'walletStats' => $this->getWalletStats($userId, $startDate, $endDate),
            'creditCardStats' => $this->getCreditCardStats($userId, $startDate, $endDate),
            'paymentPlanStats' => $this->getPaymentPlanStats($userId, $startDate, $endDate),
            
            // Trend verileri
            'monthlyTrends' => $this->getMonthlyTrends($userId),
            'weeklyTrends' => $this->getWeeklyTrends($userId, $startDate, $endDate),
            'dailyTrends' => $this->getDailyTrends($userId, $startDate, $endDate),
            'yearlyTrends' => $this->getYearlyTrends($userId),
            
            // Grafik verileri
            'categoryChart' => $this->getCategoryChartData($userId, $startDate, $endDate),
            'incomeExpenseChart' => $this->getIncomeExpenseChartData($userId, $period),
            'walletDistribution' => $this->getWalletDistribution($userId),
            'creditCardUsage' => $this->getCreditCardUsageChart($userId, $startDate, $endDate),
            'installmentChart' => $this->getInstallmentChartData($userId),
            
            // Özet veriler
            'summary' => $this->getSummaryData($userId, $startDate, $endDate),
            'topCategories' => $this->getTopCategories($userId, $startDate, $endDate),
            'topMerchants' => $this->getTopMerchants($userId, $startDate, $endDate),
            'budgetComparison' => $this->getBudgetComparison($userId, $startDate, $endDate),
            
            // Parametreler
            'startDate' => $startDate,
            'endDate' => $endDate,
            'reportType' => $reportType,
            'period' => $period
        ];
        
        return $data;
    }
    
    // Cüzdan gelirleri
    private function getWalletIncome($userId, $startDate, $endDate) {
        $sql = "SELECT 
                    t.transaction_date,
                    t.amount,
                    t.description,
                    COALESCE(c.name, 'Kategori Yok') as category_name,
                    COALESCE(w.name, 'Cüzdan Yok') as wallet_name,
                    w.currency,
                    'wallet' as source_type
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
    
    // Cüzdan giderleri
    private function getWalletExpense($userId, $startDate, $endDate) {
        $sql = "SELECT 
                    t.transaction_date,
                    t.amount,
                    t.description,
                    COALESCE(c.name, 'Kategori Yok') as category_name,
                    COALESCE(w.name, 'Cüzdan Yok') as wallet_name,
                    w.currency,
                    'wallet' as source_type
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
    
    // Kredi kartı harcamaları (taksitli ödemeler için aylık tutarlar)
    private function getCreditCardExpenses($userId, $startDate, $endDate) {
        $sql = "SELECT 
                    cct.transaction_date,
                    CASE 
                        WHEN cct.installment_count > 1 AND cct.parent_transaction_id IS NULL 
                        THEN cct.amount / cct.installment_count
                        ELSE cct.amount
                    END as amount,
                    cct.description,
                    COALESCE(c.name, 'Kategori Yok') as category_name,
                    cc.name as card_name,
                    cct.merchant_name,
                    cct.installment_count,
                    cct.installment_number,
                    'credit_card' as source_type,
                    CASE 
                        WHEN cct.installment_count > 1 
                        THEN CONCAT(cct.description, ' (', cct.installment_count, ' Taksit)')
                        ELSE cct.description
                    END as full_description
                FROM credit_card_transactions cct
                LEFT JOIN categories c ON cct.category_id = c.id
                LEFT JOIN credit_cards cc ON cct.credit_card_id = cc.id
                WHERE cc.user_id = ? 
                AND cct.type IN ('purchase', 'fee', 'interest')
                AND cct.parent_transaction_id IS NULL
                AND cct.transaction_date BETWEEN ? AND ?
                ORDER BY cct.transaction_date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Kredi kartı ödemeleri
    private function getCreditCardPayments($userId, $startDate, $endDate) {
        $sql = "SELECT 
                    cct.transaction_date,
                    cct.amount,
                    cct.description,
                    cc.name as card_name,
                    'credit_card_payment' as source_type
                FROM credit_card_transactions cct
                LEFT JOIN credit_cards cc ON cct.credit_card_id = cc.id
                WHERE cc.user_id = ? 
                AND cct.type IN ('payment', 'refund')
                AND cct.transaction_date BETWEEN ? AND ?
                ORDER BY cct.transaction_date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Ödeme planı giderleri
    private function getPaymentPlanExpenses($userId, $startDate, $endDate) {
        $sql = "SELECT 
                    ppi.due_date as transaction_date,
                    ppi.amount,
                    CONCAT(pp.title, ' - ', ppi.title) as description,
                    COALESCE(c.name, 'Kategori Yok') as category_name,
                    'payment_plan' as source_type,
                    pp.title as plan_title,
                    ppi.status
                FROM payment_plan_items ppi
                INNER JOIN payment_plans pp ON ppi.payment_plan_id = pp.id
                LEFT JOIN categories c ON pp.category_id = c.id
                WHERE pp.user_id = ? 
                AND pp.status != 'cancelled'
                AND ppi.due_date BETWEEN ? AND ?
                ORDER BY ppi.due_date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Kategori istatistikleri (tüm kaynaklar dahil)
    private function getCategoryStats($userId, $startDate, $endDate) {
        $sql = "SELECT 
                    category_name,
                    category_type,
                    SUM(amount) as total_amount,
                    COUNT(*) as transaction_count,
                    AVG(amount) as average_amount,
                    MIN(amount) as min_amount,
                    MAX(amount) as max_amount
                FROM (
                    -- Cüzdan işlemleri
                    SELECT 
                        COALESCE(c.name, 'Kategori Yok') as category_name,
                        COALESCE(c.type, 'unknown') as category_type,
                        t.amount
                    FROM transactions t
                    LEFT JOIN categories c ON t.category_id = c.id
                    WHERE t.user_id = ? 
                    AND t.transaction_date BETWEEN ? AND ?
                    
                    UNION ALL
                    
                    -- Kredi kartı harcamaları (aylık taksit tutarları)
                    SELECT 
                        COALESCE(c.name, 'Kategori Yok') as category_name,
                        COALESCE(c.type, 'expense') as category_type,
                        CASE 
                            WHEN cct.installment_count > 1 AND cct.parent_transaction_id IS NULL 
                            THEN cct.amount / cct.installment_count
                            ELSE cct.amount
                        END as amount
                    FROM credit_card_transactions cct
                    LEFT JOIN categories c ON cct.category_id = c.id
                    LEFT JOIN credit_cards cc ON cct.credit_card_id = cc.id
                    WHERE cc.user_id = ? 
                    AND cct.type IN ('purchase', 'fee', 'interest')
                    AND cct.parent_transaction_id IS NULL
                    AND cct.transaction_date BETWEEN ? AND ?
                    
                    UNION ALL
                    
                    -- Ödeme planı giderleri
                    SELECT 
                        COALESCE(c.name, 'Kategori Yok') as category_name,
                        COALESCE(c.type, 'expense') as category_type,
                        ppi.amount
                    FROM payment_plan_items ppi
                    INNER JOIN payment_plans pp ON ppi.payment_plan_id = pp.id
                    LEFT JOIN categories c ON pp.category_id = c.id
                    WHERE pp.user_id = ? 
                    AND pp.status != 'cancelled'
                    AND ppi.due_date BETWEEN ? AND ?
                ) combined_data
                GROUP BY category_name, category_type
                ORDER BY total_amount DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $startDate, $endDate, $userId, $startDate, $endDate, $userId, $startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Cüzdan istatistikleri
    private function getWalletStats($userId, $startDate, $endDate) {
        $sql = "SELECT 
                    w.name as wallet_name,
                    w.currency,
                    w.balance as current_balance,
                    COUNT(t.id) as transaction_count,
                    SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END) as total_income,
                    SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END) as total_expense,
                    (SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END) - 
                     SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END)) as net_change
                FROM wallets w
                LEFT JOIN transactions t ON w.id = t.wallet_id 
                    AND t.transaction_date BETWEEN ? AND ?
                WHERE w.user_id = ?
                GROUP BY w.id, w.name, w.currency, w.balance
                ORDER BY total_expense DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$startDate, $endDate, $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Kredi kartı istatistikleri
    private function getCreditCardStats($userId, $startDate, $endDate) {
        $sql = "SELECT 
                    cc.name as card_name,
                    cc.credit_limit,
                    cc.currency,
                    COUNT(cct.id) as transaction_count,
                    SUM(CASE WHEN cct.type IN ('purchase', 'fee', 'interest') THEN cct.amount ELSE 0 END) as total_expenses,
                    SUM(CASE WHEN cct.type IN ('payment', 'refund') THEN cct.amount ELSE 0 END) as total_payments,
                    AVG(CASE WHEN cct.type IN ('purchase', 'fee', 'interest') THEN cct.amount END) as avg_expense,
                    -- Aylık taksit tutarları toplamı
                    SUM(CASE 
                        WHEN cct.type IN ('purchase', 'fee', 'interest') AND cct.installment_count > 1 AND cct.parent_transaction_id IS NULL
                        THEN cct.amount / cct.installment_count
                        WHEN cct.type IN ('purchase', 'fee', 'interest') AND (cct.installment_count <= 1 OR cct.installment_count IS NULL)
                        THEN cct.amount
                        ELSE 0
                    END) as monthly_expense_impact,
                    -- Taksitli işlem sayısı
                    COUNT(CASE WHEN cct.installment_count > 1 AND cct.parent_transaction_id IS NULL THEN 1 END) as installment_transactions
                FROM credit_cards cc
                LEFT JOIN credit_card_transactions cct ON cc.id = cct.credit_card_id 
                    AND cct.transaction_date BETWEEN ? AND ?
                WHERE cc.user_id = ? AND cc.is_active = 1
                GROUP BY cc.id, cc.name, cc.credit_limit, cc.currency
                ORDER BY monthly_expense_impact DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$startDate, $endDate, $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Ödeme planı istatistikleri
    private function getPaymentPlanStats($userId, $startDate, $endDate) {
        $sql = "SELECT 
                    pp.title as plan_title,
                    pp.total_amount,
                    pp.paid_amount,
                    pp.remaining_amount,
                    pp.status,
                    COUNT(ppi.id) as total_items,
                    COUNT(CASE WHEN ppi.status = 'paid' THEN 1 END) as paid_items,
                    COUNT(CASE WHEN ppi.status = 'pending' THEN 1 END) as pending_items,
                    COUNT(CASE WHEN ppi.status = 'overdue' THEN 1 END) as overdue_items,
                    SUM(CASE WHEN ppi.due_date BETWEEN ? AND ? THEN ppi.amount ELSE 0 END) as period_amount
                FROM payment_plans pp
                LEFT JOIN payment_plan_items ppi ON pp.id = ppi.payment_plan_id
                WHERE pp.user_id = ? AND pp.status != 'cancelled'
                GROUP BY pp.id, pp.title, pp.total_amount, pp.paid_amount, pp.remaining_amount, pp.status
                ORDER BY pp.total_amount DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$startDate, $endDate, $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Aylık trendler
    private function getMonthlyTrends($userId) {
        $sql = "SELECT 
                    DATE_FORMAT(period_date, '%Y-%m') as period,
                    DATE_FORMAT(period_date, '%Y-%m-01') as period_start,
                    wallet_income,
                    wallet_expense,
                    credit_card_expense,
                    credit_card_payment,
                    payment_plan_expense,
                    (wallet_income - wallet_expense - credit_card_expense + credit_card_payment - payment_plan_expense) as net_amount
                FROM (
                    SELECT 
                        DATE_FORMAT(t.transaction_date, '%Y-%m-01') as period_date,
                        SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END) as wallet_income,
                        SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END) as wallet_expense,
                        0 as credit_card_expense,
                        0 as credit_card_payment,
                        0 as payment_plan_expense
                    FROM transactions t
                    INNER JOIN wallets w ON t.wallet_id = w.id
                    WHERE w.user_id = ?
                    AND t.transaction_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                    GROUP BY DATE_FORMAT(t.transaction_date, '%Y-%m')
                    
                    UNION ALL
                    
                    SELECT 
                        DATE_FORMAT(cct.transaction_date, '%Y-%m-01') as period_date,
                        0 as wallet_income,
                        0 as wallet_expense,
                        SUM(CASE 
                            WHEN cct.type IN ('purchase', 'fee', 'interest') AND cct.installment_count > 1 AND cct.parent_transaction_id IS NULL
                            THEN cct.amount / cct.installment_count
                            WHEN cct.type IN ('purchase', 'fee', 'interest') AND (cct.installment_count <= 1 OR cct.installment_count IS NULL)
                            THEN cct.amount
                            ELSE 0
                        END) as credit_card_expense,
                        SUM(CASE WHEN cct.type IN ('payment', 'refund') THEN cct.amount ELSE 0 END) as credit_card_payment,
                        0 as payment_plan_expense
                    FROM credit_card_transactions cct
                    INNER JOIN credit_cards cc ON cct.credit_card_id = cc.id
                    WHERE cc.user_id = ?
                    AND cct.transaction_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                    GROUP BY DATE_FORMAT(cct.transaction_date, '%Y-%m')
                    
                    UNION ALL
                    
                    SELECT 
                        DATE_FORMAT(ppi.due_date, '%Y-%m-01') as period_date,
                        0 as wallet_income,
                        0 as wallet_expense,
                        0 as credit_card_expense,
                        0 as credit_card_payment,
                        SUM(ppi.amount) as payment_plan_expense
                    FROM payment_plan_items ppi
                    INNER JOIN payment_plans pp ON ppi.payment_plan_id = pp.id
                    WHERE pp.user_id = ?
                    AND pp.status != 'cancelled'
                    AND ppi.due_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                    GROUP BY DATE_FORMAT(ppi.due_date, '%Y-%m')
                ) monthly_data
                GROUP BY period_date
                ORDER BY period_date DESC
                LIMIT 12";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $userId, $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Haftalık trendler
    private function getWeeklyTrends($userId, $startDate, $endDate) {
        $sql = "SELECT 
                    YEARWEEK(period_date, 1) as week_number,
                    DATE(DATE_SUB(period_date, INTERVAL WEEKDAY(period_date) DAY)) as week_start,
                    SUM(wallet_income) as wallet_income,
                    SUM(wallet_expense) as wallet_expense,
                    SUM(credit_card_expense) as credit_card_expense,
                    SUM(credit_card_payment) as credit_card_payment,
                    SUM(payment_plan_expense) as payment_plan_expense
                FROM (
                    SELECT 
                        t.transaction_date as period_date,
                        SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END) as wallet_income,
                        SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END) as wallet_expense,
                        0 as credit_card_expense,
                        0 as credit_card_payment,
                        0 as payment_plan_expense
                    FROM transactions t
                    INNER JOIN wallets w ON t.wallet_id = w.id
                    WHERE w.user_id = ? AND t.transaction_date BETWEEN ? AND ?
                    GROUP BY t.transaction_date
                    
                    UNION ALL
                    
                    SELECT 
                        cct.transaction_date as period_date,
                        0 as wallet_income,
                        0 as wallet_expense,
                        SUM(CASE 
                            WHEN cct.type IN ('purchase', 'fee', 'interest') AND cct.installment_count > 1 AND cct.parent_transaction_id IS NULL
                            THEN cct.amount / cct.installment_count
                            WHEN cct.type IN ('purchase', 'fee', 'interest') AND (cct.installment_count <= 1 OR cct.installment_count IS NULL)
                            THEN cct.amount
                            ELSE 0
                        END) as credit_card_expense,
                        SUM(CASE WHEN cct.type IN ('payment', 'refund') THEN cct.amount ELSE 0 END) as credit_card_payment,
                        0 as payment_plan_expense
                    FROM credit_card_transactions cct
                    INNER JOIN credit_cards cc ON cct.credit_card_id = cc.id
                    WHERE cc.user_id = ? AND cct.transaction_date BETWEEN ? AND ?
                    GROUP BY cct.transaction_date
                    
                    UNION ALL
                    
                    SELECT 
                        ppi.due_date as period_date,
                        0 as wallet_income,
                        0 as wallet_expense,
                        0 as credit_card_expense,
                        0 as credit_card_payment,
                        SUM(ppi.amount) as payment_plan_expense
                    FROM payment_plan_items ppi
                    INNER JOIN payment_plans pp ON ppi.payment_plan_id = pp.id
                    WHERE pp.user_id = ? AND pp.status != 'cancelled'
                    AND ppi.due_date BETWEEN ? AND ?
                    GROUP BY ppi.due_date
                ) daily_data
                GROUP BY YEARWEEK(period_date, 1)
                ORDER BY week_start";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $startDate, $endDate, $userId, $startDate, $endDate, $userId, $startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Günlük trendler
    private function getDailyTrends($userId, $startDate, $endDate) {
        $sql = "SELECT 
                    period_date,
                    SUM(wallet_income) as wallet_income,
                    SUM(wallet_expense) as wallet_expense,
                    SUM(credit_card_expense) as credit_card_expense,
                    SUM(credit_card_payment) as credit_card_payment,
                    SUM(payment_plan_expense) as payment_plan_expense
                FROM (
                    SELECT 
                        t.transaction_date as period_date,
                        SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END) as wallet_income,
                        SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END) as wallet_expense,
                        0 as credit_card_expense,
                        0 as credit_card_payment,
                        0 as payment_plan_expense
                    FROM transactions t
                    INNER JOIN wallets w ON t.wallet_id = w.id
                    WHERE w.user_id = ? AND t.transaction_date BETWEEN ? AND ?
                    GROUP BY t.transaction_date
                    
                    UNION ALL
                    
                    SELECT 
                        cct.transaction_date as period_date,
                        0 as wallet_income,
                        0 as wallet_expense,
                        SUM(CASE 
                            WHEN cct.type IN ('purchase', 'fee', 'interest') AND cct.installment_count > 1 AND cct.parent_transaction_id IS NULL
                            THEN cct.amount / cct.installment_count
                            WHEN cct.type IN ('purchase', 'fee', 'interest') AND (cct.installment_count <= 1 OR cct.installment_count IS NULL)
                            THEN cct.amount
                            ELSE 0
                        END) as credit_card_expense,
                        SUM(CASE WHEN cct.type IN ('payment', 'refund') THEN cct.amount ELSE 0 END) as credit_card_payment,
                        0 as payment_plan_expense
                    FROM credit_card_transactions cct
                    INNER JOIN credit_cards cc ON cct.credit_card_id = cc.id
                    WHERE cc.user_id = ? AND cct.transaction_date BETWEEN ? AND ?
                    GROUP BY cct.transaction_date
                    
                    UNION ALL
                    
                    SELECT 
                        ppi.due_date as period_date,
                        0 as wallet_income,
                        0 as wallet_expense,
                        0 as credit_card_expense,
                        0 as credit_card_payment,
                        SUM(ppi.amount) as payment_plan_expense
                    FROM payment_plan_items ppi
                    INNER JOIN payment_plans pp ON ppi.payment_plan_id = pp.id
                    WHERE pp.user_id = ? AND pp.status != 'cancelled'
                    AND ppi.due_date BETWEEN ? AND ?
                    GROUP BY ppi.due_date
                ) daily_data
                GROUP BY period_date
                ORDER BY period_date";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $startDate, $endDate, $userId, $startDate, $endDate, $userId, $startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Yıllık trendler
    private function getYearlyTrends($userId) {
        $sql = "SELECT 
                    YEAR(period_date) as year,
                    SUM(wallet_income) as wallet_income,
                    SUM(wallet_expense) as wallet_expense,
                    SUM(credit_card_expense) as credit_card_expense,
                    SUM(credit_card_payment) as credit_card_payment,
                    SUM(payment_plan_expense) as payment_plan_expense
                FROM (
                    SELECT 
                        t.transaction_date as period_date,
                        SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END) as wallet_income,
                        SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END) as wallet_expense,
                        0 as credit_card_expense,
                        0 as credit_card_payment,
                        0 as payment_plan_expense
                    FROM transactions t
                    INNER JOIN wallets w ON t.wallet_id = w.id
                    WHERE w.user_id = ?
                    GROUP BY t.transaction_date
                    
                    UNION ALL
                    
                    SELECT 
                        cct.transaction_date as period_date,
                        0 as wallet_income,
                        0 as wallet_expense,
                        SUM(CASE 
                            WHEN cct.type IN ('purchase', 'fee', 'interest') AND cct.installment_count > 1 AND cct.parent_transaction_id IS NULL
                            THEN cct.amount / cct.installment_count
                            WHEN cct.type IN ('purchase', 'fee', 'interest') AND (cct.installment_count <= 1 OR cct.installment_count IS NULL)
                            THEN cct.amount
                            ELSE 0
                        END) as credit_card_expense,
                        SUM(CASE WHEN cct.type IN ('payment', 'refund') THEN cct.amount ELSE 0 END) as credit_card_payment,
                        0 as payment_plan_expense
                    FROM credit_card_transactions cct
                    INNER JOIN credit_cards cc ON cct.credit_card_id = cc.id
                    WHERE cc.user_id = ?
                    GROUP BY cct.transaction_date
                    
                    UNION ALL
                    
                    SELECT 
                        ppi.due_date as period_date,
                        0 as wallet_income,
                        0 as wallet_expense,
                        0 as credit_card_expense,
                        0 as credit_card_payment,
                        SUM(ppi.amount) as payment_plan_expense
                    FROM payment_plan_items ppi
                    INNER JOIN payment_plans pp ON ppi.payment_plan_id = pp.id
                    WHERE pp.user_id = ? AND pp.status != 'cancelled'
                    GROUP BY ppi.due_date
                ) yearly_data
                GROUP BY YEAR(period_date)
                ORDER BY year DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $userId, $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Kategori grafik verisi
    private function getCategoryChartData($userId, $startDate, $endDate) {
        $categoryStats = $this->getCategoryStats($userId, $startDate, $endDate);
        
        $expenseCategories = array_filter($categoryStats, function($cat) {
            return $cat['category_type'] === 'expense';
        });
        
        $incomeCategories = array_filter($categoryStats, function($cat) {
            return $cat['category_type'] === 'income';
        });
        
        return [
            'expense' => array_slice($expenseCategories, 0, 10), // Top 10 gider kategorisi
            'income' => array_slice($incomeCategories, 0, 10)    // Top 10 gelir kategorisi
        ];
    }
    
    // Gelir-Gider grafik verisi
    private function getIncomeExpenseChartData($userId, $period) {
        switch ($period) {
            case 'daily':
                return $this->getDailyTrends($userId, date('Y-m-01'), date('Y-m-t'));
            case 'weekly':
                return $this->getWeeklyTrends($userId, date('Y-m-01'), date('Y-m-t'));
            case 'yearly':
                return $this->getYearlyTrends($userId);
            case 'monthly':
            default:
                return $this->getMonthlyTrends($userId);
        }
    }
    
    // Cüzdan dağılımı
    private function getWalletDistribution($userId) {
        $sql = "SELECT 
                    name as wallet_name,
                    balance,
                    currency,
                    CASE 
                        WHEN balance > 0 THEN 'positive'
                        WHEN balance < 0 THEN 'negative'
                        ELSE 'zero'
                    END as balance_status
                FROM wallets 
                WHERE user_id = ?
                ORDER BY balance DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Kredi kartı kullanım grafiği
    private function getCreditCardUsageChart($userId, $startDate, $endDate) {
        $sql = "SELECT 
                    cc.name as card_name,
                    cc.credit_limit,
                    COALESCE(SUM(
                        CASE 
                            WHEN cct.type IN ('purchase', 'fee', 'interest') THEN cct.amount
                            WHEN cct.type IN ('payment', 'refund') THEN -cct.amount
                            ELSE 0
                        END
                    ), 0) as current_usage,
                    (COALESCE(SUM(
                        CASE 
                            WHEN cct.type IN ('purchase', 'fee', 'interest') THEN cct.amount
                            WHEN cct.type IN ('payment', 'refund') THEN -cct.amount
                            ELSE 0
                        END
                    ), 0) / cc.credit_limit * 100) as usage_percentage
                FROM credit_cards cc
                LEFT JOIN credit_card_transactions cct ON cc.id = cct.credit_card_id
                WHERE cc.user_id = ? AND cc.is_active = 1
                GROUP BY cc.id, cc.name, cc.credit_limit
                ORDER BY usage_percentage DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Taksit grafik verisi
    private function getInstallmentChartData($userId) {
        $sql = "SELECT 
                    cc.name as card_name,
                    cct.description,
                    cct.amount as total_amount,
                    cct.installment_count,
                    (cct.amount / cct.installment_count) as monthly_amount,
                    cct.transaction_date,
                    DATE_ADD(cct.transaction_date, INTERVAL (cct.installment_count - 1) MONTH) as end_date,
                    COALESCE((
                        SELECT COUNT(*) 
                        FROM credit_card_transactions child 
                        WHERE child.parent_transaction_id = cct.id 
                        AND child.is_paid = 1
                    ), 0) as paid_installments
                FROM credit_card_transactions cct
                INNER JOIN credit_cards cc ON cct.credit_card_id = cc.id
                WHERE cc.user_id = ? 
                AND cct.parent_transaction_id IS NULL 
                AND cct.installment_count > 1
                AND cct.type = 'purchase'
                ORDER BY cct.transaction_date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Özet veriler
    private function getSummaryData($userId, $startDate, $endDate) {
        // Tüm gelir ve giderleri hesapla
        $walletIncome = array_sum(array_column($this->getWalletIncome($userId, $startDate, $endDate), 'amount'));
        $walletExpense = array_sum(array_column($this->getWalletExpense($userId, $startDate, $endDate), 'amount'));
        $creditCardExpense = array_sum(array_column($this->getCreditCardExpenses($userId, $startDate, $endDate), 'amount'));
        $creditCardPayment = array_sum(array_column($this->getCreditCardPayments($userId, $startDate, $endDate), 'amount'));
        $paymentPlanExpense = array_sum(array_column($this->getPaymentPlanExpenses($userId, $startDate, $endDate), 'amount'));
        
        $totalIncome = $walletIncome + $creditCardPayment; // Kredi kartı ödemeleri gelir sayılmaz, sadece transfer
        $totalExpense = $walletExpense + $creditCardExpense + $paymentPlanExpense;
        $netAmount = $totalIncome - $totalExpense;
        $savingsRate = $totalIncome > 0 ? ($netAmount / $totalIncome) * 100 : 0;
        
        return [
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'net_amount' => $netAmount,
            'savings_rate' => $savingsRate,
            'wallet_income' => $walletIncome,
            'wallet_expense' => $walletExpense,
            'credit_card_expense' => $creditCardExpense,
            'credit_card_payment' => $creditCardPayment,
            'payment_plan_expense' => $paymentPlanExpense,
            'expense_breakdown' => [
                'wallet' => $walletExpense,
                'credit_card' => $creditCardExpense,
                'payment_plan' => $paymentPlanExpense
            ]
        ];
    }
    
    // En çok harcama yapılan kategoriler
    private function getTopCategories($userId, $startDate, $endDate, $limit = 10) {
        $categoryStats = $this->getCategoryStats($userId, $startDate, $endDate);
        $expenseCategories = array_filter($categoryStats, function($cat) {
            return $cat['category_type'] === 'expense';
        });
        
        usort($expenseCategories, function($a, $b) {
            return $b['total_amount'] <=> $a['total_amount'];
        });
        
        return array_slice($expenseCategories, 0, $limit);
    }
    
    // En çok harcama yapılan mağazalar
    private function getTopMerchants($userId, $startDate, $endDate, $limit = 10) {
        $sql = "SELECT 
                    merchant_name,
                    COUNT(*) as transaction_count,
                    SUM(CASE 
                        WHEN installment_count > 1 AND parent_transaction_id IS NULL 
                        THEN amount / installment_count
                        ELSE amount
                    END) as total_amount,
                    AVG(CASE 
                        WHEN installment_count > 1 AND parent_transaction_id IS NULL 
                        THEN amount / installment_count
                        ELSE amount
                    END) as average_amount
                FROM credit_card_transactions cct
                INNER JOIN credit_cards cc ON cct.credit_card_id = cc.id
                WHERE cc.user_id = ? 
                AND cct.type IN ('purchase', 'fee', 'interest')
                AND cct.parent_transaction_id IS NULL
                AND cct.merchant_name IS NOT NULL 
                AND cct.merchant_name != ''
                AND cct.transaction_date BETWEEN ? AND ?
                GROUP BY merchant_name
                ORDER BY total_amount DESC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $startDate, $endDate, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Bütçe karşılaştırması (eğer budget sistemi varsa)
    private function getBudgetComparison($userId, $startDate, $endDate) {
        // Bu fonksiyon budget sistemi implement edildiğinde doldurulacak
        return [];
    }
    
    // Export fonksiyonları (mevcut koddan devam)
    public function export() {
        try {
            if (empty($_GET['type'])) {
                throw new Exception('Rapor tipi belirtilmedi.');
            }
            
            $userId = $_SESSION['user_id'];
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-t');
            $format = $_GET['format'] ?? 'csv';
            
            $data = [];
            $filename = '';
            
            switch ($_GET['type']) {
                case 'summary':
                    $data = $this->getSummaryData($userId, $startDate, $endDate);
                    $filename = 'ozet_raporu_' . date('Y-m-d');
                    break;
                case 'category':
                    $data = $this->getCategoryStats($userId, $startDate, $endDate);
                    $filename = 'kategori_raporu_' . date('Y-m-d');
                    break;
                case 'wallet':
                    $data = $this->getWalletStats($userId, $startDate, $endDate);
                    $filename = 'cuzdan_raporu_' . date('Y-m-d');
                    break;
                case 'credit_card':
                    $data = $this->getCreditCardStats($userId, $startDate, $endDate);
                    $filename = 'kredi_karti_raporu_' . date('Y-m-d');
                    break;
                case 'payment_plan':
                    $data = $this->getPaymentPlanStats($userId, $startDate, $endDate);
                    $filename = 'odeme_plani_raporu_' . date('Y-m-d');
                    break;
                case 'all':
                default:
                    $data = $this->getAllTransactionsReport($userId, $startDate, $endDate);
                    $filename = 'genel_rapor_' . date('Y-m-d');
                    break;
            }
            
            if (empty($data)) {
                throw new Exception('Rapor verisi bulunamadı.');
            }
            
            if ($format === 'pdf') {
                $this->exportToPDF($data, $filename);
            } elseif ($format === 'excel') {
                $this->exportToExcel($data, $filename);
            } else {
                $this->exportToCSV($data, $filename);
            }
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    private function getAllTransactionsReport($userId, $startDate, $endDate) {
        $sql = "SELECT 
                    'Cüzdan' as kaynak_tipi,
                    w.name as kaynak_adi,
                    t.transaction_date as tarih,
                    t.type as islem_tipi,
                    c.name as kategori,
                    t.amount as tutar,
                    t.description as aciklama
                FROM transactions t
                LEFT JOIN wallets w ON t.wallet_id = w.id
                LEFT JOIN categories c ON t.category_id = c.id
                WHERE t.user_id = ? AND t.transaction_date BETWEEN ? AND ?
                
                UNION ALL
                
                SELECT 
                    'Kredi Kartı' as kaynak_tipi,
                    cc.name as kaynak_adi,
                    cct.transaction_date as tarih,
                    cct.type as islem_tipi,
                    cat.name as kategori,
                    CASE 
                        WHEN cct.installment_count > 1 AND cct.parent_transaction_id IS NULL 
                        THEN cct.amount / cct.installment_count
                        ELSE cct.amount
                    END as tutar,
                    CASE 
                        WHEN cct.installment_count > 1 
                        THEN CONCAT(cct.description, ' (', cct.installment_count, ' Taksit - Aylık)')
                        ELSE cct.description
                    END as aciklama
                FROM credit_card_transactions cct
                LEFT JOIN credit_cards cc ON cct.credit_card_id = cc.id
                LEFT JOIN categories cat ON cct.category_id = cat.id
                WHERE cc.user_id = ? AND cct.transaction_date BETWEEN ? AND ?
                AND cct.parent_transaction_id IS NULL
                
                UNION ALL
                
                SELECT 
                    'Ödeme Planı' as kaynak_tipi,
                    pp.title as kaynak_adi,
                    ppi.due_date as tarih,
                    'expense' as islem_tipi,
                    cat.name as kategori,
                    ppi.amount as tutar,
                    CONCAT(pp.title, ' - ', ppi.title) as aciklama
                FROM payment_plan_items ppi
                INNER JOIN payment_plans pp ON ppi.payment_plan_id = pp.id
                LEFT JOIN categories cat ON pp.category_id = cat.id
                WHERE pp.user_id = ? AND ppi.due_date BETWEEN ? AND ?
                AND pp.status != 'cancelled'
                
                ORDER BY tarih DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $startDate, $endDate, $userId, $startDate, $endDate, $userId, $startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function exportToCSV($data, $filename) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        // UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Başlık satırı
        if (!empty($data)) {
            fputcsv($output, array_keys(reset($data)), ';');
            
            // Veriler
            foreach ($data as $row) {
                fputcsv($output, $row, ';');
            }
        }
        
        fclose($output);
        exit;
    }
    
    private function exportToPDF($data, $filename) {
        // Basic HTML to PDF conversion
        header('Content-Type: text/html; charset=utf-8');
        
        echo '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>' . $filename . '</title>
            <style>
                body { font-family: Arial, sans-serif; font-size: 12px; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; font-weight: bold; }
                .header { text-align: center; margin-bottom: 30px; }
                .summary { margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Finansal Rapor</h1>
                <p>Oluşturulma Tarihi: ' . date('d.m.Y H:i') . '</p>
            </div>
            <table>
                <thead>
                    <tr>';
        
        if (!empty($data)) {
            foreach (array_keys(reset($data)) as $header) {
                echo '<th>' . htmlspecialchars($header) . '</th>';
            }
            echo '</tr></thead><tbody>';
            
            foreach ($data as $row) {
                echo '<tr>';
                foreach ($row as $cell) {
                    echo '<td>' . htmlspecialchars($cell) . '</td>';
                }
                echo '</tr>';
            }
        }
        
        echo '</tbody></table></body></html>';
        exit;
    }
    
    private function exportToExcel($data, $filename) {
        // Simple Excel export using HTML table
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        echo '<table border="1">';
        echo '<tr>';
        
        if (!empty($data)) {
            foreach (array_keys(reset($data)) as $header) {
                echo '<th>' . htmlspecialchars($header) . '</th>';
            }
            echo '</tr>';
            
            foreach ($data as $row) {
                echo '<tr>';
                foreach ($row as $cell) {
                    echo '<td>' . htmlspecialchars($cell) . '</td>';
                }
                echo '</tr>';
            }
        }
        
        echo '</table>';
        exit;
    }
}

// URL tabanlı istekleri işle
if (isset($_GET['action'])) {
    $controller = new ReportController();
    
    switch ($_GET['action']) {
        case 'export':
            $controller->export();
            break;
        default:
            header('HTTP/1.0 404 Not Found');
            echo json_encode(['success' => false, 'message' => 'Action not found']);
            break;
    }
}
?> 