<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/CSRF.php';

class AuthController extends Controller {
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $user = User::findByEmail($email);
            if ($user && password_verify($password, $user['password'])) {
                Auth::login($user);
                $this->redirect('/');
            } else {
                $this->view('auth/login', ['error' => 'E-posta veya şifre hatalı']);
            }
        } else {
            $this->view('auth/login');
        }
    }
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? '';
            $surname = $_POST['surname'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $user = User::findByEmail($email);
            if ($user) {
                $this->view('auth/register', ['error' => 'Bu e-posta zaten kayıtlı']);
            } else {
                $id = User::create($name, $surname, $email, $password);
                if ($id) {
                    $this->redirect('/login');
                } else {
                    $this->view('auth/register', ['error' => 'Kayıt başarısız']);
                }
            }
        } else {
            $this->view('auth/register');
        }
    }
    public function logout() {
        Auth::logout();
        $this->redirect('/login');
    }
    public function profile() {
        if (!Auth::check()) $this->redirect('/login');
        $user = Auth::user();
        $this->view('auth/profile', ['user' => $user]);
    }
    public function forgot() {
        $this->view('auth/forgot');
    }
    public function reset() {
        $this->view('auth/reset');
    }
    public function updateProfile() {
        ob_start();
        
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            if (!isset($_SESSION['user_id'])) {
                throw new Exception('Oturum açmanız gerekiyor.');
            }
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Geçersiz istek metodu.');
            }
            
            // Gerekli alanlar kontrolü
            $requiredFields = ['first_name', 'last_name', 'email'];
            foreach ($requiredFields as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("'{$field}' alanı zorunludur.");
                }
            }
            
            $userModel = new User();
            $userId = $_SESSION['user_id'];
            
            // Mevcut kullanıcı bilgilerini al
            $currentUser = $userModel->get($userId);
            if (!$currentUser) {
                throw new Exception('Kullanıcı bulunamadı.');
            }
            
            // E-posta değişikliği kontrolü (başka kullanıcı tarafından kullanılıyor mu?)
            if ($_POST['email'] !== $currentUser['email']) {
                require_once __DIR__ . '/../core/Database.php';
                $db = Database::getInstance()->getConnection();
                $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$_POST['email'], $userId]);
                if ($stmt->fetch()) {
                    throw new Exception('Bu e-posta adresi başka bir kullanıcı tarafından kullanılıyor.');
                }
            }
            
            // Güncelleme verilerini hazırla (database column names kullan)
            $updateData = [
                'name' => $_POST['first_name'],
                'surname' => $_POST['last_name'],
                'email' => $_POST['email']
            ];
            
            // Şifre güncelleme
            if (!empty($_POST['new_password'])) {
                if (strlen($_POST['new_password']) < 6) {
                    throw new Exception('Şifre en az 6 karakter olmalıdır.');
                }
                if ($_POST['new_password'] !== $_POST['confirm_password']) {
                    throw new Exception('Şifreler eşleşmiyor.');
                }
                $updateData['password'] = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            }
            
            // Kullanıcı bilgilerini güncelle
            if ($userModel->update($userId, $updateData)) {
                // Session'daki kullanıcı adını güncelle
                $_SESSION['username'] = $updateData['name'] . ' ' . $updateData['surname'];
                
                // Aktiviteyi logla
                require_once __DIR__ . '/../core/Logger.php';
                $logger = Logger::getInstance();
                $logger->activity('Profil güncellendi', [
                    'updated_fields' => array_keys($updateData),
                    'email_changed' => $_POST['email'] !== $currentUser['email'],
                    'password_changed' => !empty($_POST['new_password'])
                ]);
                
                ob_end_clean();
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true, 
                    'message' => 'Profil başarıyla güncellendi.'
                ]);
            } else {
                throw new Exception('Profil güncellenirken bir hata oluştu.');
            }
            
        } catch (Exception $e) {
            // Hatayı logla
            require_once __DIR__ . '/../core/Logger.php';
            $logger = Logger::getInstance();
            $logger->error('Profil güncelleme hatası: ' . $e->getMessage());
            
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
    $controller = new AuthController();
    $action = $_GET['action'];
    
    switch ($action) {
        case 'login':
            $controller->login();
            break;
        case 'register':
            $controller->register();
            break;
        case 'logout':
            $controller->logout();
            break;
        case 'updateProfile':
            $controller->updateProfile();
            break;
        case 'forgot':
            $controller->forgot();
            break;
        case 'reset':
            $controller->reset();
            break;
        case 'profile':
            $controller->profile();
            break;
        default:
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Geçersiz işlem.']);
    }
    exit;
} 