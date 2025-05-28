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
$tables = $controller->getDatabaseInfo();

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
    margin-bottom: 2rem;
    transition: transform 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.stats-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px;
}

.stats-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.btn {
    border-radius: 10px;
    padding: 12px 24px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.table {
    border-radius: 10px;
    overflow: hidden;
}

.table thead th {
    background: #f8f9fc;
    border: none;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.5px;
    color: #5a5c69;
}

.progress {
    height: 8px;
    border-radius: 10px;
}

.backup-item {
    background: #f8f9fc;
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 10px;
    transition: all 0.3s ease;
}

.backup-item:hover {
    background: #e3f2fd;
}
</style>

<div class="container-fluid">
    <!-- Admin Header -->
    <div class="admin-header text-center">
        <h1 class="mb-2"><i class="fas fa-database"></i> Veritabanı Yönetimi</h1>
        <p class="mb-0 opacity-75">Veritabanı tabloları, yedekleme ve optimizasyon işlemleri</p>
    </div>

    <!-- Database Statistics -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1 opacity-75">
                                Toplam Tablo
                            </div>
                            <div class="h4 mb-0 font-weight-bold" id="totalTables">
                                <?php echo count($tables); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="stats-icon">
                                <i class="fas fa-table"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1 opacity-75">
                                Toplam Kayıt
                            </div>
                            <div class="h4 mb-0 font-weight-bold" id="totalRecords">
                                <?php 
                                $totalRecords = array_sum(array_column($tables, 'rows'));
                                echo number_format($totalRecords);
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="stats-icon">
                                <i class="fas fa-list"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1 opacity-75">
                                Veritabanı Boyutu
                            </div>
                            <div class="h4 mb-0 font-weight-bold" id="totalSize">
                                <?php 
                                $totalSize = array_sum(array_column($tables, 'size'));
                                echo formatBytes($totalSize);
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="stats-icon">
                                <i class="fas fa-hdd"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1 opacity-75">
                                Son Yedekleme
                            </div>
                            <div class="h6 mb-0 font-weight-bold" id="lastBackup">
                                Henüz yok
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="stats-icon">
                                <i class="fas fa-download"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-bolt text-warning"></i> Hızlı İşlemler</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <button class="btn btn-primary btn-block w-100" onclick="createBackup()">
                                <i class="fas fa-download"></i> Yedek Al
                            </button>
                        </div>
                        <div class="col-md-3 mb-2">
                            <button class="btn btn-warning btn-block w-100" onclick="optimizeDatabase()">
                                <i class="fas fa-tools"></i> Optimizasyon
                            </button>
                        </div>
                        <div class="col-md-3 mb-2">
                            <button class="btn btn-info btn-block w-100" onclick="checkTables()">
                                <i class="fas fa-check-circle"></i> Tablo Kontrolü
                            </button>
                        </div>
                        <div class="col-md-3 mb-2">
                            <button class="btn btn-success btn-block w-100" onclick="refreshStats()">
                                <i class="fas fa-sync-alt"></i> İstatistikleri Yenile
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Database Tables -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-table text-primary"></i> Veritabanı Tabloları</h5>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" onclick="exportToCSV()">
                            <i class="fas fa-file-csv"></i> CSV
                        </button>
                        <button class="btn btn-outline-success" onclick="exportToExcel()">
                            <i class="fas fa-file-excel"></i> Excel
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="tablesTable">
                            <thead>
                                <tr>
                                    <th>Tablo Adı</th>
                                    <th>Kayıt Sayısı</th>
                                    <th>Boyut</th>
                                    <th>Motor</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tables as $table): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($table['name']); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($table['collation']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary"><?php echo number_format($table['rows']); ?></span>
                                    </td>
                                    <td><?php echo formatBytes($table['size']); ?></td>
                                    <td>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($table['engine']); ?></span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" onclick="viewTable('<?php echo htmlspecialchars($table['name']); ?>')" title="Görüntüle">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-warning" onclick="optimizeTable('<?php echo htmlspecialchars($table['name']); ?>')" title="Optimize Et">
                                                <i class="fas fa-tools"></i>
                                            </button>
                                            <button class="btn btn-outline-success" onclick="exportTable('<?php echo htmlspecialchars($table['name']); ?>')" title="Dışa Aktar">
                                                <i class="fas fa-download"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Storage Usage Chart -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-chart-pie text-success"></i> Depolama Kullanımı</h6>
                </div>
                <div class="card-body">
                    <canvas id="storageChart" width="100" height="100"></canvas>
                </div>
            </div>

            <!-- Recent Backups -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-history text-info"></i> Son Yedeklemeler</h6>
                    <button class="btn btn-outline-primary btn-sm" onclick="loadBackupHistory()">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
                <div class="card-body">
                    <div id="backupHistory">
                        <div class="text-center text-muted">
                            <i class="fas fa-spinner fa-spin"></i> Yükleniyor...
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Back to Admin Button -->
    <div class="text-center mb-4 mt-4">
        <a href="/gelirgider/app/views/admin/index.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left"></i> Admin Paneline Dön
        </a>
    </div>
</div>

<!-- Table View Modal -->
<div class="modal fade" id="tableViewModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tablo Görünümü: <span id="modalTableName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="tableContent">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin"></i> Yükleniyor...
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#tablesTable').DataTable({
        responsive: true,
        pageLength: 10,
        language: {
            "decimal": "",
            "emptyTable": "Tabloda herhangi bir veri mevcut değil",
            "info": "_TOTAL_ kayıttan _START_ - _END_ arasındaki kayıtlar gösteriliyor",
            "infoEmpty": "Kayıt yok",
            "infoFiltered": "(_MAX_ kayıt içerisinden bulunan)",
            "lengthMenu": "_MENU_ kayıt göster",
            "loadingRecords": "Yükleniyor...",
            "processing": "İşleniyor...",
            "search": "Ara:",
            "zeroRecords": "Eşleşen kayıt bulunamadı",
            "paginate": {
                "first": "İlk",
                "last": "Son",
                "next": "Sonraki",
                "previous": "Önceki"
            }
        }
    });

    // Initialize storage chart
    initializeStorageChart();
    
    // Load backup history
    loadBackupHistory();
    
    // Auto-refresh every 5 minutes
    setInterval(function() {
        refreshStats();
    }, 300000);
});

