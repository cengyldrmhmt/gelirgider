<?php
session_start();

// Kullanıcı giriş kontrolü
if (!isset($_SESSION['user_id'])) {
    header('Location: /gelirgider/app/views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../controllers/TransactionController.php';
require_once __DIR__ . '/../../models/Wallet.php';
require_once __DIR__ . '/../../models/CreditCard.php';
require_once __DIR__ . '/../../models/Category.php';

$transactionController = new TransactionController();
$walletModel = new Wallet();
$creditCardModel = new CreditCard();
$categoryModel = new Category();

$wallets = $walletModel->getAll($_SESSION['user_id']);
$creditCards = $creditCardModel->getAll($_SESSION['user_id']);
$categories = $categoryModel->getAll($_SESSION['user_id']);

// Header'ı dahil et
include __DIR__ . '/../layouts/header.php';

// Sidebar'ı dahil et
require_once __DIR__ . '/../layouts/sidebar.php';

?>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Include DataTables CSS and JS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">

<!-- Custom CSS -->
<link rel="stylesheet" href="/gelirgider/public/css/transactions/style.css">

<!-- Custom JS -->
<script src="/gelirgider/public/js/transactions/script.js" defer></script>

<!-- Transactions Content -->
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-list text-primary"></i> Tüm İşlemler
        </h1>
        <div class="d-flex gap-2">
            <a href="/gelirgider/app/views/transactions/create.php?type=income" class="btn btn-success">
                <i class="fas fa-plus"></i> Gelir Ekle
            </a>
            <a href="/gelirgider/app/views/transactions/create.php?type=expense" class="btn btn-danger">
                <i class="fas fa-minus"></i> Gider Ekle
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Bu Ay Toplam Gelir</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="monthlyIncome">
                                Yükleniyor...
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
                                Bu Ay Toplam Gider</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="monthlyExpense">
                                Yükleniyor...
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
                                Bu Ay Net Durum</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="monthlyNet">
                                Yükleniyor...
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-balance-scale fa-2x text-info"></i>
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
                                Toplam İşlem Sayısı</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalTransactions">
                                Yükleniyor...
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-list fa-2x text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- All Transactions DataTable -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-table"></i> Tüm İşlemler
                    </h6>
                    <div class="d-flex gap-2 flex-wrap">
                        <select id="sourceFilter" class="form-select form-select-sm" style="width: auto;">
                            <option value="">Tüm Kaynaklar</option>
                            <option value="wallet">Cüzdanlar</option>
                            <option value="credit_card">Kredi Kartları</option>
                        </select>
                        <select id="typeFilter" class="form-select form-select-sm" style="width: auto;">
                            <option value="">Tüm İşlemler</option>
                            <option value="income">Gelir</option>
                            <option value="expense">Gider</option>
                            <option value="purchase">Satın Alma</option>
                            <option value="payment">Ödeme</option>
                            <option value="installment">Taksit</option>
                        </select>
                        <select id="categoryFilter" class="form-select form-select-sm" style="width: auto;">
                            <option value="">Tüm Kategoriler</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select id="tagFilter" class="form-select form-select-sm" style="width: auto;">
                            <option value="">Tüm Etiketler</option>
                            <!-- Etiketler AJAX ile yüklenecek -->
                        </select>
                        <input type="date" id="dateFromFilter" class="form-control form-control-sm" style="width: auto;" placeholder="Başlangıç Tarihi">
                        <input type="date" id="dateToFilter" class="form-control form-control-sm" style="width: auto;" placeholder="Bitiş Tarihi">
                        <button class="btn btn-sm btn-outline-primary" onclick="refreshTransactions()">
                            <i class="fas fa-sync-alt"></i> Yenile
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="clearFilters()">
                            <i class="fas fa-times"></i> Temizle
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="allTransactionsTable" class="table table-bordered table-striped" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Tarih</th>
                                    <th>Kaynak</th>
                                    <th>İşlem Türü</th>
                                    <th>Kategori</th>
                                    <th>Etiketler</th>
                                    <th>Açıklama</th>
                                    <th>Tutar</th>
                                    <th>Para Birimi</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- DataTable will populate this -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Footer'ı dahil et
include __DIR__ . '/../layouts/footer.php';
?> 