<?php
// Check if session is already active before starting
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    if (isset($_GET['action'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor.']);
        exit;
    }
}

// Check if user is admin (admin role check)
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    if (isset($_GET['action'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Bu işlem için admin yetkisi gereklidir.']);
        exit;
    } else {
        header('Location: /gelirgider/app/views/auth/login.php');
        exit;
    }
}

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Database.php';

class AdminController extends Controller {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    // Dashboard ana sayfası
    public function index() {
        $data = [
            'systemStats' => $this->getSystemStats(),
            'userStats' => $this->getUserStats(),
            'transactionStats' => $this->getTransactionStats(),
            'recentUsers' => $this->getRecentUsers(),
            'systemLogs' => $this->getSystemLogs(),
            'serverInfo' => $this->getServerInfo()
        ];
        
        return $data;
    }
    
    // Sistem istatistikleri
    private function getSystemStats() {
        $sql = "SELECT 
                    (SELECT COUNT(*) FROM users) as total_users,
                    (SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()) as new_users_today,
                    (SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as new_users_week,
                    (SELECT COUNT(*) FROM transactions) as total_transactions,
                    (SELECT COUNT(*) FROM credit_card_transactions) as total_cc_transactions,
                    (SELECT COUNT(*) FROM wallets) as total_wallets,
                    (SELECT COUNT(*) FROM credit_cards) as total_credit_cards,
                    (SELECT COUNT(*) FROM categories) as total_categories,
                    (SELECT COUNT(*) FROM payment_plans) as total_payment_plans,
                    (SELECT COALESCE(SUM(balance), 0) FROM wallets) as total_wallet_balance";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Kullanıcı istatistikleri
    private function getUserStats() {
        $sql = "SELECT 
                    u.id,
                    u.username,
                    u.email,
                    u.full_name,
                    u.is_admin,
                    u.created_at,
                    COUNT(DISTINCT w.id) as wallet_count,
                    COUNT(DISTINCT t.id) as transaction_count,
                    COUNT(DISTINCT cc.id) as credit_card_count,
                    COALESCE(SUM(w.balance), 0) as total_balance
                FROM users u
                LEFT JOIN wallets w ON u.id = w.user_id
                LEFT JOIN transactions t ON u.id = t.user_id
                LEFT JOIN credit_cards cc ON u.id = cc.user_id
                GROUP BY u.id
                ORDER BY u.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // İşlem istatistikleri
    private function getTransactionStats() {
        $sql = "SELECT 
                    DATE(transaction_date) as date,
                    COUNT(*) as transaction_count,
                    SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
                    SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense
                FROM transactions 
                WHERE transaction_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(transaction_date)
                ORDER BY date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Son kullanıcılar
    private function getRecentUsers($limit = 10) {
        $sql = "SELECT 
                    id, username, email, full_name, is_admin, created_at,
                    (SELECT COUNT(*) FROM transactions WHERE user_id = users.id) as transaction_count
                FROM users 
                ORDER BY created_at DESC 
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Sistem logları (basit activity log)
    private function getSystemLogs($limit = 20) {
        // Activity logs tablosu yoksa boş array döndür
        try {
            $sql = "SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Sunucu bilgileri
    private function getServerInfo() {
        return [
            'php_version' => phpversion(),
            'mysql_version' => $this->db->getAttribute(PDO::ATTR_SERVER_VERSION),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'max_execution_time' => ini_get('max_execution_time'),
            'memory_limit' => ini_get('memory_limit'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'disk_free_space' => disk_free_space('.'),
            'disk_total_space' => disk_total_space('.')
        ];
    }
    
    // Kullanıcı yönetimi
    public function getUsers() {
        return $this->getUserStats();
    }
    
    public function createUser() {
        try {
            $username = $_POST['username'];
            $email = $_POST['email'];
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $fullName = $_POST['full_name'];
            $isAdmin = isset($_POST['is_admin']) ? 1 : 0;
            
            $sql = "INSERT INTO users (username, email, password, full_name, is_admin, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$username, $email, $password, $fullName, $isAdmin]);
            
            $this->logActivity('user_created', "Yeni kullanıcı oluşturuldu: $username");
            
            echo json_encode(['success' => true, 'message' => 'Kullanıcı başarıyla oluşturuldu']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Kullanıcı oluşturulurken hata: ' . $e->getMessage()]);
        }
    }
    
    public function updateUser() {
        try {
            $userId = $_POST['user_id'];
            $username = $_POST['username'];
            $email = $_POST['email'];
            $fullName = $_POST['full_name'];
            $isAdmin = isset($_POST['is_admin']) ? 1 : 0;
            
            $sql = "UPDATE users SET username = ?, email = ?, full_name = ?, is_admin = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$username, $email, $fullName, $isAdmin, $userId]);
            
            // Şifre değişikliği varsa
            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $sql = "UPDATE users SET password = ? WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$password, $userId]);
            }
            
            $this->logActivity('user_updated', "Kullanıcı güncellendi: $username");
            
            echo json_encode(['success' => true, 'message' => 'Kullanıcı başarıyla güncellendi']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Kullanıcı güncellenirken hata: ' . $e->getMessage()]);
        }
    }

    public function getUser() {
        try {
            $userId = $_GET['user_id'];
            
            $sql = "SELECT id, username, email, full_name, is_admin, created_at FROM users WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                echo json_encode(['success' => true, 'data' => $user]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Kullanıcı bulunamadı']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Kullanıcı bilgileri alınırken hata: ' . $e->getMessage()]);
        }
    }

    public function getUserDetails() {
        try {
            $userId = $_GET['user_id'];
            
            $sql = "SELECT 
                        u.id, u.username, u.email, u.full_name, u.is_admin, u.created_at,
                        COUNT(DISTINCT w.id) as wallet_count,
                        COUNT(DISTINCT t.id) as transaction_count,
                        COUNT(DISTINCT cc.id) as credit_card_count,
                        COUNT(DISTINCT c.id) as category_count,
                        COALESCE(SUM(w.balance), 0) as total_balance
                    FROM users u
                    LEFT JOIN wallets w ON u.id = w.user_id
                    LEFT JOIN transactions t ON u.id = t.user_id
                    LEFT JOIN credit_cards cc ON u.id = cc.user_id
                    LEFT JOIN categories c ON u.id = c.user_id
                    WHERE u.id = ?
                    GROUP BY u.id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                echo json_encode(['success' => true, 'data' => $user]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Kullanıcı bulunamadı']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Kullanıcı detayları alınırken hata: ' . $e->getMessage()]);
        }
    }
    
    public function deleteUser() {
        try {
            $userId = $_POST['user_id'];
            
            // Admin kullanıcıyı silemez
            if ($userId == $_SESSION['user_id']) {
                throw new Exception('Kendi hesabınızı silemezsiniz');
            }
            
            // Kullanıcının verilerini sil
            $this->db->beginTransaction();
            
            // İlişkili verileri sil
            $tables = ['transactions', 'wallets', 'credit_cards', 'credit_card_transactions', 
                      'payment_plans', 'payment_plan_items', 'categories'];
            
            foreach ($tables as $table) {
                $sql = "DELETE FROM $table WHERE user_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$userId]);
            }
            
            // Kullanıcıyı sil
            $sql = "DELETE FROM users WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            
            $this->db->commit();
            
            $this->logActivity('user_deleted', "Kullanıcı silindi: ID $userId");
            
            echo json_encode(['success' => true, 'message' => 'Kullanıcı ve tüm verileri başarıyla silindi']);
        } catch (Exception $e) {
            $this->db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Kullanıcı silinirken hata: ' . $e->getMessage()]);
        }
    }
    
    // Site ayarları
    public function getSiteSettings() {
        try {
            $sql = "SELECT * FROM site_settings ORDER BY setting_key";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $formattedSettings = [];
            foreach ($settings as $setting) {
                $formattedSettings[$setting['setting_key']] = $setting['setting_value'];
            }
            
            return $formattedSettings;
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function updateSiteSettings() {
        try {
            $this->db->beginTransaction();
            
            foreach ($_POST as $key => $value) {
                if ($key !== 'action' && $key !== 'ajax') {
                    $sql = "INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) 
                            ON DUPLICATE KEY UPDATE setting_value = ?";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute([$key, $value, $value]);
                }
            }
            
            $this->db->commit();
            $this->logActivity('settings_updated', 'Site ayarları güncellendi');
            
            echo json_encode(['success' => true, 'message' => 'Ayarlar başarıyla güncellendi']);
        } catch (Exception $e) {
            $this->db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Ayarlar güncellenirken hata: ' . $e->getMessage()]);
        }
    }
    
    // İkon yönetimi
    public function getIcons() {
        $icons = [
            'wallet' => 'Cüzdan',
            'money-bill' => 'Nakit',
            'university' => 'Banka',
            'piggy-bank' => 'Kumbara',
            'chart-line' => 'Yatırım',
            'coins' => 'Bozuk Para',
            'credit-card' => 'Kredi Kartı',
            'shopping-cart' => 'Alışveriş',
            'car' => 'Araç',
            'home' => 'Ev',
            'utensils' => 'Yemek',
            'gas-pump' => 'Yakıt',
            'phone' => 'Telefon',
            'bolt' => 'Elektrik',
            'tint' => 'Su',
            'wifi' => 'İnternet',
            'graduation-cap' => 'Eğitim',
            'heartbeat' => 'Sağlık',
            'gamepad' => 'Eğlence',
            'plane' => 'Seyahat',
            'gift' => 'Hediye',
            'wrench' => 'Tamir',
            'book' => 'Kitap',
            'music' => 'Müzik',
            'film' => 'Film',
            'coffee' => 'Kahve',
            'pizza-slice' => 'Fast Food',
            'dumbbell' => 'Spor',
            'cut' => 'Kuaför',
            'paw' => 'Pet'
        ];
        
        return $icons;
    }
    
    public function addIcon() {
        try {
            $iconName = $_POST['icon_name'];
            $iconClass = $_POST['icon_class'];
            $description = $_POST['description'];
            
            $sql = "INSERT INTO custom_icons (icon_name, icon_class, description, created_at) VALUES (?, ?, ?, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$iconName, $iconClass, $description]);
            
            $this->logActivity('icon_added', "Yeni ikon eklendi: $iconName");
            
            echo json_encode(['success' => true, 'message' => 'İkon başarıyla eklendi']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'İkon eklenirken hata: ' . $e->getMessage()]);
        }
    }
    
    // Veritabanı yönetimi
    public function getDatabaseInfo() {
        try {
            $tables = [];
            
            $sql = "SHOW TABLE STATUS";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $tableStatus = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($tableStatus as $table) {
                $tableName = $table['Name'];
                
                // Tablo satır sayısı
                $sql = "SELECT COUNT(*) as row_count FROM `$tableName`";
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
                $rowCount = $stmt->fetch(PDO::FETCH_ASSOC)['row_count'];
                
                $tables[] = [
                    'name' => $tableName,
                    'rows' => $rowCount,
                    'size' => $table['Data_length'] + $table['Index_length'],
                    'engine' => $table['Engine'],
                    'collation' => $table['Collation']
                ];
            }
            
            return $tables;
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function backupDatabase() {
        try {
            $backupDir = __DIR__ . '/../../backups/';
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }
            
            $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
            $filepath = $backupDir . $filename;
            
            // Basit backup (mysqldump kullanımı önerilir)
            $tables = $this->getDatabaseInfo();
            $backup = '';
            
            foreach ($tables as $table) {
                $tableName = $table['name'];
                
                // Tablo yapısı
                $sql = "SHOW CREATE TABLE `$tableName`";
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
                $createTable = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $backup .= "\n\n-- Table: $tableName\n";
                $backup .= "DROP TABLE IF EXISTS `$tableName`;\n";
                $backup .= $createTable['Create Table'] . ";\n\n";
                
                // Tablo verileri
                $sql = "SELECT * FROM `$tableName`";
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($rows)) {
                    $backup .= "INSERT INTO `$tableName` VALUES \n";
                    $insertValues = [];
                    
                    foreach ($rows as $row) {
                        $values = array_map(function($value) {
                            return $this->db->quote($value);
                        }, array_values($row));
                        $insertValues[] = '(' . implode(', ', $values) . ')';
                    }
                    
                    $backup .= implode(",\n", $insertValues) . ";\n\n";
                }
            }
            
            file_put_contents($filepath, $backup);
            
            $this->logActivity('database_backup', "Veritabanı yedeklendi: $filename");
            
            echo json_encode(['success' => true, 'message' => 'Veritabanı başarıyla yedeklendi', 'filename' => $filename]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Yedekleme hatası: ' . $e->getMessage()]);
        }
    }
    
    // Aktivite logu
    private function logActivity($action, $description) {
        try {
            $sql = "INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $_SESSION['user_id'],
                $action,
                $description,
                $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ]);
        } catch (Exception $e) {
            // Log hatası sessizce geç
        }
    }
    
    // Sistem temizleme
    public function cleanupSystem() {
        try {
            $this->db->beginTransaction();
            
            $cleanupStats = [
                'deleted_logs' => 0,
                'deleted_temp_files' => 0,
                'optimized_tables' => 0
            ];
            
            // Eski logları temizle (30 günden eski)
            $sql = "DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $cleanupStats['deleted_logs'] = $stmt->rowCount();
            
            // Tabloları optimize et
            $tables = $this->getDatabaseInfo();
            foreach ($tables as $table) {
                $sql = "OPTIMIZE TABLE `{$table['name']}`";
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
                $cleanupStats['optimized_tables']++;
            }
            
            // Temp dosyaları temizle
            $tempDir = __DIR__ . '/../../temp/';
            if (is_dir($tempDir)) {
                $files = glob($tempDir . '*');
                foreach ($files as $file) {
                    if (is_file($file) && time() - filemtime($file) > 3600) { // 1 saat eski
                        unlink($file);
                        $cleanupStats['deleted_temp_files']++;
                    }
                }
            }
            
            $this->db->commit();
            $this->logActivity('system_cleanup', 'Sistem temizleme işlemi tamamlandı');
            
            echo json_encode(['success' => true, 'message' => 'Sistem temizleme tamamlandı', 'stats' => $cleanupStats]);
        } catch (Exception $e) {
            $this->db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Temizleme hatası: ' . $e->getMessage()]);
        }
    }
}

// URL tabanlı istekleri işle
if (isset($_GET['action'])) {
    $controller = new AdminController();
    
    switch ($_GET['action']) {
        case 'dashboard':
            echo json_encode(['success' => true, 'data' => $controller->index()]);
            break;
        case 'getUsers':
            echo json_encode(['success' => true, 'data' => $controller->getUsers()]);
            break;
        case 'createUser':
            $controller->createUser();
            break;
        case 'updateUser':
            $controller->updateUser();
            break;
        case 'deleteUser':
            $controller->deleteUser();
            break;
        case 'getUser':
            $controller->getUser();
            break;
        case 'getUserDetails':
            $controller->getUserDetails();
            break;
        case 'getSiteSettings':
            echo json_encode(['success' => true, 'data' => $controller->getSiteSettings()]);
            break;
        case 'updateSiteSettings':
            $controller->updateSiteSettings();
            break;
        case 'getIcons':
            echo json_encode(['success' => true, 'data' => $controller->getIcons()]);
            break;
        case 'addIcon':
            $controller->addIcon();
            break;
        case 'getDatabaseInfo':
            echo json_encode(['success' => true, 'data' => $controller->getDatabaseInfo()]);
            break;
        case 'backupDatabase':
            $controller->backupDatabase();
            break;
        case 'cleanupSystem':
            $controller->cleanupSystem();
            break;
        default:
            header('HTTP/1.0 404 Not Found');
            echo json_encode(['success' => false, 'message' => 'Action not found']);
            break;
    }
}
?> 