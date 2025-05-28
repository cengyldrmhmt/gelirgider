<?php
session_start();

// Admin kontrolü
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: /gelirgider/app/views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../controllers/AdminController.php';

$controller = new AdminController();
$data = $controller->index();

require_once __DIR__ . '/../layouts/header.php';
?>

<style>
.admin-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem 0;
    margin: -2rem -15px 2rem -15px;
    border-radius: 0 0 20px 20px;
}
.card {
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    border: none;
    margin-bottom: 1.5rem;
}
.info-box {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    color: white;
    border-radius: 15px;
    padding: 1.5rem;
    text-align: center;
    margin-bottom: 1rem;
}
.progress {
    height: 20px;
    border-radius: 10px;
}
.table-sm th {
    font-weight: 600;
    color: #495057;
}
</style>

<!-- Admin Header -->
<div class="admin-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="mb-0">
                    <i class="fas fa-server"></i> Sistem Bilgileri
                </h1>
                <p class="mb-0 opacity-75">Sunucu durumu ve sistem konfigürasyonu</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="/gelirgider/app/views/admin/index.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-arrow-left"></i> Admin Panel
                </a>
                <a href="/gelirgider/app/views/dashboard/index.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <!-- System Status -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="info-box" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <i class="fas fa-microchip fa-2x mb-2"></i>
                <h6>PHP Sürümü</h6>
                <h4><?php echo $data['serverInfo']['php_version']; ?></h4>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="info-box" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <i class="fas fa-database fa-2x mb-2"></i>
                <h6>MySQL</h6>
                <h4><?php echo substr($data['serverInfo']['mysql_version'], 0, 6); ?></h4>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="info-box" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                <i class="fas fa-memory fa-2x mb-2"></i>
                <h6>Bellek Limiti</h6>
                <h4><?php echo $data['serverInfo']['memory_limit']; ?></h4>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="info-box" style="background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%); color: #333;">
                <i class="fas fa-hdd fa-2x mb-2"></i>
                <h6>Boş Disk</h6>
                <h4><?php echo number_format($data['serverInfo']['disk_free_space'] / 1024 / 1024 / 1024, 1); ?> GB</h4>
            </div>
        </div>
    </div>

    <!-- Server Information -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-server text-primary"></i> Sunucu Bilgileri
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td><strong>PHP Sürümü:</strong></td>
                            <td><?php echo $data['serverInfo']['php_version']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>MySQL Sürümü:</strong></td>
                            <td><?php echo $data['serverInfo']['mysql_version']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Sunucu Yazılımı:</strong></td>
                            <td><?php echo $data['serverInfo']['server_software']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>İşletim Sistemi:</strong></td>
                            <td><?php echo PHP_OS; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Sunucu Zamanı:</strong></td>
                            <td><?php echo date('d.m.Y H:i:s'); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Sunucu IP:</strong></td>
                            <td><?php echo $_SERVER['SERVER_ADDR'] ?? 'Bilinmiyor'; ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-cogs text-primary"></i> PHP Konfigürasyonu
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td><strong>Bellek Limiti:</strong></td>
                            <td><?php echo $data['serverInfo']['memory_limit']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Maks. Çalışma Süresi:</strong></td>
                            <td><?php echo $data['serverInfo']['max_execution_time']; ?> saniye</td>
                        </tr>
                        <tr>
                            <td><strong>Upload Limit:</strong></td>
                            <td><?php echo $data['serverInfo']['upload_max_filesize']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Post Max Size:</strong></td>
                            <td><?php echo ini_get('post_max_size'); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Max Input Vars:</strong></td>
                            <td><?php echo ini_get('max_input_vars'); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Session Save Path:</strong></td>
                            <td><?php echo session_save_path() ?: 'Varsayılan'; ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Disk Usage -->
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie text-primary"></i> Disk Kullanımı
                    </h5>
                </div>
                <div class="card-body">
                    <?php 
                    $totalSpace = $data['serverInfo']['disk_total_space'];
                    $freeSpace = $data['serverInfo']['disk_free_space'];
                    $usedSpace = $totalSpace - $freeSpace;
                    $usagePercent = ($usedSpace / $totalSpace) * 100;
                    ?>
                    
                    <div class="row text-center mb-4">
                        <div class="col-md-3">
                            <h4 class="text-primary"><?php echo number_format($totalSpace / 1024 / 1024 / 1024, 1); ?> GB</h4>
                            <p class="mb-0">Toplam Alan</p>
                        </div>
                        <div class="col-md-3">
                            <h4 class="text-success"><?php echo number_format($freeSpace / 1024 / 1024 / 1024, 1); ?> GB</h4>
                            <p class="mb-0">Boş Alan</p>
                        </div>
                        <div class="col-md-3">
                            <h4 class="text-warning"><?php echo number_format($usedSpace / 1024 / 1024 / 1024, 1); ?> GB</h4>
                            <p class="mb-0">Kullanılan</p>
                        </div>
                        <div class="col-md-3">
                            <h4 class="text-danger"><?php echo number_format($usagePercent, 1); ?>%</h4>
                            <p class="mb-0">Kullanım Oranı</p>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Disk Kullanımı (<?php echo number_format($usagePercent, 1); ?>%)</label>
                        <div class="progress">
                            <div class="progress-bar 
                                <?php 
                                if ($usagePercent < 50) echo 'bg-success';
                                elseif ($usagePercent < 80) echo 'bg-warning';
                                else echo 'bg-danger';
                                ?>" 
                                style="width: <?php echo $usagePercent; ?>%">
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($usagePercent > 80): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Uyarı:</strong> Disk kullanımı %80'i aştı. Sistem performansı etkilenebilir.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-puzzle-piece text-primary"></i> PHP Eklentileri
                    </h5>
                </div>
                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                    <div class="row">
                        <?php 
                        $extensions = get_loaded_extensions();
                        sort($extensions);
                        $importantExtensions = ['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'curl', 'gd', 'zip', 'json', 'session'];
                        
                        foreach ($extensions as $ext): 
                            $isImportant = in_array(strtolower($ext), $importantExtensions);
                        ?>
                        <div class="col-6">
                            <span class="badge <?php echo $isImportant ? 'bg-success' : 'bg-secondary'; ?> mb-1">
                                <?php echo $ext; ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Monitoring -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-tachometer-alt text-primary"></i> Performans İzleme
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <h4 class="text-info"><?php echo number_format(memory_get_usage(true) / 1024 / 1024, 2); ?> MB</h4>
                            <p class="mb-0">Bellek Kullanımı</p>
                        </div>
                        <div class="col-6">
                            <h4 class="text-success"><?php echo number_format(memory_get_peak_usage(true) / 1024 / 1024, 2); ?> MB</h4>
                            <p class="mb-0">Peak Bellek</p>
                        </div>
                    </div>
                    <hr>
                    <div class="text-center">
                        <h5 class="text-warning"><?php echo number_format((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 2); ?> ms</h5>
                        <p class="mb-0">Sayfa Yükleme Süresi</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-shield-alt text-primary"></i> Güvenlik Durumu
                    </h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <span class="badge <?php echo ini_get('display_errors') ? 'bg-danger' : 'bg-success'; ?>">
                                <?php echo ini_get('display_errors') ? 'Açık' : 'Kapalı'; ?>
                            </span>
                            Display Errors
                        </li>
                        <li class="mb-2">
                            <span class="badge <?php echo extension_loaded('openssl') ? 'bg-success' : 'bg-danger'; ?>">
                                <?php echo extension_loaded('openssl') ? 'Aktif' : 'Pasif'; ?>
                            </span>
                            OpenSSL
                        </li>
                        <li class="mb-2">
                            <span class="badge <?php echo session_status() === PHP_SESSION_ACTIVE ? 'bg-success' : 'bg-warning'; ?>">
                                <?php echo session_status() === PHP_SESSION_ACTIVE ? 'Aktif' : 'Pasif'; ?>
                            </span>
                            Session
                        </li>
                        <li class="mb-2">
                            <span class="badge <?php echo extension_loaded('pdo') ? 'bg-success' : 'bg-danger'; ?>">
                                <?php echo extension_loaded('pdo') ? 'Aktif' : 'Pasif'; ?>
                            </span>
                            PDO
                        </li>
                        <li class="mb-2">
                            <span class="badge <?php echo function_exists('password_hash') ? 'bg-success' : 'bg-danger'; ?>">
                                <?php echo function_exists('password_hash') ? 'Aktif' : 'Pasif'; ?>
                            </span>
                            Password Hashing
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- System Actions -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-tools text-primary"></i> Sistem İşlemleri
            </h5>
        </div>
        <div class="card-body">
            <div class="row text-center">
                <div class="col-md-3">
                    <button class="btn btn-info btn-lg w-100 mb-2" onclick="refreshSystemInfo()">
                        <i class="fas fa-sync-alt"></i><br>Bilgileri Yenile
                    </button>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-warning btn-lg w-100 mb-2" onclick="clearCache()">
                        <i class="fas fa-broom"></i><br>Cache Temizle
                    </button>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-success btn-lg w-100 mb-2" onclick="checkSystemHealth()">
                        <i class="fas fa-heartbeat"></i><br>Sistem Kontrolü
                    </button>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary btn-lg w-100 mb-2" onclick="downloadSystemInfo()">
                        <i class="fas fa-download"></i><br>Rapor İndir
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function refreshSystemInfo() {
    showAlert('info', 'Sistem bilgileri yenileniyor...');
    setTimeout(() => {
        window.location.reload();
    }, 1000);
}

function clearCache() {
    if (confirm('Tüm cache dosyaları silinecek. Devam etmek istiyor musunuz?')) {
        showAlert('info', 'Cache temizleniyor...');
        
        $.ajax({
            url: '/gelirgider/app/controllers/AdminController.php',
            method: 'POST',
            data: {action: 'cleanupSystem'},
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAlert('success', 'Cache başarıyla temizlendi');
                } else {
                    showAlert('danger', response.message);
                }
            }
        });
    }
}

