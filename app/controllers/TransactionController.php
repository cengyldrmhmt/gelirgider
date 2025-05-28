<?php
// Check if session is already active before starting
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // AJAX isteği ise JSON döndür
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor.']);
        exit;
    } else {
        // Normal istek ise login sayfasına yönlendir
        header('Location: /gelirgider/app/views/auth/login.php');
        exit;
    }
}

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../models/Wallet.php';
require_once __DIR__ . '/../models/CreditCard.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../models/Tag.php';
require_once __DIR__ . '/../core/Database.php';

class TransactionController extends Controller {
    private $transactionModel;
    private $walletModel;
    private $creditCardModel;
    private $categoryModel;
    private $tagModel;
    private $userId;
    protected $db;
    
    public function __construct() {
        $this->transactionModel = new Transaction();
        $this->walletModel = new Wallet();
        $this->creditCardModel = new CreditCard();
        $this->categoryModel = new Category();
        $this->tagModel = new Tag();
        $this->userId = $_SESSION['user_id'];
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function index() {
        $userId = $_SESSION['user_id'];
        return [
            'transactions' => $this->transactionModel->getAll($userId),
            'wallets' => $this->walletModel->getAll($userId),
            'categories' => $this->categoryModel->getAll($userId)
        ];
    }

    public function getAllTransactions() {
        try {
            error_log('getAllTransactions called');
            $userId = $_SESSION['user_id'];
            $source = $_GET['source'] ?? null;
            $type = $_GET['type'] ?? null;
            $categoryId = $_GET['category_id'] ?? null;
            $tagId = $_GET['tag_id'] ?? null;
            $dateFrom = $_GET['date_from'] ?? null;
            $dateTo = $_GET['date_to'] ?? null;
            
            $transactions = [];
            
            // Cüzdan işlemlerini al
            if (!$source || $source === 'wallet') {
                $walletTransactions = $this->walletModel->getAllTransactions($userId, null, null);
                foreach ($walletTransactions as $transaction) {
                    $transaction['source_type'] = 'wallet';
                    $transaction['source_name'] = $transaction['wallet_name'];
                    $transaction['source_color'] = $transaction['wallet_color'];
                    $transaction['source_icon'] = $transaction['wallet_icon'];
                    
                    // Transaction'ın tag'lerini yükle
                    try {
                        error_log('Loading tags for wallet transaction ID: ' . $transaction['id']);
                        
                        if (method_exists($this->transactionModel, 'getTransactionTags')) {
                            $loadedTags = $this->transactionModel->getTransactionTags($transaction['id']);
                            error_log('Loaded wallet tags via model: ' . print_r($loadedTags, true));
                            $transaction['tags'] = $loadedTags ?: [];
                        } else {
                            // Direkt SQL ile tag'leri çek
                            $tagSql = "SELECT t.id, t.name FROM tags t 
                                      INNER JOIN transaction_tags tt ON t.id = tt.tag_id 
                                      WHERE tt.transaction_id = ?";
                            $tagStmt = $this->db->prepare($tagSql);
                            $tagStmt->execute([$transaction['id']]);
                            $loadedTags = $tagStmt->fetchAll(PDO::FETCH_ASSOC);
                            error_log('Loaded wallet tags via SQL: ' . print_r($loadedTags, true));
                            $transaction['tags'] = $loadedTags ?: [];
                        }
                    } catch (Exception $e) {
                        error_log('Error loading wallet transaction tags: ' . $e->getMessage());
                        $transaction['tags'] = [];
                    }
                    
                    $transactions[] = $transaction;
                }
            }
            
            // Kredi kartı işlemlerini al
            if (!$source || $source === 'credit_card') {
                $creditCardTransactions = $this->creditCardModel->getMainTransactions(null, $userId);
                foreach ($creditCardTransactions as $transaction) {
                    $transaction['source_type'] = 'credit_card';
                    $transaction['source_name'] = $transaction['card_name'];
                    $transaction['source_color'] = $transaction['card_color'];
                    $transaction['source_icon'] = 'credit-card';
                    $transaction['currency'] = 'TRY'; // Kredi kartları genelde TRY
                    
                    // Kredi kartı transaction'ının tag'lerini yükle
                    try {
                        error_log('Loading tags for credit card transaction ID: ' . $transaction['id']);
                        
                        // Direkt SQL ile credit card transaction tag'lerini çek
                        $tagSql = "SELECT t.id, t.name FROM tags t 
                                  INNER JOIN credit_card_transaction_tags ctt ON t.id = ctt.tag_id 
                                  WHERE ctt.credit_card_transaction_id = ?";
                        $tagStmt = $this->db->prepare($tagSql);
                        $tagStmt->execute([$transaction['id']]);
                        $loadedTags = $tagStmt->fetchAll(PDO::FETCH_ASSOC);
                        error_log('Loaded credit card tags via SQL: ' . print_r($loadedTags, true));
                        $transaction['tags'] = $loadedTags ?: [];
                    } catch (Exception $e) {
                        error_log('Error loading credit card tags: ' . $e->getMessage());
                        $transaction['tags'] = [];
                    }
                    
                    $transactions[] = $transaction;
                }
            }
            
            // Type filtrelemesi
            if ($type) {
                $transactions = array_filter($transactions, function($t) use ($type) {
                    // Cüzdan işlemleri için direkt type kontrolü
                    if ($t['source_type'] === 'wallet') {
                        return $t['type'] === $type;
                    }
                    
                    // Kredi kartı işlemleri için type mapping
                    if ($t['source_type'] === 'credit_card') {
                        switch ($type) {
                            case 'income':
                                return in_array($t['type'], ['payment', 'refund']);
                            case 'expense':
                                return in_array($t['type'], ['purchase', 'fee', 'interest']);
                            case 'purchase':
                                return $t['type'] === 'purchase';
                            case 'payment':
                                return $t['type'] === 'payment';
                            case 'installment':
                                return $t['type'] === 'installment';
                            default:
                                return $t['type'] === $type;
                        }
                    }
                    
                    return false;
                });
            }
            
            // Kategori filtrelemesi
            if ($categoryId) {
                $transactions = array_filter($transactions, function($t) use ($categoryId) {
                    return $t['category_id'] == $categoryId;
                });
            }
            
            // Etiket filtrelemesi
            if ($tagId) {
                $transactions = array_filter($transactions, function($t) use ($tagId) {
                    if (!isset($t['tags']) || !is_array($t['tags'])) {
                        return false;
                    }
                    
                    // Bu transaction'da aranan etiket var mı kontrol et
                    foreach ($t['tags'] as $tag) {
                        if ($tag['id'] == $tagId) {
                            return true;
                        }
                    }
                    return false;
                });
            }
            
            // Tarih filtrelemesi
            if ($dateFrom) {
                $transactions = array_filter($transactions, function($t) use ($dateFrom) {
                    $transactionDate = date('Y-m-d', strtotime($t['transaction_date']));
                    return $transactionDate >= $dateFrom;
                });
            }
            
            if ($dateTo) {
                $transactions = array_filter($transactions, function($t) use ($dateTo) {
                    $transactionDate = date('Y-m-d', strtotime($t['transaction_date']));
                    return $transactionDate <= $dateTo;
                });
            }
            
            // Tarihe göre sırala
            usort($transactions, function($a, $b) {
                return strtotime($b['transaction_date']) - strtotime($a['transaction_date']);
            });
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => array_values($transactions)]);
        } catch (Exception $e) {
            error_log('getAllTransactions error: ' . $e->getMessage());
            error_log('getAllTransactions trace: ' . $e->getTraceAsString());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function getSummary() {
        try {
            $userId = $_SESSION['user_id'];
            $currentMonth = date('Y-m');
            
            // Bu ay cüzdan işlemleri
            $walletTransactions = $this->walletModel->getAllTransactions($userId);
            $monthlyIncome = 0;
            $monthlyExpense = 0;
            $totalTransactions = 0;
            
            foreach ($walletTransactions as $transaction) {
                if (date('Y-m', strtotime($transaction['transaction_date'])) === $currentMonth) {
                    if ($transaction['type'] === 'income') {
                        $monthlyIncome += $transaction['amount'];
                    } elseif ($transaction['type'] === 'expense') {
                        $monthlyExpense += $transaction['amount'];
                    }
                }
                $totalTransactions++;
            }
            
            // Bu ay kredi kartı işlemleri
            $creditCardTransactions = $this->creditCardModel->getMainTransactions(null, $userId);
            foreach ($creditCardTransactions as $transaction) {
                if (date('Y-m', strtotime($transaction['transaction_date'])) === $currentMonth) {
                    $amount = $transaction['amount'];
                    
                    // Taksitli işlemler için aylık taksit tutarını hesapla
                    if ($transaction['installment_count'] > 1) {
                        $amount = $transaction['amount'] / $transaction['installment_count'];
                    }
                    
                    if (in_array($transaction['type'], ['purchase', 'fee', 'interest'])) {
                        $monthlyExpense += $amount;
                    } elseif (in_array($transaction['type'], ['payment', 'refund'])) {
                        $monthlyIncome += $amount;
                    }
                }
                $totalTransactions++;
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => [
                    'monthly_income' => $monthlyIncome,
                    'monthly_expense' => $monthlyExpense,
                    'total_transactions' => $totalTransactions
                ]
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function create() {
        try {
            if (empty($_POST['type']) || empty($_POST['amount']) || empty($_POST['wallet_id'])) {
                throw new Exception('İşlem tipi, miktar ve cüzdan gereklidir.');
            }
            
            $data = [
                'user_id' => $_SESSION['user_id'],
                'wallet_id' => $_POST['wallet_id'],
                'category_id' => !empty($_POST['category_id']) ? $_POST['category_id'] : null,
                'type' => $_POST['type'],
                'amount' => floatval($_POST['amount']),
                'description' => $_POST['description'] ?? null,
                'transaction_date' => $_POST['transaction_date'] ?? date('Y-m-d H:i:s')
            ];
            
            if ($this->transactionModel->create($data)) {
                // Cüzdan bakiyesini güncelle
                $amount = $data['amount'];
                if ($data['type'] === 'expense') {
                    $amount = -$amount;
                }
                $this->walletModel->updateBalance($data['wallet_id'], $amount, $_SESSION['user_id']);
                
                // Etiketleri kaydet
                if (!empty($_POST['tags']) && is_array($_POST['tags'])) {
                    // Son eklenen işlemin ID'sini al
                    $transactionId = $this->db->lastInsertId();
                    $this->saveTransactionTags($transactionId, $_POST['tags']);
                }
                
                $_SESSION['success'] = 'İşlem başarıyla eklendi.';
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('İşlem eklenirken bir hata oluştu.');
            }
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function update() {
        try {
            if (empty($_POST['id']) || empty($_POST['type']) || empty($_POST['amount'])) {
                throw new Exception('İşlem ID, tipi ve miktar gereklidir.');
            }
            
            $data = [
                'type' => $_POST['type'],
                'amount' => floatval($_POST['amount']),
                'description' => $_POST['description'] ?? null,
                'category_id' => !empty($_POST['category_id']) ? $_POST['category_id'] : null,
                'transaction_date' => $_POST['transaction_date'] ?? date('Y-m-d H:i:s'),
                'wallet_id' => $_POST['wallet_id'] ?? null
            ];
            
            if ($this->transactionModel->update($_POST['id'], $data, $_SESSION['user_id'])) {
                // Etiketleri güncelle
                $transactionId = $_POST['id'];
                $this->deleteTransactionTags($transactionId);
                if (!empty($_POST['tags']) && is_array($_POST['tags'])) {
                    $this->saveTransactionTags($transactionId, $_POST['tags']);
                }
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'İşlem başarıyla güncellendi.']);
            } else {
                throw new Exception('İşlem güncellenirken bir hata oluştu.');
            }
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function delete() {
        try {
            if (empty($_POST['id'])) {
                throw new Exception('İşlem ID gereklidir.');
            }
            
            if ($this->transactionModel->delete($_POST['id'], $_SESSION['user_id'])) {
                $_SESSION['success'] = 'İşlem başarıyla silindi.';
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('İşlem silinirken bir hata oluştu.');
            }
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function get() {
        try {
            if (empty($_GET['id'])) {
                throw new Exception('İşlem ID gereklidir.');
            }
            
            $transaction = $this->transactionModel->get($_GET['id'], $_SESSION['user_id']);
            
            if ($transaction) {
                // İşlemin etiketlerini al
                $tags = $this->transactionModel->getTransactionTags($_GET['id']);
                $transaction['tags'] = $tags;
                
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'data' => $transaction]);
            } else {
                throw new Exception('İşlem bulunamadı.');
            }
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function edit($id) {
        try {
            // İşlem bilgilerini al
            $transaction = $this->transactionModel->get($id, $_SESSION['user_id']);
            
            if (!$transaction) {
                throw new Exception('İşlem bulunamadı.');
            }
            
            // Form için gerekli diğer verileri al
            $categories = $this->categoryModel->getAll($_SESSION['user_id']);
            $wallets = $this->walletModel->getAll($_SESSION['user_id']);
            $tags = $this->tagModel->getAll($_SESSION['user_id']);
            
            // İşlemin mevcut etiketlerini al
            $transactionTags = $this->transactionModel->getTransactionTags($id);
            
            return [
                'transaction' => $transaction,
                'categories' => $categories,
                'wallets' => $wallets,
                'tags' => $tags,
                'transaction_tags' => $transactionTags
            ];
            
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: index.php');
            exit;
        }
    }

    public function list() {
        try {
            $start = $_GET['start'] ?? 0;
            $length = $_GET['length'] ?? 10;
            $draw = $_GET['draw'] ?? 1;
            
            // Build query conditions
            $conditions = ['t.user_id = ?'];
            $params = [$this->userId];
            
            if (!empty($_GET['startDate'])) {
                $conditions[] = 't.transaction_date >= ?';
                $params[] = $_GET['startDate'] . ' 00:00:00';
            }
            
            if (!empty($_GET['endDate'])) {
                $conditions[] = 't.transaction_date <= ?';
                $params[] = $_GET['endDate'] . ' 23:59:59';
            }
            
            if (!empty($_GET['type'])) {
                $conditions[] = 't.type = ?';
                $params[] = $_GET['type'];
            }
            
            if (!empty($_GET['category'])) {
                $conditions[] = 't.category_id = ?';
                $params[] = $_GET['category'];
            }
            
            if (!empty($_GET['wallet'])) {
                $conditions[] = 't.wallet_id = ?';
                $params[] = $_GET['wallet'];
            }
            
            $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
            
            // Get total records
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as total 
                FROM transactions t 
                $whereClause
            ");
            $stmt->execute($params);
            $totalRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get filtered records
            $stmt = $this->db->prepare("
                SELECT t.*, c.name as category_name, w.name as wallet_name
                FROM transactions t
                LEFT JOIN categories c ON t.category_id = c.id
                LEFT JOIN wallets w ON t.wallet_id = w.id
                $whereClause
                ORDER BY t.transaction_date DESC
                LIMIT ?, ?
            ");
            
            $params[] = (int)$start;
            $params[] = (int)$length;
            $stmt->execute($params);
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format dates
            foreach ($transactions as &$transaction) {
                $transaction['transaction_date'] = date('d.m.Y H:i', strtotime($transaction['transaction_date']));
            }
            
            return [
                'success' => true,
                'draw' => $draw,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $totalRecords,
                'data' => $transactions
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Bir hata oluştu: ' . $e->getMessage()
            ];
        }
    }

    public function updateTransaction() {
        ob_start(); // Start output buffering
        
        try {
            // Debug için POST verilerini logla
            error_log('UpdateTransaction POST data: ' . print_r($_POST, true));
            
            if (!isset($_SESSION['user_id'])) {
                throw new Exception('Oturum açmanız gerekiyor.');
            }

            // CSRF token kontrolünü geçici olarak devre dışı bırak (AJAX istekleri için)
            // if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            //     throw new Exception('CSRF token doğrulaması başarısız.');
            // }

            if (empty($_POST['id'])) {
                throw new Exception('İşlem ID\'si gereklidir.');
            }

            $data = [
                'type' => $_POST['type'],
                'amount' => $_POST['amount'],
                'description' => $_POST['description'],
                'transaction_date' => $_POST['transaction_date'],
                'category_id' => $_POST['category_id'] ?: null,
                'wallet_id' => $_POST['wallet_id']
            ];

            if ($this->transactionModel->update($_POST['id'], $data, $this->userId)) {
                // Mevcut etiketleri sil ve yenilerini ekle
                $this->deleteTransactionTags($_POST['id']);
                if (!empty($_POST['tags']) && is_array($_POST['tags'])) {
                    $this->saveTransactionTags($_POST['id'], $_POST['tags']);
                }
                
                ob_end_clean(); // Clear the output buffer
                
                // AJAX isteği ise JSON döndür
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'İşlem başarıyla güncellendi.']);
                } else {
                    // Normal form isteği ise success message ile redirect yap
                    $_SESSION['success'] = 'İşlem başarıyla güncellendi.';
                    header('Location: index.php');
                }
            } else {
                throw new Exception('İşlem güncellenirken bir hata oluştu.');
            }
        } catch (Exception $e) {
            ob_end_clean(); // Clear the output buffer
            
            // AJAX isteği ise JSON döndür
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            } else {
                // Normal form isteği ise error message ile redirect yap
                $_SESSION['error'] = $e->getMessage();
                header('Location: edit.php?id=' . $_POST['id']);
            }
        }
        exit;
    }

    public function deleteTransaction() {
        ob_start(); // Start output buffering
        
        try {
            if (!isset($_SESSION['user_id'])) {
                throw new Exception('Oturum açmanız gerekiyor.');
            }

            // CSRF token kontrolünü geçici olarak devre dışı bırak (AJAX istekleri için)
            // if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            //     throw new Exception('CSRF token doğrulaması başarısız.');
            // }

            if (empty($_POST['id'])) {
                throw new Exception('İşlem ID\'si gereklidir.');
            }

            if ($this->transactionModel->delete($_POST['id'], $this->userId)) {
                ob_end_clean(); // Clear the output buffer
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'İşlem başarıyla silindi.']);
            } else {
                throw new Exception('İşlem silinirken bir hata oluştu.');
            }
        } catch (Exception $e) {
            ob_end_clean(); // Clear the output buffer
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function setRecurring() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['transaction'])) {
                throw new Exception('İşlem bilgileri eksik.');
            }
            
            $transaction = $data['transaction'];
            
            // Tekrarlayan işlem tablosuna ekle
            $stmt = $this->db->prepare("
                INSERT INTO recurring_transactions (
                    user_id, 
                    description, 
                    amount, 
                    category_id, 
                    wallet_id, 
                    day_of_month,
                    last_processed_date
                ) VALUES (?, ?, ?, ?, ?, ?, CURDATE())
            ");
            
            $stmt->execute([
                $this->userId,
                $transaction['description'],
                $transaction['amount'],
                $transaction['category_id'],
                $transaction['wallet_id'],
                date('d', strtotime($transaction['transaction_date']))
            ]);
            
            echo json_encode(['success' => true]);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    private function getWallets() {
        return $this->walletModel->getAll($this->userId);
    }

    public function getTransactionTags() {
        if (!isset($_GET['transaction_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'İşlem ID\'si gerekli.']);
            return;
        }
        
        $tags = $this->transactionModel->getTransactionTags($_GET['transaction_id']);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'tags' => $tags]);
    }
    
    private function saveTransactionTags($transactionId, $tags) {
        // Duplicate'ları temizle
        $uniqueTags = array_unique($tags);
        
        foreach ($uniqueTags as $tagId) {
            // Eğer zaten varsa skip et
            $checkSql = "SELECT COUNT(*) FROM transaction_tags WHERE transaction_id = ? AND tag_id = ?";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([$transactionId, $tagId]);
            
            if ($checkStmt->fetchColumn() == 0) {
                $sql = "INSERT INTO transaction_tags (transaction_id, tag_id) VALUES (?, ?)";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$transactionId, $tagId]);
            }
        }
    }
    
