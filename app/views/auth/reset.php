<?php
session_start();
require_once __DIR__ . '/../../helpers/csrf.php';
// Fetch dynamic data from the database
$userData = []; // Replace with actual database query
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !csrf_check($_POST['csrf_token'])) {
        $_SESSION['error'] = 'Güvenlik doğrulaması başarısız oldu. Lütfen tekrar deneyin.';
    } else {
        // ... mevcut şifre sıfırlama işlemleri ...
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Şifre Sıfırla | Gelir Gider Takip</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">
    <link href="/assets/css/theme.css" rel="stylesheet">
    <style>
        body { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: var(--bs-body-bg); }
        .reset-container { max-width: 400px; width: 100%; padding: 2rem; border-radius: 1rem; box-shadow: 0 0 24px rgba(0,0,0,0.08); background: var(--bs-body-bg); }
        .form-label { font-weight: 500; }
    </style>
</head>
<body>
    <div class="reset-container">
        <h2 class="mb-4 text-center">Şifre Sıfırla</h2>
        <form method="post" action="/auth/reset">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
            <div class="mb-3">
                <label for="password" class="form-label">Yeni Şifre</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="mb-3">
                <label for="password_confirm" class="form-label">Yeni Şifre Tekrar</label>
                <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Şifremi Sıfırla</button>
        </form>
        <div class="mt-3 text-center">
            <span>Giriş yapmak için <a href="/auth/login">tıklayın</a></span>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        <?php if (isset($_SESSION['success'])): ?>
            toastr.success('<?= addslashes($_SESSION['success']) ?>');
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            toastr.error('<?= addslashes($_SESSION['error']) ?>');
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
    </script>
</body>
</html> 