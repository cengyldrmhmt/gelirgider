<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /gelirgider/app/views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../controllers/PaymentPlanController.php';
require_once __DIR__ . '/../../controllers/CategoryController.php';
require_once __DIR__ . '/../../controllers/WalletController.php';

$paymentPlanController = new PaymentPlanController();
$categoryController = new CategoryController();
$walletController = new WalletController();

$data = $paymentPlanController->index();
$categories = $categoryController->index();
$wallets = $walletController->index();

// Header'ı dahil et
include __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-calendar-alt text-primary"></i> Gelişmiş Ödeme Planları
                    </h1>
                    <p class="text-muted">Taksitli ödemeler, milestone ödemeler ve özel ödeme planları</p>
                </div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPlanModal">
                    <i class="fas fa-plus"></i> Yeni Ödeme Planı
                </button>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4" id="statisticsCards">
        <!-- Statistics will be loaded here via AJAX -->
        <div class="col-12 text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Yükleniyor...</span>
            </div>
        </div>
    </div>

    <!-- Payment Summary Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Bu Ay Ödemeler</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="thisMonthPayments">0.00 ₺</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Toplam Borç</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalDebt">0.00 ₺</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Tamamlanan</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="completedAmount">0.00 ₺</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Geciken Ödemeler</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="overduePayments">0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upcoming Payments Alert -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-left-warning">
                <div class="card-body">
                    <h5 class="card-title text-warning">
                        <i class="fas fa-exclamation-triangle"></i> Yaklaşan Ödemeler (30 Gün)
                    </h5>
                    <div id="upcomingPayments">
                        <!-- Upcoming payments will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Plans Table -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Ödeme Planları</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="paymentPlansTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Plan</th>
                                    <th>Kategori</th>
                                    <th>Tür</th>
                                    <th>Toplam Tutar</th>
                                    <th>Ödenen</th>
                                    <th>Kalan</th>
                                    <th>İlerleme</th>
                                    <th>Durum</th>
                                    <th>Sonraki Ödeme</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['plans'] as $plan): ?>
                                <tr>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($plan['title']); ?></strong>
                                            <?php if ($plan['description']): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($plan['description']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($plan['category_name'] ?? 'Kategori Yok'); ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $typeLabels = [
                                            'installment' => 'Taksit',
                                            'milestone' => 'Milestone',
                                            'mixed' => 'Karma',
                                            'custom' => 'Özel'
                                        ];
                                        $typeColors = [
                                            'installment' => 'primary',
                                            'milestone' => 'info',
                                            'mixed' => 'warning',
                                            'custom' => 'success'
                                        ];
                                        ?>
                                        <span class="badge bg-<?php echo $typeColors[$plan['plan_type']] ?? 'secondary'; ?>">
                                            <?php echo $typeLabels[$plan['plan_type']] ?? $plan['plan_type']; ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <strong><?php echo number_format($plan['total_amount'], 2); ?> ₺</strong>
                                    </td>
                                    <td class="text-end text-success">
                                        <?php echo number_format($plan['paid_amount'], 2); ?> ₺
                                    </td>
                                    <td class="text-end text-danger">
                                        <?php echo number_format($plan['remaining_amount'], 2); ?> ₺
                                    </td>
                                    <td>
                                        <?php 
                                        $percentage = $plan['total_amount'] > 0 ? ($plan['paid_amount'] / $plan['total_amount']) * 100 : 0;
                                        $progressClass = $percentage >= 100 ? 'bg-success' : ($percentage >= 50 ? 'bg-warning' : 'bg-danger');
                                        ?>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar <?php echo $progressClass; ?>" 
                                                 role="progressbar" 
                                                 style="width: <?php echo min($percentage, 100); ?>%">
                                                <?php echo number_format($percentage, 1); ?>%
                                            </div>
                                        </div>
                                        <small class="text-muted">
                                            <?php echo $plan['paid_items']; ?>/<?php echo $plan['total_items']; ?> ödeme
                                        </small>
                                    </td>
                                    <td>
                                        <?php
                                        $statusLabels = [
                                            'pending' => 'Bekliyor',
                                            'active' => 'Aktif',
                                            'completed' => 'Tamamlandı',
                                            'cancelled' => 'İptal',
                                            'overdue' => 'Gecikmiş'
                                        ];
                                        $statusColors = [
                                            'pending' => 'warning',
                                            'active' => 'primary',
                                            'completed' => 'success',
                                            'cancelled' => 'secondary',
                                            'overdue' => 'danger'
                                        ];
                                        ?>
                                        <span class="badge bg-<?php echo $statusColors[$plan['status']] ?? 'secondary'; ?>">
                                            <?php echo $statusLabels[$plan['status']] ?? $plan['status']; ?>
                                        </span>
                                        <?php if ($plan['overdue_items'] > 0): ?>
                                            <br><small class="text-danger">
                                                <i class="fas fa-exclamation-triangle"></i> 
                                                <?php echo $plan['overdue_items']; ?> gecikmiş
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($plan['next_payment_date']): ?>
                                            <?php 
                                            $nextDate = new DateTime($plan['next_payment_date']);
                                            $today = new DateTime();
                                            $diff = $today->diff($nextDate);
                                            $daysUntil = $nextDate > $today ? $diff->days : -$diff->days;
                                            ?>
                                            <div>
                                                <?php echo $nextDate->format('d.m.Y'); ?>
                                                <br>
                                                <small class="<?php echo $daysUntil < 0 ? 'text-danger' : ($daysUntil <= 7 ? 'text-warning' : 'text-muted'); ?>">
                                                    <?php 
                                                    if ($daysUntil < 0) {
                                                        echo abs($daysUntil) . ' gün gecikmiş';
                                                    } elseif ($daysUntil == 0) {
                                                        echo 'Bugün';
                                                    } else {
                                                        echo $daysUntil . ' gün kaldı';
                                                    }
                                                    ?>
                                                </small>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-primary view-plan" 
                                                    data-id="<?php echo $plan['id']; ?>"
                                                    title="Detayları Görüntüle">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-success make-payment" 
                                                    data-id="<?php echo $plan['id']; ?>"
                                                    title="Ödeme Yap"
                                                    <?php echo $plan['status'] === 'completed' ? 'disabled' : ''; ?>>
                                                <i class="fas fa-credit-card"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-warning edit-plan" 
                                                    data-id="<?php echo $plan['id']; ?>"
                                                    title="Düzenle">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger delete-plan" 
                                                    data-id="<?php echo $plan['id']; ?>"
                                                    title="Sil">
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

<!-- Add Payment Plan Modal -->
<div class="modal fade" id="addPlanModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Ödeme Planı Oluştur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addPlanForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Plan Başlığı *</label>
                                <input type="text" class="form-control" name="title" required 
                                       placeholder="Örn: Ev Alımı - 800.000 TL">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Toplam Tutar *</label>
                                <input type="number" class="form-control" name="total_amount" step="0.01" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <textarea class="form-control" name="description" rows="2" 
                                  placeholder="Ödeme planı hakkında detaylar..."></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Kategori</label>
                                <select class="form-select" name="category_id">
                                    <option value="">Kategori Seçin</option>
                                    <?php foreach ($categories['categories'] as $category): ?>
                                        <option value="<?php echo $category['id']; ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Plan Türü *</label>
                                <select class="form-select" name="plan_type" required>
                                    <option value="installment">Taksit (Düzenli)</option>
                                    <option value="cash_installment">Nakit Taksit</option>
                                    <option value="milestone">Milestone (Aşama)</option>
                                    <option value="mixed">Karma</option>
                                    <option value="custom">Özel</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Ödeme Yöntemi *</label>
                                <select class="form-select" name="payment_method" required>
                                    <option value="cash">Nakit</option>
                                    <option value="credit_card">Kredi Kartı</option>
                                    <option value="bank_transfer">Banka Transferi</option>
                                    <option value="mixed">Karma</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Başlangıç Tarihi *</label>
                                <input type="date" class="form-control" name="start_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Bitiş Tarihi</label>
                                <input type="date" class="form-control" name="end_date">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3" id="installmentCountGroup" style="display: none;">
                                <label class="form-label">Taksit Sayısı</label>
                                <input type="number" class="form-control" name="installment_count" min="1" max="60" value="1">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Auto Generate Installments Button -->
                    <div class="row" id="autoGenerateSection" style="display: none;">
                        <div class="col-12">
                            <button type="button" class="btn btn-info mb-3" id="autoGenerateInstallments">
                                <i class="fas fa-magic"></i> Taksitleri Otomatik Oluştur
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notlar</label>
                        <textarea class="form-control" name="notes" rows="2" 
                                  placeholder="Ek notlar ve açıklamalar..."></textarea>
                    </div>
                    
                    <!-- Payment Items Section -->
                    <div class="border-top pt-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">Ödeme Detayları</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="addPaymentItem">
                                <i class="fas fa-plus"></i> Ödeme Ekle
                            </button>
                        </div>
                        <div id="paymentItems">
                            <!-- Payment items will be added here dynamically -->
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" id="savePlan">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<!-- View Plan Details Modal -->
<div class="modal fade" id="viewPlanModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ödeme Planı Detayları</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="planDetailsContent">
                <!-- Plan details will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Make Payment Modal -->
<div class="modal fade" id="makePaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ödeme Yap</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="makePaymentForm">
                    <input type="hidden" name="item_id" id="paymentItemId">
                    
                    <div class="mb-3">
                        <label class="form-label">Ödeme Tutarı *</label>
                        <input type="number" class="form-control" name="amount" step="0.01" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Ödeme Yöntemi *</label>
                        <select class="form-select" name="payment_method" required>
                            <option value="cash">Nakit</option>
                            <option value="credit_card">Kredi Kartı</option>
                        </select>
                    </div>
                    
                    <div class="mb-3" id="walletGroup">
                        <label class="form-label">Cüzdan</label>
                        <select class="form-select" name="wallet_id">
                            <option value="">Cüzdan Seçin</option>
                            <?php foreach ($wallets['wallets'] as $wallet): ?>
                                <option value="<?php echo $wallet['id']; ?>">
                                    <?php echo htmlspecialchars($wallet['name']); ?> (<?php echo $wallet['currency']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3" id="creditCardGroup" style="display: none;">
                        <label class="form-label">Kredi Kartı</label>
                        <select class="form-select" name="credit_card_id">
                            <option value="">Kredi Kartı Seçin</option>
                            <!-- Credit cards will be loaded via AJAX -->
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notlar</label>
                        <textarea class="form-control" name="notes" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-success" id="processPayment">Ödemeyi Kaydet</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Payment Plan Modal -->
<div class="modal fade" id="editPlanModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ödeme Planını Düzenle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editPlanForm">
                    <input type="hidden" name="id" id="editPlanId">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Plan Başlığı *</label>
                                <input type="text" class="form-control" name="title" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Toplam Tutar *</label>
                                <input type="number" class="form-control" name="total_amount" step="0.01" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <textarea class="form-control" name="description" rows="2"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Kategori</label>
                                <select class="form-select" name="category_id">
                                    <option value="">Kategori Seçin</option>
                                    <?php foreach ($categories['categories'] as $category): ?>
                                        <option value="<?php echo $category['id']; ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Plan Türü *</label>
                                <select class="form-select" name="plan_type" required>
                                    <option value="installment">Taksit (Düzenli)</option>
                                    <option value="cash_installment">Nakit Taksit</option>
                                    <option value="milestone">Milestone (Aşama)</option>
                                    <option value="mixed">Karma</option>
                                    <option value="custom">Özel</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Durum</label>
                                <select class="form-select" name="status">
                                    <option value="pending">Bekliyor</option>
                                    <option value="active">Aktif</option>
                                    <option value="completed">Tamamlandı</option>
                                    <option value="cancelled">İptal</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Başlangıç Tarihi *</label>
                                <input type="date" class="form-control" name="start_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Bitiş Tarihi</label>
                                <input type="date" class="form-control" name="end_date">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notlar</label>
                        <textarea class="form-control" name="notes" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" id="updatePlan">Güncelle</button>
            </div>
        </div>
    </div>
</div>

<!-- Custom CSS -->
<style>
.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}

.payment-item {
    border: 1px solid #e3e6f0;
    border-radius: 0.35rem;
    padding: 1rem;
    margin-bottom: 1rem;
    background-color: #f8f9fc;
}

.payment-item:last-child {
    margin-bottom: 0;
}

.table th {
    border-top: none;
    font-weight: 600;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.progress {
    border-radius: 10px;
}

.progress-bar {
    border-radius: 10px;
}

.btn-group .btn {
    margin-right: 2px;
}

.btn-group .btn:last-child {
    margin-right: 0;
}

.card-header {
    background-color: #f8f9fc;
    border-bottom: 1px solid #e3e6f0;
}

.badge {
    font-size: 0.75rem;
}

.spinner-border {
    width: 1.5rem;
    height: 1.5rem;
}
</style>

<!-- JavaScript -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    console.log('Payment Plans page ready, initializing...');
    
    // Initialize DataTable with proper event handling
    const paymentPlansTable = $('#paymentPlansTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/tr.json"
        },
        "order": [[0, "desc"]],
        "pageLength": 25,
        "responsive": true,
        "drawCallback": function(settings) {
            // Re-bind events after DataTable redraws
            console.log('DataTable redrawn, rebinding events...');
            bindPaymentPlanEvents();
        }
    });
    
    // Initial event binding
    bindPaymentPlanEvents();
    
    // Load statistics
    loadStatistics();
    
    // Load upcoming payments
    loadUpcomingPayments();
    
    // Load payment summary cards
    loadPaymentSummary();
    
    // Set default start date to today
    $('input[name="start_date"]').val(new Date().toISOString().split('T')[0]);
    
    // Plan type change handler
    $('select[name="plan_type"]').on('change', function() {
        const planType = $(this).val();
        if (planType === 'installment' || planType === 'cash_installment') {
            $('#installmentCountGroup').show();
            $('#autoGenerateSection').show();
        } else {
            $('#installmentCountGroup').hide();
            $('#autoGenerateSection').hide();
        }
    });
    
    // Auto generate installments
    $('#autoGenerateInstallments').on('click', function() {
        autoGenerateInstallments();
    });
    
    // Payment method change handler
    $('select[name="payment_method"]').on('change', function() {
        const method = $(this).val();
        if (method === 'cash') {
            $('#walletGroup').show();
            $('#creditCardGroup').hide();
        } else if (method === 'credit_card') {
            $('#walletGroup').hide();
            $('#creditCardGroup').show();
            loadCreditCards();
        } else {
            $('#walletGroup').show();
            $('#creditCardGroup').show();
            loadCreditCards();
        }
    });
    
    // Update plan button
    $('#updatePlan').on('click', function() {
        updatePlan();
    });
    
    // Other static event handlers
    $('#addPaymentItem').on('click', function() {
        addPaymentItemRow();
    });

    $('#savePlan').on('click', function() {
        savePlan();
    });

    $('#processPayment').on('click', function() {
        processPayment();
    });

    // Remove item event handler
    $(document).on('click', '.remove-item', function() {
        $(this).closest('.payment-item').remove();
    });

    // Installment amount change handler
    $(document).on('input', '.installment-amount', function() {
        updateInstallmentTotal();
    });

    // Payment method change handler for make payment modal
    $(document).on('change', '#makePaymentModal select[name="payment_method"]', function() {
        const method = $(this).val();
        if (method === 'cash') {
            $('#walletGroup').show();
            $('#creditCardGroup').hide();
        } else if (method === 'credit_card') {
            $('#walletGroup').hide();
            $('#creditCardGroup').show();
            loadCreditCards();
        } else {
            $('#walletGroup').show();
            $('#creditCardGroup').show();
            loadCreditCards();
        }
    });

    // Modal cleanup when closed
    $('#addPlanModal').on('hidden.bs.modal', function() {
        $('#addPlanForm')[0].reset();
        $('#paymentItems').empty();
        $('#cashInstallmentWarning').remove();
    });

    $('#editPlanModal').on('hidden.bs.modal', function() {
        $('#editPlanForm')[0].reset();
    });

    $('#makePaymentModal').on('hidden.bs.modal', function() {
        $('#makePaymentForm')[0].reset();
    });

    // Debug function for testing
    function testDeleteFunction() {
        console.log('Testing delete function...');
        const testPlanId = 4; // Use an existing plan ID
        deletePlan(testPlanId);
    }

    // Add to window for debugging
    window.testDeleteFunction = testDeleteFunction;
    window.deletePlan = deletePlan;
    window.loadPlanForPayment = loadPlanForPayment;
});

