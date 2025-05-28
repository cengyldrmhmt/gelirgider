<?php
session_start();

// Kullanıcı giriş kontrolü
if (!isset($_SESSION['user_id'])) {
    header('Location: /gelirgider/app/views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../controllers/CreditCardController.php';
require_once __DIR__ . '/../../models/Category.php';
require_once __DIR__ . '/../../models/Wallet.php';

$creditCardController = new CreditCardController();
$data = $creditCardController->index();

$categoryModel = new Category();
$categories = $categoryModel->getAll($_SESSION['user_id']);

$walletModel = new Wallet();
$wallets = $walletModel->getAll($_SESSION['user_id']);

// Header'ı dahil et
include __DIR__ . '/../layouts/header.php';

// Sidebar'ı dahil et
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<!-- Credit Cards Content -->
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-credit-card text-primary"></i> Kredi Kartları
        </h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCreditCardModal">
            <i class="fas fa-plus"></i> Yeni Kredi Kartı
        </button>
    </div>

    <!-- Upcoming Payment Notifications -->
    <?php if (!empty($data['upcoming_payments'])): ?>
    <div class="row mb-4">
        <div class="col-12">
            <?php foreach ($data['upcoming_payments'] as $payment): ?>
                <?php 
                $daysUntilDue = ceil((strtotime($payment['next_due_date']) - time()) / (60 * 60 * 24));
                $alertClass = $daysUntilDue <= 3 ? 'alert-danger' : ($daysUntilDue <= 7 ? 'alert-warning' : 'alert-info');
                $icon = $daysUntilDue <= 3 ? 'fas fa-exclamation-triangle' : ($daysUntilDue <= 7 ? 'fas fa-clock' : 'fas fa-info-circle');
                ?>
                <div class="alert <?php echo $alertClass; ?> alert-dismissible fade show" role="alert">
                    <i class="<?php echo $icon; ?> me-2"></i>
                    <strong><?php echo htmlspecialchars($payment['card_name']); ?></strong> kartınızın 
                    <strong><?php echo number_format($payment['current_balance'], 2); ?> ₺</strong> borcu var. 
                    Son ödeme tarihi: <strong><?php echo date('d.m.Y', strtotime($payment['next_due_date'])); ?></strong>
                    (<?php echo $daysUntilDue; ?> gün kaldı)
                    <div class="btn-group ms-2" role="group">
                        <button type="button" class="btn btn-sm btn-outline-<?php echo $daysUntilDue <= 3 ? 'light' : 'dark'; ?>" 
                                onclick="makePayment(<?php echo $payment['card_id']; ?>)">
                            <i class="fas fa-money-bill"></i> Hemen Öde
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-<?php echo $daysUntilDue <= 3 ? 'light' : 'dark'; ?>" 
                                onclick="editUpcomingPayment(<?php echo $payment['card_id']; ?>)">
                            <i class="fas fa-edit"></i> Düzenle
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-<?php echo $daysUntilDue <= 3 ? 'light' : 'dark'; ?>" 
                                onclick="deleteUpcomingPayment(<?php echo $payment['card_id']; ?>)">
                            <i class="fas fa-trash"></i> Sil
                        </button>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Toplam Limit</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($data['total_limits']['total_limit'] ?? 0, 2); ?> ₺
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-credit-card fa-2x text-primary"></i>
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
                                Kullanılan Limit</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($data['total_limits']['total_used'] ?? 0, 2); ?> ₺
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-pie fa-2x text-danger"></i>
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
                                Kullanılabilir Limit</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($data['total_limits']['total_available'] ?? 0, 2); ?> ₺
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-wallet fa-2x text-success"></i>
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
                                Yaklaşan Ödemeler</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo count($data['upcoming_payments']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-alt fa-2x text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Credit Cards Grid -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Kredi Kartlarım</h6>
                </div>
                <div class="card-body">
                    <div class="row" id="creditCardsContainer">
                        <?php if (empty($data['credit_cards'])): ?>
                            <div class="col-12 text-center py-5">
                                <i class="fas fa-credit-card fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Henüz kredi kartı eklenmemiş</h5>
                                <p class="text-muted">İlk kredi kartınızı eklemek için yukarıdaki butona tıklayın.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($data['credit_cards'] as $card): ?>
                                <div class="col-xl-4 col-lg-6 mb-4">
                                    <div class="card credit-card-item h-100" style="border-left: 4px solid <?php echo $card['color']; ?>">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div>
                                                    <h5 class="card-title mb-1"><?php echo htmlspecialchars($card['name']); ?></h5>
                                                    <small class="text-muted">
                                                        <?php echo htmlspecialchars($card['bank_name'] ?? ''); ?>
                                                        <?php if ($card['card_number_last4']): ?>
                                                            **** <?php echo $card['card_number_last4']; ?>
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" style="z-index: 1050;">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end" style="z-index: 1051;">
                                                        <li><a class="dropdown-item" href="#" onclick="editCreditCard(<?php echo $card['id']; ?>)">
                                                            <i class="fas fa-edit"></i> Düzenle
                                                        </a></li>
                                                        <li><a class="dropdown-item" href="#" onclick="viewTransactions(<?php echo $card['id']; ?>)">
                                                            <i class="fas fa-list"></i> İşlemler
                                                        </a></li>
                                                        <li><a class="dropdown-item" href="#" onclick="makePayment(<?php echo $card['id']; ?>)">
                                                            <i class="fas fa-money-bill"></i> Ödeme Yap
                                                        </a></li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li><a class="dropdown-item text-danger" href="#" onclick="deleteCreditCard(<?php echo $card['id']; ?>)">
                                                            <i class="fas fa-trash"></i> Sil
                                                        </a></li>
                                                    </ul>
                                                </div>
                                            </div>
                                            
                                                                        <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="small">Kullanılan Limit</span>
                                    <span class="small font-weight-bold">
                                        <?php echo number_format($card['real_used_limit'], 2); ?> / <?php echo number_format($card['credit_limit'], 2); ?> ₺
                                    </span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <?php 
                                    $usagePercentage = $card['credit_limit'] > 0 ? ($card['real_used_limit'] / $card['credit_limit']) * 100 : 0;
                                    $progressClass = $usagePercentage > 80 ? 'bg-danger' : ($usagePercentage > 60 ? 'bg-warning' : 'bg-success');
                                    ?>
                                    <div class="progress-bar <?php echo $progressClass; ?>" 
                                         role="progressbar" 
                                         style="width: <?php echo min($usagePercentage, 100); ?>%">
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between mt-2">
                                    <span class="small text-success">
                                        <i class="fas fa-check-circle"></i> Kullanılabilir Bakiye
                                    </span>
                                    <span class="small font-weight-bold text-success">
                                        <?php echo number_format($card['real_available_limit'], 2); ?> ₺
                                    </span>
                                </div>
                            </div>
                                            
                                            <div class="row text-center">
                                                <div class="col-6">
                                                    <a href="/gelirgider/app/views/credit-cards/add.php?card_id=<?php echo $card['id']; ?>" class="btn btn-primary btn-sm w-100">
                                                        <i class="fas fa-shopping-cart"></i> Harcama
                                                    </a>
                                                </div>
                                                <div class="col-6">
                                                    <button class="btn btn-success btn-sm w-100" onclick="makePayment(<?php echo $card['id']; ?>)">
                                                        <i class="fas fa-money-bill"></i> Ödeme
                                                    </button>
                                                </div>
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

    <!-- Upcoming Payments -->
    <?php if (!empty($data['upcoming_payments'])): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-warning">Yaklaşan Ödemeler</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-borderless">
                            <thead>
                                <tr>
                                    <th>Kart</th>
                                    <th>Borç Tutarı</th>
                                    <th>Son Ödeme Tarihi</th>
                                    <th>Minimum Ödeme</th>
                                    <th>İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['upcoming_payments'] as $payment): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm me-3" style="background-color: <?php echo $payment['color']; ?>">
                                                <i class="fas fa-credit-card text-white"></i>
                                            </div>
                                            <?php echo htmlspecialchars($payment['card_name']); ?>
                                        </div>
                                    </td>
                                    <td class="text-danger font-weight-bold">
                                        <?php echo number_format($payment['current_balance'], 2); ?> ₺
                                    </td>
                                    <td>
                                        <?php echo date('d.m.Y', strtotime($payment['next_due_date'])); ?>
                                    </td>
                                    <td>
                                        <?php echo number_format($payment['current_balance'] * $payment['minimum_payment_rate'] / 100, 2); ?> ₺
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button class="btn btn-success btn-sm" onclick="makePayment(<?php echo $payment['card_id']; ?>)" title="Ödeme Yap">
                                                <i class="fas fa-money-bill"></i> Öde
                                            </button>
                                            <button class="btn btn-warning btn-sm" onclick="editUpcomingPayment(<?php echo $payment['card_id']; ?>)" title="Ödeme Ayarlarını Düzenle">
                                                <i class="fas fa-edit"></i> Düzenle
                                            </button>
                                            <button class="btn btn-danger btn-sm" onclick="deleteUpcomingPayment(<?php echo $payment['card_id']; ?>)" title="Yaklaşan Ödemeyi Sil">
                                                <i class="fas fa-trash"></i> Sil
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
    <?php endif; ?>

    <!-- Installment Plans -->
    <?php if (!empty($data['installment_plans'])): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info">Aktif Taksit Planları</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-borderless">
                            <thead>
                                <tr>
                                    <th>Kart</th>
                                    <th>Açıklama</th>
                                    <th>Toplam Tutar</th>
                                    <th>Taksit</th>
                                    <th>Kalan Tutar</th>
                                    <th>Bitiş Tarihi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['installment_plans'] as $plan): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm me-3" style="background-color: <?php echo $plan['card_color']; ?>">
                                                <i class="fas fa-credit-card text-white"></i>
                                            </div>
                                            <?php echo htmlspecialchars($plan['card_name']); ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($plan['description'] ?? $plan['transaction_description']); ?></td>
                                    <td><?php echo number_format($plan['total_amount'], 2); ?> ₺</td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo $plan['paid_installments']; ?> / <?php echo $plan['installment_count']; ?>
                                        </span>
                                    </td>
                                    <td class="text-warning font-weight-bold">
                                        <?php echo number_format($plan['remaining_amount'], 2); ?> ₺
                                    </td>
                                    <td><?php echo date('d.m.Y', strtotime($plan['end_date'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Credit Card Transactions DataTable -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-list"></i> Kredi Kartı İşlemleri
                    </h6>
                    <div class="d-flex gap-2">
                        <select id="cardFilter" class="form-select form-select-sm" style="width: auto;">
                            <option value="">Tüm Kartlar</option>
                            <?php foreach ($data['credit_cards'] as $card): ?>
                                <option value="<?php echo $card['id']; ?>">
                                    <?php echo htmlspecialchars($card['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select id="typeFilter" class="form-select form-select-sm" style="width: auto;">
                            <option value="">Tüm İşlemler</option>
                            <option value="purchase">Harcama</option>
                            <option value="payment">Ödeme</option>
                            <option value="fee">Ücret</option>
                            <option value="interest">Faiz</option>
                            <option value="refund">İade</option>
                            <option value="installment">Taksit</option>
                        </select>
                        <button class="btn btn-sm btn-outline-primary" onclick="refreshTransactions()">
                            <i class="fas fa-sync-alt"></i> Yenile
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="transactionsTable" class="table table-bordered table-striped" style="width:100%">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Tarih</th>
                                    <th>Kart</th>
                                    <th>İşlem Türü</th>
                                    <th>Açıklama</th>
                                    <th>Mağaza</th>
                                    <th>Kategori</th>
                                    <th>Tutar</th>
                                    <th>Taksit</th>
                                    <th>Tag'ler</th>
                                    <th>Durum</th>
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

<!-- Add Credit Card Modal -->
<div class="modal fade" id="addCreditCardModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Kredi Kartı Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addCreditCardForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kart Adı *</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Banka Adı</label>
                            <input type="text" class="form-control" name="bank_name">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kart Numarası (Son 4 Hane)</label>
                            <input type="text" class="form-control" name="card_number_last4" maxlength="4" pattern="[0-9]{4}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kart Türü</label>
                            <select class="form-select" name="card_type">
                                <option value="visa">Visa</option>
                                <option value="mastercard">Mastercard</option>
                                <option value="amex">American Express</option>
                                <option value="troy">Troy</option>
                                <option value="other">Diğer</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kredi Limiti *</label>
                            <input type="number" class="form-control" name="credit_limit" step="0.01" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Para Birimi</label>
                            <select class="form-select" name="currency">
                                <option value="TRY">TRY</option>
                                <option value="USD">USD</option>
                                <option value="EUR">EUR</option>
                                <option value="GBP">GBP</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ekstre Günü</label>
                            <input type="number" class="form-control" name="statement_day" min="1" max="31" value="1">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Son Ödeme Günü</label>
                            <input type="number" class="form-control" name="due_day" min="1" max="31" value="15">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Minimum Ödeme Oranı (%)</label>
                            <input type="number" class="form-control" name="minimum_payment_rate" step="0.01" value="5.00">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Faiz Oranı (%)</label>
                            <input type="number" class="form-control" name="interest_rate" step="0.01" value="2.50">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Yıllık Aidat</label>
                            <input type="number" class="form-control" name="annual_fee" step="0.01" value="0.00">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Renk</label>
                            <input type="color" class="form-control form-control-color" name="color" value="#007bff">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" onclick="saveCreditCard()">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Credit Card Modal -->
<div class="modal fade" id="editCreditCardModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Kredi Kartı Düzenle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editCreditCardForm">
                    <input type="hidden" name="card_id" id="editCardId">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kart Adı *</label>
                            <input type="text" class="form-control" name="name" id="editCardName" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Banka Adı</label>
                            <input type="text" class="form-control" name="bank_name" id="editBankName">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kart Numarası (Son 4 Hane)</label>
                            <input type="text" class="form-control" name="card_number_last4" id="editCardLast4" maxlength="4" pattern="[0-9]{4}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kart Türü</label>
                            <select class="form-select" name="card_type" id="editCardType">
                                <option value="visa">Visa</option>
                                <option value="mastercard">Mastercard</option>
                                <option value="amex">American Express</option>
                                <option value="troy">Troy</option>
                                <option value="other">Diğer</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kredi Limiti *</label>
                            <input type="number" class="form-control" name="credit_limit" id="editCreditLimit" step="0.01" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Para Birimi</label>
                            <select class="form-select" name="currency" id="editCurrency">
                                <option value="TRY">TRY</option>
                                <option value="USD">USD</option>
                                <option value="EUR">EUR</option>
                                <option value="GBP">GBP</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ekstre Günü</label>
                            <input type="number" class="form-control" name="statement_day" id="editStatementDay" min="1" max="31">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Son Ödeme Günü</label>
                            <input type="number" class="form-control" name="due_day" id="editDueDay" min="1" max="31">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Minimum Ödeme Oranı (%)</label>
                            <input type="number" class="form-control" name="minimum_payment_rate" id="editMinPaymentRate" step="0.01">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Faiz Oranı (%)</label>
                            <input type="number" class="form-control" name="interest_rate" id="editInterestRate" step="0.01">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Yıllık Aidat</label>
                            <input type="number" class="form-control" name="annual_fee" id="editAnnualFee" step="0.01">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Renk</label>
                            <input type="color" class="form-control form-control-color" name="color" id="editColor">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" onclick="updateCreditCard()">Güncelle</button>
            </div>
        </div>
    </div>
</div>

<!-- Make Payment Modal -->
<div class="modal fade" id="makePaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Kredi Kartı Ödemesi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="makePaymentForm">
                    <input type="hidden" name="credit_card_id" id="paymentCardId">
                    
                    <div class="mb-3">
                        <label class="form-label">Ödeme Yapılacak Cüzdan *</label>
                        <select class="form-select" name="wallet_id" required>
                            <option value="">Cüzdan Seçin</option>
                            <?php foreach ($wallets as $wallet): ?>
                                <option value="<?php echo $wallet['id']; ?>">
                                    <?php echo htmlspecialchars($wallet['name']); ?> 
                                    (<?php echo number_format($wallet['real_balance'], 2); ?> <?php echo $wallet['currency']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ödeme Tutarı *</label>
                        <input type="number" class="form-control" name="amount" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ödeme Tarihi</label>
                        <input type="datetime-local" class="form-control" name="payment_date" value="<?php echo date('Y-m-d\TH:i'); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <input type="text" class="form-control" name="description" placeholder="Ödeme açıklaması">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-success" onclick="savePayment()">Ödeme Yap</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Upcoming Payment Modal -->
<div class="modal fade" id="editUpcomingPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yaklaşan Ödeme Düzenle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editUpcomingPaymentForm">
                    <input type="hidden" name="card_id" id="editPaymentCardId">
                    
                    <div class="mb-3">
                        <label class="form-label">Son Ödeme Günü</label>
                        <input type="number" class="form-control" name="due_day" min="1" max="31" id="editDueDay">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Minimum Ödeme Oranı (%)</label>
                        <input type="number" class="form-control" name="minimum_payment_rate" step="0.01" id="editMinPaymentRate">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" onclick="updateUpcomingPayment()">Güncelle</button>
            </div>
        </div>
    </div>
</div>

<!-- Transaction Details Modal -->
<div class="modal fade" id="transactionDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">İşlem Detayları</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="transactionDetailsContent">
                <!-- Transaction details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                <button type="button" class="btn btn-warning" id="editTransactionBtn" onclick="toggleEditMode()">
                    <i class="fas fa-edit"></i> Düzenle
                </button>
                <button type="button" class="btn btn-success d-none" id="saveTransactionBtn" onclick="saveTransactionDetails()">
                    <i class="fas fa-save"></i> Güncelle
                </button>
                <button type="button" class="btn btn-secondary d-none" id="cancelEditBtn" onclick="cancelEdit()">
                    <i class="fas fa-times"></i> İptal
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Transaction Modal -->
<div class="modal fade" id="editTransactionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">İşlem Düzenle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editTransactionForm">
                    <input type="hidden" name="id" id="editTransactionId">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">İşlem ID</label>
                            <input type="text" class="form-control" id="editTransactionIdDisplay" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">İşlem Tarihi</label>
                            <input type="datetime-local" class="form-control" name="transaction_date" id="editTransactionDate">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">İşlem Türü</label>
                            <select class="form-select" name="type" id="editTransactionType">
                                <option value="purchase">Harcama</option>
                                <option value="payment">Ödeme</option>
                                <option value="fee">Ücret</option>
                                <option value="interest">Faiz</option>
                                <option value="refund">İade</option>
                                <option value="installment">Taksit</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tutar</label>
                            <input type="number" class="form-control" name="amount" id="editTransactionAmount" step="0.01">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <input type="text" class="form-control" name="description" id="editTransactionDescription">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Mağaza/İşyeri</label>
                        <input type="text" class="form-control" name="merchant_name" id="editTransactionMerchant">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Kategori</label>
                            <select class="form-select" name="category_id" id="editTransactionCategory">
                                <option value="">Kategori Seçin (İsteğe Bağlı)</option>
                                <!-- Kategoriler AJAX ile yüklenecek -->
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Para Birimi</label>
                            <select class="form-select" name="currency" id="editTransactionCurrency">
                                <option value="TRY">TRY</option>
                                <option value="USD">USD</option>
                                <option value="EUR">EUR</option>
                                <option value="GBP">GBP</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Taksit Sayısı</label>
                            <div class="row">
                                <div class="col-md-8">
                                    <select class="form-select" name="installment_count" id="editInstallmentSelect">
                                        <option value="1">Peşin</option>
                                        <option value="2">2 Taksit</option>
                                        <option value="3">3 Taksit</option>
                                        <option value="4">4 Taksit</option>
                                        <option value="6">6 Taksit</option>
                                        <option value="8">8 Taksit</option>
                                        <option value="9">9 Taksit</option>
                                        <option value="12">12 Taksit</option>
                                        <option value="18">18 Taksit</option>
                                        <option value="24">24 Taksit</option>
                                        <option value="custom">Manuel Giriş</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <input type="number" class="form-control d-none" name="custom_installment" id="editCustomInstallment" min="1" max="60" placeholder="Taksit sayısı">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tag'ler</label>
                        <select class="form-select" name="tags[]" id="editTransactionTags" multiple>
                            <!-- Tag'ler AJAX ile yüklenecek -->
                        </select>
                        <div class="form-text">Birden fazla tag seçebilirsiniz (İsteğe Bağlı)</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Ödeme Cüzdanı</label>
                        <select class="form-select" name="payment_wallet_id" id="editPaymentWalletSelect">
                            <option value="">Ödeme zamanı geldiğinde seçilecek</option>
                            <?php foreach ($wallets as $wallet): ?>
                                <option value="<?php echo $wallet['id']; ?>">
                                    <?php echo htmlspecialchars($wallet['name']); ?> 
                                    (<?php echo number_format($wallet['real_balance'], 2); ?> <?php echo $wallet['currency']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Taksitli ödemelerde her taksit bu cüzdandan çekilecek (İsteğe Bağlı)</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Durum</label>
                        <select class="form-select" name="is_paid" id="editTransactionStatus">
                            <option value="0">Beklemede</option>
                            <option value="1">Ödendi</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" onclick="updateTransaction()">Güncelle</button>
            </div>
        </div>
    </div>
</div>

<!-- Custom CSS -->
<style>
.credit-card-item {
    transition: transform 0.2s;
}

.credit-card-item:hover {
    transform: translateY(-5px);
}

.avatar {
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.avatar-sm {
    width: 1.5rem;
    height: 1.5rem;
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

.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}

.border-left-danger {
    border-left: 0.25rem solid #e74a3b !important;
}

/* DataTable custom styles */
#transactionsTable {
    font-size: 0.875rem;
}

#transactionsTable th {
    background-color: #f8f9fc;
    border-color: #e3e6f0;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    color: #5a5c69;
}

.transaction-type-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

.transaction-amount {
    font-weight: 600;
}

.transaction-amount.positive {
    color: #1cc88a;
}

.transaction-amount.negative {
    color: #e74a3b;
}

.gap-2 {
    gap: 0.5rem;
}

/* Dropdown z-index fix */
.dropdown-menu {
    z-index: 1051 !important;
}

.dropdown-toggle {
    z-index: 1050 !important;
}

.card .dropdown {
    position: relative;
    z-index: 1;
}

/* Transaction Details Edit Mode Styles */
.editable-field {
    cursor: pointer;
    padding: 2px 4px;
    border-radius: 3px;
    transition: background-color 0.2s;
}

.editable-field:hover {
    background-color: #f8f9fa;
}

.editable-field.editing {
    background-color: #fff3cd;
    border: 1px solid #ffeaa7;
}

.edit-input {
    width: 100%;
    border: 1px solid #ced4da;
    border-radius: 4px;
    padding: 4px 8px;
    font-size: 0.875rem;
}

.non-editable {
    color: #6c757d;
    font-style: italic;
}

.edit-mode-hint {
    background-color: #d1ecf1;
    border: 1px solid #bee5eb;
    border-radius: 4px;
    padding: 8px 12px;
    margin-bottom: 15px;
    font-size: 0.875rem;
    color: #0c5460;
}

/* Installment details styles */
.installment-details {
    background-color: #f8f9fa;
    border-left: 4px solid #007bff;
    margin: 10px 0;
}

.installment-details h6 {
    color: #007bff;
    margin-bottom: 15px;
}

/* DataTable expand/collapse styles */
td.dt-control {
    background: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTYiIGhlaWdodD0iMTYiIHZpZXdCb3g9IjAgMCAxNiAxNiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTggMTJMMTMgN0gzTDggMTJaIiBmaWxsPSIjNjc3Mjg5Ii8+Cjwvc3ZnPgo=') no-repeat center center;
    cursor: pointer;
}

tr.shown td.dt-control {
    background: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTYiIGhlaWdodD0iMTYiIHZpZXdCb3g9IjAgMCAxNiAxNiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTggNEwzIDlIMTNMOCA0WiIgZmlsbD0iIzY3NzI4OSIvPgo8L3N2Zz4K') no-repeat center center;
}

/* Tag styles */
.badge.bg-light {
    border: 1px solid #dee2e6;
}

/* Custom installment input */
#customInstallment {
    transition: all 0.3s ease;
}

/* Modal improvements */
.modal-lg {
    max-width: 900px;
}

/* Form improvements */
.form-text {
    font-size: 0.875rem;
    color: #6c757d;
}

/* Responsive improvements */
@media (max-width: 768px) {
    .btn-group-sm .btn {
        padding: 0.25rem 0.4rem;
        font-size: 0.75rem;
    }
    
    .installment-details {
        margin: 5px 0;
        padding: 10px !important;
    }
}
</style>

<!-- Include DataTables CSS and JS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

<script>
// jQuery yüklendiğinden emin ol
$(document).ready(function() {
    console.log('Document ready, initializing...');
    
    // Initialize DataTable
    initializeTransactionsTable();
    
    // Load tags for transaction modal
    loadTagsForTransaction();
    
    // Handle installment selection
    $('#installmentSelect').on('change', function() {
        const value = $(this).val();
        if (value === 'custom') {
            $('#customInstallment').removeClass('d-none').focus();
        } else {
            $('#customInstallment').addClass('d-none');
        }
    });
    
    // Handle edit modal installment selection
    $('#editInstallmentSelect').on('change', function() {
        const value = $(this).val();
        if (value === 'custom') {
            $('#editCustomInstallment').removeClass('d-none').focus();
        } else {
            $('#editCustomInstallment').addClass('d-none');
        }
    });
});

function loadTagsForTransaction() {
    $.ajax({
        url: '/gelirgider/app/controllers/TagController.php?action=getAll',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                let options = '';
                response.data.forEach(function(tag) {
                    options += `<option value="${tag.id}">${tag.name}</option>`;
                });
                $('#transactionTags').html(options);
            }
        },
        error: function() {
            console.error('Tag\'ler yüklenemedi');
        }
    });
}

function loadDataTableScripts() {
    const scripts = [
        // Önce ana DataTable script'leri
        'https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js',
        'https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js',
        // Sonra eklentiler
        'https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js',
        'https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js',
        'https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js',
        'https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js',
        // Export için gerekli kütüphaneler
        'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js',
        'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js',
        'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js',
        'https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js',
        'https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js'
    ];
    
    let loadedCount = 0;
    
    // Script'leri sırayla yükle
    function loadNextScript(index) {
        if (index >= scripts.length) {
            console.log('All DataTable scripts loaded, initializing...');
            setTimeout(initializeTransactionsTable, 100);
            return;
        }
        
        const script = document.createElement('script');
        script.src = scripts[index];
        script.onload = function() {
            loadedCount++;
            console.log(`DataTable script ${loadedCount}/${scripts.length} loaded: ${scripts[index]}`);
            loadNextScript(index + 1);
        };
        script.onerror = function() {
            console.error(`Failed to load script: ${scripts[index]}`);
            loadNextScript(index + 1); // Hata olsa bile devam et
        };
        document.head.appendChild(script);
    }
    
    loadNextScript(0);
}

let transactionsTable;

function initializeTransactionsTable() {
    console.log('Initializing DataTable...');
    
    // Check if table exists and has correct structure
    const table = $('#transactionsTable');
    if (table.length === 0) {
        console.error('Table #transactionsTable not found');
        return;
    }
    
    const headerCells = table.find('thead tr th').length;
    console.log('Table header cells count:', headerCells);
    
    if ($.fn.DataTable.isDataTable('#transactionsTable')) {
        $('#transactionsTable').DataTable().destroy();
    }
    
    try {
        transactionsTable = $('#transactionsTable').DataTable({
            processing: true,
            serverSide: false,
            ajax: {
                url: '/gelirgider/app/controllers/CreditCardController.php?action=getAllTransactions',
                type: 'GET',
                data: function(d) {
                    d.card_id = $('#cardFilter').val();
                    d.type = $('#typeFilter').val();
                    console.log('DataTable request data:', d);
                },
                dataSrc: function(json) {
                    console.log('DataTable response:', json); // Debug için
                    if (json && json.success) {
                        console.log('Data count:', json.data ? json.data.length : 0);
                        if (json.data && json.data.length > 0) {
                            console.log('First row sample:', json.data[0]);
                        }
                        return json.data || [];
                    } else {
                        console.error('DataTable error:', json);
                        showNotification('error', json ? json.message : 'İşlemler yüklenirken hata oluştu');
                        return [];
                    }
                },
                error: function(xhr, error, thrown) {
                    console.error('DataTable AJAX error:', {
                        status: xhr.status,
                        statusText: xhr.statusText,
                        responseText: xhr.responseText,
                        error: error,
                        thrown: thrown
                    });
                    showNotification('error', 'İşlemler yüklenirken hata oluştu: ' + error);
                }
            },
            columns: [
                {
                    // Expand/Collapse button for installments
                    className: 'dt-control',
                    orderable: false,
                    data: null,
                    defaultContent: '',
                    render: function(data, type, row) {
                        if (row.installment_count && row.installment_count > 1) {
                        
                            return '<i class="fas fa-plus-circle text-primary" style="cursor: pointer;" title="Taksit detaylarını göster"></i>';
                        }
                        return '';
                    }
                },
                {
                    data: 'transaction_date',
                    render: function(data) {
                        if (!data) return '-';
                        return new Date(data).toLocaleDateString('tr-TR', {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                    }
                },
                {
                    data: 'card_name',
                    render: function(data, type, row) {
                        if (!data) return '-';
                        return `<div class="d-flex align-items-center">
                            <div class="avatar avatar-sm me-2" style="background-color: ${row.card_color || '#007bff'}">
                                <i class="fas fa-credit-card text-white"></i>
                            </div>
                            <span>${data}</span>
                        </div>`;
                    }
                },
                {
                    data: 'type',
                    render: function(data) {
                        const types = {
                            'purchase': { text: 'Harcama', class: 'bg-danger' },
                            'payment': { text: 'Ödeme', class: 'bg-success' },
                            'fee': { text: 'Ücret', class: 'bg-warning' },
                            'interest': { text: 'Faiz', class: 'bg-danger' },
                            'refund': { text: 'İade', class: 'bg-info' },
                            'installment': { text: 'Taksit', class: 'bg-primary' }
                        };
                        const type = types[data] || { text: data || '-', class: 'bg-secondary' };
                        return `<span class="badge ${type.class} transaction-type-badge">${type.text}</span>`;
                    }
                },
                {
                    data: 'description',
                    render: function(data) {
                        return data || '-';
                    }
                },
                {
                    data: 'merchant_name',
                    render: function(data) {
                        return data || '-';
                    }
                },
                {
                    data: 'category_name',
                    render: function(data) {
                        return data || '-';
                    }
                },
                {
                    data: 'amount',
                    render: function(data, type, row) {
                        if (!data) return '-';
                        const isPositive = row.type === 'payment' || row.type === 'refund';
                        const amountClass = isPositive ? 'positive' : 'negative';
                        const sign = isPositive ? '+' : '-';
                        
                        // Taksitli işlemler için özel gösterim
                        if (row.installment_count > 1) {
                            // Ana işlemde toplam tutarı göster
                            const totalAmount = parseFloat(data);
                            const installmentAmount = totalAmount / row.installment_count;
                            return `<div>
                                <div class="transaction-amount ${amountClass}" style="font-weight: bold;">${sign}${totalAmount.toLocaleString('tr-TR', {minimumFractionDigits: 2})} ₺</div>
                                <small class="text-muted">Aylık: ${installmentAmount.toLocaleString('tr-TR', {minimumFractionDigits: 2})} ₺</small>
                            </div>`;
                        }
                        
                        return `<span class="transaction-amount ${amountClass}">${sign}${parseFloat(data).toLocaleString('tr-TR', {minimumFractionDigits: 2})} ₺</span>`;
                    }
                },
                {
                    data: 'installment_count',
                    render: function(data, type, row) {
                        if (data > 1) {
                            return `<span class="badge bg-info">${data} Taksit</span>`;
                        }
                        return '<span class="badge bg-secondary">Peşin</span>';
                    }
                },
                {
                    data: 'tags',
                    render: function(data) {
                        if (data && data.length > 0) {
                            return data.map(tag => `<span class="badge bg-light text-dark me-1">${tag.name}</span>`).join('');
                        }
                        return '-';
                    }
                },
                {
                    data: 'is_paid',
                    render: function(data) {
                        return data == 1 
                            ? '<span class="badge bg-success">Ödendi</span>' 
                            : '<span class="badge bg-warning">Bekliyor</span>';
                    }
                },
                {
                    data: 'id',
                    orderable: false,
                    render: function(data, type, row) {
                        if (!data) return '-';
                        return `
                            <div class="btn-group btn-group-sm">
                                <a href="/gelirgider/app/views/credit-cards/edit.php?id=${data}" class="btn btn-outline-warning btn-sm" title="Düzenle">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button class="btn btn-outline-danger btn-sm" onclick="deleteTransaction(${data})" title="Sil">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        `;
                    }
                }
            ],
            order: [[1, 'desc']], // Sort by date descending
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
                },
                "aria": {
                    "sortAscending": ": artan sütun sıralamasını aktifleştir",
                    "sortDescending": ": azalan sütun sıralamasını aktifleştir"
                }
            },
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                 '<"row"<"col-sm-12"tr>>' +
                 '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            drawCallback: function() {
                console.log('DataTable draw completed');
            }
        });
    } catch (error) {
        console.error('DataTable initialization error:', error);
        showNotification('error', 'İşlemler yüklenirken hata oluştu: ' + error.message);
        
        // Try a simple fallback initialization
        try {
            console.log('Attempting fallback DataTable initialization...');
            transactionsTable = $('#transactionsTable').DataTable({
                processing: true,
                serverSide: false,
                data: [],
                columns: [
                    { title: "", defaultContent: "" },
                    { title: "Tarih", defaultContent: "-" },
                    { title: "Kart", defaultContent: "-" },
                    { title: "İşlem Türü", defaultContent: "-" },
                    { title: "Açıklama", defaultContent: "-" },
                    { title: "Mağaza", defaultContent: "-" },
                    { title: "Kategori", defaultContent: "-" },
                    { title: "Tutar", defaultContent: "-" },
                    { title: "Taksit", defaultContent: "-" },
                    { title: "Tag'ler", defaultContent: "-" },
                    { title: "Durum", defaultContent: "-" },
                    { title: "İşlemler", defaultContent: "-" }
                ],
                language: {
                    "emptyTable": "Henüz işlem bulunmuyor",
                    "processing": "Yükleniyor..."
                }
            });
            console.log('Fallback DataTable initialized');
        } catch (fallbackError) {
            console.error('Fallback DataTable initialization also failed:', fallbackError);
        }
    }
    
    // Filter event listeners
    $('#cardFilter, #typeFilter').on('change', function() {
        if (transactionsTable) {
            transactionsTable.ajax.reload();
        }
    });
    
    // Add click event for expanding installment details
    $('#transactionsTable tbody').on('click', 'td.dt-control', function () {
        var tr = $(this).closest('tr');
        var row = transactionsTable.row(tr);
        
        if (row.child.isShown()) {
            // This row is already open - close it
            row.child.hide();
            tr.removeClass('shown');
            $(this).find('i').removeClass('fa-minus-circle').addClass('fa-plus-circle');
        } else {
            // Open this row
            const rowData = row.data();
            if (rowData.installment_count > 1) {
                // Load installment details
                loadInstallmentDetails(rowData.id, row, tr, $(this).find('i'));
            }
        }
    });
    
    console.log('DataTable initialized successfully');
}

function loadInstallmentDetails(parentTransactionId, row, tr, icon) {
    $.ajax({
        url: '/gelirgider/app/controllers/CreditCardController.php?action=getInstallmentDetails',
        type: 'GET',
        data: { parent_id: parentTransactionId },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                const installments = response.data;
                let detailsHtml = `
                    <div class="installment-details p-3">
                        <h6><i class="fas fa-list"></i> Taksit Detayları</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>Taksit No</th>
                                        <th>Tarih</th>
                                        <th>Tutar</th>
                                        <th>Durum</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                `;
                
                installments.forEach(function(installment, index) {
                    detailsHtml += `
                        <tr>
                            <td>${index + 1}</td>
                            <td>${new Date(installment.transaction_date).toLocaleDateString('tr-TR')}</td>
                            <td>${parseFloat(installment.amount).toLocaleString('tr-TR', {minimumFractionDigits: 2})} ₺</td>
                            <td>
                                ${installment.is_paid == 1 
                                    ? '<span class="badge bg-success">Ödendi</span>' 
                                    : '<span class="badge bg-warning">Bekliyor</span>'}
                            </td>
                            <td>
                                <a href="/gelirgider/app/views/credit-cards/edit.php?id=${installment.id}" class="btn btn-outline-warning btn-sm" title="Düzenle">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                    `;
                });
                
                detailsHtml += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;
                
                row.child(detailsHtml).show();
                tr.addClass('shown');
                icon.removeClass('fa-plus-circle').addClass('fa-minus-circle');
            } else {
                showNotification('error', 'Taksit detayları yüklenemedi');
            }
        },
        error: function() {
            showNotification('error', 'Taksit detayları yüklenirken hata oluştu');
        }
    });
}

function refreshTransactions() {
    if (transactionsTable) {
        transactionsTable.ajax.reload();
        showNotification('success', 'İşlemler yenilendi');
    }
}

function viewTransactionDetails(transactionId) {
    $.ajax({
        url: '/gelirgider/app/controllers/CreditCardController.php?action=getTransactionDetails',
        type: 'GET',
        data: { id: transactionId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const transaction = response.data;
                
                // Store transaction data globally for editing
                window.currentTransaction = transaction;
                
                const detailsHtml = `
                    <div class="edit-mode-hint d-none" id="editModeHint">
                        <i class="fas fa-info-circle"></i> Düzenleme modunda: Değiştirmek istediğiniz alanlara tıklayın. ID ve oluşturma tarihi değiştirilemez.
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <h6>İşlem Bilgileri</h6>
                            <table class="table table-sm">
                                <tr><td><strong>ID:</strong></td><td class="non-editable">${transaction.id}</td></tr>
                                <tr><td><strong>Tarih:</strong></td><td class="editable-field" data-field="transaction_date" data-type="datetime-local">${new Date(transaction.transaction_date).toLocaleString('tr-TR')}</td></tr>
                                <tr><td><strong>Tür:</strong></td><td class="editable-field" data-field="type" data-type="select">${getTransactionTypeText(transaction.type)}</td></tr>
                                <tr><td><strong>Tutar:</strong></td><td class="editable-field" data-field="amount" data-type="number">${parseFloat(transaction.amount).toLocaleString('tr-TR', {minimumFractionDigits: 2})} ₺</td></tr>
                                <tr><td><strong>Açıklama:</strong></td><td class="editable-field" data-field="description" data-type="text">${transaction.description || '-'}</td></tr>
                                <tr><td><strong>Mağaza:</strong></td><td class="editable-field" data-field="merchant_name" data-type="text">${transaction.merchant_name || '-'}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Kart ve Kategori</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Kart:</strong></td><td class="non-editable">${transaction.card_name}</td></tr>
                                <tr><td><strong>Kategori:</strong></td><td class="editable-field" data-field="category_id" data-type="select-category">${transaction.category_name || '-'}</td></tr>
                                <tr><td><strong>Para Birimi:</strong></td><td class="editable-field" data-field="currency" data-type="select-currency">${transaction.currency}</td></tr>
                                <tr><td><strong>Taksit:</strong></td><td class="non-editable">${transaction.installment_count > 1 ? transaction.installment_number + '/' + transaction.installment_count : 'Peşin'}</td></tr>
                                <tr><td><strong>Durum:</strong></td><td class="editable-field" data-field="is_paid" data-type="select-status">${transaction.is_paid == 1 ? 'Ödendi' : 'Bekliyor'}</td></tr>
                                <tr><td><strong>Oluşturma:</strong></td><td class="non-editable">${new Date(transaction.created_at).toLocaleString('tr-TR')}</td></tr>
                            </table>
                        </div>
                    </div>
                `;
                $('#transactionDetailsContent').html(detailsHtml);
                
                // Reset edit mode
                resetEditMode();
                
                $('#transactionDetailsModal').modal('show');
            } else {
                showNotification('error', response.message);
            }
        },
        error: function() {
            showNotification('error', 'İşlem detayları yüklenirken hata oluştu');
        }
    });
}