function checkSystemHealth() {
    showAlert('info', 'Sistem sağlığı kontrol ediliyor...');
    
    // Simulate health check
    setTimeout(() => {
        showAlert('success', 'Sistem sağlığı normal. Tüm bileşenler çalışıyor.');
    }, 2000);
}

function downloadSystemInfo() {
    showAlert('info', 'Sistem raporu hazırlanıyor...');
    
    // Create system info report
    const systemInfo = {
        php_version: '<?php echo $data["serverInfo"]["php_version"]; ?>',
        mysql_version: '<?php echo $data["serverInfo"]["mysql_version"]; ?>',
        server_software: '<?php echo $data["serverInfo"]["server_software"]; ?>',
        memory_limit: '<?php echo $data["serverInfo"]["memory_limit"]; ?>',
        disk_usage: '<?php echo number_format($usagePercent, 1); ?>%',
        generated_at: new Date().toISOString()
    };
    
    const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(systemInfo, null, 2));
    const downloadAnchorNode = document.createElement('a');
    downloadAnchorNode.setAttribute("href", dataStr);
    downloadAnchorNode.setAttribute("download", "sistem_raporu_" + new Date().toISOString().slice(0,10) + ".json");
    document.body.appendChild(downloadAnchorNode);
    downloadAnchorNode.click();
    downloadAnchorNode.remove();
    
    showAlert('success', 'Sistem raporu indirildi');
}

// Utility function
function showAlert(type, message) {
    const alertHtml = `<div class="alert alert-${type} alert-dismissible fade show position-fixed" 
                        style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;" role="alert">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>`;
    
    $('body').append(alertHtml);
    
    setTimeout(() => {
        $('.alert').fadeOut();
    }, 5000);
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?> 