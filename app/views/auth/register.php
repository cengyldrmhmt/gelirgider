<?php
session_start();
require_once __DIR__ . '/../../helpers/csrf.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: /gelirgider/app/views/dashboard/index.php');
    exit;
}

require_once __DIR__ . '/../../core/Database.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !csrf_check($_POST['csrf_token'])) {
        $errors[] = 'Güvenlik doğrulaması başarısız oldu. Lütfen tekrar deneyin.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $surname = trim($_POST['surname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

        // Validation
        if (empty($name)) {
            $errors[] = 'Ad alanı zorunludur.';
        }
        if (empty($surname)) {
            $errors[] = 'Soyad alanı zorunludur.';
        }
        if (empty($email)) {
            $errors[] = 'E-posta alanı zorunludur.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Geçerli bir e-posta adresi giriniz.';
        }
        if (empty($password)) {
            $errors[] = 'Şifre alanı zorunludur.';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Şifre en az 6 karakter olmalıdır.';
        }
        if ($password !== $password_confirm) {
            $errors[] = 'Şifreler eşleşmiyor.';
        }

        if (empty($errors)) {
            try {
                $db = Database::getInstance();
                
                // Check if email already exists
                $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $errors[] = 'Bu e-posta adresi zaten kayıtlı.';
                } else {
                    // Create user
                    $stmt = $db->prepare("
                        INSERT INTO users (name, surname, email, password, email_verified) 
                        VALUES (?, ?, ?, ?, 0)
                    ");
                    $stmt->execute([
                        $name,
                        $surname,
                        $email,
                        password_hash($password, PASSWORD_DEFAULT)
                    ]);

                    // Create default categories for the user
                    $user_id = $db->lastInsertId();
                    $default_categories = [
                        ['Maaş', 'income', '#28a745', 'money-bill'],
                        ['Diğer Gelir', 'income', '#17a2b8', 'plus-circle'],
                        ['Market', 'expense', '#dc3545', 'shopping-cart'],
                        ['Faturalar', 'expense', '#ffc107', 'file-invoice'],
                        ['Ulaşım', 'expense', '#6f42c1', 'car'],
                        ['Sağlık', 'expense', '#20c997', 'heartbeat'],
                        ['Eğlence', 'expense', '#fd7e14', 'film'],
                        ['Diğer Gider', 'expense', '#6c757d', 'ellipsis-h']
                    ];

                    $stmt = $db->prepare("
                        INSERT INTO categories (user_id, name, type, color, icon, is_default) 
                        VALUES (?, ?, ?, ?, ?, 1)
                    ");

                    foreach ($default_categories as $category) {
                        $stmt->execute([$user_id, $category[0], $category[1], $category[2], $category[3]]);
                    }

                    // Create default wallet
                    $stmt = $db->prepare("
                        INSERT INTO wallets (user_id, name, type, currency, balance, is_default) 
                        VALUES (?, 'Nakit', 'cash', 'TRY', 0.00, 1)
                    ");
                    $stmt->execute([$user_id]);

                    $success = true;
                    $_SESSION['success'] = 'Kayıt başarılı! Giriş yapabilirsiniz.';
                    header('Location: login.php');
                    exit;
                }
            } catch (Exception $e) {
                $errors[] = 'Bir hata oluştu. Lütfen daha sonra tekrar deneyin.';
                error_log($e->getMessage());
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol | Gelir Gider Takip</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/gelirgider/public/css/auth/style.css">
</head>
<body>
    <div class="container">
        <div class="register-container mx-auto">
            <h2 class="text-center mb-4">Kayıt Ol</h2>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Ad</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="surname" class="form-label">Soyad</label>
                        <input type="text" class="form-control" id="surname" name="surname" value="<?php echo htmlspecialchars($_POST['surname'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">E-posta</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Şifre</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <div class="password-requirements mt-1">
                        Şifreniz en az 6 karakter uzunluğunda olmalıdır.
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password_confirm" class="form-label">Şifre Tekrar</label>
                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Kayıt Ol</button>
                    <a href="login.php" class="btn btn-link">Zaten hesabınız var mı? Giriş yapın</a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/gelirgider/public/js/auth/script.js"></script>
</body>
</html> 