function deleteTransaction(transactionId) {
    if (confirm('Bu işlemi silmek istediğinizden emin misiniz?')) {
        $.ajax({
            url: '/gelirgider/app/controllers/CreditCardController.php?action=deleteTransaction',
            type: 'POST',
            data: { id: transactionId, ajax: '1' },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification('success', response.message);
                    transactionsTable.ajax.reload();
                    // Reload page to update card balances
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification('error', response.message);
                }
            },
            error: function() {
                showNotification('error', 'İşlem silinirken hata oluştu');
            }
        });
    }
}

function getTransactionTypeText(type) {
    const types = {
        'purchase': 'Harcama',
        'payment': 'Ödeme',
        'fee': 'Ücret',
        'interest': 'Faiz',
        'refund': 'İade',
        'installment': 'Taksit'
    };
    return types[type] || type;
}

function saveCreditCard() {
    const formData = new FormData(document.getElementById('addCreditCardForm'));
    formData.append('ajax', '1');
    
    $.ajax({
        url: '/gelirgider/app/controllers/CreditCardController.php?action=create',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#addCreditCardModal').modal('hide');
                showNotification('success', response.message);
                location.reload();
            } else {
                showNotification('error', response.message);
            }
        },
        error: function() {
            showNotification('error', 'Kredi kartı eklenirken bir hata oluştu.');
        }
    });
}

