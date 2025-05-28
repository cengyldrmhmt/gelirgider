<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/Category.php';

class CategoryController extends Controller {
    private $categoryModel;
    
    public function __construct() {
        // Session kontrolü constructor'da yap
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor.']);
                exit;
            }
        }
        
        $this->categoryModel = new Category();
    }
    
    public function index() {
        $userId = $_SESSION['user_id'];
        $categories = $this->categoryModel->getAll($userId);
        
        // Her kategori için işlem sayısını hesapla
        foreach ($categories as &$category) {
            $category['transaction_count'] = $this->getTransactionCount($category['id'], $userId);
        }
        
        return [
            'categories' => $categories
        ];
    }
    
    private function getTransactionCount($categoryId, $userId) {
        try {
            require_once __DIR__ . '/../core/Database.php';
            $db = Database::getInstance()->getConnection();
            
            // Cüzdan işlemlerini say
            $stmt = $db->prepare("
                SELECT COUNT(*) as count 
                FROM transactions 
                WHERE category_id = ? AND user_id = ?
            ");
            $stmt->execute([$categoryId, $userId]);
            $walletCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            
            // Kredi kartı işlemlerini say
            $stmt = $db->prepare("
                SELECT COUNT(*) as count 
                FROM credit_card_transactions 
                WHERE category_id = ? AND user_id = ?
            ");
            $stmt->execute([$categoryId, $userId]);
            $creditCardCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            
            return $walletCount + $creditCardCount;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    public function create() {
        ob_start(); // Output buffering başlat
        
        try {
            if (empty($_POST['name']) || empty($_POST['type'])) {
                throw new Exception('Kategori adı ve tipi gereklidir.');
            }
            
            $data = [
                'user_id' => $_SESSION['user_id'],
                'name' => $_POST['name'],
                'type' => $_POST['type'],
                'color' => $_POST['color'] ?? '#000000',
                'icon' => $_POST['icon'] ?? 'ellipsis-h'
            ];
            
            if ($this->categoryModel->create($data)) {
                $_SESSION['success'] = 'Kategori başarıyla eklendi.';
                ob_end_clean(); // Buffer'ı temizle
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Kategori eklenirken bir hata oluştu.');
            }
        } catch (Exception $e) {
            ob_end_clean(); // Buffer'ı temizle
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    public function update() {
        ob_start(); // Output buffering başlat
        
        try {
            if (empty($_POST['id']) || empty($_POST['name']) || empty($_POST['type'])) {
                throw new Exception('Kategori ID, adı ve tipi gereklidir.');
            }
            
            $data = [
                'name' => $_POST['name'],
                'type' => $_POST['type'],
                'color' => $_POST['color'] ?? '#000000',
                'icon' => $_POST['icon'] ?? 'ellipsis-h'
            ];
            
            if ($this->categoryModel->update($_POST['id'], $data, $_SESSION['user_id'])) {
                $_SESSION['success'] = 'Kategori başarıyla güncellendi.';
                ob_end_clean(); // Buffer'ı temizle
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Kategori güncellenirken bir hata oluştu.');
            }
        } catch (Exception $e) {
            ob_end_clean(); // Buffer'ı temizle
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    public function delete() {
        ob_start(); // Output buffering başlat
        
        try {
            if (empty($_POST['id'])) {
                throw new Exception('Kategori ID gereklidir.');
            }
            
            if ($this->categoryModel->delete($_POST['id'], $_SESSION['user_id'])) {
                $_SESSION['success'] = 'Kategori başarıyla silindi.';
                ob_end_clean(); // Buffer'ı temizle
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Kategori silinirken bir hata oluştu.');
            }
        } catch (Exception $e) {
            ob_end_clean(); // Buffer'ı temizle
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    public function get() {
        ob_start(); // Output buffering başlat
        
        try {
            if (empty($_GET['id'])) {
                throw new Exception('Kategori ID gereklidir.');
            }
            
            $category = $this->categoryModel->get($_GET['id'], $_SESSION['user_id']);
            
            if ($category) {
                ob_end_clean(); // Buffer'ı temizle
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'data' => $category]);
            } else {
                throw new Exception('Kategori bulunamadı.');
            }
        } catch (Exception $e) {
            ob_end_clean(); // Buffer'ı temizle
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function getAll() {
        if (!isset($_SESSION['user_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor.']);
            exit;
        }

        $categories = $this->categoryModel->getAll($_SESSION['user_id']);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $categories]);
        exit;
    }

    // JSON header göndermeden kategorileri döndüren method
    public function getAllForPage() {
        if (!isset($_SESSION['user_id'])) {
            return [];
        }
        return $this->categoryModel->getAll($_SESSION['user_id']);
    }

    public function getTransactions() {
        try {
            $userId = $_SESSION['user_id'];
            $categoryId = $_GET['category_id'] ?? null;
            $type = $_GET['type'] ?? null;
            
            require_once __DIR__ . '/../core/Database.php';
            $db = Database::getInstance()->getConnection();
            
            $sql = "SELECT 
                        t.id,
                        t.transaction_date,
                        t.type,
                        t.amount,
                        t.description,
                        c.name as category_name,
                        c.color as category_color,
                        w.name as source_name,
                        'wallet' as source_type
                    FROM transactions t
                    LEFT JOIN categories c ON t.category_id = c.id
                    LEFT JOIN wallets w ON t.wallet_id = w.id
                    WHERE t.user_id = ?";
            
            $params = [$userId];
            
            if ($categoryId) {
                $sql .= " AND t.category_id = ?";
                $params[] = $categoryId;
            }
            
            if ($type) {
                $sql .= " AND t.type = ?";
                $params[] = $type;
            }
            
            $sql .= " UNION ALL
                    SELECT 
                        cct.id,
                        cct.transaction_date,
                        cct.type,
                        cct.amount,
                        cct.description,
                        c.name as category_name,
                        c.color as category_color,
                        cc.name as source_name,
                        'credit_card' as source_type
                    FROM credit_card_transactions cct
                    LEFT JOIN categories c ON cct.category_id = c.id
                    LEFT JOIN credit_cards cc ON cct.credit_card_id = cc.id
                    WHERE cct.user_id = ?";
            
            $params[] = $userId;
            
            if ($categoryId) {
                $sql .= " AND cct.category_id = ?";
                $params[] = $categoryId;
            }
            
            if ($type) {
                $sql .= " AND cct.type = ?";
                $params[] = $type;
            }
            
            $sql .= " ORDER BY transaction_date DESC";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => $transactions]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function getStats() {
        try {
            $userId = $_SESSION['user_id'];
            $categories = $this->categoryModel->getAll($userId);
            
            $stats = [
                'totalCategories' => count($categories),
                'incomeCategories' => 0,
                'expenseCategories' => 0,
                'mostUsedCategory' => null
            ];
            
            $maxTransactions = 0;
            
            foreach ($categories as $category) {
                if ($category['type'] === 'income') {
                    $stats['incomeCategories']++;
                } else {
                    $stats['expenseCategories']++;
                }
                
                // En çok kullanılan kategoriyi bul
                $transactionCount = $this->getTransactionCount($category['id'], $userId);
                if ($transactionCount > $maxTransactions) {
                    $maxTransactions = $transactionCount;
                    $stats['mostUsedCategory'] = [
                        'name' => $category['name'],
                        'color' => $category['color'],
                        'count' => $transactionCount
                    ];
                }
            }
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => $stats]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}

// URL tabanlı istekleri işle - sadece direkt bu dosyaya yapılan isteklerde çalışsın
if (isset($_GET['action']) && basename($_SERVER['PHP_SELF']) === 'CategoryController.php') {
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
    
    $controller = new CategoryController();
    
    switch ($_GET['action']) {
        case 'getAll':
            $controller->getAll();
            break;
        case 'create':
            $controller->create();
            break;
        case 'update':
            $controller->update();
            break;
        case 'delete':
            $controller->delete();
            break;
        case 'get':
            $controller->get();
            break;
        case 'getTransactions':
            $controller->getTransactions();
            break;
        case 'getStats':
            $controller->getStats();
            break;
        default:
            header('HTTP/1.0 404 Not Found');
            echo json_encode(['success' => false, 'message' => 'Action not found']);
            break;
    }
} 