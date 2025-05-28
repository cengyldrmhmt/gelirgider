<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/CreditCard.php';
require_once __DIR__ . '/../models/Category.php';

class CreditCardController extends Controller {
    private $creditCardModel;
    private $categoryModel;
    
    public function __construct() {
        $this->creditCardModel = new CreditCard();
        $this->categoryModel = new Category();
    }
    
    public function index() {
        $userId = $_SESSION['user_id'];
        
        $data = [
            'title' => 'Kredi Kartları',
            'credit_cards' => $this->creditCardModel->getAll($userId),
            'total_limits' => $this->creditCardModel->getTotalUsedLimit($userId),
            'upcoming_payments' => $this->creditCardModel->getUpcomingPayments($userId, 60),
            'installment_plans' => $this->creditCardModel->getInstallmentPlans($userId)
        ];
        
        // AJAX isteği ise JSON döndür
        if (isset($_GET['action']) && $_GET['action'] === 'getAll') {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $data['credit_cards']
            ]);
            exit;
        }
        
        return $data;
    }
    
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                if (!isset($_SESSION['user_id'])) {
                    throw new Exception('Oturum açmanız gerekiyor.');
                }

                $data = [
                    'user_id' => $_SESSION['user_id'],
                    'name' => $_POST['name'],
                    'bank_name' => $_POST['bank_name'] ?? null,
                    'card_number_last4' => $_POST['card_number_last4'] ?? null,
                    'card_type' => $_POST['card_type'] ?? 'visa',
                    'credit_limit' => floatval($_POST['credit_limit']),
                    'currency' => $_POST['currency'] ?? 'TRY',
                    'statement_day' => intval($_POST['statement_day'] ?? 1),
                    'due_day' => intval($_POST['due_day'] ?? 15),
                    'minimum_payment_rate' => floatval($_POST['minimum_payment_rate'] ?? 5.00),
                    'interest_rate' => floatval($_POST['interest_rate'] ?? 2.50),
                    'annual_fee' => floatval($_POST['annual_fee'] ?? 0.00),
                    'color' => $_POST['color'] ?? '#007bff',
                    'icon' => $_POST['icon'] ?? 'credit-card'
                ];

                if ($cardId = $this->creditCardModel->create($data)) {
                    if (isset($_POST['ajax'])) {
                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => true,
                            'message' => 'Kredi kartı başarıyla eklendi.',
                            'card_id' => $cardId
                        ]);
                        exit;
                    }
                    
                    $_SESSION['success_message'] = 'Kredi kartı başarıyla eklendi.';
                    header('Location: /gelirgider/app/views/credit-cards/index.php');
                    exit;
                } else {
                    throw new Exception('Kredi kartı eklenirken bir hata oluştu.');
                }
            } catch (Exception $e) {
                if (isset($_POST['ajax'])) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => $e->getMessage()
                    ]);
                    exit;
                }
                
                $_SESSION['error_message'] = $e->getMessage();
                header('Location: /gelirgider/app/views/credit-cards/index.php');
                exit;
            }
        }
        
        // GET isteği için form verilerini hazırla
        return [
            'title' => 'Yeni Kredi Kartı',
            'card_types' => [
                'visa' => 'Visa',
                'mastercard' => 'Mastercard',
                'amex' => 'American Express',
                'troy' => 'Troy',
                'other' => 'Diğer'
            ],
            'currencies' => ['TRY', 'USD', 'EUR', 'GBP']
        ];
    }
    
    public function edit() {
        $userId = $_SESSION['user_id'];
        $cardId = $_GET['id'] ?? $_POST['card_id'] ?? null;
        
        if (!$cardId) {
            if (isset($_POST['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Kredi kartı ID gereklidir.'
                ]);
                exit;
            }
            $_SESSION['error_message'] = 'Kredi kartı ID gereklidir.';
            header('Location: /gelirgider/app/views/credit-cards/index.php');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $data = [
                    'name' => $_POST['name'],
                    'bank_name' => $_POST['bank_name'] ?? null,
                    'card_number_last4' => $_POST['card_number_last4'] ?? null,
                    'card_type' => $_POST['card_type'] ?? 'visa',
                    'credit_limit' => floatval($_POST['credit_limit']),
                    'currency' => $_POST['currency'] ?? 'TRY',
                    'statement_day' => intval($_POST['statement_day'] ?? 1),
                    'due_day' => intval($_POST['due_day'] ?? 15),
                    'minimum_payment_rate' => floatval($_POST['minimum_payment_rate'] ?? 5.00),
                    'interest_rate' => floatval($_POST['interest_rate'] ?? 2.50),
                    'annual_fee' => floatval($_POST['annual_fee'] ?? 0.00),
                    'color' => $_POST['color'] ?? '#007bff',
                    'icon' => $_POST['icon'] ?? 'credit-card'
                ];

                if ($this->creditCardModel->update($cardId, $data, $userId)) {
                    if (isset($_POST['ajax'])) {
                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => true,
                            'message' => 'Kredi kartı başarıyla güncellendi.'
                        ]);
                        exit;
                    }
                    
                    $_SESSION['success_message'] = 'Kredi kartı başarıyla güncellendi.';
                    header('Location: /gelirgider/app/views/credit-cards/index.php');
                    exit;
                } else {
                    throw new Exception('Kredi kartı güncellenirken bir hata oluştu.');
                }
            } catch (Exception $e) {
                if (isset($_POST['ajax'])) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => $e->getMessage()
                    ]);
                    exit;
                }
                
                $_SESSION['error_message'] = $e->getMessage();
            }
        }
        
        $creditCard = $this->creditCardModel->getById($cardId, $userId);
        if (!$creditCard) {
            if (isset($_POST['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Kredi kartı bulunamadı.'
                ]);
                exit;
            }
            $_SESSION['error_message'] = 'Kredi kartı bulunamadı.';
            header('Location: /gelirgider/app/views/credit-cards/index.php');
            exit;
        }
        
        return [
            'title' => 'Kredi Kartı Düzenle',
            'credit_card' => $creditCard,
            'card_types' => [
                'visa' => 'Visa',
                'mastercard' => 'Mastercard',
                'amex' => 'American Express',
                'troy' => 'Troy',
                'other' => 'Diğer'
            ],
            'currencies' => ['TRY', 'USD', 'EUR', 'GBP']
        ];
    }
    
    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $userId = $_SESSION['user_id'];
                $cardId = $_POST['id'] ?? null;
                
                if (!$cardId) {
                    throw new Exception('Kredi kartı ID gereklidir.');
                }
                
                if ($this->creditCardModel->delete($cardId, $userId)) {
                    if (isset($_POST['ajax'])) {
                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => true,
                            'message' => 'Kredi kartı başarıyla silindi.'
                        ]);
                        exit;
                    }
                    
                    $_SESSION['success_message'] = 'Kredi kartı başarıyla silindi.';
                } else {
                    throw new Exception('Kredi kartı silinirken bir hata oluştu.');
                }
            } catch (Exception $e) {
                if (isset($_POST['ajax'])) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => $e->getMessage()
                    ]);
                    exit;
                }
                
                $_SESSION['error_message'] = $e->getMessage();
            }
        }
        
        header('Location: /gelirgider/app/views/credit-cards/index.php');
        exit;
    }
    
    public function get() {
        try {
            $userId = $_SESSION['user_id'];
            $cardId = $_GET['id'] ?? null;
            
            if (!$cardId) {
                throw new Exception('Kredi kartı ID gereklidir.');
            }
            
            $creditCard = $this->creditCardModel->getById($cardId, $userId);
            
            if (!$creditCard) {
                throw new Exception('Kredi kartı bulunamadı.');
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $creditCard
            ]);
            exit;
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
            exit;
        }
    }
    
    public function addTransaction() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                if (!isset($_SESSION['user_id'])) {
                    throw new Exception('Oturum açmanız gerekiyor.');
                }

                $data = [
                    'user_id' => $_SESSION['user_id'],
                    'credit_card_id' => $_POST['credit_card_id'],
                    'category_id' => !empty($_POST['category_id']) ? $_POST['category_id'] : null,
                    'type' => $_POST['type'] ?? 'purchase',
                    'amount' => floatval($_POST['amount']),
                    'currency' => 'TRY', // Sabit TRY kullan
                    'description' => $_POST['description'] ?? null,
                    'merchant_name' => $_POST['merchant_name'] ?? null,
                    'installment_count' => intval($_POST['installment_count'] ?? 1),
                    'transaction_date' => $_POST['transaction_date'] ?? date('Y-m-d H:i:s'),
                    'payment_wallet_id' => $_POST['payment_wallet_id'] ?? null,
                    'statement_payment_wallet_id' => $_POST['statement_payment_wallet_id'] ?? null,
                    'is_paid' => intval($_POST['is_paid'] ?? 0)
                ];

                // Etiketleri işle
                $tags = [];
                if (!empty($_POST['tags'])) {
                    if (is_string($_POST['tags'])) {
                        // JSON string ise decode et
                        $tagsData = json_decode($_POST['tags'], true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($tagsData)) {
                            $tags = $tagsData;
                        } else {
                            // Virgülle ayrılmış string ise split et
                            $tags = array_map('trim', explode(',', $_POST['tags']));
                            $tags = array_filter($tags, function($tag) { return !empty($tag); });
                        }
                    } elseif (is_array($_POST['tags'])) {
                        $tags = $_POST['tags'];
                    }
                }

                // Yeni taksitli işlem sistemi kullan
                if ($transactionId = $this->creditCardModel->addInstallmentTransaction($data)) {
                    // Etiketleri ekle
                    if (!empty($tags)) {
                        $this->addTransactionTags($transactionId, $tags, $_SESSION['user_id']);
                    }
                    
                    // Bildirim oluştur
                    require_once __DIR__ . '/../models/Notification.php';
                    $notificationModel = new Notification();
                    
                    if ($data['installment_count'] > 1) {
                        $notificationModel->createSuccessNotification(
                            $data['user_id'],
                            'Taksitli İşlem Eklendi',
                            $data['installment_count'] . ' taksitli işlem eklendi. Toplam: ' . number_format($data['amount'], 2) . ' ₺, Aylık taksit: ' . number_format($data['amount'] / $data['installment_count'], 2) . ' ₺'
                        );
                    } else {
                        $notificationModel->createSuccessNotification(
                            $data['user_id'],
                            'Kredi Kartı İşlemi Eklendi',
                            number_format($data['amount'], 2) . ' ₺ tutarında ' . ($data['type'] === 'purchase' ? 'harcama' : 'işlem') . ' eklendi.'
                        );
                    }
                    
                    // Bildirim cache'ini temizle
                    $this->clearNotificationCache();
                    
                    if (isset($_POST['ajax'])) {
                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => true,
                            'message' => 'İşlem başarıyla eklendi.',
                            'transaction_id' => $transactionId
                        ]);
                        exit;
                    }
                    
                    $_SESSION['success_message'] = 'İşlem başarıyla eklendi.';
                } else {
                    throw new Exception('İşlem eklenirken bir hata oluştu.');
                }
            } catch (Exception $e) {
                if (isset($_POST['ajax'])) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => $e->getMessage()
                    ]);
                    exit;
                }
                
                $_SESSION['error_message'] = $e->getMessage();
            }
        }
        
        header('Location: /gelirgider/app/views/credit-cards/index.php');
        exit;
    }
    
    public function getTransactions() {
        try {
            $userId = $_SESSION['user_id'];
            $cardId = $_GET['card_id'] ?? null;
            $limit = intval($_GET['limit'] ?? 50);
            $offset = intval($_GET['offset'] ?? 0);
            
            if (!$cardId) {
                throw new Exception('Kredi kartı ID gereklidir.');
            }
            
            $transactions = $this->creditCardModel->getTransactions($cardId, $userId, $limit, $offset);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $transactions
            ]);
            exit;
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
            exit;
        }
    }
    
    public function getStatement() {
        try {
            $userId = $_SESSION['user_id'];
            $cardId = $_GET['card_id'] ?? null;
            $year = intval($_GET['year'] ?? date('Y'));
            $month = intval($_GET['month'] ?? date('n'));
            
            if (!$cardId) {
                throw new Exception('Kredi kartı ID gereklidir.');
            }
            
            $statement = $this->creditCardModel->getMonthlyStatement($cardId, $userId, $year, $month);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $statement
            ]);
            exit;
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
            exit;
        }
    }
    
    public function makePayment() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                if (!isset($_SESSION['user_id'])) {
                    throw new Exception('Oturum açmanız gerekiyor.');
                }

                $walletId = $_POST['wallet_id'] ?? null;
                if (!$walletId) {
                    throw new Exception('Ödeme yapılacak cüzdan seçilmelidir.');
                }

                // Cüzdan kontrolü ve bakiye kontrolü
                require_once __DIR__ . '/../models/Wallet.php';
                $walletModel = new Wallet();
                $wallet = $walletModel->get($walletId, $_SESSION['user_id']);
                
                if (!$wallet) {
                    throw new Exception('Seçilen cüzdan bulunamadı.');
                }

                $paymentAmount = floatval($_POST['amount']);
                if ($paymentAmount <= 0) {
                    throw new Exception('Ödeme tutarı 0\'dan büyük olmalıdır.');
                }

                if ($wallet['real_balance'] < $paymentAmount) {
                    throw new Exception('Cüzdan bakiyesi yetersiz. Mevcut bakiye: ' . number_format($wallet['real_balance'], 2) . ' ' . $wallet['currency']);
                }

                // Kredi kartı işlemi ekle
                $data = [
                    'user_id' => $_SESSION['user_id'],
                    'credit_card_id' => $_POST['credit_card_id'],
                    'type' => 'payment',
                    'amount' => $paymentAmount,
                    'currency' => $_POST['currency'] ?? 'TRY',
                    'description' => $_POST['description'] ?? 'Kredi kartı ödemesi',
                    'transaction_date' => $_POST['payment_date'] ?? date('Y-m-d H:i:s')
                ];

                if ($transactionId = $this->creditCardModel->addTransaction($data)) {
                    // Cüzdandan para çek (gider işlemi olarak)
                    require_once __DIR__ . '/../models/Transaction.php';
                    $transactionModel = new Transaction();
                    
                    $walletTransactionData = [
                        'user_id' => $_SESSION['user_id'],
                        'wallet_id' => $walletId,
                        'category_id' => null, // Kredi kartı ödemesi için özel kategori eklenebilir
                        'type' => 'expense',
                        'amount' => $paymentAmount,
                        'currency' => $wallet['currency'],
                        'description' => 'Kredi kartı ödemesi - ' . ($_POST['description'] ?? ''),
                        'transaction_date' => $_POST['payment_date'] ?? date('Y-m-d H:i:s')
                    ];
                    
                    $walletTransactionId = $transactionModel->create($walletTransactionData);
                    
                    if ($walletTransactionId) {
                        // Bildirim oluştur
                        require_once __DIR__ . '/../models/Notification.php';
                        $notificationModel = new Notification();
                        $notificationModel->createSuccessNotification(
                            $_SESSION['user_id'],
                            'Kredi Kartı Ödemesi Yapıldı',
                            number_format($paymentAmount, 2) . ' ₺ tutarında kredi kartı ödemesi başarıyla yapıldı ve cüzdan bakiyesi güncellendi.'
                        );
                        
                        // Bildirim cache'ini temizle
                        $this->clearNotificationCache();
                        
                        if (isset($_POST['ajax'])) {
                            header('Content-Type: application/json');
                            echo json_encode([
                                'success' => true,
                                'message' => 'Ödeme başarıyla kaydedildi ve cüzdan bakiyesi güncellendi.',
                                'transaction_id' => $transactionId,
                                'wallet_transaction_id' => $walletTransactionId
                            ]);
                            exit;
                        }
                        
                        $_SESSION['success_message'] = 'Ödeme başarıyla kaydedildi ve cüzdan bakiyesi güncellendi.';
                    } else {
                        // Kredi kartı işlemini geri al
                        $this->creditCardModel->deleteTransaction($transactionId, $_SESSION['user_id']);
                        throw new Exception('Cüzdan işlemi kaydedilemedi. Ödeme iptal edildi.');
                    }
                } else {
                    throw new Exception('Ödeme kaydedilirken bir hata oluştu.');
                }
            } catch (Exception $e) {
                if (isset($_POST['ajax'])) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => $e->getMessage()
                    ]);
                    exit;
                }
                
                $_SESSION['error_message'] = $e->getMessage();
            }
        }
        
        header('Location: /gelirgider/app/views/credit-cards/index.php');
        exit;
    }
    
    public function getAllTransactions() {
        try {
            $userId = $_SESSION['user_id'];
            $cardId = $_GET['card_id'] ?? null;
            $type = $_GET['type'] ?? null;
            
            // Ana işlemleri getir (taksitli işlemler tek satırda görünür)
            $transactions = $this->creditCardModel->getMainTransactions($cardId, $userId);
            
            // Type filtresi varsa uygula
            if ($type) {
                $transactions = array_filter($transactions, function($transaction) use ($type) {
                    return $transaction['type'] === $type;
                });
                $transactions = array_values($transactions); // Re-index array
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $transactions
            ]);
            exit;
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
            exit;
        }
    }
    
    public function getTransactionDetails() {
        try {
            $userId = $_SESSION['user_id'];
            $transactionId = $_GET['id'] ?? null;
            
            if (!$transactionId) {
                throw new Exception('İşlem ID gereklidir.');
            }
            
            require_once __DIR__ . '/../core/Database.php';
            $db = Database::getInstance()->getConnection();
            
            $sql = "
                SELECT 
                    cct.*,
                    cc.name as card_name,
                    cc.color as card_color,
                    c.name as category_name
                FROM credit_card_transactions cct
                LEFT JOIN credit_cards cc ON cct.credit_card_id = cc.id
                LEFT JOIN categories c ON cct.category_id = c.id
                WHERE cct.id = ? AND cct.user_id = ?
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$transactionId, $userId]);
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$transaction) {
                throw new Exception('İşlem bulunamadı.');
            }
            
            // Tag'leri getir
            $transaction['tags'] = $this->creditCardModel->getTransactionTags($transactionId);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $transaction
            ]);
            exit;
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
            exit;
        }
    }
    
    public function getTransaction() {
        $this->getTransactionDetails();
    }
    
    public function updateTransaction() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $userId = $_SESSION['user_id'];
                $transactionId = $_POST['id'] ?? null;
                
                if (!$transactionId) {
                    throw new Exception('İşlem ID gereklidir.');
                }
                
                // Güncelleme verilerini hazırla
                $updateData = [];
                
                $allowedFields = [
                    'transaction_date',
                    'type', 
                    'amount',
                    'description',
                    'merchant_name',
                    'category_id',
                    'is_paid',
                    'installment_count',
                    'payment_wallet_id'
                ];
                
                foreach ($allowedFields as $field) {
                    if (isset($_POST[$field])) {
                        // category_id boşsa NULL yap
                        if ($field === 'category_id' && empty($_POST[$field])) {
                            $updateData[$field] = null;
                        } else {
                            $updateData[$field] = $_POST[$field];
                        }
                    }
                }
                
                // Etiketleri işle
                $tags = [];
                if (isset($_POST['tags'])) {
                    if (is_string($_POST['tags'])) {
                        // JSON string ise decode et
                        $tagsData = json_decode($_POST['tags'], true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($tagsData)) {
                            $tags = $tagsData;
                        } else {
                            // Virgülle ayrılmış string ise split et
                            $tags = array_map('trim', explode(',', $_POST['tags']));
                            $tags = array_filter($tags, function($tag) { return !empty($tag); });
                        }
                    } elseif (is_array($_POST['tags'])) {
                        $tags = $_POST['tags'];
                    }
                }
                
                if (empty($updateData) && empty($tags)) {
                    throw new Exception('Güncellenecek alan bulunamadı.');
                }
                
                // Model'deki updateTransaction metodunu kullan
                $success = true;
                if (!empty($updateData)) {
                    $success = $this->creditCardModel->updateTransaction($transactionId, $updateData, $userId);
                }
                
                // Etiketleri güncelle
                if ($success && isset($_POST['tags'])) {
                    $this->updateTransactionTags($transactionId, $tags, $userId);
                }
                
                if ($success) {
                    // Bildirim oluştur
                    require_once __DIR__ . '/../models/Notification.php';
                    $notificationModel = new Notification();
                    
                    $notificationModel->createSuccessNotification(
                        $userId,
                        'İşlem Güncellendi',
                        'Kredi kartı işlemi başarıyla güncellendi.'
                    );
                    
                    // Bildirim cache'ini temizle
                    $this->clearNotificationCache();
                    
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => 'İşlem başarıyla güncellendi.'
                    ]);
                    exit;
                } else {
                    throw new Exception('İşlem güncellenirken bir hata oluştu.');
                }
                
            } catch (Exception $e) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
                exit;
            }
        }
        
        header('HTTP/1.0 405 Method Not Allowed');
        exit;
    }
    
    /**
     * İşleme etiket ekler
     */
    private function addTransactionTags($transactionId, $tags, $userId) {
        if (empty($tags)) {
            return;
        }
        
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();
        
        try {
            $db->beginTransaction();
            
            foreach ($tags as $tagName) {
                $tagName = trim($tagName);
                if (empty($tagName)) {
                    continue;
                }
                
                // Tag'i bul veya oluştur
                $stmt = $db->prepare("SELECT id FROM tags WHERE name = ? AND user_id = ?");
                $stmt->execute([$tagName, $userId]);
                $tag = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$tag) {
                    // Yeni tag oluştur
                    $stmt = $db->prepare("INSERT INTO tags (name, user_id, created_at) VALUES (?, ?, NOW())");
                    $stmt->execute([$tagName, $userId]);
                    $tagId = $db->lastInsertId();
                } else {
                    $tagId = $tag['id'];
                }
                
                // İşlem-tag ilişkisini oluştur
                $stmt = $db->prepare("INSERT IGNORE INTO credit_card_transaction_tags (transaction_id, tag_id) VALUES (?, ?)");
                $stmt->execute([$transactionId, $tagId]);
            }
            
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
    
    /**
     * İşlem etiketlerini günceller
     */
    private function updateTransactionTags($transactionId, $tags, $userId) {
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();
        
        try {
            $db->beginTransaction();
            
            // Mevcut etiketleri sil
            $stmt = $db->prepare("DELETE FROM credit_card_transaction_tags WHERE transaction_id = ?");
            $stmt->execute([$transactionId]);
            
            // Yeni etiketleri ekle
            if (!empty($tags)) {
                $this->addTransactionTags($transactionId, $tags, $userId);
            }
            
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
    
    public function deleteTransaction() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $userId = $_SESSION['user_id'];
                $transactionId = $_POST['id'] ?? null;
                
                if (!$transactionId) {
                    throw new Exception('İşlem ID gereklidir.');
                }
                
                require_once __DIR__ . '/../core/Database.php';
                $db = Database::getInstance()->getConnection();
                
                // First check if transaction belongs to user
                $stmt = $db->prepare("SELECT credit_card_id FROM credit_card_transactions WHERE id = ? AND user_id = ?");
                $stmt->execute([$transactionId, $userId]);
                $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$transaction) {
                    throw new Exception('İşlem bulunamadı veya size ait değil.');
                }
                
                // Delete the transaction
                $stmt = $db->prepare("DELETE FROM credit_card_transactions WHERE id = ? AND user_id = ?");
                $success = $stmt->execute([$transactionId, $userId]);
                
                if ($success && $stmt->rowCount() > 0) {
                    if (isset($_POST['ajax'])) {
                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => true,
                            'message' => 'İşlem başarıyla silindi.'
                        ]);
                        exit;
                    }
                    
                    $_SESSION['success_message'] = 'İşlem başarıyla silindi.';
                } else {
                    throw new Exception('İşlem silinirken bir hata oluştu.');
                }
            } catch (Exception $e) {
                if (isset($_POST['ajax'])) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => $e->getMessage()
                    ]);
                    exit;
                }
                
                $_SESSION['error_message'] = $e->getMessage();
            }
        }
        
        header('Location: /gelirgider/app/views/credit-cards/index.php');
        exit;
    }
    
    public function getInstallmentDetails() {
        try {
            $userId = $_SESSION['user_id'];
            $parentTransactionId = $_GET['parent_id'] ?? null;
            
            if (!$parentTransactionId) {
                throw new Exception('Ana işlem ID gereklidir.');
            }
            
            $installmentDetails = $this->creditCardModel->getInstallmentDetails($parentTransactionId, $userId);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $installmentDetails
            ]);
            exit;
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
            exit;
        }
    }
    
    /**
     * Bildirim cache'ini temizler
     */
    private function clearNotificationCache() {
        unset($_SESSION['notification_count']);
        unset($_SESSION['latest_notifications']);
        unset($_SESSION['notification_count_time']);
    }
    
    /**
     * Kredi kartı istatistiklerini getirir
     */
    public function getStats() {
        try {
            $userId = $_SESSION['user_id'];
            
            // Toplam limitler
            $totalLimits = $this->creditCardModel->getTotalUsedLimit($userId);
            
            // Yaklaşan ödemeler
            $upcomingPayments = $this->creditCardModel->getUpcomingPayments($userId, 30);
            
            // Bu ay harcamalar
            $thisMonthSpending = $this->creditCardModel->getMonthlySpending($userId, date('Y-m'));
            
            // Toplam borç
            $totalDebt = $this->creditCardModel->getTotalDebt($userId);
            
            $stats = [
                'total_limit' => $totalLimits['total_limit'] ?? 0,
                'used_limit' => $totalLimits['total_used'] ?? 0,
                'available_limit' => $totalLimits['total_available'] ?? 0,
                'upcoming_payments_count' => count($upcomingPayments),
                'this_month_spending' => $thisMonthSpending,
                'total_debt' => $totalDebt,
                'usage_percentage' => $totalLimits['total_limit'] > 0 ? 
                    round(($totalLimits['total_used'] / $totalLimits['total_limit']) * 100, 2) : 0
            ];
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
            exit;
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
            exit;
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
    
    $controller = new CreditCardController();
    
    switch ($_GET['action']) {
        case 'getAll':
            $controller->index();
            break;
        case 'get':
            $controller->get();
            break;
        case 'create':
            $controller->create();
            break;
        case 'edit':
            $controller->edit();
            break;
        case 'update':
            $controller->edit();
            break;
        case 'delete':
            $controller->delete();
            break;
        case 'addTransaction':
            $controller->addTransaction();
            break;
        case 'getTransactions':
            $controller->getTransactions();
            break;
        case 'getStatement':
            $controller->getStatement();
            break;
        case 'makePayment':
            $controller->makePayment();
            break;
        case 'getAllTransactions':
            $controller->getAllTransactions();
            break;
        case 'getTransactionDetails':
            $controller->getTransactionDetails();
            break;
        case 'getTransaction':
            $controller->getTransactionDetails();
            break;
        case 'updateTransaction':
            $controller->updateTransaction();
            break;
        case 'deleteTransaction':
            $controller->deleteTransaction();
            break;
        case 'getInstallmentDetails':
            $controller->getInstallmentDetails();
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