function addTransaction(cardId) {
    $('#transactionCardId').val(cardId);
    
    // Load categories for add transaction modal
    loadCategoriesForAdd();
    
    $('#addTransactionModal').modal('show');
}

function saveTransaction() {
    const formData = new FormData(document.getElementById('addTransactionForm'));
    
    // Handle custom installment count
    const installmentSelect = $('#installmentSelect').val();
    if (installmentSelect === 'custom') {
        const customInstallment = $('#customInstallment').val();
        if (customInstallment && customInstallment > 0) {
            formData.set('installment_count', customInstallment);
        } else {
            showNotification('error', 'Lütfen geçerli bir taksit sayısı girin');
            return;
        }
    }
    
    formData.append('ajax', '1');
    
    $.ajax({
        url: '/gelirgider/app/controllers/CreditCardController.php?action=addTransaction',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#addTransactionModal').modal('hide');
                showNotification('success', response.message);
                
                // Reset form
                document.getElementById('addTransactionForm').reset();
                $('#customInstallment').addClass('d-none');
                $('#transactionTags').val([]).trigger('change');
                
                // Reload transactions table if exists
                if (typeof transactionsTable !== 'undefined') {
                    transactionsTable.ajax.reload();
                }
                
                // Reload page to update balances
                setTimeout(() => location.reload(), 1500);
            } else {
                showNotification('error', response.message || 'İşlem eklenirken hata oluştu');
            }
        },
        error: function() {
            showNotification('error', 'İşlem eklenirken hata oluştu');
        }
    });
}