function initializeStorageChart() {
    const ctx = document.getElementById('storageChart').getContext('2d');
    
    // Calculate storage data
    const tableData = <?php echo json_encode($tables); ?>;
    const chartData = tableData.slice(0, 8).map(table => ({
        label: table.name,
        data: table.size
    }));
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: chartData.map(item => item.label),
            datasets: [{
                data: chartData.map(item => item.data),
                backgroundColor: [
                    '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
                    '#858796', '#5a5c69', '#6f42c1'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
}

function createBackup() {
    showNotification('info', 'Yedekleme başlatıldı...');
    
    $.ajax({
        url: '/gelirgider/app/controllers/AdminController.php',
        type: 'POST',
        data: { action: 'backupDatabase', ajax: '1' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showNotification('success', 'Veritabanı başarıyla yedeklendi!');
                loadBackupHistory();
            } else {
                showNotification('error', response.message || 'Yedekleme başarısız.');
            }
        },
        error: function() {
            showNotification('error', 'Yedekleme sırasında hata oluştu.');
        }
    });
}

function optimizeDatabase() {
    if (confirm('Tüm veritabanı tablolarını optimize etmek istediğinizden emin misiniz?')) {
        showNotification('info', 'Optimizasyon başlatıldı...');
        
        $.ajax({
            url: '/gelirgider/app/controllers/AdminController.php',
            type: 'POST',
            data: { action: 'optimizeDatabase', ajax: '1' },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification('success', 'Veritabanı başarıyla optimize edildi!');
                    refreshStats();
                } else {
                    showNotification('error', response.message || 'Optimizasyon başarısız.');
                }
            },
            error: function() {
                showNotification('error', 'Optimizasyon sırasında hata oluştu.');
            }
        });
    }
}

function checkTables() {
    showNotification('info', 'Tablo kontrolü başlatıldı...');
    
    $.ajax({
        url: '/gelirgider/app/controllers/AdminController.php',
        type: 'POST',
        data: { action: 'checkTables', ajax: '1' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showNotification('success', 'Tablo kontrolü tamamlandı. Sorun bulunamadı.');
            } else {
                showNotification('warning', 'Bazı tablolarda sorunlar tespit edildi: ' + response.message);
            }
        },
        error: function() {
            showNotification('error', 'Tablo kontrolü sırasında hata oluştu.');
        }
    });
}

function refreshStats() {
    location.reload();
}

