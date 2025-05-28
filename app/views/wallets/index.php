<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /gelirgider/app/views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../controllers/WalletController.php';
require_once __DIR__ . '/../../models/Category.php';
require_once __DIR__ . '/../../models/ExchangeRate.php';
require_once __DIR__ . '/../layouts/header.php';

$controller = new WalletController();
$data = $controller->index();

$categoryModel = new Category();
$categories = $categoryModel->getAll($_SESSION['user_id']);

$exchangeRateModel = new ExchangeRate();

include '../layouts/sidebar.php';
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-wallet text-primary"></i> Cüzdanlar
        </h1>
        <div class="d-flex gap-2">
            <button class="btn btn-info btn-sm" onclick="updateExchangeRates()" title="Döviz Kurlarını Güncelle">
                <i class="fas fa-sync-alt"></i> Kurları Güncelle
            </button>
            <button class="btn btn-warning btn-sm" onclick="forceUpdateExchangeRates()" title="Zorla Güncelle (Önbelleği Temizle)">
                <i class="fas fa-redo"></i> Zorla Güncelle
            </button>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addWalletModal">
                <i class="fas fa-plus"></i> Yeni Cüzdan
            </button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Toplam Cüzdan</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo count($data['wallets']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-wallet fa-2x text-primary"></i>
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
                                Toplam Bakiye (TRY)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php 
                                $totalBalance = 0;
                                foreach ($data['wallets'] as $wallet) {
                                    $balance = $wallet['real_balance'];
                                    // Döviz kurları ile TRY'ye çevir
                                    if ($wallet['currency'] !== 'TRY') {
                                        $convertedBalance = $exchangeRateModel->convertToTRY($balance, $wallet['currency']);
                                        $totalBalance += $convertedBalance;
                                    } else {
                                        $totalBalance += $balance;
                                    }
                                }
                                echo number_format($totalBalance, 2);
                                ?> ₺
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-money-bill-wave fa-2x text-success"></i>
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
                                Para Birimleri</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php 
                                $currencies = array_unique(array_column($data['wallets'], 'currency'));
                                echo count($currencies);
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-coins fa-2x text-info"></i>
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
                                En Yüksek Bakiye</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php 
                                if (!empty($data['wallets'])) {
                                    $maxWallet = array_reduce($data['wallets'], function($max, $wallet) {
                                        return ($wallet['real_balance'] > ($max['real_balance'] ?? 0)) ? $wallet : $max;
                                    });
                                    if ($maxWallet && isset($maxWallet['real_balance'])) {
                                        echo number_format($maxWallet['real_balance'], 2) . ' ' . ($maxWallet['currency'] ?? '₺');
                                    } else {
                                        echo '0.00 ₺';
                                    }
                                } else {
                                    echo '0.00 ₺';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Wallets Grid -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Cüzdanlarım</h6>
                </div>
                <div class="card-body">
                    <div class="row" id="walletsContainer">
                        <?php if (empty($data['wallets'])): ?>
                            <div class="col-12 text-center py-5">
                                <i class="fas fa-wallet fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Henüz cüzdan eklenmemiş</h5>
                                <p class="text-muted">İlk cüzdanınızı eklemek için yukarıdaki butona tıklayın.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($data['wallets'] as $wallet): ?>
                                <div class="col-xl-4 col-lg-6 mb-4">
                                    <div class="card wallet-item h-100" style="border-left: 4px solid <?php echo $wallet['color']; ?>" data-wallet-id="<?php echo $wallet['id']; ?>">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div>
                                                    <h5 class="card-title mb-1">
                                                        <i class="fas fa-<?php echo $wallet['icon']; ?>" style="color: <?php echo $wallet['color']; ?>"></i>
                                                        <?php echo htmlspecialchars($wallet['name']); ?>
                                                    </h5>
                                                    <small class="text-muted">
                                                        <?php echo $wallet['currency']; ?>
                                                        <?php if ($wallet['currency'] !== 'TRY'): ?>
                                                            <?php 
                                                            $rateInfo = $exchangeRateModel->getFormattedRate($wallet['currency']);
                                                            if ($rateInfo): ?>
                                                                <span class="exchange-rate-info text-success ms-1">
                                                                    (1 <?php echo $wallet['currency']; ?> = <?php echo $rateInfo['rate']; ?> ₺)
                                                                </span>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" data-bs-boundary="viewport" data-bs-auto-close="true">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li><a class="dropdown-item" href="#" onclick="editWallet(<?php echo $wallet['id']; ?>)">
                                                            <i class="fas fa-edit"></i> Düzenle
                                                        </a></li>
                                                        <li><a class="dropdown-item" href="#" onclick="viewTransactions(<?php echo $wallet['id']; ?>)">
                                                            <i class="fas fa-list"></i> İşlemler
                                                        </a></li>
                                                        <li><a class="dropdown-item" href="#" onclick="depositMoney(<?php echo $wallet['id']; ?>)">
                                                            <i class="fas fa-plus-circle"></i> Para Yatır
                                                        </a></li>
                                                        <li><a class="dropdown-item" href="#" onclick="withdrawMoney(<?php echo $wallet['id']; ?>)">
                                                            <i class="fas fa-minus-circle"></i> Para Çek
                                                        </a></li>
                                                        <li><a class="dropdown-item" href="#" onclick="transferMoney(<?php echo $wallet['id']; ?>)">
                                                            <i class="fas fa-exchange-alt"></i> Transfer
                                                        </a></li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li><a class="dropdown-item text-danger delete-wallet" href="#" data-id="<?php echo $wallet['id']; ?>" data-name="<?php echo htmlspecialchars($wallet['name']); ?>">
                                                            <i class="fas fa-trash"></i> Sil
                                                        </a></li>
                                                    </ul>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <span class="h4 mb-0 font-weight-bold" style="color: <?php echo $wallet['color']; ?>">
                                                            <?php echo number_format($wallet['real_balance'], 2); ?> <?php echo $wallet['currency']; ?>
                                                        </span>
                                                        
                                                        <!-- Foreign currency conversion -->
                                                        <?php if ($wallet['currency'] !== 'TRY'): ?>
                                                            <?php 
                                                            $rateInfo = $exchangeRateModel->getFormattedRate($wallet['currency']);
                                                            $convertedAmount = $exchangeRateModel->convertToTRY($wallet['real_balance'], $wallet['currency']);
                                                            ?>
                                                            <div class="mt-1">
                                                                <small class="text-success fw-bold">
                                                                    ≈ <?php echo number_format($convertedAmount, 2); ?> ₺
                                                                </small>
                                                                <?php if ($rateInfo): ?>
                                                                    <small class="text-muted d-block">
                                                                        <i class="fas fa-exchange-alt me-1"></i>
                                                                        1 <?php echo $wallet['currency']; ?> = <?php echo $rateInfo['rate']; ?> ₺
                                                                        <span class="badge badge-light ms-1"><?php echo $rateInfo['updated_at']; ?></span>
                                                                    </small>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <?php if ($wallet['currency'] !== 'TRY'): ?>
                                                        <div class="text-end">
                                                            <div class="currency-badge">
                                                                <span class="badge bg-primary"><?php echo $wallet['currency']; ?></span>
                                                                <i class="fas fa-arrow-right mx-1 text-muted"></i>
                                                                <span class="badge bg-success">₺</span>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <div class="btn-group w-100">
                                                <button class="btn btn-outline-success btn-sm" onclick="depositMoney(<?php echo $wallet['id']; ?>)">
                                                    <i class="fas fa-plus"></i> Yatır
                                                </button>
                                                <button class="btn btn-outline-danger btn-sm" onclick="withdrawMoney(<?php echo $wallet['id']; ?>)">
                                                    <i class="fas fa-minus"></i> Çek
                                                </button>
                                                <button class="btn btn-outline-info btn-sm" onclick="transferMoney(<?php echo $wallet['id']; ?>)">
                                                    <i class="fas fa-exchange-alt"></i> Transfer
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

    <!-- Wallet Transactions DataTable -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-list"></i> Cüzdan İşlemleri
                    </h6>
                    <div class="d-flex gap-2">
                        <select id="walletFilter" class="form-select form-select-sm" style="width: auto;">
                            <option value="">Tüm Cüzdanlar</option>
                            <?php foreach ($data['wallets'] as $wallet): ?>
                                <option value="<?php echo $wallet['id']; ?>">
                                    <?php echo htmlspecialchars($wallet['name']); ?> (<?php echo $wallet['currency']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select id="typeFilter" class="form-select form-select-sm" style="width: auto;">
                            <option value="">Tüm Tiplar</option>
                            <option value="income">Gelir</option>
                            <option value="expense">Gider</option>
                            <option value="transfer">Transfer</option>
                        </select>
                        <button class="btn btn-sm btn-outline-primary" onclick="refreshTransactions()">
                            <i class="fas fa-sync-alt"></i> Yenile
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="walletTransactionsTable" class="table table-bordered table-striped" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Tarih</th>
                                    <th>Cüzdan</th>
                                    <th>Tip</th>
                                    <th>Kategori</th>
                                    <th>Açıklama</th>
                                    <th>Miktar</th>
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

<!-- Add Wallet Modal -->
<div class="modal fade" id="addWalletModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Cüzdan Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addWalletForm">
                    <div class="mb-3">
                        <label class="form-label">Cüzdan Adı *</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Para Birimi *</label>
                        <select class="form-select" name="currency" required>
                            <option value="">Para birimi seçin</option>
                            <option value="TRY">Türk Lirası (TRY)</option>
                            <option value="USD">Amerikan Doları (USD)</option>
                            <option value="EUR">Euro (EUR)</option>
                            <option value="GBP">İngiliz Sterlini (GBP)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Başlangıç Bakiyesi</label>
                        <input type="number" class="form-control" name="balance" value="0" step="0.01" min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Renk</label>
                        <input type="color" class="form-control form-control-color" name="color" value="#007bff">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">İkon</label>
                        <select class="form-select" name="icon" required>
                            <option value="wallet">Cüzdan</option>
                            <option value="credit-card">Kredi Kartı</option>
                            <option value="piggy-bank">Kumbara</option>
                            <option value="university">Banka</option>
                            <option value="coins">Bozuk Para</option>
                            <option value="money-bill">Nakit</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" onclick="saveWallet()">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Wallet Confirmation Modal -->
<div class="modal fade" id="deleteWalletModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle"></i> Cüzdan Silme Onayı
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
                            <strong id="deleteWalletName"></strong> cüzdanını silmek istediğinizden emin misiniz?
                        </p>
                    </div>
                </div>
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle"></i>
                    <strong>Uyarı:</strong> Bu cüzdan silindiğinde, bu cüzdana ait tüm işlemler de kalıcı olarak silinecektir.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> İptal
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteWallet">
                    <i class="fas fa-trash"></i> Evet, Sil
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Wallet Modal -->
<div class="modal fade" id="editWalletModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit"></i> Cüzdan Düzenle
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editWalletForm">
                    <input type="hidden" id="editWalletId" name="id">
                    <div class="mb-3">
                        <label class="form-label">Cüzdan Adı *</label>
                        <input type="text" class="form-control" id="editWalletName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Para Birimi *</label>
                        <select class="form-select" id="editWalletCurrency" name="currency" required>
                            <option value="TRY">Türk Lirası (TRY)</option>
                            <option value="USD">Amerikan Doları (USD)</option>
                            <option value="EUR">Euro (EUR)</option>
                            <option value="GBP">İngiliz Sterlini (GBP)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mevcut Bakiye</label>
                        <input type="number" class="form-control" id="editWalletBalance" name="balance" step="0.01">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tip</label>
                        <select class="form-select" id="editWalletType" name="type">
                            <option value="cash">Nakit</option>
                            <option value="bank">Banka</option>
                            <option value="credit">Kredi</option>
                            <option value="savings">Tasarruf</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Renk</label>
                        <input type="color" class="form-control form-control-color" id="editWalletColor" name="color">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">İkon</label>
                        <select class="form-select" id="editWalletIcon" name="icon">
                            <option value="wallet">Cüzdan</option>
                            <option value="credit-card">Kredi Kartı</option>
                            <option value="piggy-bank">Kumbara</option>
                            <option value="university">Banka</option>
                            <option value="coins">Bozuk Para</option>
                            <option value="money-bill">Nakit</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" onclick="updateWallet()">Güncelle</button>
            </div>
        </div>
    </div>
</div>

<!-- Deposit Money Modal -->
<div class="modal fade" id="depositModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle"></i> Para Yatır
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="depositForm">
                    <input type="hidden" id="depositWalletId" name="wallet_id">
                    <div class="mb-3">
                        <label class="form-label">Cüzdan</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-wallet"></i></span>
                            <input type="text" class="form-control" id="depositWalletName" readonly>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Miktar *</label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="amount" step="0.01" min="0" required>
                            <span class="input-group-text" id="depositCurrency">TRY</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select class="form-select" name="category_id">
                            <option value="">Kategori seçin (opsiyonel)</option>
                            <?php foreach ($categories as $category): ?>
                                <?php if ($category['type'] === 'income'): ?>
                                    <option value="<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <textarea class="form-control" name="description" rows="2" placeholder="Para yatırma açıklaması..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tarih</label>
                        <input type="datetime-local" class="form-control" name="transaction_date" value="<?php echo date('Y-m-d\TH:i'); ?>">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-success" onclick="processDeposit()">
                    <i class="fas fa-plus-circle"></i> Para Yatır
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Withdraw Money Modal -->
<div class="modal fade" id="withdrawModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-minus-circle"></i> Para Çek
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="withdrawForm">
                    <input type="hidden" id="withdrawWalletId" name="wallet_id">
                    <div class="mb-3">
                        <label class="form-label">Cüzdan</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-wallet"></i></span>
                            <input type="text" class="form-control" id="withdrawWalletName" readonly>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mevcut Bakiye</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-money-bill-wave"></i></span>
                            <input type="text" class="form-control" id="withdrawCurrentBalance" readonly>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Miktar *</label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="amount" step="0.01" min="0" required>
                            <span class="input-group-text" id="withdrawCurrency">TRY</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select class="form-select" name="category_id">
                            <option value="">Kategori seçin (opsiyonel)</option>
                            <?php foreach ($categories as $category): ?>
                                <?php if ($category['type'] === 'expense'): ?>
                                    <option value="<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <textarea class="form-control" name="description" rows="2" placeholder="Para çekme açıklaması..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tarih</label>
                        <input type="datetime-local" class="form-control" name="transaction_date" value="<?php echo date('Y-m-d\TH:i'); ?>">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-danger" onclick="processWithdraw()">
                    <i class="fas fa-minus-circle"></i> Para Çek
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Transfer Money Modal -->
<div class="modal fade" id="transferModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exchange-alt"></i> Para Transfer Et
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="transferForm">
                    <input type="hidden" id="transferSourceWalletId" name="source_wallet_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Kaynak Cüzdan</label>
                        <input type="text" class="form-control" id="transferSourceWalletName" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Mevcut Bakiye</label>
                        <input type="text" class="form-control" id="transferCurrentBalance" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Hedef Cüzdan *</label>
                        <select class="form-select" name="target_wallet_id" id="transferTargetWallet" required>
                            <option value="">Hedef cüzdan seçin</option>
                            <?php foreach ($data['wallets'] as $wallet): ?>
                                <option value="<?php echo $wallet['id']; ?>" data-currency="<?php echo $wallet['currency']; ?>">
                                    <?php echo htmlspecialchars($wallet['name']); ?> (<?php echo $wallet['currency']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Miktar *</label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="amount" step="0.01" min="0" required>
                            <span class="input-group-text" id="transferCurrency">TRY</span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <textarea class="form-control" name="description" rows="2" placeholder="Transfer açıklaması..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tarih</label>
                        <input type="datetime-local" class="form-control" name="transaction_date" value="<?php echo date('Y-m-d\TH:i'); ?>">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-info" onclick="processTransfer()">
                    <i class="fas fa-exchange-alt"></i> Transfer Et
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Transaction Modal -->
<div class="modal fade" id="editTransactionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title">
                    <i class="fas fa-edit"></i> İşlem Düzenle
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editTransactionForm">
                    <input type="hidden" id="editTransactionId" name="id">
                    
                    <div class="mb-3">
                        <label class="form-label">Cüzdan *</label>
                        <select class="form-select" id="editTransactionWalletId" name="wallet_id" required>
                            <?php foreach ($data['wallets'] as $wallet): ?>
                                <option value="<?php echo $wallet['id']; ?>">
                                    <?php echo htmlspecialchars($wallet['name']); ?> (<?php echo $wallet['currency']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">İşlem Tipi *</label>
                        <select class="form-select" id="editTransactionType" name="type" required>
                            <option value="income">Gelir</option>
                            <option value="expense">Gider</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Miktar *</label>
                        <input type="number" class="form-control" id="editTransactionAmount" name="amount" step="0.01" min="0" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select class="form-select" id="editTransactionCategoryId" name="category_id">
                            <option value="">Kategori seçin (opsiyonel)</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" data-type="<?php echo $category['type']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?> (<?php echo ucfirst($category['type']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <textarea class="form-control" id="editTransactionDescription" name="description" rows="2" placeholder="İşlem açıklaması..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tarih</label>
                        <input type="datetime-local" class="form-control" id="editTransactionDate" name="transaction_date">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-warning" onclick="updateTransaction()">
                    <i class="fas fa-save"></i> Güncelle
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Transaction Confirmation Modal -->
<div class="modal fade" id="deleteTransactionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle"></i> İşlem Silme Onayı
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
                            Bu işlemi silmek istediğinizden emin misiniz?
                        </p>
                    </div>
                </div>
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle"></i>
                    <strong>Uyarı:</strong> Bu işlem silindiğinde, cüzdan bakiyesi otomatik olarak güncellenecektir.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> İptal
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteTransactionBtn" onclick="confirmDeleteTransaction()">
                    <i class="fas fa-trash"></i> Evet, Sil
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Include External CSS -->
<link rel="stylesheet" href="/gelirgider/public/css/wallets/style.css">

<!-- Include DataTables CSS and JS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">

<!-- Include Toastr CSS and JS -->
<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<!-- DataTables JS -->
<script type="text/javascript" src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

<!-- Include External JS -->
<script src="/gelirgider/public/js/wallets/script.js"></script>

<?php include '../layouts/footer.php'; ?> 