function makePayment(cardId) {
    $('#paymentCardId').val(cardId);
    $('#makePaymentModal').modal('show');
}

function savePayment() {
    const formData = new FormData(document.getElementById('makePaymentForm'));
    formData.append('ajax', '1');
    
    // Cüzdan seçimi kontrolü
    if (!formData.get('wallet_id')) {
        showNotification('error', 'Lütfen ödeme yapılacak cüzdanı seçin.');
        return;
    }
    
    $.ajax({
        url: '/gelirgider/app/controllers/CreditCardController.php?action=makePayment',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#makePaymentModal').modal('hide');
                showNotification('success', response.message);
                // Immediate page reload to update balances and notifications
                location.reload();
            } else {
                showNotification('error', response.message);
            }
        },
        error: function() {
            showNotification('error', 'Ödeme kaydedilirken bir hata oluştu.');
        }
    });
}

function editCreditCard(cardId) {
    // Load credit card data and show edit modal
    $.ajax({
        url: '/gelirgider/app/controllers/CreditCardController.php?action=get',
        type: 'GET',
        data: { id: cardId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const card = response.data;
                
                // Populate edit form
                $('#editCardId').val(card.id);
                $('#editCardName').val(card.name);
                $('#editBankName').val(card.bank_name || '');
                $('#editCardLast4').val(card.card_number_last4 || '');
                $('#editCardType').val(card.card_type);
                $('#editCreditLimit').val(card.credit_limit);
                $('#editCurrency').val(card.currency);
                $('#editStatementDay').val(card.statement_day);
                $('#editDueDay').val(card.due_day);
                $('#editMinPaymentRate').val(card.minimum_payment_rate);
                $('#editInterestRate').val(card.interest_rate);
                $('#editAnnualFee').val(card.annual_fee);
                $('#editColor').val(card.color);
                
                // Show modal
                $('#editCreditCardModal').modal('show');
            } else {
                showNotification('error', response.message);
            }
        },
        error: function() {
            showNotification('error', 'Kredi kartı bilgileri yüklenirken hata oluştu.');
        }
    });
}

