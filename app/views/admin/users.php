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
.avatar-sm {
    width: 40px;
    height: 40px;
    font-size: 16px;
    font-weight: bold;
}
.table thead th {
    border-bottom: 2px solid #dee2e6;
    font-weight: 600;
    color: #495057;
    background: #f8f9fc;
}
.btn-group .btn {
    border-radius: 8px !important;
    margin: 0 2px;
}
</style>

<!-- DataTables CSS -->
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">

<!-- Admin Header -->
<div class="admin-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="mb-0">
                    <i class="fas fa-users"></i> Kullanıcı Yönetimi
                </h1>
                <p class="mb-0 opacity-75">Tüm kullanıcıları görüntüle, düzenle ve yönet</p>
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
    <!-- User Management Card -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">
                    <i class="fas fa-users text-primary"></i> Kullanıcı Listesi
                </h5>
                <small class="text-muted">
                    Toplam <?php echo number_format(count($data['userStats'])); ?> kullanıcı
                </small>
            </div>
            <button class="btn btn-primary" onclick="addUser()">
                <i class="fas fa-plus"></i> Yeni Kullanıcı Ekle
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="usersTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Kullanıcı</th>
                            <th>E-posta</th>
                            <th>Ad Soyad</th>
                            <th>Rol</th>
                            <th>Kayıt Tarihi</th>
                            <th>İstatistikler</th>
                            <th>Durum</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['userStats'] as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm bg-primary rounded-circle text-white me-3 d-flex align-items-center justify-content-center">
                                        <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            Son giriş: <?php echo date('d.m.Y', strtotime($user['created_at'])); ?>
                                        </small>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['full_name'] ?: 'Belirtilmemiş'); ?></td>
                            <td>
                                <?php if ($user['is_admin']): ?>
                                    <span class="badge bg-danger">
                                        <i class="fas fa-shield-alt"></i> Admin
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">
                                        <i class="fas fa-user"></i> Kullanıcı
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></td>
                            <td>
                                <div class="small">
                                    <div><strong><?php echo number_format($user['wallet_count']); ?></strong> cüzdan</div>
                                    <div><strong><?php echo number_format($user['transaction_count']); ?></strong> işlem</div>
                                    <div><strong><?php echo number_format($user['total_balance'], 0); ?></strong> ₺ bakiye</div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-success">
                                    <i class="fas fa-check"></i> Aktif
                                </span>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button class="btn btn-sm btn-outline-primary" onclick="editUser(<?php echo $user['id']; ?>)" title="Düzenle">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-info" onclick="viewUserDetails(<?php echo $user['id']; ?>)" title="Detayları Gör">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteUser(<?php echo $user['id']; ?>)" title="Sil">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
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

<!-- User Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalTitle">Kullanıcı İşlemleri</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="userForm">
                    <input type="hidden" id="userId" name="user_id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Kullanıcı Adı *</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">E-posta *</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Ad Soyad</label>
                                <input type="text" class="form-control" id="fullName" name="full_name">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Şifre</label>
                                <input type="password" class="form-control" id="password" name="password">
                                <small class="text-muted">Boş bırakırsanız şifre değişmeyecektir</small>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="isAdmin" name="is_admin">
                            <label class="form-check-label" for="isAdmin">
                                <i class="fas fa-shield-alt"></i> Admin Yetkisi Ver
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" onclick="saveUser()">
                    <i class="fas fa-save"></i> Kaydet
                </button>
            </div>
        </div>
    </div>
</div>

<!-- User Details Modal -->
<div class="modal fade" id="userDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Kullanıcı Detayları</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="userDetailsContent">
                <!-- User details will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#usersTable').DataTable({
        responsive: true,
        pageLength: 25,
        order: [[0, 'desc']],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/Turkish.json'
        },
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"B>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        buttons: [
            {
                extend: 'excel',
                text: '<i class="fas fa-file-excel"></i> Excel',
                className: 'btn btn-success btn-sm',
                title: 'Kullanici_Listesi_' + new Date().toISOString().slice(0,10)
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf"></i> PDF',
                className: 'btn btn-danger btn-sm',
                title: 'Kullanici_Listesi_' + new Date().toISOString().slice(0,10)
            },
            {
                extend: 'print',
                text: '<i class="fas fa-print"></i> Yazdır',
                className: 'btn btn-info btn-sm'
            }
        ]
    });
});

