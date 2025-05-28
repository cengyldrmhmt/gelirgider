<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /gelirgider/app/views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../controllers/CategoryController.php';
require_once __DIR__ . '/../layouts/header.php';

$controller = new CategoryController();
$data = $controller->index();

include '../layouts/sidebar.php';

// CSS dosyasını ekle (JS dosyasını kaldırıyoruz)
echo '<link rel="stylesheet" href="/gelirgider/public/css/categories/style.css">';
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-tags text-primary"></i> Kategoriler
        </h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
            <i class="fas fa-plus"></i> Yeni Kategori
        </button>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Toplam Kategori</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo count($data['categories']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tags fa-2x text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Gelir Kategorileri</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php 
                                $incomeCategories = array_filter($data['categories'], function($cat) {
                                    return $cat['type'] === 'income';
                                });
                                echo count($incomeCategories);
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-arrow-up fa-2x text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Gider Kategorileri</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php 
                                $expenseCategories = array_filter($data['categories'], function($cat) {
                                    return $cat['type'] === 'expense';
                                });
                                echo count($expenseCategories);
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-arrow-down fa-2x text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Toplam İşlem</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php 
                                $totalTransactions = array_sum(array_column($data['categories'], 'transaction_count'));
                                echo $totalTransactions;
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-list fa-2x text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Kullanım Oranı</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php 
                                $usedCategories = count(array_filter($data['categories'], function($cat) {
                                    return $cat['transaction_count'] > 0;
                                }));
                                $totalCategories = count($data['categories']);
                                echo $totalCategories > 0 ? number_format(($usedCategories / $totalCategories) * 100, 1) : 0;
                                ?>%
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-percentage fa-2x text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Categories Grid -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Kategorilerim</h6>
                </div>
                <div class="card-body">
                    <div class="row" id="categoriesContainer">
                        <?php if (empty($data['categories'])): ?>
                            <div class="col-12 text-center py-5">
                                <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Henüz kategori eklenmemiş</h5>
                                <p class="text-muted">İlk kategorinizi eklemek için yukarıdaki butona tıklayın.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($data['categories'] as $category): ?>
                                <div class="col-xl-4 col-lg-6 mb-4">
                                    <div class="card category-item h-100" style="border-left: 4px solid <?php echo $category['color']; ?>">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div>
                                                    <h5 class="card-title mb-1">
                                                        <i class="fas fa-<?php echo $category['icon']; ?>" style="color: <?php echo $category['color']; ?>"></i>
                                                        <?php echo htmlspecialchars($category['name']); ?>
                                                    </h5>
                                                    <small class="badge <?php echo $category['type'] === 'income' ? 'bg-success' : 'bg-danger'; ?>">
                                                        <?php echo $category['type'] === 'income' ? 'Gelir' : 'Gider'; ?>
                                                    </small>
                                                </div>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li><a class="dropdown-item edit-category" href="#" data-id="<?php echo $category['id']; ?>">
                                                            <i class="fas fa-edit"></i> Düzenle
                                                        </a></li>
                                                        <li><a class="dropdown-item" href="#" onclick="viewCategoryTransactions(<?php echo $category['id']; ?>)">
                                                            <i class="fas fa-list"></i> İşlemler
                                                        </a></li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li><a class="dropdown-item text-danger delete-category" href="#" data-id="<?php echo $category['id']; ?>">
                                                            <i class="fas fa-trash"></i> Sil
                                                        </a></li>
                                                    </ul>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <span class="small">İşlem Sayısı</span>
                                                    <span class="small font-weight-bold">
                                                        <?php echo $category['transaction_count']; ?>
                                                    </span>
                                                </div>
                                                <div class="progress" style="height: 8px;">
                                                    <?php 
                                                    $maxTransactions = max(array_column($data['categories'], 'transaction_count'));
                                                    $percentage = $maxTransactions > 0 ? ($category['transaction_count'] / $maxTransactions) * 100 : 0;
                                                    ?>
                                                    <div class="progress-bar" 
                                                         role="progressbar" 
                                                         style="width: <?php echo $percentage; ?>%; background-color: <?php echo $category['color']; ?>">
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="text-center">
                                                <button class="btn btn-outline-primary btn-sm w-100" onclick="viewCategoryTransactions(<?php echo $category['id']; ?>)">
                                                    <i class="fas fa-eye"></i> İşlemleri Görüntüle
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Usage Statistics DataTable -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-bar"></i> Kategori Kullanım İstatistikleri
                    </h6>
                    <div class="d-flex gap-2">
                        <select id="usageFilter" class="form-select form-select-sm" style="width: auto;">
                            <option value="">Tüm Kategoriler</option>
                            <option value="used">Kullanılan Kategoriler</option>
                            <option value="unused">Kullanılmayan Kategoriler</option>
                            <option value="income">Gelir Kategorileri</option>
                            <option value="expense">Gider Kategorileri</option>
                        </select>
                        <button class="btn btn-sm btn-outline-primary" onclick="refreshUsageStats()">
                            <i class="fas fa-sync-alt"></i> Yenile
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="categoryUsageTable" class="table table-bordered table-striped" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Kategori</th>
                                    <th>Tip</th>
                                    <th>Renk</th>
                                    <th>Kullanım Sayısı</th>
                                    <th>Son Kullanım</th>
                                    <th>Popülerlik</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $totalUsage = array_sum(array_column($data['categories'], 'transaction_count'));
                                foreach ($data['categories'] as $category): 
                                ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-<?= $category['icon'] ?> me-2" style="color: <?= $category['color'] ?>"></i>
                                            <strong><?= htmlspecialchars($category['name']) ?></strong>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge <?= $category['type'] === 'income' ? 'bg-success' : 'bg-danger' ?>">
                                            <?= $category['type'] === 'income' ? 'Gelir' : 'Gider' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge" style="background-color: <?= $category['color'] ?>; color: white;">
                                            <?= $category['color'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?= $category['transaction_count'] > 0 ? 'bg-success' : 'bg-secondary' ?>">
                                            <?= $category['transaction_count'] ?> işlem
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        if ($category['transaction_count'] > 0) {
                                            require_once __DIR__ . '/../../core/Database.php';
                                            $db = Database::getInstance()->getConnection();
                                            
                                            $stmt = $db->prepare("
                                                SELECT MAX(transaction_date) as last_used 
                                                FROM (
                                                    SELECT transaction_date 
                                                    FROM transactions 
                                                    WHERE category_id = ? AND user_id = ?
                                                    
                                                    UNION ALL
                                                    
                                                    SELECT transaction_date 
                                                    FROM credit_card_transactions 
                                                    WHERE category_id = ? AND user_id = ?
                                                ) as all_transactions
                                            ");
                                            $stmt->execute([$category['id'], $_SESSION['user_id'], $category['id'], $_SESSION['user_id']]);
                                            $lastUsed = $stmt->fetch(PDO::FETCH_ASSOC)['last_used'];
                                            echo $lastUsed ? date('d.m.Y', strtotime($lastUsed)) : '-';
                                        } else {
                                            echo '<span class="text-muted">Hiç kullanılmamış</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $popularity = $totalUsage > 0 ? ($category['transaction_count'] / $totalUsage) * 100 : 0;
                                        ?>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar" 
                                                 style="width: <?= $popularity ?>%; background-color: <?= $category['color'] ?>"
                                                 role="progressbar">
                                                <?= number_format($popularity, 1) ?>%
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-info" onclick="viewCategoryUsage(<?= $category['id'] ?>)" title="Detay">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-primary edit-category" data-id="<?= $category['id'] ?>" title="Düzenle">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-outline-danger delete-category" data-id="<?= $category['id'] ?>" title="Sil">
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
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Kategori Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addCategoryForm">
                    <div class="mb-3">
                        <label class="form-label">Kategori Adı *</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kategori Tipi *</label>
                        <select class="form-select" name="type" required>
                            <option value="">Tip seçin</option>
                            <option value="income">Gelir</option>
                            <option value="expense">Gider</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Renk</label>
                        <input type="color" class="form-control form-control-color" name="color" value="#007bff">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">İkon</label>
                        <select class="form-select" name="icon" required>
                            <option value="money-bill">Para</option>
                            <option value="shopping-cart">Alışveriş</option>
                            <option value="car">Araba</option>
                            <option value="home">Ev</option>
                            <option value="utensils">Yemek</option>
                            <option value="heartbeat">Sağlık</option>
                            <option value="graduation-cap">Eğitim</option>
                            <option value="plane">Seyahat</option>
                            <option value="gift">Hediye</option>
                            <option value="file-invoice">Faturalar</option>
                            <option value="plus-circle">Diğer Gelir</option>
                            <option value="ellipsis-h">Diğer</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" onclick="saveCategory()">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Kategori Düzenle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editCategoryForm">
                    <input type="hidden" name="id" id="editCategoryId">
                    <div class="mb-3">
                        <label class="form-label">Kategori Adı *</label>
                        <input type="text" class="form-control" name="name" id="editCategoryName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kategori Tipi *</label>
                        <select class="form-select" name="type" id="editCategoryType" required>
                            <option value="income">Gelir</option>
                            <option value="expense">Gider</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Renk</label>
                        <input type="color" class="form-control form-control-color" name="color" id="editCategoryColor">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">İkon</label>
                        <select class="form-select" name="icon" id="editCategoryIcon" required>
                            <option value="money-bill">Para</option>
                            <option value="shopping-cart">Alışveriş</option>
                            <option value="car">Araba</option>
                            <option value="home">Ev</option>
                            <option value="utensils">Yemek</option>
                            <option value="heartbeat">Sağlık</option>
                            <option value="graduation-cap">Eğitim</option>
                            <option value="plane">Seyahat</option>
                            <option value="gift">Hediye</option>
                            <option value="file-invoice">Faturalar</option>
                            <option value="plus-circle">Diğer Gelir</option>
                            <option value="ellipsis-h">Diğer</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" onclick="updateCategory()">Güncelle</button>
            </div>
        </div>
    </div>
</div>

<!-- Category Usage Detail Modal -->
<div class="modal fade" id="categoryUsageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-chart-line text-info"></i> <span id="categoryUsageModalTitle">Kategori Kullanım Detayı</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="categoryUsageContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Yükleniyor...</span>
                        </div>
                        <p class="mt-2">Kullanım detayları yükleniyor...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Kapat
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Category Confirmation Modal -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle"></i> Kategori Silme Onayı
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-danger fa-3x"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="mb-1">Bu işlem geri alınamaz!</h6>
                        <p class="mb-0 text-muted">
                            <strong id="deleteCategoryName"></strong> kategorisini silmek istediğinizden emin misiniz?
                        </p>
                    </div>
                </div>
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle"></i>
                    <strong>Uyarı:</strong> Bu kategori silindiğinde, bu kategoriye ait tüm işlemler "kategorisiz" olarak işaretlenecektir.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> İptal
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteCategory">
                    <i class="fas fa-trash"></i> Evet, Sil
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Custom CSS -->
<style>
.category-item {
    transition: transform 0.2s;
}

.category-item:hover {
    transform: translateY(-5px);
}

.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}

.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}

.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}

.border-left-danger {
    border-left: 0.25rem solid #e74a3b !important;
}

.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}