function updateCreditCard() {
    const formData = new FormData(document.getElementById('editCreditCardForm'));
    formData.append('ajax', '1');
    
    $.ajax({
        url: '/gelirgider/app/controllers/CreditCardController.php?action=edit',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#editCreditCardModal').modal('hide');
                showNotification('success', response.message);
                location.reload();
            } else {
                showNotification('error', response.message);
            }
        },
        error: function() {
            showNotification('error', 'Kredi kartı güncellenirken bir hata oluştu.');
        }
    });
}

function deleteCreditCard(cardId) {
    if (confirm('Bu kredi kartını silmek istediğinizden emin misiniz?')) {
        $.ajax({
            url: '/gelirgider/app/controllers/CreditCardController.php?action=delete',
            type: 'POST',
            data: { id: cardId, ajax: '1' },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification('success', response.message);
                    location.reload();
                } else {
                    showNotification('error', response.message);
                }
            },
            error: function() {
                showNotification('error', 'Kredi kartı silinirken bir hata oluştu.');
            }
        });
    }
}

function viewTransactions(cardId) {
    // Filter transactions by card and scroll to table
    $('#cardFilter').val(cardId);
    transactionsTable.ajax.reload();
    
    // Scroll to transactions table
    $('html, body').animate({
        scrollTop: $('#transactionsTable').offset().top - 100
    }, 1000);
    
    showNotification('info', 'İşlemler filtrelendi ve tabloya yönlendirildi.');
}