// User management functions
function addUser() {
    $('#userModalTitle').text('Yeni Kullanıcı Ekle');
    $('#userForm')[0].reset();
    $('#userId').val('');
    $('#password').prop('required', true);
    $('#userModal').modal('show');
}

function editUser(userId) {
    $('#userModalTitle').text('Kullanıcı Düzenle');
    $('#userId').val(userId);
    $('#password').prop('required', false);
    
    // Load user data
    $.get('/gelirgider/app/controllers/AdminController.php', {
        action: 'getUser',
        user_id: userId
    }).done(function(response) {
        if (response.success) {
            const user = response.data;
            $('#username').val(user.username);
            $('#email').val(user.email);
            $('#fullName').val(user.full_name);
            $('#isAdmin').prop('checked', user.is_admin == 1);
        }
    });
    
    $('#userModal').modal('show');
}

function saveUser() {
    const formData = new FormData($('#userForm')[0]);
    const action = $('#userId').val() ? 'updateUser' : 'createUser';
    formData.append('action', action);
    
    $.ajax({
        url: '/gelirgider/app/controllers/AdminController.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showAlert('success', response.message);
                $('#userModal').modal('hide');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showAlert('danger', response.message);
            }
        },
        error: function() {
            showAlert('danger', 'İşlem sırasında bir hata oluştu');
        }
    });
}

function deleteUser(userId) {
    if (confirm('Bu kullanıcıyı ve tüm verilerini silmek istediğinizden emin misiniz?\n\nBu işlem geri alınamaz!')) {
        $.ajax({
            url: '/gelirgider/app/controllers/AdminController.php',
            method: 'POST',
            data: {
                action: 'deleteUser',
                user_id: userId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAlert('success', response.message);
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showAlert('danger', response.message);
                }
            },
            error: function() {
                showAlert('danger', 'Silme işlemi sırasında bir hata oluştu');
            }
        });
    }
}

function viewUserDetails(userId) {
    $.get('/gelirgider/app/controllers/AdminController.php', {
        action: 'getUserDetails',
        user_id: userId
    }).done(function(response) {
        if (response.success) {
            renderUserDetails(response.data);
            $('#userDetailsModal').modal('show');
        }
    });
}

function renderUserDetails(user) {
    const html = `
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h6>Kullanıcı Bilgileri</h6>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <div class="avatar-lg bg-primary rounded-circle text-white mx-auto d-flex align-items-center justify-content-center" style="width: 80px; height: 80px; font-size: 2rem;">
                                ${user.username.charAt(0).toUpperCase()}
                            </div>
                            <h5 class="mt-3">${user.username}</h5>
                            <p class="text-muted">${user.email}</p>
                        </div>
                        <table class="table table-sm">
                            <tr><td>Ad Soyad:</td><td>${user.full_name || 'Belirtilmemiş'}</td></tr>
                            <tr><td>Rol:</td><td>${user.is_admin ? '<span class="badge bg-danger">Admin</span>' : '<span class="badge bg-secondary">Kullanıcı</span>'}</td></tr>
                            <tr><td>Kayıt Tarihi:</td><td>${new Date(user.created_at).toLocaleDateString('tr-TR')}</td></tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body text-center">
                                <h3 class="text-primary">${user.wallet_count || 0}</h3>
                                <p class="mb-0">Cüzdan Sayısı</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body text-center">
                                <h3 class="text-success">${user.transaction_count || 0}</h3>
                                <p class="mb-0">Toplam İşlem</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <h6>Finansal Özet</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <h4 class="text-info">${parseFloat(user.total_balance || 0).toLocaleString('tr-TR')} ₺</h4>
                                <p>Toplam Bakiye</p>
                            </div>
                            <div class="col-md-4 text-center">
                                <h4 class="text-warning">${user.credit_card_count || 0}</h4>
                                <p>Kredi Kartı</p>
                            </div>
                            <div class="col-md-4 text-center">
                                <h4 class="text-secondary">${user.category_count || 0}</h4>
                                <p>Kategori</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    $('#userDetailsContent').html(html);
}

// Utility functions
function showAlert(type, message) {
    const alertHtml = `<div class="alert alert-${type} alert-dismissible fade show position-fixed" 
                        style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;" role="alert">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>`;
    
    $('body').append(alertHtml);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        $('.alert').fadeOut();
    }, 5000);
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?> 