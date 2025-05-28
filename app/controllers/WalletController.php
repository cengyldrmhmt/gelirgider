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
require_once __DIR__ . '/../models/Wallet.php';
require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../models/ExchangeRate.php';

class WalletController extends Controller {
    private $walletModel;
    
    public function __construct() {
        $this->walletModel = new Wallet();
    }
    
    public function index() {
        $userId = $_SESSION['user_id'];
        return [
            'wallets' => $this->walletModel->getAll($userId)
        ];
    }
    
    public function create() {
        try {
            if (empty($_POST['name']) || empty($_POST['currency'])) {
                throw new Exception('Cüzdan adı ve para birimi gereklidir.');
            }
            
            $data = [
                'user_id' => $_SESSION['user_id'],
                'name' => $_POST['name'],
                'currency' => $_POST['currency'],
                'balance' => $_POST['balance'] ?? 0,
                'color' => $_POST['color'] ?? '#000000',
                'icon' => $_POST['icon'] ?? 'wallet'
            ];
            
            if ($this->walletModel->create($data)) {
                $_SESSION['success'] = 'Cüzdan başarıyla eklendi.';
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Cüzdan eklenirken bir hata oluştu.');
            }
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    public function update() {
        try {
            if (empty($_POST['id']) || empty($_POST['name']) || empty($_POST['currency'])) {
                throw new Exception('Cüzdan ID, adı ve para birimi gereklidir.');
            }
            
            $data = [
                'name' => $_POST['name'],
                'currency' => $_POST['currency'],
                'balance' => $_POST['balance'] ?? 0,
                'type' => $_POST['type'] ?? 'cash',
                'color' => $_POST['color'] ?? '#007bff',
                'icon' => $_POST['icon'] ?? 'wallet'
            ];
            
            if ($this->walletModel->update($_POST['id'], $data, $_SESSION['user_id'])) {
                $_SESSION['success'] = 'Cüzdan başarıyla güncellendi.';
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Cüzdan güncellenirken bir hata oluştu.');
            }
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    public function delete() {
        try {
            if (empty($_POST['id'])) {
                throw new Exception('Cüzdan ID gereklidir.');
            }
            
            if ($this->walletModel->delete($_POST['id'], $_SESSION['user_id'])) {
                $_SESSION['success'] = 'Cüzdan başarıyla silindi.';
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Cüzdan silinirken bir hata oluştu.');
            }
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    public function get() {
        try {
            if (empty($_GET['id'])) {
                throw new Exception('Cüzdan ID gereklidir.');
            }
            
            $wallet = $this->walletModel->getById($_GET['id'], $_SESSION['user_id']);
            
            if ($wallet) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'data' => $wallet]);
            } else {
                throw new Exception('Cüzdan bulunamadı.');
            }
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    public function deposit() {
        try {
            if (empty($_POST['wallet_id']) || empty($_POST['amount'])) {
                throw new Exception('Cüzdan ID ve miktar gereklidir.');
            }
            
            $amount = floatval($_POST['amount']);
            if ($amount <= 0) {
                throw new Exception('Miktar 0\'dan büyük olmalıdır.');
            }
            
            // Sadece işlem kaydı oluştur (cüzdan bakiyesini manuel olarak güncelleme)
            $transactionModel = new Transaction();
            
            $transactionData = [
                'user_id' => $_SESSION['user_id'],
                'wallet_id' => $_POST['wallet_id'],
                'category_id' => !empty($_POST['category_id']) ? $_POST['category_id'] : null,
                'type' => 'income',
                'amount' => $amount,
                'description' => $_POST['description'] ?? 'Para yatırma',
                'transaction_date' => $_POST['transaction_date'] ?? date('Y-m-d H:i:s')
            ];
            
            if ($transactionModel->create($transactionData)) {
                $_SESSION['success'] = 'Para başarıyla yatırıldı.';
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Para yatırma işlemi başarısız oldu.');
            }
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    public function withdraw() {
        try {
            if (empty($_POST['wallet_id']) || empty($_POST['amount'])) {
                throw new Exception('Cüzdan ID ve miktar gereklidir.');
            }
            
            $amount = floatval($_POST['amount']);
            if ($amount <= 0) {
                throw new Exception('Miktar 0\'dan büyük olmalıdır.');
            }
            
            // Bakiye kontrolü
            $wallet = $this->walletModel->getById($_POST['wallet_id'], $_SESSION['user_id']);
            if ($wallet['real_balance'] < $amount) {
                throw new Exception('Yetersiz bakiye. Mevcut bakiye: ' . number_format($wallet['real_balance'], 2) . ' ' . $wallet['currency']);
            }
            
            // Sadece işlem kaydı oluştur (cüzdan bakiyesini manuel olarak güncelleme)
            $transactionModel = new Transaction();
            
            $transactionData = [
                'user_id' => $_SESSION['user_id'],
                'wallet_id' => $_POST['wallet_id'],
                'category_id' => !empty($_POST['category_id']) ? $_POST['category_id'] : null,
                'type' => 'expense',
                'amount' => $amount,
                'description' => $_POST['description'] ?? 'Para çekme',
                'transaction_date' => $_POST['transaction_date'] ?? date('Y-m-d H:i:s')
            ];
            
            if ($transactionModel->create($transactionData)) {
                $_SESSION['success'] = 'Para başarıyla çekildi.';
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Para çekme işlemi başarısız oldu.');
            }
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    public function transfer() {
        try {
            if (empty($_POST['source_wallet_id']) || empty($_POST['target_wallet_id']) || empty($_POST['amount'])) {
                throw new Exception('Kaynak cüzdan, hedef cüzdan ve miktar gereklidir.');
            }
            
            $amount = floatval($_POST['amount']);
            if ($amount <= 0) {
                throw new Exception('Miktar 0\'dan büyük olmalıdır.');
            }
            
            // Kaynak cüzdan kontrolü
            $sourceWallet = $this->walletModel->getById($_POST['source_wallet_id'], $_SESSION['user_id']);
            if ($sourceWallet['real_balance'] < $amount) {
                throw new Exception('Yetersiz bakiye. Mevcut bakiye: ' . number_format($sourceWallet['real_balance'], 2) . ' ' . $sourceWallet['currency']);
            }
            
            // Hedef cüzdan kontrolü
            $targetWallet = $this->walletModel->getById($_POST['target_wallet_id'], $_SESSION['user_id']);
            if (!$targetWallet) {
                throw new Exception('Hedef cüzdan bulunamadı.');
            }
            
            // İşlem kayıtları oluştur (sadece transaction kayıtları, cüzdan bakiyelerini manuel güncelleme)
            $transactionModel = new Transaction();
            
            $description = $_POST['description'] ?? 'Transfer';
            $transactionDate = $_POST['transaction_date'] ?? date('Y-m-d H:i:s');
            
            // Kaynak cüzdan için gider kaydı
            $sourceTransactionData = [
                'user_id' => $_SESSION['user_id'],
                'wallet_id' => $_POST['source_wallet_id'],
                'category_id' => null,
                'type' => 'expense',
                'amount' => $amount,
                'description' => $description . ' (→ ' . $targetWallet['name'] . ')',
                'transaction_date' => $transactionDate
            ];
            
            // Hedef cüzdan için gelir kaydı
            $targetTransactionData = [
                'user_id' => $_SESSION['user_id'],
                'wallet_id' => $_POST['target_wallet_id'],
                'category_id' => null,
                'type' => 'income',
                'amount' => $amount,
                'description' => $description . ' (← ' . $sourceWallet['name'] . ')',
                'transaction_date' => $transactionDate
            ];
            
            if ($transactionModel->create($sourceTransactionData) && $transactionModel->create($targetTransactionData)) {
                $_SESSION['success'] = 'Transfer başarıyla tamamlandı.';
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Transfer işlemi başarısız oldu.');
            }
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    public function getTransactions() {
        try {
            if (empty($_GET['wallet_id'])) {
                throw new Exception('Cüzdan ID gereklidir.');
            }
            
            $transactions = $this->walletModel->getTransactions($_GET['wallet_id'], $_SESSION['user_id']);
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => $transactions]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function getAllTransactions() {
        try {
            $userId = $_SESSION['user_id'];
            $walletId = $_GET['wallet_id'] ?? null;
            $type = $_GET['type'] ?? null;
            
            $transactions = $this->walletModel->getAllTransactions($userId, $walletId, $type);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $transactions
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function deleteTransaction() {
        try {
            if (empty($_POST['id'])) {
                throw new Exception('İşlem ID gereklidir.');
            }
            
            $transactionModel = new Transaction();
            
            if ($transactionModel->delete($_POST['id'], $_SESSION['user_id'])) {
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

    public function getAll() {
        if (!isset($_SESSION['user_id'])) {
            return json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor.']);
        }

        $wallets = $this->walletModel->getAll();
        return $wallets;
    }

    public function getStats() {
        try {
            $userId = $_SESSION['user_id'];
            $wallets = $this->walletModel->getAll($userId);
            
            $totalBalance = 0;
            $totalIncome = 0;
            $totalExpense = 0;
            
            foreach ($wallets as $wallet) {
                $totalBalance += $wallet['real_balance'];
                $totalIncome += $wallet['total_income'];
                $totalExpense += $wallet['total_expense'];
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => [
                    'total_balance' => $totalBalance,
                    'monthly_income' => $totalIncome, // Bu ay için hesaplanabilir
                    'monthly_expense' => $totalExpense, // Bu ay için hesaplanabilir
                    'currency' => 'TRY' // Ana para birimi
                ]
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function updateExchangeRates() {
        try {
            $exchangeRate = new ExchangeRate();
            
            $result = $exchangeRate->updateRatesFromAPI();
            
            if ($result) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true, 
                    'message' => 'Döviz kurları başarıyla güncellendi',
                    'rates' => $exchangeRate->getAllRates()
                ]);
            } else {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false, 
                    'message' => 'Döviz kurları güncellenirken hata oluştu'
                ]);
            }
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function forceUpdateExchangeRates() {
        try {
            $exchangeRate = new ExchangeRate();
            
            // Eski kurları temizle ve zorla güncelle
            $result = $exchangeRate->forceUpdateRates();
            
            if ($result) {
                $rates = $exchangeRate->getAllRates();
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true, 
                    'message' => 'Döviz kurları zorla güncellendi',
                    'rates' => $rates,
                    'debug' => [
                        'usd_rate' => isset($rates[0]) ? $rates[0]['rate'] : 'N/A',
                        'timestamp' => date('Y-m-d H:i:s')
                    ]
                ]);
            } else {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false, 
                    'message' => 'Döviz kurları güncellenirken hata oluştu (fallback kullanıldı)'
                ]);
            }
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}

// URL tabanlı istekleri işle
if (isset($_GET['action'])) {
    // Session already started at the top of the file, no need to start again
    
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
    
    $controller = new WalletController();
    
    switch ($_GET['action']) {
        case 'getAll':
            $wallets = $controller->index();
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => $wallets['wallets']]);
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
        case 'deposit':
            $controller->deposit();
            break;
        case 'withdraw':
            $controller->withdraw();
            break;
        case 'transfer':
            $controller->transfer();
            break;
        case 'getTransactions':
            $controller->getTransactions();
            break;
        case 'getAllTransactions':
            $controller->getAllTransactions();
            break;
        case 'deleteTransaction':
            $controller->deleteTransaction();
            break;
        case 'getStats':
            $controller->getStats();
            break;
        case 'updateExchangeRates':
            $controller->updateExchangeRates();
            break;
        case 'forceUpdateExchangeRates':
            $controller->forceUpdateExchangeRates();
            break;
        default:
            header('HTTP/1.0 404 Not Found');
            echo json_encode(['success' => false, 'message' => 'Action not found']);
            break;
    }
} 