function editUpcomingPayment(cardId) {
    // Load credit card data for editing payment settings
    $.ajax({
        url: '/gelirgider/app/controllers/CreditCardController.php?action=get',
        type: 'GET',
        data: { id: cardId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const card = response.data;
                $('#editPaymentCardId').val(card.id);
                $('#editDueDay').val(card.due_day);
                $('#editMinPaymentRate').val(card.minimum_payment_rate);
                $('#editUpcomingPaymentModal').modal('show');
            } else {
                showNotification('error', response.message);
            }
        },
        error: function() {
            showNotification('error', 'Kart bilgileri yüklenirken hata oluştu.');
        }
    });
}

function updateUpcomingPayment() {
    const formData = new FormData(document.getElementById('editUpcomingPaymentForm'));
    formData.append('ajax', '1');
    
    $.ajax({
        url: '/gelirgider/app/controllers/CreditCardController.php?action=edit',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#editUpcomingPaymentModal').modal('hide');
                showNotification('success', 'Ödeme ayarları güncellendi.');
                location.reload();
            } else {
                showNotification('error', response.message);
            }
        },
        error: function() {
            showNotification('error', 'Güncelleme sırasında hata oluştu.');
        }
    });
}

function deleteUpcomingPayment(cardId) {
    if (confirm('Bu kartın yaklaşan ödemesini silmek istediğinizden emin misiniz? Bu işlem kartı deaktive edecektir.')) {
        $.ajax({
            url: '/gelirgider/app/controllers/CreditCardController.php?action=delete',
            type: 'POST',
            data: { id: cardId, ajax: '1' },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification('success', 'Yaklaşan ödeme silindi.');
                    location.reload();
                } else {
                    showNotification('error', response.message);
                }
            },
            error: function() {
                showNotification('error', 'Silme işlemi sırasında hata oluştu.');
            }
        });
    }
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

