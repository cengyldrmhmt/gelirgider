<?php
// Check if session is already active before starting
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/Analytics.php';
require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../models/Budget.php';
require_once __DIR__ . '/../models/CreditCard.php';
require_once __DIR__ . '/../core/Database.php';

class AnalyticsController extends Controller {
    private $analyticsModel;
    private $transactionModel;
    private $budgetModel;
    private $creditCardModel;
    protected $db;
    
    public function __construct() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: /gelirgider/app/views/auth/login.php');
            exit;
        }
        
        $this->analyticsModel = new Analytics();
        $this->transactionModel = new Transaction();
        $this->budgetModel = new Budget();
        $this->creditCardModel = new CreditCard();
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function index() {
        $userId = $_SESSION['user_id'];
        $startDate = $_GET['start_date'] ?? date('Y-m-01'); // Ayın başı
        $endDate = $_GET['end_date'] ?? date('Y-m-t'); // Ayın sonu
        
        // Temel analitik veriler
        $data = [
            'dailyStats' => $this->analyticsModel->getDailyStats($userId, $startDate, $endDate),
            'categoryDistribution' => $this->analyticsModel->getCategoryDistribution($userId, $startDate, $endDate),
            'walletDistribution' => $this->analyticsModel->getWalletDistribution($userId, $startDate, $endDate),
            'trendAnalysis' => $this->analyticsModel->getTrendAnalysis($userId),
            'startDate' => $startDate,
            'endDate' => $endDate
        ];
        
        // AI destekli analizler
        $data['ai_insights'] = $this->generateAIInsights($userId);
        $data['smart_alerts'] = $this->generateSmartAlerts($userId);
        $data['spending_patterns'] = $this->analyzeSpendingPatterns($userId);
        $data['budget_analysis'] = $this->analyzeBudgetPerformance($userId);
        $data['predictions'] = $this->generatePredictions($userId);
        $data['recommendations'] = $this->generateRecommendations($userId);
        
        return $data;
    }
    
    private function generateAIInsights($userId) {
        $insights = [];
        
        // Son 3 ayın verilerini al
        $currentMonth = date('Y-m-01');
        $lastMonth = date('Y-m-01', strtotime('-1 month'));
        $twoMonthsAgo = date('Y-m-01', strtotime('-2 months'));
        
        $currentData = $this->getMonthlyData($userId, $currentMonth, date('Y-m-t'));
        $lastMonthData = $this->getMonthlyData($userId, $lastMonth, date('Y-m-t', strtotime($lastMonth)));
        $twoMonthsData = $this->getMonthlyData($userId, $twoMonthsAgo, date('Y-m-t', strtotime($twoMonthsAgo)));
        
        // Harcama trendi analizi
        if ($lastMonthData['total_expense'] > 0) {
            $expenseChange = (($currentData['total_expense'] - $lastMonthData['total_expense']) / $lastMonthData['total_expense']) * 100;
            
            if ($expenseChange > 20) {
                $insights[] = [
                    'type' => 'warning',
                    'icon' => 'fas fa-chart-line',
                    'title' => 'Harcama Artışı Tespit Edildi',
                    'message' => "Bu ay harcamalarınız geçen aya göre %" . number_format($expenseChange, 1) . " arttı. Bütçe kontrolü yapmanızı öneririz.",
                    'confidence' => 85,
                    'action' => 'Detaylı harcama analizi için kategorileri inceleyin.'
                ];
            } elseif ($expenseChange < -10) {
                $insights[] = [
                    'type' => 'success',
                    'icon' => 'fas fa-thumbs-up',
                    'title' => 'Harika Tasarruf!',
                    'message' => "Bu ay harcamalarınızı %" . number_format(abs($expenseChange), 1) . " azalttınız. Bu disiplinli yaklaşımınızı sürdürün!",
                    'confidence' => 92,
                    'action' => 'Bu tasarruf oranını koruyarak yıllık hedeflerinize ulaşabilirsiniz.'
                ];
            }
        }
        
        // Gelir analizi
        if ($lastMonthData['total_income'] > 0) {
            $incomeChange = (($currentData['total_income'] - $lastMonthData['total_income']) / $lastMonthData['total_income']) * 100;
            
            if ($incomeChange > 10) {
                $insights[] = [
                    'type' => 'success',
                    'icon' => 'fas fa-arrow-up',
                    'title' => 'Gelir Artışı',
                    'message' => "Geliriniz geçen aya göre %" . number_format($incomeChange, 1) . " arttı. Bu fırsatı değerlendirerek tasarruf yapabilirsiniz.",
                    'confidence' => 88,
                    'action' => 'Ek geliri acil durum fonu veya yatırım için değerlendirin.'
                ];
            }
        }
        
        // Tasarruf oranı analizi
        $savingsRate = $currentData['total_income'] > 0 ? 
            (($currentData['total_income'] - $currentData['total_expense']) / $currentData['total_income']) * 100 : 0;
        
        if ($savingsRate < 10) {
            $insights[] = [
                'type' => 'warning',
                'icon' => 'fas fa-piggy-bank',
                'title' => 'Düşük Tasarruf Oranı',
                'message' => "Tasarruf oranınız %" . number_format($savingsRate, 1) . ". Finansal güvenlik için en az %20 tasarruf hedefleyin.",
                'confidence' => 90,
                'action' => 'Gereksiz harcamaları tespit etmek için kategori analizi yapın.'
            ];
        } elseif ($savingsRate > 30) {
            $insights[] = [
                'type' => 'info',
                'icon' => 'fas fa-star',
                'title' => 'Mükemmel Tasarruf!',
                'message' => "%" . number_format($savingsRate, 1) . " tasarruf oranıyla harika bir performans sergiliyorsunuz!",
                'confidence' => 95,
                'action' => 'Bu tasarrufları yatırım fırsatlarında değerlendirmeyi düşünün.'
            ];
        }
        
        return $insights;
    }
    
    private function generateSmartAlerts($userId) {
        $alerts = [];
        
        // Bütçe uyarıları
        try {
            $budgets = $this->budgetModel->getAll($userId);
            foreach ($budgets as $budget) {
                if ($budget['percentage'] > 80) {
                    $alerts[] = [
                        'type' => $budget['percentage'] > 100 ? 'danger' : 'warning',
                        'icon' => 'fas fa-exclamation-triangle',
                        'title' => 'Bütçe Uyarısı: ' . $budget['category_name'],
                        'message' => "Bütçenizin %" . number_format($budget['percentage'], 1) . "'ini kullandınız.",
                        'amount' => number_format($budget['spent'], 2) . ' / ' . number_format($budget['amount'], 2) . ' ₺',
                        'urgency' => $budget['percentage'] > 100 ? 'high' : 'medium'
                    ];
                }
            }
        } catch (Exception $e) {
            // Budget model yoksa sessizce geç
        }
        
        // Kredi kartı uyarıları
        try {
            $creditCards = $this->creditCardModel->getAll($userId);
            foreach ($creditCards as $card) {
                $usagePercentage = $card['credit_limit'] > 0 ? ($card['real_used_limit'] / $card['credit_limit']) * 100 : 0;
                
                if ($usagePercentage > 70) {
                    $alerts[] = [
                        'type' => $usagePercentage > 90 ? 'danger' : 'warning',
                        'icon' => 'fas fa-credit-card',
                        'title' => 'Kredi Kartı Limit Uyarısı',
                        'message' => $card['name'] . " kartınızın %" . number_format($usagePercentage, 1) . "'ini kullandınız.",
                        'amount' => number_format($card['real_used_limit'], 2) . ' / ' . number_format($card['credit_limit'], 2) . ' ₺',
                        'urgency' => $usagePercentage > 90 ? 'high' : 'medium'
                    ];
                }
            }
            
            // Yaklaşan ödemeler
            $upcomingPayments = $this->creditCardModel->getUpcomingPayments($userId, 7);
            foreach ($upcomingPayments as $payment) {
                $alerts[] = [
                    'type' => 'info',
                    'icon' => 'fas fa-calendar-alt',
                    'title' => 'Yaklaşan Ödeme',
                    'message' => $payment['card_name'] . " kartınızın ödemesi yaklaşıyor.",
                    'amount' => number_format($payment['current_balance'], 2) . ' ₺',
                    'urgency' => 'medium'
                ];
            }
        } catch (Exception $e) {
            // Credit card model yoksa sessizce geç
        }
        
        return $alerts;
    }
    
    private function analyzeSpendingPatterns($userId) {
        // Haftalık harcama paterni
        $sql = "SELECT 
                    DAYOFWEEK(transaction_date) as day_of_week,
                    AVG(amount) as avg_amount,
                    COUNT(*) as transaction_count
                FROM transactions 
                WHERE user_id = ? AND type = 'expense'
                AND transaction_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY DAYOFWEEK(transaction_date)
                ORDER BY day_of_week";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $weeklyPattern = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Saatlik harcama paterni
        $sql = "SELECT 
                    HOUR(transaction_date) as hour_of_day,
                    AVG(amount) as avg_amount,
                    COUNT(*) as transaction_count
                FROM transactions 
                WHERE user_id = ? AND type = 'expense'
                AND transaction_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY HOUR(transaction_date)
                ORDER BY hour_of_day";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $hourlyPattern = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'weekly' => $weeklyPattern,
            'hourly' => $hourlyPattern
        ];
    }
    
    private function analyzeBudgetPerformance($userId) {
        try {
            $budgets = $this->budgetModel->getAll($userId);
            $performance = [];
            
            foreach ($budgets as $budget) {
                $performance[] = [
                    'category' => $budget['category_name'],
                    'budget_amount' => $budget['amount'],
                    'spent_amount' => $budget['spent'],
                    'percentage' => $budget['percentage'],
                    'remaining' => $budget['amount'] - $budget['spent'],
                    'status' => $budget['percentage'] > 100 ? 'exceeded' : 
                               ($budget['percentage'] > 80 ? 'warning' : 'good'),
                    'trend' => $this->getBudgetTrend($userId, $budget['category_id'])
                ];
            }
            
            return $performance;
        } catch (Exception $e) {
            return [];
        }
    }
    
    private function generatePredictions($userId) {
        // Gelecek ay harcama tahmini
        $sql = "SELECT 
                    AVG(monthly_expense) as avg_monthly_expense,
                    STDDEV(monthly_expense) as std_monthly_expense
                FROM (
                    SELECT 
                        DATE_FORMAT(transaction_date, '%Y-%m') as month,
                        SUM(amount) as monthly_expense
                    FROM transactions 
                    WHERE user_id = ? AND type = 'expense'
                    AND transaction_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                    GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
                ) as monthly_data";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $expenseStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $predictions = [];
        
        if ($expenseStats['avg_monthly_expense']) {
            $predictions['next_month_expense'] = [
                'amount' => $expenseStats['avg_monthly_expense'],
                'confidence' => 75,
                'range' => [
                    'min' => $expenseStats['avg_monthly_expense'] - $expenseStats['std_monthly_expense'],
                    'max' => $expenseStats['avg_monthly_expense'] + $expenseStats['std_monthly_expense']
                ]
            ];
        }
        
        return $predictions;
    }
    
    private function generateRecommendations($userId) {
        $recommendations = [];
        
        // En çok harcama yapılan kategorileri bul
        $sql = "SELECT 
                    c.name as category_name,
                    SUM(t.amount) as total_amount,
                    COUNT(*) as transaction_count,
                    AVG(t.amount) as avg_amount
                FROM transactions t
                JOIN categories c ON t.category_id = c.id
                WHERE t.user_id = ? AND t.type = 'expense'
                AND t.transaction_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY c.id, c.name
                ORDER BY total_amount DESC
                LIMIT 3";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $topCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($topCategories as $category) {
            if ($category['avg_amount'] > 100) {
                $recommendations[] = [
                    'type' => 'cost_optimization',
                    'category' => $category['category_name'],
                    'title' => $category['category_name'] . ' Harcamalarını Optimize Edin',
                    'description' => "Bu kategoride ortalama " . number_format($category['avg_amount'], 2) . " ₺ harcıyorsunuz. %10-15 tasarruf mümkün.",
                    'potential_saving' => $category['total_amount'] * 0.125,
                    'priority' => 'high'
                ];
            }
        }
        
        return $recommendations;
    }
    
    private function getMonthlyData($userId, $startDate, $endDate) {
        $sql = "SELECT 
                    SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
                    SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense,
                    COUNT(*) as transaction_count
                FROM transactions 
                WHERE user_id = ? 
                AND transaction_date BETWEEN ? AND ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $startDate, $endDate]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total_income' => 0, 'total_expense' => 0, 'transaction_count' => 0];
    }
    
    private function getBudgetTrend($userId, $categoryId) {
        // Son 3 ayın bütçe performansını karşılaştır
        $sql = "SELECT 
                    DATE_FORMAT(transaction_date, '%Y-%m') as month,
                    SUM(amount) as monthly_expense
                FROM transactions 
                WHERE user_id = ? AND category_id = ? AND type = 'expense'
                AND transaction_date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
                GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
                ORDER BY month DESC
                LIMIT 3";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $categoryId]);
        $monthlyData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($monthlyData) >= 2) {
            $current = $monthlyData[0]['monthly_expense'];
            $previous = $monthlyData[1]['monthly_expense'];
            
            if ($current > $previous * 1.1) {
                return 'increasing';
            } elseif ($current < $previous * 0.9) {
                return 'decreasing';
            }
        }
        
        return 'stable';
    }
} 