// Separate function to bind payment plan events
function bindPaymentPlanEvents() {
    console.log('Binding payment plan events...');
    
    // Remove existing handlers to prevent duplicates
    $(document).off('click', '.view-plan');
    $(document).off('click', '.make-payment');
    $(document).off('click', '.edit-plan');
    $(document).off('click', '.delete-plan');
    
    // Bind new handlers
    $(document).on('click', '.view-plan', function(e) {
        e.preventDefault();
        const planId = $(this).data('id');
        console.log('View plan clicked, ID:', planId);
        viewPlanDetails(planId);
    });

    $(document).on('click', '.make-payment', function(e) {
        e.preventDefault();
        const itemId = $(this).data('item-id');
        const planId = $(this).data('id');
        console.log('Make payment clicked, item ID:', itemId, 'plan ID:', planId);
        
        if (itemId) {
            $('#paymentItemId').val(itemId);
            $('#makePaymentModal').modal('show');
        } else if (planId) {
            loadPlanForPayment(planId);
        }
    });

    $(document).on('click', '.edit-plan', function(e) {
        e.preventDefault();
        const planId = $(this).data('id');
        console.log('Edit plan clicked, ID:', planId);
        editPlan(planId);
    });

    $(document).on('click', '.delete-plan', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const planId = $(this).data('id');
        console.log('Delete plan clicked, ID:', planId);
        
        if (!planId) {
            console.error('Plan ID not found!');
            showNotification('error', 'Plan ID bulunamadı');
            return;
        }
        
        deletePlan(planId);
    });
    
    console.log('Payment plan events bound successfully');
}