// Transaction Details Edit Mode Functions
let isEditMode = false;
let originalValues = {};

function toggleEditMode() {
    isEditMode = !isEditMode;
    
    if (isEditMode) {
        enterEditMode();
    } else {
        exitEditMode();
    }
}

function enterEditMode() {
    // Show edit mode hint
    $('#editModeHint').removeClass('d-none');
    
    // Change button states
    $('#editTransactionBtn').addClass('d-none');
    $('#saveTransactionBtn').removeClass('d-none');
    $('#cancelEditBtn').removeClass('d-none');
    
    // Store original values
    originalValues = {};
    
    // Make editable fields clickable
    $('.editable-field').each(function() {
        const $field = $(this);
        const fieldName = $field.data('field');
        const fieldType = $field.data('type');
        
        // Store original value
        originalValues[fieldName] = getFieldValue(fieldName);
        
        // Add click handler
        $field.off('click').on('click', function() {
            if (!$field.hasClass('editing')) {
                editField($field, fieldName, fieldType);
            }
        });
        
        // Add hover effect
        $field.addClass('editable-field');
    });
    
    showNotification('info', 'Düzenleme modu aktif. Değiştirmek istediğiniz alanlara tıklayın.');
}

function exitEditMode() {
    // Hide edit mode hint
    $('#editModeHint').addClass('d-none');
    
    // Change button states
    $('#editTransactionBtn').removeClass('d-none');
    $('#saveTransactionBtn').addClass('d-none');
    $('#cancelEditBtn').addClass('d-none');
    
    // Remove edit handlers and classes
    $('.editable-field').off('click').removeClass('editing');
    
    isEditMode = false;
}

function resetEditMode() {
    isEditMode = false;
    originalValues = {};
    exitEditMode();
}

function editField($field, fieldName, fieldType) {
    const currentValue = getFieldValue(fieldName);
    let inputHtml = '';
    
    switch (fieldType) {
        case 'text':
            inputHtml = `<input type="text" class="edit-input" value="${currentValue || ''}" data-field="${fieldName}">`;
            break;
        case 'number':
            const numValue = currentValue ? parseFloat(currentValue.toString().replace(/[^\d.,]/g, '').replace(',', '.')) : 0;
            inputHtml = `<input type="number" step="0.01" class="edit-input" value="${numValue}" data-field="${fieldName}">`;
            break;
        case 'datetime-local':
            const dateValue = new Date(currentValue).toISOString().slice(0, 16);
            inputHtml = `<input type="datetime-local" class="edit-input" value="${dateValue}" data-field="${fieldName}">`;
            break;
        case 'select':
            const typeOptions = {
                'purchase': 'Harcama',
                'payment': 'Ödeme',
                'fee': 'Ücret',
                'interest': 'Faiz',
                'refund': 'İade',
                'installment': 'Taksit'
            };
            inputHtml = '<select class="edit-input" data-field="' + fieldName + '">';
            Object.keys(typeOptions).forEach(key => {
                const selected = window.currentTransaction.type === key ? 'selected' : '';
                inputHtml += `<option value="${key}" ${selected}>${typeOptions[key]}</option>`;
            });
            inputHtml += '</select>';
            break;
        case 'select-category':
            inputHtml = '<select class="edit-input" data-field="' + fieldName + '">';
            inputHtml += '<option value="">Kategori Seçin</option>';
            // Categories will be loaded via AJAX
            loadCategoriesForSelect(inputHtml, window.currentTransaction.category_id);
            return; // Exit early, will be handled by AJAX
        case 'select-currency':
            const currencies = ['TRY', 'USD', 'EUR', 'GBP'];
            inputHtml = '<select class="edit-input" data-field="' + fieldName + '">';
            currencies.forEach(currency => {
                const selected = window.currentTransaction.currency === currency ? 'selected' : '';
                inputHtml += `<option value="${currency}" ${selected}>${currency}</option>`;
            });
            inputHtml += '</select>';
            break;
        case 'select-status':
            inputHtml = '<select class="edit-input" data-field="' + fieldName + '">';
            inputHtml += `<option value="0" ${window.currentTransaction.is_paid == 0 ? 'selected' : ''}>Bekliyor</option>`;
            inputHtml += `<option value="1" ${window.currentTransaction.is_paid == 1 ? 'selected' : ''}>Ödendi</option>`;
            inputHtml += '</select>';
            break;
    }
    
    $field.addClass('editing').html(inputHtml);
    
    // Focus on input
    $field.find('.edit-input').focus();
    
    // Handle blur event to save changes
    $field.find('.edit-input').on('blur', function() {
        const newValue = $(this).val();
        updateFieldValue(fieldName, newValue);
        updateFieldDisplay($field, fieldName, newValue);
    });
    
    // Handle Enter key
    $field.find('.edit-input').on('keypress', function(e) {
        if (e.which === 13) {
            $(this).blur();
        }
    });
}

function loadCategoriesForSelect(baseHtml, selectedCategoryId) {
    $.ajax({
        url: '/gelirgider/app/models/Category.php?action=getAll',
        type: 'GET',
        dataType: 'json',
        success: function(categories) {
            let inputHtml = '<select class="edit-input" data-field="category_id">';
            inputHtml += '<option value="">Kategori Seçin</option>';
            categories.forEach(category => {
                const selected = selectedCategoryId == category.id ? 'selected' : '';
                inputHtml += `<option value="${category.id}" ${selected}>${category.name}</option>`;
            });
            inputHtml += '</select>';
            
            const $field = $('.editable-field[data-field="category_id"]');
            $field.addClass('editing').html(inputHtml);
            $field.find('.edit-input').focus();
            
            // Handle events
            $field.find('.edit-input').on('blur', function() {
                const newValue = $(this).val();
                updateFieldValue('category_id', newValue);
                updateFieldDisplay($field, 'category_id', newValue);
            });
        },
        error: function() {
            showNotification('error', 'Kategoriler yüklenirken hata oluştu');
        }
    });
}

function getFieldValue(fieldName) {
    if (!window.currentTransaction) return '';
    
    switch (fieldName) {
        case 'transaction_date':
            return window.currentTransaction.transaction_date;
        case 'type':
            return window.currentTransaction.type;
        case 'amount':
            return window.currentTransaction.amount;
        case 'description':
            return window.currentTransaction.description || '';
        case 'merchant_name':
            return window.currentTransaction.merchant_name || '';
        case 'category_id':
            return window.currentTransaction.category_id;
        case 'currency':
            return window.currentTransaction.currency;
        case 'is_paid':
            return window.currentTransaction.is_paid;
        default:
            return '';
    }
}

