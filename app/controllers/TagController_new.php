<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Database.php';

class TagController extends Controller {
    private $db;

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
        
        $this->db = Database::getInstance()->getConnection();
    }

    public function index() {
        $userId = $_SESSION['user_id'];
        $stmt = $this->db->prepare("SELECT * FROM tags WHERE user_id = ? ORDER BY name ASC");
        $stmt->execute([$userId]);
        return [
            'tags' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ];
    }

    public function create() {
        header('Content-Type: application/json');
        
        try {
            error_log("=== NEW TAG CREATE START ===");
            error_log("POST data: " . print_r($_POST, true));
            
            $name = trim($_POST['name'] ?? '');
            $color = $_POST['color'] ?? '#007bff';
            $userId = $_SESSION['user_id'];
            
            if (empty($name)) {
                throw new Exception('Etiket adı gereklidir.');
            }
            
            // Check if tag name already exists for this user
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM tags WHERE user_id = ? AND name = ?");
            $stmt->execute([$userId, $name]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Bu etiket adı zaten kullanılıyor.');
            }
            
            // Insert new tag
            $sql = "INSERT INTO tags (user_id, name, color, created_at) VALUES (?, ?, ?, NOW())";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$userId, $name, $color]);
            
            if ($result) {
                $insertId = $this->db->lastInsertId();
                error_log("Tag created successfully with ID: " . $insertId);
                echo json_encode(['success' => true, 'message' => 'Etiket başarıyla oluşturuldu.', 'id' => $insertId]);
            } else {
                throw new Exception('Etiket oluşturulurken bir hata oluştu.');
            }
            
        } catch (Exception $e) {
            error_log("Tag creation error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        
        error_log("=== NEW TAG CREATE END ===");
        exit;
    }

    public function update() {
        header('Content-Type: application/json');
        
        try {
            error_log("=== NEW TAG UPDATE START ===");
            error_log("POST data: " . print_r($_POST, true));
            
            $id = intval($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $color = $_POST['color'] ?? '#007bff';
            $userId = $_SESSION['user_id'];
            
            if ($id <= 0) {
                throw new Exception('Geçersiz etiket ID.');
            }
            
            if (empty($name)) {
                throw new Exception('Etiket adı gereklidir.');
            }
            
            // Check if tag exists and belongs to user
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM tags WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $userId]);
            if ($stmt->fetchColumn() == 0) {
                throw new Exception('Etiket bulunamadı.');
            }
            
            // Check if new name conflicts with existing tags (excluding current tag)
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM tags WHERE user_id = ? AND name = ? AND id != ?");
            $stmt->execute([$userId, $name, $id]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Bu etiket adı zaten kullanılıyor.');
            }
            
            // Update tag
            $sql = "UPDATE tags SET name = ?, color = ?, updated_at = NOW() WHERE id = ? AND user_id = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$name, $color, $id, $userId]);
            
            if ($result && $stmt->rowCount() > 0) {
                error_log("Tag updated successfully");
                echo json_encode(['success' => true, 'message' => 'Etiket başarıyla güncellendi.']);
            } else {
                throw new Exception('Etiket güncellenirken bir hata oluştu.');
            }
            
        } catch (Exception $e) {
            error_log("Tag update error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        
        error_log("=== NEW TAG UPDATE END ===");
        exit;
    }

    public function delete() {
        header('Content-Type: application/json');
        
        try {
            error_log("=== NEW TAG DELETE START ===");
            error_log("POST data: " . print_r($_POST, true));
            
            $id = intval($_POST['id'] ?? 0);
            $userId = $_SESSION['user_id'];
            
            if ($id <= 0) {
                throw new Exception('Geçersiz etiket ID.');
            }
            
            // Check if tag exists and belongs to user
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM tags WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $userId]);
            if ($stmt->fetchColumn() == 0) {
                throw new Exception('Etiket bulunamadı.');
            }
            
            // Start transaction
            $this->db->beginTransaction();
            
            try {
                // Delete related transaction tags
                $stmt = $this->db->prepare("DELETE FROM transaction_tags WHERE tag_id = ?");
                $stmt->execute([$id]);
                
                // Delete related credit card transaction tags
                $stmt = $this->db->prepare("DELETE FROM credit_card_transaction_tags WHERE tag_id = ?");
                $stmt->execute([$id]);
                
                // Delete the tag itself
                $stmt = $this->db->prepare("DELETE FROM tags WHERE id = ? AND user_id = ?");
                $result = $stmt->execute([$id, $userId]);
                
                if ($result && $stmt->rowCount() > 0) {
                    $this->db->commit();
                    error_log("Tag deleted successfully");
                    echo json_encode(['success' => true, 'message' => 'Etiket başarıyla silindi.']);
                } else {
                    throw new Exception('Etiket silinirken bir hata oluştu.');
                }
                
            } catch (Exception $e) {
                $this->db->rollBack();
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log("Tag delete error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        
        error_log("=== NEW TAG DELETE END ===");
        exit;
    }

    public function get() {
        header('Content-Type: application/json');
        
        try {
            $id = intval($_GET['id'] ?? 0);
            $userId = $_SESSION['user_id'];
            
            if ($id <= 0) {
                throw new Exception('Geçersiz etiket ID.');
            }
            
            $stmt = $this->db->prepare("SELECT * FROM tags WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $userId]);
            $tag = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($tag) {
                echo json_encode(['success' => true, 'data' => $tag]);
            } else {
                throw new Exception('Etiket bulunamadı.');
            }
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        
        exit;
    }

    public function getAll() {
        try {
            $userId = $_SESSION['user_id'];
            $stmt = $this->db->prepare("SELECT * FROM tags WHERE user_id = ? ORDER BY name ASC");
            $stmt->execute([$userId]);
            $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
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