function loadStatistics() {
    $.ajax({
        url: '/gelirgider/app/controllers/PaymentPlanController.php?action=getStatistics',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                displayStatistics(response.data);
            }
        },
        error: function() {
            $('#statisticsCards').html('<div class="col-12"><div class="alert alert-danger">İstatistikler yüklenemedi</div></div>');
        }
    });
}

function displayStatistics(stats) {
    const html = `
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Toplam Plan</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">${stats.total_plans}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-alt fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Toplam Tutar</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">${parseFloat(stats.total_amount).toLocaleString('tr-TR', {minimumFractionDigits: 2})} ₺</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Ödenen</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">${parseFloat(stats.total_paid).toLocaleString('tr-TR', {minimumFractionDigits: 2})} ₺</div>
                            <div class="row no-gutters align-items-center">
                                <div class="col-auto">
                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">${stats.completion_percentage.toFixed(1)}%</div>
                                </div>
                                <div class="col">
                                    <div class="progress progress-sm mr-2">
                                        <div class="progress-bar bg-info" role="progressbar" style="width: ${stats.completion_percentage}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Kalan</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">${parseFloat(stats.total_remaining).toLocaleString('tr-TR', {minimumFractionDigits: 2})} ₺</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    $('#statisticsCards').html(html);
}

function loadUpcomingPayments() {
    $.ajax({
        url: '/gelirgider/app/controllers/PaymentPlanController.php?action=getUpcomingPayments&days=30',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                displayUpcomingPayments(response.data);
            }
        }
    });
}

function displayUpcomingPayments(payments) {
    if (payments.length === 0) {
        $('#upcomingPayments').html('<p class="text-muted mb-0">Yaklaşan ödeme bulunmuyor.</p>');
        return;
    }
    
    let html = '<div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Plan</th><th>Ödeme</th><th>Tutar</th><th>Vade</th><th>Durum</th></tr></thead><tbody>';
    
    payments.forEach(function(payment) {
        const daysClass = payment.days_until_due < 0 ? 'text-danger' : (payment.days_until_due <= 7 ? 'text-warning' : 'text-muted');
        const daysText = payment.days_until_due < 0 ? `${Math.abs(payment.days_until_due)} gün gecikmiş` : 
                        payment.days_until_due === 0 ? 'Bugün' : `${payment.days_until_due} gün kaldı`;
        
        html += `
            <tr>
                <td><strong>${payment.plan_title}</strong></td>
                <td>${payment.title}</td>
                <td>${parseFloat(payment.amount).toLocaleString('tr-TR', {minimumFractionDigits: 2})} ₺</td>
                <td>
                    ${new Date(payment.due_date).toLocaleDateString('tr-TR')}
                    <br><small class="${daysClass}">${daysText}</small>
                </td>
                <td>
                    <button class="btn btn-sm btn-outline-success make-payment" data-item-id="${payment.id}">
                        <i class="fas fa-credit-card"></i> Öde
                    </button>
                </td>
            </tr>
        `;
    });
    
    html += '</tbody></table></div>';
    $('#upcomingPayments').html(html);
}

function loadCreditCards() {
    console.log('Loading credit cards...');
    
    $.ajax({
        url: '/gelirgider/app/controllers/CreditCardController.php?action=getAll',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                const select = $('select[name="credit_card_id"]');
                select.empty().append('<option value="">Kredi Kartı Seçin</option>');
                
                response.data.forEach(function(card) {
                    select.append(`<option value="${card.id}">${card.name} (${card.currency})</option>`);
                });
            }
        },
        error: function() {
            console.log('Credit cards could not be loaded');
        }
    });
}

function viewPlanDetails(planId) {
    $.ajax({
        url: '/gelirgider/app/controllers/PaymentPlanController.php?action=getPlan&id=' + planId,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                displayPlanDetails(response.data);
                $('#viewPlanModal').modal('show');
            } else {
                alert('Plan detayları yüklenemedi: ' + response.message);
            }
        }
    });
}

function displayPlanDetails(plan) {
    let html = `
        <div class="row mb-4">
            <div class="col-md-8">
                <h4>${plan.title}</h4>
                <p class="text-muted">${plan.description || ''}</p>
            </div>
            <div class="col-md-4 text-end">
                <div class="card">
                    <div class="card-body">
                        <h5>Toplam: ${parseFloat(plan.total_amount).toLocaleString('tr-TR', {minimumFractionDigits: 2})} ₺</h5>
                        <p class="mb-1">Ödenen: <span class="text-success">${parseFloat(plan.paid_amount).toLocaleString('tr-TR', {minimumFractionDigits: 2})} ₺</span></p>
                        <p class="mb-0">Kalan: <span class="text-danger">${parseFloat(plan.remaining_amount).toLocaleString('tr-TR', {minimumFractionDigits: 2})} ₺</span></p>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    if (plan.items && plan.items.length > 0) {
        html += `
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Sıra</th>
                            <th>Ödeme</th>
                            <th>Tutar</th>
                            <th>Vade Tarihi</th>
                            <th>Ödeme Yöntemi</th>
                            <th>Durum</th>
                            <th>İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        plan.items.forEach(function(item) {
            const statusClass = item.status === 'paid' ? 'success' : (item.status === 'overdue' ? 'danger' : 'warning');
            const statusText = item.status === 'paid' ? 'Ödendi' : (item.status === 'overdue' ? 'Gecikmiş' : 'Bekliyor');
            
            html += `
                <tr>
                    <td>${item.item_order}</td>
                    <td>
                        <strong>${item.title}</strong>
                        ${item.description ? '<br><small class="text-muted">' + item.description + '</small>' : ''}
                    </td>
                    <td>${parseFloat(item.amount).toLocaleString('tr-TR', {minimumFractionDigits: 2})} ₺</td>
                    <td>${new Date(item.due_date).toLocaleDateString('tr-TR')}</td>
                    <td>
                        <span class="badge bg-secondary">${item.payment_method === 'cash' ? 'Nakit' : 'Kredi Kartı'}</span>
                        ${item.wallet_name ? '<br><small>' + item.wallet_name + '</small>' : ''}
                        ${item.credit_card_name ? '<br><small>' + item.credit_card_name + '</small>' : ''}
                    </td>
                    <td>
                        <span class="badge bg-${statusClass}">${statusText}</span>
                        ${item.paid_amount > 0 ? '<br><small>Ödenen: ' + parseFloat(item.paid_amount).toLocaleString('tr-TR', {minimumFractionDigits: 2}) + ' ₺</small>' : ''}
                    </td>
                    <td>
                        ${item.status !== 'paid' ? '<button class="btn btn-sm btn-success make-payment" data-item-id="' + item.id + '"><i class="fas fa-credit-card"></i></button>' : ''}
                    </td>
                </tr>
            `;
        });
        
        html += '</tbody></table></div>';
    }
    
    $('#planDetailsContent').html(html);
}

function addPaymentItemRow() {
    const itemCount = $('#paymentItems .payment-item').length;
    const html = `
        <div class="payment-item">
            <div class="row">
                <div class="col-md-4">
                    <label class="form-label">Ödeme Başlığı</label>
                    <input type="text" class="form-control" name="items[${itemCount}][title]" placeholder="Örn: 1. Taksit">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tutar</label>
                    <input type="number" class="form-control" name="items[${itemCount}][amount]" step="0.01">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Vade Tarihi</label>
                    <input type="date" class="form-control" name="items[${itemCount}][due_date]">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-danger w-100 remove-item">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-4">
                    <label class="form-label">Ödeme Yöntemi</label>
                    <select class="form-select" name="items[${itemCount}][payment_method]">
                        <option value="cash">Nakit</option>
                        <option value="credit_card">Kredi Kartı</option>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Açıklama</label>
                    <input type="text" class="form-control" name="items[${itemCount}][description]" placeholder="Ek açıklama...">
                </div>
            </div>
        </div>
    `;
    $('#paymentItems').append(html);
}

function savePlan() {
    const formData = new FormData($('#addPlanForm')[0]);
    const planType = $('select[name="plan_type"]').val();
    const totalAmount = parseFloat($('input[name="total_amount"]').val()) || 0;
    
    // Collect payment items
    const items = [];
    let itemsTotal = 0;
    
    $('#paymentItems .payment-item').each(function(index) {
        const item = {
            title: $(this).find('input[name*="[title]"]').val(),
            amount: $(this).find('input[name*="[amount]"]').val(),
            due_date: $(this).find('input[name*="[due_date]"]').val(),
            payment_method: $(this).find('select[name*="[payment_method]"]').val(),
            description: $(this).find('input[name*="[description]"]').val()
        };
        if (item.title && item.amount && item.due_date) {
            items.push(item);
            itemsTotal += parseFloat(item.amount) || 0;
        }
    });
    
    // Nakit taksit için toplam kontrol
    if (planType === 'cash_installment' && items.length > 0) {
        if (Math.abs(itemsTotal - totalAmount) > 0.01) {
            showNotification('error', `Taksit tutarlarının toplamı (${itemsTotal.toLocaleString('tr-TR', {minimumFractionDigits: 2})} ₺) plan toplam tutarı (${totalAmount.toLocaleString('tr-TR', {minimumFractionDigits: 2})} ₺) ile eşleşmiyor.`);
            return;
        }
    }
    
    if (items.length === 0) {
        showNotification('error', 'En az bir ödeme kalemi eklemelisiniz.');
        return;
    }
    
    formData.append('items', JSON.stringify(items));
    
    $.ajax({
        url: '/gelirgider/app/controllers/PaymentPlanController.php?action=create',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#addPlanModal').modal('hide');
                showNotification('success', 'Ödeme planı başarıyla oluşturuldu!');
                setTimeout(function() {
                    location.reload();
                }, 1000);
            } else {
                showNotification('error', 'Hata: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Save plan error:', error);
            showNotification('error', 'Bir hata oluştu. Lütfen tekrar deneyin.');
        }
    });
}

function processPayment() {
    const formData = new FormData($('#makePaymentForm')[0]);
    
    $.ajax({
        url: '/gelirgider/app/controllers/PaymentPlanController.php?action=makePayment',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#makePaymentModal').modal('hide');
                location.reload();
            } else {
                alert('Hata: ' + response.message);
            }
        },
        error: function() {
            alert('Bir hata oluştu. Lütfen tekrar deneyin.');
        }
    });
}

function deletePlan(planId) {
    console.log('=== DELETE PLAN FUNCTION CALLED ===');
    console.log('Plan ID:', planId);
    console.log('Plan ID type:', typeof planId);
    
    // Plan ID kontrolü
    if (!planId || planId === '' || planId === 'undefined') {
        console.error('Invalid plan ID:', planId);
        showNotification('error', 'Geçersiz plan ID: ' + planId);
        return;
    }
    
    // Confirm dialog
    const confirmMessage = `Bu ödeme planını silmek istediğinizden emin misiniz?\n\nPlan ID: ${planId}`;
    if (!confirm(confirmMessage)) {
        console.log('User cancelled deletion');
        return;
    }
    
    console.log('User confirmed deletion, preparing AJAX request...');
    
    // AJAX request
    $.ajax({
        url: '/gelirgider/app/controllers/PaymentPlanController.php?action=delete',
        type: 'POST',
        data: { 
            id: planId 
        },
        dataType: 'json',
        timeout: 10000, // 10 second timeout
        beforeSend: function(xhr) {
            console.log('AJAX request starting...');
            console.log('URL:', '/gelirgider/app/controllers/PaymentPlanController.php?action=delete');
            console.log('Data being sent:', { id: planId });
            
            // Disable the delete button to prevent double-clicks
            $(`.delete-plan[data-id="${planId}"]`).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        },
        success: function(response, textStatus, xhr) {
            console.log('=== AJAX SUCCESS ===');
            console.log('Response:', response);
            console.log('Text Status:', textStatus);
            console.log('XHR Status:', xhr.status);
            
            // Re-enable button
            $(`.delete-plan[data-id="${planId}"]`).prop('disabled', false).html('<i class="fas fa-trash"></i>');
            
            if (response && response.success) {
                console.log('Deletion successful!');
                showNotification('success', 'Ödeme planı başarıyla silindi');
                
                // Reload page after short delay
                setTimeout(function() {
                    console.log('Reloading page...');
                    location.reload();
                }, 1500);
            } else {
                console.error('Deletion failed:', response);
                const errorMessage = response && response.message ? response.message : 'Bilinmeyen hata oluştu';
                showNotification('error', 'Silme işlemi başarısız: ' + errorMessage);
            }
        },
        error: function(xhr, textStatus, errorThrown) {
            console.log('=== AJAX ERROR ===');
            console.error('XHR object:', xhr);
            console.error('Text Status:', textStatus);
            console.error('Error Thrown:', errorThrown);
            console.error('Response Text:', xhr.responseText);
            console.error('Status Code:', xhr.status);
            
            // Re-enable button
            $(`.delete-plan[data-id="${planId}"]`).prop('disabled', false).html('<i class="fas fa-trash"></i>');
            
            let errorMessage = 'Silme işlemi sırasında bir hata oluştu.';
            
            if (xhr.status === 0) {
                errorMessage = 'Sunucuya bağlanılamadı. İnternet bağlantınızı kontrol edin.';
            } else if (xhr.status === 404) {
                errorMessage = 'Controller dosyası bulunamadı (404).';
            } else if (xhr.status === 500) {
                errorMessage = 'Sunucu hatası (500). Lütfen sistem yöneticisine başvurun.';
            } else if (textStatus === 'timeout') {
                errorMessage = 'İstek zaman aşımına uğradı. Lütfen tekrar deneyin.';
            } else if (xhr.responseText) {
                try {
                    const errorResponse = JSON.parse(xhr.responseText);
                    if (errorResponse.message) {
                        errorMessage = errorResponse.message;
                    }
                } catch (e) {
                    console.error('Could not parse error response as JSON');
                    if (xhr.responseText.length < 200) {
                        errorMessage += ' Server response: ' + xhr.responseText;
                    }
                }
            }
            
            showNotification('error', errorMessage);
        },
        complete: function(xhr, textStatus) {
            console.log('=== AJAX COMPLETE ===');
            console.log('Final status:', textStatus);
            console.log('Final XHR status:', xhr.status);
        }
    });
}

function showNotification(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
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

function loadPaymentSummary() {
    $.ajax({
        url: '/gelirgider/app/controllers/PaymentPlanController.php?action=getPaymentSummary',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                displayPaymentSummary(response.data);
            }
        },
        error: function() {
            console.log('Payment summary could not be loaded');
        }
    });
}

function displayPaymentSummary(data) {
    $('#thisMonthPayments').text(parseFloat(data.this_month_payments || 0).toLocaleString('tr-TR', {minimumFractionDigits: 2}) + ' ₺');
    $('#totalDebt').text(parseFloat(data.total_debt || 0).toLocaleString('tr-TR', {minimumFractionDigits: 2}) + ' ₺');
    $('#completedAmount').text(parseFloat(data.completed_amount || 0).toLocaleString('tr-TR', {minimumFractionDigits: 2}) + ' ₺');
    $('#overduePayments').text(data.overdue_count || 0);
}

function autoGenerateInstallments() {
    const totalAmount = parseFloat($('input[name="total_amount"]').val());
    const installmentCount = parseInt($('input[name="installment_count"]').val());
    const startDate = new Date($('input[name="start_date"]').val());
    const planType = $('select[name="plan_type"]').val();
    
    if (!totalAmount || !installmentCount || !startDate) {
        showNotification('error', 'Lütfen toplam tutar, taksit sayısı ve başlangıç tarihini girin.');
        return;
    }
    
    // Clear existing items
    $('#paymentItems').empty();
    
    if (planType === 'cash_installment') {
        // Nakit taksit için manuel giriş alanları oluştur
        for (let i = 0; i < installmentCount; i++) {
            const dueDate = new Date(startDate);
            dueDate.setMonth(dueDate.getMonth() + i);
            
            const html = `
                <div class="payment-item">
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-label">Ödeme Başlığı</label>
                            <input type="text" class="form-control" name="items[${i}][title]" value="${i + 1}. Nakit Taksit">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tutar (Manuel Girin) *</label>
                            <input type="number" class="form-control installment-amount" name="items[${i}][amount]" step="0.01" placeholder="Taksit tutarını girin" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Vade Tarihi</label>
                            <input type="date" class="form-control" name="items[${i}][due_date]" value="${dueDate.toISOString().split('T')[0]}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" class="btn btn-danger w-100 remove-item">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-4">
                            <label class="form-label">Ödeme Yöntemi</label>
                            <select class="form-select" name="items[${i}][payment_method]">
                                <option value="cash" selected>Nakit</option>
                                <option value="credit_card">Kredi Kartı</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Açıklama</label>
                            <input type="text" class="form-control" name="items[${i}][description]" placeholder="Ek açıklama...">
                        </div>
                    </div>
                </div>
            `;
            $('#paymentItems').append(html);
        }
        
        // Toplam kontrol uyarısı ve hesaplama alanı ekle
        const warningHtml = `
            <div class="alert alert-warning mt-3" id="cashInstallmentWarning">
                <div class="row">
                    <div class="col-md-8">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Dikkat:</strong> Nakit taksit seçeneğinde taksit tutarlarını manuel olarak girmelisiniz. 
                        Taksit tutarlarının toplamının <strong>${totalAmount.toLocaleString('tr-TR', {minimumFractionDigits: 2})} ₺</strong> olmasına dikkat edin.
                    </div>
                    <div class="col-md-4">
                        <div class="text-end">
                            <strong>Girilen Toplam: <span id="currentTotal" class="text-danger">0.00 ₺</span></strong>
                            <br>
                            <small>Hedef: ${totalAmount.toLocaleString('tr-TR', {minimumFractionDigits: 2})} ₺</small>
                        </div>
                    </div>
                </div>
            </div>
        `;
        $('#paymentItems').append(warningHtml);
        
        // Toplam hesaplama için event listener ekle
        $(document).on('input', '.installment-amount', function() {
            updateInstallmentTotal();
        });
        
    } else {
        // Normal eşit taksitler
        const installmentAmount = totalAmount / installmentCount;
        
        for (let i = 0; i < installmentCount; i++) {
            const dueDate = new Date(startDate);
            dueDate.setMonth(dueDate.getMonth() + i);
            
            const html = `
                <div class="payment-item">
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-label">Ödeme Başlığı</label>
                            <input type="text" class="form-control" name="items[${i}][title]" value="${i + 1}. Taksit">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tutar</label>
                            <input type="number" class="form-control" name="items[${i}][amount]" step="0.01" value="${installmentAmount.toFixed(2)}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Vade Tarihi</label>
                            <input type="date" class="form-control" name="items[${i}][due_date]" value="${dueDate.toISOString().split('T')[0]}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" class="btn btn-danger w-100 remove-item">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-4">
                            <label class="form-label">Ödeme Yöntemi</label>
                            <select class="form-select" name="items[${i}][payment_method]">
                                <option value="cash">Nakit</option>
                                <option value="credit_card">Kredi Kartı</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Açıklama</label>
                            <input type="text" class="form-control" name="items[${i}][description]" placeholder="Ek açıklama...">
                        </div>
                    </div>
                </div>
            `;
            $('#paymentItems').append(html);
        }
    }
}

// Nakit taksit toplam hesaplama fonksiyonu
function updateInstallmentTotal() {
    let total = 0;
    $('.installment-amount').each(function() {
        const value = parseFloat($(this).val()) || 0;
        total += value;
    });
    
    const targetAmount = parseFloat($('input[name="total_amount"]').val()) || 0;
    const $currentTotal = $('#currentTotal');
    
    if ($currentTotal.length) {
        $currentTotal.text(total.toLocaleString('tr-TR', {minimumFractionDigits: 2}) + ' ₺');
        
        // Renk kontrolü
        if (Math.abs(total - targetAmount) < 0.01) {
            $currentTotal.removeClass('text-danger text-warning').addClass('text-success');
        } else if (total > targetAmount) {
            $currentTotal.removeClass('text-success text-warning').addClass('text-danger');
        } else {
            $currentTotal.removeClass('text-success text-danger').addClass('text-warning');
        }
    }
}

function editPlan(planId) {
    $.ajax({
        url: '/gelirgider/app/controllers/PaymentPlanController.php?action=getPlan&id=' + planId,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                populateEditForm(response.data);
                $('#editPlanModal').modal('show');
            } else {
                showNotification('error', 'Plan bilgileri yüklenemedi: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Edit plan error:', error);
            showNotification('error', 'Plan bilgileri yüklenirken bir hata oluştu.');
        }
    });
}

function populateEditForm(plan) {
    $('#editPlanId').val(plan.id);
    $('#editPlanForm input[name="title"]').val(plan.title);
    $('#editPlanForm textarea[name="description"]').val(plan.description);
    $('#editPlanForm input[name="total_amount"]').val(plan.total_amount);
    $('#editPlanForm select[name="category_id"]').val(plan.category_id);
    $('#editPlanForm select[name="plan_type"]').val(plan.plan_type);
    $('#editPlanForm select[name="status"]').val(plan.status);
    $('#editPlanForm input[name="start_date"]').val(plan.start_date);
    $('#editPlanForm input[name="end_date"]').val(plan.end_date);
    $('#editPlanForm textarea[name="notes"]').val(plan.notes);
}

function updatePlan() {
    const formData = new FormData($('#editPlanForm')[0]);
    
    $.ajax({
        url: '/gelirgider/app/controllers/PaymentPlanController.php?action=update',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#editPlanModal').modal('hide');
                showNotification('success', 'Ödeme planı başarıyla güncellendi!');
                setTimeout(function() {
                    location.reload();
                }, 1000);
            } else {
                showNotification('error', 'Hata: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Update plan error:', error);
            showNotification('error', 'Bir hata oluştu. Lütfen tekrar deneyin.');
        }
    });
}

// Eksik fonksiyonları ekle
function loadPlanForPayment(planId) {
    console.log('Loading plan for payment, ID:', planId);
    
    $.ajax({
        url: '/gelirgider/app/controllers/PaymentPlanController.php?action=getPlan&id=' + planId,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data.items) {
                showPaymentSelectionModal(response.data);
            } else {
                showNotification('error', 'Plan ödemeleri yüklenemedi');
            }
        },
        error: function() {
            showNotification('error', 'Plan bilgileri yüklenirken hata oluştu');
        }
    });
}

function showPaymentSelectionModal(plan) {
    let html = '<div class="mb-3"><h6>Hangi ödemeyi yapmak istiyorsunuz?</h6></div>';
    
    plan.items.forEach(function(item) {
        if (item.status !== 'paid') {
            const dueDate = new Date(item.due_date).toLocaleDateString('tr-TR');
            html += `
                <div class="border rounded p-2 mb-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${item.title}</strong><br>
                            <small class="text-muted">Vade: ${dueDate} - ${parseFloat(item.amount).toLocaleString('tr-TR', {minimumFractionDigits: 2})} ₺</small>
                        </div>
                        <button class="btn btn-sm btn-success make-payment" data-item-id="${item.id}">
                            <i class="fas fa-credit-card"></i> Öde
                        </button>
                    </div>
                </div>
            `;
        }
    });
    
    if (html.indexOf('make-payment') === -1) {
        html += '<div class="alert alert-info">Tüm ödemeler tamamlanmış.</div>';
    }
    
    // Create and show modal
    const modalHtml = `
        <div class="modal fade" id="paymentSelectionModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Ödeme Seçin - ${plan.title}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        ${html}
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if any
    $('#paymentSelectionModal').remove();
    
    // Add new modal to body
    $('body').append(modalHtml);
    
    // Show modal
    $('#paymentSelectionModal').modal('show');
}
</script>

<?php
// Footer'ı dahil et
include __DIR__ . '/../layouts/footer.php';
?> 