function updateFieldValue(fieldName, newValue) {
    if (!window.currentTransaction) return;
    window.currentTransaction[fieldName] = newValue;
}

function updateFieldDisplay($field, fieldName, newValue) {
    $field.removeClass('editing');
    
    let displayValue = newValue;
    
    switch (fieldName) {
        case 'transaction_date':
            displayValue = new Date(newValue).toLocaleString('tr-TR');
            break;
        case 'type':
            displayValue = getTransactionTypeText(newValue);
            break;
        case 'amount':
            displayValue = parseFloat(newValue).toLocaleString('tr-TR', {minimumFractionDigits: 2}) + ' ₺';
            break;
        case 'description':
        case 'merchant_name':
            displayValue = newValue || '-';
            break;
        case 'category_id':
            // Will be updated after save
            displayValue = newValue ? 'Kategori güncellendi' : '-';
            break;
        case 'currency':
            displayValue = newValue;
            break;
        case 'is_paid':
            displayValue = newValue == 1 ? 'Ödendi' : 'Bekliyor';
            break;
    }
    
    $field.html(displayValue);
}

function saveTransactionDetails() {
    if (!window.currentTransaction) {
        showNotification('error', 'İşlem verisi bulunamadı');
        return;
    }
    
    const updateData = {
        id: window.currentTransaction.id,
        transaction_date: window.currentTransaction.transaction_date,
        type: window.currentTransaction.type,
        amount: window.currentTransaction.amount,
        description: window.currentTransaction.description,
        merchant_name: window.currentTransaction.merchant_name,
        category_id: window.currentTransaction.category_id,
        currency: window.currentTransaction.currency,
        is_paid: window.currentTransaction.is_paid,
        ajax: '1'
    };
    
    $.ajax({
        url: '/gelirgider/app/controllers/CreditCardController.php?action=updateTransaction',
        type: 'POST',
        data: updateData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showNotification('success', 'İşlem başarıyla güncellendi');
                $('#transactionDetailsModal').modal('hide');
                
                // Reload transactions table if exists
                if (typeof transactionsTable !== 'undefined') {
                    transactionsTable.ajax.reload();
                }
                
                // Reload page to update balances
                setTimeout(() => location.reload(), 1500);
            } else {
                showNotification('error', response.message || 'İşlem güncellenirken hata oluştu');
            }
        },
        error: function() {
            showNotification('error', 'İşlem güncellenirken hata oluştu');
        }
    });
}

function cancelEdit() {
    if (!window.currentTransaction || !originalValues) {
        exitEditMode();
        return;
    }
    
    // Restore original values
    Object.keys(originalValues).forEach(fieldName => {
        window.currentTransaction[fieldName] = originalValues[fieldName];
    });
    
    // Reload the modal content
    viewTransactionDetails(window.currentTransaction.id);
}

function updateTransaction() {
    const formData = new FormData(document.getElementById('editTransactionForm'));
    
    // Handle custom installment count
    const installmentSelect = $('#editInstallmentSelect').val();
    if (installmentSelect === 'custom') {
        const customInstallment = $('#editCustomInstallment').val();
        if (customInstallment && customInstallment > 0) {
            formData.set('installment_count', customInstallment);
        } else {
            showNotification('error', 'Lütfen geçerli bir taksit sayısı girin');
            return;
        }
    }
    
    formData.append('ajax', '1');
    
    $.ajax({
        url: '/gelirgider/app/controllers/CreditCardController.php?action=updateTransaction',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showNotification('success', 'İşlem başarıyla güncellendi');
                $('#editTransactionModal').modal('hide');
                
                // Reload transactions table if exists
                if (typeof transactionsTable !== 'undefined') {
                    transactionsTable.ajax.reload();
                }
                
                // Reload page to update balances
                setTimeout(() => location.reload(), 1500);
            } else {
                showNotification('error', response.message || 'İşlem güncellenirken hata oluştu');
            }
        },
        error: function() {
            showNotification('error', 'İşlem güncellenirken hata oluştu');
        }
    });
}

function editTransaction(transactionId) {
    $.ajax({
        url: '/gelirgider/app/controllers/CreditCardController.php?action=getTransactionDetails',
        type: 'GET',
        data: { id: transactionId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const transaction = response.data;
                
                // Fill the edit form
                $('#editTransactionId').val(transaction.id);
                $('#editTransactionIdDisplay').val(transaction.id);
                $('#editTransactionDate').val(transaction.transaction_date.replace(' ', 'T'));
                $('#editTransactionType').val(transaction.type);
                $('#editTransactionAmount').val(transaction.amount);
                $('#editTransactionDescription').val(transaction.description || '');
                $('#editTransactionMerchant').val(transaction.merchant_name || '');
                $('#editTransactionCurrency').val(transaction.currency);
                $('#editTransactionStatus').val(transaction.is_paid);
                
                // Set installment count
                const installmentCount = transaction.installment_count || 1;
                if (installmentCount <= 24 && [1,2,3,4,6,8,9,12,18,24].includes(installmentCount)) {
                    $('#editInstallmentSelect').val(installmentCount);
                    $('#editCustomInstallment').addClass('d-none');
                } else {
                    $('#editInstallmentSelect').val('custom');
                    $('#editCustomInstallment').removeClass('d-none').val(installmentCount);
                }
                
                // Set payment wallet
                $('#editPaymentWalletSelect').val(transaction.payment_wallet_id || '');
                
                // Load categories
                loadCategoriesForEdit(transaction.category_id);
                
                // Load tags
                loadTagsForEdit(transaction.tags);
                
                $('#editTransactionModal').modal('show');
            } else {
                showNotification('error', response.message);
            }
        },
        error: function() {
            showNotification('error', 'İşlem detayları yüklenirken hata oluştu');
        }
    });
}

function loadCategoriesForEdit(selectedCategoryId) {
    $.ajax({
        url: '/gelirgider/app/controllers/CategoryController.php?action=getAll',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                let options = '<option value="">Kategori Seçin (İsteğe Bağlı)</option>';
                response.data.forEach(function(category) {
                    const selected = category.id == selectedCategoryId ? 'selected' : '';
                    options += `<option value="${category.id}" ${selected}>${category.name} (${category.type === 'expense' ? 'Gider' : 'Gelir'})</option>`;
                });
                $('#editTransactionCategory').html(options);
            }
        },
        error: function() {
            console.error('Kategoriler yüklenemedi');
            // Fallback - boş seçenek ekle
            $('#editTransactionCategory').html('<option value="">Kategori Seçin (İsteğe Bağlı)</option>');
        }
    });
}

function loadTagsForEdit(selectedTags) {
    $.ajax({
        url: '/gelirgider/app/controllers/TagController.php?action=getAll',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                let options = '';
                response.data.forEach(function(tag) {
                    const selected = selectedTags && selectedTags.some(t => t.id == tag.id) ? 'selected' : '';
                    options += `<option value="${tag.id}" ${selected}>${tag.name}</option>`;
                });
                $('#editTransactionTags').html(options);
            }
        },
        error: function() {
            console.error('Tag\'ler yüklenemedi');
        }
    });
}

function loadCategoriesForAdd() {
    $.ajax({
        url: '/gelirgider/app/controllers/CategoryController.php?action=getAll',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                let options = '<option value="">Kategori Seçin (İsteğe Bağlı)</option>';
                response.data.forEach(function(category) {
                    options += `<option value="${category.id}">${category.name} (${category.type === 'expense' ? 'Gider' : 'Gelir'})</option>`;
                });
                $('#addTransactionCategory').html(options);
            }
        },
        error: function() {
            console.error('Kategoriler yüklenemedi');
            // Fallback - boş seçenek ekle
            $('#addTransactionCategory').html('<option value="">Kategori Seçin (İsteğe Bağlı)</option>');
        }
    });
}
</script>

<?php
// Footer'ı dahil et
include __DIR__ . '/../layouts/footer.php';
?> 