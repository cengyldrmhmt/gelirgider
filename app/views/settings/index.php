<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /gelirgider/app/views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../controllers/SettingsController.php';
require_once __DIR__ . '/../layouts/header.php';

$settingsController = new SettingsController();
$data = $settingsController->index();

include '../layouts/sidebar.php';
?>

<link rel="stylesheet" href="/gelirgider/public/css/settings/style.css">
<script src="/gelirgider/public/js/settings/script.js" defer></script>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-cog"></i> Sistem Ayarları
                    </h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <!-- Genel Ayarlar -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user-cog"></i> Genel Ayarlar
                    </h5>
                </div>
                <div class="card-body">
                    <form id="settingsForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Para Birimi</label>
                                    <select class="form-select" name="currency">
                                        <option value="TRY" <?= $data['settings']['currency'] === 'TRY' ? 'selected' : '' ?>>₺ Türk Lirası</option>
                                        <option value="USD" <?= $data['settings']['currency'] === 'USD' ? 'selected' : '' ?>>$ Amerikan Doları</option>
                                        <option value="EUR" <?= $data['settings']['currency'] === 'EUR' ? 'selected' : '' ?>>€ Euro</option>
                                        <option value="GBP" <?= $data['settings']['currency'] === 'GBP' ? 'selected' : '' ?>>£ İngiliz Sterlini</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Dil</label>
                                    <select class="form-select" name="language">
                                        <option value="tr" <?= $data['settings']['language'] === 'tr' ? 'selected' : '' ?>>Türkçe</option>
                                        <option value="en" <?= $data['settings']['language'] === 'en' ? 'selected' : '' ?>>English</option>
                                        <option value="de" <?= $data['settings']['language'] === 'de' ? 'selected' : '' ?>>Deutsch</option>
                                        <option value="fr" <?= $data['settings']['language'] === 'fr' ? 'selected' : '' ?>>Français</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Zaman Dilimi</label>
                                    <select class="form-select" name="timezone">
                                        <option value="Europe/Istanbul" <?= $data['settings']['timezone'] === 'Europe/Istanbul' ? 'selected' : '' ?>>İstanbul</option>
                                        <option value="Europe/London" <?= $data['settings']['timezone'] === 'Europe/London' ? 'selected' : '' ?>>Londra</option>
                                        <option value="America/New_York" <?= $data['settings']['timezone'] === 'America/New_York' ? 'selected' : '' ?>>New York</option>
                                        <option value="Asia/Tokyo" <?= $data['settings']['timezone'] === 'Asia/Tokyo' ? 'selected' : '' ?>>Tokyo</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tarih Formatı</label>
                                    <select class="form-select" name="date_format">
                                        <option value="d.m.Y" <?= $data['settings']['date_format'] === 'd.m.Y' ? 'selected' : '' ?>>31.12.2024</option>
                                        <option value="Y-m-d" <?= $data['settings']['date_format'] === 'Y-m-d' ? 'selected' : '' ?>>2024-12-31</option>
                                        <option value="d/m/Y" <?= $data['settings']['date_format'] === 'd/m/Y' ? 'selected' : '' ?>>31/12/2024</option>
                                        <option value="m/d/Y" <?= $data['settings']['date_format'] === 'm/d/Y' ? 'selected' : '' ?>>12/31/2024</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tema</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="theme" value="light" <?= $data['settings']['theme'] === 'light' ? 'checked' : '' ?>>
                                        <label class="form-check-label">
                                            <i class="fas fa-sun"></i> Açık Tema
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="theme" value="dark" <?= $data['settings']['theme'] === 'dark' ? 'checked' : '' ?>>
                                        <label class="form-check-label">
                                            <i class="fas fa-moon"></i> Koyu Tema
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <h6><i class="fas fa-bell"></i> Bildirim Ayarları</h6>
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="notifications_enabled" <?= $data['settings']['notifications_enabled'] ? 'checked' : '' ?>>
                                <label class="form-check-label">Bildirimleri Etkinleştir</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="email_notifications" <?= $data['settings']['email_notifications'] ? 'checked' : '' ?>>
                                <label class="form-check-label">E-posta Bildirimleri</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="budget_alerts" <?= $data['settings']['budget_alerts'] ? 'checked' : '' ?>>
                                <label class="form-check-label">Bütçe Uyarıları</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="expense_warnings" <?= $data['settings']['expense_warnings'] ? 'checked' : '' ?>>
                                <label class="form-check-label">Harcama Uyarıları</label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Ayarları Kaydet
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Veri Yönetimi -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-database"></i> Veri Yönetimi
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Veri Dışa Aktarma -->
                    <div class="mb-4">
                        <h6><i class="fas fa-download"></i> Veri Dışa Aktarma</h6>
                        <p class="text-muted">Tüm verilerinizi JSON formatında indirin.</p>
                        <button type="button" class="btn btn-success" id="exportDataBtn">
                            <i class="fas fa-file-export"></i> Verileri Dışa Aktar
                        </button>
                    </div>

                    <hr>

                    <!-- Veri Temizleme -->
                    <div class="mb-4">
                        <h6><i class="fas fa-trash"></i> Veri Temizleme</h6>
                        <p class="text-muted text-danger">
                            <strong>Dikkat:</strong> Bu işlemler geri alınamaz!
                        </p>
                        
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-outline-warning" onclick="clearData('transactions')">
                                <i class="fas fa-exchange-alt"></i> Tüm İşlemleri Sil
                            </button>
                            <button type="button" class="btn btn-outline-warning" onclick="clearData('categories')">
                                <i class="fas fa-tags"></i> Tüm Kategorileri Sil
                            </button>
                            <button type="button" class="btn btn-outline-danger" onclick="clearData('all')">
                                <i class="fas fa-bomb"></i> Tüm Verileri Sil
                            </button>
                        </div>
                    </div>

                    <hr>

                    <!-- Hesap İstatistikleri -->
                    <div class="mb-4">
                        <h6><i class="fas fa-chart-bar"></i> Hesap İstatistikleri</h6>
                        <ul class="list-unstyled">
                            <li><strong>Hesap Oluşturma:</strong> <?= date('d.m.Y', strtotime($data['user']['created_at'])) ?></li>
                            <li><strong>Son Güncelleme:</strong> <?= date('d.m.Y H:i', strtotime($data['settings']['updated_at'] ?? $data['settings']['created_at'])) ?></li>
                            <li><strong>Tema:</strong> <?= $data['settings']['theme'] === 'dark' ? 'Koyu' : 'Açık' ?></li>
                            <li><strong>Para Birimi:</strong> <?= $data['settings']['currency'] ?></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Sistem Bilgileri -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle"></i> Sistem Bilgileri
                    </h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li><strong>Uygulama:</strong> GELİRGİDER v1.0</li>
                        <li><strong>PHP Sürümü:</strong> <?= phpversion() ?></li>
                        <li><strong>Server:</strong> <?= $_SERVER['SERVER_SOFTWARE'] ?? 'Bilinmiyor' ?></li>
                        <li><strong>Zaman Dilimi:</strong> <?= date_default_timezone_get() ?></li>
                        <li><strong>Mevcut Zaman:</strong> <?= date('d.m.Y H:i:s') ?></li>
                    </ul>
                    
                    <div class="mt-3">
                        <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#systemInfoModal">
                            <i class="fas fa-server"></i> Detaylı Sistem Bilgileri
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sistem Bilgileri Modal -->
<div class="modal fade" id="systemInfoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detaylı Sistem Bilgileri</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <tbody>
                            <tr><td><strong>PHP Sürümü</strong></td><td><?= phpversion() ?></td></tr>
                            <tr><td><strong>Memory Limit</strong></td><td><?= ini_get('memory_limit') ?></td></tr>
                            <tr><td><strong>Max Execution Time</strong></td><td><?= ini_get('max_execution_time') ?> saniye</td></tr>
                            <tr><td><strong>Upload Max Filesize</strong></td><td><?= ini_get('upload_max_filesize') ?></td></tr>
                            <tr><td><strong>Post Max Size</strong></td><td><?= ini_get('post_max_size') ?></td></tr>
                            <tr><td><strong>Server Software</strong></td><td><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Bilinmiyor' ?></td></tr>
                            <tr><td><strong>Document Root</strong></td><td><?= $_SERVER['DOCUMENT_ROOT'] ?? 'Bilinmiyor' ?></td></tr>
                            <tr><td><strong>Server Admin</strong></td><td><?= $_SERVER['SERVER_ADMIN'] ?? 'Bilinmiyor' ?></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Ayarları kaydet
    document.getElementById('settingsForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('/gelirgider/app/controllers/SettingsController.php?action=update', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                toastr.success(data.message);
            } else {
                toastr.error(data.message || 'Ayarlar kaydedilirken hata oluştu.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            toastr.error('Bağlantı hatası oluştu.');
        });
    });

    // Veri dışa aktarma
    document.getElementById('exportDataBtn').addEventListener('click', function() {
        const btn = this;
        const originalText = btn.innerHTML;
        
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Dışa aktarılıyor...';
        btn.disabled = true;
        
        // Yeni pencerede dosyayı indir
        window.location.href = '/gelirgider/app/controllers/SettingsController.php?action=exportData';
        
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
            toastr.success('Veriler başarıyla dışa aktarıldı.');
        }, 2000);
    });
});

