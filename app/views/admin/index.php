<?php
session_start();

// Admin kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /gelirgider/login');
    exit;
}

require_once __DIR__ . '/../../app/controllers/AdminController.php';
require_once __DIR__ . '/../layouts/header.php';

$adminController = new AdminController();
$data = $adminController->getDashboardData();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="admin-header">
                <h1>Admin Paneli</h1>
                <button class="btn btn-light" onclick="refreshDashboard()">
                    <i class="fas fa-sync-alt"></i> Yenile
                </button>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-3">
            <div class="stat-card">
                <h3><?php echo number_format($data['systemStats']['total_users']); ?></h3>
                <p>Toplam Kullanıcı</p>
                <small>Bugün: <?php echo $data['systemStats']['new_users_today']; ?></small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <h3><?php echo number_format($data['systemStats']['total_transactions']); ?></h3>
                <p>Toplam İşlem</p>
                <small>KK: <?php echo number_format($data['systemStats']['total_cc_transactions']); ?></small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <h3><?php echo number_format($data['systemStats']['total_wallets']); ?></h3>
                <p>Toplam Cüzdan</p>
                <small><?php echo number_format($data['systemStats']['total_wallet_balance'], 0); ?> ₺</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <h3><?php echo number_format($data['systemStats']['total_credit_cards']); ?></h3>
                <p>Kredi Kartı</p>
                <small>Kategori: <?php echo number_format($data['systemStats']['total_categories']); ?></small>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Hızlı İşlemler</h5>
                    <div class="quick-actions">
                        <button class="btn btn-primary" onclick="backupDatabase()">
                            <i class="fas fa-database"></i> Veritabanı Yedeği
                        </button>
                        <button class="btn btn-warning" onclick="cleanupSystem()">
                            <i class="fas fa-broom"></i> Sistem Temizliği
                        </button>
                        <button class="btn btn-info" onclick="showUsers()">
                            <i class="fas fa-users"></i> Kullanıcılar
                        </button>
                        <button class="btn btn-success" onclick="showSettings()">
                            <i class="fas fa-cog"></i> Ayarlar
                        </button>
                        <button class="btn btn-secondary" onclick="showSystem()">
                            <i class="fas fa-server"></i> Sistem Bilgisi
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <ul class="nav nav-pills mb-3" id="adminTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="users-tab" data-toggle="pill" href="#users" role="tab">
                                <i class="fas fa-users"></i> Kullanıcılar
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="settings-tab" data-toggle="pill" href="#settings" role="tab">
                                <i class="fas fa-cog"></i> Ayarlar
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="system-tab" data-toggle="pill" href="#system" role="tab">
                                <i class="fas fa-server"></i> Sistem Bilgisi
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content" id="adminTabContent">
                        <div class="tab-pane fade show active" id="users" role="tabpanel">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Kullanıcı Yönetimi</h5>
                                    <button class="btn btn-primary" onclick="addUser()">
                                        <i class="fas fa-plus"></i> Yeni Kullanıcı
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped" id="usersTable">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Kullanıcı Adı</th>
                                                    <th>E-posta</th>
                                                    <th>Ad Soyad</th>
                                                    <th>Rol</th>
                                                    <th>Kayıt Tarihi</th>
                                                    <th>Cüzdan</th>
                                                    <th>İşlem</th>
                                                    <th>Bakiye</th>
                                                    <th>İşlemler</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($data['userStats'] as $user): ?>
                                                <tr>
                                                    <td><?php echo $user['id']; ?></td>
                                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                                    <td>
                                                        <?php if ($user['is_admin']): ?>
                                                            <span class="badge bg-danger">Admin</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">Kullanıcı</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></td>
                                                    <td><?php echo number_format($user['wallet_count']); ?></td>
                                                    <td><?php echo number_format($user['transaction_count']); ?></td>
                                                    <td><?php echo number_format($user['total_balance'], 0); ?> ₺</td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button class="btn btn-sm btn-info" onclick="editUser(<?php echo $user['id']; ?>)">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-danger" onclick="deleteUser(<?php echo $user['id']; ?>)">
                                                                <i class="fas fa-trash"></i>
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
                        <div class="tab-pane fade" id="settings" role="tabpanel">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Sistem Ayarları</h5>
                                </div>
                                <div class="card-body">
                                    <form id="settingsForm">
                                        <div class="form-group">
                                            <label>Site Başlığı</label>
                                            <input type="text" class="form-control" name="site_title" value="<?php echo $data['settings']['site_title']; ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>Site Açıklaması</label>
                                            <textarea class="form-control" name="site_description"><?php echo $data['settings']['site_description']; ?></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label>E-posta Bildirimleri</label>
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="emailNotifications" name="email_notifications" <?php echo $data['settings']['email_notifications'] ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="emailNotifications">Aktif</label>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Kaydet</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="system" role="tabpanel">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Sistem Bilgisi</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>Sunucu Bilgileri</h6>
                                            <table class="table table-sm">
                                                <tr>
                                                    <td>PHP Versiyonu</td>
                                                    <td><?php echo phpversion(); ?></td>
                                                </tr>
                                                <tr>
                                                    <td>MySQL Versiyonu</td>
                                                    <td><?php echo $data['systemInfo']['mysql_version']; ?></td>
                                                </tr>
                                                <tr>
                                                    <td>Sunucu Yazılımı</td>
                                                    <td><?php echo $_SERVER['SERVER_SOFTWARE']; ?></td>
                                                </tr>
                                            </table>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Sistem Durumu</h6>
                                            <table class="table table-sm">
                                                <tr>
                                                    <td>Disk Kullanımı</td>
                                                    <td><?php echo $data['systemInfo']['disk_usage']; ?></td>
                                                </tr>
                                                <tr>
                                                    <td>Bellek Kullanımı</td>
                                                    <td><?php echo $data['systemInfo']['memory_usage']; ?></td>
                                                </tr>
                                                <tr>
                                                    <td>CPU Yükü</td>
                                                    <td><?php echo $data['systemInfo']['cpu_load']; ?></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="/gelirgider/public/css/admin/style.css">
<script src="/gelirgider/public/js/admin/script.js"></script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?> 