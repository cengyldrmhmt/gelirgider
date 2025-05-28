<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/PaymentPlan.php';

class PaymentPlanController extends Controller {
    private $paymentPlanModel;
    
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            header('Location: /gelirgider/app/views/auth/login.php');
            exit;
        }
        
        $this->paymentPlanModel = new PaymentPlan();
    }
    
    public function index() {
        try {
            $userId = $_SESSION['user_id'];
            $plans = $this->paymentPlanModel->getAllPlans($userId);
            
            return [
                'title' => 'Ödeme Planları',
                'plans' => $plans
            ];
        } catch (Exception $e) {
            error_log("PaymentPlanController index error: " . $e->getMessage());
            return [
                'title' => 'Ödeme Planları',
                'plans' => [],
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function create() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Geçersiz istek metodu');
            }
            
            $userId = $_SESSION['user_id'];
            $data = [
                'user_id' => $userId,
                'title' => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? '',
                'total_amount' => floatval($_POST['total_amount'] ?? 0),
                'category_id' => !empty($_POST['category_id']) ? $_POST['category_id'] : null,
                'plan_type' => $_POST['plan_type'] ?? 'installment',
                'payment_method' => $_POST['payment_method'] ?? 'cash',
                'start_date' => $_POST['start_date'] ?? date('Y-m-d'),
                'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] : null,
                'notes' => $_POST['notes'] ?? ''
            ];
            
            // Validation
            if (empty($data['title']) || $data['total_amount'] <= 0) {
                throw new Exception('Başlık ve tutar gereklidir');
            }
            
            $planId = $this->paymentPlanModel->createPlan($data);
            
            // Ödeme planı detaylarını ekle
            if (!empty($_POST['items'])) {
                $items = json_decode($_POST['items'], true);
                foreach ($items as $index => $item) {
                    $itemData = [
                        'payment_plan_id' => $planId,
                        'item_order' => $index + 1,
                        'title' => $item['title'] ?? '',
                        'description' => $item['description'] ?? '',
                        'amount' => floatval($item['amount'] ?? 0),
                        'due_date' => $item['due_date'] ?? date('Y-m-d'),
                        'payment_method' => $item['payment_method'] ?? 'cash',
                        'wallet_id' => !empty($item['wallet_id']) ? $item['wallet_id'] : null,
                        'credit_card_id' => !empty($item['credit_card_id']) ? $item['credit_card_id'] : null,
                        'installment_count' => intval($item['installment_count'] ?? 1),
                        'notes' => $item['notes'] ?? ''
                    ];
                    
                    $this->paymentPlanModel->createPlanItem($itemData);
                }
            }
            
            // Log history
            $this->paymentPlanModel->addHistory($planId, null, 'created', null, json_encode($data), null, 'Ödeme planı oluşturuldu', $userId);
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Ödeme planı başarıyla oluşturuldu', 'plan_id' => $planId]);
            exit;
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
    
    public function update() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Geçersiz istek metodu');
            }
            
            $planId = $_POST['id'] ?? null;
            if (!$planId) {
                throw new Exception('Plan ID gereklidir');
            }
            
            $userId = $_SESSION['user_id'];
            $data = [
                'title' => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? '',
                'total_amount' => floatval($_POST['total_amount'] ?? 0),
                'category_id' => !empty($_POST['category_id']) ? $_POST['category_id'] : null,
                'plan_type' => $_POST['plan_type'] ?? 'installment',
                'payment_method' => $_POST['payment_method'] ?? 'cash',
                'start_date' => $_POST['start_date'] ?? date('Y-m-d'),
                'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] : null,
                'notes' => $_POST['notes'] ?? '',
                'status' => $_POST['status'] ?? 'pending'
            ];
            
            $this->paymentPlanModel->updatePlan($planId, $data, $userId);
            
            // Log history
            $this->paymentPlanModel->addHistory($planId, null, 'updated', null, json_encode($data), null, 'Ödeme planı güncellendi', $userId);
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Ödeme planı başarıyla güncellendi']);
            exit;
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
    
    public function makePayment() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Geçersiz istek metodu');
            }
            
            $itemId = $_POST['item_id'] ?? null;
            $amount = floatval($_POST['amount'] ?? 0);
            $paymentMethod = $_POST['payment_method'] ?? 'cash';
            $walletId = !empty($_POST['wallet_id']) ? $_POST['wallet_id'] : null;
            $creditCardId = !empty($_POST['credit_card_id']) ? $_POST['credit_card_id'] : null;
            $notes = $_POST['notes'] ?? '';
            
            if (!$itemId || $amount <= 0) {
                throw new Exception('Ödeme kalemi ID ve tutar gereklidir');
            }
            
            $userId = $_SESSION['user_id'];
            
            // Ödeme kalemini al
            $item = $this->paymentPlanModel->getPlanItem($itemId, $userId);
            if (!$item) {
                throw new Exception('Ödeme kalemi bulunamadı');
            }
            
            // İşlem oluştur
            $transactionId = null;
            $creditCardTransactionId = null;
            
            if ($paymentMethod === 'cash' && $walletId) {
                // Cüzdan işlemi oluştur
                require_once __DIR__ . '/../models/Transaction.php';
                $transactionModel = new Transaction();
                
                $transactionData = [
                    'user_id' => $userId,
                    'wallet_id' => $walletId,
                    'category_id' => null, // Ödeme planından kategori alınabilir
                    'type' => 'expense',
                    'amount' => $amount,
                    'description' => 'Ödeme Planı: ' . $item['plan_title'] . ' - ' . $item['title'],
                    'transaction_date' => date('Y-m-d H:i:s'),
                    'notes' => $notes
                ];
                
                $transactionId = $transactionModel->create($transactionData);
                
            } elseif ($paymentMethod === 'credit_card' && $creditCardId) {
                // Kredi kartı işlemi oluştur
                require_once __DIR__ . '/../core/Database.php';
                $db = Database::getInstance()->getConnection();
                
                $stmt = $db->prepare("INSERT INTO credit_card_transactions (user_id, credit_card_id, category_id, type, amount, description, transaction_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $userId,
                    $creditCardId,
                    null,
                    'purchase',
                    $amount,
                    'Ödeme Planı: ' . $item['plan_title'] . ' - ' . $item['title'],
                    date('Y-m-d H:i:s')
                ]);
                
                $creditCardTransactionId = $db->lastInsertId();
            }
            
            // Ödeme kalemini güncelle
            $this->paymentPlanModel->makePayment($itemId, $amount, $transactionId, $creditCardTransactionId, $notes, $userId);
            
            // Log history
            $this->paymentPlanModel->addHistory($item['payment_plan_id'], $itemId, 'payment_made', null, null, $amount, 'Ödeme yapıldı: ' . number_format($amount, 2) . ' TL', $userId);
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Ödeme başarıyla kaydedildi']);
            exit;
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
    
    public function getPlan() {
        try {
            $planId = $_GET['id'] ?? null;
            if (!$planId) {
                throw new Exception('Plan ID gereklidir');
            }
            
            $userId = $_SESSION['user_id'];
            $plan = $this->paymentPlanModel->getPlan($planId, $userId);
            
            if (!$plan) {
                throw new Exception('Ödeme planı bulunamadı');
            }
            
            $items = $this->paymentPlanModel->getPlanItems($planId, $userId);
            $plan['items'] = $items;
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => $plan]);
            exit;
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
    
    public function getUpcomingPayments() {
        try {
            $userId = $_SESSION['user_id'];
            $days = intval($_GET['days'] ?? 30);
            
            $upcomingPayments = $this->paymentPlanModel->getUpcomingPayments($userId, $days);
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => $upcomingPayments]);
            exit;
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
    
    public function getStatistics() {
        try {
            $userId = $_SESSION['user_id'];
            $stats = $this->paymentPlanModel->getStatistics($userId);
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => $stats]);
            exit;
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
    
    public function getPaymentSummary() {
        try {
            $userId = $_SESSION['user_id'];
            $summary = $this->paymentPlanModel->getPaymentSummary($userId);
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => $summary]);
            exit;
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
    
    public function delete() {
        try {
            // Session kontrolü
            if (!isset($_SESSION['user_id'])) {
                throw new Exception('Oturum açmanız gerekiyor');
            }
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Geçersiz istek metodu');
            }
            
            $planId = $_POST['id'] ?? null;
            if (!$planId) {
                throw new Exception('Plan ID gereklidir');
            }
            
            $userId = $_SESSION['user_id'];
            
            // Plan var mı kontrol et
            $plan = $this->paymentPlanModel->getPlan($planId, $userId);
            if (!$plan) {
                throw new Exception('Plan bulunamadı veya size ait değil');
            }
            
            // Log history before deletion
            $this->paymentPlanModel->addHistory($planId, null, 'status_changed', $plan['status'], 'cancelled', null, 'Ödeme planı iptal edildi', $userId);
            
            // Plan silme işlemi
            $result = $this->paymentPlanModel->deletePlan($planId, $userId);
            
            if (!$result) {
                throw new Exception('Plan silinirken bir hata oluştu');
            }
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Ödeme planı başarıyla silindi']);
            exit;
            
        } catch (Exception $e) {
            error_log("PaymentPlan delete error: " . $e->getMessage());
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    public function addItem() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Geçersiz istek metodu');
            }
            
            $planId = $_POST['payment_plan_id'] ?? null;
            if (!$planId) {
                throw new Exception('Plan ID gereklidir');
            }
            
            $userId = $_SESSION['user_id'];
            
            // Plan kontrolü
            $plan = $this->paymentPlanModel->getPlan($planId, $userId);
            if (!$plan) {
                throw new Exception('Plan bulunamadı');
            }
            
            // Sıra numarası hesapla
            $items = $this->paymentPlanModel->getPlanItems($planId, $userId);
            $itemOrder = count($items) + 1;
            
            $itemData = [
                'payment_plan_id' => $planId,
                'item_order' => $itemOrder,
                'title' => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? '',
                'amount' => floatval($_POST['amount'] ?? 0),
                'due_date' => $_POST['due_date'] ?? date('Y-m-d'),
                'payment_method' => $_POST['payment_method'] ?? 'cash',
                'wallet_id' => null,
                'credit_card_id' => null,
                'installment_count' => 1,
                'notes' => ''
            ];
            
            if (empty($itemData['title']) || $itemData['amount'] <= 0) {
                throw new Exception('Başlık ve tutar gereklidir');
            }
            
            $itemId = $this->paymentPlanModel->createPlanItem($itemData);
            
            // Log history
            $this->paymentPlanModel->addHistory($planId, $itemId, 'item_added', null, json_encode($itemData), $itemData['amount'], 'Ödeme detayı eklendi', $userId);
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Ödeme detayı başarıyla eklendi', 'item_id' => $itemId]);
            exit;
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
    
    public function updateItem() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Geçersiz istek metodu');
            }
            
            $itemId = $_POST['item_id'] ?? null;
            if (!$itemId) {
                throw new Exception('Ödeme detayı ID gereklidir');
            }
            
            $userId = $_SESSION['user_id'];
            
            // Item kontrolü
            $item = $this->paymentPlanModel->getPlanItem($itemId, $userId);
            if (!$item) {
                throw new Exception('Ödeme detayı bulunamadı');
            }
            
            // Ödeme yapılmış ise düzenleme yapılamaz
            if ($item['status'] === 'paid') {
                throw new Exception('Ödeme yapılmış detaylar düzenlenemez');
            }
            
            $data = [
                'title' => $_POST['title'] ?? $item['title'],
                'description' => $_POST['description'] ?? $item['description'],
                'amount' => floatval($_POST['amount'] ?? $item['amount']),
                'due_date' => $_POST['due_date'] ?? $item['due_date']
            ];
            
            if (empty($data['title']) || $data['amount'] <= 0) {
                throw new Exception('Başlık ve tutar gereklidir');
            }
            
            // Update item
            $sql = "UPDATE payment_plan_items SET 
                        title = ?, description = ?, amount = ?, due_date = ?,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?";
            
            require_once __DIR__ . '/../core/Database.php';
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $data['title'],
                $data['description'],
                $data['amount'],
                $data['due_date'],
                $itemId
            ]);
            
            // Update plan totals
            $this->paymentPlanModel->updatePlanTotals($item['payment_plan_id']);
            
            // Log history
            $this->paymentPlanModel->addHistory($item['payment_plan_id'], $itemId, 'item_updated', json_encode($item), json_encode($data), $data['amount'], 'Ödeme detayı güncellendi', $userId);
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Ödeme detayı başarıyla güncellendi']);
            exit;
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
    
    public function deleteItem() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Geçersiz istek metodu');
            }
            
            $itemId = $_POST['item_id'] ?? null;
            if (!$itemId) {
                throw new Exception('Ödeme detayı ID gereklidir');
            }
            
            $userId = $_SESSION['user_id'];
            
            // Item kontrolü
            $item = $this->paymentPlanModel->getPlanItem($itemId, $userId);
            if (!$item) {
                throw new Exception('Ödeme detayı bulunamadı');
            }
            
            // Ödeme yapılmış ise silinemez
            if ($item['status'] === 'paid') {
                throw new Exception('Ödeme yapılmış detaylar silinemez');
            }
            
            // Delete item
            require_once __DIR__ . '/../core/Database.php';
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("DELETE FROM payment_plan_items WHERE id = ?");
            $stmt->execute([$itemId]);
            
            // Update plan totals
            $this->paymentPlanModel->updatePlanTotals($item['payment_plan_id']);
            
            // Log history
            $this->paymentPlanModel->addHistory($item['payment_plan_id'], $itemId, 'item_deleted', json_encode($item), null, null, 'Ödeme detayı silindi', $userId);
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Ödeme detayı başarıyla silindi']);
            exit;
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
}

// Handle AJAX requests
if (isset($_GET['action'])) {
    $controller = new PaymentPlanController();
    $action = $_GET['action'];
    
    switch ($action) {
        case 'create':
            $controller->create();
            break;
        case 'update':
            $controller->update();
            break;
        case 'makePayment':
            $controller->makePayment();
            break;
        case 'getPlan':
            $controller->getPlan();
            break;
        case 'getUpcomingPayments':
            $controller->getUpcomingPayments();
            break;
        case 'getStatistics':
            $controller->getStatistics();
            break;
        case 'getPaymentSummary':
            $controller->getPaymentSummary();
            break;
        case 'delete':
            $controller->delete();
            break;
        case 'addItem':
            $controller->addItem();
            break;
        case 'updateItem':
            $controller->updateItem();
            break;
        case 'deleteItem':
            $controller->deleteItem();
            break;
        default:
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Geçersiz işlem']);
            break;
    }
    exit;
}
?> 