// Veri temizleme fonksiyonu
function clearData(dataType) {
    let message = '';
    let confirmMessage = '';
    
    switch(dataType) {
        case 'transactions':
            message = 'Tüm işlemleriniz silinecek.';
            confirmMessage = 'Tüm işlemleri silmek istediğinizden emin misiniz?';
            break;
        case 'categories':
            message = 'Tüm kategorileriniz silinecek ve işlemlerinizdeki kategori bağlantıları kaldırılacak.';
            confirmMessage = 'Tüm kategorileri silmek istediğinizden emin misiniz?';
            break;
        case 'all':
            message = 'Tüm verileriniz (işlemler, kategoriler, cüzdanlar, bütçeler, hedefler) silinecek. Sadece kullanıcı hesabınız ve ayarlarınız kalacak.';
            confirmMessage = 'TÜM VERİLERİNİZİ silmek istediğinizden emin misiniz? Bu işlem GERİ ALINAMAZ!';
            break;
    }
    
    Swal.fire({
        title: 'Veri Silme Onayı',
        html: `
            <div class="text-danger">
                <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                <p><strong>UYARI: Bu işlem geri alınamaz!</strong></p>
                <p>${message}</p>
            </div>
        `,
        text: confirmMessage,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Evet, Sil!',
        cancelButtonText: 'İptal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('data_type', dataType);
            
            fetch('/gelirgider/app/controllers/SettingsController.php?action=clearData', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Silindi!', data.message, 'success').then(() => {
                        // Sayfayı yenile
                        location.reload();
                    });
                } else {
                    Swal.fire('Hata!', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Hata!', 'Bağlantı hatası oluştu.', 'error');
            });
        }
    });
}
</script>

<?php include '../layouts/footer.php'; ?> 