function viewTable(tableName) {
    $('#modalTableName').text(tableName);
    $('#tableViewModal').modal('show');
    
    $.ajax({
        url: '/gelirgider/app/controllers/AdminController.php',
        type: 'POST',
        data: { action: 'getTableData', table: tableName, ajax: '1' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                displayTableData(response.data);
            } else {
                $('#tableContent').html('<div class="alert alert-danger">Tablo verisi yüklenemedi.</div>');
            }
        },
        error: function() {
            $('#tableContent').html('<div class="alert alert-danger">Tablo verisi yüklenirken hata oluştu.</div>');
        }
    });
}

function displayTableData(data) {
    if (!data || data.length === 0) {
        $('#tableContent').html('<div class="alert alert-info">Bu tabloda veri bulunmuyor.</div>');
        return;
    }
    
    let html = '<div class="table-responsive"><table class="table table-sm table-striped"><thead><tr>';
    
    // Headers
    Object.keys(data[0]).forEach(key => {
        html += `<th>${key}</th>`;
    });
    html += '</tr></thead><tbody>';
    
    // Rows (limit to first 100 for performance)
    data.slice(0, 100).forEach(row => {
        html += '<tr>';
        Object.values(row).forEach(value => {
            html += `<td>${value || '-'}</td>`;
        });
        html += '</tr>';
    });
    
    html += '</tbody></table></div>';
    
    if (data.length > 100) {
        html += '<div class="alert alert-info">İlk 100 kayıt gösteriliyor. Toplam: ' + data.length + ' kayıt</div>';
    }
    
    $('#tableContent').html(html);
}

function optimizeTable(tableName) {
    if (confirm(`"${tableName}" tablosunu optimize etmek istediğinizden emin misiniz?`)) {
        $.ajax({
            url: '/gelirgider/app/controllers/AdminController.php',
            type: 'POST',
            data: { action: 'optimizeTable', table: tableName, ajax: '1' },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification('success', `"${tableName}" tablosu başarıyla optimize edildi!`);
                } else {
                    showNotification('error', response.message || 'Optimizasyon başarısız.');
                }
            },
            error: function() {
                showNotification('error', 'Optimizasyon sırasında hata oluştu.');
            }
        });
    }
}

function exportTable(tableName) {
    showNotification('info', `"${tableName}" tablosu dışa aktarılıyor...`);
    
    // Create download link
    const link = document.createElement('a');
    link.href = `/gelirgider/app/controllers/AdminController.php?action=exportTable&table=${tableName}`;
    link.download = `${tableName}_export.sql`;
    link.click();
    
    showNotification('success', 'Tablo dışa aktarma başlatıldı!');
}

function exportToCSV() {
    showNotification('info', 'CSV dışa aktarma yakında eklenecek.');
}

function exportToExcel() {
    showNotification('info', 'Excel dışa aktarma yakında eklenecek.');
}

function loadBackupHistory() {
    $.ajax({
        url: '/gelirgider/app/controllers/AdminController.php',
        type: 'POST',
        data: { action: 'getBackupHistory', ajax: '1' },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data.length > 0) {
                let html = '';
                response.data.forEach(backup => {
                    html += `
                        <div class="backup-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>${backup.filename}</strong>
                                    <br>
                                    <small class="text-muted">${backup.date}</small>
                                </div>
                                <div>
                                    <small class="text-muted">${backup.size}</small>
                                    <br>
                                    <button class="btn btn-outline-primary btn-sm" onclick="downloadBackup('${backup.filename}')">
                                        <i class="fas fa-download"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                });
                $('#backupHistory').html(html);
                
                // Update last backup info
                if (response.data.length > 0) {
                    $('#lastBackup').html(response.data[0].date);
                }
            } else {
                $('#backupHistory').html('<div class="text-center text-muted">Henüz yedekleme yapılmamış</div>');
            }
        },
        error: function() {
            $('#backupHistory').html('<div class="text-center text-danger">Yedekleme geçmişi yüklenemedi</div>');
        }
    });
}

function downloadBackup(filename) {
    const link = document.createElement('a');
    link.href = `/gelirgider/app/controllers/AdminController.php?action=downloadBackup&file=${filename}`;
    link.download = filename;
    link.click();
}

function showNotification(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : (type === 'error' ? 'alert-danger' : 'alert-info');
    const notification = `
        <div class="alert ${alertClass} alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999;">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    $('body').append(notification);
    
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 3000);
}
</script>

<?php
function formatBytes($size, $precision = 2) {
    $base = log($size, 1024);
    $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
}

require_once __DIR__ . '/../layouts/footer.php';
?> 