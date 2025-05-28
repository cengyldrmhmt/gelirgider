<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../models/Wallet.php';
require_once __DIR__ . '/../models/Budget.php';
require_once __DIR__ . '/../models/FinancialGoal.php';

class DashboardController extends Controller {
    private $transaction;
    private $category;
    private $wallet;
    private $budget;
    private $financialGoal;
    
    public function __construct() {
        // Session kontrolü
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            header('Location: /gelirgider/app/views/auth/login.php');
            exit;
        }
        
        $this->transaction = new Transaction();
        $this->category = new Category();
        $this->wallet = new Wallet();
        $this->budget = new Budget();
        $this->financialGoal = new FinancialGoal();
    }
    
    public function index() {
        $userId = $_SESSION['user_id'];
        
        try {
            require_once __DIR__ . '/../core/Database.php';
            $db = Database::getInstance()->getConnection();
            
            // Bu ayın başlangıç ve bitiş tarihleri
            $currentMonth = date('Y-m-01');
            $nextMonth = date('Y-m-01', strtotime('+1 month'));
            
            // Bu ay toplam gelir
            $stmt = $db->prepare("
                SELECT COALESCE(SUM(amount), 0) as total
                FROM (
                    SELECT amount FROM transactions 
                    WHERE user_id = ? AND type = 'income' 
                    AND transaction_date >= ? AND transaction_date < ?
                    
                    UNION ALL
                    
                    SELECT amount FROM credit_card_transactions 
                    WHERE user_id = ? AND type IN ('payment', 'refund')
                    AND transaction_date >= ? AND transaction_date < ?
                ) as all_income
            ");
            $stmt->execute([$userId, $currentMonth, $nextMonth, $userId, $currentMonth, $nextMonth]);
            $monthlyIncome = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            
            // Bu ay toplam gider
            $stmt = $db->prepare("
                SELECT COALESCE(SUM(amount), 0) as total
                FROM (
                    SELECT amount FROM transactions 
                    WHERE user_id = ? AND type = 'expense' 
                    AND transaction_date >= ? AND transaction_date < ?
                    
                    UNION ALL
                    
                    SELECT amount FROM credit_card_transactions 
                    WHERE user_id = ? AND type IN ('purchase', 'fee', 'interest', 'installment')
                    AND transaction_date >= ? AND transaction_date < ?
                ) as all_expense
            ");
            $stmt->execute([$userId, $currentMonth, $nextMonth, $userId, $currentMonth, $nextMonth]);
            $monthlyExpense = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            
            // Toplam cüzdan bakiyesi
            $stmt = $db->prepare("SELECT COALESCE(SUM(balance), 0) as total FROM wallets WHERE user_id = ?");
            $stmt->execute([$userId]);
            $walletBalance = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            
            // Kredi kartı borçları (kullanılan limit)
            $stmt = $db->prepare("SELECT COALESCE(SUM(used_limit), 0) as total FROM credit_cards WHERE user_id = ?");
            $stmt->execute([$userId]);
            $creditCardBalance = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            
            // Net bakiye (cüzdan bakiyesi - kredi kartı borçları)
            $netBalance = $walletBalance - abs($creditCardBalance);
            
            // Aktif cüzdan sayısı
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM wallets WHERE user_id = ?");
            $stmt->execute([$userId]);
            $activeWallets = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            
            // Son işlemler (son 10 işlem)
            $stmt = $db->prepare("
                SELECT 'wallet' as source_type, transaction_date, description, amount, t.type, c.name as category_name
                FROM transactions t
                LEFT JOIN categories c ON t.category_id = c.id
                WHERE t.user_id = ?
                
                UNION ALL
                
                SELECT 'credit_card' as source_type, transaction_date, description, amount, 
                       CASE 
                           WHEN cct.type IN ('payment', 'refund') THEN 'income'
                           WHEN cct.type IN ('purchase', 'fee', 'interest', 'installment') THEN 'expense'
                           ELSE cct.type
                       END as type, 
                       c.name as category_name
                FROM credit_card_transactions cct
                LEFT JOIN categories c ON cct.category_id = c.id
                WHERE cct.user_id = ?
                
                ORDER BY transaction_date DESC
                LIMIT 10
            ");
            $stmt->execute([$userId, $userId]);
            $recentTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Kategori bazlı harcamalar (bu ay)
            $stmt = $db->prepare("
                SELECT c.name as category_name, c.color, COALESCE(SUM(expense_total), 0) as total_expense
                FROM categories c
                LEFT JOIN (
                    SELECT category_id, SUM(amount) as expense_total
                    FROM (
                        SELECT category_id, amount FROM transactions 
                        WHERE user_id = ? AND type = 'expense' 
                        AND transaction_date >= ? AND transaction_date < ?
                        
                        UNION ALL
                        
                        SELECT category_id, amount FROM credit_card_transactions 
                        WHERE user_id = ? AND type IN ('purchase', 'fee', 'interest', 'installment')
                        AND transaction_date >= ? AND transaction_date < ?
                    ) as all_expenses
                    GROUP BY category_id
                ) as expenses ON c.id = expenses.category_id
                WHERE c.user_id = ? AND c.type = 'expense'
                ORDER BY total_expense DESC
                LIMIT 10
            ");
            $stmt->execute([$userId, $currentMonth, $nextMonth, $userId, $currentMonth, $nextMonth, $userId]);
            $categoryExpenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Cüzdan detayları
            $stmt = $db->prepare("SELECT name, currency, balance FROM wallets WHERE user_id = ? ORDER BY balance DESC");
            $stmt->execute([$userId]);
            $walletDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Kredi kartı detayları
            $stmt = $db->prepare("SELECT name, credit_limit, used_limit FROM credit_cards WHERE user_id = ? ORDER BY used_limit DESC");
            $stmt->execute([$userId]);
            $creditCardDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Geçen ay ile karşılaştırma
            $lastMonth = date('Y-m-01', strtotime('-1 month'));
            $currentMonthStart = date('Y-m-01');
            
            // Geçen ay gelir
            $stmt = $db->prepare("
                SELECT COALESCE(SUM(amount), 0) as total
                FROM (
                    SELECT amount FROM transactions 
                    WHERE user_id = ? AND type = 'income' 
                    AND transaction_date >= ? AND transaction_date < ?
                    
                    UNION ALL
                    
                    SELECT amount FROM credit_card_transactions 
                    WHERE user_id = ? AND type IN ('payment', 'refund')
                    AND transaction_date >= ? AND transaction_date < ?
                ) as all_income
            ");
            $stmt->execute([$userId, $lastMonth, $currentMonthStart, $userId, $lastMonth, $currentMonthStart]);
            $lastMonthIncome = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            
            // Geçen ay gider
            $stmt = $db->prepare("
                SELECT COALESCE(SUM(amount), 0) as total
                FROM (
                    SELECT amount FROM transactions 
                    WHERE user_id = ? AND type = 'expense' 
                    AND transaction_date >= ? AND transaction_date < ?
                    
                    UNION ALL
                    
                    SELECT amount FROM credit_card_transactions 
                    WHERE user_id = ? AND type IN ('purchase', 'fee', 'interest', 'installment')
                    AND transaction_date >= ? AND transaction_date < ?
                ) as all_expense
            ");
            $stmt->execute([$userId, $lastMonth, $currentMonthStart, $userId, $lastMonth, $currentMonthStart]);
            $lastMonthExpense = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            
            // Değişim yüzdeleri
            $incomeChange = $lastMonthIncome > 0 ? (($monthlyIncome - $lastMonthIncome) / $lastMonthIncome) * 100 : 0;
            $expenseChange = $lastMonthExpense > 0 ? (($monthlyExpense - $lastMonthExpense) / $lastMonthExpense) * 100 : 0;
            
            $data = [
                'title' => 'Dashboard',
                'total_income' => $monthlyIncome,
                'total_expense' => $monthlyExpense,
                'net_balance' => $netBalance,
                'wallet_balance' => $walletBalance,
                'credit_card_balance' => $creditCardBalance,
                'active_wallets' => $activeWallets,
                'recent_transactions' => $recentTransactions,
                'category_expenses' => $categoryExpenses,
                'wallet_details' => $walletDetails,
                'credit_card_details' => $creditCardDetails,
                'income_change' => $incomeChange,
                'expense_change' => $expenseChange,
                'savings_rate' => $monthlyIncome > 0 ? (($monthlyIncome - $monthlyExpense) / $monthlyIncome) * 100 : 0
            ];
            
        } catch (Exception $e) {
            error_log("Dashboard error: " . $e->getMessage());
            // Hata durumunda varsayılan değerler
            $data = [
                'title' => 'Dashboard',
                'total_income' => 0,
                'total_expense' => 0,
                'net_balance' => 0,
                'wallet_balance' => 0,
                'credit_card_balance' => 0,
                'active_wallets' => 0,
                'recent_transactions' => [],
                'category_expenses' => [],
                'wallet_details' => [],
                'credit_card_details' => [],
                'income_change' => 0,
                'expense_change' => 0,
                'savings_rate' => 0
            ];
        }
        
        return $data;
    }
    
    private function getWalletDetails($userId) {
        try {
            $wallets = $this->wallet->getAll($userId);
            $details = [];
            
            foreach ($wallets as $wallet) {
                $details[] = [
                    'name' => $wallet['name'],
                    'currency' => $wallet['currency'],
                    'balance' => $wallet['real_balance'],
                    'balance_in_try' => $this->convertToTRY($wallet['real_balance'], $wallet['currency'])
                ];
            }
            
            return $details;
        } catch (Exception $e) {
            return [];
        }
    }
    
    private function convertToTRY($amount, $currency) {
        switch ($currency) {
            case 'USD':
                return $amount * 34.25; // USD/TRY kuru
            case 'EUR':
                return $amount * 37.15; // EUR/TRY kuru
            case 'GBP':
                return $amount * 43.85; // GBP/TRY kuru
            case 'TRY':
            default:
                return $amount;
        }
    }
    
    public function getSummary() {
        try {
            $userId = $_SESSION['user_id'];
            require_once __DIR__ . '/../core/Database.php';
            $db = Database::getInstance()->getConnection();
            
            // Bu ayın başlangıç ve bitiş tarihleri
            $currentMonth = date('Y-m-01');
            $nextMonth = date('Y-m-01', strtotime('+1 month'));
            
            // Bu ay toplam gelir
            $stmt = $db->prepare("
                SELECT COALESCE(SUM(amount), 0) as total
                FROM (
                    SELECT amount FROM transactions 
                    WHERE user_id = ? AND type = 'income' 
                    AND transaction_date >= ? AND transaction_date < ?
                    
                    UNION ALL
                    
                    SELECT amount FROM credit_card_transactions 
                    WHERE user_id = ? AND type IN ('payment', 'refund')
                    AND transaction_date >= ? AND transaction_date < ?
                ) as all_income
            ");
            $stmt->execute([$userId, $currentMonth, $nextMonth, $userId, $currentMonth, $nextMonth]);
            $totalIncome = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            
            // Bu ay toplam gider
            $stmt = $db->prepare("
                SELECT COALESCE(SUM(amount), 0) as total
                FROM (
                    SELECT amount FROM transactions 
                    WHERE user_id = ? AND type = 'expense' 
                    AND transaction_date >= ? AND transaction_date < ?
                    
                    UNION ALL
                    
                    SELECT amount FROM credit_card_transactions 
                    WHERE user_id = ? AND type IN ('purchase', 'fee', 'interest', 'installment')
                    AND transaction_date >= ? AND transaction_date < ?
                ) as all_expense
            ");
            $stmt->execute([$userId, $currentMonth, $nextMonth, $userId, $currentMonth, $nextMonth]);
            $totalExpense = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            
            // Net bakiye
            $stmt = $db->prepare("SELECT COALESCE(SUM(balance), 0) as wallet_total FROM wallets WHERE user_id = ?");
            $stmt->execute([$userId]);
            $walletBalance = $stmt->fetch(PDO::FETCH_ASSOC)['wallet_total'] ?? 0;
            
            $stmt = $db->prepare("SELECT COALESCE(SUM(used_limit), 0) as cc_total FROM credit_cards WHERE user_id = ?");
            $stmt->execute([$userId]);
            $creditCardBalance = $stmt->fetch(PDO::FETCH_ASSOC)['cc_total'] ?? 0;
            
            $netBalance = $walletBalance - abs($creditCardBalance);
            
            $data = [
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'net_balance' => $netBalance
            ];
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => $data]);
            exit;
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    public function getUserStats($userId) {
        try {
            require_once __DIR__ . '/../core/Database.php';
            $db = Database::getInstance()->getConnection();
            
            // Toplam işlem sayısı
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM transactions WHERE user_id = ?");
            $stmt->execute([$userId]);
            $totalTransactions = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // Toplam gelir
            $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE user_id = ? AND type = 'income'");
            $stmt->execute([$userId]);
            $totalIncome = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Toplam gider
            $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE user_id = ? AND type = 'expense'");
            $stmt->execute([$userId]);
            $totalExpense = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Cüzdan sayısı
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM wallets WHERE user_id = ?");
            $stmt->execute([$userId]);
            $walletCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // Üyelik günü
            $stmt = $db->prepare("SELECT DATEDIFF(NOW(), created_at) as days FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $daysSinceRegistration = $stmt->fetch(PDO::FETCH_ASSOC)['days'];
            
            // Aylık ortalama (son 12 ay)
            $stmt = $db->prepare("
                SELECT COALESCE(AVG(monthly_total), 0) as avg_monthly 
                FROM (
                    SELECT YEAR(transaction_date) as year, MONTH(transaction_date) as month, 
                           SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END) as monthly_total
                    FROM transactions 
                    WHERE user_id = ? AND transaction_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                    GROUP BY YEAR(transaction_date), MONTH(transaction_date)
                ) as monthly_data
            ");
            $stmt->execute([$userId]);
            $monthlyAverage = $stmt->fetch(PDO::FETCH_ASSOC)['avg_monthly'];
            
            // En çok kullanılan kategori
            $stmt = $db->prepare("
                SELECT c.name as category_name, COUNT(*) as usage_count
                FROM transactions t
                LEFT JOIN categories c ON t.category_id = c.id
                WHERE t.user_id = ? AND c.name IS NOT NULL
                GROUP BY c.id, c.name
                ORDER BY usage_count DESC
                LIMIT 1
            ");
            $stmt->execute([$userId]);
            $mostUsedCategory = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Bu ayki işlem sayısı
            $stmt = $db->prepare("
                SELECT COUNT(*) as count 
                FROM transactions 
                WHERE user_id = ? AND YEAR(transaction_date) = YEAR(NOW()) AND MONTH(transaction_date) = MONTH(NOW())
            ");
            $stmt->execute([$userId]);
            $thisMonthTransactions = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // En yüksek günlük harcama
            $stmt = $db->prepare("
                SELECT MAX(daily_expense) as max_expense
                FROM (
                    SELECT DATE(transaction_date) as transaction_day, SUM(amount) as daily_expense
                    FROM transactions 
                    WHERE user_id = ? AND type = 'expense'
                    GROUP BY DATE(transaction_date)
                ) as daily_data
            ");
            $stmt->execute([$userId]);
            $maxDailyExpense = $stmt->fetch(PDO::FETCH_ASSOC)['max_expense'] ?? 0;
            
            return [
                'total_transactions' => $totalTransactions,
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'net_balance' => $totalIncome - $totalExpense,
                'wallet_count' => $walletCount,
                'days_since_registration' => $daysSinceRegistration,
                'monthly_average' => $monthlyAverage,
                'most_used_category' => $mostUsedCategory['category_name'] ?? 'Belirlenmemiş',
                'most_used_category_count' => $mostUsedCategory['usage_count'] ?? 0,
                'this_month_transactions' => $thisMonthTransactions,
                'max_daily_expense' => $maxDailyExpense
            ];
            
        } catch (Exception $e) {
            error_log("getUserStats error: " . $e->getMessage());
            return [
                'total_transactions' => 0,
                'total_income' => 0,
                'total_expense' => 0,
                'net_balance' => 0,
                'wallet_count' => 0,
                'days_since_registration' => 0,
                'monthly_average' => 0,
                'most_used_category' => 'Belirlenmemiş',
                'most_used_category_count' => 0,
                'this_month_transactions' => 0,
                'max_daily_expense' => 0
            ];
        }
    }
}

// URL tabanlı istekleri işle
if (isset($_GET['action'])) {
    session_start();
    
    // Session kontrolü
    if (!isset($_SESSION['user_id'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => 'Oturum açmanız gerekiyor.',
            'redirect' => '/gelirgider/app/views/auth/login.php'
        ]);
        exit;
    }
    
    $controller = new DashboardController();
    $action = $_GET['action'];
    
    switch ($action) {
        case 'getSummary':
            $controller->getSummary();
            break;
        case 'getUserStats':
            $userId = $_SESSION['user_id'];
            $stats = $controller->getUserStats($userId);
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => $stats]);
            break;
        default:
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Geçersiz işlem']);
            break;
    }
    exit;
}
?> 