/* DataTable custom styles */
#categoryUsageTable {
    font-size: 0.875rem;
}

#categoryUsageTable th {
    background-color: #f8f9fc;
    border-color: #e3e6f0;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    color: #5a5c69;
}

.gap-2 {
    gap: 0.5rem;
}
</style>

<!-- Include DataTables CSS and JS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">

<!-- Include Toastr CSS and JS -->
<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<script>
// jQuery yüklendiğinden emin ol
$(document).ready(function() {
    console.log('Categories page ready, initializing...');
    
    // Configure Toastr
    toastr.options = {
        "closeButton": true,
        "debug": false,
        "newestOnTop": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "preventDuplicates": false,
        "onclick": null,
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": "5000",
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    };
    
    // Initialize DataTable
    initializeCategoryUsageTable();
});

let categoryUsageTable;

function initializeCategoryUsageTable() {
    console.log('Initializing Category Usage DataTable...');
    
    if ($.fn.DataTable.isDataTable('#categoryUsageTable')) {
        $('#categoryUsageTable').DataTable().destroy();
    }
    
    try {
        categoryUsageTable = $('#categoryUsageTable').DataTable({
            order: [[3, 'desc']], // Sort by usage count descending
            pageLength: 25,
            responsive: true,
            language: {
                "decimal": "",
                "emptyTable": "Tabloda herhangi bir veri mevcut değil",
                "info": "_TOTAL_ kayıttan _START_ - _END_ arasındaki kayıtlar gösteriliyor",
                "infoEmpty": "Kayıt yok",
                "infoFiltered": "(_MAX_ kayıt içerisinden bulunan)",
                "infoPostFix": "",
                "thousands": ".",
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
        
        // Filter event listener
        $('#usageFilter').on('change', function() {
            const filterValue = $(this).val();
            if (filterValue === 'used') {
                categoryUsageTable.column(3).search('^(?!.*0 işlem).*$', true, false).draw();
            } else if (filterValue === 'unused') {
                categoryUsageTable.column(3).search('0 işlem').draw();
            } else if (filterValue === 'income') {
                categoryUsageTable.column(1).search('Gelir').draw();
            } else if (filterValue === 'expense') {
                categoryUsageTable.column(1).search('Gider').draw();
            } else {
                categoryUsageTable.search('').columns().search('').draw();
            }
        });
        
    } catch (error) {
        console.error('DataTable initialization error:', error);
        showNotification('error', 'Kullanım tablosu yüklenirken hata oluştu: ' + error.message);
    }
}

function viewCategoryTransactions(categoryId) {
    // Scroll to usage table
    $('html, body').animate({
        scrollTop: $('#categoryUsageTable').offset().top - 100
    }, 1000);
    
    showNotification('info', 'Kategori kullanım istatistikleri tablosuna yönlendirildi.');
}

function refreshUsageStats() {
    location.reload();
}

function saveCategory() {
    const formData = new FormData(document.getElementById('addCategoryForm'));
    formData.append('ajax', '1');
    
    $.ajax({
        url: '/gelirgider/app/controllers/CategoryController.php?action=create',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#addCategoryModal').modal('hide');
                showNotification('success', 'Kategori başarıyla eklendi');
                location.reload();
            } else {
                showNotification('error', response.message);
            }
        },
        error: function() {
            showNotification('error', 'Kategori eklenirken bir hata oluştu.');
        }
    });
}

// Edit Category
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.edit-category').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.dataset.id;
            
            $.ajax({
                url: '/gelirgider/app/controllers/CategoryController.php?action=get',
                type: 'GET',
                data: { id: id },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const category = response.data;
                        
                        $('#editCategoryId').val(category.id);
                        $('#editCategoryName').val(category.name);
                        $('#editCategoryType').val(category.type);
                        $('#editCategoryColor').val(category.color);
                        $('#editCategoryIcon').val(category.icon);
                        
                        $('#editCategoryModal').modal('show');
                    } else {
                        showNotification('error', response.message);
                    }
                },
                error: function() {
                    showNotification('error', 'Kategori bilgileri yüklenirken hata oluştu.');
                }
            });
        });
    });
    
    // Delete Category
    let categoryToDelete = null;
    
    document.querySelectorAll('.delete-category').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const id = this.dataset.id;
            const categoryName = this.closest('tr').querySelector('strong').textContent || 
                                this.closest('.card').querySelector('.card-title').textContent.trim();
            
            // Store category info for deletion
            categoryToDelete = {
                id: id,
                name: categoryName
            };
            
            // Set category name in modal
            $('#deleteCategoryName').text(categoryName);
            
            // Show delete confirmation modal
            $('#deleteCategoryModal').modal('show');
        });
    });
    
    // Handle delete confirmation
    $('#confirmDeleteCategory').on('click', function() {
        if (categoryToDelete) {
            // Disable button to prevent double-click
            $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Siliniyor...');
            
            $.ajax({
                url: '/gelirgider/app/controllers/CategoryController.php?action=delete',
                type: 'POST',
                data: { id: categoryToDelete.id, ajax: '1' },
                dataType: 'json',
                success: function(response) {
                    $('#deleteCategoryModal').modal('hide');
                    
                    if (response.success) {
                        showNotification('success', `"${categoryToDelete.name}" kategorisi başarıyla silindi`);
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        showNotification('error', response.message);
                        $('#confirmDeleteCategory').prop('disabled', false).html('<i class="fas fa-trash"></i> Evet, Sil');
                    }
                },
                error: function() {
                    $('#deleteCategoryModal').modal('hide');
                    showNotification('error', 'Kategori silinirken hata oluştu');
                    $('#confirmDeleteCategory').prop('disabled', false).html('<i class="fas fa-trash"></i> Evet, Sil');
                }
            });
        }
    });
    
    // Reset delete button when modal is hidden
    $('#deleteCategoryModal').on('hidden.bs.modal', function() {
        $('#confirmDeleteCategory').prop('disabled', false).html('<i class="fas fa-trash"></i> Evet, Sil');
        categoryToDelete = null;
    });
});

