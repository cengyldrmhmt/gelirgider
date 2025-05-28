<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/FinancialGoal.php';

class FinancialGoalController extends Controller {
    private $financialGoal;
    
    public function __construct() {
        $this->financialGoal = new FinancialGoal();
    }
    
    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /gelirgider/login');
            exit;
        }
        
        return $this->financialGoal->getAll($_SESSION['user_id']);
    }
    
    public function create() {
        if (!isset($_SESSION['user_id'])) {
            return $this->jsonResponse(['success' => false, 'message' => 'Oturum açmanız gerekiyor']);
        }
        
        $data = [
            'user_id' => $_SESSION['user_id'],
            'title' => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? '',
            'target_amount' => $_POST['target_amount'] ?? 0,
            'current_amount' => $_POST['current_amount'] ?? 0,
            'target_date' => $_POST['target_date'] ?? '',
            'category_id' => $_POST['category_id'] ?: null,
            'wallet_id' => $_POST['wallet_id'] ?: null,
            'status' => $_POST['status'] ?? 'active'
        ];
        
        if (empty($data['title']) || empty($data['target_amount']) || empty($data['target_date'])) {
            return $this->jsonResponse(['success' => false, 'message' => 'Lütfen gerekli alanları doldurun']);
        }
        
        if ($this->financialGoal->create($data)) {
            return $this->jsonResponse(['success' => true, 'message' => 'Hedef başarıyla oluşturuldu']);
        } else {
            return $this->jsonResponse(['success' => false, 'message' => 'Hedef oluşturulurken bir hata oluştu']);
        }
    }
    
    public function update() {
        if (!isset($_SESSION['user_id'])) {
            return $this->jsonResponse(['success' => false, 'message' => 'Oturum açmanız gerekiyor']);
        }
        
        $data = [
            'id' => $_POST['id'] ?? 0,
            'user_id' => $_SESSION['user_id'],
            'title' => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? '',
            'target_amount' => $_POST['target_amount'] ?? 0,
            'current_amount' => $_POST['current_amount'] ?? 0,
            'target_date' => $_POST['target_date'] ?? '',
            'category_id' => $_POST['category_id'] ?: null,
            'wallet_id' => $_POST['wallet_id'] ?: null,
            'status' => $_POST['status'] ?? 'active'
        ];
        
        if (empty($data['id']) || empty($data['title']) || empty($data['target_amount']) || empty($data['target_date'])) {
            return $this->jsonResponse(['success' => false, 'message' => 'Lütfen gerekli alanları doldurun']);
        }
        
        if ($this->financialGoal->update($data)) {
            return $this->jsonResponse(['success' => true, 'message' => 'Hedef başarıyla güncellendi']);
        } else {
            return $this->jsonResponse(['success' => false, 'message' => 'Hedef güncellenirken bir hata oluştu']);
        }
    }
    
    public function delete() {
        if (!isset($_SESSION['user_id'])) {
            return $this->jsonResponse(['success' => false, 'message' => 'Oturum açmanız gerekiyor']);
        }
        
        $id = $_POST['id'] ?? 0;
        
        if (empty($id)) {
            return $this->jsonResponse(['success' => false, 'message' => 'Geçersiz hedef ID']);
        }
        
        if ($this->financialGoal->delete($id, $_SESSION['user_id'])) {
            return $this->jsonResponse(['success' => true, 'message' => 'Hedef başarıyla silindi']);
        } else {
            return $this->jsonResponse(['success' => false, 'message' => 'Hedef silinirken bir hata oluştu']);
        }
    }
    
    public function get() {
        if (!isset($_SESSION['user_id'])) {
            return $this->jsonResponse(['success' => false, 'message' => 'Oturum açmanız gerekiyor']);
        }
        
        $id = $_GET['id'] ?? 0;
        
        if (empty($id)) {
            return $this->jsonResponse(['success' => false, 'message' => 'Geçersiz hedef ID']);
        }
        
        $goal = $this->financialGoal->getById($id, $_SESSION['user_id']);
        
        if ($goal) {
            return $this->jsonResponse(['success' => true, 'data' => $goal]);
        } else {
            return $this->jsonResponse(['success' => false, 'message' => 'Hedef bulunamadı']);
        }
    }

    protected function jsonResponse($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
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
    
    $controller = new FinancialGoalController();
    
    switch ($_GET['action']) {
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
        default:
            header('HTTP/1.0 404 Not Found');
            echo json_encode(['success' => false, 'message' => 'Action not found']);
            break;
    }
} 