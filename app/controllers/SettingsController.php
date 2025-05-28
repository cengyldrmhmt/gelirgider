<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/Settings.php';
require_once __DIR__ . '/../models/User.php';

class SettingsController extends Controller {
    private $settingsModel;
    private $userModel;
    
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
        
        $this->settingsModel = new Settings();
        $this->userModel = new User();
    }
    
    public function index() {
        $userId = $_SESSION['user_id'];
        
        // Kullanıcı ayarlarını al
        $settings = $this->settingsModel->getByUser($userId);
        
        // Eğer ayarlar yoksa (boş array döndü), varsayılan ayarları oluştur
        if (empty($settings) || !isset($settings['currency'])) {
            $this->settingsModel->createDefaults($userId);
            $settings = $this->settingsModel->getByUser($userId);
        }
        
        // Kullanıcı bilgilerini al
        $user = $this->userModel->get($userId);
        
        return [
            'settings' => $settings,
            'user' => $user
        ];
    }
    
    public function update() {
        ob_start();
        
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Geçersiz istek metodu.');
            }
            
            $userId = $_SESSION['user_id'];
            
            // Güncelleme verilerini hazırla
            $updateData = [
                'currency' => $_POST['currency'] ?? 'TRY',
                'language' => $_POST['language'] ?? 'tr',
                'timezone' => $_POST['timezone'] ?? 'Europe/Istanbul',
                'date_format' => $_POST['date_format'] ?? 'd.m.Y',
                'theme' => $_POST['theme'] ?? 'light',
                'notifications_enabled' => isset($_POST['notifications_enabled']) ? 1 : 0,
                'email_notifications' => isset($_POST['email_notifications']) ? 1 : 0,
                'budget_alerts' => isset($_POST['budget_alerts']) ? 1 : 0,
                'expense_warnings' => isset($_POST['expense_warnings']) ? 1 : 0
            ];
            
            // Ayarları güncelle
            if ($this->settingsModel->updateByUser($userId, $updateData)) {
                // Aktiviteyi logla
                require_once __DIR__ . '/../core/Logger.php';
                $logger = Logger::getInstance();
                $logger->activity('Ayarlar güncellendi', [
                    'updated_settings' => array_keys($updateData)
                ]);
                
                ob_end_clean();
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true, 
                    'message' => 'Ayarlar başarıyla güncellendi.'
                ]);
            } else {
                throw new Exception('Ayarlar güncellenirken bir hata oluştu.');
            }
            
        } catch (Exception $e) {
            require_once __DIR__ . '/../core/Logger.php';
            $logger = Logger::getInstance();
            $logger->error('Ayar güncelleme hatası: ' . $e->getMessage());
            
            ob_end_clean();
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
    
    public function exportData() {
        ob_start();
        
        try {
            $userId = $_SESSION['user_id'];
            
            require_once __DIR__ . '/../core/Database.php';
            $db = Database::getInstance()->getConnection();
            
            // Kullanıcının tüm verilerini topla
            $data = [];
            
            // Kullanıcı bilgileri
            $stmt = $db->prepare("SELECT first_name, last_name, email, phone, created_at FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $data['user'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // İşlemler
            $stmt = $db->prepare("
                SELECT t.*, c.name as category_name, w.name as wallet_name 
                FROM transactions t 
                LEFT JOIN categories c ON t.category_id = c.id 
                LEFT JOIN wallets w ON t.wallet_id = w.id 
                WHERE t.user_id = ? 
                ORDER BY t.transaction_date DESC
            ");
            $stmt->execute([$userId]);
            $data['transactions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Kategoriler
            $stmt = $db->prepare("SELECT * FROM categories WHERE user_id = ? ORDER BY name");
            $stmt->execute([$userId]);
            $data['categories'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Cüzdanlar
            $stmt = $db->prepare("SELECT * FROM wallets WHERE user_id = ? ORDER BY name");
            $stmt->execute([$userId]);
            $data['wallets'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Bütçeler
            $stmt = $db->prepare("SELECT * FROM budgets WHERE user_id = ? ORDER BY name");
            $stmt->execute([$userId]);
            $data['budgets'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Finansal hedefler
            $stmt = $db->prepare("SELECT * FROM financial_goals WHERE user_id = ? ORDER BY name");
            $stmt->execute([$userId]);
            $data['financial_goals'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Ayarlar
            $stmt = $db->prepare("SELECT * FROM settings WHERE user_id = ?");
            $stmt->execute([$userId]);
            $data['settings'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // JSON olarak dışa aktar
            $filename = 'gelirgider_export_' . date('Y-m-d_H-i-s') . '.json';
            $exportData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            
            // Aktiviteyi logla
            require_once __DIR__ . '/../core/Logger.php';
            $logger = Logger::getInstance();
            $logger->activity('Veri dışa aktarıldı', [
                'filename' => $filename,
                'data_size' => strlen($exportData)
            ]);
            
            ob_end_clean();
            
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            echo $exportData;
            
        } catch (Exception $e) {
            require_once __DIR__ . '/../core/Logger.php';
            $logger = Logger::getInstance();
            $logger->error('Veri dışa aktarma hatası: ' . $e->getMessage());
            
            ob_end_clean();
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'Veri dışa aktarılırken hata oluştu: ' . $e->getMessage()
            ]);
        }
        exit;
    }
    
    public function clearData() {
        ob_start();
        
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Geçersiz istek metodu.');
            }
            
            $userId = $_SESSION['user_id'];
            $dataType = $_POST['data_type'] ?? '';
            
            require_once __DIR__ . '/../core/Database.php';
            $db = Database::getInstance()->getConnection();
            
            $db->beginTransaction();
            
            switch ($dataType) {
                case 'transactions':
                    $stmt = $db->prepare("DELETE FROM transactions WHERE user_id = ?");
                    $stmt->execute([$userId]);
                    $message = 'Tüm işlemler silindi.';
                    break;
                    
                case 'categories':
                    // Önce işlemlerdeki kategori referanslarını temizle
                    $stmt = $db->prepare("UPDATE transactions SET category_id = NULL WHERE user_id = ?");
                    $stmt->execute([$userId]);
                    // Sonra kategorileri sil
                    $stmt = $db->prepare("DELETE FROM categories WHERE user_id = ?");
                    $stmt->execute([$userId]);
                    $message = 'Tüm kategoriler silindi.';
                    break;
                    
                case 'all':
                    // Tüm veriyi sil (kullanıcı ve ayarlar hariç)
                    $tables = ['transactions', 'categories', 'wallets', 'budgets', 'financial_goals'];
                    foreach ($tables as $table) {
                        $stmt = $db->prepare("DELETE FROM {$table} WHERE user_id = ?");
                        $stmt->execute([$userId]);
                    }
                    $message = 'Tüm veriler silindi.';
                    break;
                    
                default:
                    throw new Exception('Geçersiz veri tipi.');
            }
            
            $db->commit();
            
            // Aktiviteyi logla
            require_once __DIR__ . '/../core/Logger.php';
            $logger = Logger::getInstance();
            $logger->activity('Veri temizlendi', [
                'data_type' => $dataType
            ]);
            
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'message' => $message
            ]);
            
        } catch (Exception $e) {
            if (isset($db)) {
                $db->rollBack();
            }
            
            require_once __DIR__ . '/../core/Logger.php';
            $logger = Logger::getInstance();
            $logger->error('Veri temizleme hatası: ' . $e->getMessage());
            
            ob_end_clean();
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
}

// Handle request
if (isset($_GET['action'])) {
    $controller = new SettingsController();
    $action = $_GET['action'];
    
    switch ($action) {
        case 'update':
            $controller->update();
            break;
        case 'exportData':
            $controller->exportData();
            break;
        case 'clearData':
            $controller->clearData();
            break;
        default:
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Geçersiz işlem.']);
    }
    exit;
} 