function updateCategory() {
    const formData = new FormData(document.getElementById('editCategoryForm'));
    formData.append('ajax', '1');
    
    $.ajax({
        url: '/gelirgider/app/controllers/CategoryController.php?action=update',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#editCategoryModal').modal('hide');
                showNotification('success', 'Kategori başarıyla güncellendi');
                location.reload();
            } else {
                showNotification('error', response.message);
            }
        },
        error: function() {
            showNotification('error', 'Kategori güncellenirken bir hata oluştu.');
        }
    });
}

function showNotification(type, message) {
    // Show notification based on type
    switch(type) {
        case 'success':
            toastr.success(message, 'Başarılı');
            break;
        case 'error':
            toastr.error(message, 'Hata');
            break;
        case 'warning':
            toastr.warning(message, 'Uyarı');
            break;
        case 'info':
        default:
            toastr.info(message, 'Bilgi');
            break;
    }
}

function viewCategoryUsage(categoryId) {
    $('#categoryUsageModalTitle').text('Kategori Kullanım Detayı');
    $('#categoryUsageModal').modal('show');
    
    // Load category usage details
    $.ajax({
        url: '/gelirgider/app/controllers/CategoryController.php?action=getTransactions',
        type: 'GET',
        data: { category_id: categoryId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const transactions = response.data;
                let content = '';
                
                if (transactions.length === 0) {
                    content = '<div class="text-center py-4"><p class="text-muted">Bu kategori henüz hiçbir işlemde kullanılmamış.</p></div>';
                } else {
                    // Summary
                    const incomeTransactions = transactions.filter(t => t.type === 'income');
                    const expenseTransactions = transactions.filter(t => t.type === 'expense');
                    
                    content = `
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="card border-primary">
                                    <div class="card-body text-center">
                                        <h5 class="text-primary">${transactions.length}</h5>
                                        <small>Toplam İşlem</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-success">
                                    <div class="card-body text-center">
                                        <h5 class="text-success">${incomeTransactions.length}</h5>
                                        <small>Gelir İşlemi</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-danger">
                                    <div class="card-body text-center">
                                        <h5 class="text-danger">${expenseTransactions.length}</h5>
                                        <small>Gider İşlemi</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Tarih</th>
                                        <th>Tip</th>
                                        <th>Kaynak</th>
                                        <th>Miktar</th>
                                        <th>Açıklama</th>
                                    </tr>
                                </thead>
                                <tbody>
                    `;
                    
                    transactions.forEach(function(transaction) {
                        const date = new Date(transaction.transaction_date).toLocaleDateString('tr-TR');
                        const typeClass = transaction.type === 'income' ? 'success' : 'danger';
                        const typeText = transaction.type === 'income' ? 'Gelir' : 'Gider';
                        const amountFormatted = parseFloat(transaction.amount).toLocaleString('tr-TR', {minimumFractionDigits: 2});
                        
                        content += `
                            <tr>
                                <td>${date}</td>
                                <td><span class="badge bg-${typeClass}">${typeText}</span></td>
                                <td><i class="fas fa-${transaction.source_type === 'wallet' ? 'wallet' : 'credit-card'}"></i> ${transaction.source_name}</td>
                                <td class="text-${typeClass}">${amountFormatted} ₺</td>
                                <td>${transaction.description || '-'}</td>
                            </tr>
                        `;
                    });
                    
                    content += '</tbody></table></div>';
                }
                
                $('#categoryUsageContent').html(content);
            } else {
                $('#categoryUsageContent').html('<div class="alert alert-danger">Detaylar yüklenirken hata oluştu: ' + response.message + '</div>');
            }
        },
        error: function() {
            $('#categoryUsageContent').html('<div class="alert alert-danger">Detaylar yüklenirken hata oluştu.</div>');
        }
    });
}
</script>

<?php include '../layouts/footer.php'; ?> 