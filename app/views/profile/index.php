<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /gelirgider/app/views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../controllers/DashboardController.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../layouts/header.php';

$db = Database::getInstance()->getConnection();
$userModel = new User();
$dashboardController = new DashboardController();

// Kullanıcı bilgilerini al
$user = $userModel->get($_SESSION['user_id']);

// İstatistikleri al
$stats = $dashboardController->getUserStats($_SESSION['user_id']);

// Son aktiviteler
try {
    $stmt = $db->prepare("
        SELECT message, created_at, level 
        FROM activity_logs 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Eğer activity_logs tablosu yoksa boş array döndür
    $activities = [];
    error_log("Activity logs table not found: " . $e->getMessage());
}

include '../layouts/sidebar.php';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/gelirgider/public/css/profile/style.css">
</head>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-user-circle"></i> Kullanıcı Profili
                    </h3>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                        <i class="fas fa-edit"></i> Profili Düzenle
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <!-- Profil Bilgileri -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="profile-avatar mb-3">
                        <img src="https://via.placeholder.com/150/2c3e50/ffffff?text=<?= strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)) ?>" 
                             class="rounded-circle" alt="Profil Fotoğrafı" width="150" height="150">
                    </div>
                    <h4><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h4>
                    <p class="text-muted"><?= htmlspecialchars($user['email']) ?></p>
                    <p class="badge bg-success">Aktif Kullanıcı</p>
                    <hr>
                    <div class="row text-center">
                        <div class="col-4">
                            <h5><?= $stats['total_transactions'] ?></h5>
                            <small class="text-muted">İşlem</small>
                        </div>
                        <div class="col-4">
                            <h5><?= $stats['wallet_count'] ?></h5>
                            <small class="text-muted">Cüzdan</small>
                        </div>
                        <div class="col-4">
                            <h5><?= number_format($stats['days_since_registration']) ?></h5>
                            <small class="text-muted">Gün</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hesap Bilgileri -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle"></i> Hesap Bilgileri
                    </h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li><strong>Üyelik Tarihi:</strong> <?= date('d.m.Y', strtotime($user['created_at'])) ?></li>
                        <li><strong>Son Giriş:</strong> <?= isset($user['last_login']) ? date('d.m.Y H:i', strtotime($user['last_login'])) : 'Bilinmiyor' ?></li>
                        <li><strong>Hesap Durumu:</strong> <span class="badge bg-success">Aktif</span></li>
                        <li><strong>Para Birimi:</strong> ₺ (TRY)</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- İstatistikler ve Grafikler -->
        <div class="col-md-8">
            <div class="row">
                <!-- Hızlı İstatistikler -->
                <div class="col-md-6 mb-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title">Toplam Gelir</h4>
                                    <h2><?= number_format($stats['total_income'], 2, ',', '.') ?> ₺</h2>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-arrow-up fa-3x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title">Toplam Gider</h4>
                                    <h2><?= number_format($stats['total_expense'], 2, ',', '.') ?> ₺</h2>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-arrow-down fa-3x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title">Net Durum</h4>
                                    <h2><?= number_format($stats['net_balance'], 2, ',', '.') ?> ₺</h2>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-balance-scale fa-3x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title">Aylık Ortalama</h4>
                                    <h2><?= number_format($stats['monthly_average'], 2, ',', '.') ?> ₺</h2>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-chart-line fa-3x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Son Aktiviteler -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-history"></i> Son Aktiviteler
                    </h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <?php foreach ($activities as $activity): ?>
                            <div class="timeline-item mb-3">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <span class="badge bg-<?= 
                                            $activity['level'] === 'ERROR' ? 'danger' : 
                                            ($activity['level'] === 'WARNING' ? 'warning' : 
                                            ($activity['level'] === 'ACTIVITY' ? 'success' : 'info')) 
                                        ?>">
                                            <?= strtolower($activity['level']) ?>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <p class="mb-1"><?= htmlspecialchars($activity['message']) ?></p>
                                        <small class="text-muted"><?= date('d.m.Y H:i', strtotime($activity['created_at'])) ?></small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Profil Düzenleme Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Profil Bilgilerini Düzenle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="profileForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Ad</label>
                                <input type="text" class="form-control" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Soyad</label>
                                <input type="text" class="form-control" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">E-posta</label>
                        <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                    <!-- Phone field commented out since it doesn't exist in current database schema
                    <div class="mb-3">
                        <label class="form-label">Telefon</label>
                        <input type="tel" class="form-control" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                    </div>
                    -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Yeni Şifre (Opsiyonel)</label>
                                <input type="password" class="form-control" name="new_password" placeholder="Değiştirmek istemiyorsanız boş bırakın">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Yeni Şifre Tekrar</label>
                                <input type="password" class="form-control" name="confirm_password" placeholder="Şifreyi tekrar giriniz">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" id="saveProfile">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/gelirgider/public/js/profile/script.js"></script>
</body>
</html> 