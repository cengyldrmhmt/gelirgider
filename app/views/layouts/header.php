<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /gelirgider/app/views/auth/login.php');
    exit;
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

if ($current_dir === 'auth') {
    return;
}

if (!isset($_SESSION['notification_count']) || !isset($_SESSION['notification_count_time']) || 
    (time() - $_SESSION['notification_count_time']) > 300) {
    
    require_once __DIR__ . '/../../core/Database.php';
    require_once __DIR__ . '/../../controllers/NotificationController.php';
    
    $notificationController = new NotificationController();
    $_SESSION['notification_count'] = $notificationController->getUnreadCount();
    $_SESSION['latest_notifications'] = $notificationController->getLatest();
    $_SESSION['notification_count_time'] = time();
}

$unreadCount = $_SESSION['notification_count'];
$latestNotifications = $_SESSION['latest_notifications'] ?? [];
?><!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
    <title>GELİRGİDER - Finansal Takip Sistemi</title>
    
    <!-- jQuery önce yüklensin -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Bootstrap CSS ve JS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Font Awesome ve Toastr -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    
    <!-- DataTables CSS ve JS -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="/gelirgider/public/css/layouts/header.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="/gelirgider/app/views/dashboard/index.php">
                <i class="fas fa-wallet"></i> GELİRGİDER
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
                    <li class="nav-item me-3">
                        <a class="nav-link btn btn-outline-danger btn-sm px-3" href="/gelirgider/app/views/admin/index.php" style="border-radius: 20px;">
                            <i class="fas fa-shield-alt"></i> Admin Panel
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item dropdown me-3">
                        <a class="nav-link dropdown-toggle position-relative" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bell"></i>
                            <?php if ($unreadCount > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?php echo $unreadCount; ?></span>
                            <?php endif; ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationsDropdown" style="width: 350px; max-height: 400px; overflow-y: auto;">
                            <h6 class="dropdown-header sticky-top bg-white">
                                <i class="fas fa-bell"></i> Bildirimler 
                                <?php if ($unreadCount > 0): ?>
                                    <span class="badge bg-danger ms-1"><?php echo $unreadCount; ?></span>
                                <?php endif; ?>
                            </h6>
                            
                            <?php if (empty($latestNotifications)): ?>
                                <div class="dropdown-item text-center text-muted py-3">
                                    <i class="fas fa-bell-slash"></i><br>
                                    <small>Henüz bildirim yok</small>
                                </div>
                            <?php else: ?>
                                <?php foreach (array_slice($latestNotifications, 0, 10) as $notification): ?>
                                    <div class="dropdown-item <?php echo $notification['is_read'] ? '' : 'bg-light'; ?>" style="border-left: 3px solid <?php 
                                        $borderColors = [
                                            'info' => '#17a2b8',
                                            'success' => '#28a745', 
                                            'warning' => '#ffc107',
                                            'error' => '#dc3545'
                                        ];
                                        echo $borderColors[$notification['type']] ?? '#17a2b8';
                                    ?>;">
                                        <div class="d-flex align-items-center">
                                            <div class="me-2">
                                                <?php
                                                $typeIcon = [
                                                    'info' => 'fas fa-info-circle text-info',
                                                    'success' => 'fas fa-check-circle text-success',
                                                    'warning' => 'fas fa-exclamation-triangle text-warning',
                                                    'error' => 'fas fa-times-circle text-danger'
                                                ];
                                                $type = $notification['type'] ?? 'info';
                                                ?>
                                                <i class="<?php echo $typeIcon[$type] ?? 'fas fa-info-circle text-info'; ?>"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <?php
                                                // Mesajdan başlığı çıkar (: karakterine kadar olan kısım)
                                                $message = $notification['message'];
                                                $colonPos = strpos($message, ':');
                                                if ($colonPos !== false) {
                                                    $title = substr($message, 0, $colonPos);
                                                    $content = trim(substr($message, $colonPos + 1));
                                                } else {
                                                    $title = $message;
                                                    $content = '';
                                                }
                                                ?>
                                                <div class="fw-bold small text-truncate" title="<?php echo htmlspecialchars($title); ?>">
                                                    <?php echo htmlspecialchars($title); ?>
                                                </div>
                                                <div class="small text-muted">
                                                    <i class="fas fa-clock"></i> <?php echo date('d.m H:i', strtotime($notification['created_at'])); ?>
                                                </div>
                                            </div>
                                            <?php if (!$notification['is_read']): ?>
                                                <div class="ms-2">
                                                    <span class="badge bg-primary rounded-pill" style="width: 8px; height: 8px; padding: 0;"></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            
                            <div class="dropdown-divider"></div>
                            <div class="dropdown-item text-center sticky-bottom bg-white">
                                <a href="/gelirgider/app/views/notifications/index.php" class="text-decoration-none">
                                    <i class="fas fa-list"></i> Tüm bildirimleri görüntüle
                                </a>
                            </div>
                        </div>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle"></i> <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Kullanıcı'; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="/gelirgider/app/views/profile/index.php">
                                <i class="fas fa-user"></i> Profil
                            </a></li>
                            <li><a class="dropdown-item" href="/gelirgider/app/views/settings/index.php">
                                <i class="fas fa-cog"></i> Ayarlar
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/gelirgider/app/views/auth/logout.php">
                                <i class="fas fa-sign-out-alt"></i> Çıkış Yap
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Bootstrap Yükleme Kontrolü -->
    <script>
    // Sadece Bootstrap'ın yüklendiğini kontrol et
    document.addEventListener('DOMContentLoaded', function() {
        console.log('✅ Bootstrap loaded:', typeof bootstrap !== 'undefined');
        console.log('✅ jQuery loaded:', typeof $ !== 'undefined');
        
        // Dropdown elementleri mevcut mu kontrol et
        var dropdowns = document.querySelectorAll('.dropdown-toggle');
        console.log('✅ Dropdown elements found:', dropdowns.length);
        
        // Bootstrap dropdown'larının otomatik çalıştığını belirt
        console.log('ℹ️ Bootstrap dropdowns are auto-initialized');
    });
    </script>
    
    <div class="container-fluid">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?> 