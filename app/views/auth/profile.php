<?php
session_start();
require_once __DIR__ . '/../../helpers/csrf.php';
// Fetch dynamic data from the database
$userData = []; // Replace with actual database query
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !csrf_check($_POST['csrf_token'])) {
        $_SESSION['error'] = 'Güvenlik doğrulaması başarısız oldu. Lütfen tekrar deneyin.';
    } else {
        // ... mevcut profil güncelleme işlemleri ...
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil | Gelir Gider Takip</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">
    <link href="/assets/css/theme.css" rel="stylesheet">
    <style>
        body { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: var(--bs-body-bg); }
        .profile-container { max-width: 400px; width: 100%; padding: 2rem; border-radius: 1rem; box-shadow: 0 0 24px rgba(0,0,0,0.08); background: var(--bs-body-bg); }
        .form-label { font-weight: 500; }
    </style>
</head>
<body>
    <div class="profile-container">
        <h2 class="mb-4 text-center">Profil</h2>
        <form method="post" action="/auth/profile">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
            <div class="mb-3">
                <label for="name" class="form-label">Ad</label>
                <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($userData['name'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">E-posta</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($userData['email'] ?? '') ?>" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Profili Güncelle</button>
        </form>
        <div class="mt-3 text-center">
            <span>Şifrenizi değiştirmek için <a href="/auth/reset">tıklayın</a></span>
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