<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/Tag.php';

class TagController extends Controller {
    private $tagModel;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor.']);
                exit;
            } else {
                header('Location: /gelirgider/app/views/auth/login.php');
                exit;
            }
        }
        
        $this->tagModel = new Tag();
    }

    public function index() {
        $userId = $_SESSION['user_id'];
        return [
            'tags' => $this->tagModel->getAll($userId)
        ];
    }

    public function create() {
        ob_start();
        
        try {
            error_log("=== TAG CREATE OPERATION START ===");
            error_log("POST data: " . print_r($_POST, true));
            error_log("Session user_id: " . $_SESSION['user_id']);
            
            if (empty($_POST['name'])) {
                throw new Exception('Etiket adı gereklidir.');
            }

            $data = [
                'name' => $_POST['name'],
                'color' => $_POST['color'] ?? '#000000',
                'user_id' => $_SESSION['user_id']
            ];

            error_log("Data to be inserted: " . print_r($data, true));

            if ($this->tagModel->create($data)) {
                error_log("Tag created successfully");
                $_SESSION['success'] = 'Etiket başarıyla oluşturuldu.';
                ob_end_clean();
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Etiket başarıyla oluşturuldu.']);
            } else {
                error_log("Tag creation failed in model");
                throw new Exception('Etiket oluşturulurken bir hata oluştu.');
            }
        } catch (Exception $e) {
            error_log("Tag creation exception: " . $e->getMessage());
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        error_log("=== TAG CREATE OPERATION END ===");
        exit;
    }

    public function update() {
        ob_start();
        
        try {
            error_log("=== TAG UPDATE OPERATION START ===");
            error_log("POST data: " . print_r($_POST, true));
            error_log("Session user_id: " . $_SESSION['user_id']);
            
            if (empty($_POST['id']) || empty($_POST['name'])) {
                throw new Exception('Etiket ID ve adı gereklidir.');
            }

            $data = [
                'name' => $_POST['name'],
                'color' => $_POST['color'] ?? '#000000'
            ];

            error_log("Tag ID to update: " . $_POST['id']);
            error_log("Data to be updated: " . print_r($data, true));
            error_log("User ID: " . $_SESSION['user_id']);

            if ($this->tagModel->update($_POST['id'], $data, $_SESSION['user_id'])) {
                error_log("Tag updated successfully");
                $_SESSION['success'] = 'Etiket başarıyla güncellendi.';
                ob_end_clean();
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Etiket başarıyla güncellendi.']);
            } else {
                error_log("Tag update failed in model");
                throw new Exception('Etiket güncellenirken bir hata oluştu.');
            }
        } catch (Exception $e) {
            error_log("Tag update exception: " . $e->getMessage());
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        error_log("=== TAG UPDATE OPERATION END ===");
        exit;
    }

    public function delete() {
        ob_start();
        
        try {
            error_log("=== TAG DELETE OPERATION START ===");
            error_log("POST data: " . print_r($_POST, true));
            error_log("Session user_id: " . $_SESSION['user_id']);
            
            if (empty($_POST['id'])) {
                throw new Exception('Etiket ID gereklidir.');
            }

            error_log("Tag ID to delete: " . $_POST['id']);
            error_log("User ID: " . $_SESSION['user_id']);

            if ($this->tagModel->delete($_POST['id'], $_SESSION['user_id'])) {
                error_log("Tag deleted successfully");
                $_SESSION['success'] = 'Etiket başarıyla silindi.';
                ob_end_clean();
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Etiket başarıyla silindi.']);
            } else {
                error_log("Tag deletion failed in model");
                throw new Exception('Etiket silinirken bir hata oluştu.');
            }
        } catch (Exception $e) {
            error_log("Tag deletion exception: " . $e->getMessage());
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        error_log("=== TAG DELETE OPERATION END ===");
        exit;
    }

    public function get() {
        ob_start();
        
        try {
            if (empty($_GET['id'])) {
                throw new Exception('Etiket ID gereklidir.');
            }

            $tag = $this->tagModel->get($_GET['id'], $_SESSION['user_id']);
            
            if ($tag) {
                ob_end_clean();
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'data' => $tag]);
            } else {
                throw new Exception('Etiket bulunamadı.');
            }
        } catch (Exception $e) {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function getAll() {
        try {
            if (!isset($_SESSION['user_id'])) {
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor.']);
                    exit;
                } else {
                    return [];
                }
            }

            $tags = $this->tagModel->getAll($_SESSION['user_id']);
            
            // AJAX isteği ise JSON döndür
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'data' => $tags]);
                exit;
            }
            
            // Normal istek ise array döndür
            return $tags;
        } catch (Exception $e) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                exit;
            }
            return [];
        }
    }
}

// Handle request
if (isset($_GET['action'])) {
    $controller = new TagController();
    $action = $_GET['action'];
    
    switch ($action) {
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
        case 'getAll':
            $controller->getAll();
            break;
        default:
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Geçersiz işlem.']);
    }
    exit;
} 