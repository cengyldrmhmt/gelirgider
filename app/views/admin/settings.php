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
$settings = $controller->getSiteSettings();

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

.nav-pills-custom .nav-link {
    border-radius: 25px;
    padding: 12px 24px;
    font-weight: 500;
    margin: 0 5px 10px 0;
    color: #667eea;
    background: white;
    border: 2px solid #e3f2fd;
    transition: all 0.3s ease;
}

.nav-pills-custom .nav-link:hover {
    color: #667eea;
    background: #f8f9fc;
    border-color: #667eea;
    transform: translateY(-2px);
}

.nav-pills-custom .nav-link.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white !important;
    border-color: transparent;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
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

.form-control, .form-select {
    border-radius: 10px;
    border: 2px solid #e3f2fd;
    padding: 12px 15px;
    transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
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

.setting-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
    margin-right: 15px;
}

.setting-group {
    background: #f8f9fc;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 20px;
}
</style>

<div class="container-fluid">
    <!-- Admin Header -->
    <div class="admin-header text-center">
        <h1 class="mb-2"><i class="fas fa-cogs"></i> Sistem Ayarları</h1>
        <p class="mb-0 opacity-75">Site ayarlarını ve sistem konfigürasyonunu yönetin</p>
    </div>

    <!-- Navigation Tabs -->
    <div class="row mb-4">
        <div class="col-12">
            <ul class="nav nav-pills nav-pills-custom justify-content-center" id="settingsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="general-tab" data-bs-toggle="pill" data-bs-target="#general" type="button" role="tab">
                        <i class="fas fa-cog"></i> Genel Ayarlar
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="email-tab" data-bs-toggle="pill" data-bs-target="#email" type="button" role="tab">
                        <i class="fas fa-envelope"></i> E-posta Ayarları
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="security-tab" data-bs-toggle="pill" data-bs-target="#security" type="button" role="tab">
                        <i class="fas fa-shield-alt"></i> Güvenlik
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="currency-tab" data-bs-toggle="pill" data-bs-target="#currency" type="button" role="tab">
                        <i class="fas fa-dollar-sign"></i> Para Birimi
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="notifications-tab" data-bs-toggle="pill" data-bs-target="#notifications" type="button" role="tab">
                        <i class="fas fa-bell"></i> Bildirimler
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <!-- Tab Content -->
    <div class="tab-content" id="settingsTabContent">
        <!-- General Settings -->
        <div class="tab-pane fade show active" id="general" role="tabpanel">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <div class="setting-icon">
                                <i class="fas fa-cog"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">Genel Site Ayarları</h5>
                                <small class="text-muted">Temel site konfigürasyonu</small>
                            </div>
                        </div>
                        <div class="card-body">
                            <form id="generalSettingsForm">
                                <div class="setting-group">
                                    <h6 class="text-primary mb-3"><i class="fas fa-info-circle"></i> Site Bilgileri</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Site Adı</label>
                                                <input type="text" class="form-control" name="site_name" value="<?php echo htmlspecialchars($settings['site_name'] ?? 'Gelir Gider Takip'); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Site URL</label>
                                                <input type="url" class="form-control" name="site_url" value="<?php echo htmlspecialchars($settings['site_url'] ?? 'http://localhost/gelirgider'); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Site Açıklaması</label>
                                        <textarea class="form-control" name="site_description" rows="3"><?php echo htmlspecialchars($settings['site_description'] ?? 'Kişisel finans yönetim sistemi'); ?></textarea>
                                    </div>
                                </div>

                                <div class="setting-group">
                                    <h6 class="text-primary mb-3"><i class="fas fa-paint-brush"></i> Görünüm</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Dil</label>
                                                <select class="form-select" name="default_language">
                                                    <option value="tr" <?php echo ($settings['default_language'] ?? 'tr') === 'tr' ? 'selected' : ''; ?>>Türkçe</option>
                                                    <option value="en" <?php echo ($settings['default_language'] ?? 'tr') === 'en' ? 'selected' : ''; ?>>English</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Zaman Dilimi</label>
                                                <select class="form-select" name="timezone">
                                                    <option value="Europe/Istanbul" <?php echo ($settings['timezone'] ?? 'Europe/Istanbul') === 'Europe/Istanbul' ? 'selected' : ''; ?>>Türkiye (UTC+3)</option>
                                                    <option value="UTC" <?php echo ($settings['timezone'] ?? 'Europe/Istanbul') === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="setting-group">
                                    <h6 class="text-primary mb-3"><i class="fas fa-users"></i> Kullanıcı Ayarları</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="allow_registration" <?php echo ($settings['allow_registration'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                                <label class="form-check-label">Kayıt olmaya izin ver</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="email_verification" <?php echo ($settings['email_verification'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                                <label class="form-check-label">E-posta doğrulama gerekli</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Kaydet
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Email Settings -->
        <div class="tab-pane fade" id="email" role="tabpanel">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <div class="setting-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">E-posta Ayarları</h5>
                                <small class="text-muted">SMTP ve e-posta konfigürasyonu</small>
                            </div>
                        </div>
                        <div class="card-body">
                            <form id="emailSettingsForm">
                                <div class="setting-group">
                                    <h6 class="text-primary mb-3"><i class="fas fa-server"></i> SMTP Ayarları</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">SMTP Host</label>
                                                <input type="text" class="form-control" name="smtp_host" value="<?php echo htmlspecialchars($settings['smtp_host'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">SMTP Port</label>
                                                <input type="number" class="form-control" name="smtp_port" value="<?php echo htmlspecialchars($settings['smtp_port'] ?? '587'); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">SMTP Kullanıcı Adı</label>
                                                <input type="email" class="form-control" name="smtp_username" value="<?php echo htmlspecialchars($settings['smtp_username'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">SMTP Şifre</label>
                                                <input type="password" class="form-control" name="smtp_password" value="<?php echo htmlspecialchars($settings['smtp_password'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Güvenlik</label>
                                                <select class="form-select" name="smtp_encryption">
                                                    <option value="tls" <?php echo ($settings['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                                    <option value="ssl" <?php echo ($settings['smtp_encryption'] ?? 'tls') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                                    <option value="" <?php echo ($settings['smtp_encryption'] ?? 'tls') === '' ? 'selected' : ''; ?>>Yok</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch mt-4">
                                                <input class="form-check-input" type="checkbox" name="smtp_auth" <?php echo ($settings['smtp_auth'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                                <label class="form-check-label">SMTP Authentication</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="setting-group">
                                    <h6 class="text-primary mb-3"><i class="fas fa-paper-plane"></i> Gönderici Bilgileri</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Gönderici Adı</label>
                                                <input type="text" class="form-control" name="mail_from_name" value="<?php echo htmlspecialchars($settings['mail_from_name'] ?? 'Gelir Gider Takip'); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Gönderici E-posta</label>
                                                <input type="email" class="form-control" name="mail_from_email" value="<?php echo htmlspecialchars($settings['mail_from_email'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-end">
                                    <button type="button" class="btn btn-outline-primary me-2" onclick="testEmailSettings()">
                                        <i class="fas fa-paper-plane"></i> Test E-postası Gönder
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Kaydet
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Security Settings -->
        <div class="tab-pane fade" id="security" role="tabpanel">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <div class="setting-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">Güvenlik Ayarları</h5>
                                <small class="text-muted">Sistem güvenlik konfigürasyonu</small>
                            </div>
                        </div>
                        <div class="card-body">
                            <form id="securitySettingsForm">
                                <div class="setting-group">
                                    <h6 class="text-primary mb-3"><i class="fas fa-lock"></i> Oturum Ayarları</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Oturum Süresi (dakika)</label>
                                                <input type="number" class="form-control" name="session_timeout" value="<?php echo htmlspecialchars($settings['session_timeout'] ?? '120'); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Başarısız Giriş Limiti</label>
                                                <input type="number" class="form-control" name="login_attempts" value="<?php echo htmlspecialchars($settings['login_attempts'] ?? '5'); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="force_https" <?php echo ($settings['force_https'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                                <label class="form-check-label">HTTPS Zorunlu</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="two_factor_auth" <?php echo ($settings['two_factor_auth'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                                <label class="form-check-label">İki Faktörlü Doğrulama</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="setting-group">
                                    <h6 class="text-primary mb-3"><i class="fas fa-key"></i> Şifre Politikası</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Minimum Şifre Uzunluğu</label>
                                                <input type="number" class="form-control" name="min_password_length" value="<?php echo htmlspecialchars($settings['min_password_length'] ?? '6'); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch mt-4">
                                                <input class="form-check-input" type="checkbox" name="require_special_chars" <?php echo ($settings['require_special_chars'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                                <label class="form-check-label">Özel Karakter Zorunlu</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Kaydet
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Currency Settings -->
        <div class="tab-pane fade" id="currency" role="tabpanel">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <div class="setting-icon">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">Para Birimi Ayarları</h5>
                                <small class="text-muted">Varsayılan para birimi ve format ayarları</small>
                            </div>
                        </div>
                        <div class="card-body">
                            <form id="currencySettingsForm">
                                <div class="setting-group">
                                    <h6 class="text-primary mb-3"><i class="fas fa-coins"></i> Para Birimi</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Varsayılan Para Birimi</label>
                                                <select class="form-select" name="default_currency">
                                                    <option value="TRY" <?php echo ($settings['default_currency'] ?? 'TRY') === 'TRY' ? 'selected' : ''; ?>>Türk Lirası (TRY)</option>
                                                    <option value="USD" <?php echo ($settings['default_currency'] ?? 'TRY') === 'USD' ? 'selected' : ''; ?>>US Dollar (USD)</option>
                                                    <option value="EUR" <?php echo ($settings['default_currency'] ?? 'TRY') === 'EUR' ? 'selected' : ''; ?>>Euro (EUR)</option>
                                                    <option value="GBP" <?php echo ($settings['default_currency'] ?? 'TRY') === 'GBP' ? 'selected' : ''; ?>>British Pound (GBP)</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Para Birimi Sembolü</label>
                                                <input type="text" class="form-control" name="currency_symbol" value="<?php echo htmlspecialchars($settings['currency_symbol'] ?? '₺'); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Ondalık Basamak</label>
                                                <select class="form-select" name="decimal_places">
                                                    <option value="0" <?php echo ($settings['decimal_places'] ?? '2') === '0' ? 'selected' : ''; ?>>0</option>
                                                    <option value="1" <?php echo ($settings['decimal_places'] ?? '2') === '1' ? 'selected' : ''; ?>>1</option>
                                                    <option value="2" <?php echo ($settings['decimal_places'] ?? '2') === '2' ? 'selected' : ''; ?>>2</option>
                                                    <option value="3" <?php echo ($settings['decimal_places'] ?? '2') === '3' ? 'selected' : ''; ?>>3</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Binlik Ayırıcı</label>
                                                <select class="form-select" name="thousand_separator">
                                                    <option value="." <?php echo ($settings['thousand_separator'] ?? '.') === '.' ? 'selected' : ''; ?>>Nokta (.)</option>
                                                    <option value="," <?php echo ($settings['thousand_separator'] ?? '.') === ',' ? 'selected' : ''; ?>>Virgül (,)</option>
                                                    <option value=" " <?php echo ($settings['thousand_separator'] ?? '.') === ' ' ? 'selected' : ''; ?>>Boşluk ( )</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Ondalık Ayırıcı</label>
                                                <select class="form-select" name="decimal_separator">
                                                    <option value="," <?php echo ($settings['decimal_separator'] ?? ',') === ',' ? 'selected' : ''; ?>>Virgül (,)</option>
                                                    <option value="." <?php echo ($settings['decimal_separator'] ?? ',') === '.' ? 'selected' : ''; ?>>Nokta (.)</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch mt-4">
                                                <input class="form-check-input" type="checkbox" name="currency_before" <?php echo ($settings['currency_before'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                                <label class="form-check-label">Sembol sayıdan önce</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="setting-group">
                                    <h6 class="text-primary mb-3"><i class="fas fa-eye"></i> Önizleme</h6>
                                    <div class="alert alert-info">
                                        <strong>Örnek Format:</strong> <span id="currencyPreview">1.234,56 ₺</span>
                                    </div>
                                </div>

                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Kaydet
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notification Settings -->
        <div class="tab-pane fade" id="notifications" role="tabpanel">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <div class="setting-icon">
                                <i class="fas fa-bell"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">Bildirim Ayarları</h5>
                                <small class="text-muted">E-posta ve sistem bildirim tercihleri</small>
                            </div>
                        </div>
                        <div class="card-body">
                            <form id="notificationSettingsForm">
                                <div class="setting-group">
                                    <h6 class="text-primary mb-3"><i class="fas fa-envelope"></i> E-posta Bildirimleri</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="notify_new_user" <?php echo ($settings['notify_new_user'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                                <label class="form-check-label">Yeni kullanıcı kaydı</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="notify_budget_exceeded" <?php echo ($settings['notify_budget_exceeded'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                                <label class="form-check-label">Bütçe aşımı</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="notify_payment_due" <?php echo ($settings['notify_payment_due'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                                <label class="form-check-label">Ödeme vadeleri</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="notify_backup_complete" <?php echo ($settings['notify_backup_complete'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                                <label class="form-check-label">Yedekleme tamamlandı</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="setting-group">
                                    <h6 class="text-primary mb-3"><i class="fas fa-cog"></i> Sistem Bildirimleri</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Bildirim E-postası</label>
                                                <input type="email" class="form-control" name="notification_email" value="<?php echo htmlspecialchars($settings['notification_email'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Ödeme Hatırlatma Süresi (gün)</label>
                                                <input type="number" class="form-control" name="payment_reminder_days" value="<?php echo htmlspecialchars($settings['payment_reminder_days'] ?? '3'); ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Kaydet
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Back to Admin Button -->
    <div class="text-center mb-4">
        <a href="/gelirgider/app/views/admin/index.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left"></i> Admin Paneline Dön
        </a>
    </div>
</div>

<script>
$(document).ready(function() {
    // Currency preview update
    updateCurrencyPreview();
    
    // Listen for currency setting changes
    $('#currencySettingsForm input, #currencySettingsForm select').on('change', function() {
        updateCurrencyPreview();
    });
    
    // Form submissions
    $('#generalSettingsForm').on('submit', function(e) {
        e.preventDefault();
        saveSettings('general', this);
    });
    
    $('#emailSettingsForm').on('submit', function(e) {
        e.preventDefault();
        saveSettings('email', this);
    });
    
    $('#securitySettingsForm').on('submit', function(e) {
        e.preventDefault();
        saveSettings('security', this);
    });
    
    $('#currencySettingsForm').on('submit', function(e) {
        e.preventDefault();
        saveSettings('currency', this);
    });
    
    $('#notificationSettingsForm').on('submit', function(e) {
        e.preventDefault();
        saveSettings('notifications', this);
    });
});

function updateCurrencyPreview() {
    const symbol = $('input[name="currency_symbol"]').val() || '₺';
    const decimalPlaces = parseInt($('select[name="decimal_places"]').val()) || 2;
    const thousandSep = $('select[name="thousand_separator"]').val() || '.';
    const decimalSep = $('select[name="decimal_separator"]').val() || ',';
    const symbolBefore = $('input[name="currency_before"]').is(':checked');
    
    let amount = '1234.56';
    let formattedAmount = parseFloat(amount).toFixed(decimalPlaces);
    
    // Replace separators
    formattedAmount = formattedAmount.replace('.', '|DECIMAL|');
    formattedAmount = formattedAmount.replace(/\B(?=(\d{3})+(?!\d))/g, thousandSep);
    formattedAmount = formattedAmount.replace('|DECIMAL|', decimalSep);
    
    const preview = symbolBefore ? symbol + ' ' + formattedAmount : formattedAmount + ' ' + symbol;
    $('#currencyPreview').text(preview);
}

function saveSettings(type, form) {
    const formData = new FormData(form);
    formData.append('action', 'updateSiteSettings');
    formData.append('ajax', '1');
    
    // Convert checkboxes to 1/0
    $(form).find('input[type="checkbox"]').each(function() {
        const name = $(this).attr('name');
        if (!formData.has(name)) {
            formData.append(name, '0');
        } else {
            formData.set(name, '1');
        }
    });
    
    $.ajax({
        url: '/gelirgider/app/controllers/AdminController.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showNotification('success', 'Ayarlar başarıyla güncellendi!');
            } else {
                showNotification('error', response.message || 'Ayarlar güncellenirken hata oluştu.');
            }
        },
        error: function() {
            showNotification('error', 'Ayarlar güncellenirken hata oluştu.');
        }
    });
}

function testEmailSettings() {
    // Test email functionality
    showNotification('info', 'Test e-postası gönderme özelliği yakında eklenecek.');
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
require_once __DIR__ . '/../layouts/footer.php';
?> 