    private function deleteTransactionTags($transactionId) {
        $sql = "DELETE FROM transaction_tags WHERE transaction_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$transactionId]);
    }

    public function deposit() {
        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $requiredFields = ['amount', 'wallet_id'];
                foreach ($requiredFields as $field) {
                    if (empty($_POST[$field])) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => 'Lütfen tüm zorunlu alanları doldurun.']);
                        return;
                    }
                }
                
                $data = [
                    'user_id' => $this->userId,
                    'type' => 'income',
                    'amount' => $_POST['amount'],
                    'description' => $_POST['description'] ?? '',
                    'transaction_date' => $_POST['transaction_date'] ?? date('Y-m-d H:i:s'),
                    'category_id' => $_POST['category_id'] ?? null,
                    'wallet_id' => $_POST['wallet_id']
                ];
                
                $transactionId = $this->transactionModel->create($data);
                
                if ($transactionId) {
                    // Etiketleri kaydet
                    if (!empty($_POST['tags']) && is_array($_POST['tags'])) {
                        $this->saveTransactionTags($transactionId, $_POST['tags']);
                    }
                    
                    // Session'a success mesajı ekle
                    $_SESSION['success'] = 'Gelir başarıyla eklendi.';
                    
                    // NOT: Bakiye güncellemesi create() metodunda zaten yapılıyor, burada tekrar yapmıyoruz
                    // $this->walletModel->updateBalance($data['wallet_id'], $data['amount'], $this->userId);
                    
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Gelir başarıyla eklendi.']);
                } else {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Gelir eklenirken bir hata oluştu.']);
                }
            }
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Bir hata oluştu: ' . $e->getMessage()]);
        }
    }
    
    public function withdraw() {
        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $requiredFields = ['amount', 'wallet_id'];
                foreach ($requiredFields as $field) {
                    if (empty($_POST[$field])) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => 'Lütfen tüm zorunlu alanları doldurun.']);
                        return;
                    }
                }
                
                $data = [
                    'user_id' => $this->userId,
                    'type' => 'expense',
                    'amount' => $_POST['amount'],
                    'description' => $_POST['description'] ?? '',
                    'transaction_date' => $_POST['transaction_date'] ?? date('Y-m-d H:i:s'),
                    'category_id' => $_POST['category_id'] ?? null,
                    'wallet_id' => $_POST['wallet_id']
                ];
                
                $transactionId = $this->transactionModel->create($data);
                
                if ($transactionId) {
                    // Etiketleri kaydet
                    if (!empty($_POST['tags']) && is_array($_POST['tags'])) {
                        $this->saveTransactionTags($transactionId, $_POST['tags']);
                    }
                    
                    // Session'a success mesajı ekle
                    $_SESSION['success'] = 'Gider başarıyla eklendi.';
                    
                    // NOT: Bakiye güncellemesi create() metodunda zaten yapılıyor, burada tekrar yapmıyoruz
                    // $this->walletModel->updateBalance($data['wallet_id'], -$data['amount'], $this->userId);
                    
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Gider başarıyla eklendi.']);
                } else {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Gider eklenirken bir hata oluştu.']);
                }
            }
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Bir hata oluştu: ' . $e->getMessage()]);
        }
    }
}

// URL tabanlı istekleri işle
if (isset($_GET['action'])) {
    // Session already started at the top of the file, no need to start again
    
    $controller = new TransactionController();
    
    switch ($_GET['action']) {
        case 'getAllTransactions':
            $controller->getAllTransactions();
            break;
        case 'getSummary':
            $controller->getSummary();
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
        case 'list':
            $response = $controller->list();
            break;
        case 'updateTransaction':
            $response = $controller->updateTransaction();
            break;
        case 'deleteTransaction':
            $response = $controller->deleteTransaction();
            break;
        case 'setRecurring':
            $response = $controller->setRecurring();
            break;
        case 'deposit':
            $response = $controller->deposit();
            break;
        case 'withdraw':
            $response = $controller->withdraw();
            break;
        case 'getTransactionTags':
            $response = $controller->getTransactionTags();
            break;
        case 'edit':
            $response = $controller->edit($_GET['id']);
            break;
        default:
            header('HTTP/1.0 404 Not Found');
            echo json_encode(['success' => false, 'message' => 'Action not found']);
            break;
    }

    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    if (isset($response)) {
        echo json_encode($response);
    }
    exit;
} 