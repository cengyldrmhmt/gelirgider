<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear any existing session data
session_unset();
session_destroy();
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: /gelirgider/app/views/dashboard/index.php');
    exit;
}

require_once __DIR__ . '/../../core/Database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Debug: Log POST data
    error_log("Login attempt - Email: " . $email);
    
    if (empty($email) || empty($password)) {
        $error = 'Lütfen tüm alanları doldurun.';
        error_log("Login error: Empty fields");
    } else {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Debug: Log user query result
            error_log("User query result: " . ($user ? "User found" : "User not found"));
            
            if ($user && password_verify($password, $user['password'])) {
                // Debug: Log successful password verification
                error_log("Password verified successfully");
                
                // Start a new session
                session_regenerate_id(true);
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['name'] . ' ' . $user['surname'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['is_admin'] = $user['is_admin'];
                
                // Debug: Log session variables
                error_log("Session variables set: " . print_r($_SESSION, true));
                
                // Update last login
                $stmt = $db->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                // Ensure session is written
                session_write_close();
                
                // Debug: Log redirect attempt
                error_log("Attempting redirect to dashboard");
                
                // Redirect to dashboard
                header('Location: /gelirgider/app/views/dashboard/index.php');
                exit;
            } else {
                $error = 'Geçersiz e-posta veya şifre.';
                error_log("Login error: Invalid credentials");
            }
        } catch (Exception $e) {
            $error = 'Bir hata oluştu: ' . $e->getMessage();
            error_log("Login error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - Gelir Gider Takip</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/gelirgider/public/css/auth/style.css">
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Giriş Yap</h4>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="email" class="form-label">E-posta</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Şifre</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Giriş Yap</button>
                    </form>
                    
                    <div class="register-link">
                        <p class="mb-0">Hesabınız yok mu? <a href="register.php">Kayıt Ol</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/gelirgider/public/js/auth/script.js"></script>
</body>
</html> 